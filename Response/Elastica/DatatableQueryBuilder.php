<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Response\Elastica;

use Doctrine\ORM\Mapping\ClassMetadata;
use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Nested;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use FOS\ElasticaBundle\HybridResult;
use FOS\ElasticaBundle\Paginator\PartialResultsInterface;
use Sg\DatatablesBundle\Datatable\Column\ColumnInterface;
use Sg\DatatablesBundle\Datatable\Filter\FilterInterface;
use Sg\DatatablesBundle\Datatable\Filter\SelectFilter;
use Sg\DatatablesBundle\Model\ModelDefinitionInterface;
use Sg\DatatablesBundle\Response\AbstractDatatableQueryBuilder;

abstract class DatatableQueryBuilder extends AbstractDatatableQueryBuilder
{
    public const CONDITION_TYPE_SHOULD = 'should';
    public const CONDITION_TYPE_MUST = 'must';

    public const QUERY_TYPE_TERMS = 'terms';
    public const QUERY_TYPE_MATCH = 'match';
    public const QUERY_TYPE_EXACT_MATCH = 'exact_match';
    public const QUERY_TYPE_REGEXP = 'regexp';

    /** @var PaginatedFinderInterface */
    protected $paginatedFinder;

    /** @var ModelDefinitionInterface */
    protected $modelDefinition;

    /** @var array */
    protected $nestedPaths;

    /** @var array */
    protected $sourceFields;

    public function setPaginatedFinder(PaginatedFinderInterface $paginatedFinder)
    {
        $this->paginatedFinder = $paginatedFinder;
    }

    public function setModelDefinition(ModelDefinitionInterface $modelDefinition)
    {
        $this->modelDefinition = $modelDefinition;
    }

    public function execute(): ElasticaEntries
    {
        $results = $this->getHybridResultsForOffsetAndLength(
            $this->requestParams['start'],
            $this->requestParams['length']
        );

        return $this->generateElasticaEntriesForResults(
            $results->getTotalHits(),
            $this->extractSourceFromResultset($results)
        );
    }

    /**
     * @param array|string[] $fields
     */
    public function getAllResultsForFields(array $fields): ElasticaEntries
    {
        $resultEntries = [];
        $this->sourceFields = $fields;
        $result = $this->getRawResultsForOffsetAndLength(0, 1);
        $countAll = $result->getTotalHits();

        $resultsPerStep = 100;

        for ($i = 0; $i < $countAll / $resultsPerStep; ++$i) {
            $partialResults = $this->getRawResultsForOffsetAndLength(
                $i * $resultsPerStep,
                ($i + 1) * $resultsPerStep
            );
            $resultEntries = $this->extractSourceFromResultset($partialResults, $resultEntries);
        }

        return $this->generateElasticaEntriesForResults($countAll, $resultEntries);
    }

    public function getCountAllResults(): int
    {
        return (int) $this->paginatedFinder->createRawPaginatorAdapter($this->getQuery(true))->getTotalHits();
    }

    abstract protected function setTermsFilters(BoolQuery $query): BoolQuery;

    /** nothing needed more than in abstract */
    protected function loadIndividualConstructSettings()
    {
        $this->nestedPaths = [];
        $this->selectColumns = [];
        $this->searchColumns = [];
        $this->orderColumns = [];
        $this->searchColumnGroups = [];
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    protected function addNestedPath(string $columnAlias, $path): self
    {
        if (null !== $columnAlias && '' !== $columnAlias && null !== $path && false !== strpos($path, '.')) {
            $pathParts = explode('.', $path);
            if (\count($pathParts) > 1) {
                $this->nestedPaths[$columnAlias] =
                    implode('.', \array_slice($pathParts, 0, -1));
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    protected function getNestedPath(string $columnAlias)
    {
        if ('' !== $columnAlias && isset($this->nestedPaths[$columnAlias])) {
            return $this->nestedPaths[$columnAlias];
        }

        return null;
    }

    /**
     * @return $this
     */
    protected function initColumnArrays(): self
    {
        /**
         * @var int|string      $key
         * @var ColumnInterface $column
         */
        foreach ($this->columns as $key => $column) {
            $dql = $this->accessor->getValue($column, 'dql');
            $data = $this->accessor->getValue($column, 'data');

            if ($this->hasCustomDql($column)) {
                $this->addSearchOrderColumn($column, $dql);
            } elseif ($this->isSelectColumn($column)) {
                $this->addSearchOrderColumn($column, $data);
            } else {
                if ($this->accessor->isReadable($column, 'orderColumn')
                    && $this->isOrderableColumn($column)
                ) {
                    $orderColumn = $this->accessor->getValue($column, 'orderColumn');
                    $this->addOrderColumn($column, $orderColumn);
                } else {
                    $this->addOrderColumn($column, null);
                }

                if ($this->accessor->isReadable($column, 'searchColumn')
                    && $this->isSearchableColumn($column)
                ) {
                    $searchColumn = $this->accessor->getValue($column, 'searchColumn');
                    $this->addSearchColumn($column, $searchColumn);
                } else {
                    $this->addSearchColumn($column, null);
                }
            }

            $this->addSearchColumnGroupEntry($column, $key);
        }

        return $this;
    }

    protected function isQueryValid($query): bool
    {
        if (! $query instanceof AbstractQuery) {
            return false;
        }

        if (empty($query->toArray())) {
            return false;
        }

        if (\is_object($query->getParams())) {
            return false;
        }

        return true;
    }

    /**
     * @return $this
     */
    protected function addGlobalFilteringSearchTerms(BoolQuery $query): self
    {
        if (isset($this->requestParams['search']) && '' !== $this->requestParams['search']['value']) {
            /** @var BoolQuery $filterQueries */
            $filterQueries = new BoolQuery();

            $searchValue = $this->requestParams['search']['value'];

            /**
             * @var int|string      $key
             * @var ColumnInterface $column
             */
            foreach ($this->columns as $key => $column) {
                if (true === $this->isSearchableColumn($column)) {
                    /** @var string $columnAlias */
                    $columnAlias = $this->searchColumns[$key];
                    if ('' === $columnAlias || null === $columnAlias) {
                        continue;
                    }

                    /** @var FilterInterface $filter */
                    $filter = $this->accessor->getValue($column, 'filter');

                    if ($filter instanceof SelectFilter) {
                        continue;
                    }

                    $this->addColumnSearchTerm(
                        $filterQueries,
                        self::CONDITION_TYPE_SHOULD,
                        $column,
                        $columnAlias,
                        $searchValue
                    );
                }
            }

            if ($this->isQueryValid($filterQueries)) {
                $query->addFilter($filterQueries);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function addIndividualFilteringSearchTerms(BoolQuery $query): self
    {
        if ($this->isIndividualFiltering()) {
            /** @var BoolQuery $filterQueries */
            $filterQueries = new BoolQuery();

            /**
             * @var int|string      $key
             * @var ColumnInterface $column
             */
            foreach ($this->columns as $key => $column) {
                if (true === $this->isSearchableColumn($column)) {
                    if (false === \array_key_exists('columns', $this->requestParams)) {
                        continue;
                    }
                    if (false === \array_key_exists($key, $this->requestParams['columns'])) {
                        continue;
                    }

                    /** @var string $columnAlias */
                    $columnAlias = $this->searchColumns[$key];
                    if ('' === $columnAlias || null === $columnAlias) {
                        continue;
                    }

                    $searchValue = $this->requestParams['columns'][$key]['search']['value'];

                    if ('' !== $searchValue && 'null' !== $searchValue) {
                        /** @var string $searchColumnGroup */
                        $searchColumnGroup = $this->getColumnSearchColumnGroup($column);

                        if ('' !== $searchColumnGroup) {
                            $this->addColumnGroupSearchTerm(
                                $filterQueries,
                                $searchColumnGroup,
                                $searchValue
                            );
                        } else {
                            $this->addColumnSearchTerm(
                                $filterQueries,
                                self::CONDITION_TYPE_MUST,
                                $column,
                                $columnAlias,
                                $searchValue
                            );
                        }
                    }
                }
            }

            if ($this->isQueryValid($filterQueries)) {
                $query->addFilter($filterQueries);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function addSearchTerms(BoolQuery $query): self
    {
        $this->addGlobalFilteringSearchTerms($query);
        $this->addIndividualFilteringSearchTerms($query);

        return $this;
    }

    /**
     * @param int|string $searchValue
     *
     * @return $this
     */
    protected function addColumnGroupSearchTerm(
        BoolQuery $filterQueries,
        string $searchColumnGroup,
        $searchValue
    ): self {
        /** @var BoolQuery $filterQueries */
        $groupFilterQueries = new BoolQuery();

        /** @var int|string $key */
        foreach ($this->searchColumnGroups[$searchColumnGroup] as $key) {
            $this->addColumnSearchTerm(
                $groupFilterQueries,
                self::CONDITION_TYPE_SHOULD,
                $this->columns[$key],
                $this->searchColumns[$key],
                $searchValue
            );
        }

        if ($this->isQueryValid($groupFilterQueries)) {
            $filterQueries->addMust($groupFilterQueries);
        }

        return $this;
    }

    /**
     * @param int|string|bool $searchValue
     *
     * @return $this
     */
    protected function addColumnSearchTerm(
        BoolQuery $filterQueries,
        string $conditionType,
        ColumnInterface $column,
        string $columnAlias,
        $searchValue
    ): self {
        /** @var AbstractQuery|null $filterSubQuery */
        $filterSubQuery = null;

        switch ($column->getTypeOfField()) {
            case 'boolean':
            case 'integer':
                if (is_numeric($searchValue) || \is_bool($searchValue)) {
                    $filterSubQuery = $this->createIntegerFilterTerm(
                        $columnAlias,
                        (int) $searchValue
                    );
                }
                break;
            case 'string':
                /** @var array|null $searchValues */
                $searchValues = null;

                /** @var string $queryType */
                $queryType = self::QUERY_TYPE_MATCH;

                /** @var FilterInterface $filter */
                $filter = $this->accessor->getValue($column, 'filter');

                if ($filter instanceof SelectFilter) {
                    $queryType = self::QUERY_TYPE_EXACT_MATCH;

                    if (true === $filter->isMultiple()) {
                        $searchValues = explode(',', $searchValue);
                    }
                }

                if (\is_array($searchValues) && \count($searchValues) > 1) {
                    $filterSubQuery = $this->createStringMultiFilterTerm(
                        $columnAlias,
                        $queryType,
                        self::CONDITION_TYPE_SHOULD,
                        (array) $searchValues
                    );
                } else {
                    $filterSubQuery = $this->createStringFilterTerm(
                        $columnAlias,
                        $queryType,
                        $conditionType,
                        (string) $searchValue
                    );
                }
                break;
            default:
                break;
        }

        if ($this->isQueryValid($filterSubQuery)) {
            if (self::CONDITION_TYPE_MUST === $conditionType) {
                $filterQueries->addMust($filterSubQuery);
            } elseif (self::CONDITION_TYPE_SHOULD === $conditionType) {
                $filterQueries->addShould($filterSubQuery);
            }
        }

        return $this;
    }

    /**
     * @return AbstractQuery|null
     */
    protected function createIntegerFilterTerm(
        string $columnAlias,
        int $searchValue
    ) {
        if ('' !== $columnAlias) {
            $integerTerm = $this->createFilterTerm($columnAlias, $searchValue);

            if ($this->isQueryValid($integerTerm)) {
                /** @var string|null $nestedPath */
                $nestedPath = $this->getNestedPath($columnAlias);
                if (null !== $nestedPath) {
                    /** @var Nested $nested */
                    $nested = new Nested();
                    $nested->setPath($nestedPath);
                    /** @var BoolQuery $boolQuery */
                    $boolQuery = new BoolQuery();
                    $boolQuery->addMust($integerTerm);
                    $nested->setQuery($boolQuery);

                    return $nested;
                }

                return $integerTerm;
            }
        }

        return null;
    }

    /**
     * @return AbstractQuery|null
     */
    protected function createStringMultiFilterTerm(
        string $columnAlias,
        string $queryType,
        string $conditionType,
        array $searchValues
    ) {
        if ('' !== $columnAlias && \is_array($searchValues) && ! empty($searchValues)) {
            /** @var BoolQuery $filterQueries */
            $filterQueries = new BoolQuery();

            foreach ($searchValues as $searchValue) {
                $filterSubQuery = $this->createStringFilterTerm(
                    $columnAlias,
                    $queryType,
                    $conditionType,
                    (string) $searchValue
                );
                if ($this->isQueryValid($filterSubQuery)) {
                    $filterQueries->addShould($filterSubQuery);
                }
            }

            if ($this->isQueryValid($filterQueries)) {
                return $filterQueries;
            }
        }

        return null;
    }

    /**
     * @return AbstractQuery|null
     */
    protected function createStringFilterTerm(
        string $columnAlias,
        string $queryType,
        string $conditionType,
        string $searchValue
    ) {
        $searchValue = trim($searchValue);
        if ('' !== $columnAlias && '' !== $searchValue && 'null' !== $searchValue) {
            if (self::QUERY_TYPE_MATCH === $queryType) {
                $fieldQuery = $this->createFilterMatchTerm($columnAlias, $searchValue, $conditionType);
            } elseif (self::QUERY_TYPE_EXACT_MATCH === $queryType) {
                $fieldQuery = $this->createFilterExactMatchTerm($columnAlias, $searchValue, $conditionType);
            } elseif (self::QUERY_TYPE_REGEXP === $queryType) {
                $fieldQuery = $this->createFilterRegexpTerm($columnAlias . '.raw', $searchValue, $conditionType);
            } else {
                $fieldQuery = $this->createFilterTerm($columnAlias . '.raw', $searchValue, $conditionType);
            }

            if ($this->isQueryValid($fieldQuery)) {
                /** @var string|null $nestedPath */
                $nestedPath = $this->getNestedPath($columnAlias);
                if (null !== $nestedPath) {
                    /** @var Nested $nested */
                    $nested = new Nested();
                    $nested->setPath($nestedPath);
                    $nested->setQuery($fieldQuery);

                    return $nested;
                }

                return $fieldQuery;
            }
        }

        return null;
    }

    /**
     * @param string|int $searchValue
     * @param string     $conditionType
     *
     * @return AbstractQuery|null
     */
    protected function createFilterTerm(string $columnAlias, $searchValue, string $conditionType = null)
    {
        if ('' !== $columnAlias) {
            /** @var Query\Term() $query */
            $query = new Query\Term();
            $query->setTerm($columnAlias, $searchValue);

            return $query;
        }

        return null;
    }

    /**
     * @param string|int $searchValue
     * @param string     $conditionType
     *
     * @return AbstractQuery|null
     */
    protected function createFilterMatchTerm(string $columnAlias, $searchValue, string $conditionType = null)
    {
        if ('' !== $columnAlias) {
            /** @var Query\MatchQuery() $query */
            $query = new Query\MatchQuery();
            $query->setFieldQuery($columnAlias, $searchValue);
            $query->setFieldMinimumShouldMatch($columnAlias, 1);
            if ($conditionType === self::CONDITION_TYPE_MUST) {
                $query->setFieldOperator($columnAlias, Query\MatchQuery::OPERATOR_AND);
            }

            return $query;
        }

        return null;
    }

    /**
     * @param string|int $searchValue
     * @param string     $conditionType
     *
     * @return AbstractQuery|null
     */
    protected function createFilterExactMatchTerm(string $columnAlias, $searchValue, string $conditionType = null)
    {
        $query = $this->createFilterMatchTerm($columnAlias, $searchValue, $conditionType);

        if ($this->isQueryValid($query)) {
            $query->setFieldMinimumShouldMatch($columnAlias, '100%');
        }

        return $query;
    }

    /**
     * @param string|int $searchValue
     * @param string     $conditionType
     *
     * @return AbstractQuery|null
     */
    protected function createFilterRegexpTerm(string $columnAlias, $searchValue, string $conditionType = null)
    {
        if ('' !== $columnAlias) {
            /* @var Query\Regexp $query */
            return new Query\Regexp($columnAlias, '.*' . $searchValue . '.*');
        }

        return null;
    }

    /**
     * @param string $data
     *
     * @return $this
     */
    protected function addSearchOrderColumn(ColumnInterface $column, $data): self
    {
        $this->addSearchColumn($column, $data);
        $this->addOrderColumn($column, $data);

        return $this;
    }

    /**
     * @param string $data
     *
     * @return $this
     */
    protected function addOrderColumn(ColumnInterface $column, $data): self
    {
        $col = null;
        if ($data !== null && $this->isOrderableColumn($column)) {
            $typeOfField = $column->getTypeOfField();

            if ($this->accessor->isReadable($column, 'orderColumnTypeOfField')) {
                $typeOfField = $this
                    ->accessor
                    ->getValue($column, 'orderColumnTypeOfField') ??
                    $column->getTypeOfField();
            }

            if ($typeOfField === 'string') {
                $col = $data . '.' . $this->getSortFieldSuffix();
            } else {
                $col = $data;
            }
        }

        $col = str_replace('[,]', '', $col);

        $this->orderColumns[] = $col;

        $this->addNestedPath($col, $data);

        return $this;
    }

    protected function getSortFieldSuffix(): string
    {
        return 'keyword';
    }

    /**
     * @param string $data
     *
     * @return $this
     */
    protected function addSearchColumn(ColumnInterface $column, $data): self
    {
        $col = $this->isSearchableColumn($column) ? $data : null;
        $col = str_replace('[,]', '', $col);

        $this->searchColumns[] = $col;

        $this->addNestedPath($col, $data);

        return $this;
    }

    /**
     * @return $this
     */
    protected function setOrderBy(Query $query): self
    {
        if (isset($this->requestParams['order'])
            && \count($this->requestParams['order'])
        ) {
            $counter = \count($this->requestParams['order']);

            for ($i = 0; $i < $counter; ++$i) {
                $columnIdx = (int) $this->requestParams['order'][$i]['column'];
                $requestColumn = $this->requestParams['columns'][$columnIdx];

                if ('true' === $requestColumn['orderable']) {
                    $columnName = $this->orderColumns[$columnIdx];
                    $orderOptions = [
                        'order' => $this->requestParams['order'][$i]['dir'],
                    ];

                    /** @var string|null $nestedPath */
                    $nestedPath = $this->getNestedPath($columnName);
                    if ($nestedPath !== null) {
                        $orderOptions['nested_path'] = $nestedPath;
                    }

                    $query->setSort([$columnName => $orderOptions]);
                }
            }
        }

        return $this;
    }

    /**
     * @param bool $countQuery
     */
    protected function getQuery($countQuery = false): Query
    {
        /** @var Query $query */
        $query = new Query();

        /** @var BoolQuery $boolQuery */
        $boolQuery = new BoolQuery();

        $this->setTermsFilters($boolQuery);

        if (! $countQuery) {
            $this->addSearchTerms($boolQuery);
        }

        $query->setQuery($boolQuery);

        if (! $countQuery) {
            $this->setOrderBy($query);
        }

        if (\is_array($this->sourceFields) && ! empty($this->sourceFields)) {
            $query->setSource($this->sourceFields);
        }

        return $query;
    }

    protected function getEntityShortName(ClassMetadata $metadata): string
    {
        return strtolower($metadata->getReflectionClass()->getShortName());
    }

    protected function getSafeName($name): string
    {
        return $name;
    }

    private function hasCustomDql(ColumnInterface $column): bool
    {
        return true === $this->accessor->getValue($column, 'customDql');
    }

    private function isSelectColumn(ColumnInterface $column): bool
    {
        return true === $this->accessor->getValue($column, 'selectColumn');
    }

    private function isOrderableColumn(ColumnInterface $column): bool
    {
        return true === $this->accessor->getValue($column, 'orderable');
    }

    private function generateElasticaEntriesForResults(int $countAll, array $resultEntries): ElasticaEntries
    {
        /** @var ElasticaEntries $entries */
        $entries = new ElasticaEntries();
        $entries->setCount($countAll);
        $entries->setEntries($resultEntries);

        return $entries;
    }

    private function getResultsForOffsetAndLength(
        int $offset,
        int $length
    ): PartialResultsInterface {
        return $this->paginatedFinder->createPaginatorAdapter($this->getQuery())->getResults($offset, $length);
    }

    private function getHybridResultsForOffsetAndLength(
        int $offset,
        int $length
    ): PartialResultsInterface {
        return $this->paginatedFinder->createHybridPaginatorAdapter($this->getQuery())->getResults($offset, $length);
    }

    private function getRawResultsForOffsetAndLength(
        int $offset,
        int $length
    ): PartialResultsInterface {
        return $this->paginatedFinder->createRawPaginatorAdapter($this->getQuery())->getResults($offset, $length);
    }

    private function extractSourceFromResultset(
        PartialResultsInterface $partialResults,
        array $resultEntries = []
    ): array {
        foreach ($partialResults->toArray() as $item) {
            if ($item instanceof HybridResult) {
                $resultEntries[] = $item->getResult()->getSource();
            } elseif (\is_array($item)) {
                $resultEntries[] = $item;
            }
        }

        return $resultEntries;
    }
}
