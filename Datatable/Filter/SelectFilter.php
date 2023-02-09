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

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\QueryBuilder;
use Exception;
use RuntimeException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectFilter extends AbstractFilter
{
    /**
     * This allows to define a search type (e.g. 'like' or 'isNull') for each item in 'selectOptions'.
     * Default: array() - The default value of searchType is used.
     */
    protected array $selectSearchTypes = [];

    /**
     * Select options for this filter type (e.g. for boolean column: '1' => 'Yes', '0' => 'No').
     * Default: array().
     */
    protected array $selectOptions = [];

    /**
     * Lets the user select more than one option in the select list.
     * Default: false.
     */
    protected bool $multiple = false;

    // -------------------------------------------------
    // FilterInterface
    // -------------------------------------------------

    public function getTemplate(): string
    {
        return '@SgDatatables/filter/select.html.twig';
    }

    /**
     * @throws Exception
     */
    public function addAndExpression(Andx $andExpr, QueryBuilder $qb, $searchField, $searchValue, $searchTypeOfField, &$parameterCounter): Composite
    {
        $searchValues = explode(',', $searchValue);
        if ($this->multiple && \is_array($searchValues) && \count($searchValues) > 1) {
            $orExpr = $qb->expr()->orX();

            foreach ($searchValues as $searchValueElem) {
                $this->setSelectSearchType($searchValueElem);
                $orExpr->add($this->getExpression($qb->expr()->andX(), $qb, $this->searchType, $searchField, $searchValueElem, $searchTypeOfField, $parameterCounter));
            }

            return $andExpr->add($orExpr);
        }

        $this->setSelectSearchType($searchValue);

        return $this->getExpression($andExpr, $qb, $this->searchType, $searchField, $searchValue, $searchTypeOfField, $parameterCounter);
    }

    // -------------------------------------------------
    // Options
    // -------------------------------------------------

    /**
     * @return $this
     */
    public function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        // placeholder is not a valid attribute on a <select> input
        $resolver->remove('placeholder');
        $resolver->remove('placeholder_text');

        $resolver->setDefaults([
            'select_search_types' => [],
            'select_options' => [],
            'multiple' => false,
        ]);

        $resolver->setAllowedTypes('select_search_types', 'array');
        $resolver->setAllowedTypes('select_options', 'array');
        $resolver->setAllowedTypes('multiple', 'bool');

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    public function getSelectSearchTypes(): array
    {
        return $this->selectSearchTypes;
    }

    public function setSelectSearchTypes(array $selectSearchTypes): static
    {
        $this->selectSearchTypes = $selectSearchTypes;

        return $this;
    }

    public function getSelectOptions(): array
    {
        return $this->selectOptions;
    }

    public function setSelectOptions(array $selectOptions): static
    {
        $this->selectOptions = $selectOptions;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    // -------------------------------------------------
    // Private
    // -------------------------------------------------

    /**
     * @throws Exception
     */
    private function setSelectSearchType(string $searchValue): void
    {
        $searchTypesCount = \count($this->selectSearchTypes);

        if ($searchTypesCount > 0) {
            if ($searchTypesCount === \count($this->selectOptions)) {
                $this->searchType = $this->selectSearchTypes[$searchValue];
            } else {
                throw new RuntimeException('SelectFilter::setSelectSearchType(): The search types array is not valid.');
            }
        }
    }
}
