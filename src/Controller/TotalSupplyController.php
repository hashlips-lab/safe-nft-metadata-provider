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
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @author Marco Lipparini <developer@liarco.net>
 */
#[Route('/total-supply', name: RouteName::GET_TOTAL_SUPPLY)]
final class TotalSupplyController extends AbstractNftController
{
    /**
     * @var string
     */
    private const CACHE_RESPONSE_DATA = 'total_supply_controller.response_data';

    public function __invoke(): Response
    {
        /** @var array<string, int> $responseData */
        $responseData = $this->cache->get(self::CACHE_RESPONSE_DATA, function (ItemInterface $item): array {
            $item->expiresAfter($this->getDefaultCacheExpiration());

            return [
                'total_supply' => $this->totalSupplyProvider->getTotalSupply(),
            ];
        });

        return $this->json($responseData);
    }
}
