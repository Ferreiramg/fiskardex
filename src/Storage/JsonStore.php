<?php

declare(strict_types=1);

namespace Fiskardex\Storage;

final class JsonStore
{
    public function __construct(private readonly string $file)
    {
    }

    public function all(): array
    {
        if (!file_exists($this->file)) {
            return [];
        }

        return json_decode((string) file_get_contents($this->file), true) ?: [];
    }

    public function get(string $id): ?array
    {
        foreach ($this->all() as $job) {
            if ($job['id'] === $id) {
                return $job;
            }
        }

        return null;
    }

    public function byHash(string $hash): ?array
    {
        foreach ($this->all() as $job) {
            if ($job['sha256'] === $hash) {
                return $job;
            }
        }

        return null;
    }

    public function save(array $job): void
    {
        $items = $this->all();
        $found = false;

        foreach ($items as $index => $item) {
            if ($item['id'] === $job['id']) {
                $items[$index] = $job;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $items[] = $job;
        }

        file_put_contents(
            $this->file,
            json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX,
        );
    }
}
