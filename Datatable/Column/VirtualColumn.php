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

namespace Sg\DatatablesBundle\Datatable\Column;

use Doctrine\DBAL\Types\Type as DoctrineType;
use RuntimeException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VirtualColumn extends Column
{
    /**
     * Order field.
     */
    protected ?string $orderColumn = null;

    /**
     * Search field.
     */
    protected ?string $searchColumn = null;

    /**
     * Order field type.
     */
    protected ?string $orderColumnTypeOfField = null;

    // -------------------------------------------------
    // Options
    // -------------------------------------------------

    public function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        $resolver->remove('data');
        $resolver->remove('join_type');
        $resolver->remove('editable');

        $resolver->setDefaults([
            'orderable' => false,
            'searchable' => false,
            'order_column' => null,
            'search_column' => null,
            'order_column_type_of_field' => null,
        ]);

        $resolver->setAllowedTypes('order_column', ['null', 'string', 'array']);
        $resolver->setAllowedTypes('search_column', ['null', 'string', 'array']);

        $resolver->setAllowedValues('order_column_type_of_field', [...[null], ...array_keys(DoctrineType::getTypesMap())]);

        $resolver->setNormalizer('orderable', function (Options $options, $value): bool {
            if (null !== $options['order_column']) {
                return $value;
            }
            if (true !== $value) {
                return $value;
            }
            throw new RuntimeException('VirtualColumn::configureOptions(): For the orderable option, order_column should not be null.');
        });

        $resolver->setNormalizer('searchable', function (Options $options, $value): bool {
            if (null !== $options['search_column']) {
                return $value;
            }
            if (true !== $value) {
                return $value;
            }
            throw new RuntimeException('VirtualColumn::configureOptions(): For the searchable option, search_column should not be null.');
        });

        return $this;
    }

    // -------------------------------------------------
    // ColumnInterface
    // -------------------------------------------------

    public function isSelectColumn(): bool
    {
        return false;
    }

    public function getColumnType(): string
    {
        return parent::VIRTUAL_COLUMN;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    public function getOrderColumn(): ?string
    {
        return $this->orderColumn;
    }

    public function setOrderColumn(?string $orderColumn): static
    {
        $this->orderColumn = $orderColumn;

        return $this;
    }

    public function getSearchColumn(): ?string
    {
        return $this->searchColumn;
    }

    public function setSearchColumn(?string $searchColumn): static
    {
        $this->searchColumn = $searchColumn;

        return $this;
    }

    /**
     * Get orderColumnTypeOfField.
     */
    public function getOrderColumnTypeOfField(): ?string
    {
        return $this->orderColumnTypeOfField;
    }

    /**
     * Set orderColumnTypeOfField.
     */
    public function setOrderColumnTypeOfField(?string $orderColumnTypeOfField): static
    {
        $this->orderColumnTypeOfField = $orderColumnTypeOfField;

        return $this;
    }
}
