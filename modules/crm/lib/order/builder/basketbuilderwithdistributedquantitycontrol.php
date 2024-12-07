<?php

namespace Bitrix\Crm\Order\Builder;

use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Helpers\Order\Builder\BasketBuilder;
use Bitrix\Sale\Helpers\Order\Builder\SettingsContainer;
use Bitrix\Sale\PayableBasketItem;
use Bitrix\Sale\Payment;

/**
 * Basket builder with processing not distributed quantity.
 */
class BasketBuilderWithDistributedQuantityControl extends BasketBuilder
{
	/**
	 * @inheritDoc
	 */
	public function initBasket()
	{
		parent::initBasket();

		$order = $this->getOrder();
		if (!$order)
		{
			return $this;
		}

		$result = [];
		$maxIndex = $this->getMaxNewIndex($this->formData['PRODUCT']);

		$products = $this->formData['PRODUCT'];
		krsort($products);

		foreach ($products as $index => $product)
		{
			if (!static::isBasketItemNew($index))
			{
				/** @var BasketItem $basketItem */
				$basketItem = $this->getBasket()->getItemByBasketCode($index);
				if (!$basketItem)
				{
					continue;
				}

				$notDistributedQuantity = $this->getNotDistributedItemQuantity($basketItem);

				if (
					$basketItem->getProductId() !== (int)$product['PRODUCT_ID']
					&& abs($notDistributedQuantity - $basketItem->getQuantity()) > 1e-5
				)
				{
					$result[$index] = $basketItem->getFieldValues();
					$result[$index]['QUANTITY'] = $basketItem->getQuantity() - $notDistributedQuantity;
					$result[$index]['MANUALLY_EDITED'] = 'Y';

					$product['BASKET_CODE'] = 'n'.++$maxIndex;
					$product['ORIGIN_BASKET_ID'] = $basketItem->getId();
					unset($product['XML_ID']);

					$result[$product['BASKET_CODE']] = $product;

					$basketCodeMap[$product['ORIGIN_BASKET_ID']] = $product['BASKET_CODE'];

					continue;
				}

				if ($notDistributedQuantity === 0)
				{
					$newQuantity = $basketItem->getQuantity() + $product['QUANTITY'];
				}
				else if ($notDistributedQuantity < $product['QUANTITY'])
				{
					$newQuantity = $basketItem->getQuantity() + $product['QUANTITY'] - $notDistributedQuantity;
				}
				else
				{
					$newQuantity = $basketItem->getQuantity();
				}

				$product['ORIGIN_PRODUCT_ID'] = $basketItem->getProductId();
				$product['QUANTITY'] = $newQuantity;
				$product['ID'] = $index;
			}
			elseif (!empty($product['ORIGIN_BASKET_ID']))
			{
				/** @var BasketItem $basketItem */
				$basketItem = $this->getBasket()->getItemById($product['ORIGIN_BASKET_ID']);
				if (!$basketItem)
				{
					continue;
				}

				$notDistributedQuantity = $this->getNotDistributedItemQuantity($basketItem);

				$newIndex = $basketItem->getId();

				$result[$newIndex] = $basketItem->getFieldValues();
				if ($notDistributedQuantity >= $product['QUANTITY'])
				{
					$result[$newIndex]['QUANTITY'] = $basketItem->getQuantity() - $product['QUANTITY'];
				}
				else
				{
					$result[$newIndex]['QUANTITY'] = $basketItem->getQuantity() - $notDistributedQuantity;
				}

				$result[$newIndex]['MANUALLY_EDITED'] = 'Y';
			}
			else
			{
				$basketItem = $this->getExistsItem($product['MODULE'], $product['PRODUCT_ID']);
				if (
					$basketItem
					&& !isset($result[$basketItem->getId()])
				)
				{
					$index = $basketItem->getId();

					$notDistributedQuantity = $this->getNotDistributedItemQuantity($basketItem);
					if ($notDistributedQuantity === 0)
					{
						$newQuantity = $basketItem->getQuantity() + $product['QUANTITY'];
					}
					else if ($notDistributedQuantity < $product['QUANTITY'])
					{
						$newQuantity = $basketItem->getQuantity() + $product['QUANTITY'] - $notDistributedQuantity;
					}
					else
					{
						$newQuantity = $basketItem->getQuantity();
					}

					$product['QUANTITY'] = $newQuantity;

					$basketCodeMap[$product['BASKET_CODE']] = $index;

					$product['ID'] = $index;
					$product['BASKET_CODE'] = $index;
					$product['BASKET_ID'] = $index;
					$product['XML_ID'] = $basketItem->getField('XML_ID');
				}
				else
				{
					$product['MANUALLY_EDITED'] = 'Y';
				}
			}

			$result[$index] = $product;
		}

		$this->formData['PRODUCT'] = $result;

		// prepare shipment product
		if (isset($basketCodeMap) && !empty($this->formData['SHIPMENT']))
		{
			foreach ($this->formData['SHIPMENT'] as $index => $shipment)
			{
				$shipmentProducts = $shipment['PRODUCT'] ?? null;
				if ($shipmentProducts)
				{
					$preparedShipmentProducts = [];
					foreach ($shipmentProducts as $code => $shipmentProduct)
					{
						if (isset($basketCodeMap[$code]))
						{
							$code = $basketCodeMap[$code];
							$shipmentProduct['BASKET_ID'] = $code;
							$shipmentProduct['BASKET_CODE'] = $code;
						}

						$preparedShipmentProducts[$code] = $shipmentProduct;
					}

					$this->formData['SHIPMENT'][$index]['PRODUCT'] = $preparedShipmentProducts;
				}
			}
		}

		// prepare payment product
		if (isset($basketCodeMap) && !empty($this->formData['PAYMENT']))
		{
			foreach ($this->formData['PAYMENT'] as $index => $payment)
			{
				$paymentProducts = $payment['PRODUCT'] ?? null;
				if ($paymentProducts)
				{
					$preparedPaymentProducts = [];
					foreach ($paymentProducts as $code => $paymentProduct)
					{
						if (isset($basketCodeMap[$code]))
						{
							$code = $basketCodeMap[$code];

							$paymentProduct['BASKET_CODE'] = $code;
						}

						$preparedPaymentProducts[$code] = $paymentProduct;
					}

					$this->formData['PAYMENT'][$index]['PRODUCT'] = $preparedPaymentProducts;
				}
			}
		}

		return $this;
	}

	private function getMaxNewIndex($productList)
	{
		$max = 0;
		foreach ($productList as $index => $product)
		{
			if (mb_strpos($index, 'n') !== false)
			{
				$number = mb_substr($index, 0, 1);
				if ($number > $max)
				{
					$max = $number;
				}
			}
		}

		return $max;
	}

	private function getNotDistributedItemQuantity(BasketItem $basketItem)
	{
		$order = $this->getOrder();
		if (!$order)
		{
			return 0;
		}

		if ($this->getSettingsContainer()->getItemValue('builderScenario') === SettingsContainer::BUILDER_SCENARIO_SHIPMENT)
		{
			$systemShipment = $order->getShipmentCollection()->getSystemShipment();
			$quantity = $systemShipment->getBasketItemQuantity($basketItem);

			if (isset($this->formData['SHIPMENT']))
			{
				foreach ($this->formData['SHIPMENT'] as $shipmentArray)
				{
					if (empty($shipmentArray['ID']))
					{
						continue;
					}

					$shipment = $order->getShipmentCollection()->getItemById($shipmentArray['ID']);
					$quantity += $shipment->getBasketItemQuantity($basketItem);
				}
			}

			return $quantity;
		}

		$distributedQuantity = 0;
		/** @var Payment $payment */
		foreach ($order->getPaymentCollection() as $payment)
		{
			/** @var PayableBasketItem $item */
			foreach ($payment->getPayableItemCollection()->getBasketItems() as $item)
			{
				$entity = $item->getEntityObject();
				if ($entity->getBasketCode() === $basketItem->getBasketCode())
				{
					$distributedQuantity += $item->getQuantity();
				}
			}
		}

		return $basketItem->getQuantity() - $distributedQuantity;
	}

	/**
	 * @inheritDoc
	 */
	protected function getExistsItem($moduleId, $productId, array $properties = [])
	{
		/** @var BasketItem $basketItem */
		foreach ($this->getBasket() as $basketItem)
		{
			if (
				$basketItem->getField('MODULE') === $moduleId
				&&
				$basketItem->getProductId() === (int)$productId
			)
			{
				return $basketItem;
			}
		}

		return null;
	}
}
