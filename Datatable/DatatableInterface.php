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

namespace Sg\DatatablesBundle\Datatable;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Sg\DatatablesBundle\Datatable\Column\ColumnBuilder;

/**
 * Interface DatatableInterface.
 */
interface DatatableInterface
{
    public const NAME_REGEX = '/^[a-zA-Z0-9\-\_]+$/';

    /**
     * Builds the datatable.
     */
    public function buildDatatable(array $options = []);

    /**
     * Returns a callable that modify the data row.
     */
    public function getLineFormatter(): ?Closure;

    public function getColumnBuilder(): ColumnBuilder;

    /**
     * Get Ajax instance.
     */
    public function getAjax(): Ajax;

    /**
     * Get Options instance.
     */
    public function getOptions(): Options;

    /**
     * Get Features instance.
     */
    public function getFeatures(): Features;

    /**
     * Get Callbacks instance.
     */
    public function getCallbacks(): Callbacks;

    /**
     * Get Events instance.
     */
    public function getEvents(): Events;

    /**
     * Get Extensions instance.
     */
    public function getExtensions(): Extensions;

    /**
     * Get Language instance.
     */
    public function getLanguage(): Language;

    /**
     * Get the EntityManager.
     */
    public function getEntityManager(): EntityManagerInterface;

    /**
     * Help function to create an option array for filtering.
     */
    public function getOptionsArrayFromEntities(array $entities, string $keyFrom = 'id', string $valueFrom = 'name'): array;

    /**
     * Returns the name of the entity.
     */
    public function getEntity(): string;

    /**
     * Returns the name of this datatable view.
     */
    public function getName(): string;

    /**
     * Returns the unique id of this datatable view.
     */
    public function getUniqueId(): int;

    /**
     * Returns the unique name of this datatable view.
     */
    public function getUniqueName(): string;
}
