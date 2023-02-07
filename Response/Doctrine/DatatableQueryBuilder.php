<?php

/**
 * This file is part of the SgDatatablesBundle package.
 *
 * (c) stwe <https://github.com/stwe/DatatablesBundle>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sg\DatatablesBundle\Response\Doctrine;

use Doctrine\DBAL\Exception as DBALException;
use Sg\DatatablesBundle\Datatable\Column\ColumnInterface;
use Sg\DatatablesBundle\Datatable\Filter\AbstractFilter;
use Sg\DatatablesBundle\Datatable\Filter\FilterInterface;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Sg\DatatablesBundle\Response\AbstractDatatableQueryBuilder;

/**
 * @todo: remove phpcs warnings
 *
 * @package Sg\DatatablesBundle\Response
 */
class DatatableQueryBuilder extends AbstractDatatableQueryBuilder
{
    /**
     * @var array
     */
    protected $joins = [];

    /**
     * Flag indicating state of query cache for records retrieval. This value is passed to Query object when it is
     * prepared. Default value is false
     * @var bool
     */
    protected $useQueryCache = false;

    /**
     * Flag indicating state of query cache for records counting. This value is passed to Query object when it is
     * created. Default value is false
     * @var bool
     */
    protected $useCountQueryCache = false;

    /**
     * Arguments to pass when configuring result cache on query for records retrieval. Those arguments are used when
     * calling useResultCache method on Query object when one is created.
     * @var array
     */
    protected $useResultCacheArgs = [false];

    /**
     * Arguments to pass when configuring result cache on query for counting records. Those arguments are used when
     * calling useResultCache method on Query object when one is created.
     * @var array
     */
    protected $useCountResultCacheArgs = [false];

    protected function loadIndividualConstructSettings()
    {
        $this->qb = $this->em->createQueryBuilder()->from($this->entityName, $this->entityShortName);
        $this->selectColumns = [];
        $this->searchColumns = [];
        $this->orderColumns = [];
        $this->joins = [];
    }

    /**
     * @return AbstractDatatableQueryBuilder
     * @throws Exception
     */
    protected function initColumnArrays()
    {
        foreach ($this->columns as $key => $column) {
            $dql = $this->accessor->getValue($column, 'dql');
            $data = $this->accessor->getValue($column, 'data');

            $currentPart = $this->entityShortName;
            $currentAlias = $currentPart;
            $metadata = $this->metadata;

            if (true === $this->accessor->getValue($column, 'customDql')) {
                $columnAlias = str_replace('.', '_', $data);

                $selectDql = preg_replace('/\{([\w]+)\}/', '$1', $dql);
                $this->addSelectColumn(null, $selectDql . ' ' . $columnAlias);

                $this->addOrderColumn($column, null, $columnAlias);

                $searchDql = preg_replace('/\{([\w]+)\}/', '$1_search', $dql);
                $this->addSearchColumn($column, null, $searchDql);
            } elseif (true === $this->accessor->getValue($column, 'selectColumn')) {
                $parts = explode('.', $dql);

                while (count($parts) > 1) {
                    $previousPart = $currentPart;
                    $previousAlias = $currentAlias;

                    $currentPart = array_shift($parts);
                    $currentAlias = ($previousPart === $this->entityShortName ? '' : $previousPart . '_') . $currentPart;
                    $currentAlias = $this->getSafeName($currentAlias);

                    if (!array_key_exists($previousAlias . '.' . $currentPart, $this->joins)) {
                        $this->addJoin(
                            $previousAlias . '.' . $currentPart,
                            $currentAlias,
                            $this->accessor->getValue($column, 'joinType')
                        );
                    }

                    $metadata = $this->setIdentifierFromAssociation($currentAlias, $currentPart, $metadata);
                }

                $this->addSelectColumn($currentAlias, $this->getIdentifier($metadata));
                $this->addSelectColumn($currentAlias, $parts[0]);
                $this->addSearchOrderColumn($column, $currentAlias, $parts[0]);
            } else {
                // Add Order-Field for VirtualColumn
                if (
                    $this->accessor->isReadable($column, 'orderColumn') &&
                    true === $this->accessor->getValue($column, 'orderable')
                ) {
                    $orderColumns = (array) $this->accessor->getValue($column, 'orderColumn');
                    foreach ($orderColumns as $orderColumn) {
                        $orderParts = explode('.', $orderColumn);
                        if (\count($orderParts) < 2) {
                            if (! isset($this->columnNames[$orderColumn]) || null === $this->accessor->getValue($this->columns[$this->columnNames[$orderColumn]], 'customDql')) {
                                $orderColumn = $this->entityShortName.'.'.$orderColumn;
                            }
                        }
                        $this->orderColumns[$key][] = $orderColumn;
                    }
                } else {
                    $this->orderColumns[] = null;
                }

                // Add Search-Field for VirtualColumn
                if (
                    $this->accessor->isReadable($column, 'searchColumn') &&
                    true === $this->accessor->getValue($column, 'searchable')
                ) {
                    $searchColumns = (array) $this->accessor->getValue($column, 'searchColumn');
                    foreach ($searchColumns as $searchColumn) {
                        $searchParts = explode('.', $searchColumn);
                        if (\count($searchParts) < 2) {
                            $searchColumn = $this->entityShortName.'.'.$searchColumn;
                        }
                        $this->searchColumns[$key][] = $searchColumn;
                    }
                } else {
                    $this->searchColumns[] = null;
                }
            }
        }

        return $this;
    }

    /**
     * @deprecated No longer used by internal code.
     *
     * @return $this
     */
    public function buildQuery()
    {
        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function getQb()
    {
        return $this->qb;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return $this
     */
    public function setQb($qb): self
    {
        $this->qb = $qb;

        return $this;
    }

    /**
     * @return QueryBuilder
     * @throws Exception
     */
    public function getBuiltQb(): QueryBuilder
    {
        $qb = clone $this->qb;

        $this->setSelectFrom($qb);
        $this->setJoins($qb);
        $this->setWhere($qb);
        $this->setOrderBy($qb);
        $this->setLimit($qb);

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return $this
     */
    protected function setSelectFrom(QueryBuilder $qb): self
    {
        foreach ($this->selectColumns as $key => $value) {
            if (false === empty($key)) {
                $qb->addSelect('partial ' . $key . '.{' . implode(',', $value) . '}');
            } else {
                $qb->addSelect($value);
            }
        }

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return $this
     */
    protected function setJoins(QueryBuilder $qb): self
    {
        foreach ($this->joins as $key => $value) {
            $qb->{$value['type']}($key, $value['alias']);
        }

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return $this
     */
    protected function setWhere(QueryBuilder $qb): self
    {
        if (isset($this->requestParams['search']) && '' !== $this->requestParams['search']['value']) {
            $orExpr = $qb->expr()->orX();

            $globalSearch = $this->requestParams['search']['value'];
            $globalSearchType = $this->options->getGlobalSearchType();

            foreach ($this->columns as $key => $column) {
                if (true === $this->isSearchableColumn($column)) {
                    /** @var AbstractFilter $filter */
                    $filter = $this->accessor->getValue($column, 'filter');
                    $searchType = $globalSearchType;
                    $searchFields = (array) $this->searchColumns[$key];
                    $searchValue = $globalSearch;
                    $searchTypeOfField = $column->getTypeOfField();
                    foreach ($searchFields as $searchField) {
                        $orExpr = $filter->addOrExpression($orExpr, $qb, $searchType, $searchField, $searchValue, $searchTypeOfField, $key);
                    }
                }
            }

            if ($orExpr->count() > 0) {
                $qb->andWhere($orExpr);
            }
        }

        // individual filtering
        if (true === $this->accessor->getValue($this->options, 'individualFiltering')) {
            $andExpr = $qb->expr()->andX();

            $parameterCounter = self::INIT_PARAMETER_COUNTER;

            foreach ($this->columns as $key => $column) {
                if (true === $this->isSearchableColumn($column)) {
                    if (false === array_key_exists('columns', $this->requestParams)) {
                        continue;
                    }
                    if (false === array_key_exists($key, $this->requestParams['columns'])) {
                        continue;
                    }

                    $searchValue = $this->requestParams['columns'][$key]['search']['value'];

                    if ('' !== $searchValue && 'null' !== $searchValue) {
                        /** @var FilterInterface $filter */
                        $filter = $this->accessor->getValue($column, 'filter');
                        $searchFields = (array) $this->searchColumns[$key];
                        $searchTypeOfField = $column->getTypeOfField();
                        foreach ($searchFields as $searchField) {
                            $andExpr = $filter->addAndExpression($andExpr, $qb, $searchField, $searchValue, $searchTypeOfField, $parameterCounter);
                        }
                    }
                }
            }

            if ($andExpr->count() > 0) {
                $qb->andWhere($andExpr);
            }
        }

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return $this
     */
    protected function setOrderBy(QueryBuilder $qb): self
    {
        if (isset($this->requestParams['order']) && count($this->requestParams['order'])) {
            $counter = count($this->requestParams['order']);

            for ($i = 0; $i < $counter; $i++) {
                $columnIdx = (int) $this->requestParams['order'][$i]['column'];
                $requestColumn = $this->requestParams['columns'][$columnIdx];

                if ('true' === $requestColumn['orderable']) {
                    $columnNames = (array) $this->orderColumns[$columnIdx];
                    $orderDirection = $this->requestParams['order'][$i]['dir'];

                    foreach ($columnNames as $columnName) {
                        $qb->addOrderBy($columnName, $orderDirection);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return $this
     * @throws Exception
     */
    protected function setLimit(QueryBuilder $qb): self
    {
        if (true === $this->features->getPaging() || null === $this->features->getPaging()) {
            if (isset($this->requestParams['start']) && self::DISABLE_PAGINATION !== $this->requestParams['length']) {
                $qb->setFirstResult($this->requestParams['start'])->setMaxResults($this->requestParams['length']);
            }
        } elseif ($this->ajax->getPipeline() > 0) {
            throw new \RuntimeException('DatatableQueryBuilder::setLimit(): For disabled paging, the ajax Pipeline-Option must be turned off.');
        }

        return $this;
    }

    /**
     * @return Query
     * @throws Exception
     */
    public function execute(): Query
    {
        $qb = $this->getBuiltQb();

        $query = $qb->getQuery();
        $query->setHydrationMode(Query::HYDRATE_ARRAY)->useQueryCache($this->useQueryCache);
        \call_user_func_array([$query, 'useResultCache'], $this->useResultCacheArgs);

        return $query;
    }

    /**
     * @inheritdoc
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCountAllResults(): int
    {
        $qb = clone $this->qb;
        $qb->select('count(distinct ' . $this->entityShortName . '.' . $this->rootEntityIdentifier . ')');
        $qb->resetDQLPart('orderBy');
        $this->setJoins($qb);

        $query = $qb->getQuery();
        $query->useQueryCache($this->useCountQueryCache);
        \call_user_func_array([$query, 'useResultCache'], $this->useCountResultCacheArgs);

        return !$qb->getDQLPart('groupBy')
            ? (int)$query->getSingleScalarResult()
            : \count($query->getResult());
    }

    /**
     * @param bool $bool
     *
     * @return $this
     */
    public function useQueryCache(bool $bool): self
    {
        $this->useQueryCache = $bool;

        return $this;
    }

    /**
     * @param bool $bool
     *
     * @return $this
     */
    public function useCountQueryCache(bool $bool): self
    {
        $this->useCountQueryCache = $bool;

        return $this;
    }

    /**
     * Set wheter or not to cache result of records retrieval query and if so, for how long and under which ID. Method is
     * consistent with {@see \Doctrine\ORM\AbstractQuery::useResultCache} method.
     *
     * @param bool        $bool          flag defining whether use caching or not
     * @param int|null    $lifetime      lifetime of cache in seconds
     * @param string|null $resultCacheId string identifier for result cache if left empty ID will be generated by Doctrine
     *
     * @return $this
     */
    public function useResultCache(bool $bool, ?int $lifetime = null, ?string $resultCacheId = null): self
    {
        $this->useResultCacheArgs = func_get_args();

        return $this;
    }

    /**
     * Set wheter or not to cache result of records counting query and if so, for how long and under which ID. Method is
     * consistent with {@see \Doctrine\ORM\AbstractQuery::useResultCache} method.
     *
     * @param boolean $bool flag defining whether use caching or not
     * @param int|null $lifetime lifetime of cache in seconds
     * @param string|null $resultCacheId string identifier for result cache if left empty ID will be generated by Doctrine
     *
     * @return $this
     */
    public function useCountResultCache(bool $bool, ?int $lifetime = null, ?string $resultCacheId = null): self
    {
        $this->useCountResultCacheArgs = func_get_args();

        return $this;
    }

    /**
     * @author Gaultier Boniface <https://github.com/wysow>
     *
     * @param array|string       $association
     * @param string             $key
     * @param ClassMetadata|null $metadata
     *
     * @return ClassMetadata
     * @throws Exception
     */
    protected function setIdentifierFromAssociation(array|string $association, string $key, ?ClassMetadata $metadata = null): ClassMetadata
    {
        if (null === $metadata) {
            $metadata = $this->metadata;
        }

        $targetEntityClass = $metadata->getAssociationTargetClass($key);
        $targetMetadata = $this->getMetadata($targetEntityClass);
        $this->addSelectColumn($association, $this->getIdentifier($targetMetadata));

        return $targetMetadata;
    }

    /**
     * @param string $columnTableName
     * @param string $data
     *
     * @return $this
     */
    protected function addSelectColumn(string $columnTableName, string $data): self
    {
        if (isset($this->selectColumns[$columnTableName])) {
            if (!in_array($data, $this->selectColumns[$columnTableName], true)) {
                $this->selectColumns[$columnTableName][] = $data;
            }
        } else {
            $this->selectColumns[$columnTableName][] = $data;
        }

        return $this;
    }

    /**
     * @param object $column
     * @param string $columnTableName
     * @param string $data
     *
     * @return $this
     */
    protected function addOrderColumn(object $column, string $columnTableName, string $data): self
    {
        true === $this->accessor->getValue($column, 'orderable') ?
            $this->orderColumns[] = ($columnTableName ? $columnTableName . '.' : '') . $data :
            $this->orderColumns[] = null;

        return $this;
    }

    /**
     * @param object $column
     * @param string $columnTableName
     * @param string $data
     *
     * @return $this
     */
    protected function addSearchColumn(object $column, string $columnTableName, string $data): self
    {
        true === $this->accessor->getValue($column, 'searchable') ?
            $this->searchColumns[] = ($columnTableName ? $columnTableName . '.' : '') . $data :
            $this->searchColumns[] = null;

        return $this;
    }

    /**
     * Add search/order column.
     *
     * @param object $column
     * @param string $columnTableName
     * @param string $data
     *
     * @return $this
     */
    protected function addSearchOrderColumn(object $column, string $columnTableName, string $data): self
    {
        $this->addOrderColumn($column, $columnTableName, $data);
        $this->addSearchColumn($column, $columnTableName, $data);

        return $this;
    }

    /**
     * Add join.
     *
     * @param string $columnTableName
     * @param string $alias
     * @param string $type
     *
     * @return $this
     */
    protected function addJoin(string $columnTableName, string $alias, string $type): self
    {
        $this->joins[$columnTableName] = [
            'alias' => $alias,
            'type' => $type,
        ];

        return $this;
    }

    /**
     * @param string $entityName
     *
     * @return ClassMetadata
     * @throws Exception
     */
    protected function getMetadata($entityName): ClassMetadata
    {
        try {
            $metadata = $this->em->getMetadataFactory()->getMetadataFor($entityName);
        } catch (MappingException $e) {
            throw new \RuntimeException('DatatableQueryBuilder::getMetadata(): Given object ' . $entityName . ' is not a Doctrine Entity.');
        }

        return $metadata;
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    protected function getEntityShortName(ClassMetadata $metadata): string
    {
        $entityShortName = strtolower($metadata->getReflectionClass()?->getShortName());

        return $this->getSafeName($entityShortName);
    }

    /**
     * Get safe name.
     *
     * @param $name
     *
     * @return string
     */
    protected function getSafeName($name): string
    {
        try {
            $reservedKeywordsList = $this->em->getConnection()->getDatabasePlatform()?->getReservedKeywordsList();
            $isReservedKeyword = $reservedKeywordsList->isKeyword($name);
        } catch (DBALException $exception) {
            $isReservedKeyword = false;
        }

        return $isReservedKeyword ? "_{$name}" : $name;
    }
}
