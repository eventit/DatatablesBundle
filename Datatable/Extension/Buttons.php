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

use RuntimeException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Buttons extends AbstractExtension
{
    protected ?array $showButtons = null;

    /** @var Button[]|null */
    protected ?array $createButtons = null;

    public function __construct()
    {
        parent::__construct('buttons');
    }

    /**
     * @return $this
     */
    public function configureOptions(OptionsResolver $resolver): static
    {
        $resolver->setDefaults([
            'show_buttons' => null,
            'create_buttons' => null,
        ]);

        $resolver->setAllowedTypes('show_buttons', ['null', 'array']);
        $resolver->setAllowedTypes('create_buttons', ['null', 'array']);

        return $this;
    }

    public function getShowButtons(): ?array
    {
        if (\is_array($this->showButtons)) {
            return $this->optionToJson($this->showButtons);
        }

        return $this->showButtons;
    }

    public function setShowButtons(?array $showButtons): static
    {
        $this->showButtons = $showButtons;

        return $this;
    }

    /**
     * @return Button[]|null
     */
    public function getCreateButtons(): ?array
    {
        return $this->createButtons;
    }

    /**
     * @return array<int, array[]>
     */
    public function getCreateButtonsForJavaScriptConfiguration(): array
    {
        $createButtons = [];
        if (\is_array($this->getCreateButtons())) {
            foreach ($this->getCreateButtons() as $button) {
                /* @var Button $button */
                $createButtons[] = $button->getJavaScriptConfiguration();
            }
        }

        return $createButtons;
    }

    public function setCreateButtons(?array $createButtons): static
    {
        if (\is_array($createButtons)) {
            if ($createButtons !== []) {
                foreach ($createButtons as $button) {
                    $newButton = new Button();
                    $this->createButtons[] = $newButton->set($button);
                }
            } else {
                throw new RuntimeException('Buttons::setCreateButtons(): The createButtons array should contain at least one element.');
            }
        } else {
            $this->createButtons = $createButtons;
        }

        return $this;
    }

    public function getJavaScriptConfiguration(array $config = []): array
    {
        if (null !== $this->getCreateButtons()) {
            $config['createButtons'] = $this->getCreateButtonsForJavaScriptConfiguration();
        }

        if (null !== $this->getShowButtons()) {
            $config['showButtons'] = $this->getShowButtons();
        }

        return parent::getJavaScriptConfiguration($config);
    }
}
