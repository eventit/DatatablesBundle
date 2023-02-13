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

namespace Sg\DatatablesBundle\Twig;

use Closure;
use JsonException;
use Sg\DatatablesBundle\Datatable\Action\Action;
use Sg\DatatablesBundle\Datatable\Column\ColumnInterface;
use Sg\DatatablesBundle\Datatable\DatatableInterface;
use Sg\DatatablesBundle\Datatable\Filter\FilterInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Twig\Environment as Twig_Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class DatatableTwigExtension extends AbstractExtension
{
    protected \Symfony\Component\PropertyAccess\PropertyAccessor $accessor;

    public function __construct()
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    public function getName(): string
    {
        return 'sg_datatables_twig_extension';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'sg_datatables_render',
                fn (\Twig\Environment $twig, DatatableInterface $datatable): string => $this->datatablesRender($twig, $datatable),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFunction(
                'sg_datatables_render_html',
                fn (\Twig\Environment $twig, DatatableInterface $datatable): string => $this->datatablesRenderHtml($twig, $datatable),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFunction(
                'sg_datatables_render_js',
                fn (\Twig\Environment $twig, DatatableInterface $datatable): string => $this->datatablesRenderJs($twig, $datatable),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFunction(
                'sg_datatable_extensions_render',
                fn (\Twig\Environment $twig, DatatableInterface $datatable): string => $this->datatablesRenderExtensions($twig, $datatable),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFunction(
                'sg_datatables_render_filter',
                fn (\Twig\Environment $twig, DatatableInterface $datatable, ColumnInterface $column, string $position): string => $this->datatablesRenderFilter($twig, $datatable, $column, $position),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFunction(
                'sg_datatables_render_multiselect_actions',
                fn (\Twig\Environment $twig, ColumnInterface $multiselectColumn, int $pipeline): string => $this->datatablesRenderMultiselectActions($twig, $multiselectColumn, $pipeline),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('sg_datatables_bool_var', fn ($value): string => $this->boolVar($value)),
        ];
    }

    public function datatablesRender(Twig_Environment $twig, DatatableInterface $datatable): string
    {
        return $twig->render(
            '@SgDatatables/datatable/datatable.html.twig',
            [
                'sg_datatables_view' => $datatable,
            ]
        );
    }

    public function datatablesRenderHtml(Twig_Environment $twig, DatatableInterface $datatable): string
    {
        return $twig->render(
            '@SgDatatables/datatable/datatable_html.html.twig',
            [
                'sg_datatables_view' => $datatable,
            ]
        );
    }

    public function datatablesRenderJs(Twig_Environment $twig, DatatableInterface $datatable): string
    {
        return $twig->render(
            '@SgDatatables/datatable/datatable_js.html.twig',
            [
                'sg_datatables_view' => $datatable,
            ]
        );
    }

    /**
     * @throws JsonException
     */
    public function datatablesRenderExtensions(Twig_Environment $twig, DatatableInterface $datatable): string
    {
        $extensionRegistry = $datatable->getExtensions();
        $jsParts = [];

        foreach ($extensionRegistry->getExtensions() as $extension) {
            if (! $extension->isEnabled()) {
                continue;
            }
            $config = $extension->getJavaScriptConfiguration();

            $key = key($config);
            $jsParts[$key] = $config[$key];
        }

        return json_encode($jsParts, JSON_THROW_ON_ERROR);
    }

    public function datatablesRenderFilter(
        Twig_Environment $twig,
        DatatableInterface $datatable,
        ColumnInterface $column,
        string $position
    ): string {
        /** @var FilterInterface $filter */
        $filter = $this->accessor->getValue($column, 'filter');
        $index = $this->accessor->getValue($column, 'index');
        $searchColumn = $this->accessor->getValue($filter, 'searchColumn');

        if (null !== $searchColumn) {
            $columns = $datatable->getColumnBuilder()->getColumnNames();
            $searchColumnIndex = $columns[$searchColumn];
        } else {
            $searchColumnIndex = $index;
        }

        return $twig->render(
            $filter->getTemplate(),
            [
                'column' => $column,
                'search_column_index' => $searchColumnIndex,
                'datatable_name' => $datatable->getName(),
                'position' => $position,
            ]
        );
    }

    public function datatablesRenderMultiselectActions(
        Twig_Environment $twig,
        ColumnInterface $multiselectColumn,
        int $pipeline
    ): string {
        $parameters = [];
        $values = [];
        $actions = $this->accessor->getValue($multiselectColumn, 'actions');
        $domId = $this->accessor->getValue($multiselectColumn, 'renderActionsToId');
        $datatableName = $this->accessor->getValue($multiselectColumn, 'datatableName');

        /** @var Action $action */
        foreach ($actions as $actionKey => $action) {
            $routeParameters = $action->getRouteParameters();
            if (\is_array($routeParameters)) {
                foreach ($routeParameters as $key => $value) {
                    $parameters[$actionKey][$key] = $value;
                }
            } elseif ($routeParameters instanceof Closure) {
                $parameters[$actionKey] = $routeParameters();
            } else {
                $parameters[$actionKey] = [];
            }

            if ($action->isButton()) {
                if (null !== $action->getButtonValue()) {
                    $values[$actionKey] = $action->getButtonValue();

                    if ($action->isButtonValuePrefix()) {
                        $values[$actionKey] = 'sg-datatables-' . $datatableName . '-multiselect-button-' . $actionKey . '-' . $values[$actionKey];
                    }
                } else {
                    $values[$actionKey] = null;
                }
            }
        }

        return $twig->render(
            '@SgDatatables/datatable/multiselect_actions.html.twig',
            [
                'actions' => $actions,
                'route_parameters' => $parameters,
                'values' => $values,
                'datatable_name' => $datatableName,
                'dom_id' => $domId,
                'pipeline' => $pipeline,
            ]
        );
    }

    /**
     * Renders: {{ var ? 'true' : 'false' }}.
     */
    public function boolVar($value): string
    {
        if ($value) {
            return 'true';
        }

        return 'false';
    }
}
