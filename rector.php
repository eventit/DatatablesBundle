<?php

declare(strict_types=1);

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * (c) stwe <https://github.com/stwe/DatatablesBundle>
 * (c) event it AG <https://github.com/eventit/DatatablesBundle>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitLevelSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/Controller',
        __DIR__ . '/Datatable',
        __DIR__ . '/DependencyInjection',
        __DIR__ . '/Model',
        __DIR__ . '/Resources',
        __DIR__ . '/Response',
        __DIR__ . '/Tests',
        __DIR__ . '/Twig',
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_80,
        SetList::CODE_QUALITY,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        PHPUnitLevelSetList::UP_TO_PHPUNIT_91,
    ]);

    // use imports
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();
};
