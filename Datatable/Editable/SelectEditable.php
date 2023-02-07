<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Datatable\Editable;

use Exception;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectEditable extends AbstractEditable
{
    /**
     * Source data for list.
     * Default: array().
     *
     * @var array
     */
    protected $source;

    // -------------------------------------------------
    // FilterInterface
    // -------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'select';
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

        $resolver->setRequired('source');

        $resolver->setAllowedTypes('source', 'array');

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    /**
     * @return array
     */
    public function getSource()
    {
        return $this->optionToJson($this->source);
    }

    /**
     * @throws Exception
     *
     * @return $this
     */
    public function setSource(array $source)
    {
        if (empty($source)) {
            throw new Exception('SelectEditable::setSource(): The source array should contain at least one element.');
        }

        $this->source = $source;

        return $this;
    }
}
