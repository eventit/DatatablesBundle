<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Datatable\Extension;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface ExtensionInterface
{
    /**
     * @return string
     */
    public function setName(string $name): self;

    public function getName(): string;

    public function isEnabled(): bool;

    public function setEnabled(bool $enabled): self;

    public function setOptions(array $options): self;

    public function configureOptions(OptionsResolver $resolver): self;

    public function getJavaScriptConfiguration(array $config = []): array;
}
