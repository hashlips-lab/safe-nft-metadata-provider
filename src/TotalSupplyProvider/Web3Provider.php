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

namespace App\TotalSupplyProvider;

use App\Contract\CollectionContractInterface;
use App\Contract\TotalSupplyProviderInterface;
use App\Service\CollectionManager;
use Ethereum\Ethereum;
use Ethereum\SmartContract;
use RuntimeException;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
final class Web3Provider implements TotalSupplyProviderInterface
{
    private int $totalSupply;

    private readonly SmartContract $contract;

    public function __construct(
        string $contractAddress,
        string $infuraEndpoint,
        CollectionManager $collectionManager,
    ) {
        $this->contract = new SmartContract(
            $collectionManager->getAbi(),
            $contractAddress,
            new Ethereum($infuraEndpoint),
        );
    }

    public function getTotalSupply(): int
    {
        if (! isset($this->totalSupply)) {
            /** @var CollectionContractInterface $smartContract */
            $smartContract = $this->contract;
            $totalSupply = $smartContract->totalSupply()->val();

            if (! is_int($totalSupply)) {
                throw new RuntimeException('Unexpected result from "totalSupply" call: "'.$totalSupply.'"');
            }

            $this->totalSupply = $totalSupply;
        }

        return $this->totalSupply;
    }
}
