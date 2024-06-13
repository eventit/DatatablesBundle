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

use Exception;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UnexpectedValueException;

class Responsive extends AbstractExtension
{
    protected array|bool $details;

    public function __construct()
    {
        parent::__construct('responsive');
    }

    /**
     * @return $this
     */
    public function configureOptions(OptionsResolver $resolver): static
    {
        $resolver->setRequired('details');
        $resolver->setAllowedTypes('details', ['array', 'bool']);

        return $this;
    }

    public function getDetails(): array|bool
    {
        return $this->details;
    }

    /**
     * @throws Exception
     *
     * @return $this
     */
    public function setDetails(array|bool $details): static
    {
        if (\is_array($details)) {
            foreach (array_keys($details) as $key) {
                if (! \in_array($key, ['type', 'target', 'renderer', 'display'], true)) {
                    throw new UnexpectedValueException(
                        "Responsive::setDetails(): {$key} is not an valid option."
                    );
                }
            }

            if (\is_array($details['renderer'])) {
                $this->validateArrayForTemplateAndOther($details['renderer']);
            }

            if (\is_array($details['display'])) {
                $this->validateArrayForTemplateAndOther($details['display']);
            }
        }

        $this->details = $details;

        return $this;
    }

    public function getJavaScriptConfiguration(array $config = []): array
    {
        if (null !== $this->getDetails()) {
            $config['details'] = $this->getDetails();
        }

        return parent::getJavaScriptConfiguration($config);
    }
}
