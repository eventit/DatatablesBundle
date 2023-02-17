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

use Sg\DatatablesBundle\Datatable\Editable\EditableInterface;
use Sg\DatatablesBundle\Datatable\Filter\SelectFilter;
use Sg\DatatablesBundle\Datatable\Helper;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanColumn extends AbstractColumn
{
    use EditableTrait;

    use FilterableTrait;

    /**
     * @internal
     */
    public const RENDER_TRUE_VALUE = 'true';

    /**
     * @internal
     */
    public const RENDER_FALSE_VALUE = 'false';

    /**
     * The icon for a value that is true.
     * Default: null.
     */
    protected ?string $trueIcon = null;

    /**
     * The icon for a value that is false.
     * Default: null.
     */
    protected ?string $falseIcon = null;

    /**
     * The label for a value that is true.
     * Default: null.
     */
    protected ?string $trueLabel = null;

    /**
     * The label for a value that is false.
     * Default: null.
     */
    protected ?string $falseLabel = null;

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

        if ($this->accessor->isReadable($row, $path)) {
            $entries = $this->accessor->getValue($row, $path);

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

    public function getCellContentTemplate(): string
    {
        return '@SgDatatables/render/boolean.html.twig';
    }

    public function renderPostCreateDatatableJsContent(): ?string
    {
        if (! $this->editable instanceof EditableInterface) {
            return null;
        }

        return $this->twig->render(
            '@SgDatatables/column/column_post_create_dt.js.twig',
            [
                'column_class_editable_selector' => $this->getColumnClassEditableSelector(),
                'editable_options' => $this->editable,
                'entity_class_name' => $this->getEntityClassName(),
                'column_dql' => $this->dql,
                'original_type_of_field' => $this->getOriginalTypeOfField(),
            ]
        );
    }

    // -------------------------------------------------
    // Options
    // -------------------------------------------------

    public function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'filter' => [
                    SelectFilter::class,
                    [
                        'search_type' => 'eq',
                        'select_options' => ['' => 'Any', '1' => 'Yes', '0' => 'No'],
                    ],
                ],
                'true_icon' => null,
                'false_icon' => null,
                'true_label' => null,
                'false_label' => null,
                'editable' => null,
            ]
        );

        $resolver->setAllowedTypes('filter', 'array');
        $resolver->setAllowedTypes('true_icon', ['null', 'string']);
        $resolver->setAllowedTypes('false_icon', ['null', 'string']);
        $resolver->setAllowedTypes('true_label', ['null', 'string']);
        $resolver->setAllowedTypes('false_label', ['null', 'string']);
        $resolver->setAllowedTypes('editable', ['null', 'array']);

        $resolver->setNormalizer('true_label', function (Options $options, $value) {
            if (null !== $options['true_icon']) {
                return $value;
            }

            return $value ?? self::RENDER_TRUE_VALUE;
        });

        $resolver->setNormalizer('false_label', function (Options $options, $value) {
            if (null !== $options['false_icon']) {
                return $value;
            }

            return $value ?? self::RENDER_FALSE_VALUE;
        });

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    public function getTrueIcon(): ?string
    {
        return $this->trueIcon;
    }

    public function setTrueIcon(?string $trueIcon): static
    {
        $this->trueIcon = $trueIcon;

        return $this;
    }

    public function getFalseIcon(): ?string
    {
        return $this->falseIcon;
    }

    public function setFalseIcon(?string $falseIcon): static
    {
        $this->falseIcon = $falseIcon;

        return $this;
    }

    public function getTrueLabel(): ?string
    {
        return $this->trueLabel;
    }

    public function setTrueLabel(?string $trueLabel): static
    {
        $this->trueLabel = $trueLabel;

        return $this;
    }

    public function getFalseLabel(): ?string
    {
        return $this->falseLabel;
    }

    public function setFalseLabel(?string $falseLabel): static
    {
        $this->falseLabel = $falseLabel;

        return $this;
    }

    // -------------------------------------------------
    // Helper
    // -------------------------------------------------

    private function renderTemplate(mixed $data, ?string $pk = null, ?string $path = null): string
    {
        $renderVars = [
            'data' => $this->isCustomDql() && \in_array($data, [0, 1, '0', '1'], true) ? (bool) $data : $data,
            'default_content' => $this->getDefaultContent(),
            'true_label' => $this->trueLabel,
            'true_icon' => $this->trueIcon,
            'false_label' => $this->falseLabel,
            'false_icon' => $this->falseIcon,
        ];

        // editable vars
        if (null !== $pk) {
            $renderVars = array_merge($renderVars, [
                'column_class_editable_selector' => $this->getColumnClassEditableSelector(),
                'pk' => $pk,
                'path' => $path,
                'empty_text' => $this->editable->getEmptyText(),
            ]);
        }

        return $this->twig->render(
            $this->getCellContentTemplate(),
            $renderVars
        );
    }
}
