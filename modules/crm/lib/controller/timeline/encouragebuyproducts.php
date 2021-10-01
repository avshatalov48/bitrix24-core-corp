<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Main\Engine\Controller;

/**
 * Class EncourageBuyProducts
 * @package Bitrix\SalesCenter\Controller
 */
class EncourageBuyProducts extends Controller
{
	/**
	 * @param int $dealId
	 * @param int $productId
	 * @param array $options
	 */
	public function addProductToDealAction(int $dealId, int $productId, array $options = [])
	{
		if (!\CCrmDeal::CheckUpdatePermission($dealId, \CCrmPerms::GetCurrentUserPermissions()))
		{
			return;
		}

		$row = [
			'PRODUCT_ID' => $productId,
			'QUANTITY' => 1,
		];

		if (isset($options['price']))
		{
			$price = $options['price'];

			$row = array_merge(
				$row,
				[
					'PRICE' => $price,
					'PRICE_ACCOUNT' => $price,
					'PRICE_EXCLUSIVE' => $price,
					'PRICE_NETTO' => $price,
					'PRICE_BRUTTO' => $price,
				]
			);
		}

		\CCrmDeal::addProductRows($dealId, [$row]);

	}
}
