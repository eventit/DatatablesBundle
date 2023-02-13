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

use Closure;
use Sg\DatatablesBundle\Datatable\OptionsTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractEditable implements EditableInterface
{
    use OptionsTrait;

    // -------------------------------------------------
    // X-editable Options
    // -------------------------------------------------

    /**
     * Url for submit.
     * Default: 'sg_datatables_edit'.
     */
    protected string $url = 'sg_datatables_edit';

    /**
     * Additional params for submit It is appended to original ajax data (pk, name and value).
     * Default: null.
     */
    protected ?array $params = null;

    /**
     * Value that will be displayed in input if original field value is empty (null|undefined|'').
     * Default: null.
     */
    protected ?string $defaultValue = null;

    /**
     * Css class applied when editable text is empty.
     * Default: 'editable-empty'.
     */
    protected string $emptyClass = 'editable-empty';

    /**
     * Text shown when element is empty.
     * Default: 'Empty'.
     */
    protected string $emptyText = 'Empty';

    /**
     * Color used to highlight element after update.
     * Default: '#FFFF80'.
     */
    protected string $highlight = '#FFFF80';

    /**
     * Mode of editable, can be 'popup' or 'inline'.
     * Default: 'popup'.
     */
    protected string $mode = 'popup';

    /**
     * Name of field. Will be submitted on server. Can be taken from id attribute.
     * Default: null.
     */
    protected ?string $name = null;

    /**
     * Primary key of editable object.
     * Default: 'id'.
     */
    protected string $pk = 'id';

    // -------------------------------------------------
    // Custom Options
    // -------------------------------------------------

    /**
     * Editable only if conditions are True.
     */
    protected ?Closure $editableIf = null;

    public function __construct()
    {
        $this->initOptions();
    }

    // -------------------------------------------------
    // EditableInterface
    // -------------------------------------------------

    public function callEditableIfClosure(array $row = [])
    {
        if ($this->editableIf instanceof Closure) {
            return \call_user_func($this->editableIf, $row);
        }

        return true;
    }

    public function getPk(): string
    {
        return $this->pk;
    }

    public function getEmptyText(): string
    {
        return $this->emptyText;
    }

    // -------------------------------------------------
    // Options
    // -------------------------------------------------

    public function configureOptions(OptionsResolver $resolver): static
    {
        $resolver->setDefaults([
            'url' => 'sg_datatables_edit',
            'params' => null,
            'default_value' => null,
            'empty_class' => 'editable-empty',
            'empty_text' => 'Empty',
            'highlight' => '#FFFF80',
            'mode' => 'popup',
            'name' => null,
            'pk' => 'id',
            'editable_if' => null,
        ]);

        $resolver->setAllowedTypes('url', 'string');
        $resolver->setAllowedTypes('params', ['null', 'array']);
        $resolver->setAllowedTypes('default_value', ['string', 'null']);
        $resolver->setAllowedTypes('empty_class', 'string');
        $resolver->setAllowedTypes('empty_text', 'string');
        $resolver->setAllowedTypes('highlight', 'string');
        $resolver->setAllowedTypes('mode', 'string');
        $resolver->setAllowedTypes('name', ['string', 'null']);
        $resolver->setAllowedTypes('pk', 'string');
        $resolver->setAllowedTypes('editable_if', ['Closure', 'null']);

        $resolver->setAllowedValues('mode', ['popup', 'inline']);

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function setParams(?array $params): static
    {
        $this->params = $params;

        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?string $defaultValue): static
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function getEmptyClass(): string
    {
        return $this->emptyClass;
    }

    public function setEmptyClass(string $emptyClass): static
    {
        $this->emptyClass = $emptyClass;

        return $this;
    }

    public function setEmptyText(string $emptyText): static
    {
        $this->emptyText = $emptyText;

        return $this;
    }

    public function getHighlight(): string
    {
        return $this->highlight;
    }

    public function setHighlight(string $highlight): static
    {
        $this->highlight = $highlight;

        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function setPk(string $pk): static
    {
        $this->pk = $pk;

        return $this;
    }

    public function getEditableIf(): ?Closure
    {
        return $this->editableIf;
    }

    public function setEditableIf(?Closure $editableIf): static
    {
        $this->editableIf = $editableIf;

        return $this;
    }
}
