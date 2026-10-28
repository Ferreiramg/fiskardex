<?php

declare(strict_types=1);

namespace Fiskardex\Tests;

use Fiskardex\Application\Kardex\ProcessEfdEntries;
use Fiskardex\Database\Migrator;
use Fiskardex\Http\Controller\KardexController;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class KardexEntriesTest extends TestCase
{
    public function testNormalizesEntryAndIsIdempotentForRepeatedConversionAndReimport(): void
    {
        [$db, $service] = $this->fixture([
            ['0200', 'SKU-1', 'Produto', 'UN'], ['0220', 'CX', '12'], ['0220', 'CX', '12'],
            ['C100', '0', '01072026'], ['C170', '1', 'SKU-1', '1', 'CX', '120,00'],
            ['C100', '0', '02072026'], ['C170', '1', 'SKU-1', '0,5', 'CX', '60,00'],
            ['C100', '0', '03072026'], ['C170', '1', 'SKU-1', '2', 'UN', '20,00'],
        ]);
        $first = $service->execute('import-1');
        $again = $service->execute('import-1'); // Same source hash and algorithm version.
        $moves = $service->movements($first['id']);

        self::assertSame($first['id'], $again['id']);
        self::assertSame(3, $first['accepted_count']);
        self::assertSame('20', $first['accepted_quantity_base']);
        self::assertSame(['12', '6', '2'], array_column($moves, 'quantity_base'));
        self::assertSame(['120', '60', '20'], array_column($moves, 'item_value_original'));
        self::assertSame('1', $moves[2]['conversion_factor']);
        self::assertSame('CX', $moves[0]['unit_original']);
        self::assertSame('UN', $moves[0]['unit_base']);
        self::assertSame(5, $moves[0]['source_line']);
        self::assertSame(4, $moves[0]['parent_c100_line']);
        self::assertSame('|C170|', substr(base64_decode($moves[0]['source_record_base64']), 0, 6));
        self::assertSame('|C100|', substr(base64_decode($moves[0]['parent_record_base64']), 0, 6));
        self::assertSame(3, (int) $db->query('SELECT COUNT(*) FROM kardex_movements')->fetchColumn());
    }

    public function testConflictingAndMissingFactorsAreReportedAndNeverCreateMovements(): void
    {
        [, $service] = $this->fixture([
            ['0200', 'SKU-1', 'Produto', 'UN'], ['0220', 'CX', '12'], ['0220', 'CX', '10'],
            ['C100', '0', '01072026'], ['C170', '1', 'SKU-1', '1', 'CX', '120,00'],
            ['C100', '0', '02072026'], ['C170', '2', 'SKU-1', '2', 'PCT', '30,00'],
        ]);
        $run = $service->execute('import-1');
        self::assertSame(0, $run['accepted_count']);
        self::assertSame(2, $run['exception_count']);
        self::assertSame(['CONVERSION_CONFLICT', 'CONVERSION_MISSING'], array_column($run['exceptions'], 'code'));
        self::assertCount(0, $service->movements($run['id']));
    }

    public function testInvalidConversionFactorProducesPendingException(): void
    {
        [, $service] = $this->fixture([
            ['0200', 'SKU-1', 'Produto', 'UN'], ['0220', 'CX', '0'],
            ['C100', '0', '01072026'], ['C170', '1', 'SKU-1', '1', 'CX', '120,00'],
        ]);
        $run = $service->execute('import-1');
        self::assertSame('CONVERSION_INVALID', $run['exceptions'][0]['code']);
    }

    public function testKardexViewerApiReturnsSpreadsheetColumnsAndSourceReferences(): void
    {
        [$db, $service] = $this->fixture([
            ['0200', 'SKU-1', 'Produto de teste', 'UN'],
            ['C100', '0', '01072026'],
            ['C170', '1', 'SKU-1', '2', 'UN', '25,50'],
        ]);
        $run = $service->execute('import-1');
        $controller = new KardexController($db);
        $response = $controller->movements(Request::create('/'), ['id' => $run['id']]);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1, $data['total']);
        self::assertSame('Produto de teste', $data['items'][0]['product']);
        self::assertSame('25.5', $data['items'][0]['itemValue']);
        self::assertSame(3, $data['items'][0]['sourceLine']);
        self::assertSame(2, $data['items'][0]['parentLine']);
    }

    private function fixture(array $specs): array
    {
        $db = new PDO('sqlite::memory:');
        (new Migrator($db, dirname(__DIR__) . '/database/migrations'))->migrate();
        $db->exec("INSERT INTO imports (id, original_name, stored_name, mime_type, size_bytes, sha256, kind, status, created_at) VALUES ('import-1','sample.efd','sample.efd','text/plain',1,'source-hash','efd','completed','2026-01-01T00:00:00Z')");
        $insert = $db->prepare('INSERT INTO efd_records (import_id, line_number, record_type, fields_json, raw_base64) VALUES (?, ?, ?, ?, ?)');
        $line = 0;
        $parentItem = 0;
        foreach ($specs as $spec) {
            $line++;
            $type = array_shift($spec);
            $values = $spec;
            if ($type === '0200') {
                $fields = ['', '0200', $values[0], $values[1], '', '', $values[2]];
            } elseif ($type === '0220') {
                $fields = ['', '0220', $values[0], $values[1]];
            } elseif ($type === 'C100') {
                $fields = ['', 'C100', $values[0], '', '', '', '00', '', '', '', $values[1]];
            } else {
                $fields = ['', 'C170', $values[0], $values[1], '', $values[2], $values[3], $values[4]];
            }
            $raw = '|' . implode('|', array_slice($fields, 1)) . '|';
            $insert->execute(['import-1', $line, $type, json_encode($fields, JSON_THROW_ON_ERROR), base64_encode($raw)]);
        }
        return [$db, new ProcessEfdEntries($db)];
    }
}
