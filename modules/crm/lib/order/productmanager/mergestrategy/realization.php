<?php

namespace Bitrix\Crm\Order\ProductManager\MergeStrategy;

class Realization extends Base
{
	public function mergeProducts($orderProducts, $dealProducts): array
	{
		$result = [];

		$counter = 0;
		$foundProducts = [];

		foreach ($dealProducts as $dealProduct)
		{
			$index = $this->searchProduct($dealProduct, $orderProducts);
			if ($index === false)
			{
				$basketItem = $this->getBasketItemByEntityProduct($dealProduct, $foundProducts);
				if ($basketItem)
				{
					$dealProduct['BASKET_CODE'] = $basketItem->getBasketCode();
				}
				else
				{
					$dealProduct['BASKET_CODE'] = 'n' . (++$counter);
				}
			}
			else
			{
				if (!$this->getOrder())
				{
					continue;
				}

				$basketItem = $this->getOrder()->getBasket()->getItemByBasketCode($orderProducts[$index]['BASKET_CODE']);
				if (!$basketItem)
				{
					continue;
				}

				$dealProduct['BASKET_CODE'] = $basketItem->getBasketCode();
				$dealProduct['QUANTITY'] -= $orderProducts[$index]['QUANTITY'];
			}

			$result[] = $dealProduct;
		}

		return $result;
	}
}
