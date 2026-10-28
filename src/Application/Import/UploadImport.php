<?php

declare(strict_types=1);

namespace Fiskardex\Application\Import;

use Fiskardex\Domain\Import\ImportRepository;
use Fiskardex\Fiscal\EfdParser;
use Fiskardex\Fiscal\XmlParser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

final class UploadImport
{
    private const MAX_FILE_SIZE = 104857600;

    public function __construct(
        private readonly ImportRepository $imports,
        private readonly string $uploadsDirectory,
    ) {
    }

    public function __invoke(?UploadedFile $file): array
    {
        if ($file === null || !$file->isValid()) {
            return ['response' => [
                'title' => 'Invalid upload',
                'detail' => 'Envie um arquivo no campo file.',
            ], 'status' => 400];
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return ['response' => ['title' => 'File too large'], 'status' => 413];
        }

        $name = basename((string) $file->getClientOriginalName());
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name) ?: 'upload';
        $id = Uuid::v4()->toRfc4122();
        $stored = $id . '-' . $safeName;
        $path = $this->uploadsDirectory . '/' . $stored;
        $file->move($this->uploadsDirectory, $stored);
        $hash = hash_file('sha256', $path);

        if ($existing = $this->imports->byHash($hash)) {
            unlink($path);

            return ['response' => $existing, 'status' => 200];
        }

        $lower = strtolower($name);
        $kind = str_ends_with($lower, '.xml')
            ? 'nfe-xml'
            : (str_ends_with($lower, '.txt') || str_ends_with($lower, '.efd') ? 'efd' : 'unknown');
        $import = [
            'id' => $id,
            'originalName' => $name,
            'storedName' => $stored,
            'mimeType' => $file->getClientMimeType() ?: 'application/octet-stream',
            'size' => filesize($path),
            'sha256' => $hash,
            'status' => 'processing',
            'kind' => $kind,
            'createdAt' => date(DATE_ATOM),
            'encoding' => 'unknown',
            'period' => [],
            'recordCounts' => [],
            'records' => [],
            'documents' => [],
            'issues' => [],
        ];

        if ($kind === 'efd') {
            $import = (new EfdParser())->parse($path, $import);
        } elseif ($kind === 'nfe-xml') {
            $import = (new XmlParser())->parse($path, $import);
        } else {
            $import['status'] = 'quarantined';
            $import['issues'][] = [
                'id' => Uuid::v4()->toRfc4122(),
                'severity' => 'error',
                'code' => 'UNSUPPORTED_FILE',
                'message' => 'Formato não suportado. Use EFD/TXT ou NF-e XML.',
            ];
        }

        $this->imports->save($import);

        return ['response' => [
            'id' => $id,
            'status' => $import['status'],
            'sha256' => $hash,
        ], 'status' => 202];
    }
}
