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

namespace Sg\DatatablesBundle\Datatable\Filter;

use RuntimeException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UnexpectedValueException;

class Select2Filter extends SelectFilter
{
    /**
     * Select2 supports displaying a placeholder by default using the placeholder option.
     * Default: null.
     *
     * @var string|null
     */
    protected $placeholder;

    /**
     * Setting this option to true will enable an "x" icon that will reset the selection to the placeholder.
     * Default: null.
     */
    protected ?bool $allowClear = null;

    /**
     * Tagging support.
     * Default: null.
     */
    protected ?bool $tags = null;

    /**
     * i18n language code.
     * Default: null (get locale).
     */
    protected ?string $language = null;

    /**
     * URL to get the results from.
     * Default: null.
     */
    protected ?string $url = null;

    /**
     * Wait some milliseconds before triggering the request.
     * Default: 250.
     */
    protected int $delay = 250;

    /**
     * The AJAX cache.
     * Default: true.
     */
    protected bool $cache = true;

    // -------------------------------------------------
    // FilterInterface
    // -------------------------------------------------

    public function getTemplate(): string
    {
        return '@SgDatatables/filter/select2.html.twig';
    }

    // -------------------------------------------------
    // OptionsInterface
    // -------------------------------------------------

    public function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'placeholder' => null,
            'allow_clear' => null,
            'tags' => null,
            'language' => null,
            'url' => null,
            'delay' => 250,
            'cache' => true,
        ]);

        $resolver->setAllowedTypes('placeholder', ['string', 'null']);
        $resolver->setAllowedTypes('allow_clear', ['bool', 'null']);
        $resolver->setAllowedTypes('tags', ['bool', 'null']);
        $resolver->setAllowedTypes('language', ['string', 'null']);
        $resolver->setAllowedTypes('url', ['string', 'null']);
        $resolver->setAllowedTypes('delay', 'int');
        $resolver->setAllowedTypes('cache', 'bool');

        $resolver->setNormalizer('allow_clear', function (Options $options, $value): bool {
            if (null !== $options['placeholder']) {
                return $value;
            }
            if (true !== $value) {
                return $value;
            }
            throw new RuntimeException('Select2Filter::configureOptions(): The allow_clear option will only work if a placeholder is set.');
        });

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    /**
     * @param string|null $placeholder
     */
    public function setPlaceholder($placeholder): static
    {
        if (null !== $placeholder && ! \is_string($placeholder)) {
            throw new UnexpectedValueException('placeholder must be of type string or null');
        }

        $this->placeholder = $placeholder;

        return $this;
    }

    public function getAllowClear(): ?bool
    {
        return $this->allowClear;
    }

    public function setAllowClear(?bool $allowClear): static
    {
        $this->allowClear = $allowClear;

        return $this;
    }

    public function getTags(): ?bool
    {
        return $this->tags;
    }

    public function setTags(?bool $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function setDelay(int $delay): static
    {
        $this->delay = $delay;

        return $this;
    }

    public function isCache(): bool
    {
        return $this->cache;
    }

    public function setCache(bool $cache): static
    {
        $this->cache = $cache;

        return $this;
    }
}
