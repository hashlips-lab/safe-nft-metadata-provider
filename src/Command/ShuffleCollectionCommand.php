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
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
#[AsCommand(
    name: self::NAME,
    description: self::DESCRIPTION,
)]
class ShuffleCollectionCommand extends Command
{
    /**
     * @var string
     */
    final public const NAME = 'nft:shuffle-collection';

    /**
     * @var string
     */
    final public const DESCRIPTION = 'Generates a new shuffle mapping for all the tokens (or a given range)';

    /**
     * @var string
     */
    private const MIN_TOKEN_ID = 'min';

    /**
     * @var string
     */
    private const MAX_TOKEN_ID = 'max';

    public function __construct(
        private readonly CollectionManager $collectionManager,
        string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addOption(
            self::MIN_TOKEN_ID,
            null,
            InputOption::VALUE_OPTIONAL,
            'The minimum token ID to be shuffled',
        );

        $this->addOption(
            self::MAX_TOKEN_ID,
            null,
            InputOption::VALUE_OPTIONAL,
            'The maximum token ID to be shuffled',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $minTokenIdOptionValue = $input->getOption(self::MIN_TOKEN_ID);
        $minTokenId = is_numeric($minTokenIdOptionValue) ? (int) $minTokenIdOptionValue : 1;
        $maxTokenIdOptionValue = $input->getOption(self::MAX_TOKEN_ID);
        $maxTokenId = is_numeric(
            $maxTokenIdOptionValue,
        ) ? (int) $maxTokenIdOptionValue : $this->collectionManager->getMaxTokenId();

        if (! $symfonyStyle->confirm(
            "I'm about to generate a new shuffle mapping for your collection starting from token #".$minTokenId.' up to token #'.$maxTokenId.'. This will overwrite the previous shuffle mapping. Are you sure?',
            false,
        )) {
            $symfonyStyle->warning('Aborting...');

            return Command::SUCCESS;
        }

        $this->collectionManager->shuffle($minTokenId, $maxTokenId);

        $symfonyStyle->info('The new shuffle mapping has been generated and added to the storage...');

        // Clear app cache...
        $symfonyStyle->info('Running "bin/console cache:clear"...');

        $command = $this->getApplication()?->find('cache:clear');

        if (! $command instanceof Command) {
            throw new RuntimeException('Could not find the "cache:clear" command.');
        }

        $returnCode = $command->run(new ArrayInput([]), $output);

        if (Command::SUCCESS !== $returnCode) {
            throw new RuntimeException('An error occurred while clearing the cache.');
        }

        $symfonyStyle->success('New shuffle mapping generated successfully!');

        return Command::SUCCESS;
    }
}
