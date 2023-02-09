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

use Exception;
use RuntimeException;
use Sg\DatatablesBundle\Datatable\Column\ColumnInterface;
use Sg\DatatablesBundle\Datatable\DatatableInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractDatatableResponse
{
    protected ?Request $request;

    protected array $requestParams = [];

    /**
     * A DatatableInterface instance.
     * Default: null.
     */
    protected ?DatatableInterface $datatable = null;

    /**
     * A DatatableQueryBuilder instance.
     * This class generates a Query by given Columns.
     * Default: null.
     */
    protected ?AbstractDatatableQueryBuilder $datatableQueryBuilder = null;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    abstract public function getResponse(
        bool $countAllResults = true,
        bool $outputWalkers = false,
        bool $fetchJoinCollection = true
    ): JsonResponse;

    abstract public function getData(
        bool $countAllResults = true,
        bool $outputWalkers = false,
        bool $fetchJoinCollection = true
    ): array;

    /**
     * @throws Exception
     */
    abstract public function getJsonResponse(): JsonResponse;

    abstract public function resetResponseOptions(): void;

    /**
     * @throws Exception
     */
    public function setDatatable(DatatableInterface $datatable): static
    {
        $val = $this->validateColumnsPositions($datatable);
        if (\is_int($val)) {
            throw new RuntimeException("DatatableResponse::setDatatable(): The Column with the index {$val} is on a not allowed position.");
        }

        $this->datatable = $datatable;
        $this->datatableQueryBuilder = null;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function getDatatableQueryBuilder(): AbstractDatatableQueryBuilder
    {
        return $this->datatableQueryBuilder ?: $this->createDatatableQueryBuilder();
    }

    protected function checkResponseDependencies(): void
    {
        if (null === $this->datatable) {
            throw new RuntimeException('DatatableResponse::getResponse(): Set a Datatable class with setDatatable().');
        }

        if (null === $this->datatableQueryBuilder) {
            throw new RuntimeException('DatatableResponse::getResponse(): A DatatableQueryBuilder instance is needed. Call getDatatableQueryBuilder().');
        }
    }

    /**
     * @throws Exception
     */
    abstract protected function createDatatableQueryBuilder(): AbstractDatatableQueryBuilder;

    /**
     * Get request params.
     */
    protected function getRequestParams(): array
    {
        $parameterBag = null;
        $type = $this->datatable->getAjax()->getMethod();

        if ('GET' === strtoupper($type)) {
            $parameterBag = $this->request->query;
        }

        if ('POST' === strtoupper($type)) {
            $parameterBag = $this->request->request;
        }

        return $parameterBag->all();
    }

    protected function validateColumnsPositions(DatatableInterface $datatable): bool|int
    {
        $columns = $datatable->getColumnBuilder()->getColumns();
        $lastPosition = \count($columns);

        /** @var ColumnInterface $column */
        foreach ($columns as $column) {
            $allowedPositions = $column->allowedPositions();
            /** @noinspection PhpUndefinedMethodInspection */
            $index = $column->getIndex();
            if (\is_array($allowedPositions)) {
                $allowedPositions = array_flip($allowedPositions);
                if (\array_key_exists(ColumnInterface::LAST_POSITION, $allowedPositions)) {
                    $allowedPositions[$lastPosition] = $allowedPositions[ColumnInterface::LAST_POSITION];
                    unset($allowedPositions[ColumnInterface::LAST_POSITION]);
                }

                if (! \array_key_exists($index, $allowedPositions)) {
                    return $index;
                }
            }
        }

        return true;
    }
}
