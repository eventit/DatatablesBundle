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

namespace Sg\DatatablesBundle\Response\Doctrine;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Exception;
use RuntimeException;
use Sg\DatatablesBundle\Datatable\Filter\AbstractFilter;
use Sg\DatatablesBundle\Datatable\Filter\FilterInterface;
use Sg\DatatablesBundle\Response\AbstractDatatableQueryBuilder;

/**
 * @todo: remove phpcs warnings
 */
class DatatableQueryBuilder extends AbstractDatatableQueryBuilder
{
    protected array $joins = [];

    /**
     * Flag indicating state of query cache for records retrieval. This value is passed to Query object when it is
     * prepared. Default value is false.
     */
    protected bool $useQueryCache = false;

    /**
     * Flag indicating state of query cache for records counting. This value is passed to Query object when it is
     * created. Default value is false.
     */
    protected bool $useCountQueryCache = false;

    /**
     * Arguments to pass when configuring result cache on query for records retrieval. Those arguments are used when
     * calling useResultCache method on Query object when one is created.
     */
    protected array $useResultCacheArgs = [false];

    /**
     * Arguments to pass when configuring result cache on query for counting records. Those arguments are used when
     * calling useResultCache method on Query object when one is created.
     */
    protected array $useCountResultCacheArgs = [false];

    /**
     * @deprecated no longer used by internal code
     */
    public function buildQuery(): static
    {
        return $this;
    }

    public function getQb(): ?QueryBuilder
    {
        return $this->qb;
    }

    public function setQb(QueryBuilder $qb): static
    {
        $this->qb = $qb;

        return $this;
    }

    /**
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
     * @throws Exception
     */
    public function execute(): Query
    {
        $qb = $this->getBuiltQb();

        $query = $qb->getQuery();
        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY)->useQueryCache($this->useQueryCache);
        \call_user_func_array(static fn (bool $useCache, ?int $lifetime = null, ?string $resultCacheId = null): Query => $query->useResultCache($useCache, $lifetime, $resultCacheId), $this->useResultCacheArgs);

        return $query;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getCountAllResults(): int
    {
        $qb = clone $this->qb;
        $qb->select('count(distinct ' . $this->entityShortName . '.' . $this->rootEntityIdentifier . ')');
        $qb->resetDQLPart('orderBy');
        $this->setJoins($qb);

        $query = $qb->getQuery();
        $query->useQueryCache($this->useCountQueryCache);
        \call_user_func_array(static fn (bool $useCache, ?int $lifetime = null, ?string $resultCacheId = null): Query => $query->useResultCache($useCache, $lifetime, $resultCacheId), $this->useCountResultCacheArgs);

        return $qb->getDQLPart('groupBy')
            ? \count($query->getResult())
            : (int) $query->getSingleScalarResult();
    }

    public function useQueryCache(bool $bool): static
    {
        $this->useQueryCache = $bool;

        return $this;
    }

    public function useCountQueryCache(bool $bool): static
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
    public function useResultCache(bool $bool, ?int $lifetime = null, ?string $resultCacheId = null): static
    {
        $this->useResultCacheArgs = \func_get_args();

        return $this;
    }

    /**
     * Set wheter or not to cache result of records counting query and if so, for how long and under which ID. Method is
     * consistent with {@see \Doctrine\ORM\AbstractQuery::useResultCache} method.
     *
     * @param bool        $bool          flag defining whether use caching or not
     * @param int|null    $lifetime      lifetime of cache in seconds
     * @param string|null $resultCacheId string identifier for result cache if left empty ID will be generated by Doctrine
     *
     * @return $this
     */
    public function useCountResultCache(bool $bool, ?int $lifetime = null, ?string $resultCacheId = null): static
    {
        $this->useCountResultCacheArgs = \func_get_args();

        return $this;
    }

    protected function loadIndividualConstructSettings(): void
    {
        $this->qb = $this->em->createQueryBuilder()->from($this->entityName, $this->entityShortName);
        $this->selectColumns = [];
        $this->searchColumns = [];
        $this->orderColumns = [];
        $this->joins = [];
    }

    /**
     * @throws Exception
     */
    protected function initColumnArrays(): static
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

                while (\count($parts) > 1) {
                    $previousPart = $currentPart;
                    $previousAlias = $currentAlias;

                    $currentPart = array_shift($parts);
                    $currentAlias = ($previousPart === $this->entityShortName ? '' : $previousPart . '_') . $currentPart;
                    $currentAlias = $this->getSafeName($currentAlias);

                    if (! \array_key_exists($previousAlias . '.' . $currentPart, $this->joins)) {
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
                    $this->accessor->isReadable($column, 'orderColumn')
                    && true === $this->accessor->getValue($column, 'orderable')
                ) {
                    $orderColumns = (array) $this->accessor->getValue($column, 'orderColumn');
                    foreach ($orderColumns as $orderColumn) {
                        $orderParts = explode('.', $orderColumn);
                        if (\count($orderParts) < 2 && (! isset($this->columnNames[$orderColumn]) || null === $this->accessor->getValue($this->columns[$this->columnNames[$orderColumn]], 'customDql'))) {
                            $orderColumn = $this->entityShortName . '.' . $orderColumn;
                        }
                        $this->orderColumns[$key][] = $orderColumn;
                    }
                } else {
                    $this->orderColumns[] = null;
                }

                // Add Search-Field for VirtualColumn
                if (
                    $this->accessor->isReadable($column, 'searchColumn')
                    && true === $this->accessor->getValue($column, 'searchable')
                ) {
                    $searchColumns = (array) $this->accessor->getValue($column, 'searchColumn');
                    foreach ($searchColumns as $searchColumn) {
                        $searchParts = explode('.', $searchColumn);
                        if (\count($searchParts) < 2) {
                            $searchColumn = $this->entityShortName . '.' . $searchColumn;
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

    protected function setSelectFrom(QueryBuilder $qb): static
    {
        foreach ($this->selectColumns as $key => $value) {
            if (! empty($key)) {
                $qb->addSelect('partial ' . $key . '.{' . implode(',', $value) . '}');
            } else {
                $qb->addSelect($value);
            }
        }

        return $this;
    }

    protected function setJoins(QueryBuilder $qb): static
    {
        foreach ($this->joins as $key => $value) {
            $qb->{$value['type']}($key, $value['alias']);
        }

        return $this;
    }

    protected function setWhere(QueryBuilder $qb): static
    {
        if (isset($this->requestParams['search']) && '' !== $this->requestParams['search']['value']) {
            $orExpr = $qb->expr()->orX();

            $globalSearch = $this->requestParams['search']['value'];
            $globalSearchType = $this->options->getGlobalSearchType();

            foreach ($this->columns as $key => $column) {
                if ($this->isSearchableColumn($column)) {
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
                if ($this->isSearchableColumn($column)) {
                    if (! \array_key_exists('columns', $this->requestParams)) {
                        continue;
                    }
                    if (! \array_key_exists($key, $this->requestParams['columns'])) {
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

    protected function setOrderBy(QueryBuilder $qb): static
    {
        if (isset($this->requestParams['order']) && (is_countable($this->requestParams['order']) ? \count($this->requestParams['order']) : 0)) {
            $counter = is_countable($this->requestParams['order']) ? \count($this->requestParams['order']) : 0;

            for ($i = 0; $i < $counter; ++$i) {
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
     * @throws Exception
     */
    protected function setLimit(QueryBuilder $qb): static
    {
        if (true === $this->features->getPaging() || null === $this->features->getPaging()) {
            if (isset($this->requestParams['start']) && self::DISABLE_PAGINATION !== $this->requestParams['length']) {
                $qb->setFirstResult($this->requestParams['start'])->setMaxResults($this->requestParams['length']);
            }
        } elseif ($this->ajax->getPipeline() > 0) {
            throw new RuntimeException('DatatableQueryBuilder::setLimit(): For disabled paging, the ajax Pipeline-Option must be turned off.');
        }

        return $this;
    }

    /**
     * @author Gaultier Boniface <https://github.com/wysow>
     *
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

    protected function addSelectColumn(string $columnTableName, string $data): static
    {
        if (isset($this->selectColumns[$columnTableName])) {
            if (! \in_array($data, $this->selectColumns[$columnTableName], true)) {
                $this->selectColumns[$columnTableName][] = $data;
            }
        } else {
            $this->selectColumns[$columnTableName][] = $data;
        }

        return $this;
    }

    protected function addOrderColumn(object $column, string $columnTableName, string $data): static
    {
        true === $this->accessor->getValue($column, 'orderable') ?
            $this->orderColumns[] = ($columnTableName !== '' && $columnTableName !== '0' ? $columnTableName . '.' : '') . $data :
            $this->orderColumns[] = null;

        return $this;
    }

    protected function addSearchColumn(object $column, string $columnTableName, string $data): static
    {
        true === $this->accessor->getValue($column, 'searchable') ?
            $this->searchColumns[] = ($columnTableName !== '' && $columnTableName !== '0' ? $columnTableName . '.' : '') . $data :
            $this->searchColumns[] = null;

        return $this;
    }

    /**
     * Add search/order column.
     */
    protected function addSearchOrderColumn(object $column, string $columnTableName, string $data): static
    {
        $this->addOrderColumn($column, $columnTableName, $data);
        $this->addSearchColumn($column, $columnTableName, $data);

        return $this;
    }

    /**
     * Add join.
     */
    protected function addJoin(string $columnTableName, string $alias, string $type): static
    {
        $this->joins[$columnTableName] = [
            'alias' => $alias,
            'type' => $type,
        ];

        return $this;
    }

    /**
     * @throws Exception
     */
    protected function getMetadata(string $entityName): ClassMetadata
    {
        try {
            $metadata = $this->em->getMetadataFactory()->getMetadataFor($entityName);
        } catch (MappingException) {
            throw new RuntimeException('DatatableQueryBuilder::getMetadata(): Given object ' . $entityName . ' is not a Doctrine Entity.');
        }

        return $metadata;
    }

    protected function getEntityShortName(ClassMetadata $metadata): string
    {
        $entityShortName = strtolower($metadata->getReflectionClass()?->getShortName());

        return $this->getSafeName($entityShortName);
    }

    /**
     * Get safe name.
     */
    protected function getSafeName($name): string
    {
        try {
            $reservedKeywordsList = $this->em->getConnection()->getDatabasePlatform()?->getReservedKeywordsList();
            $isReservedKeyword = $reservedKeywordsList->isKeyword($name);
        } catch (DBALException) {
            $isReservedKeyword = false;
        }

        return $isReservedKeyword ? "_{$name}" : $name;
    }
}
