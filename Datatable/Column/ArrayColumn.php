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

namespace Sg\DatatablesBundle\Datatable\Column;

use DateTime;

class ArrayColumn extends Column
{
    public function renderSingleField(array &$row): static
    {
        $row[$this->data] = $this->arrayToString($row[$this->data] ?? []);

        return parent::renderSingleField($row);
    }

    protected function arrayToString(array $array, int $tab = 0): string
    {
        $isArrayAssociative = $this->isAssociative($array);

        $arrayField = '';
        foreach ($array as $key => $arrayElement) {
            $arrayField = str_repeat('&nbsp&nbsp', $tab);

            if ($isArrayAssociative) {
                $arrayField .= $key . ': ';
            }

            if (\is_array($arrayElement)) {
                $arrayField .= '[<br/>';
                $arrayField .= $this->arrayToString($arrayElement, $tab + 1);
                $arrayField .= ']<br/>';

                continue;
            }

            if ($arrayElement instanceof DateTime) {
                $arrayField .= $arrayElement->format('Y-m-d') . '<br/>';

                continue;
            }

            $arrayField .= $arrayElement . '<br/>';
        }

        return $arrayField;
    }

    protected function isAssociative(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, \count($array) - 1);
    }
}
