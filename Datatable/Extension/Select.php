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

namespace Sg\DatatablesBundle\Datatable\Extension;

use Symfony\Component\OptionsResolver\OptionsResolver;

class Select extends AbstractExtension
{
    /**
     * Indicate if the selected items will be removed when clicking outside of the table.
     */
    protected ?bool $blurable = null;

    /**
     * Set the class name that will be applied to selected items.
     */
    protected ?string $className = null;

    /**
     * Enable / disable the display for item selection information in the table summary.
     */
    protected ?bool $info = null;

    /**
     * Set which table items to select (rows, columns or cells).
     */
    protected ?string $items = null;

    /**
     * Set the element selector used for mouse event capture to select items.
     */
    protected ?string $selector = null;

    /**
     * Set the selection style for end user interaction with the table.
     */
    protected ?string $style = null;

    public function __construct()
    {
        parent::__construct('select');
    }

    public function configureOptions(OptionsResolver $resolver): static
    {
        $resolver->setDefaults(
            [
                'blurable' => null,
                'class_name' => null,
                'info' => null,
                'items' => null,
                'selector' => null,
                'style' => null,
            ]
        );

        $resolver->setAllowedTypes('blurable', ['boolean', 'null']);
        $resolver->setAllowedTypes('class_name', ['string', 'null']);
        $resolver->setAllowedTypes('info', ['boolean', 'null']);
        $resolver->setAllowedTypes('items', ['string', 'null']);
        $resolver->setAllowedValues('items', ['row', 'column', 'cell']);
        $resolver->setAllowedTypes('selector', ['string', 'null']);
        $resolver->setAllowedTypes('style', ['string', 'null']);
        $resolver->setAllowedValues('style', ['api', 'single', 'multi', 'os', 'multi+shift']);

        return $this;
    }

    public function getBlurable(): ?bool
    {
        return $this->blurable;
    }

    public function setBlurable(?string $blurable): static
    {
        $this->blurable = $blurable;

        return $this;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function setClassName(?string $className): static
    {
        $this->className = $className;

        return $this;
    }

    public function getInfo(): ?bool
    {
        return $this->info;
    }

    public function setInfo(?bool $info): static
    {
        $this->info = $info;

        return $this;
    }

    public function getItems(): ?string
    {
        return $this->items;
    }

    public function setItems(?string $items): static
    {
        $this->items = $items;

        return $this;
    }

    public function getSelector(): ?string
    {
        return $this->selector;
    }

    public function setSelector(?string $selector): static
    {
        $this->selector = $selector;

        return $this;
    }

    public function getStyle(): ?string
    {
        return $this->style;
    }

    public function setStyle(?string $style): static
    {
        $this->style = $style;

        return $this;
    }

    public function getJavaScriptConfiguration(array $config = []): array
    {
        if ($this->getBlurable() !== null) {
            $config['blurable'] = $this->getBlurable();
        }

        if ($this->getClassName() !== null) {
            $config['className'] = $this->getClassName();
        }

        if ($this->getInfo() !== null) {
            $config['info'] = $this->getInfo();
        }

        if ($this->getItems() !== null) {
            $config['items'] = $this->getItems();
        }

        if ($this->getSelector() !== null) {
            $config['selector'] = $this->getSelector();
        }

        if ($this->getStyle() !== null) {
            $config['style'] = $this->getStyle();
        }

        return parent::getJavaScriptConfiguration($config);
    }
}
