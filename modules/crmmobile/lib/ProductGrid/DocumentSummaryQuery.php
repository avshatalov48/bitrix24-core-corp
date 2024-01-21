<?php

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\CatalogMobile\Catalog;
use Bitrix\Crm\Order\BasketItem;
use Bitrix\Crm\Order\PayableShipmentItem;
use Bitrix\Crm\Order\Shipment;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Sale\Repository\PaymentRepository;

Loader::requireModule('crm');
Loader::requireModule('catalogmobile');

final class DocumentSummaryQuery extends BaseSummaryQuery
{
	private int $documentId;

	public function __construct(int $documentId, array $products, ?string $currencyId = null, array $additionalConfig = [])
	{
		$this->documentId = $documentId;
		$this->products = $products;
		$this->currencyId = $currencyId ?? Catalog::getBaseCurrency();
		$this->accounting = Container::getInstance()->getAccounting();
		$this->precision = \CCrmCurrency::GetCurrencyDecimals($this->currencyId);
		$this->additionalConfig = $additionalConfig;
	}

	public function execute(): array
	{
		$payment = PaymentRepository::getInstance()->getById($this->documentId);
		if (!$payment)
		{
			return [];
		}
		$items = $payment->getPayableItemCollection()->toArray();
		$deliveryCost = 0;
		foreach ($payment->getPayableItemCollection()->getShipments() as $delivery)
		{
			/**
			 * @var Shipment $entity
			 * @var PayableShipmentItem $delivery
			 */
			$entity = $delivery->getEntityObject();
			if (!$entity)
			{
				continue;
			}
			$deliveryCost += $entity->getPrice();
		}
		$productCost = 0;
		$totalTax = 0;
		$totalDiscount = 0;
		$totalBrutto = 0;
		foreach ($payment->getPayableItemCollection()->getBasketItems() as $item)
		{
			/**
			 * @var BasketItem $entity
			 */
			$entity = $item->getEntityObject();
			$quantity = $item->getQuantity();
			$productCost += $entity->getPriceWithVat() * $quantity;
			$totalTax += $entity->getVat() * $quantity;
			$totalDiscount += ($entity->getBasePriceWithVat() - $entity->getPriceWithVat()) * $quantity;
			$totalBrutto += $entity->getBasePriceWithVat() * $quantity;
		}

		return [
			'items' => $items,
			'totalRows' => count($items),
			'totalProductCost' => $productCost,
			'deliveryCost' => $deliveryCost,
			'totalCost' => $productCost + $deliveryCost,
			'totalTax' => $totalTax,
			'totalDiscount' => $totalDiscount,
			'totalWithoutDiscount' => $totalBrutto + $deliveryCost,
			'currency' => $this->currencyId,
		];
	}
}
