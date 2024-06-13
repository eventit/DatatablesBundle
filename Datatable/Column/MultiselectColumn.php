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

use Exception;
use RuntimeException;
use Sg\DatatablesBundle\Datatable\Action\MultiselectAction;
use Sg\DatatablesBundle\Datatable\RenderIfTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MultiselectColumn extends ActionColumn
{
    // Render a Checkbox only if conditions are TRUE.
    use RenderIfTrait;

    /**
     * HTML <input> Tag attributes (except 'type' and 'value').
     * Default: null.
     */
    protected ?array $attributes = null;

    /**
     * A checkbox value, generated by column name.
     * Default: 'id'.
     */
    protected string $value = 'id';

    /**
     * Use the Datatable-Name as prefix for the value.
     * Default: false.
     */
    protected bool $valuePrefix = false;

    /**
     * Id selector where all multiselect actions are rendered.
     * Default: null ('sg-datatables-{{ sg_datatables_view.name }}-multiselect-actions').
     */
    protected ?string $renderActionsToId = null;

    // -------------------------------------------------
    // ColumnInterface
    // -------------------------------------------------

    public function isUnique(): bool
    {
        return true;
    }

    public function getOptionsTemplate(): string
    {
        return '@SgDatatables/column/multiselect.html.twig';
    }

    public function addDataToOutputArray(array &$row): static
    {
        $row['sg_datatables_cbox'] = $this->callRenderIfClosure($row);

        return $this;
    }

    public function renderSingleField(array &$row): static
    {
        $value = $row[$this->value];

        if (\is_bool($value)) {
            $value = (int) $value;
        }

        if ($this->valuePrefix) {
            $value = 'sg-datatables-' . $this->getDatatableName() . '-checkbox-' . $value;
        }

        $row[$this->getIndex()] = $this->twig->render(
            $this->getCellContentTemplate(),
            [
                'attributes' => $this->attributes,
                'value' => $value,
                'start_html' => $this->startHtml,
                'end_html' => $this->endHtml,
                'render_if_cbox' => $row['sg_datatables_cbox'],
            ]
        );

        return $this;
    }

    public function getCellContentTemplate(): string
    {
        return '@SgDatatables/render/multiselect.html.twig';
    }

    public function allowedPositions(): ?array
    {
        return [0, self::LAST_POSITION];
    }

    public function getColumnType(): string
    {
        return parent::MULTISELECT_COLUMN;
    }

    // -------------------------------------------------
    // Options
    // -------------------------------------------------

    /**
     * Configure options.
     */
    public function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        // predefined in the view as Checkbox
        $resolver->remove('title');

        $resolver->setDefaults([
            'attributes' => null,
            'value' => 'id',
            'value_prefix' => false,
            'render_actions_to_id' => null,
            'render_if' => null,
        ]);

        $resolver->setAllowedTypes('attributes', ['null', 'array']);
        $resolver->setAllowedTypes('value', 'string');
        $resolver->setAllowedTypes('value_prefix', 'bool');
        $resolver->setAllowedTypes('render_actions_to_id', ['null', 'string']);
        $resolver->setAllowedTypes('render_if', ['null', 'Closure']);

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    /**
     * @throws Exception
     */
    public function setActions(array $actions): static
    {
        if ($actions !== []) {
            foreach ($actions as $action) {
                $this->addAction($action);
            }
        } else {
            throw new RuntimeException('MultiselectColumn::setActions(): The actions array should contain at least one element.');
        }

        return $this;
    }

    /**
     * Add action.
     */
    public function addAction(array $action): static
    {
        $newAction = new MultiselectAction($this->datatableName);
        $this->actions[] = $newAction->set($action);

        return $this;
    }

    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    /**
     * @throws Exception
     */
    public function setAttributes(?array $attributes): static
    {
        $value = 'sg-datatables-' . $this->datatableName . '-multiselect-checkbox';

        if (\is_array($attributes)) {
            if (\array_key_exists('type', $attributes)) {
                throw new RuntimeException('MultiselectColumn::setAttributes(): The type attribute is not supported.');
            }

            if (\array_key_exists('value', $attributes)) {
                throw new RuntimeException('MultiselectColumn::setAttributes(): The value attribute is not supported.');
            }

            $attributes['name'] = \array_key_exists('name', $attributes) ? $attributes['name'] . '[]' : $value . '[]';
            $attributes['class'] = \array_key_exists('class', $attributes) ? $value . ' ' . $attributes['class'] : $value;
        } else {
            $attributes['name'] = $value . '[]';
            $attributes['class'] = $value;
        }

        $this->attributes = $attributes;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value prefix.
     */
    public function isValuePrefix(): bool
    {
        return $this->valuePrefix;
    }

    /**
     * Set value prefix.
     */
    public function setValuePrefix(bool $valuePrefix): static
    {
        $this->valuePrefix = $valuePrefix;

        return $this;
    }

    public function getRenderActionsToId(): ?string
    {
        return $this->renderActionsToId;
    }

    public function setRenderActionsToId(?string $renderActionsToId): static
    {
        $this->renderActionsToId = $renderActionsToId;

        return $this;
    }
}
