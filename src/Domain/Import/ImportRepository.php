<?php

declare(strict_types=1);

namespace Fiskardex\Domain\Import;

interface ImportRepository
{
    public function all(): array;

    public function get(string $id): ?array;

    public function byHash(string $hash): ?array;

    public function save(array $import): void;

    public function delete(string $id): ?array;
}
