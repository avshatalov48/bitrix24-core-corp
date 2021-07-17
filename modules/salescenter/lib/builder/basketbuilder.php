<?php

namespace Bitrix\Salescenter\Builder;

use Bitrix\Main;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Helpers;
use Bitrix\Sale\PayableBasketItem;
use Bitrix\Sale\Payment;

/**
 * Class BasketBuilder
 * @package Bitrix\Salescneter\Builder
 */
class BasketBuilder extends Helpers\Order\Builder\BasketBuilder
{
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

		foreach ($this->formData['PRODUCT'] as $index => $product)
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
					$result[$product['BASKET_CODE']] = $product;

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
				if ($basketItem)
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
					$product['ID'] = $index;
				}
			}

			$result[$index] = $product;
		}

		$this->formData['PRODUCT'] = $result;

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

	protected function getNotDistributedItemQuantity(BasketItem $basketItem)
	{
		$order = $this->getOrder();
		if (!$order)
		{
			return 0;
		}

		if ($this->getSettingsContainer()->getItemValue('builderScenario') === SettingsContainer::BUILDER_SCENARIO_SHIPMENT)
		{
			$systemShipment = $order->getShipmentCollection()->getSystemShipment();
			return $systemShipment->getBasketItemQuantity($basketItem);
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
	 * @param $moduleId
	 * @param $productId
	 * @param array $properties
	 * @return BasketItem|null
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
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