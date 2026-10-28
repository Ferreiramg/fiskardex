<?php

declare(strict_types=1);

namespace Fiskardex\Application\Import;

use Fiskardex\Domain\Import\ImportRepository;

final class GetImportCollection
{
    public function __construct(private readonly ImportRepository $imports)
    {
    }

    public function __invoke(string $id, string $collection): ?array
    {
        $import = $this->imports->get($id);

        return $import === null ? null : ($import[$collection] ?? []);
    }
}
