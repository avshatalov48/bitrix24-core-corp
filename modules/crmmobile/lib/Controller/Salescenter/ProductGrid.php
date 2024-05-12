<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Salescenter;

use Bitrix\Crm\Engine\ActionFilter\CheckReadPermission;
use Bitrix\Crm\Item;
use Bitrix\CrmMobile\ProductGrid\SingleProductCreatePaymentQuery;
use Bitrix\CrmMobile\Controller\BaseJson;
use Bitrix\Main\Loader;
use Bitrix\SalesCenter\Controller\Order;
use Bitrix\SalesCenter\Integration\CrmManager;
use Bitrix\Main\Engine\ActionFilter;

Loader::requireModule('salescenter');

class ProductGrid extends BaseJson
{
	protected function getDefaultPreFilters(): array
	{
		return [
			...parent::getDefaultPreFilters(),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
			new CheckReadPermission(),
		];
	}

	public function loadProductGridSummaryAction(Item $entity,
		array $products = [],
		?string $currencyId = null
	): array
	{
		$orderId = CrmManager::getOrderIdByEntity($entity);
		$basketItems = Product2BasketItemConverter::convert($products);

		$refreshBasketResult = $this->forward(
			Order::class,
			'refreshBasket',
			[
				'orderId' => $orderId,
				'basketItems' => $basketItems,
			]
		);

		$items = $refreshBasketResult['items'];
		$result = [
			'totalRows' => count($items),
			'currency' => $currencyId,
		];

		$total = $refreshBasketResult['total'] ?? [];
		if (!empty($total))
		{
			$result = array_merge(
				$result,
				[
					'items' => $items,
					'totalRows' => count($items),
					'totalCost' => $total['result'],
					'totalTax' => $total['taxSum'],
					'totalDiscount' => $total['discount'],
					'totalWithoutDiscount' => $total['sum'],
					'taxIncluded' => $this->isTaxIncluded($items),
					'taxPartlyIncluded' => $this->isTaxPartlyIncluded($items),
					'currency' => $currencyId,
				]
			);
		}

		return $result;
	}

	public function loadProductModelAction(int $productId, string $currencyId, Item $entity): array
	{
		return (new SingleProductCreatePaymentQuery($entity, $productId, $currencyId))->execute();
	}

	private function isTaxIncluded(array $items): bool
	{
		foreach ($items as $productRow)
		{
			if ($productRow['taxIncluded'] === 'Y')
			{
				return true;
			}
		}
		return false;
	}

	private function isTaxPartlyIncluded($items): bool
	{
		$hasItemsWithTaxIncluded = null;
		$hasItemsWithNoTaxIncluded = null;

		foreach ($items as $productRow)
		{
			if (isset($productRow['taxIncluded']) && $productRow['taxIncluded'] === 'Y')
			{
				$hasItemsWithTaxIncluded = true;
			}
			elseif (isset($productRow['taxRate']) && $productRow['taxRate'] > 0)
			{
				$hasItemsWithNoTaxIncluded = true;
			}
		}

		return ($hasItemsWithNoTaxIncluded && $hasItemsWithTaxIncluded);
	}
}
