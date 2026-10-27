<?php

declare(strict_types=1);

namespace Fiskardex\Fiscal;

use Symfony\Component\Uid\Uuid;

final class XmlParser
{
    public function parse(string $path, array $job): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($path, 'SimpleXMLElement', LIBXML_NONET);

        if ($xml === false) {
            $job['status'] = 'quarantined';
            $job['issues'][] = $this->issue('error', 'XML_INVALID', 'XML inválido ou não processável.');

            return $job;
        }

        $information = $xml->NFe->infNFe ?? $xml->infNFe ?? null;

        if (!$information) {
            $job['status'] = 'quarantined';
            $job['issues'][] = $this->issue('error', 'NFE_ROOT_NOT_FOUND', 'Estrutura NF-e não identificada.');

            return $job;
        }

        $attributes = $information->attributes();
        $items = [];

        foreach ($information->det as $detail) {
            $attributesDetail = $detail->attributes();
            $items[] = [
                'itemNumber' => (string) ($attributesDetail['nItem'] ?? ''),
                'productCode' => (string) ($detail->prod->cProd ?? ''),
                'description' => (string) ($detail->prod->xProd ?? ''),
                'quantity' => (string) ($detail->prod->qCom ?? ''),
                'unit' => (string) ($detail->prod->uCom ?? ''),
                'total' => (string) ($detail->prod->vProd ?? ''),
            ];
        }

        $job['documents'][] = [
            'id' => Uuid::v4()->toRfc4122(),
            'model' => (string) ($information->ide->mod ?? ''),
            'key' => str_replace('NFe', '', (string) ($attributes['Id'] ?? '')),
            'number' => (string) ($information->ide->nNF ?? ''),
            'date' => (string) ($information->ide->dhEmi ?? $information->ide->dEmi ?? ''),
            'direction' => 'unknown',
            'items' => $items,
            'source' => 'xml',
        ];
        $job['recordCounts'] = ['NFE' => 1, 'DET' => count($items)];
        $job['encoding'] = 'utf-8';
        $job['status'] = $job['issues'] ? 'completed_with_issues' : 'completed';

        return $job;
    }

    private function issue(string $severity, string $code, string $message): array
    {
        return [
            'id' => Uuid::v4()->toRfc4122(),
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
        ];
    }
}
