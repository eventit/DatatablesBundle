<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * <https://github.com/eventit/DatatablesBundle>
 */

namespace Sg\DatatablesBundle\Datatable\Extension;

use Symfony\Component\OptionsResolver\OptionsResolver;
use UnexpectedValueException;

class Responsive extends AbstractExtension
{
    /** @var array|bool */
    protected $details;

    public function __construct()
    {
        parent::__construct('responsive');
    }

    /**
     * @return $this
     */
    public function configureOptions(OptionsResolver $resolver): ExtensionInterface
    {
        $resolver->setRequired('details');
        $resolver->setAllowedTypes('details', ['array', 'bool']);

        return $this;
    }

    /**
     * @return array|bool
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param array|bool $details
     *
     * @throws Exception
     *
     * @return $this
     */
    public function setDetails($details): self
    {
        if (\is_array($details)) {
            foreach ($details as $key => $value) {
                if (false === \in_array($key, ['type', 'target', 'renderer', 'display'], true)) {
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
