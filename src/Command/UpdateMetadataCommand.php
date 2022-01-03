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
    name: 'nft:update-metadata',
    description: 'Updates the metadata inside all JSON files',
)]
class UpdateMetadataCommand extends Command
{
    /**
     * @var string
     */
    private const URI_PREFIX_OPTION = 'uri-prefix';

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
                self::URI_PREFIX_OPTION,
                InputArgument::REQUIRED,
                'The URI prefix for assets (e.g. IPFS folder)',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $uriPrefix = $input->getArgument(self::URI_PREFIX_OPTION);

        if (! is_string($uriPrefix) || strlen($uriPrefix) <= 0) {
            throw new RuntimeException('Invalid URI prefix.');
        }

        if (! $symfonyStyle->confirm('You are about to update your collection metadata. Are you sure?', false)) {
            $symfonyStyle->warning('Aborting...');

            return Command::SUCCESS;
        }

        $this->collectionManager->updateMetadata($uriPrefix);

        $symfonyStyle->success('Metadata updated successfully!');

        return Command::SUCCESS;
    }
}
