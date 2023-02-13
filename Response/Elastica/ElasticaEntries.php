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

namespace Sg\DatatablesBundle\Response\Elastica;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

class ElasticaEntries implements IteratorAggregate
{
    protected int $count = 0;

    protected array $entries = [];

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): static
    {
        $this->count = $count;

        return $this;
    }

    public function getEntries(): array
    {
        return $this->entries;
    }

    public function setEntries(array $entries): static
    {
        $this->entries = $entries;

        return $this;
    }

    public function getIterator(): ArrayIterator|Traversable
    {
        return new ArrayIterator($this->getEntries());
    }
}
