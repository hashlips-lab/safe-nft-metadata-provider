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
use Nette\Utils\Json;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
#[AsCommand(
    name: 'nft:build-metadata',
    description: 'Builds a new metadata folder with all files shuffled using the current mapping',
)]
class BuildMetadataCommand extends Command
{
    /**
     * @var string
     */
    private const URI_PREFIX = 'uri-prefix';

    /**
     * @var string
     */
    private const OUTPUT_PATH = 'output-path';

    public function __construct(
        private readonly CollectionManager $collectionManager,
        private readonly Filesystem $filesystem,
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
                'The URI prefix where the assets have been uploaded to',
            )
        ;

        $this
            ->addArgument(
                self::OUTPUT_PATH,
                InputArgument::REQUIRED,
                'The output path for the new files (local paths only)',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $uriPrefix = $input->getArgument(self::URI_PREFIX);
        $outputPath = $input->getArgument(self::OUTPUT_PATH);

        if (! is_string($outputPath) || strlen($outputPath) <= 0) {
            throw new RuntimeException('Invalid output path.');
        }

        if (! is_string($uriPrefix) || strlen($uriPrefix) <= 0) {
            throw new RuntimeException('Invalid URI prefix.');
        }

        if (is_dir($outputPath)) {
            if (! $symfonyStyle->confirm(
                "I'm about to delete the output directory and its content. Are you sure?",
                false,
            )) {
                $symfonyStyle->warning('Aborting...');

                return Command::SUCCESS;
            }

            $this->filesystem->remove($outputPath);
            $this->filesystem->mkdir($outputPath);
        }

        $symfonyStyle->progressStart($this->collectionManager->getMaxTokenId());

        foreach (range(1, $this->collectionManager->getMaxTokenId()) as $tokenId) {
            $this->filesystem->dumpFile(
                $outputPath.'/'.$tokenId.'.json',
                Json::encode(
                    $this->collectionManager->getMetadata($tokenId, trim($uriPrefix, '/').'/'.$tokenId.'.json'),
                    Json::PRETTY,
                ),
            );

            $symfonyStyle->progressAdvance();
        }

        $symfonyStyle->progressFinish();

        $symfonyStyle->success('Metadata built successfully!');

        return Command::SUCCESS;
    }
}
