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

class Button extends AbstractExtension
{
    protected ?array $action = null;

    protected ?array $available = null;

    protected ?string $className = null;

    protected ?array $destroy = null;

    protected ?string $extend = null;

    protected ?array $init = null;

    protected ?string $key = null;

    protected ?string $namespace = null;

    protected ?string $text = null;

    protected ?string $titleAttr = null;

    protected ?array $buttonOptions = null;

    public function __construct()
    {
        parent::__construct('button');
    }

    public function configureOptions(OptionsResolver $resolver): static
    {
        $resolver->setDefaults([
            'action' => null,
            'available' => null,
            'class_name' => null,
            'destroy' => null,
            'enabled' => null,
            'extend' => null,
            'init' => null,
            'key' => null,
            'name' => null,
            'namespace' => null,
            'text' => null,
            'title_attr' => null,
            'button_options' => null,
        ]);

        $resolver->setAllowedTypes('action', ['array', 'null']);
        $resolver->setAllowedTypes('available', ['array', 'null']);
        $resolver->setAllowedTypes('class_name', ['string', 'null']);
        $resolver->setAllowedTypes('destroy', ['array', 'null']);
        $resolver->setAllowedTypes('enabled', ['bool', 'null']);
        $resolver->setAllowedTypes('extend', ['string', 'null']);
        $resolver->setAllowedTypes('init', ['array', 'null']);
        $resolver->setAllowedTypes('key', ['string', 'null']);
        $resolver->setAllowedTypes('name', ['string', 'null']);
        $resolver->setAllowedTypes('namespace', ['string', 'null']);
        $resolver->setAllowedTypes('text', ['string', 'null']);
        $resolver->setAllowedTypes('title_attr', ['string', 'null']);
        $resolver->setAllowedTypes('button_options', ['array', 'null']);

        return $this;
    }

    public function getAction(): ?array
    {
        return $this->action;
    }

    public function setAction(?array $action): static
    {
        if (\is_array($action)) {
            $this->validateArrayForTemplateAndOther($action);
        }

        $this->action = $action;

        return $this;
    }

    public function getAvailable(): ?array
    {
        return $this->available;
    }

    public function setAvailable(?array $available): static
    {
        if (\is_array($available)) {
            $this->validateArrayForTemplateAndOther($available);
        }

        $this->available = $available;

        return $this;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function setClassName(?string $className): static
    {
        $this->className = $className;

        return $this;
    }

    public function getDestroy(): ?array
    {
        return $this->destroy;
    }

    public function setDestroy(?array $destroy): static
    {
        if (\is_array($destroy)) {
            $this->validateArrayForTemplateAndOther($destroy);
        }

        $this->destroy = $destroy;

        return $this;
    }

    public function getExtend(): ?string
    {
        return $this->extend;
    }

    public function setExtend(?string $extend): static
    {
        $this->extend = $extend;

        return $this;
    }

    public function getInit(): ?array
    {
        return $this->init;
    }

    public function setInit(?array $init): static
    {
        if (\is_array($init)) {
            $this->validateArrayForTemplateAndOther($init);
        }

        $this->init = $init;

        return $this;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function setNamespace(?string $namespace): static
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getTitleAttr(): ?string
    {
        return $this->titleAttr;
    }

    public function setTitleAttr(?string $titleAttr): static
    {
        $this->titleAttr = $titleAttr;

        return $this;
    }

    public function getButtonOptions(): ?array
    {
        return $this->buttonOptions;
    }

    public function setButtonOptions(?array $buttonOptions): static
    {
        $this->buttonOptions = $buttonOptions;

        return $this;
    }

    public function getJavaScriptConfiguration(array $config = []): array
    {
        if (null !== $this->getAction()) {
            $config['action'] = $this->getAction();
        }

        if (null !== $this->getAvailable()) {
            $config['available'] = $this->getAvailable();
        }

        if (null !== $this->getButtonOptions()) {
            $config['buttonOptions'] = $this->getButtonOptions();
        }

        if (null !== $this->getClassName()) {
            $config['className'] = $this->getClassName();
        }

        if (null !== $this->getDestroy()) {
            $config['destroy'] = $this->getDestroy();
        }

        if (null !== $this->getExtend()) {
            $config['extend'] = $this->getExtend();
        }

        if (null !== $this->getInit()) {
            $config['init'] = $this->getInit();
        }

        if (null !== $this->getKey()) {
            $config['key'] = $this->getKey();
        }

        if (null !== $this->getNamespace()) {
            $config['nameSpace'] = $this->getNamespace();
        }

        if (null !== $this->getText()) {
            $config['text'] = $this->getText();
        }

        if (null !== $this->getTitleAttr()) {
            $config['titleAttr'] = $this->getTitleAttr();
        }

        return parent::getJavaScriptConfiguration($config);
    }
}
