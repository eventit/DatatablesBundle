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

use Closure;
use Sg\DatatablesBundle\Datatable\Filter\TextFilter;
use Sg\DatatablesBundle\Datatable\Helper;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LinkColumn extends AbstractColumn
{
    // The LinkColumn is filterable.
    use FilterableTrait;

    /**
     * @var mixed|Closure|array
     */
    public mixed $emptyValue = null;

    /**
     * The route name
     * A required option.
     */
    protected string $route = '';

    /**
     * The route params.
     */
    protected array|Closure $routeParams;

    /**
     * The text rendered if data is null.
     */
    protected string $empty_value = '';

    /**
     * The text displayed for each item in the link.
     */
    protected ?Closure $text = null;

    /**
     * The separator for to-many fields.
     */
    protected string $separator = '';

    /**
     * Function to filter the toMany results.
     */
    protected ?Closure $filterFunction = null;

    /**
     * Boolean to indicate if it's an email link.
     */
    protected bool $email = false;

    // -------------------------------------------------
    // ColumnInterface
    // -------------------------------------------------

    public function renderSingleField(array &$row): static
    {
        $path = Helper::getDataPropertyPath($this->data);

        if ($this->accessor->isReadable($row, $path)) {
            if ($this->getEmail()) {
                $content = '<a href="mailto:';
                $content .= $this->accessor->getValue($row, $path);
                $content .= '">';

                if (\is_callable($this->text)) {
                    $content .= \call_user_func($this->text, $row);
                } else {
                    $content .= $this->accessor->getValue($row, $path);
                }

                $content .= '</a>';
            } else {
                $renderRouteParams = \is_callable($this->routeParams) ? \call_user_func($this->routeParams, $row) : $this->routeParams;

                if (\in_array(null, $renderRouteParams, true)) {
                    $content = $this->getEmptyValue();
                } else {
                    $content = '<a href="';
                    $content .= $this->router->generate($this->getRoute(), $renderRouteParams);
                    $content .= '">';

                    if (\is_callable($this->text)) {
                        $content .= \call_user_func($this->text, $row);
                    } else {
                        $content .= $this->accessor->getValue($row, $path);
                    }

                    $content .= '</a>';
                }
            }
            $this->accessor->setValue($row, $path, $content);
        }

        return $this;
    }

    public function renderToMany(array &$row): static
    {
        $value = null;
        $path = Helper::getDataPropertyPath($this->data, $value);
        $content = '';

        if ($this->accessor->isReadable($row, $path)) {
            $entries = $this->accessor->getValue($row, $path);

            if ($this->isEditableContentRequired($row)) {
                // e.g. comments[ ].createdBy.username
                //     => $path = [comments]
                //     => $value = [createdBy][username]

                if ((is_countable($entries) ? \count($entries) : 0) > 0) {
                    foreach ($entries as $key => $entry) {
                        $currentPath = Helper::getPropertyPathObjectNotation($path, $key, $value);

                        $content = $this->renderTemplate(
                            $this->accessor->getValue($row, $currentPath)
                        );

                        $this->accessor->setValue($row, $currentPath, $content);
                    }
                }
            // no placeholder - leave this blank
            } else {
                if (null !== $this->getFilterFunction()) {
                    $entries = array_values(array_filter($entries, $this->getFilterFunction()));
                }

                if ($entries !== []) {
                    foreach ($entries as $i => $entry) {
                        $renderRouteParams = \is_callable($this->routeParams) ? \call_user_func($this->routeParams, $entry) : $this->routeParams;
                        $content .= '<a href="';
                        $content .= $this->router->generate($this->getRoute(), $renderRouteParams);
                        $content .= '">';
                        if (\is_callable($this->text)) {
                            $content .= \call_user_func($this->text, $entry);
                        }
                        $content .= '</a>';
                        if ($i < (is_countable($entries) ? \count($entries) : 0) - 1) {
                            $content .= $this->separator;
                        }
                    }

                    $this->accessor->setValue($row, $path, $content);
                } else {
                    $this->accessor->setValue($row, $path, $this->getEmptyValue());
                }
            }
        }

        return $this;
    }

    public function getCellContentTemplate(): string
    {
        return '@SgDatatables/render/link.html.twig';
    }

    public function getColumnType(): string
    {
        return parent::ACTION_COLUMN;
    }

    // -------------------------------------------------
    // Options
    // -------------------------------------------------

    public function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'filter' => [TextFilter::class, []],
            'route' => '',
            'route_params' => [],
            'empty_value' => '',
            'text' => null,
            'separator' => '',
            'filterFunction' => null,
            'email' => false,
        ]);

        $resolver->setAllowedTypes('filter', 'array');
        $resolver->setAllowedTypes('route', 'string');
        $resolver->setAllowedTypes('route_params', ['array', 'Closure']);
        $resolver->setAllowedTypes('empty_value', ['string']);
        $resolver->setAllowedTypes('text', ['Closure', 'null']);
        $resolver->setAllowedTypes('separator', ['string']);
        $resolver->setAllowedTypes('filterFunction', ['null', 'Closure']);
        $resolver->setAllowedTypes('email', ['bool']);

        return $this;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function setRoute(string $route): static
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Get route params.
     */
    public function getRouteParams(): array|Closure
    {
        return $this->routeParams;
    }

    /**
     * Set route params.
     */
    public function setRouteParams(array|Closure $routeParams): static
    {
        $this->routeParams = $routeParams;

        return $this;
    }

    /**
     * Get empty value.
     */
    public function getEmptyValue(): array|Closure|string|null
    {
        return $this->emptyValue;
    }

    /**
     * Set empty value.
     */
    public function setEmptyValue(array|Closure $emptyValue): static
    {
        $this->emptyValue = $emptyValue;

        return $this;
    }

    public function getText(): ?Closure
    {
        return $this->text;
    }

    public function setText(?Closure $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }

    public function setSeparator(string $separator): static
    {
        $this->separator = $separator;

        return $this;
    }

    /**
     * Get filter function.
     */
    public function getFilterFunction(): ?Closure
    {
        return $this->filterFunction;
    }

    /**
     * Set filter function.
     */
    public function setFilterFunction(?Closure $filterFunction): static
    {
        $this->filterFunction = $filterFunction;

        return $this;
    }

    /**
     * Get email boolean.
     */
    public function getEmail(): bool
    {
        return $this->email;
    }

    /**
     * Set email boolean.
     */
    public function setEmail(bool $email): static
    {
        $this->email = $email;

        return $this;
    }

    // -------------------------------------------------
    // Helper
    // -------------------------------------------------

    /**
     * Render template.
     */
    private function renderTemplate(?string $data): string
    {
        return $this->twig->render(
            $this->getCellContentTemplate(),
            [
                'data' => $data,
            ]
        );
    }
}
