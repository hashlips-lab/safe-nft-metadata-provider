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

namespace App\Command;

use App\Service\CollectionManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
#[AsCommand(
    name: self::NAME,
    description: self::DESCRIPTION,
)]
class ExportAssetsCommand extends Command
{
    /**
     * @var string
     */
    final public const NAME = 'nft:export-assets';

    /**
     * @var string
     */
    final public const DESCRIPTION = 'Exports a new assets folder with all the files shuffling them using the current mapping (if any)';

    public function __construct(
        private readonly CollectionManager $collectionManager,
        string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        if (! $symfonyStyle->confirm(
            "I'm about to delete the exported assets directory and its content. Are you sure?",
            false,
        )) {
            $symfonyStyle->warning('Aborting...');

            return Command::SUCCESS;
        }

        $symfonyStyle->info('Deleting old data...');
        $this->collectionManager->clearExportedAssets();

        $symfonyStyle->info('Exporting new data...');
        $symfonyStyle->progressStart($this->collectionManager->getMaxTokenId());

        foreach (range(1, $this->collectionManager->getMaxTokenId()) as $tokenId) {
            $this->collectionManager->storeExportedAsset($tokenId);
            gc_collect_cycles();

            $symfonyStyle->progressAdvance();
        }

        $symfonyStyle->progressFinish();

        $symfonyStyle->success('Assets exported successfully!');

        return Command::SUCCESS;
    }
}
