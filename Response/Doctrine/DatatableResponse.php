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

use Sg\DatatablesBundle\Datatable\DatatableInterface;
use Sg\DatatablesBundle\Datatable\Column\ColumnInterface;
use Sg\DatatablesBundle\Response\AbstractDatatableResponse;
use \Sg\DatatablesBundle\Response\Doctrine\DatatableFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;

class DatatableResponse extends AbstractDatatableResponse
{
    /** @var bool */
    protected bool $countAllResults = false;

    /** @var bool */
    protected bool $outputWalkers = false;

    /** @var bool */
    protected bool $fetchJoinCollection = false;

    /**
     * @param bool $countAllResults
     *
     * @return DatatableResponse
     */
    public function setCountAllResults(bool $countAllResults): DatatableResponse
    {
        $this->countAllResults = $countAllResults;

        return $this;
    }

    /**
     * @param bool $outputWalkers
     *
     * @return DatatableResponse
     */
    public function setOutputWalkers(bool $outputWalkers): DatatableResponse
    {
        $this->outputWalkers = $outputWalkers;

        return $this;
    }

    /**
     * @param bool $fetchJoinCollection
     *
     * @return DatatableResponse
     */
    public function setFetchJoinCollection(bool $fetchJoinCollection): DatatableResponse
    {
        $this->fetchJoinCollection = $fetchJoinCollection;

        return $this;
    }

    /**
     * @param DatatableInterface $datatable
     *
     * @return $this
     * @throws \Exception
     */
    public function setDatatable(DatatableInterface $datatable): AbstractDatatableResponse
    {
        $val = $this->validateColumnsPositions($datatable);
        if (is_int($val)) {
            throw new \RuntimeException("DatatableResponse::setDatatable(): The Column with the index $val is on a not allowed position.");
        };

        $this->datatable = $datatable;
        $this->datatableQueryBuilder = null;

        return $this;
    }

    public function resetResponseOptions(): void
    {
        $this->countAllResults = true;
        $this->outputWalkers = false;
        $this->fetchJoinCollection = true;
    }

    /**
     * @param bool $countAllResults
     * @param bool $outputWalkers
     * @param bool $fetchJoinCollection
     *
     * @throws \Exception
     *@return JsonResponse
     */
    public function getResponse(
        bool $countAllResults = true,
        bool $outputWalkers = false,
        bool $fetchJoinCollection = true
    ): JsonResponse {
        $this->countAllResults = $countAllResults;
        $this->outputWalkers = $outputWalkers;
        $this->fetchJoinCollection = $fetchJoinCollection;

        return $this->getJsonResponse();
    }


    /**
     * Get response data as array.
     *
     * @param bool $countAllResults
     * @param bool $outputWalkers
     * @param bool $fetchJoinCollection
     *
     * @throws Exception
     *
     * @return array
     */
    public function getData(bool $countAllResults = true, bool $outputWalkers = false, bool $fetchJoinCollection = true): array
    {
        if (null === $this->datatable) {
            throw new \RuntimeException('DatatableResponse::getResponse(): Set a Datatable class with setDatatable().');
        }

        if (null === $this->datatableQueryBuilder) {
            throw new \RuntimeException('DatatableResponse::getResponse(): A DatatableQueryBuilder instance is needed. Call getDatatableQueryBuilder().');
        }

        $paginator = new Paginator($this->datatableQueryBuilder->execute(), $fetchJoinCollection);
        $paginator->setUseOutputWalkers($outputWalkers);

        $formatter = new DatatableFormatter();
        $formatter->runFormatter($paginator, $this->datatable);

        $outputHeader = [
            'draw' => (int) $this->requestParams['draw'],
            'recordsFiltered' => \count($paginator),
            'recordsTotal' => true === $countAllResults ? (int) $this->datatableQueryBuilder->getCountAllResults() : 0,
        ];

        return array_merge($outputHeader, $formatter->getOutput());
    }

    /**
     * @inheritdoc
     */
    public function getJsonResponse(): JsonResponse
    {
        $paginator = new Paginator($this->datatableQueryBuilder->execute(), $this->fetchJoinCollection);
        $paginator->setUseOutputWalkers($this->outputWalkers);

        $formatter = new DatatableFormatter();
        $formatter->runFormatter($paginator, $this->datatable);

        $outputHeader = [
            'draw' => (int)$this->requestParams['draw'],
            'recordsFiltered' => count($paginator),
            'recordsTotal' => true === $this->countAllResults ? (int)$this->datatableQueryBuilder->getCountAllResults() : 0,
        ];

        $response = new JsonResponse(array_merge($outputHeader, $formatter->getOutput()));
        $this->resetResponseOptions();

        return $response;
    }

    /**
     * @return DatatableQueryBuilder
     * @throws \Exception
     */
    protected function createDatatableQueryBuilder(): DatatableQueryBuilder
    {
        if (null === $this->datatable) {
            throw new \RuntimeException('DatatableResponse::getDatatableQueryBuilder(): Set a Datatable class with setDatatable().');
        }

        $this->requestParams = $this->getRequestParams();
        $this->datatableQueryBuilder = new DatatableQueryBuilder($this->requestParams, $this->datatable);

        return $this->datatableQueryBuilder;
    }
}
