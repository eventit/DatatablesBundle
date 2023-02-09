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

use RuntimeException;
use Sg\DatatablesBundle\Datatable\Filter\TextFilter;
use Sg\DatatablesBundle\Datatable\Helper;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageColumn extends AbstractColumn
{
    use FilterableTrait;

    /**
     * The imagine filter used to display image preview.
     * Required option.
     *
     * @see https://github.com/liip/LiipImagineBundle#create-thumbnails
     */
    protected string $imagineFilter = '';

    /**
     * The imagine filter used to display the enlarged image's size;
     * if not set or null, no filter will be applied;
     * $enlarged need to be set to true.
     * Default: null.
     *
     * @see https://github.com/liip/LiipImagineBundle#create-thumbnails
     */
    protected ?string $imagineFilterEnlarged = null;

    /**
     * The relative path.
     * Required option.
     */
    protected string $relativePath = '';

    /**
     * The placeholder url.
     * e.g. "http://placehold.it"
     * Default: null.
     */
    protected ?string $holderUrl = null;

    /**
     * The default width of the placeholder.
     * Default: '50'.
     */
    protected string $holderWidth = '50';

    /**
     * The default height of the placeholder.
     * Default: '50'.
     */
    protected string $holderHeight = '50';

    /**
     * Enlarge thumbnail.
     * Default: false.
     */
    protected bool $enlarge = false;

    // -------------------------------------------------
    // ColumnInterface
    // -------------------------------------------------

    public function renderSingleField(array &$row): static
    {
        $path = Helper::getDataPropertyPath($this->data);

        if ($this->accessor->isReadable($row, $path)) {
            $content = $this->renderImageTemplate($this->accessor->getValue($row, $path), '-image');

            $this->accessor->setValue($row, $path, $content);
        }

        return $this;
    }

    public function renderToMany(array &$row): static
    {
        // e.g. images[ ].fileName
        //     => $path = [images]
        //     => $value = [fileName]
        $value = null;
        $path = Helper::getDataPropertyPath($this->data, $value);

        if ($this->accessor->isReadable($row, $path)) {
            $images = $this->accessor->getValue($row, $path);

            if ((is_countable($images) ? \count($images) : 0) > 0) {
                foreach ($images as $key => $image) {
                    $currentPath = $path . '[' . $key . ']' . $value;
                    $content = $this->renderImageTemplate($this->accessor->getValue($row, $currentPath), '-gallery-image');
                    $this->accessor->setValue($row, $currentPath, $content);
                }
            } else {
                // create an entry for the placeholder image
                $currentPath = $path . '[0]' . $value;
                $content = $this->renderImageTemplate(null, '-gallery-image');
                $this->accessor->setValue($row, $currentPath, $content);
            }
        }

        return $this;
    }

    public function getCellContentTemplate(): string
    {
        return '@SgDatatables/render/thumb.html.twig';
    }

    // -------------------------------------------------
    // Options
    // -------------------------------------------------

    public function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(['imagine_filter']);
        $resolver->setRequired(['relative_path']);

        $resolver->setDefaults([
            'filter' => [TextFilter::class, []],
            'imagine_filter_enlarged' => null,
            'holder_url' => null,
            'holder_width' => '50',
            'holder_height' => '50',
            'enlarge' => false,
        ]);

        $resolver->setAllowedTypes('filter', 'array');
        $resolver->setAllowedTypes('imagine_filter', 'string');
        $resolver->setAllowedTypes('imagine_filter_enlarged', ['null', 'string']);
        $resolver->setAllowedTypes('relative_path', 'string');
        $resolver->setAllowedTypes('holder_url', ['null', 'string']);
        $resolver->setAllowedTypes('holder_width', 'string');
        $resolver->setAllowedTypes('holder_height', 'string');
        $resolver->setAllowedTypes('enlarge', 'bool');

        $resolver->setNormalizer('enlarge', function (Options $options, $value): bool {
            if (null !== $options['imagine_filter_enlarged']) {
                return $value;
            }
            if (true !== $value) {
                return $value;
            }

            throw new RuntimeException('ImageColumn::configureOptions(): For the enlarge option, imagine_filter_enlarged should not be null.');
        });

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    public function getImagineFilter(): string
    {
        return $this->imagineFilter;
    }

    public function setImagineFilter(string $imagineFilter): static
    {
        $this->imagineFilter = $imagineFilter;

        return $this;
    }

    public function getImagineFilterEnlarged(): ?string
    {
        return $this->imagineFilterEnlarged;
    }

    public function setImagineFilterEnlarged(?string $imagineFilterEnlarged): static
    {
        $this->imagineFilterEnlarged = $imagineFilterEnlarged;

        return $this;
    }

    public function getRelativePath(): string
    {
        return $this->relativePath;
    }

    public function setRelativePath(string $relativePath): static
    {
        $this->relativePath = $relativePath;

        return $this;
    }

    public function getHolderUrl(): ?string
    {
        return $this->holderUrl;
    }

    public function setHolderUrl(?string $holderUrl): static
    {
        $this->holderUrl = $holderUrl;

        return $this;
    }

    public function getHolderWidth(): string
    {
        return $this->holderWidth;
    }

    public function setHolderWidth(string $holderWidth): static
    {
        $this->holderWidth = $holderWidth;

        return $this;
    }

    public function getHolderHeight(): string
    {
        return $this->holderHeight;
    }

    public function setHolderHeight(string $holderHeight): static
    {
        $this->holderHeight = $holderHeight;

        return $this;
    }

    public function isEnlarge(): bool
    {
        return $this->enlarge;
    }

    public function setEnlarge(bool $enlarge): static
    {
        $this->enlarge = $enlarge;

        return $this;
    }

    // -------------------------------------------------
    // Helper
    // -------------------------------------------------
    /**
     * Render image template.
     */
    private function renderImageTemplate(string $data, string $classSuffix): string
    {
        return $this->twig->render(
            $this->getCellContentTemplate(),
            [
                'data' => $data,
                'image' => $this,
                'image_class' => 'sg-datatables-' . $this->getDatatableName() . $classSuffix,
            ]
        );
    }
}
