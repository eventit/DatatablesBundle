<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Datatable\Column;

class TextColumn extends Column
{
    /**
     * @return string
     */
    public function getTypeOfField()
    {
        return 'string';
    }
}
