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
    public function setSearch(array $search): static;

    /**
     * @deprecated
     */
    public function getSearch(): array;
}
