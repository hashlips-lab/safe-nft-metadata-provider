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

use App\TotalSupplyProvider\CachedTotalSupplyProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
#[AsCommand(
    name: 'nft:total-supply',
    description: 'Returns the current total supply',
)]
class TotalSupplyCommand extends Command
{
    public function __construct(
        private readonly CachedTotalSupplyProvider $cachedTotalSupplyProvider,
        string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $symfonyStyle->success('Current total supply: '.$this->cachedTotalSupplyProvider->getTotalSupply().' (cached)');

        return Command::SUCCESS;
    }
}
