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

namespace App\Contract;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
interface TotalSupplyProviderInterface
{
    public function getTotalSupply(): int;
}
