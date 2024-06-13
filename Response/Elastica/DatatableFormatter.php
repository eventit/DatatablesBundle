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

namespace Sg\DatatablesBundle\Response\Elastica;

use Sg\DatatablesBundle\Response\AbstractDatatableFormatter;

class DatatableFormatter extends AbstractDatatableFormatter
{
    protected function doCustomFormatterForRow(array &$row): void
    {
        // nothing to do here right now
    }
}
