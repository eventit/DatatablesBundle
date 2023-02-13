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
use RuntimeException;
use Sg\DatatablesBundle\Datatable\Action\Action;
use Sg\DatatablesBundle\Datatable\Helper;
use Sg\DatatablesBundle\Datatable\HtmlContainerTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionColumn extends AbstractColumn
{
    /*
     * This Column has a 'start_html' and a 'end_html' option.
     * <startHtml> action1 action2 actionX </endHtml>
     */
    use HtmlContainerTrait;

    /**
     * The Actions container.
     * A required option.
     */
    protected array $actions = [];

    // -------------------------------------------------
    // ColumnInterface
    // -------------------------------------------------

    public function dqlConstraint($dql): bool
    {
        return null === $dql;
    }

    public function isSelectColumn(): bool
    {
        return false;
    }

    public function addDataToOutputArray(array &$row): static
    {
        $actionRowItems = [];

        /** @var Action $action */
        foreach ($this->actions as $actionKey => $action) {
            $actionRowItems[$actionKey] = $action->callRenderIfClosure($row);
        }

        $row['sg_datatables_actions'][$this->getIndex()] = $actionRowItems;

        return $this;
    }

    public function renderSingleField(array &$row): static
    {
        $parameters = [];
        $attributes = [];
        $values = [];

        /** @var Action $action */
        foreach ($this->actions as $actionKey => $action) {
            $routeParameters = $action->getRouteParameters();
            if (\is_array($routeParameters)) {
                foreach ($routeParameters as $key => $value) {
                    if (isset($row[$value])) {
                        $parameters[$actionKey][$key] = $row[$value];
                    } else {
                        $path = Helper::getDataPropertyPath($value);
                        $entry = $this->accessor->getValue($row, $path);

                        $parameters[$actionKey][$key] = empty($entry) ? $value : $entry;
                    }
                }
            } elseif ($routeParameters instanceof Closure) {
                $parameters[$actionKey] = $routeParameters($row);
            } else {
                $parameters[$actionKey] = [];
            }

            $actionAttributes = $action->getAttributes();
            if (\is_array($actionAttributes)) {
                $attributes[$actionKey] = $actionAttributes;
            } elseif ($actionAttributes instanceof Closure) {
                $attributes[$actionKey] = $actionAttributes($row);
            } else {
                $attributes[$actionKey] = [];
            }

            if ($action->isButton()) {
                if (null !== $action->getButtonValue()) {
                    if (isset($row[$action->getButtonValue()])) {
                        $values[$actionKey] = $row[$action->getButtonValue()];
                    } else {
                        $values[$actionKey] = $action->getButtonValue();
                    }

                    if (\is_bool($values[$actionKey])) {
                        $values[$actionKey] = (int) $values[$actionKey];
                    }

                    if ($action->isButtonValuePrefix()) {
                        $values[$actionKey] = 'sg-datatables-' . $this->getDatatableName() . '-action-button-' . $actionKey . '-' . $values[$actionKey];
                    }
                } else {
                    $values[$actionKey] = null;
                }
            }
        }

        $row[$this->getIndex()] = $this->twig->render(
            $this->getCellContentTemplate(),
            [
                'actions' => $this->actions,
                'route_parameters' => $parameters,
                'attributes' => $attributes,
                'values' => $values,
                'render_if_actions' => $row['sg_datatables_actions'][$this->index],
                'start_html_container' => $this->startHtml,
                'end_html_container' => $this->endHtml,
            ]
        );

        return $this;
    }

    public function renderToMany(array &$row): static
    {
        throw new RuntimeException('ActionColumn::renderToMany(): This function should never be called.');
    }

    public function getCellContentTemplate(): string
    {
        return '@SgDatatables/render/action.html.twig';
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

        $resolver->remove('dql');
        $resolver->remove('data');
        $resolver->remove('default_content');

        // the 'orderable' option is removed, but via getter it returns 'false' for the view
        $resolver->remove('orderable');
        $resolver->remove('order_data');
        $resolver->remove('order_sequence');

        // the 'searchable' option is removed, but via getter it returns 'false' for the view
        $resolver->remove('searchable');

        $resolver->remove('join_type');
        $resolver->remove('type_of_field');

        $resolver->setRequired(['actions']);

        $resolver->setDefaults([
            'start_html' => null,
            'end_html' => null,
        ]);

        $resolver->setAllowedTypes('actions', 'array');
        $resolver->setAllowedTypes('start_html', ['null', 'string']);
        $resolver->setAllowedTypes('end_html', ['null', 'string']);

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    /**
     * @return Action[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @throws RuntimeException
     *
     * @return $this
     */
    public function setActions(array $actions): static
    {
        if ($actions !== []) {
            foreach ($actions as $action) {
                $this->addAction($action);
            }
        } else {
            throw new RuntimeException('ActionColumn::setActions(): The actions array should contain at least one element.');
        }

        return $this;
    }

    /**
     * Add action.
     */
    public function addAction(array $action): static
    {
        $newAction = new Action($this->datatableName);
        $this->actions[] = $newAction->set($action);

        return $this;
    }

    /**
     * Remove action.
     */
    public function removeAction(Action $action): static
    {
        foreach ($this->actions as $k => $a) {
            if ($action === $a) {
                unset($this->actions[$k]);

                break;
            }
        }

        return $this;
    }

    public function getOrderable(): bool
    {
        return false;
    }

    public function getSearchable(): bool
    {
        return false;
    }
}
