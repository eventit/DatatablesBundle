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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Exception;
use RuntimeException;
use Sg\DatatablesBundle\Datatable\Factory;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment as Twig_Environment;

class ColumnBuilder
{
    /**
     * The generated Columns.
     */
    private array $columns = [];

    /**
     * This variable stores the array of column names as keys and column ids as values
     * in order to perform search column id by name.
     */
    private array $columnNames = [];

    /**
     * Unique Columns.
     */
    private array $uniqueColumns = [];

    /**
     * The fully-qualified class name of the entity (e.g. AppBundle\Entity\Post).
     */
    private string $entityClassName;

    public function __construct(
        private ClassMetadata $metadata,
        private Twig_Environment $twig,
        private RouterInterface $router,
        private string $datatableName,
        private EntityManagerInterface $em
    ) {
        $this->entityClassName = $metadata->getName();
    }

    // -------------------------------------------------
    // Builder
    // -------------------------------------------------
    /**
     * Add Column.
     *
     * @throws Exception
     */
    public function add(?string $dql, ColumnInterface|string $class, array $options = []): static
    {
        $column = Factory::create($class, ColumnInterface::class);
        $column->initOptions();

        $this->handleDqlProperties($dql, $options, $column);
        $this->setEnvironmentProperties($column);
        $column->set($options);

        $this->setTypeProperties($dql, $column);
        $this->addColumn($dql, $column);

        $this->checkUnique();

        return $this;
    }

    /**
     * Remove Column.
     */
    public function remove(?string $dql): static
    {
        foreach ($this->columns as $column) {
            if ($column->getDql() === $dql) {
                $this->removeColumn($dql, $column);

                break;
            }
        }

        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumnNames(): array
    {
        return $this->columnNames;
    }

    /**
     * Get a unique Column by his type.
     */
    public function getUniqueColumn(string $columnType): ?AbstractColumn
    {
        return $this->uniqueColumns[$columnType] ?? null;
    }

    // -------------------------------------------------
    // Helper
    // -------------------------------------------------

    /**
     * @throws Exception
     */
    private function getMetadata(?string $entityName): ClassMetadata
    {
        try {
            $metadata = $this->em->getMetadataFactory()->getMetadataFor($entityName);
        } catch (MappingException) {
            throw new RuntimeException('DatatableQueryBuilder::getMetadata(): Given object ' . $entityName . ' is not a Doctrine Entity.');
        }

        return $metadata;
    }

    /**
     * Get metadata from association.
     */
    private function getMetadataFromAssociation(string $association, ClassMetadata $metadata): ClassMetadata
    {
        $targetClass = $metadata->getAssociationTargetClass($association);

        return $this->getMetadata($targetClass);
    }

    private function setTypeOfField(ClassMetadata $metadata, AbstractColumn $column, string $field): static
    {
        if (null === $column->getTypeOfField()) {
            $column->setTypeOfField($metadata->getTypeOfField($field));
        }

        $column->setOriginalTypeOfField($metadata->getTypeOfField($field));

        return $this;
    }

    /**
     * Handle dql properties.
     */
    private function handleDqlProperties(?string $dql, array $options, AbstractColumn $column): void
    {
        // the Column 'data' property has normally the same value as 'dql'
        $column->setData($dql);

        if (! isset($options['dql'])) {
            $column->setCustomDql(false);
            $column->setDql($dql);
        } else {
            $column->setCustomDql(true);
        }
    }

    /**
     * Set environment properties.
     */
    private function setEnvironmentProperties(AbstractColumn $column): void
    {
        $column->setDatatableName($this->datatableName);
        $column->setEntityClassName($this->entityClassName);
        $column->setTwig($this->twig);
        $column->setRouter($this->router);
    }

    /**
     * Sets some types.
     */
    private function setTypeProperties(?string $dql, AbstractColumn $column): void
    {
        if (null !== $dql && $column->isSelectColumn() && ! $column->isCustomDql()) {
            $metadata = $this->metadata;
            $parts = explode('.', $dql);
            // add associations types
            if ($column->isAssociation()) {
                while (\count($parts) > 1) {
                    $currentPart = array_shift($parts);

                    // @noinspection PhpUndefinedMethodInspection
                    $column->addTypeOfAssociation($metadata->getAssociationMapping($currentPart)['type']);
                    $metadata = $this->getMetadataFromAssociation($currentPart, $metadata);
                }
            } else {
                $column->setTypeOfAssociation(null);
            }

            // set the type of the field
            $this->setTypeOfField($metadata, $column, $parts[0]);
        } else {
            $column->setTypeOfAssociation(null);
            $column->setOriginalTypeOfField(null);
        }
    }

    /**
     * Adds a Column.
     */
    private function addColumn(?string $dql, AbstractColumn $column): void
    {
        if ($column->callAddIfClosure()) {
            $this->columns[] = $column;
            $index = \count($this->columns) - 1;
            $this->columnNames[$dql] = $index;
            $column->setIndex($index);

            // Use the Column-Index as data source for Columns with 'dql' === null
            if (null === $column->getDql() && null === $column->getData()) {
                $column->setData($index);
            }

            if ($column->isUnique()) {
                $this->uniqueColumns[$column->getColumnType()] = $column;
            }
        }
    }

    /**
     * Removes a Column.
     */
    private function removeColumn(string $dql, AbstractColumn $column): void
    {
        // Remove column from columns
        foreach ($this->columns as $k => $c) {
            if ($c === $column) {
                unset($this->columns[$k]);
                $this->columns = array_values($this->columns);

                break;
            }
        }

        // Remove column from columnNames
        if (\array_key_exists($dql, $this->columnNames)) {
            unset($this->columnNames[$dql]);
        }

        // Reindex columnNames
        foreach ($this->columns as $k => $c) {
            $this->columnNames[$c->getDql()] = $k;
        }

        // Remove column from uniqueColumns
        foreach ($this->uniqueColumns as $k => $c) {
            if ($c === $column) {
                unset($this->uniqueColumns[$k]);
                $this->uniqueColumns = array_values($this->uniqueColumns);

                break;
            }
        }
    }

    /**
     * Check unique.
     *
     * @throws Exception
     */
    private function checkUnique(): void
    {
        $unique = $this->uniqueColumns;

        if (\count(array_unique($unique)) < \count($unique)) {
            throw new RuntimeException('ColumnBuilder::checkUnique(): Unique columns are only allowed once.');
        }
    }
}
