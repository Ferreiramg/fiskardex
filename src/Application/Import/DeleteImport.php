<?php

declare(strict_types=1);

namespace Fiskardex\Application\Import;

use Fiskardex\Domain\Import\ImportRepository;

final class DeleteImport
{
    public function __construct(
        private readonly ImportRepository $imports,
        private readonly string $uploadsDirectory,
    ) {
    }

    public function __invoke(string $id): ?array
    {
        $import = $this->imports->delete($id);
        if ($import === null) {
            return null;
        }

        $path = $this->uploadsDirectory . '/' . basename($import['storedName']);
        if (is_file($path)) {
            unlink($path);
        }

        return $import;
    }
}
