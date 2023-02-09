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

namespace Sg\DatatablesBundle\Datatable\Filter;

use DateTime;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateRangeFilter extends AbstractFilter
{
    // -------------------------------------------------
    // FilterInterface
    // -------------------------------------------------

    public function getTemplate(): string
    {
        return '@SgDatatables/filter/daterange.html.twig';
    }

    /**
     * @throws Exception
     */
    public function addAndExpression(Andx $andExpr, QueryBuilder $qb, $searchField, $searchValue, $searchTypeOfField, &$parameterCounter): Andx
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

    public function configureOptions(OptionsResolver $resolver): static
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
     */
    public function getType(): string
    {
        return 'text';
    }
}
