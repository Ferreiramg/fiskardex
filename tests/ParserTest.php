<?php

declare(strict_types=1);

namespace Fiskardex\Tests;

use Fiskardex\Fiscal\EfdParser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    public function testEfdParserReadsPeriodAndCounts(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'fiskardex');

        self::assertNotFalse($file);
        file_put_contents($file, "|0000|020|0|01072026|31072026|\n|C100|0|1|\n|C170|1|ABC|\n|9999|4|\n");

        $result = (new EfdParser())->parse($file, ['issues' => []]);

        self::assertSame('01072026', $result['period']['start']);
        self::assertSame(1, $result['recordCounts']['C100']);
        self::assertSame('completed', $result['status']);

        unlink($file);
    }
}
