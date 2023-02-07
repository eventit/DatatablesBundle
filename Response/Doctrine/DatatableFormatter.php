<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Response\Doctrine;

use Sg\DatatablesBundle\Response\AbstractDatatableFormatter;

class DatatableFormatter extends AbstractDatatableFormatter
{
    protected function doCustomFormatterForRow(array &$row): void
    {
        if (isset($row[0])) {
            $row = array_merge($row, $row[0]);
            unset($row[0]);
        }
    }
}
