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
use Doctrine\Common\Annotations\Annotation\Attributes;
use Exception;
use Sg\DatatablesBundle\Datatable\Filter\TextFilter;
use Sg\DatatablesBundle\Datatable\Helper;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttributeColumn extends AbstractColumn
{
    // The AttributeColumn is filterable.
    use FilterableTrait;

    /**
     * The Attributes container.
     * A required option.
     */
    protected array|Closure|null $attributes = null;

    // -------------------------------------------------
    // ColumnInterface
    // -------------------------------------------------

    public function renderSingleField(array &$row): static
    {
        $renderAttributes = \call_user_func($this->attributes, $row);

        $path = Helper::getDataPropertyPath($this->data);

        $content = $this->twig->render(
            $this->getCellContentTemplate(),
            [
                'attributes' => $renderAttributes,
                'data' => $this->accessor->getValue($row, $path),
            ]
        );

        $this->accessor->setValue($row, $path, $content);

        return $this;
    }

    public function renderToMany(array &$row): static
    {
        $value = null;
        $path = Helper::getDataPropertyPath($this->data, $value);

        if ($this->accessor->isReadable($row, $path) && $this->isEditableContentRequired($row)) {
            // e.g. comments[ ].createdBy.username
            //     => $path = [comments]
            //     => $value = [createdBy][username]
            $entries = $this->accessor->getValue($row, $path);
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
        }

        return $this;
    }

    public function getCellContentTemplate(): string
    {
        return '@SgDatatables/render/attributeColumn.html.twig';
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
            'attributes' => null,
        ]);

        $resolver->setAllowedTypes('filter', 'array');
        $resolver->setAllowedTypes('attributes', ['null', 'array', 'Closure']);

        return $this;
    }

    public function getAttributes(): array|Closure|null
    {
        return $this->attributes;
    }

    /**
     * @throws Exception
     */
    public function setAttributes(array|Closure|null $attributes): static
    {
        $this->attributes = $attributes;

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
