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

namespace Sg\DatatablesBundle\Datatable\Extension;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface ExtensionInterface
{
    public function setName(string $name): static;

    public function getName(): string;

    public function isEnabled(): bool;

    public function setEnabled(bool $enabled): static;

    public function setOptions(array $options): static;

    public function configureOptions(OptionsResolver $resolver): static;

    public function getJavaScriptConfiguration(array $config = []): array;
}
