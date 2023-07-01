<?php

namespace Bitrix\CrmMobile\Controller\ReceivePayment;

use Bitrix\Crm\Item;
use Bitrix\CrmMobile\ProductGrid\SingleProductReceivePaymentQuery;
use Bitrix\SalesCenter\Controller\Order;

class ProductStep extends Base
{
	use SalescenterControllerWrapper;

	public function loadProductGridSummaryAction(Item $entity,
		array $products = [],
		?string $currencyId = null
	): array
	{
		$orderId = $this->getOrderId($entity);
		$basketItems = $this->prepareBasketItems($products);

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
		return (new SingleProductReceivePaymentQuery($entity, $productId, $currencyId))->execute();
	}
}
