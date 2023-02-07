<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Response;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Sg\DatatablesBundle\Datatable\Ajax;
use Sg\DatatablesBundle\Datatable\Column\ColumnInterface;
use Sg\DatatablesBundle\Datatable\DatatableInterface;
use Sg\DatatablesBundle\Datatable\Features;
use Sg\DatatablesBundle\Datatable\Options;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

abstract class AbstractDatatableQueryBuilder
{
    /**
     * @internal
     */
    public const DISABLE_PAGINATION = -1;

    /**
     * @internal
     */
    public const INIT_PARAMETER_COUNTER = 100;

    /** @var array */
    protected $requestParams;

    /** @var EntityManagerInterface */
    protected $em;

    /** @var string */
    protected $entityName;

    /** @var string */
    protected $entityShortName;

    /** @var ClassMetadata */
    protected $metadata;

    protected $rootEntityIdentifier;

    /** @var QueryBuilder */
    protected $qb;

    /** @var PropertyAccessor */
    protected $accessor;

    /** @var array */
    protected $columns;

    /** @var array */
    protected $columnNames;

    /** @var array */
    protected $selectColumns = [];

    /** @var array */
    protected $searchColumns = [];

    /** @var array */
    protected $searchColumnGroups = [];

    /** @var array */
    protected $orderColumns = [];

    /** @var Options */
    protected $options;

    /** @var Features */
    protected $features;

    /** @var Ajax */
    protected $ajax;

    /**
     * @throws Exception
     */
    public function __construct(array $requestParams, DatatableInterface $datatable)
    {
        $this->requestParams = $requestParams;

        $this->em = $datatable->getEntityManager();
        $this->entityName = $datatable->getEntity();

        $this->metadata = $this->getMetadata($this->entityName);
        $this->entityShortName = $this->getEntityShortName($this->metadata);

        $this->rootEntityIdentifier = $this->getIdentifier($this->metadata);

        $this->loadIndividualConstructSettings();

        $this->accessor = PropertyAccess::createPropertyAccessor();

        $this->columns = $datatable->getColumnBuilder()->getColumns();
        $this->columnNames = $datatable->getColumnBuilder()->getColumnNames();

        $this->options = $datatable->getOptions();
        $this->features = $datatable->getFeatures();
        $this->ajax = $datatable->getAjax();

        $this->initColumnArrays();
    }

    abstract public function getCountAllResults(): int;

    abstract protected function loadIndividualConstructSettings();

    abstract protected function initColumnArrays();

    abstract protected function getEntityShortName(ClassMetadata $metadata): string;

    /**
     * @param string $entityName
     *
     * @throws Exception
     */
    protected function getMetadata($entityName): ClassMetadata
    {
        try {
            $metadata = $this->em->getMetadataFactory()->getMetadataFor($entityName);
        } catch (MappingException $e) {
            throw new Exception('DatatableQueryBuilder::getMetadata(): Given object ' . $entityName . ' is not a Doctrine Entity.');
        }

        return $metadata;
    }

    abstract protected function getSafeName($name): string;

    protected function getIdentifier(ClassMetadata $metadata)
    {
        $identifiers = $metadata->getIdentifierFieldNames();

        return array_shift($identifiers);
    }

    protected function isSearchableColumn(ColumnInterface $column): bool
    {
        $searchColumn = null !== $this->accessor->getValue($column, 'dql')
            && true === $this->accessor->getValue($column, 'searchable')
        ;

        if (false === $this->options->isSearchInNonVisibleColumns()) {
            return $searchColumn && true === $this->accessor->getValue($column, 'visible');
        }

        return $searchColumn;
    }

    protected function isIndividualFiltering(): bool
    {
        return true === $this->accessor->getValue($this->options, 'individualFiltering');
    }

    protected function isSearchColumnGroupFiltering(): bool
    {
        return true === $this->accessor->getValue($this->options, 'searchColumnGroupFiltering');
    }

    /**
     * @param int|string $key
     *
     * @return $this
     */
    protected function addSearchColumnGroupEntry(ColumnInterface $column, $key): self
    {
        /** @var string|null $searchColumnGroup */
        $searchColumnGroup = $this->accessor->getValue($column, 'searchColumnGroup');
        if (null !== $searchColumnGroup && '' !== $searchColumnGroup) {
            if (! isset($this->searchColumnGroups[$searchColumnGroup])
                || ! \is_array($this->searchColumnGroups[$searchColumnGroup])
            ) {
                $this->searchColumnGroups[$searchColumnGroup] = [];
            }
            if (! \in_array($key, $this->searchColumnGroups[$searchColumnGroup], true)) {
                $this->searchColumnGroups[$searchColumnGroup][] = $key;
            }
        }

        return $this;
    }

    protected function getColumnSearchColumnGroup(ColumnInterface $column): string
    {
        if ($this->isSearchColumnGroupFiltering()) {
            $searchColumnGroup = $column->getSearchColumnGroup();
            if ('' !== $searchColumnGroup && null !== $searchColumnGroup
                && isset($this->searchColumnGroups[$searchColumnGroup])
            ) {
                return (string) $searchColumnGroup;
            }
        }

        return '';
    }
}
