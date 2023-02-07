<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Datatable\Editable;

use Symfony\Component\OptionsResolver\OptionsResolver;

class TextareaEditable extends AbstractEditable
{
    /**
     * Number of rows in textarea.
     *
     * @var int
     */
    protected $rows;

    // -------------------------------------------------
    // FilterInterface
    // -------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'textarea';
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

        $resolver->setDefaults([
            'rows' => 7,
        ]);

        $resolver->setAllowedTypes('rows', 'int');

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    /**
     * @return int
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @param int $rows
     *
     * @return $this
     */
    public function setRows($rows)
    {
        $this->rows = $rows;

        return $this;
    }
}
