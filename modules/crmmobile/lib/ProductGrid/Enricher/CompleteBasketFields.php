<?php

namespace Bitrix\CrmMobile\ProductGrid\Enricher;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\ProductManager;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service\Container;

class CompleteBasketFields implements EnricherContract
{
	private Item $entity;

	public function __construct(Item $entity)
	{
		$this->entity = $entity;
	}

	public function enrich(array $rows): array
	{
		$productManager = new ProductManager(
			$this->entity->getEntityTypeId(),
			$this->entity->getId()
		);

		$orderId = $this->getOrderId();
		if (!is_null($orderId))
		{
			$order = Order::load($orderId);
			if (!is_null($order))
			{
				$productManager->setOrder($order);
			}
		}

		$payableItems = $productManager->getPayableItems();

		$result = [];

		foreach ($rows as $row)
		{
			$payableItem = $this->getPayableItem($row->toArray(), $payableItems);
			if (is_null($payableItem))
			{
				continue;
			}

			$row->source['QUANTITY'] = $payableItem['QUANTITY'];

			$originBasketCode = '';
			if (mb_strpos((string)$payableItem['BASKET_CODE'], 'n') === false)
			{
				$originBasketCode = $payableItem['BASKET_CODE'];
			}

			$basketItemFields = [
				'BASKET_CODE' => $payableItem['BASKET_CODE'],
				'XML_ID' => $payableItem['XML_ID'],
				'MODULE' => $payableItem['MODULE'],
				'ADDITIONAL_FIELDS' => [
					'ORIGIN_BASKET_ID' => $originBasketCode,
					'ORIGIN_PRODUCT_ID' => $row->getProductId(),
				],
			];
			$row->basketItemFields = $basketItemFields;

			$result[] = $row;
		}

		return $result;
	}

	private function getPayableItem(array $entityProduct, array $payableItems): ?array
	{
		foreach ($payableItems as $payableItem)
		{
			if ((int)$payableItem['PRODUCT_ID'] === (int)$entityProduct['PRODUCT_ID'])
			{
				return $payableItem;
			}
		}

		return null;
	}

	private function getOrderId(): ?int
	{
		$relation = Container::getInstance()->getRelationManager()
			->getRelation(
				new RelationIdentifier(
					$this->entity->getEntityTypeId(),
					\CCrmOwnerType::Order
				)
			)
		;
		if (!$relation)
		{
			return null;
		}

		$result = null;

		$orderIdentifiers = $relation->getChildElements(
			new ItemIdentifier(
				$this->entity->getEntityTypeId(),
				$this->entity->getId()
			)
		);
		foreach ($orderIdentifiers as $orderIdentifier)
		{
			$result = $orderIdentifier->getEntityId();
		}

		return $result;
	}
}
