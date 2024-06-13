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
use Sg\DatatablesBundle\Datatable\Factory;

trait EditableTrait
{
    /**
     * An EditableInterface instance.
     * Default: null.
     */
    protected ?EditableInterface $editable = null;

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    public function getEditable(): ?EditableInterface
    {
        return $this->editable;
    }

    /**
     * @throws Exception
     *
     * @return $this
     */
    public function setEditable(?array $editableClassAndOptions): static
    {
        if (\is_array($editableClassAndOptions)) {
            if (2 !== \count($editableClassAndOptions)) {
                throw new RuntimeException('EditableTrait::setEditable(): Two arguments expected.');
            }

            if (! isset($editableClassAndOptions[0]) || (! \is_string($editableClassAndOptions[0]) && ! $editableClassAndOptions[0] instanceof EditableInterface)) {
                throw new RuntimeException('EditableTrait::setEditable(): Set a Editable class.');
            }

            if (! isset($editableClassAndOptions[1]) || ! \is_array($editableClassAndOptions[1])) {
                throw new RuntimeException('EditableTrait::setEditable(): Set an options array.');
            }

            $newEditable = Factory::create($editableClassAndOptions[0], EditableInterface::class);
            $this->editable = $newEditable->set($editableClassAndOptions[1]);
        } else {
            $this->editable = $editableClassAndOptions;
        }

        return $this;
    }

    public function isEditableContentRequired(array $row): bool
    {
        return $this->editable instanceof EditableInterface && $this->editable->callEditableIfClosure($row);
    }

    // -------------------------------------------------
    // Helper
    // -------------------------------------------------

    /**
     * Get class selector name for editable.
     */
    protected function getColumnClassEditableSelector(): string
    {
        return 'sg-datatables-' . $this->getDatatableName() . '-editable-column-' . $this->index;
    }
}
