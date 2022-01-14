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

namespace App\Config;

class RouteName
{
    /**
     * @var string
     */
    final public const ROOT = 'root';

    /**
     * @var string
     */
    final public const GET_ASSET = 'get_asset';

    /**
     * @var string
     */
    final public const GET_HIDDEN_ASSET = 'get_hidden_asset';

    /**
     * @var string
     */
    final public const GET_METADATA = 'get_metadata';
}
