<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * (c) stwe <https://github.com/stwe/DatatablesBundle>
 * (c) event it AG <https://github.com/eventit/DatatablesBundle>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sg\DatatablesBundle\Tests\Column;

use DateTime;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Sg\DatatablesBundle\Datatable\Column\ArrayColumn;

/**
 * @internal
 *
 * @coversNothing
 */
final class ArrayColumnTest extends TestCase
{
    public function testIsAssociative(): void
    {
        $arrayColumn = new ArrayColumn();
        self::assertFalse($this->callMethod($arrayColumn, 'isAssociative', [['a', 'b']]));
        self::assertTrue($this->callMethod($arrayColumn, 'isAssociative', [['a' => 1, 'b' => 1]]));
    }

    public function testArrayToString(): void
    {
        $arrayColumn = new ArrayColumn();
        $result = $this->callMethod($arrayColumn, 'arrayToString', [['a', 'b' => ['d' => new DateTime()]]]);
        self::assertNotEmpty($result);
        self::assertIsString($result);
    }

    /**
     * @throws ReflectionException
     */
    public static function callMethod($obj, $name, array $args)
    {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }
}
