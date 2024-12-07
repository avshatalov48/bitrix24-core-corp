<?php

namespace Bitrix\Crm\Controller\Item\Payment;

use Bitrix\Crm;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\OrderDealSynchronizer\Products;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Salescenter;

class Product extends Base
{
	public function listAction(
		int $paymentId,
		array $filter = [],
		array $order = []
	): ?array
	{
		$paymentItemList = parent::listAction($paymentId, $filter, $order);
		if (is_null($paymentItemList))
		{
			return null;
		}

		$result = [];
		$xmlIdList = [];

		foreach ($paymentItemList as $paymentItem)
		{
			$xmlIdList[] = Products\ProductRowXmlId::getXmlIdFromBasketId($paymentItem['ENTITY_ID']);

			$result[$paymentItem['ENTITY_ID']] = $paymentItem;
		}

		if (!$result)
		{
			return [];
		}

		$dbRes = ProductRowTable::getList(
			[
				'select' => ['ID', 'XML_ID'],
				'filter' => ['=XML_ID' => $xmlIdList],
			]
		);
		while ($row = $dbRes->fetch())
		{
			$basketId = Products\ProductRowXmlId::getBasketIdFromXmlId($row['XML_ID']);

			$result[$basketId]['ROW_ID'] = (int)$row['ID'];

			unset($result[$basketId]['ENTITY_ID']);
		}

		$productList = array_values($result);

		return $this->convertKeysToCamelCase($productList);
	}

	public function addAction(int $paymentId, int $rowId, int $quantity) : ?int
	{
		if ($quantity <= 0)
		{
			$this->addError(
				new Error(
					Loc::getMessage('CRM_CONTROLLER_ITEM_PAYMENT_PRODUCT_ZERO_QUANTITY')
				)
			);

			return null;
		}

		/** @var Crm\Order\Payment $payment */
		$payment = $this->getPaymentById($paymentId);
		if (!$payment)
		{
			$this->addError(new Error('Payment has not been found'));

			return null;
		}

		if (!$this->canEditPayment($payment))
		{
			$this->setAccessDenied();

			return null;
		}

		$order = Order::load($payment->getOrderId());

		$binding = $order->getEntityBinding();
		$entityId = $binding ? $binding->getOwnerId() : 0;
		$entityTypeId = $binding ? $binding->getOwnerTypeId() : 0;

		if (!$entityTypeId || !$entityId)
		{
			$this->addError(new Error('Order binding has not been found'));

			return null;
		}

		$factory = Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		if (!$factory || !$factory->isLinkWithProductsEnabled())
		{
			$this->addError(new Error('Entity does not support links with catalog products'));

			return null;
		}

		$productManager = new Crm\Order\ProductManager($entityTypeId, $entityId);
		$productManager->setOrder($order);

		$payableItems = $productManager->getPayableItems();

		$productRow = $this->findItemByRowId($rowId, $payableItems);
		if (!$productRow)
		{
			$this->addError(
				new Error(
					Loc::getMessage('CRM_CONTROLLER_ITEM_PAYMENT_PRODUCT_NOT_FOUND')
				)
			);

			return null;
		}

		if ($productRow['QUANTITY'] < $quantity)
		{
			$this->addError(
				new Error(
					Loc::getMessage('CRM_CONTROLLER_ITEM_PAYMENT_PRODUCT_AVAILABLE_QUANTITY')
				)
			);

			return null;
		}

		$fields = $this->getFieldsForBuilder($productRow, $payment, $quantity);

		$builder = SalesCenter\Builder\Manager::getBuilder(
			Sale\Helpers\Order\Builder\SettingsContainer::BUILDER_SCENARIO_PAYMENT
		);

		$order = $builder->build($fields)->getOrder();

		/** @var Crm\Order\Payment $payment */
		$payment = $order->getPaymentCollection()->getItemById($payment->getId());

		$payableItem = $this->getNewPayableItem($payment);
		if (!$payableItem)
		{
			$this->addErrors($builder->getErrorsContainer()->getErrors());

			return null;
		}

		$this->recalculatePaymentSum($payment);

		$result = $order->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $payableItem->getId();
	}

	private function findItemByRowId(int $rowId, array $payableItems)
	{
		$productRow = ProductRowTable::getRowById($rowId);
		if (!$productRow)
		{
			return [];
		}

		foreach ($payableItems as $payableItem)
		{
			if ($payableItem['XML_ID'] === Products\BasketXmlId::getXmlIdFromRowId($productRow['ID']))
			{
				return $payableItem;
			}
		}

		return [];
	}

	private function getNewPayableItem(Crm\Order\Payment $payment) : ?Crm\Order\PayableBasketItem
	{
		foreach ($payment->getPayableItemCollection()->getBasketItems() as $payableItem)
		{
			if ($payableItem->getId() === 0)
			{
				return $payableItem;
			}
		}

		return null;
	}

	public function setQuantityAction(int $id, int $quantity): ?bool
	{
		if ($quantity <= 0)
		{
			$this->addError(
				new Error(
					Loc::getMessage('CRM_CONTROLLER_ITEM_PAYMENT_PRODUCT_ZERO_QUANTITY')
				)
			);

			return null;
		}

		$payment = $this->getPaymentByPayableId($id);
		if (!$payment)
		{
			$this->addError(new Error('Payable item has not been found'));

			return null;
		}

		if (!$this->canEditPayment($payment))
		{
			$this->setAccessDenied();

			return null;
		}

		/** @var Crm\Order\PayableBasketItem $payableItem */
		$payableItem = $payment->getPayableItemCollection()->getItemById($id);

		if (!$this->canSetQuantity($payableItem, $quantity))
		{
			$this->addError(
				new Error(
					Loc::getMessage('CRM_CONTROLLER_ITEM_PAYMENT_PRODUCT_AVAILABLE_QUANTITY')
				)
			);

			return null;
		}

		$result = $payableItem->setField('QUANTITY', $quantity);
		if (!$result->isSuccess())
		{
			$this->addError(new Error('Set quantity internal error'));

			return null;
		}

		$this->recalculatePaymentSum($payment);

		$result = $payment->getOrder()->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	private function canSetQuantity(Crm\Order\PayableBasketItem $payableItem, float $quantity) : bool
	{
		/** @var Crm\Order\BasketItem $basketItem */
		$basketItem = $payableItem->getEntityObject();

		/** @var Crm\Order\PayableItemCollection $payableCollection */
		$payableCollection = $payableItem->getCollection();

		/** @var Crm\Order\PaymentCollection $paymentCollection */
		$paymentCollection = $payableCollection->getPayment()->getCollection();

		$disturbQuantity = $paymentCollection->getBasketItemQuantity($basketItem);

		return ($disturbQuantity - $payableItem->getQuantity()) + $quantity <= $basketItem->getQuantity();
	}

	private function getFieldsForBuilder(array $product, Crm\Order\Payment $payment, $quantity) : array
	{
		$basketCode = $product['BASKET_CODE'];

		$fields = [
			'ID' => $payment->getOrder()->getId(),
			'PRODUCT' => [$basketCode => $product],
			'PAYMENT' => [
				[
					'ID' => $payment->getId(),
					'PRODUCT' => [
						$basketCode => [
							'BASKET_CODE' => $product['BASKET_CODE'],
							'QUANTITY' => $quantity
						]
					]
				]
			]
		];

		$entity = $payment->getOrder()->getEntityBinding();
		if ($entity)
		{
			$fields['CLIENT'] = Salescenter\Integration\CrmManager::getInstance()->getClientInfo(
				$entity->getOwnerTypeId(),
				$entity->getOwnerId()
			);
		}

		return $fields;
	}

	protected function getEntityType(): string
	{
		return Sale\Registry::ENTITY_BASKET_ITEM;
	}
}
