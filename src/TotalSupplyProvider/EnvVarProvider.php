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

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
final class EnvVarProvider implements TotalSupplyProviderInterface
{
    public function __construct(
        private readonly int $totalSupply,
    ) {
    }

    public function getTotalSupply(): int
    {
        return $this->totalSupply;
    }
}
