<?php

declare(strict_types=1);

namespace Fiskardex\Tests;

use Fiskardex\Database\Migrator;
use Fiskardex\Storage\SqliteImportStore;
use PDO;
use PHPUnit\Framework\TestCase;

final class SqliteImportStoreTest extends TestCase
{
    public function testItStoresAndDeletesAnEfdImport(): void
    {
        $database = tempnam(sys_get_temp_dir(), 'fiskardex-sqlite');

        self::assertNotFalse($database);
        $connection = new PDO('sqlite:' . $database);
        (new Migrator($connection, dirname(__DIR__) . '/database/migrations'))->migrate();
        $store = new SqliteImportStore($connection);
        $job = [
            'id' => 'test-import',
            'originalName' => 'arquivo.efd',
            'storedName' => 'test-import-arquivo.efd',
            'mimeType' => 'text/plain',
            'size' => 42,
            'sha256' => hash('sha256', 'arquivo.efd'),
            'kind' => 'efd',
            'status' => 'completed',
            'encoding' => 'utf-8',
            'period' => ['start' => '01072026', 'end' => '31072026'],
            'createdAt' => '2026-07-31T00:00:00+00:00',
            'records' => [[
                'line' => 1,
                'type' => '0000',
                'fields' => ['', '0000', '020'],
                'rawBase64' => 'fDAwMDB8MDIwfA==',
            ]],
            'documents' => [],
            'issues' => [[
                'id' => 'test-issue',
                'severity' => 'warning',
                'code' => 'TEST_ISSUE',
                'message' => 'Mensagem de teste.',
                'location' => 'line:1',
            ]],
        ];

        $store->save($job);
        $stored = $store->get('test-import');

        self::assertSame('arquivo.efd', $stored['originalName']);
        self::assertSame(1, $stored['recordCounts']['0000']);
        self::assertSame('TEST_ISSUE', $stored['issues'][0]['code']);
        self::assertSame($job['sha256'], $store->byHash($job['sha256'])['sha256']);
        self::assertCount(1, $store->all());
        self::assertSame('test-import', $store->delete('test-import')['id']);
        self::assertNull($store->get('test-import'));

        unset($store, $connection);
        unlink($database);
    }
}
