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
    name: 'nft:build-assets',
    description: 'Builds a new assets folder with all files shuffled using the current mapping',
)]
class BuildAssetsCommand extends Command
{
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
            "I'm about to delete the shuffled assets directory and its content. Are you sure?",
            false,
        )) {
            $symfonyStyle->warning('Aborting...');

            return Command::SUCCESS;
        }

        $this->collectionManager->clearShuffledAssets();

        $symfonyStyle->progressStart($this->collectionManager->getMaxTokenId());

        foreach (range(1, $this->collectionManager->getMaxTokenId()) as $tokenId) {
            $this->collectionManager->storeShuffledAsset($tokenId);
            gc_collect_cycles();

            $symfonyStyle->progressAdvance();
        }

        $symfonyStyle->progressFinish();

        $symfonyStyle->success('Assets built successfully!');

        return Command::SUCCESS;
    }
}
