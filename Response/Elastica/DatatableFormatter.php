<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Response\Elastica;

use Sg\DatatablesBundle\Response\AbstractDatatableFormatter;

class DatatableFormatter extends AbstractDatatableFormatter
{
    protected function doCustomFormatterForRow(array &$row): void
    {
        // nothing to do here right now
    }
}
