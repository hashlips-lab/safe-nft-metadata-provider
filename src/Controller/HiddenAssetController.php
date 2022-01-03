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

namespace App\Controller;

use App\Config\RouteName;
use App\Contract\AbstractNftController;
use App\Service\CollectionManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
#[Route('/hidden', name: RouteName::GET_HIDDEN_ASSET)]
final class HiddenAssetController extends AbstractNftController
{
    public function __invoke(CollectionManager $collectionManager): Response
    {
        return $this->file($collectionManager->getHiddenFilePath(), null, ResponseHeaderBag::DISPOSITION_INLINE)
            ->setPublic()
            ->setMaxAge(self::YEAR_CACHE_EXPIRATION)
        ;
    }
}
