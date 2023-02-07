<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Tests\Column;

use DateTime;
use ReflectionClass;
use ReflectionException;
use Sg\DatatablesBundle\Datatable\Column\ArrayColumn;

/**
 * @internal
 *
 * @coversNothing
 */
final class ArrayColumnTest extends \PHPUnit\Framework\TestCase
{
    public function testIsAssociative()
    {
        $arrayColumn = new ArrayColumn();
        static::assertFalse($this->callMethod($arrayColumn, 'isAssociative', [['a', 'b']]));
        static::assertTrue($this->callMethod($arrayColumn, 'isAssociative', [['a' => 1, 'b' => 1]]));
    }

    public function testArrayToString()
    {
        $arrayColumn = new ArrayColumn();
        $result = $this->callMethod($arrayColumn, 'arrayToString', [['a', 'b' => ['d' => new DateTime()]]]);
        static::assertNotEmpty($result);
        static::assertIsString($result);
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
