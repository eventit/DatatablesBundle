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

/**
 * Interface ColumnInterface.
 */
interface ColumnInterface
{
    /**
     * @var int
     */
    public const LAST_POSITION = -1;

    /**
     * Validates $dql. Normally a non-empty string is expected.
     */
    public function dqlConstraint($dql): bool;

    /**
     * Specifies whether only a single column of this type is allowed (example: MultiselectColumn).
     */
    public function isUnique(): bool;

    /**
     * Checks whether an association is given.
     */
    public function isAssociation(): bool;

    /**
     * Checks whether a toMany association is given.
     */
    public function isToManyAssociation(): bool;

    /**
     * Use the column data value in SELECT statement.
     * Normally is it true. In case of virtual Column, multi select column or data is null is it false.
     */
    public function isSelectColumn(): bool;

    /**
     * Get the template, in which all DataTables-Columns-Options set.
     */
    public function getOptionsTemplate(): string;

    /**
     * Sometimes it is necessary to add some special data to the output array.
     * For example, the visibility of actions.
     */
    public function addDataToOutputArray(array &$row): static;

    /**
     * Render images or any other special content.
     * This function works similar to the DataTables Plugin 'columns.render'.
     */
    public function renderCellContent(array &$row): static;

    /**
     * Render single field.
     */
    public function renderSingleField(array &$row): static;

    /**
     * Render toMany.
     */
    public function renderToMany(array &$row): static;

    /**
     * Get the template for the 'renderCellContent' function.
     */
    public function getCellContentTemplate(): string;

    /**
     * Implementation of the 'Draw Event' - fired once the table has completed a draw.
     * With this function can javascript execute after drawing the whole table.
     * Used - for example - for the Editable function.
     */
    public function renderPostCreateDatatableJsContent(): ?string;

    /**
     * The allowed Column positions as array.
     */
    public function allowedPositions(): ?array;

    /**
     * Returns the Column type.
     */
    public function getColumnType(): string;

    /**
     * Does special content need to be rendered for editable?
     */
    public function isEditableContentRequired(array $row): bool;

    /**
     * Get type of field.
     */
    public function getTypeOfField(): ?string;
}
