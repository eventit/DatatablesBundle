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

use Exception;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use RuntimeException;
use Sg\DatatablesBundle\Datatable\DatatableInterface;
use Sg\DatatablesBundle\Model\ModelDefinitionInterface;
use Sg\DatatablesBundle\Response\AbstractDatatableQueryBuilder;
use Sg\DatatablesBundle\Response\AbstractDatatableResponse;
use Sg\DatatablesBundle\Response\Doctrine\DatatableFormatter;
use Symfony\Component\HttpFoundation\JsonResponse;

class DatatableResponse extends AbstractDatatableResponse
{
    protected ?PaginatedFinderInterface $paginatedFinder = null;

    protected ?string $datatableQueryBuilderClass = null;

    protected ?ModelDefinitionInterface $modelDefinition = null;

    protected bool $countAllResults = false;

    public function setPaginatedFinder(PaginatedFinderInterface $paginatedFinder): static
    {
        $this->paginatedFinder = $paginatedFinder;

        return $this;
    }

    public function setDatatableQueryBuilderClass(string $datatableQueryBuilderClass): static
    {
        $this->datatableQueryBuilderClass = $datatableQueryBuilderClass;

        return $this;
    }

    public function setModelDefinition(ModelDefinitionInterface $modelDefinition): static
    {
        $this->modelDefinition = $modelDefinition;

        return $this;
    }

    public function setCountAllResults(bool $countAllResults): static
    {
        $this->countAllResults = $countAllResults;

        return $this;
    }

    public function resetResponseOptions(): void
    {
        $this->countAllResults = true;
    }

    /**
     * @throws Exception
     */
    public function getResponse(
        bool $countAllResults = true,
        bool $outputWalkers = false,
        bool $fetchJoinCollection = false
    ): JsonResponse {
        $this->countAllResults = $countAllResults;

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

        $entries = $this->datatableQueryBuilder->execute();

        $formatter = new DatatableFormatter();
        $formatter->runFormatter($entries, $this->datatable);

        $outputHeader = [
            'draw' => (int) $this->requestParams['draw'],
            'recordsFiltered' => $entries->getCount(),
            'recordsTotal' => $countAllResults ? $this->datatableQueryBuilder->getCountAllResults() : 0,
        ];

        return array_merge($outputHeader, $formatter->getOutput());
    }

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
            'recordsTotal' => $this->countAllResults ? $this->datatableQueryBuilder->getCountAllResults() : 0,
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
        if (! $this->datatable instanceof DatatableInterface) {
            throw new RuntimeException('Elastica\DatatableResponse::getDatatableQueryBuilder(): Set a Datatable class with setDatatable().');
        }

        if (null === $this->datatableQueryBuilderClass) {
            throw new RuntimeException('Elastica\DatatableResponse::getDatatableQueryBuilder(): Set a datatableQueryBuilderClass first.');
        }

        $this->requestParams = $this->getRequestParams();
        $this->datatableQueryBuilder = new $this->datatableQueryBuilderClass($this->requestParams, $this->datatable);

        return $this->datatableQueryBuilder;
    }
}
