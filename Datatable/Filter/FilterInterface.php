<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Datatable\Filter;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\QueryBuilder;

/**
 * Interface FilterInterface.
 */
interface FilterInterface
{
    /**
     * @return string
     */
    public function getTemplate();

    /**
     * Add an and condition.
     *
     * @param string $searchField
     * @param string $searchTypeOfField
     * @param int    $parameterCounter
     *
     * @return Andx
     */
    public function addAndExpression(Andx $andExpr, QueryBuilder $qb, $searchField, $searchValue, $searchTypeOfField, &$parameterCounter);
}
