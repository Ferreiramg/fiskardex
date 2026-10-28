<?php

declare(strict_types=1);

namespace Fiskardex\Database;

use PDO;

final class Migrator
{
    public function __construct(
        private readonly PDO $connection,
        private readonly string $migrationsDirectory,
    ) {
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->exec('PRAGMA foreign_keys = ON');
    }

    public function migrate(): int
    {
        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version TEXT PRIMARY KEY,
                applied_at TEXT NOT NULL
            )',
        );

        $applied = $this->connection
            ->query('SELECT version FROM schema_migrations')
            ->fetchAll(PDO::FETCH_COLUMN);
        $migrations = glob($this->migrationsDirectory . '/*.sql') ?: [];
        sort($migrations);
        $executed = 0;

        foreach ($migrations as $migration) {
            $version = basename($migration);

            if (in_array($version, $applied, true)) {
                continue;
            }

            $sql = file_get_contents($migration);

            if ($sql === false) {
                throw new \RuntimeException(sprintf('Não foi possível ler a migration %s.', $version));
            }

            $this->connection->beginTransaction();

            try {
                $this->connection->exec($sql);
                $statement = $this->connection->prepare(
                    'INSERT INTO schema_migrations (version, applied_at) VALUES (:version, :appliedAt)',
                );
                $statement->execute([
                    'version' => $version,
                    'appliedAt' => date(DATE_ATOM),
                ]);
                $this->connection->commit();
                $executed++;
            } catch (\Throwable $exception) {
                $this->connection->rollBack();

                throw $exception;
            }
        }

        return $executed;
    }
}
