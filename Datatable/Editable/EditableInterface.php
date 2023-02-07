<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Datatable\Editable;

/**
 * Interface EditableInterface.
 */
interface EditableInterface
{
    /**
     * @return string
     */
    public function getType();

    /**
     * Checks whether the object may be editable.
     *
     * @return bool
     */
    public function callEditableIfClosure(array $row = []);

    /**
     * @return string
     */
    public function getPk();

    /**
     * @return string
     */
    public function getEmptyText();
}
