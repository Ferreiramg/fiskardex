<?php

declare(strict_types=1);

namespace Fiskardex\Fiscal;

use Symfony\Component\Uid\Uuid;

final class EfdParser
{
    public function parse(string $path, array $job): array
    {
        $counts = [];
        $records = [];
        $issues = [];
        $line = 0;
        $encoding = 'utf-8';
        $handle = fopen($path, 'rb');

        while (($raw = fgets($handle)) !== false) {
            $line++;
            $raw = rtrim($raw, "\r\n");

            if ($raw === '') {
                continue;
            }

            $validUtf8 = mb_check_encoding($raw, 'UTF-8');

            if (!$validUtf8) {
                $encoding = 'unknown';
            }

            $fields = explode('|', $raw);
            $type = $fields[1] ?? '';

            if ($type === '') {
                $issues[] = $this->issue(
                    'warning',
                    'RECORD_WITHOUT_TYPE',
                    'Registro sem tipo identificável.',
                    'line:' . $line,
                );
                continue;
            }

            $counts[$type] = ($counts[$type] ?? 0) + 1;

            if (count($records) < 5000) {
                $records[] = [
                    'line' => $line,
                    'type' => $type,
                    'fields' => $validUtf8 ? $fields : [],
                    'rawBase64' => base64_encode($raw),
                ];
            }

            if ($type === '0000') {
                $job['period'] = [
                    'start' => $fields[4] ?? null,
                    'end' => $fields[5] ?? null,
                ];
            }
        }

        fclose($handle);

        if ($encoding === 'unknown') {
            $issues[] = $this->issue(
                'warning',
                'ENCODING_UNCERTAIN',
                'Bytes incompatíveis com UTF-8 foram encontrados; o original foi preservado.',
            );
        }

        $job['encoding'] = $encoding;
        $job['recordCounts'] = $counts;
        $job['records'] = $records;
        $job['issues'] = array_merge($job['issues'], $issues);
        $job['status'] = $issues ? 'completed_with_issues' : 'completed';

        return $job;
    }

    private function issue(string $severity, string $code, string $message, ?string $location = null): array
    {
        return [
            'id' => Uuid::v4()->toRfc4122(),
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'location' => $location,
        ];
    }
}
