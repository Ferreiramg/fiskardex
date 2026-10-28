<?php

declare(strict_types=1);

namespace Fiskardex\Application\Kardex;

use Fiskardex\Domain\Kardex\Decimal;
use PDO;
use Symfony\Component\Uid\Uuid;

/** Builds one immutable, versioned Kardex run from the persisted EFD source records. */
final class ProcessEfdEntries
{
    public const VERSION = '1.0.0';

    public function __construct(private readonly PDO $db)
    {
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('PRAGMA foreign_keys = ON');
    }

    public function execute(string $importId): array
    {
        $importQuery = $this->db->prepare('SELECT id, sha256 FROM imports WHERE id = :id AND kind = \'efd\'');
        $importQuery->execute(['id' => $importId]);
        $import = $importQuery->fetch(PDO::FETCH_ASSOC);
        if (!$import) {
            throw new \InvalidArgumentException('Importação EFD não encontrada.');
        }

        $existing = $this->db->prepare('SELECT * FROM kardex_runs WHERE import_id = :id AND algorithm_version = :version');
        $existing->execute(['id' => $importId, 'version' => self::VERSION]);
        $run = $existing->fetch(PDO::FETCH_ASSOC);
        if ($run) {
            return $this->report($run);
        }

        $records = $this->records($importId);
        $runId = Uuid::v4()->toRfc4122();
        $movementRows = [];
        $exceptions = [];
        $document = null;
        $products = [];
        $conversions = [];
        $quantities = [];

        $conversionProduct = null;
        foreach ($records as $record) {
            $f = $record['fields'];
            if ($record['type'] === '0200') {
                $code = $f[2] ?? '';
                $products[$code][] = ['record' => $record, 'unit' => strtoupper(trim($f[6] ?? ''))];
                $conversionProduct = $code;
            } elseif ($record['type'] === '0220') {
                if ($conversionProduct !== null) {
                    $conversions[$conversionProduct][] = $record;
                }
            } elseif (in_array($record['type'], ['0205', '0206', '0209'], true)) {
                continue;
            } elseif ($record['type'] !== '') {
                $conversionProduct = null;
            }
        }

        foreach ($records as $record) {
            $f = $record['fields'];
            if ($record['type'] === 'C100') {
                $document = [
                    'record' => $record,
                    'operation' => trim($f[2] ?? ''),
                    'status' => trim($f[6] ?? ''),
                    'date' => $this->date($f[10] ?? ''),
                ];
                continue;
            }
            if ($record['type'] !== 'C170' || $document === null || $document['operation'] !== '0') {
                continue;
            }

            $line = $record['line'];
            $parent = $document['record'];
            $code = trim($f[3] ?? '');
            $sourceUnit = strtoupper(trim($f[6] ?? ''));
            $quantity = $this->decimal($f[5] ?? '');
            $value = $this->decimal($f[7] ?? '');
            $issue = null;
            if (in_array($document['status'], ['02', '03', '04', '05', '06', '07', '08', '10'], true)) {
                $issue = ['CANCELLED_DOCUMENT', 'Documento com situação de cancelamento ou denegação requer análise.', ['status' => $document['status']]];
            } elseif ($quantity === null || Decimal::scaled($quantity) <= 0) {
                $issue = ['INVALID_QUANTITY', 'Quantidade original ausente, inválida ou não positiva.', []];
            } elseif ($value === null) {
                $issue = ['INVALID_ITEM_VALUE', 'Valor original do item ausente ou inválido.', []];
            } elseif (count($products[$code] ?? []) !== 1) {
                $issue = [empty($products[$code]) ? 'PRODUCT_0200_MISSING' : 'PRODUCT_0200_AMBIGUOUS', 'Não há um único registro 0200 para o código do item.', ['matches' => count($products[$code] ?? [])]];
            }

            $product = count($products[$code] ?? []) === 1 ? $products[$code][0] : null;
            if ($issue === null && $product !== null) {
                $baseUnit = $product['unit'];
                if ($baseUnit === '') {
                    $issue = ['BASE_UNIT_MISSING', 'Unidade de inventário do 0200 está ausente.', []];
                } elseif ($sourceUnit === $baseUnit) {
                    $factor = '1';
                    $conversionRecord = null;
                } else {
                    $candidates = [];
                    foreach ($conversions[$code] ?? [] as $candidate) {
                        $cf = $candidate['fields'];
                        if (strtoupper(trim($cf[2] ?? '')) !== $sourceUnit) {
                            continue;
                        }
                        $candidateFactor = $this->decimal($cf[3] ?? '');
                        if ($candidateFactor === null || Decimal::scaled($candidateFactor) <= 0) {
                            $candidates['invalid'][] = $candidate;
                        } else {
                            $candidates[$candidateFactor][] = $candidate;
                        }
                    }
                    $validFactors = array_values(array_filter(array_keys($candidates), static fn ($k) => $k !== 'invalid'));
                    if (isset($candidates['invalid'])) {
                        $issue = ['CONVERSION_INVALID', 'Há fator 0220 inválido para a unidade original.', ['conversionLines' => array_column($candidates['invalid'], 'line')]];
                    } elseif (count($validFactors) === 0) {
                        $issue = ['CONVERSION_MISSING', 'Não existe fator 0220 aprovado para a unidade original.', []];
                    } elseif (count($validFactors) > 1) {
                        $issue = ['CONVERSION_CONFLICT', 'Há fatores 0220 conflitantes para a mesma unidade original.', ['factors' => $validFactors]];
                    } else {
                        $factor = (string) $validFactors[0];
                        $conversionRecord = $candidates[$factor][0];
                    }
                }
            }

            if ($issue !== null) {
                $exceptions[] = ['line' => $line, 'parentLine' => $parent['line'], 'product' => $code, 'code' => $issue[0], 'message' => $issue[1], 'details' => $issue[2]];
                continue;
            }

            $baseQuantity = Decimal::multiply($quantity, $factor);
            if (Decimal::scaled($baseQuantity) <= 0) {
                $exceptions[] = ['line' => $line, 'parentLine' => $parent['line'], 'product' => $code, 'code' => 'INVALID_CONVERTED_QUANTITY', 'message' => 'Quantidade convertida não positiva.', 'details' => []];
                continue;
            }
            $movementRows[] = [
                'id' => Uuid::v4()->toRfc4122(), 'line' => $line, 'parent' => $parent['line'],
                'item' => trim($f[2] ?? ''), 'product' => $code, 'date' => $document['date'],
                'original' => $quantity, 'sourceUnit' => $sourceUnit, 'base' => $baseQuantity,
                'baseUnit' => $product['unit'], 'factor' => $factor, 'value' => $value,
                'sourceRaw' => $record['rawBase64'], 'parentRaw' => $parent['rawBase64'],
                'productRaw' => $product['record']['rawBase64'],
                'conversionRaw' => $conversionRecord['rawBase64'] ?? null,
            ];
            $quantities[] = Decimal::scaled($baseQuantity);
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare('INSERT INTO kardex_runs (id, import_id, algorithm_version, source_sha256, status, accepted_quantity_base, accepted_count, exception_count, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$runId, $importId, self::VERSION, $import['sha256'], $exceptions ? 'completed_with_exceptions' : 'completed', Decimal::format(array_sum($quantities)), count($movementRows), count($exceptions), date(DATE_ATOM)]);
            $movement = $this->db->prepare('INSERT INTO kardex_movements (id, run_id, import_id, source_line, parent_c100_line, item_number, product_code, movement_date, quantity_original, unit_original, quantity_base, unit_base, conversion_factor, item_value_original, source_record_base64, parent_record_base64, product_record_base64, conversion_record_base64) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            foreach ($movementRows as $m) {
                $movement->execute([$m['id'], $runId, $importId, $m['line'], $m['parent'], $m['item'], $m['product'], $m['date'], $m['original'], $m['sourceUnit'], $m['base'], $m['baseUnit'], $m['factor'], $m['value'], $m['sourceRaw'], $m['parentRaw'], $m['productRaw'], $m['conversionRaw']]);
            }
            $exceptionInsert = $this->db->prepare('INSERT INTO kardex_exceptions (id, run_id, source_line, parent_c100_line, product_code, code, message, details_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            foreach ($exceptions as $e) {
                $exceptionInsert->execute([Uuid::v4()->toRfc4122(), $runId, $e['line'], $e['parentLine'], $e['product'], $e['code'], $e['message'], json_encode($e['details'], JSON_THROW_ON_ERROR)]);
            }
            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
        return $this->getRun($runId);
    }

    public function exceptions(string $runId): array
    {
        $statement = $this->db->prepare('SELECT source_line, parent_c100_line, product_code, code, message, details_json FROM kardex_exceptions WHERE run_id = ? ORDER BY source_line');
        $statement->execute([$runId]);
        return array_map(static function (array $row): array {
            $row['details'] = json_decode($row['details_json'], true, 512, JSON_THROW_ON_ERROR);
            unset($row['details_json']);
            return $row;
        }, $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function movements(string $runId): array
    {
        $statement = $this->db->prepare('SELECT * FROM kardex_movements WHERE run_id = ? ORDER BY source_line');
        $statement->execute([$runId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getRun(string $id): array
    {
        $statement = $this->db->prepare('SELECT * FROM kardex_runs WHERE id = ?');
        $statement->execute([$id]);
        return $this->report($statement->fetch(PDO::FETCH_ASSOC));
    }

    private function report(array $run): array
    {
        $run['accepted_count'] = (int) $run['accepted_count'];
        $run['exception_count'] = (int) $run['exception_count'];
        $run['exceptions'] = $this->exceptions($run['id']);
        return $run;
    }

    private function records(string $importId): array
    {
        $q = $this->db->prepare('SELECT line_number, record_type, fields_json, raw_base64 FROM efd_records WHERE import_id = ? ORDER BY line_number');
        $q->execute([$importId]);
        return array_map(static fn (array $r): array => ['line' => (int) $r['line_number'], 'type' => $r['record_type'], 'fields' => json_decode($r['fields_json'], true, 512, JSON_THROW_ON_ERROR), 'rawBase64' => $r['raw_base64']], $q->fetchAll(PDO::FETCH_ASSOC));
    }

    private function decimal(string $value): ?string
    {
        try {
            return Decimal::format(Decimal::scaled($value));
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private function date(string $value): ?string
    {
        if (!preg_match('/^(\d{2})(\d{2})(\d{4})$/', $value, $m)) {
            return null;
        }
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
}
