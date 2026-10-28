<?php

declare(strict_types=1);

namespace Fiskardex\Http\Controller;

use PDO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class KardexController
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function runs(Request $request, array $match): Response
    {
        $rows = $this->db->query(
            'SELECT r.id, r.import_id, r.algorithm_version, r.source_sha256, r.status, r.accepted_quantity_base,
                r.accepted_count, r.exception_count, r.created_at, i.original_name, i.period_start, i.period_end
             FROM kardex_runs r JOIN imports i ON i.id = r.import_id
             ORDER BY r.created_at DESC',
        )->fetchAll(PDO::FETCH_ASSOC);

        return new JsonResponse($rows);
    }

    public function movements(Request $request, array $match): Response
    {
        $run = $this->db->prepare('SELECT id, import_id FROM kardex_runs WHERE id = ?');
        $run->execute([$match['id']]);
        $runInfo = $run->fetch(PDO::FETCH_ASSOC);
        if (!$runInfo) {
            return new JsonResponse(['title' => 'Not found'], 404);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $perPage = min(100, max(10, $request->query->getInt('per_page', 50)));
        $search = trim((string) $request->query->get('q', ''));
        $query = $this->db->prepare(
            'SELECT m.*, c.fields_json AS item_fields, h.fields_json AS document_fields,
                p.fields_json AS product_fields
             FROM kardex_movements m
             JOIN efd_records c ON c.import_id = m.import_id AND c.line_number = m.source_line
             JOIN efd_records h ON h.import_id = m.import_id AND h.line_number = m.parent_c100_line
             JOIN efd_records p ON p.import_id = m.import_id AND p.line_number = (
                SELECT line_number FROM efd_records
                WHERE import_id = m.import_id AND record_type = \'0200\'
                    AND json_extract(fields_json, \'$[2]\') = m.product_code
                ORDER BY line_number LIMIT 1
             )
             WHERE m.run_id = :run ORDER BY m.source_line',
        );
        $query->execute(['run' => $match['id']]);
        $all = [];
        $totals = [
            'itemValue' => 0,
            'discount' => 0,
            'icmsBase' => 0,
            'icmsValue' => 0,
            'icmsStBase' => 0,
            'icmsStValue' => 0,
        ];
        $partners = $this->partners($runInfo['import_id']);
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $item = json_decode($row['item_fields'], true, 512, JSON_THROW_ON_ERROR);
            $document = json_decode($row['document_fields'], true, 512, JSON_THROW_ON_ERROR);
            $product = json_decode($row['product_fields'], true, 512, JSON_THROW_ON_ERROR);
            $view = [
                'id' => $row['id'],
                'date' => $row['movement_date'],
                'documentNumber' => $document[8] ?? '',
                'partner' => $partners[$document[4] ?? ''] ?? ($document[4] ?? ''),
                'productCode' => $row['product_code'],
                'product' => $product[3] ?? '',
                'ncm' => $product[8] ?? '',
                'unitOriginal' => $row['unit_original'],
                'quantityOriginal' => $row['quantity_original'],
                'unitBase' => $row['unit_base'],
                'quantityBase' => $row['quantity_base'],
                'factor' => $row['conversion_factor'],
                'itemValue' => $row['item_value_original'],
                'discount' => $item[8] ?? '0',
                'cst' => $item[10] ?? '',
                'cfop' => $item[11] ?? '',
                'icmsBase' => $item[13] ?? '0',
                'icmsRate' => $item[14] ?? '0',
                'icmsValue' => $item[15] ?? '0',
                'icmsStBase' => $item[16] ?? '0',
                'icmsStValue' => $item[18] ?? '0',
                'sourceLine' => (int) $row['source_line'],
                'parentLine' => (int) $row['parent_c100_line'],
            ];
            $haystack = mb_strtolower(implode(' ', [$view['documentNumber'], $view['partner'], $view['productCode'], $view['product'], $view['cfop']]));
            if ($search === '' || str_contains($haystack, mb_strtolower($search))) {
                $all[] = $view;
                foreach (array_keys($totals) as $field) {
                    $totals[$field] += $this->toCents($view[$field]);
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
            'monetaryTotals' => array_map($this->fromCents(...), $totals),
        ]);
    }

    public function exceptions(Request $request, array $match): Response
    {
        $check = $this->db->prepare('SELECT 1 FROM kardex_runs WHERE id = ?');
        $check->execute([$match['id']]);
        if (!$check->fetchColumn()) {
            return new JsonResponse(['title' => 'Not found'], 404);
        }
        $statement = $this->db->prepare(
            'SELECT source_line, parent_c100_line, product_code, code, message, details_json
             FROM kardex_exceptions WHERE run_id = ? ORDER BY source_line',
        );
        $statement->execute([$match['id']]);
        $items = array_map(static function (array $row): array {
            $row['details'] = json_decode($row['details_json'], true, 512, JSON_THROW_ON_ERROR);
            unset($row['details_json']);
            return $row;
        }, $statement->fetchAll(PDO::FETCH_ASSOC));

        return new JsonResponse($items);
    }

    private function partners(string $importId): array
    {
        $statement = $this->db->prepare(
            'SELECT fields_json FROM efd_records WHERE import_id = ? AND record_type = \'0150\' ORDER BY line_number',
        );
        $statement->execute([$importId]);
        $partners = [];
        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $fieldsJson) {
            $fields = json_decode($fieldsJson, true, 512, JSON_THROW_ON_ERROR);
            $partners[$fields[2] ?? ''] = $fields[3] ?? ($fields[2] ?? '');
        }
        return $partners;
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
}
