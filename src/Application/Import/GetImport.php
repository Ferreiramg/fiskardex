<?php

declare(strict_types=1);

namespace Fiskardex\Application\Import;

use Fiskardex\Domain\Import\ImportRepository;

final class GetImport
{
    public function __construct(private readonly ImportRepository $imports)
    {
    }

    public function __invoke(string $id): ?array
    {
        return $this->imports->get($id);
    }
}
