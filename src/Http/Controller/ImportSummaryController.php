<?php

declare(strict_types=1);

namespace Fiskardex\Http\Controller;

use PDO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ImportSummaryController
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function show(Request $request, array $match): Response
    {
        $importQuery = $this->db->prepare('SELECT * FROM imports WHERE id = ? AND kind = \'efd\'');
        $importQuery->execute([$match['id']]);
        $import = $importQuery->fetch(PDO::FETCH_ASSOC);
        if (!$import) {
            return new JsonResponse(['title' => 'Importação EFD não encontrada'], 404);
        }

        $recordsQuery = $this->db->prepare(
            'SELECT line_number, record_type, fields_json FROM efd_records WHERE import_id = ? ORDER BY line_number',
        );
        $recordsQuery->execute([$match['id']]);
        $taxpayer = null;
        $declaredRecordCount = null;
        $document = null;
        $documents = [
            'entries' => $this->emptyDocuments(),
            'exits' => $this->emptyDocuments(),
        ];
        $analytical = [
            'entries' => $this->emptyAnalytics(),
            'exits' => $this->emptyAnalytics(),
        ];
        $apurationPeriod = null;
        $apurations = [];
        $obligations = [];
        $exitC170Count = 0;

        foreach ($recordsQuery->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $fields = json_decode($row['fields_json'], true, 512, JSON_THROW_ON_ERROR);
            switch ($row['record_type']) {
                case '0000':
                    $taxpayer = [
                        'name' => $fields[6] ?? '',
                        'cnpj' => $fields[7] ?? '',
                        'stateRegistration' => $fields[10] ?? '',
                        'profile' => $fields[14] ?? '',
                        'periodStart' => $this->date($fields[4] ?? ''),
                        'periodEnd' => $this->date($fields[5] ?? ''),
                    ];
                    break;
                case '9999':
                    $declaredRecordCount = isset($fields[2]) && $fields[2] !== '' ? (int) $fields[2] : null;
                    break;
                case 'C100':
                    $operation = trim($fields[2] ?? '');
                    $status = trim($fields[6] ?? '');
                    $direction = $operation === '0' ? 'entries' : ($operation === '1' ? 'exits' : null);
                    $regular = $direction !== null && $status === '00';
                    $document = [
                        'direction' => $direction,
                        'regular' => $regular,
                        'hasItems' => false,
                    ];
                    if ($regular) {
                        $documents[$direction]['count']++;
                        $documents[$direction]['value'] += $this->toCents($fields[12] ?? '');
                        $documents[$direction]['icmsBase'] += $this->toCents($fields[21] ?? '');
                        $documents[$direction]['icms'] += $this->toCents($fields[22] ?? '');
                        $documents[$direction]['icmsStBase'] += $this->toCents($fields[23] ?? '');
                        $documents[$direction]['icmsSt'] += $this->toCents($fields[24] ?? '');
                        $documents[$direction]['ipi'] += $this->toCents($fields[25] ?? '');
                    }
                    break;
                case 'C170':
                    if (($document['regular'] ?? false) && ($document['direction'] ?? null) === 'exits') {
                        $exitC170Count++;
                    }
                    if ($document !== null) {
                        $document['hasItems'] = true;
                    }
                    break;
                case 'C190':
                    if (!($document['regular'] ?? false)) {
                        break;
                    }
                    $side = $document['direction'];
                    $analytical[$side]['operationValue'] += $this->toCents($fields[5] ?? '');
                    $analytical[$side]['icmsBase'] += $this->toCents($fields[6] ?? '');
                    $analytical[$side]['icms'] += $this->toCents($fields[7] ?? '');
                    $analytical[$side]['icmsStBase'] += $this->toCents($fields[8] ?? '');
                    $analytical[$side]['icmsSt'] += $this->toCents($fields[9] ?? '');
                    $analytical[$side]['ipi'] += $this->toCents($fields[11] ?? '');
                    break;
                case 'E100':
                    $apurationPeriod = [
                        'start' => $this->date($fields[2] ?? ''),
                        'end' => $this->date($fields[3] ?? ''),
                    ];
                    break;
                case 'E110':
                    $apurations[] = [
                        'period' => $apurationPeriod,
                        'debits' => $this->money($fields[2] ?? ''),
                        'entryCredits' => $this->money($fields[6] ?? ''),
                        'balanceDue' => $this->money($fields[11] ?? ''),
                        'deductions' => $this->money($fields[12] ?? ''),
                        'payable' => $this->money($fields[13] ?? ''),
                        'creditBalanceForward' => $this->money($fields[14] ?? ''),
                        'specialDebits' => $this->money($fields[15] ?? ''),
                    ];
                    break;
                case 'E116':
                    $obligations[] = [
                        'code' => $fields[2] ?? '',
                        'amount' => $this->money($fields[3] ?? ''),
                        'dueDate' => $this->date($fields[4] ?? ''),
                        'revenueCode' => $fields[5] ?? '',
                        'sourceLine' => (int) $row['line_number'],
                    ];
                    break;
            }
        }

        $actualRecordCount = (int) $this->countRecords($match['id']);
        $coverageComplete = $declaredRecordCount !== null && $actualRecordCount >= $declaredRecordCount;
        $notes = [];
        if ($documents['exits']['count'] > 0 && $exitC170Count === 0) {
            $notes[] = 'A escrituração não possui C170 para as saídas; os valores de saída são apresentados pelos totais analíticos C190, sem detalhamento de produto.';
        }
        if (!$coverageComplete) {
            $notes[] = 'O arquivo importado tem menos registros disponíveis que o total declarado no 9999. Os totais podem estar incompletos; é necessário reparsar o arquivo original para obter a escrituração integral.';
        }

        return new JsonResponse([
            'source' => [
                'id' => $import['id'],
                'filename' => $import['original_name'],
                'sha256' => $import['sha256'],
                'recordsAvailable' => $actualRecordCount,
                'recordsDeclared' => $declaredRecordCount,
                'coverageComplete' => $coverageComplete,
            ],
            'taxpayer' => $taxpayer,
            'documents' => [
                'entries' => $this->formatDocuments($documents['entries']),
                'exits' => $this->formatDocuments($documents['exits']),
            ],
            'analytical' => [
                'entries' => $this->formatAnalytics($analytical['entries']),
                'exits' => $this->formatAnalytics($analytical['exits']),
            ],
            'apurations' => $apurations,
            'obligations' => $obligations,
            'exitC170Count' => $exitC170Count,
            'notes' => $notes,
        ]);
    }

    public function documents(Request $request, array $match): Response
    {
        $import = $this->db->prepare('SELECT 1 FROM imports WHERE id = ? AND kind = \'efd\'');
        $import->execute([$match['id']]);
        if (!$import->fetchColumn()) {
            return new JsonResponse(['title' => 'Importação EFD não encontrada'], 404);
        }

        $direction = $request->query->get('direction', 'exits') === 'entries' ? '0' : '1';
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = min(100, max(10, $request->query->getInt('per_page', 50)));
        $search = trim((string) $request->query->get('q', ''));
        $query = $this->db->prepare(
            'SELECT line_number, fields_json FROM efd_records
             WHERE import_id = :import AND record_type = \'C100\'
                AND json_extract(fields_json, \'$[2]\') = :direction
                AND json_extract(fields_json, \'$[6]\') = \'00\'
             ORDER BY line_number',
        );
        $query->execute(['import' => $match['id'], 'direction' => $direction]);
        $all = [];
        $totals = ['documentValue' => 0, 'icmsBase' => 0, 'icms' => 0, 'icmsStBase' => 0, 'icmsSt' => 0];
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $fields = json_decode($row['fields_json'], true, 512, JSON_THROW_ON_ERROR);
            $entry = [
                'line' => (int) $row['line_number'],
                'date' => $this->date($fields[11] ?? '') ?? $this->date($fields[10] ?? ''),
                'model' => $fields[5] ?? '',
                'series' => $fields[7] ?? '',
                'number' => $fields[8] ?? '',
                'status' => $fields[6] ?? '',
                'documentValue' => $this->money($fields[12] ?? ''),
                'icmsBase' => $this->money($fields[21] ?? ''),
                'icms' => $this->money($fields[22] ?? ''),
                'icmsStBase' => $this->money($fields[23] ?? ''),
                'icmsSt' => $this->money($fields[24] ?? ''),
                'key' => $fields[9] ?? '',
            ];
            if ($search === '' || str_contains(mb_strtolower(implode(' ', [$entry['number'], $entry['model'], $entry['key']])), mb_strtolower($search))) {
                $all[] = $entry;
                foreach (array_keys($totals) as $field) {
                    $totals[$field] += $this->toCents($entry[$field]);
                }
            }
        }
        $total = count($all);
        return new JsonResponse([
            'items' => array_slice($all, ($page - 1) * $perPage, $perPage),
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'pages' => (int) ceil($total / $perPage),
            'totals' => array_map($this->fromCents(...), $totals),
        ]);
    }

    private function emptyDocuments(): array
    {
        return ['count' => 0, 'value' => 0, 'icmsBase' => 0, 'icms' => 0, 'icmsStBase' => 0, 'icmsSt' => 0, 'ipi' => 0];
    }

    private function formatDocuments(array $documentTotals): array
    {
        return array_map(
            fn (int $value): string => $this->fromCents($value),
            array_intersect_key($documentTotals, array_flip(['value', 'icmsBase', 'icms', 'icmsStBase', 'icmsSt', 'ipi'])),
        ) + ['count' => $documentTotals['count']];
    }

    private function emptyAnalytics(): array
    {
        return ['operationValue' => 0, 'icmsBase' => 0, 'icms' => 0, 'icmsStBase' => 0, 'icmsSt' => 0, 'ipi' => 0];
    }

    private function formatAnalytics(array $values): array
    {
        return array_map($this->fromCents(...), $values);
    }

    private function countRecords(string $importId): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM efd_records WHERE import_id = ?');
        $statement->execute([$importId]);
        return (int) $statement->fetchColumn();
    }

    private function toCents(string $amount): int
    {
        $amount = str_replace(',', '.', trim($amount));
        if (!preg_match('/^(-?)(\d+)(?:\.(\d*))?$/', $amount, $matches)) {
            return 0;
        }
        $fraction = str_pad(substr($matches[3] ?? '', 0, 2), 2, '0');
        $cents = ((int) $matches[2] * 100) + (int) $fraction;
        return ($matches[1] ?? '') === '-' ? -$cents : $cents;
    }

    private function fromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);
        return $sign . intdiv($absolute, 100) . '.' . str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }

    private function money(string $value): string
    {
        return $this->fromCents($this->toCents($value));
    }

    private function date(string $value): ?string
    {
        if (!preg_match('/^(\d{2})(\d{2})(\d{4})$/', $value, $matches)) {
            return null;
        }
        return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
    }
}
