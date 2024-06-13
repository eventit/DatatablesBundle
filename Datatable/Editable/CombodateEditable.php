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

class CombodateEditable extends AbstractEditable
{
    /**
     * Format used for sending value to server.
     */
    protected string $format = '';

    /**
     * Format used for displaying date. If not specified equals to $format.
     */
    protected ?string $viewFormat = null;

    /**
     * Minimum value in years dropdown.
     * Default: 1970.
     */
    protected int $minYear = 1970;

    /**
     * Maximum value in years dropdown.
     * Default: 2035.
     */
    protected int $maxYear = 2035;

    /**
     * Step of values in minutes dropdown.
     * Default: 5.
     */
    protected int $minuteStep = 5;

    /**
     * Step of values in seconds dropdown.
     * Default: 1.
     */
    protected int $secondStep = 1;

    /**
     * If false - number of days in dropdown is always 31.
     * If true - number of days depends on selected month and year.
     * Default: false.
     */
    protected bool $smartDays = false;

    // -------------------------------------------------
    // FilterInterface
    // -------------------------------------------------

    public function getType(): string
    {
        return 'combodate';
    }

    // -------------------------------------------------
    // Options
    // -------------------------------------------------
    public function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'format' => 'YYYY-MM-DD',
            'view_format' => null,
            'min_year' => 1970,
            'max_year' => 2035,
            'minute_step' => 5,
            'second_step' => 1,
            'smart_days' => false,
        ]);

        $resolver->setAllowedTypes('format', 'string');
        $resolver->setAllowedTypes('view_format', ['string', 'null']);
        $resolver->setAllowedTypes('min_year', 'int');
        $resolver->setAllowedTypes('max_year', 'int');
        $resolver->setAllowedTypes('minute_step', 'int');
        $resolver->setAllowedTypes('second_step', 'int');
        $resolver->setAllowedTypes('smart_days', 'bool');

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function getViewFormat(): ?string
    {
        return $this->viewFormat;
    }

    public function setViewFormat(?string $viewFormat): static
    {
        $this->viewFormat = $viewFormat;

        return $this;
    }

    public function getMinYear(): int
    {
        return $this->minYear;
    }

    public function setMinYear(int $minYear): static
    {
        $this->minYear = $minYear;

        return $this;
    }

    public function getMaxYear(): int
    {
        return $this->maxYear;
    }

    public function setMaxYear(int $maxYear): static
    {
        $this->maxYear = $maxYear;

        return $this;
    }

    public function getMinuteStep(): int
    {
        return $this->minuteStep;
    }

    public function setMinuteStep(int $minuteStep): static
    {
        $this->minuteStep = $minuteStep;

        return $this;
    }

    public function getSecondStep(): int
    {
        return $this->secondStep;
    }

    public function setSecondStep(int $secondStep): static
    {
        $this->secondStep = $secondStep;

        return $this;
    }

    public function isSmartDays(): bool
    {
        return $this->smartDays;
    }

    public function setSmartDays(bool $smartDays): static
    {
        $this->smartDays = $smartDays;

        return $this;
    }
}
