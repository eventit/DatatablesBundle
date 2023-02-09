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

namespace Sg\DatatablesBundle\Datatable;

use Exception;
use Sg\DatatablesBundle\Datatable\Extension\Buttons;
use Sg\DatatablesBundle\Datatable\Extension\Exception\ExtensionAlreadyRegisteredException;
use Sg\DatatablesBundle\Datatable\Extension\ExtensionInterface;
use Sg\DatatablesBundle\Datatable\Extension\FixedHeaderFooter;
use Sg\DatatablesBundle\Datatable\Extension\Responsive;
use Sg\DatatablesBundle\Datatable\Extension\RowGroup;
use Sg\DatatablesBundle\Datatable\Extension\Select;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Extensions
{
    use OptionsTrait;

    /**
     * The Buttons extension.
     * Default: null.
     */
    protected array|bool|null|Buttons $buttons = null;

    /**
     * The Responsive Extension.
     * Automatically optimise the layout for different screen sizes.
     * Default: null.
     */
    protected Responsive|bool|array|null $responsive = null;

    /**
     * The Select Extension.
     * Select adds item selection capabilities to a DataTable.
     * Default: null.
     */
    protected Select|array|bool|null $select = null;

    /**
     * The RowGroup Extension.
     * Automatically group rows.
     * Default: null.
     */
    protected array|bool|null|RowGroup $rowGroup = null;

    /** @var ExtensionInterface[] */
    protected array $extensions = [];

    protected ?FixedHeaderFooter $fixedHeaderFooter = null;

    public function __construct()
    {
        $this->initOptions();
    }

    /**
     * @return $this
     */
    public function configureOptions(OptionsResolver $resolver): static
    {
        $resolver->setDefaults([
            'buttons' => null,
            'responsive' => null,
            'select' => null,
            'row_group' => null,
        ]);

        $resolver->setAllowedTypes('buttons', ['null', 'array', 'bool']);
        $resolver->setAllowedTypes('responsive', ['null', 'array', 'bool']);
        $resolver->setAllowedTypes('select', ['null', 'array', 'bool']);
        $resolver->setAllowedTypes('row_group', ['null', 'array', 'bool']);

        foreach (array_keys($this->extensions) as $name) {
            $resolver->setDefault($name, null);
            $resolver->addAllowedTypes($name, ['null', 'array', 'bool']);
        }

        return $this;
    }

    public function getButtons(): Buttons|bool|array|null
    {
        return $this->buttons;
    }

    /**
     * @throws Exception
     */
    public function setButtons(bool|array|null $buttons): static
    {
        if (\is_array($buttons)) {
            $newButton = new Buttons();
            $this->buttons = $newButton->set($buttons);
        } else {
            $this->buttons = $buttons;
        }

        return $this;
    }

    public function getResponsive(): array|bool|Responsive|null
    {
        return $this->responsive;
    }

    public function setResponsive(bool|array|null $responsive): static
    {
        if (\is_array($responsive)) {
            $newResponsive = new Responsive();
            $this->responsive = $newResponsive->set($responsive);
        } else {
            $this->responsive = $responsive;
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function addExtension(ExtensionInterface $extension): static
    {
        $extName = $extension->getName();
        if ($this->hasExtension($extName)) {
            throw new ExtensionAlreadyRegisteredException(
                sprintf(
                    'Extension with name "%s" already registered',
                    $extName
                )
            );
        }

        $this->extensions[$extName] = $extension;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function getExtension(string $name): ExtensionInterface
    {
        if (! $this->hasExtension($name)) {
            throw new ExtensionAlreadyRegisteredException(
                sprintf(
                    'Extension with name "%s" already registered',
                    $name
                )
            );
        }

        return $this->extensions[$name];
    }

    /**
     * @throws Exception
     */
    public function enableExtension(string $name): void
    {
        $this->getExtension($name)->setEnabled(true);
    }

    /**
     * @throws Exception
     *
     * @return $this
     */
    public function setExtensionOptions(string $name, array|bool $options): static
    {
        $extension = $this->getExtension($name);
        $extension->setEnabled(true);
        $extension->setOptions($options);

        return $this;
    }

    public function hasExtension(string $name): bool
    {
        return \array_key_exists($name, $this->extensions);
    }

    /**
     * @return ExtensionInterface[]
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function getSelect(): bool|array|Select|null
    {
        return $this->select;
    }

    public function setSelect(bool|array|null $select): static
    {
        if (\is_array($select)) {
            $newSelect = new Select();
            $this->select = $newSelect->set($select);
        } else {
            $this->select = $select;
        }

        return $this;
    }

    public function getRowGroup(): RowGroup|bool|array|null
    {
        return $this->rowGroup;
    }

    /**
     * @throws Exception
     */
    public function setRowGroup(bool|array|null $rowGroup): static
    {
        if (\is_array($rowGroup)) {
            $newRowGroup = new RowGroup();
            $this->rowGroup = $newRowGroup->set($rowGroup);
        } else {
            $this->rowGroup = $rowGroup;
        }

        return $this;
    }
}
