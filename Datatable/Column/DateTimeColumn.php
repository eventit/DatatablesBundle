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
use Sg\DatatablesBundle\Datatable\Editable\EditableInterface;
use Sg\DatatablesBundle\Datatable\Filter\TextFilter;
use Sg\DatatablesBundle\Datatable\Helper;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeColumn extends AbstractColumn
{
    use EditableTrait;

    use FilterableTrait;

    /**
     * Moment.js date format.
     * Default: 'lll'.
     *
     * @see http://momentjs.com/
     */
    protected string $dateFormat = 'lll';

    /**
     * Use the time ago format.
     * Default: false.
     */
    protected bool $timeago = false;

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

            if (null !== $entries && (is_countable($entries) ? \count($entries) : 0) > 0) {
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
        return '@SgDatatables/render/datetime.html.twig';
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

        $resolver->setDefaults([
            'date_format' => 'lll',
            'timeago' => false,
            'filter' => [TextFilter::class, []],
            'editable' => null,
        ]);

        $resolver->setAllowedTypes('date_format', 'string');
        $resolver->setAllowedTypes('timeago', 'bool');
        $resolver->setAllowedTypes('filter', 'array');
        $resolver->setAllowedTypes('editable', ['null', 'array']);

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    /**
     * Get date format.
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * Set date format.
     *
     * @throws Exception
     */
    public function setDateFormat(string $dateFormat): static
    {
        if ($dateFormat === '') {
            throw new RuntimeException('DateTimeColumn::setDateFormat(): A non-empty string is expected.');
        }

        $this->dateFormat = $dateFormat;

        return $this;
    }

    public function isTimeago(): bool
    {
        return $this->timeago;
    }

    public function setTimeago(bool $timeago): static
    {
        $this->timeago = $timeago;

        return $this;
    }

    // -------------------------------------------------
    // Helper
    // -------------------------------------------------

    /**
     * Render template.
     */
    private function renderTemplate(mixed $data, ?string $pk = null, ?string $path = null): string
    {
        $renderVars = [
            'data' => $data,
            'default_content' => $this->getDefaultContent(),
            'date_format' => $this->dateFormat,
            'timeago' => $this->timeago,
            'datatable_name' => $this->getDatatableName(),
            'row_id' => Helper::generateUniqueID(),
        ];

        // editable vars
        if (null !== $pk) {
            $renderVars = array_merge($renderVars, [
                'column_class_editable_selector' => $this->getColumnClassEditableSelector(),
                'pk' => $pk,
                'path' => $path,
            ]);
        }

        return $this->twig->render(
            $this->getCellContentTemplate(),
            $renderVars
        );
    }
}
