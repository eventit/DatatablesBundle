<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Datatable;

use Exception;

class Factory
{
    /**
     * Create.
     *
     * @throws Exception
     */
    public static function create($class, $interface)
    {
        if (empty($class) || ! \is_string($class) && ! $class instanceof $interface) {
            throw new Exception("Factory::create(): String or {$interface} expected.");
        }

        if ($class instanceof $interface) {
            return $class;
        }

        if (\is_string($class) && class_exists($class)) {
            $instance = new $class();

            if (! $instance instanceof $interface) {
                throw new Exception("Factory::create(): String or {$interface} expected.");
            }

            return $instance;
        }

        throw new Exception("Factory::create(): {$class} is not callable.");
    }
}
