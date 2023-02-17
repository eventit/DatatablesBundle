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

use NumberFormatter;
use Sg\DatatablesBundle\Datatable\Helper;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class NumberColumn extends Column
{
    /**
     * A NumberFormatter instance.
     * A required option.
     */
    protected ?NumberFormatter $formatter = null;

    /**
     * Use NumberFormatter::formatCurrency instead NumberFormatter::format to format the value.
     * Default: false.
     */
    protected bool $useFormatCurrency = false;

    /**
     * The currency code.
     * Default: null => NumberFormatter::INTL_CURRENCY_SYMBOL is used.
     */
    protected ?string $currency = null;

    // -------------------------------------------------
    // ColumnInterface
    // -------------------------------------------------

    public function renderSingleField(array &$row): static
    {
        $path = Helper::getDataPropertyPath($this->data);

        if ($this->accessor->isReadable($row, $path)) {
            if ($this->isEditableContentRequired($row)) {
                $content = $this->renderTemplate($this->accessor->getValue($row, $path), $row[$this->editable->getPk()]);
            } else {
                $content = $this->renderTemplate($this->accessor->getValue($row, $path));
            }

            $this->accessor->setValue($row, $path, $content);
        }

        return $this;
    }

    public function renderToMany(array &$row): static
    {
        $value = null;
        $path = Helper::getDataPropertyPath($this->data, $value);

        $entries = $this->accessor->getValue($row, $path);

        if ($this->accessor->isReadable($row, $path)) {
            if ((is_countable($entries) ? \count($entries) : 0) > 0) {
                foreach ($entries as $key => $entry) {
                    $currentPath = $path . '[' . $key . ']' . $value;
                    $currentObjectPath = Helper::getPropertyPathObjectNotation($path, $key, $value);

                    if ($this->isEditableContentRequired($row)) {
                        $content = $this->renderTemplate(
                            $this->accessor->getValue($row, $currentPath),
                            $row[$this->editable->getPk()],
                            $currentObjectPath
                        );
                    } else {
                        $content = $this->renderTemplate($this->accessor->getValue($row, $currentPath));
                    }

                    $this->accessor->setValue($row, $currentPath, $content);
                }
            }
            // no placeholder - leave this blank
        }

        return $this;
    }

    // -------------------------------------------------
    // Options
    // -------------------------------------------------

    public function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        $resolver->setRequired('formatter');

        $resolver->setDefaults(
            [
                'use_format_currency' => false,
                'currency' => null,
            ]
        );

        $resolver->setAllowedTypes('formatter', ['object']);
        $resolver->setAllowedTypes('use_format_currency', ['bool']);
        $resolver->setAllowedTypes('currency', ['null', 'string']);

        $resolver->setAllowedValues('formatter', fn ($formatter): bool => $formatter instanceof NumberFormatter);

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    public function getFormatter(): ?NumberFormatter
    {
        return $this->formatter;
    }

    public function setFormatter(NumberFormatter $formatter): static
    {
        $this->formatter = $formatter;

        return $this;
    }

    public function isUseFormatCurrency(): bool
    {
        return $this->useFormatCurrency;
    }

    public function setUseFormatCurrency(bool $useFormatCurrency): static
    {
        $this->useFormatCurrency = $useFormatCurrency;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    // -------------------------------------------------
    // Helper
    // -------------------------------------------------

    /**
     * Render template.
     *
     * @param mixed       $data
     * @param string|null $pk
     * @param string|null $path
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @return string
     */
    private function renderTemplate(mixed $data, ?string $pk = null, ?string $path = null): string
    {
        if ($this->useFormatCurrency) {
            if (null === $this->currency) {
                $this->currency = $this->formatter->getSymbol(NumberFormatter::INTL_CURRENCY_SYMBOL);
            }
            if (false !== ($formattedData = $this->formatter->formatCurrency((float) $data, $this->currency))) {
                $data = $formattedData;
            } else {
                $data = null;
            }
        } elseif (false !== ($formattedData = $this->formatter->format($data))) {
            $data = $formattedData;
        } else {
            $data = null;
        }

        return $this->twig->render(
            $this->getCellContentTemplate(),
            [
                'data' => $data,
                'column_class_editable_selector' => $this->getColumnClassEditableSelector(),
                'pk' => $pk,
                'path' => $path,
            ]
        );
    }
}
