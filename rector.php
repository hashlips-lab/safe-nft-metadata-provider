<?php

/*
 * This file is part of the Safe NFT Metadata Provider package.
 *
 * (c) Marco Lipparini <developer@liarco.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    // Set target PHP version
    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_81);

    // Sources
    $parameters->set(Option::PATHS, [__DIR__.'/src', __DIR__.'/ecs.php', __DIR__.'/rector.php']);

    // Define what rule sets will be applied
    $containerConfigurator->import(SetList::PHP_81);
    $containerConfigurator->import(SetList::DEAD_CODE);
    $containerConfigurator->import(SetList::CODE_QUALITY);
    $containerConfigurator->import(SymfonySetList::SYMFONY_CODE_QUALITY);
    $containerConfigurator->import(DoctrineSetList::DOCTRINE_CODE_QUALITY);
    $containerConfigurator->import(SetList::CODING_STYLE);
    $containerConfigurator->import(SetList::NAMING);
    $containerConfigurator->import(SetList::ORDER);
    $containerConfigurator->import(SetList::TYPE_DECLARATION);

    // Custom configuration
    $parameters->set(Option::AUTO_IMPORT_NAMES, true);
};
