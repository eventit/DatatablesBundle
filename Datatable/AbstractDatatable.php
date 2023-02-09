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

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;
use Sg\DatatablesBundle\Datatable\Column\ColumnBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface as LegacyTranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as Twig_Environment;

abstract class AbstractDatatable implements DatatableInterface
{
    protected TranslatorInterface $translator;

    protected ColumnBuilder $columnBuilder;

    protected Ajax $ajax;

    protected Options $options;

    protected Features $features;

    protected Callbacks $callbacks;

    protected Events $events;

    protected Extensions $extensions;

    protected Language $language;

    protected int $uniqueId;

    protected PropertyAccessor $accessor;

    protected static array $uniqueCounter = [];

    /**
     * @throws LogicException
     */
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected TokenStorageInterface $securityToken,
        $translator,
        protected RouterInterface $router,
        protected EntityManagerInterface $em,
        protected Twig_Environment $twig,
        ?Extensions $registry = null
    ) {
        $this->validateName();

        if (isset(self::$uniqueCounter[$this->getName()])) {
            $this->uniqueId = ++self::$uniqueCounter[$this->getName()];
        } else {
            $this->uniqueId = self::$uniqueCounter[$this->getName()] = 1;
        }

        if (! ($translator instanceof LegacyTranslatorInterface) && ! ($translator instanceof TranslatorInterface)) {
            throw new InvalidArgumentException(sprintf('The $translator argument of %s must be an instance of %s or %s, a %s was given.', static::class, LegacyTranslatorInterface::class, TranslatorInterface::class, $translator::class));
        }
        $this->translator = $translator;

        $metadata = $em->getClassMetadata($this->getEntity());
        $this->columnBuilder = new ColumnBuilder($metadata, $twig, $router, $this->getName(), $em);

        $this->ajax = new Ajax();
        $this->options = new Options();
        $this->features = new Features();
        $this->callbacks = new Callbacks();
        $this->events = new Events();
        $this->extensions = $registry instanceof Extensions ? $registry : new Extensions();
        $this->language = new Language();

        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    public function getLineFormatter(): ?Closure
    {
        return null;
    }

    public function getColumnBuilder(): ColumnBuilder
    {
        return $this->columnBuilder;
    }

    public function getAjax(): Ajax
    {
        return $this->ajax;
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    public function getFeatures(): Features
    {
        return $this->features;
    }

    public function getCallbacks(): Callbacks
    {
        return $this->callbacks;
    }

    public function getEvents(): Events
    {
        return $this->events;
    }

    public function getExtensions(): Extensions
    {
        return $this->extensions;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<int|string, mixed>
     */
    public function getOptionsArrayFromEntities(array $entities, string $keyFrom = 'id', string $valueFrom = 'name'): array
    {
        $options = [];

        foreach ($entities as $entity) {
            if ($this->accessor->isReadable($entity, $keyFrom) && $this->accessor->isReadable($entity, $valueFrom)) {
                $options[$this->accessor->getValue($entity, $keyFrom)] = $this->accessor->getValue($entity, $valueFrom);
            }
        }

        return $options;
    }

    public function getUniqueId(): int
    {
        return $this->uniqueId;
    }

    public function getUniqueName(): string
    {
        return $this->getName() . ($this->getUniqueId() > 1 ? '-' . $this->getUniqueId() : '');
    }

    /**
     * Checks the name only contains letters, numbers, underscores or dashes.
     *
     * @throws LogicException
     */
    protected function validateName(): void
    {
        $name = $this->getName();
        if (1 !== preg_match(self::NAME_REGEX, $name)) {
            throw new LogicException(sprintf('AbstractDatatable::validateName(): "%s" is invalid Datatable Name. Name can only contain letters, numbers, underscore and dashes.', $name));
        }
    }
}
