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

use Exception;
use JsonException;
use RuntimeException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

trait OptionsTrait
{
    /**
     * Options container.
     */
    protected array $options = [];

    /**
     * The PropertyAccessor.
     */
    protected ?PropertyAccessor $accessor = null;

    // -------------------------------------------------
    // Public
    // -------------------------------------------------

    /**
     * Init optionsTrait.
     */
    public function initOptions(bool $resolve = false): static
    {
        $this->options = [];

        // @noinspection PhpUndefinedMethodInspection
        $this->accessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableMagicCall()
            ->getPropertyAccessor()
        ;

        if ($resolve) {
            $this->set($this->options);
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function set(array $options): static
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
        $this->callingSettersWithOptions($this->options);

        return $this;
    }

    /**
     * Option to JSON.
     *
     * @throws JsonException
     */
    protected function optionToJson(mixed $value): mixed
    {
        if (\is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return $value;
    }

    /**
     * Validates an array whether the "template" and "vars" options are set.
     *
     * @throws RuntimeException
     */
    protected function validateArrayForTemplateAndOther(array $array, array $other = ['template', 'vars']): bool
    {
        if (! \array_key_exists('template', $array)) {
            throw new RuntimeException('OptionsTrait::validateArrayForTemplateAndOther(): The "template" option is required.');
        }

        foreach (array_keys($array) as $key) {
            if (! \in_array($key, $other, true)) {
                throw new RuntimeException("OptionsTrait::validateArrayForTemplateAndOther(): {$key} is not an valid option.");
            }
        }

        return true;
    }

    // -------------------------------------------------
    // Helper
    // -------------------------------------------------

    /**
     * Calls the setters.
     */
    private function callingSettersWithOptions(array $options): static
    {
        foreach ($options as $setter => $value) {
            $this->accessor->setValue($this, $setter, $value);
        }

        return $this;
    }
}
