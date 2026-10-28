#!/usr/bin/env php
<?php

declare(strict_types=1);

use Fiskardex\Fiscal\EfdParser;
use Fiskardex\Storage\SqliteImportStore;

require dirname(__DIR__) . '/vendor/autoload.php';

$importId = $argv[1] ?? '';
if ($importId === '') {
    fwrite(STDERR, "Uso: php bin/reparse-efd.php <import-id>\n");
    exit(2);
}

$root = dirname(__DIR__);
$databasePath = getenv('APP_DATABASE_PATH') ?: $root . '/var/data/fiskardex.sqlite';
$connection = new PDO('sqlite:' . $databasePath);
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$connection->exec('PRAGMA foreign_keys = ON');
$query = $connection->prepare('SELECT * FROM imports WHERE id = :id AND kind = \'efd\'');
$query->execute(['id' => $importId]);
$stored = $query->fetch(PDO::FETCH_ASSOC);
if (!$stored) {
    fwrite(STDERR, "Importação EFD não encontrada.\n");
    exit(1);
}

$sourcePath = $root . '/var/data/uploads/' . $stored['stored_name'];
if (!is_file($sourcePath) || hash_file('sha256', $sourcePath) !== $stored['sha256']) {
    fwrite(STDERR, "O arquivo original não foi encontrado ou o hash não confere.\n");
    exit(1);
}

$job = [
    'id' => $stored['id'],
    'originalName' => $stored['original_name'],
    'storedName' => $stored['stored_name'],
    'mimeType' => $stored['mime_type'],
    'size' => (int) $stored['size_bytes'],
    'sha256' => $stored['sha256'],
    'kind' => $stored['kind'],
    'status' => 'processing',
    'encoding' => $stored['encoding'] ?? 'unknown',
    'period' => [],
    'createdAt' => $stored['created_at'],
    'records' => [],
    'documents' => [],
    'issues' => [],
];

$job = (new EfdParser())->parse($sourcePath, $job);
(new SqliteImportStore($connection))->save($job);

printf(
    "Importação reparsada: %s registros, %d C100, %d C170 e %d C190. Hash original preservado: %s%s",
    array_sum($job['recordCounts']),
    $job['recordCounts']['C100'] ?? 0,
    $job['recordCounts']['C170'] ?? 0,
    $job['recordCounts']['C190'] ?? 0,
    $job['sha256'],
    PHP_EOL,
);
