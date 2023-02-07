<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Response\Elastica;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

class ElasticaEntries implements IteratorAggregate
{
    /** @var int */
    protected $count = 0;

    /** @var array */
    protected $entries = [];

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getEntries(): array
    {
        return $this->entries;
    }

    public function setEntries(array $entries): self
    {
        $this->entries = $entries;

        return $this;
    }

    /**
     * @return ArrayIterator|Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->getEntries());
    }
}
