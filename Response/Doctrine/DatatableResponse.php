<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Response\Doctrine;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use RuntimeException;
use Sg\DatatablesBundle\Datatable\DatatableInterface;
use Sg\DatatablesBundle\Response\AbstractDatatableResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class DatatableResponse extends AbstractDatatableResponse
{
    protected bool $countAllResults = false;

    protected bool $outputWalkers = false;

    protected bool $fetchJoinCollection = false;

    public function setCountAllResults(bool $countAllResults): self
    {
        $this->countAllResults = $countAllResults;

        return $this;
    }

    public function setOutputWalkers(bool $outputWalkers): self
    {
        $this->outputWalkers = $outputWalkers;

        return $this;
    }

    public function setFetchJoinCollection(bool $fetchJoinCollection): self
    {
        $this->fetchJoinCollection = $fetchJoinCollection;

        return $this;
    }

    /**
     * @throws Exception
     *
     * @return $this
     */
    public function setDatatable(DatatableInterface $datatable): AbstractDatatableResponse
    {
        $val = $this->validateColumnsPositions($datatable);
        if (\is_int($val)) {
            throw new RuntimeException("Doctrine\DatatableResponse::setDatatable(): The Column with the index {$val} is on a not allowed position.");
        }

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
     * @throws Exception
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
     * @throws Exception
     */
    public function getData(bool $countAllResults = true, bool $outputWalkers = false, bool $fetchJoinCollection = true): array
    {
        $this->checkResponseDependencies();

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
     * {@inheritdoc}
     */
    public function getJsonResponse(): JsonResponse
    {
        $this->checkResponseDependencies();

        $paginator = new Paginator($this->datatableQueryBuilder->execute(), $this->fetchJoinCollection);
        $paginator->setUseOutputWalkers($this->outputWalkers);

        $formatter = new DatatableFormatter();
        $formatter->runFormatter($paginator, $this->datatable);

        $outputHeader = [
            'draw' => (int) $this->requestParams['draw'],
            'recordsFiltered' => \count($paginator),
            'recordsTotal' => true === $this->countAllResults ? (int) $this->datatableQueryBuilder->getCountAllResults() : 0,
        ];

        $response = new JsonResponse(array_merge($outputHeader, $formatter->getOutput()));
        $this->resetResponseOptions();

        return $response;
    }

    /**
     * @throws Exception
     */
    protected function createDatatableQueryBuilder(): DatatableQueryBuilder
    {
        if (null === $this->datatable) {
            throw new RuntimeException('Doctrine\DatatableResponse::getDatatableQueryBuilder(): Set a Datatable class with setDatatable().');
        }

        $this->requestParams = $this->getRequestParams();
        $this->datatableQueryBuilder = new DatatableQueryBuilder($this->requestParams, $this->datatable);

        return $this->datatableQueryBuilder;
    }
}
