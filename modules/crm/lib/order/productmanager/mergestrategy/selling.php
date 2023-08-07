<?php

namespace Bitrix\Crm\Order\ProductManager\MergeStrategy;

class Selling extends Base
{
	/**
	 * @inheritDoc
	 */
	public function mergeProducts($orderProducts, $dealProducts): array
	{
		$result = [];

		$counter = 0;
		$foundProducts = [];

		foreach ($dealProducts as $product)
		{
			$index = $this->searchProduct($product, $orderProducts);
			if ($index === false)
			{
				$basketItem = $this->getBasketItemByEntityProduct($product, $foundProducts, true);
				if ($basketItem)
				{
					if ($product['QUANTITY'] <= $basketItem->getQuantity())
					{
						continue;
					}

					$product['BASKET_CODE'] = $basketItem->getBasketCode();
					$product['QUANTITY'] -= $basketItem->getQuantity();
				}
				else
				{
					$product['BASKET_CODE'] = 'n'.(++$counter);
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

				$product['BASKET_CODE'] = $basketItem->getBasketCode();

				$product['QUANTITY'] = $orderProducts[$index]['QUANTITY'];
			}

			$result[] = $product;
		}

		return $result;
	}
}
