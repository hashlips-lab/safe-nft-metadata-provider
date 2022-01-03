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

namespace App\MetadataUpdater;

use App\Contract\MetadataUpdaterInterface;
use App\Service\CollectionManager;

/**
 * This is an example custom metadata updater for the SABC collection.
 *
 * It updates the following properties using the new token ID:
 *  - name
 *  - edition
 *
 * @author Marco Lipparini <developer@liarco.net>
 */
final class SabcMetadataUpdater implements MetadataUpdaterInterface
{
    public function updateMetadata(
        array &$metadata,
        string $uriPrefix,
        string $tokenId,
        CollectionManager $collectionManager,
    ): void {
        $metadata['name'] = 'SABC #'.$tokenId;
        $metadata['edition'] = (int) $tokenId;
    }
}
