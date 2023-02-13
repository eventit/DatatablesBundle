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

    protected ?PaginatedFinderInterface $paginatedFinder = null;

    protected ?ModelDefinitionInterface $modelDefinition = null;

    protected array $nestedPaths = [];

    protected array $sourceFields = [];

    public function setPaginatedFinder(PaginatedFinderInterface $paginatedFinder): void
    {
        $this->paginatedFinder = $paginatedFinder;
    }

    public function setModelDefinition(ModelDefinitionInterface $modelDefinition): void
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
     * @param string[] $fields
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
        return $this->paginatedFinder->createRawPaginatorAdapter($this->getQuery(true))->getTotalHits();
    }

    abstract protected function setTermsFilters(BoolQuery $query): BoolQuery;

    /** nothing needed more than in abstract */
    protected function loadIndividualConstructSettings(): void
    {
        $this->nestedPaths = [];
        $this->selectColumns = [];
        $this->searchColumns = [];
        $this->orderColumns = [];
        $this->searchColumnGroups = [];
    }

    protected function addNestedPath(?string $columnAlias, ?string $path): static
    {
        if (null !== $columnAlias && '' !== $columnAlias && null !== $path && str_contains($path, '.')) {
            $pathParts = explode('.', $path);
            if (\count($pathParts) > 1) {
                $this->nestedPaths[$columnAlias] =
                    implode('.', \array_slice($pathParts, 0, -1));
            }
        }

        return $this;
    }

    protected function getNestedPath(string $columnAlias): ?string
    {
        if ('' === $columnAlias) {
            return null;
        }

        return $this->nestedPaths[$columnAlias] ?? null;
    }

    protected function initColumnArrays(): static
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

        return ! \is_object($query->getParams());
    }

    protected function addGlobalFilteringSearchTerms(BoolQuery $query): static
    {
        if (isset($this->requestParams['search']) && '' !== $this->requestParams['search']['value']) {
            $filterQueries = new BoolQuery();

            $searchValue = $this->requestParams['search']['value'];

            /**
             * @var int|string      $key
             * @var ColumnInterface $column
             */
            foreach ($this->columns as $key => $column) {
                if ($this->isSearchableColumn($column)) {
                    /** @var string $columnAlias */
                    $columnAlias = $this->searchColumns[$key];
                    if ('' === $columnAlias) {
                        continue;
                    }
                    if (null === $columnAlias) {
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

    protected function addIndividualFilteringSearchTerms(BoolQuery $query): static
    {
        if ($this->isIndividualFiltering()) {
            $filterQueries = new BoolQuery();

            /**
             * @var int|string      $key
             * @var ColumnInterface $column
             */
            foreach ($this->columns as $key => $column) {
                if ($this->isSearchableColumn($column)) {
                    if (! \array_key_exists('columns', $this->requestParams)) {
                        continue;
                    }
                    if (! \array_key_exists($key, $this->requestParams['columns'])) {
                        continue;
                    }

                    /** @var string $columnAlias */
                    $columnAlias = $this->searchColumns[$key];
                    if ('' === $columnAlias) {
                        continue;
                    }
                    if (null === $columnAlias) {
                        continue;
                    }

                    $searchValue = $this->requestParams['columns'][$key]['search']['value'];

                    if ('' !== $searchValue && 'null' !== $searchValue) {
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

    protected function addSearchTerms(BoolQuery $query): static
    {
        $this->addGlobalFilteringSearchTerms($query);
        $this->addIndividualFilteringSearchTerms($query);

        return $this;
    }

    protected function addColumnGroupSearchTerm(
        BoolQuery $filterQueries,
        string $searchColumnGroup,
        int|string $searchValue
    ): static {
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

    protected function addColumnSearchTerm(
        BoolQuery $filterQueries,
        string $conditionType,
        ColumnInterface $column,
        string $columnAlias,
        int|string|bool $searchValue
    ): static {
        /** @var AbstractQuery|null $filterSubQuery */
        $filterSubQuery = null;

        /** @var FilterInterface $filter */
        $filter = $this->accessor->getValue($column, 'filter');

        /** @var array|null $searchValues */
        $searchValues = null;

        if (($filter instanceof SelectFilter) && $filter->isMultiple()) {
            $searchValues = explode(',', $searchValue);
        }

        switch ($column->getTypeOfField()) {
            case 'boolean':
                if (\is_numeric($searchValue) || \is_bool($searchValue)) {
                    $filterSubQuery = $this->createIntegerFilterTerm(
                        $columnAlias,
                        (int) $searchValue
                    );
                }
                break;
            case 'integer':
                if (\is_array($searchValues) && \count($searchValues) > 1) {
                    $filterSubQuery = $this->createIntegerMultiFilterTerm(
                        $columnAlias,
                        $searchValues
                    );
                } elseif (\is_numeric($searchValue) || \is_bool($searchValue)) {
                    $filterSubQuery = $this->createIntegerFilterTerm(
                        $columnAlias,
                        (int) $searchValue
                    );
                }
                break;
            case 'string':
                $queryType = self::QUERY_TYPE_MATCH;

                if ($filter instanceof SelectFilter) {
                    $queryType = self::QUERY_TYPE_EXACT_MATCH;
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

    protected function createIntegerMultiFilterTerm(
        string $columnAlias,
        array $searchValues
    ): ?AbstractQuery {
        if ('' === $columnAlias) {
            return null;
        }

        $searchValues = array_filter($searchValues, static function ($v, $k) {
            return is_numeric($v) || \is_bool($v);
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($searchValues)) {
            return null;
        }

        if (\count($searchValues) === 1) {
            return $this->createIntegerFilterTerm(
                $columnAlias,
                (int) array_shift($searchValues)
            );
        }

        $filterQueries = new BoolQuery();

        foreach ($searchValues as $searchValue) {
            $filterSubQuery = $this->createIntegerFilterTerm(
                $columnAlias,
                (int) $searchValue
            );

            if ($this->isQueryValid($filterSubQuery)) {
                $filterQueries->addShould($filterSubQuery);
            }
        }

        if ($this->isQueryValid($filterQueries)) {
            return $filterQueries;
        }

        return null;
    }

    protected function createIntegerFilterTerm(
        string $columnAlias,
        int $searchValue
    ): ?AbstractQuery {
        if ('' === $columnAlias) {
            return null;
        }

        $integerTerm = $this->createFilterTerm($columnAlias, $searchValue);

        if ($this->isQueryValid($integerTerm)) {
            /** @var string|null $nestedPath */
            $nestedPath = $this->getNestedPath($columnAlias);
            if (null !== $nestedPath) {
                $nested = new Nested();
                $nested->setPath($nestedPath);
                $boolQuery = new BoolQuery();
                $boolQuery->addMust($integerTerm);
                $nested->setQuery($boolQuery);

                return $nested;
            }

            return $integerTerm;
        }

        return null;
    }

    protected function createStringMultiFilterTerm(
        string $columnAlias,
        string $queryType,
        string $conditionType,
        array $searchValues
    ): ?AbstractQuery {
        if ('' === $columnAlias) {
            return null;
        }

        if (empty($searchValues)) {
            return null;
        }

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

        return null;
    }

    protected function createStringFilterTerm(
        string $columnAlias,
        string $queryType,
        string $conditionType,
        string $searchValue
    ): ?AbstractQuery {
        if ('' === $columnAlias) {
            return null;
        }

        $searchValue = trim($searchValue);

        if ('' !== $searchValue && 'null' !== $searchValue) {
            return null;
        }

        if (self::QUERY_TYPE_MATCH === $queryType) {
            $fieldQuery = $this->createFilterMatchTerm($columnAlias, $searchValue, $conditionType);
        } elseif (self::QUERY_TYPE_EXACT_MATCH === $queryType) {
            $fieldQuery = $this->createFilterExactMatchTerm($columnAlias, $searchValue, $conditionType);
        } elseif (self::QUERY_TYPE_REGEXP === $queryType) {
            $fieldQuery = $this->createFilterRegexpTerm($columnAlias . '.raw', $searchValue, $conditionType);
        } else {
            $fieldQuery = $this->createFilterTerm($columnAlias . '.raw', $searchValue, $conditionType);
        }

        if (null !== $fieldQuery && $this->isQueryValid($fieldQuery)) {
            /** @var string|null $nestedPath */
            $nestedPath = $this->getNestedPath($columnAlias);
            if (null !== $nestedPath) {
                $nested = new Nested();
                $nested->setPath($nestedPath);
                $nested->setQuery($fieldQuery);

                return $nested;
            }

            return $fieldQuery;
        }

        return null;
    }

    protected function createFilterTerm(
        string $columnAlias,
        string|int $searchValue,
        string $conditionType = null
    ): ?AbstractQuery {
        if ('' !== $columnAlias) {
            $query = new Query\Term();
            $query->setTerm($columnAlias, $searchValue);

            return $query;
        }

        return null;
    }

    protected function createFilterMatchTerm(
        string $columnAlias,
        string|int $searchValue,
        string $conditionType = null
    ): ?AbstractQuery {
        if ('' !== $columnAlias) {
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

    protected function createFilterExactMatchTerm(
        string $columnAlias,
        string|int $searchValue,
        string $conditionType = null
    ): ?AbstractQuery {
        $query = $this->createFilterMatchTerm($columnAlias, $searchValue, $conditionType);

        if (null !== $query && $this->isQueryValid($query) && method_exists($query, 'setFieldMinimumShouldMatch')) {
            $query->setFieldMinimumShouldMatch($columnAlias, '100%');
        }

        return $query;
    }

    protected function createFilterRegexpTerm(
        string $columnAlias,
        string|int $searchValue,
        string $conditionType = null
    ): ?AbstractQuery {
        if ('' !== $columnAlias) {
            return new Query\Regexp($columnAlias, '.*' . $searchValue . '.*');
        }

        return null;
    }

    protected function addSearchOrderColumn(ColumnInterface $column, ?string $data): static
    {
        $this->addSearchColumn($column, $data);
        $this->addOrderColumn($column, $data);

        return $this;
    }

    protected function addOrderColumn(ColumnInterface $column, ?string $data): static
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

            $col = $typeOfField === 'string' ? $data . '.' . $this->getSortFieldSuffix() : $data;
        }

        if (null !== $col) {
            $col = str_replace('[,]', '', $col);

            $this->orderColumns[] = $col;

            $this->addNestedPath($col, $data);
        }

        return $this;
    }

    protected function getSortFieldSuffix(): string
    {
        return 'keyword';
    }

    protected function addSearchColumn(ColumnInterface $column, ?string $data): static
    {
        $col = $this->isSearchableColumn($column) ? $data : null;

        if (null !== $col) {
            $col = str_replace('[,]', '', $col);

            $this->searchColumns[] = $col;

            $this->addNestedPath($col, $data);
        }

        return $this;
    }

    protected function setOrderBy(Query $query): static
    {
        if (isset($this->requestParams['order'])
            && (is_countable($this->requestParams['order']) ? \count($this->requestParams['order']) : 0)
        ) {
            $counter = is_countable($this->requestParams['order']) ? \count($this->requestParams['order']) : 0;

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

    protected function getQuery(bool $countQuery = false): Query
    {
        $query = new Query();

        $boolQuery = new BoolQuery();

        $this->setTermsFilters($boolQuery);

        if (! $countQuery) {
            $this->addSearchTerms($boolQuery);
        }

        $query->setQuery($boolQuery);

        if (! $countQuery) {
            $this->setOrderBy($query);
        }

        if (! empty($this->sourceFields)) {
            $query->setSource($this->sourceFields);
        }

        return $query;
    }

    protected function getEntityShortName(ClassMetadata $metadata): string
    {
        return strtolower($metadata->getReflectionClass()?->getShortName() ?? '');
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
