<?php

namespace Bitrix\Crm\Controller\Item\Payment;

use Bitrix\Crm;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\OrderDealSynchronizer\Products;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Sale;
use Bitrix\Salescenter;

class Product extends Base
{
	/**
	 * @param int $paymentId
	 * @param array $filter
	 * @param array $order
	 * @return array
	 * @throws SystemException
	 */
	public function listAction(
		int $paymentId,
		array $filter = [],
		array $order = []
	): array
	{
		$paymentItemList = parent::listAction($paymentId, $filter, $order);

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

			$result[$basketId]['ROW_ID'] = $row['ID'];

			unset($result[$basketId]['ENTITY_ID']);
		}

		$productList = array_values($result);

		return $this->convertKeysToCamelCase($productList);
	}


	/**
	 * @param int $paymentId
	 * @param int $rowId
	 * @param int $quantity
	 * @return int|null
	 * @throws SystemException
	 */
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

		$payment = $this->getPaymentById($paymentId);
		if (!$payment || !$this->canEditPayment($payment))
		{
			return null;
		}

		$order = Order::load($payment->getOrderId());

		$binding = $order->getEntityBinding();
		$entityId = $binding ? $binding->getOwnerId() : 0;
		$entityTypeId = $binding ? $binding->getOwnerTypeId() : 0;

		if (!$entityTypeId || !$entityId)
		{
			return null;
		}

		$factory = Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		if (!$factory || !$factory->isLinkWithProductsEnabled())
		{
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

	private function getNewPayableItem(Sale\Payment $payment) : ?Sale\PayableItem
	{
		foreach ($payment->getPayableItemCollection() as $payableItem)
		{
			if ($payableItem->getId() === 0)
			{
				return $payableItem;
			}
		}

		return null;
	}

	/**
	 * @param int $id
	 * @param int $rowId
	 * @param int $quantity
	 * @return bool
	 */
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
		if (!$payment || !$this->canEditPayment($payment))
		{
			return false;
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

		$payableItem->setField('QUANTITY', $quantity);

		$this->recalculatePaymentSum($payment);

		$result = $payment->getOrder()->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	private function canSetQuantity(Sale\PayableItem $payableItem, float $quantity) : bool
	{
		/** @var Crm\Order\BasketItem $basketItem */
		$basketItem = $payableItem->getEntityObject();

		/** @var Sale\PayableItemCollection $payableCollection */
		$payableCollection = $payableItem->getCollection();

		/** @var Sale\PaymentCollection $paymentCollection */
		$paymentCollection = $payableCollection->getPayment()->getCollection();

		$disturbQuantity = $paymentCollection->getBasketItemQuantity($basketItem);

		return ($disturbQuantity - $payableItem->getQuantity()) + $quantity <= $basketItem->getQuantity();
	}

	private function getFieldsForBuilder(array $product, Sale\Payment $payment, $quantity)
	{
		$basketCode = $product['BASKET_CODE'];

		return [
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
	}

	protected function getEntityType(): string
	{
		return Sale\Registry::ENTITY_BASKET_ITEM;
	}

}