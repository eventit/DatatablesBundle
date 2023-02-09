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

use Symfony\Component\OptionsResolver\OptionsResolver;

class NumberFilter extends TextFilter
{
    /**
     * Minimum value.
     * Default: '0'.
     */
    protected string $min = '0';

    /**
     * Maximum value.
     * Default: '100'.
     */
    protected string $max = '100';

    /**
     * The Step scale factor of the slider.
     * Default: '1'.
     */
    protected string $step = '1';

    /**
     * Determines whether a label with the current value is displayed.
     * Default: false.
     */
    protected bool $showLabel = false;

    /**
     * Pre-defined values.
     * Default: null.
     */
    protected ?array $datalist = null;

    /**
     * The <input> type.
     * Default: 'number'.
     */
    protected string $type = 'number';

    // -------------------------------------------------
    // Options
    // -------------------------------------------------

    public function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        $resolver->remove('placeholder');
        $resolver->remove('placeholder_text');

        $resolver->setDefaults([
            'min' => '0',
            'max' => '100',
            'step' => '1',
            'show_label' => false,
            'datalist' => null,
            'type' => 'number',
        ]);

        $resolver->setAllowedTypes('min', 'string');
        $resolver->setAllowedTypes('max', 'string');
        $resolver->setAllowedTypes('step', 'string');
        $resolver->setAllowedTypes('show_label', 'bool');
        $resolver->setAllowedTypes('datalist', ['null', 'array']);
        $resolver->setAllowedTypes('type', 'string');

        $resolver->addAllowedValues('type', ['number', 'range']);

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    public function getMin(): string
    {
        return $this->min;
    }

    public function setMin(string $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function getMax(): string
    {
        return $this->max;
    }

    public function setMax(string $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function getStep(): string
    {
        return $this->step;
    }

    public function setStep(string $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function isShowLabel(): bool
    {
        return $this->showLabel;
    }

    public function setShowLabel(bool $showLabel): static
    {
        $this->showLabel = $showLabel;

        return $this;
    }

    public function getDatalist(): ?array
    {
        return $this->datalist;
    }

    public function setDatalist(?array $datalist): static
    {
        $this->datalist = $datalist;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
