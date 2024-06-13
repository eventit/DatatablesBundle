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

namespace Sg\DatatablesBundle\Datatable\Action;

use Closure;
use RuntimeException;
use Sg\DatatablesBundle\Datatable\HtmlContainerTrait;
use Sg\DatatablesBundle\Datatable\OptionsTrait;
use Sg\DatatablesBundle\Datatable\RenderIfTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Action
{
    /*
     * An Action has a 'start_html' and a 'end_html' option.
     * <startHtml>action</endHtml>
     */
    use HtmlContainerTrait;

    use OptionsTrait;

    // Render an Action only if conditions are TRUE.
    use RenderIfTrait;

    /**
     * The name of the Action route.
     * Default: null.
     */
    protected ?string $route = null;

    /**
     * The route parameters.
     * Default: null.
     */
    protected array|Closure|null $routeParameters = null;

    /**
     * An icon for the Action.
     * Default: null.
     */
    protected ?string $icon = null;

    /**
     * A label for the Action.
     * Default: null.
     */
    protected ?string $label = null;

    /**
     * Show confirm message if true.
     * Default: false.
     */
    protected bool $confirm = false;

    /**
     * The confirm message.
     * Default: null.
     */
    protected ?string $confirmMessage = null;

    /**
     * HTML attributes (except 'href' and 'value').
     * Default: null.
     */
    protected array|Closure|null $attributes = null;

    /**
     * Render a button instead of a link.
     * Default: false.
     */
    protected bool $button = false;

    /**
     * The button value.
     * Default: null.
     */
    protected ?string $buttonValue = null;

    /**
     * Use the Datatable-Name as prefix for the button value.
     * Default: false.
     */
    protected bool $buttonValuePrefix = false;

    public function __construct(protected string $datatableName)
    {
        $this->initOptions();
    }

    // -------------------------------------------------
    // Options
    // -------------------------------------------------

    /**
     * Configure options.
     */
    public function configureOptions(OptionsResolver $resolver): static
    {
        $resolver->setDefaults([
            'route' => null,
            'route_parameters' => null,
            'icon' => null,
            'label' => null,
            'confirm' => false,
            'confirm_message' => null,
            'attributes' => null,
            'button' => false,
            'button_value' => null,
            'button_value_prefix' => false,
            'render_if' => null,
            'start_html' => null,
            'end_html' => null,
        ]);

        $resolver->setAllowedTypes('route', ['null', 'string']);
        $resolver->setAllowedTypes('route_parameters', ['null', 'array', 'Closure']);
        $resolver->setAllowedTypes('icon', ['null', 'string']);
        $resolver->setAllowedTypes('label', ['null', 'string']);
        $resolver->setAllowedTypes('confirm', 'bool');
        $resolver->setAllowedTypes('confirm_message', ['null', 'string']);
        $resolver->setAllowedTypes('attributes', ['null', 'array', 'Closure']);
        $resolver->setAllowedTypes('button', 'bool');
        $resolver->setAllowedTypes('button_value', ['null', 'string']);
        $resolver->setAllowedTypes('button_value_prefix', 'bool');
        $resolver->setAllowedTypes('render_if', ['null', 'Closure']);
        $resolver->setAllowedTypes('start_html', ['null', 'string']);
        $resolver->setAllowedTypes('end_html', ['null', 'string']);

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): static
    {
        $this->route = $route;

        return $this;
    }

    public function getRouteParameters(): array|Closure|null
    {
        return $this->routeParameters;
    }

    public function setRouteParameters(array|Closure|null $routeParameters): static
    {
        $this->routeParameters = $routeParameters;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function isConfirm(): bool
    {
        return $this->confirm;
    }

    public function setConfirm(bool $confirm): static
    {
        $this->confirm = $confirm;

        return $this;
    }

    public function getConfirmMessage(): ?string
    {
        return $this->confirmMessage;
    }

    public function setConfirmMessage(?string $confirmMessage): static
    {
        $this->confirmMessage = $confirmMessage;

        return $this;
    }

    public function getAttributes(): array|Closure|null
    {
        return $this->attributes;
    }

    /**
     * @throws RuntimeException
     */
    public function setAttributes(array|Closure|null $attributes): static
    {
        if (\is_array($attributes)) {
            if (\array_key_exists('href', $attributes)) {
                throw new RuntimeException('Action::setAttributes(): The href attribute is not allowed in this context.');
            }

            if (\array_key_exists('value', $attributes)) {
                throw new RuntimeException('Action::setAttributes(): The value attribute is not allowed in this context.');
            }
        }

        $this->attributes = $attributes;

        return $this;
    }

    public function isButton(): bool
    {
        return $this->button;
    }

    public function setButton(bool $button): static
    {
        $this->button = $button;

        return $this;
    }

    public function getButtonValue(): ?string
    {
        return $this->buttonValue;
    }

    public function setButtonValue(?string $buttonValue): static
    {
        $this->buttonValue = $buttonValue;

        return $this;
    }

    public function isButtonValuePrefix(): bool
    {
        return $this->buttonValuePrefix;
    }

    public function setButtonValuePrefix(bool $buttonValuePrefix): static
    {
        $this->buttonValuePrefix = $buttonValuePrefix;

        return $this;
    }

    public function getDatatableName(): string
    {
        return $this->datatableName;
    }

    public function setDatatableName(string $datatableName): static
    {
        $this->datatableName = $datatableName;

        return $this;
    }
}
