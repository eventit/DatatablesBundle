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

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface as LegacyTranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class DatatableFactory
{
    protected TranslatorInterface|LegacyTranslatorInterface $translator;

    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected TokenStorageInterface $securityToken,
        $translator,
        protected RouterInterface $router,
        protected EntityManagerInterface $em,
        protected Environment $twig
    ) {
        if (! ($translator instanceof LegacyTranslatorInterface) && ! ($translator instanceof TranslatorInterface)) {
            throw new InvalidArgumentException(sprintf('The $translator argument of %s must be an instance of %s or %s, a %s was given.', static::class, LegacyTranslatorInterface::class, TranslatorInterface::class, $translator::class));
        }
        $this->translator = $translator;
    }

    // -------------------------------------------------
    // Create Datatable
    // -------------------------------------------------

    /**
     * @throws Exception
     */
    public function create(string $class): DatatableInterface
    {
        if (! class_exists($class)) {
            throw new RuntimeException("DatatableFactory::create(): {$class} does not exist");
        }

        if (\in_array(DatatableInterface::class, class_implements($class), true)) {
            return new $class(
                $this->authorizationChecker,
                $this->securityToken,
                $this->translator,
                $this->router,
                $this->em,
                $this->twig
            );
        }

        throw new RuntimeException("DatatableFactory::create(): The class {$class} should implement the DatatableInterface.");
    }
}
