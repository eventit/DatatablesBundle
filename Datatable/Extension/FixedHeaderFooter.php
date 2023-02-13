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

class FixedHeaderFooter extends AbstractExtension
{
    protected bool $header;

    protected bool $footer;

    protected int $headerOffset;

    public function __construct()
    {
        parent::__construct('fixedHeader');
    }

    /**
     * @return $this
     */
    public function configureOptions(OptionsResolver $resolver): static
    {
        $resolver->setDefaults([
            'header' => false,
            'footer' => false,
            'headerOffset' => 0,
        ]);

        $resolver->setAllowedTypes('header', ['bool', 'false']);
        $resolver->setAllowedTypes('footer', ['bool', 'false']);
        $resolver->setAllowedTypes('headerOffset', 'int');

        return $this;
    }

    public function setHeader(bool $enabled): static
    {
        $this->header = $enabled;

        return $this;
    }

    public function getHeader(): bool
    {
        return $this->header;
    }

    public function getFooter(): bool
    {
        return $this->footer;
    }

    public function setFooter(bool $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    public function getHeaderOffset(): int
    {
        return $this->headerOffset;
    }

    public function setHeaderOffset(int $headerOffset): static
    {
        $this->headerOffset = $headerOffset;

        return $this;
    }

    public function getJavaScriptConfiguration(array $config = []): array
    {
        $config['header'] = $this->getHeader();
        $config['footer'] = $this->getFooter();
        $config['headerOffset'] = $this->getHeaderOffset();

        return parent::getJavaScriptConfiguration($config);
    }
}
