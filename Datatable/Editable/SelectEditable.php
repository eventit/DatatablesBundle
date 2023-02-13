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

namespace Sg\DatatablesBundle\Datatable\Editable;

use Exception;
use RuntimeException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectEditable extends AbstractEditable
{
    /**
     * Source data for list.
     * Default: array().
     */
    protected array $source = [];

    // -------------------------------------------------
    // FilterInterface
    // -------------------------------------------------

    public function getType(): string
    {
        return 'select';
    }

    // -------------------------------------------------
    // Options
    // -------------------------------------------------

    public function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        $resolver->setRequired('source');

        $resolver->setAllowedTypes('source', 'array');

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    public function getSource(): array
    {
        return $this->optionToJson($this->source);
    }

    /**
     * @throws Exception
     */
    public function setSource(array $source): static
    {
        if (empty($source)) {
            throw new RuntimeException('SelectEditable::setSource(): The source array should contain at least one element.');
        }

        $this->source = $source;

        return $this;
    }
}
