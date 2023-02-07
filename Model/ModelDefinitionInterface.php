<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Model;

interface ModelDefinitionInterface
{
    /**
     * @deprecated
     */
    public function hasSearch(): bool;

    /**
     * @deprecated
     */
    public function setSearch(array $search): self;

    /**
     * @deprecated
     */
    public function getSearch(): array;
}
