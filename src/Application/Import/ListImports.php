<?php

declare(strict_types=1);

namespace Fiskardex\Application\Import;

use Fiskardex\Domain\Import\ImportRepository;

final class ListImports
{
    public function __construct(private readonly ImportRepository $imports)
    {
    }

    public function __invoke(): array
    {
        return $this->imports->all();
    }
}
