<?php

declare(strict_types=1);

namespace Fiskardex\Storage;

use Fiskardex\Domain\Import\ImportRepository;
use PDO;

final class SqliteImportStore implements ImportRepository
{
    public function __construct(private readonly PDO $connection)
    {
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->exec('PRAGMA foreign_keys = ON');
    }

    public function all(): array
    {
        $statement = $this->connection->query('SELECT * FROM imports ORDER BY created_at DESC');

        return array_map($this->hydrate(...), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function get(string $id): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM imports WHERE id = :id');
        $statement->execute(['id' => $id]);
        $import = $statement->fetch(PDO::FETCH_ASSOC);

        return $import === false ? null : $this->hydrate($import, true);
    }

    public function byHash(string $hash): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM imports WHERE sha256 = :hash');
        $statement->execute(['hash' => $hash]);
        $import = $statement->fetch(PDO::FETCH_ASSOC);

        return $import === false ? null : $this->hydrate($import, true);
    }

    public function save(array $job): void
    {
        $this->connection->beginTransaction();

        try {
            $statement = $this->connection->prepare(
                'INSERT INTO imports (
                    id, original_name, stored_name, mime_type, size_bytes, sha256, kind,
                    status, encoding, period_start, period_end, created_at
                ) VALUES (
                    :id, :originalName, :storedName, :mimeType, :size, :sha256, :kind,
                    :status, :encoding, :periodStart, :periodEnd, :createdAt
                ) ON CONFLICT(id) DO UPDATE SET
                    status = excluded.status,
                    encoding = excluded.encoding,
                    period_start = excluded.period_start,
                    period_end = excluded.period_end',
            );
            $statement->execute([
                'id' => $job['id'],
                'originalName' => $job['originalName'],
                'storedName' => $job['storedName'],
                'mimeType' => $job['mimeType'],
                'size' => $job['size'],
                'sha256' => $job['sha256'],
                'kind' => $job['kind'],
                'status' => $job['status'],
                'encoding' => $job['encoding'],
                'periodStart' => $job['period']['start'] ?? null,
                'periodEnd' => $job['period']['end'] ?? null,
                'createdAt' => $job['createdAt'],
            ]);

            $this->connection
                ->prepare('DELETE FROM efd_records WHERE import_id = :id')
                ->execute(['id' => $job['id']]);
            $this->connection
                ->prepare('DELETE FROM import_issues WHERE import_id = :id')
                ->execute(['id' => $job['id']]);
            $this->saveRecords($job);
            $this->saveIssues($job);
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }
    }

    public function delete(string $id): ?array
    {
        $job = $this->get($id);

        if ($job === null) {
            return null;
        }

        $statement = $this->connection->prepare('DELETE FROM imports WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $job;
    }

    private function saveRecords(array $job): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO efd_records (import_id, line_number, record_type, fields_json, raw_base64)
             VALUES (:importId, :line, :type, :fields, :rawBase64)',
        );

        foreach ($job['records'] as $record) {
            $statement->execute([
                'importId' => $job['id'],
                'line' => $record['line'],
                'type' => $record['type'],
                'fields' => json_encode($record['fields'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'rawBase64' => $record['rawBase64'],
            ]);
        }
    }

    private function saveIssues(array $job): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO import_issues (id, import_id, severity, code, message, location)
             VALUES (:id, :importId, :severity, :code, :message, :location)',
        );

        foreach ($job['issues'] as $issue) {
            $statement->execute([
                'id' => $issue['id'],
                'importId' => $job['id'],
                'severity' => $issue['severity'],
                'code' => $issue['code'],
                'message' => $issue['message'],
                'location' => $issue['location'] ?? null,
            ]);
        }
    }

    private function hydrate(array $import, bool $includeRecords = false): array
    {
        $id = $import['id'];
        $job = [
            'id' => $id,
            'originalName' => $import['original_name'],
            'storedName' => $import['stored_name'],
            'mimeType' => $import['mime_type'],
            'size' => (int) $import['size_bytes'],
            'sha256' => $import['sha256'],
            'status' => $import['status'],
            'kind' => $import['kind'],
            'createdAt' => $import['created_at'],
            'encoding' => $import['encoding'],
            'period' => ['start' => $import['period_start'], 'end' => $import['period_end']],
            'recordCounts' => $this->recordCounts($id),
            'records' => $includeRecords ? $this->records($id) : [],
            'documents' => [],
            'issues' => $this->issues($id),
        ];

        return $job;
    }

    private function recordCounts(string $id): array
    {
        $statement = $this->connection->prepare(
            'SELECT record_type, COUNT(*) AS total FROM efd_records WHERE import_id = :id GROUP BY record_type',
        );
        $statement->execute(['id' => $id]);
        $counts = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $record) {
            $counts[$record['record_type']] = (int) $record['total'];
        }

        return $counts;
    }

    private function records(string $id): array
    {
        $statement = $this->connection->prepare(
            'SELECT line_number, record_type, fields_json, raw_base64
             FROM efd_records WHERE import_id = :id ORDER BY line_number',
        );
        $statement->execute(['id' => $id]);

        return array_map(
            static fn (array $record): array => [
                'line' => (int) $record['line_number'],
                'type' => $record['record_type'],
                'fields' => json_decode($record['fields_json'], true, 512, JSON_THROW_ON_ERROR),
                'rawBase64' => $record['raw_base64'],
            ],
            $statement->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    private function issues(string $id): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, severity, code, message, location FROM import_issues WHERE import_id = :id ORDER BY rowid',
        );
        $statement->execute(['id' => $id]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
