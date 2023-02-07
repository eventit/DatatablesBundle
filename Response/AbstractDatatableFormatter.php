<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Response;

use IteratorAggregate;
use Sg\DatatablesBundle\Datatable\Column\ColumnInterface;
use Sg\DatatablesBundle\Datatable\DatatableInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

abstract class AbstractDatatableFormatter
{
    /** @var array */
    protected $output;

    /** @var PropertyAccessor */
    protected $accessor;

    public function __construct()
    {
        $this->output = ['data' => []];

        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    public function runFormatter(IteratorAggregate $entries, DatatableInterface $datatable): void
    {
        $lineFormatter = $datatable->getLineFormatter();
        $columns = $datatable->getColumnBuilder()->getColumns();

        foreach ($entries as $row) {
            $this->doCustomFormatterForRow($row);

            // Format custom DQL fields output ('custom.dql.name' => $row['custom']['dql']['name'] = 'value')
            foreach ($columns as $column) {
                /* @noinspection PhpUndefinedMethodInspection */
                if (true === $column->isCustomDql()) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $columnAlias = str_replace('.', '_', $column->getData());
                    /** @noinspection PhpUndefinedMethodInspection */
                    $columnPath = '[' . str_replace('.', '][', $column->getData()) . ']';
                    /* @noinspection PhpUndefinedMethodInspection */
                    if ($columnAlias !== $column->getData()) {
                        $this->accessor->setValue($row, $columnPath, $row[$columnAlias]);
                        unset($row[$columnAlias]);
                    }
                }
            }

            // 1. Set (if necessary) the custom data source for the Columns with a 'data' option
            foreach ($columns as $column) {
                /** @noinspection PhpUndefinedMethodInspection */
                $dql = $column->getDql();
                /** @noinspection PhpUndefinedMethodInspection */
                $data = $column->getData();

                /* @noinspection PhpUndefinedMethodInspection */
                if (false === $column->isAssociation()) {
                    if (null !== $dql && $dql !== $data && false === \array_key_exists($data, $row)) {
                        $row[$data] = $row[$dql];
                        unset($row[$dql]);
                    }
                }
            }

            // 2. Call the the lineFormatter to format row items
            if (null !== $lineFormatter && \is_callable($lineFormatter)) {
                $row = \call_user_func($datatable->getLineFormatter(), $row);
            }

            /** @var ColumnInterface $column */
            foreach ($columns as $column) {
                // 3. Add some special data to the output array. For example, the visibility of actions.
                $column->addDataToOutputArray($row);
                // 4. Call Columns renderContent method to format row items (e.g. for images or boolean values)
                $column->renderCellContent($row);
            }

            foreach ($columns as $column) {
                if (! $column->getSentInResponse()) {
                    unset($row[$column->getDql()]);
                }
            }

            $this->output['data'][] = $row;
        }
    }

    public function getOutput(): array
    {
        return $this->output;
    }

    abstract protected function doCustomFormatterForRow(array &$row): void;
}
