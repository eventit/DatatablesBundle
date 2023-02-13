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

use Symfony\Component\OptionsResolver\OptionsResolver;

class TextEditable extends AbstractEditable
{
    /**
     * Whether to show clear button.
     * Default: true.
     *
     * Currently not usable: x-editable bug https://github.com/vitalets/x-editable/issues/977
     */
    protected bool $clear = true;

    /**
     * Placeholder attribute of input. Shown when input is empty.
     * Default: null.
     */
    protected ?string $placeholder = null;

    // -------------------------------------------------
    // FilterInterface
    // -------------------------------------------------

    public function getType(): string
    {
        return 'text';
    }

    // -------------------------------------------------
    // Options
    // -------------------------------------------------

    public function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'clear' => true,
            'placeholder' => null,
        ]);

        $resolver->setAllowedTypes('clear', 'bool');
        $resolver->setAllowedTypes('placeholder', ['null', 'string']);

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    public function isClear(): bool
    {
        return $this->clear;
    }

    public function setClear(bool $clear): static
    {
        $this->clear = $clear;

        return $this;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function setPlaceholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }
}
