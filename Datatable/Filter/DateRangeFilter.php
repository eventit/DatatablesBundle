<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Datatable\Filter;

use DateTime;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateRangeFilter extends AbstractFilter
{
    // -------------------------------------------------
    // FilterInterface
    // -------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return '@SgDatatables/filter/daterange.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function addAndExpression(Andx $andExpr, QueryBuilder $qb, $searchField, $searchValue, $searchTypeOfField, &$parameterCounter)
    {
        list($_dateStart, $_dateEnd) = explode(' - ', $searchValue);
        $dateStart = new DateTime($_dateStart);
        $dateEnd = new DateTime($_dateEnd);
        $dateEnd->setTime(23, 59, 59);

        $andExpr = $this->getBetweenAndExpression($andExpr, $qb, $searchField, $dateStart->format('Y-m-d H:i:s'), $dateEnd->format('Y-m-d H:i:s'), $parameterCounter);
        $parameterCounter += 2;

        return $andExpr;
    }

    // -------------------------------------------------
    // Options
    // -------------------------------------------------

    /**
     * @return $this
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->remove('search_type');

        return $this;
    }

    // -------------------------------------------------
    // Helper
    // -------------------------------------------------

    /**
     * Returns the type for the <input> element.
     *
     * @return string
     */
    public function getType()
    {
        return 'text';
    }
}
