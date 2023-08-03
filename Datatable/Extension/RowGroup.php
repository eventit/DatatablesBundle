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
use UnexpectedValueException;

class RowGroup extends AbstractExtension
{
    protected ?string $dataSrc = null;

    protected ?array $startRender = null;

    protected ?array $endRender = null;

    protected ?string $className = null;

    protected ?string $emptyDataGroup = null;

    protected ?string $endClassName = null;

    protected ?string $startClassName = null;

    public function __construct()
    {
        parent::__construct('rowGroup');
    }

    /**
     * @return $this
     */
    public function configureOptions(OptionsResolver $resolver): static
    {
        $resolver->setRequired('data_src');
        $resolver->setDefined('start_render');
        $resolver->setDefined('end_render');
        $resolver->setDefined('enable');
        $resolver->setDefined('class_name');
        $resolver->setDefined('empty_data_group');
        $resolver->setDefined('end_class_name');
        $resolver->setDefined('start_class_name');

        $resolver->setDefaults([
            'enable' => true,
        ]);

        $resolver->setAllowedTypes('data_src', ['string']);
        $resolver->setAllowedTypes('start_render', ['array']);
        $resolver->setAllowedTypes('end_render', ['array']);
        $resolver->setAllowedTypes('enable', ['bool']);
        $resolver->setAllowedTypes('class_name', ['string']);
        $resolver->setAllowedTypes('empty_data_group', ['string']);
        $resolver->setAllowedTypes('end_class_name', ['string']);
        $resolver->setAllowedTypes('start_class_name', ['string']);

        return $this;
    }

    public function getDataSrc(): ?string
    {
        return $this->dataSrc;
    }

    /**
     * @throws UnexpectedValueException
     */
    public function setDataSrc(string $dataSrc): static
    {
        if ($dataSrc === '') {
            throw new UnexpectedValueException(
                'RowGroup::setDataSrc(): the column name is empty.'
            );
        }

        $this->dataSrc = $dataSrc;

        return $this;
    }

    public function getStartRender(): ?array
    {
        return $this->startRender;
    }

    public function setStartRender(array $startRender): static
    {
        if (! \array_key_exists('template', $startRender)) {
            throw new UnexpectedValueException(
                'RowGroup::setStartRender(): The "template" option is required.'
            );
        }

        foreach (array_keys($startRender) as $key) {
            if (! \in_array($key, ['template', 'vars'], true)) {
                throw new UnexpectedValueException(
                    'RowGroup::setStartRender(): ' . $key . ' is not a valid option.'
                );
            }
        }

        $this->startRender = $startRender;

        return $this;
    }

    public function getEndRender(): ?array
    {
        return $this->endRender;
    }

    public function setEndRender(array $endRender): static
    {
        if (! \array_key_exists('template', $endRender)) {
            throw new UnexpectedValueException(
                'RowGroup::setEndRender(): The "template" option is required.'
            );
        }

        foreach (array_keys($endRender) as $key) {
            if (! \in_array($key, ['template', 'vars'], true)) {
                throw new UnexpectedValueException(
                    'RowGroup::setEndRender(): ' . $key . ' is not a valid option.'
                );
            }
        }

        $this->endRender = $endRender;

        return $this;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function setClassName(string $className): static
    {
        if ($className === '') {
            throw new UnexpectedValueException(
                'RowGroup::setClassName(): the class name is empty.'
            );
        }

        $this->className = $className;

        return $this;
    }

    public function getEmptyDataGroup(): ?string
    {
        return $this->emptyDataGroup;
    }

    public function setEmptyDataGroup(string $emptyDataGroup): static
    {
        if ($emptyDataGroup === '') {
            throw new UnexpectedValueException(
                'RowGroup::setEmptyDataGroup(): the empty data group text is empty.'
            );
        }

        $this->emptyDataGroup = $emptyDataGroup;

        return $this;
    }

    public function getEndClassName(): ?string
    {
        return $this->endClassName;
    }

    public function setEndClassName(string $endClassName): static
    {
        if ($endClassName === '') {
            throw new UnexpectedValueException(
                'RowGroup::setEndClassName(): the end class name is empty.'
            );
        }

        $this->endClassName = $endClassName;

        return $this;
    }

    public function getStartClassName(): ?string
    {
        return $this->startClassName;
    }

    public function setStartClassName(string $startClassName): static
    {
        if ($startClassName === '') {
            throw new UnexpectedValueException(
                'RowGroup::setStartClassName(): the start class name is empty.'
            );
        }

        $this->startClassName = $startClassName;

        return $this;
    }

    public function getJavaScriptConfiguration(array $config = []): array
    {
        if (null !== $this->getDataSrc()) {
            $config['dataSrc'] = $this->getDataSrc();
        }

        if (null !== $this->getEmptyDataGroup()) {
            $config['emptyDataGroup'] = $this->getEmptyDataGroup();
        }

        if (null !== $this->getEndClassName()) {
            $config['endClassName'] = $this->getEndClassName();
        }

        if (null !== $this->getEndRender()) {
            $config['endRender'] = $this->getEndRender();
        }

        if (null !== $this->getStartClassName()) {
            $config['startClassName'] = $this->getStartClassName();
        }

        if (null !== $this->getStartRender()) {
            $config['startRender'] = $this->getStartRender();
        }

        return parent::getJavaScriptConfiguration($config); // TODO: Change the autogenerated stub
    }
}
