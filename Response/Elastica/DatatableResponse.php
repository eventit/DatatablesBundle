<?php

namespace Sg\DatatablesBundle\Response\Elastica;

use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Sg\DatatablesBundle\Model\ModelDefinitionInterface;
use Sg\DatatablesBundle\Response\AbstractDatatableQueryBuilder;
use Sg\DatatablesBundle\Response\AbstractDatatableResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class DatatableResponse extends AbstractDatatableResponse
{
    /** @var AbstractDatatableQueryBuilder $datatableQueryBuilder */
    /** @var PaginatedFinderInterface $paginatedFinder */
    protected $paginatedFinder;

    /** @var string */
    protected $datatableQueryBuilderClass;

    /** @var ModelDefinitionInterface */
    protected $modelDefinition;

    /** @var bool */
    protected $countAllResults;

    /**
     * @param PaginatedFinderInterface $paginatedFinder
     *
     * @return DatatableResponse
     */
    public function setPaginatedFinder(PaginatedFinderInterface $paginatedFinder): self
    {
        $this->paginatedFinder = $paginatedFinder;

        return $this;
    }

    /**
     * @param string $datatableQueryBuilderClass
     *
     * @return DatatableResponse
     */
    public function setDatatableQueryBuilderClass(string $datatableQueryBuilderClass): self
    {
        $this->datatableQueryBuilderClass = $datatableQueryBuilderClass;

        return $this;
    }

    /**
     * @param ModelDefinitionInterface $modelDefinition
     *
     * @return DatatableResponse
     */
    public function setModelDefinition(ModelDefinitionInterface $modelDefinition): self
    {
        $this->modelDefinition = $modelDefinition;

        return $this;
    }

    /**
     * @param bool $countAllResults
     *
     * @return DatatableResponse
     */
    public function setCountAllResults(bool $countAllResults): self
    {
        $this->countAllResults = $countAllResults;

        return $this;
    }

    public function __construct(RequestStack $requestStack)
    {
        parent::__construct($requestStack);
    }

    public function resetResponseOptions()
    {
        $this->countAllResults = true;
    }

    /**
     * @param bool $countAllResults
     * @param bool $outputWalkers
     * @param bool $fetchJoinCollection
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function getResponse($countAllResults = true)
    {
        $this->countAllResults = $countAllResults;

        return $this->getJsonResponse();
    }

    /**
     * @inheritdoc
     */
    public function getJsonResponse(): JsonResponse
    {
        $this->checkResponseDependencies();
        $this->getDatatableQueryBuilder()->setPaginatedFinder($this->paginatedFinder);
        $this->getDatatableQueryBuilder()->setModelDefinition($this->modelDefinition);

        $entries = $this->getDatatableQueryBuilder()->execute();

        $formatter = new DatatableFormatter();
        $formatter->runFormatter($entries, $this->datatable);

        $outputHeader = [
            'draw' => (int)$this->requestParams['draw'],
            'recordsFiltered' => $entries->getCount(),
            'recordsTotal' => true === $this->countAllResults ? $this->datatableQueryBuilder->getCountAllResults() : 0,
        ];

        $response = new JsonResponse(array_merge($outputHeader, $formatter->getOutput()));
        $this->resetResponseOptions();

        return $response;
    }

    /**
     * @return DatatableQueryBuilder
     * @throws \Exception
     */
    protected function createDatatableQueryBuilder(): AbstractDatatableQueryBuilder
    {
        if (null === $this->datatable) {
            throw new \Exception('DatatableResponse::getDatatableQueryBuilder(): Set a Datatable class with setDatatable().');
        }

        $this->requestParams = $this->getRequestParams();
        $this->datatableQueryBuilder = new $this->datatableQueryBuilderClass($this->requestParams, $this->datatable);

        return $this->datatableQueryBuilder;
    }
}
