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
use Symfony\Component\Console\Input\InputArgument;
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
class ExportMetadataCommand extends Command
{
    /**
     * @var string
     */
    final public const NAME = 'nft:export-metadata';

    /**
     * @var string
     */
    final public const DESCRIPTION = 'Exports a new metadata folder with all the files updating and shuffling them using the current mapping (if any)';

    /**
     * @var string
     */
    private const URI_PREFIX = 'uri-prefix';

    public function __construct(
        private readonly CollectionManager $collectionManager,
        string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::URI_PREFIX,
                InputArgument::REQUIRED,
                'The URI prefix where the exported assets have been uploaded to',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $uriPrefix = $input->getArgument(self::URI_PREFIX);

        if (! is_string($uriPrefix) || strlen($uriPrefix) <= 0) {
            throw new RuntimeException('Invalid URI prefix.');
        }

        if (! $symfonyStyle->confirm(
            "I'm about to delete the exported metadata directory and its content. Are you sure?",
            false,
        )) {
            $symfonyStyle->warning('Aborting...');

            return Command::SUCCESS;
        }

        $symfonyStyle->info('Deleting old data...');
        $this->collectionManager->clearExportedMetadata();

        $symfonyStyle->info('Exporting new data...');
        $symfonyStyle->progressStart($this->collectionManager->getMaxTokenId());

        foreach (range(1, $this->collectionManager->getMaxTokenId()) as $tokenId) {
            $this->collectionManager->storeExportedMetadata($tokenId, trim($uriPrefix, '/'));
            gc_collect_cycles();

            $symfonyStyle->progressAdvance();
        }

        $symfonyStyle->progressFinish();

        $symfonyStyle->success('Metadata exported successfully!');

        return Command::SUCCESS;
    }
}
