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
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
#[AsCommand(
    name: 'nft:build-assets',
    description: 'Builds a new assets folder with all files shuffled using the current mapping',
)]
class BuildAssetsCommand extends Command
{
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
                self::OUTPUT_PATH,
                InputArgument::REQUIRED,
                'The output path for the new files (local paths only)',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $outputPath = $input->getArgument(self::OUTPUT_PATH);

        if (! is_string($outputPath) || strlen($outputPath) <= 0) {
            throw new RuntimeException('Invalid output path.');
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
            $this->filesystem->copy(
                $this->collectionManager->getAssetFileInfo($tokenId)->getPathname(),
                $outputPath.'/'.$tokenId.'.'.$this->collectionManager->getAssetsExtension(),
            );

            $symfonyStyle->progressAdvance();
        }

        $symfonyStyle->progressFinish();

        $symfonyStyle->success('Assets built successfully!');

        return Command::SUCCESS;
    }
}
