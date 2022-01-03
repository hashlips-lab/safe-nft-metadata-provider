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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
#[AsCommand(
    name: 'nft:shuffle-collection',
    description: 'Shuffles all or a range of tokens',
)]
class ShuffleCollectionCommand extends Command
{
    /**
     * @var string
     */
    private const START_FROM_OPTION = 'start-from';

    public function __construct(
        private readonly CollectionManager $collectionManager,
        string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption(self::START_FROM_OPTION, 's', InputOption::VALUE_OPTIONAL, 'The first token ID to be shuffled')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $startFromTokenOptionValue = $input->getOption(self::START_FROM_OPTION) ?? 1;
        $startTokenId = is_numeric($startFromTokenOptionValue) ? (int) $startFromTokenOptionValue : 1;
        $maxTokenId = $this->collectionManager->getMaxTokenId();

        if (! $symfonyStyle->confirm(
            'You are about to shuffle your collection starting from token #'.$startTokenId.' up to token #'.$maxTokenId.'. Are you sure?',
            false,
        )) {
            $symfonyStyle->warning('Aborting...');

            return Command::SUCCESS;
        }

        $tokenIds = range($startTokenId, $maxTokenId);
        shuffle($tokenIds);

        $symfonyStyle->progressStart(count($tokenIds));

        foreach ($tokenIds as $i => $newId) {
            $oldId = $i + $startTokenId;

            $this->collectionManager->temporarlyMoveToken($oldId, $newId);

            $symfonyStyle->progressAdvance();
        }

        $symfonyStyle->progressFinish();

        $this->collectionManager->cleanTemporaryNames();

        $symfonyStyle->success('Collection shuffled successfully!');

        $symfonyStyle->info('Remember to run "bin/console nft:update-metadata URI_PREFIX" to update the json content!');

        return Command::SUCCESS;
    }
}
