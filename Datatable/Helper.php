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

namespace Sg\DatatablesBundle\Datatable;

class Helper
{
    /**
     * Generate a unique ID.
     */
    public static function generateUniqueID(string $prefix = ''): string
    {
        $id = sha1(microtime(true) . random_int(10000, 90000));

        return $prefix !== '' && $prefix !== '0' ? $prefix . '-' . $id : $id;
    }

    /**
     * Returns a array notated property path for the Accessor.
     */
    public static function getDataPropertyPath(string $data, ?string &$value = null): string
    {
        // handle nested array case
        if (\is_int(strpos($data, '['))) {
            $before = strstr($data, '[', true);
            $value = strstr($data, ']', false);

            // remove needle
            $value = str_replace('].', '', $value);
            // format value
            $value = '[' . str_replace('.', '][', $value) . ']';

            if (false !== $before) {
                $data = $before;
            }
        }

        // e.g. 'createdBy.allowed' => [createdBy][allowed]
        return '[' . str_replace('.', '][', $data) . ']';
    }

    /**
     * Returns object notated property path.
     */
    public static function getPropertyPathObjectNotation(string $path, int $key, string $value): string
    {
        $objectValue = str_replace(['][', '[', ']'], ['.', '', ''], $value);

        return str_replace(['[', ']'], '', $path) . '[' . $key . '].' . $objectValue;
    }
}
