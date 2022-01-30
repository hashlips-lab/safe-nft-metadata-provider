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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
#[Route(
    '/hidden.{_format}',
    name: RouteName::GET_HIDDEN_ASSET,
    defaults: [
        '_format' => null,
    ],
)]
final class HiddenAssetController extends AbstractNftController
{
    public function __invoke(): Response
    {
        return $this->collectionManager->getHiddenAssetResponse()
            ->setPublic()
            ->setMaxAge($this->getDefaultCacheExpiration())
        ;
    }
}
