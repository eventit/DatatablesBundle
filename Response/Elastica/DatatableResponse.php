<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Response\Elastica;

use Exception;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Sg\DatatablesBundle\Model\ModelDefinitionInterface;
use Sg\DatatablesBundle\Response\AbstractDatatableQueryBuilder;
use Sg\DatatablesBundle\Response\AbstractDatatableResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use UnexpectedValueException;

class DatatableResponse extends AbstractDatatableResponse
{
    /** @var AbstractDatatableQueryBuilder */
    /** @var PaginatedFinderInterface */
    protected $paginatedFinder;

    /** @var string */
    protected $datatableQueryBuilderClass;

    /** @var ModelDefinitionInterface */
    protected $modelDefinition;

    /** @var bool */
    protected $countAllResults;

    public function setPaginatedFinder(PaginatedFinderInterface $paginatedFinder): self
    {
        $this->paginatedFinder = $paginatedFinder;

        return $this;
    }

    public function setDatatableQueryBuilderClass(string $datatableQueryBuilderClass): self
    {
        $this->datatableQueryBuilderClass = $datatableQueryBuilderClass;

        return $this;
    }

    public function setModelDefinition(ModelDefinitionInterface $modelDefinition): self
    {
        $this->modelDefinition = $modelDefinition;

        return $this;
    }

    public function setCountAllResults(bool $countAllResults): self
    {
        $this->countAllResults = $countAllResults;

        return $this;
    }

    public function resetResponseOptions()
    {
        $this->countAllResults = true;
    }

    /**
     * @param bool $countAllResults
     *
     * @throws Exception
     */
    public function getResponse($countAllResults = true): JsonResponse
    {
        $this->countAllResults = $countAllResults;

        return $this->getJsonResponse();
    }

    /**
     * {@inheritdoc}
     */
    public function getJsonResponse(): JsonResponse
    {
        $this->checkResponseDependencies();
        /** @var DatatableQueryBuilder $datatableQueryBuilder */
        $datatableQueryBuilder = $this->getDatatableQueryBuilder();
        $datatableQueryBuilder->setPaginatedFinder($this->paginatedFinder);
        $datatableQueryBuilder->setModelDefinition($this->modelDefinition);

        $entries = $datatableQueryBuilder->execute();

        $formatter = new DatatableFormatter();
        $formatter->runFormatter($entries, $this->datatable);

        $outputHeader = [
            'draw' => (int) $this->requestParams['draw'],
            'recordsFiltered' => $entries->getCount(),
            'recordsTotal' => true === $this->countAllResults ? $this->datatableQueryBuilder->getCountAllResults() : 0,
        ];

        $response = new JsonResponse(array_merge($outputHeader, $formatter->getOutput()));
        $this->resetResponseOptions();

        return $response;
    }

    /**
     * @throws Exception
     *
     * @return DatatableQueryBuilder
     */
    protected function createDatatableQueryBuilder(): AbstractDatatableQueryBuilder
    {
        if (null === $this->datatable) {
            throw new UnexpectedValueException('Elastica\DatatableResponse::getDatatableQueryBuilder(): Set a Datatable class with setDatatable().');
        }

        if (null === $this->datatableQueryBuilderClass) {
            throw new UnexpectedValueException('Elastica\DatatableResponse::getDatatableQueryBuilder(): Set a datatableQueryBuilderClass first.');
        }

        $this->requestParams = $this->getRequestParams();
        $this->datatableQueryBuilder = new $this->datatableQueryBuilderClass($this->requestParams, $this->datatable);

        return $this->datatableQueryBuilder;
    }
}
