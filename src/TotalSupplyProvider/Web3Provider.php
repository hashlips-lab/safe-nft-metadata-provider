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

use App\Contract\TotalSupplyProviderInterface;
use App\Service\CollectionManager;
use phpseclib\Math\BigInteger;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Web3\Contract;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
final class Web3Provider implements TotalSupplyProviderInterface
{
    private int $totalSupply;

    private readonly Contract $contract;

    public function __construct(
        private readonly string $contractAddress,
        string $infuraEndpoint,
        CollectionManager $collectionManager,
        private readonly CacheInterface $cache,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        $this->contract = new Contract($infuraEndpoint, $collectionManager->getAbi());
    }

    public function getTotalSupply(): int
    {
        if (! isset($this->totalSupply)) {
            /** @var int $cachedTotalSupply */
            $cachedTotalSupply = $this->cache->get('web3_provider.total_supply', function (ItemInterface $item): int {
                $item->expiresAfter((int) $this->parameterBag->get('app.cache_expiration'));
                $totalSupply = 0;

                $this->contract->at($this->contractAddress)->call(
                    'totalSupply',
                    [],
                    function ($error, $decodedTransaction) use (&$totalSupply): void {
                        /** @var BigInteger $result */
                        $result = $decodedTransaction[0];

                        $totalSupply = (int) $result->toString();
                    },
                );

                if (0 === $totalSupply) {
                    throw new RuntimeException('Unable to get total supply using Web3 provider.');
                }

                return $totalSupply;
            });

            $this->totalSupply = $cachedTotalSupply;
        }

        return $this->totalSupply;
    }
}
