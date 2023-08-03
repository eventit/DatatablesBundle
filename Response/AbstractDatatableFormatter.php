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

namespace Sg\DatatablesBundle\Response;

use Closure;
use IteratorAggregate;
use Sg\DatatablesBundle\Datatable\Column\ColumnInterface;
use Sg\DatatablesBundle\Datatable\DatatableInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

abstract class AbstractDatatableFormatter
{
    protected array $output = ['data' => []];

    protected PropertyAccessor $accessor;

    public function __construct()
    {
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
                if (false === $column->isAssociation() && (null !== $dql && $dql !== $data && ! \array_key_exists($data, $row))) {
                    $row[$data] = $row[$dql];
                    unset($row[$dql]);
                }
            }

            // 2. Call the the lineFormatter to format row items
            if ($lineFormatter instanceof Closure && \is_callable($lineFormatter)) {
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
