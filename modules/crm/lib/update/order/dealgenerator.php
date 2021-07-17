<?php

namespace Bitrix\Crm\Update\Order;

use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Crm;

Main\Localization\Loc::loadMessages(__FILE__);

final class DealGenerator extends Main\Update\Stepper
{
	private const MAX_ORDERS_FOR_STEP = 20;

	protected static $moduleId = 'crm';

	public function execute(array &$option)
	{
		if (empty($option))
		{
			$option["steps"] = 0;
			$option["count"] = self::getUnbindingActiveOrdersCount();
		}

		foreach (self::getUnbindingActiveOrders() as $order)
		{
			$order = Crm\Order\Order::load($order['ID']);

			$dealId = $this->createDeal($order);
			if ($dealId !== false)
			{
				$this->addProductToDeal($dealId, $order);

				$this->bindDealToOrder($dealId, $order);
				$this->fillPayableCollection($order);

				$order->save();
			}

			$option["steps"]++;
		}

		if (self::getUnbindingActiveOrdersCount() > 0)
		{
			return self::CONTINUE_EXECUTION;
		}

		return self::FINISH_EXECUTION;
	}

	protected function createDeal(Crm\Order\Order $order)
	{
		$deal = new \CCrmDeal(false);

		$fields = $this->extractDealFields($order);

		 return $deal->Add($fields, true, ['DISABLE_USER_FIELD_CHECK' => true]);
	}

	protected function addProductToDeal($dealId, Crm\Order\Order $order)
	{
		$productList = [];

		/** @var Crm\Order\BasketItem $basketItem */
		foreach ($order->getBasket() as $basketItem)
		{
			$item = [
				'PRODUCT_ID' => $basketItem->getProductId(),
				'PRODUCT_NAME' => $basketItem->getField('NAME'),
				'PRICE' => $basketItem->getBasePrice(),
				'PRICE_ACCOUNT' => $basketItem->getBasePrice(),
				'PRICE_EXCLUSIVE' => $basketItem->getBasePrice(),
				'PRICE_NETTO' => $basketItem->getBasePrice(),
				'PRICE_BRUTTO' => $basketItem->getBasePrice(),
				'QUANTITY' => $basketItem->getQuantity(),
				'MEASURE_CODE' => $basketItem->getField('MEASURE_CODE'),
				'MEASURE_NAME' => $basketItem->getField('MEASURE_NAME'),
				'TAX_RATE' => $basketItem->getVatRate(),
				'TAX_INCLUDED' => $basketItem->isVatInPrice() ? 'Y' : 'N',
			];

			if ($basketItem->getDiscountPrice())
			{
				$item['DISCOUNT_TYPE_ID'] = Crm\Discount::MONETARY;
				$item['DISCOUNT_SUM'] = $basketItem->getDiscountPrice();

				$item['PRICE'] -= $basketItem->getDiscountPrice();
				$item['PRICE_ACCOUNT'] -= $basketItem->getDiscountPrice();
				$item['PRICE_EXCLUSIVE'] -= $basketItem->getDiscountPrice();
			}

			$productList[] = $item;
		}

		\CCrmDeal::addProductRows($dealId, $productList);
	}

	protected function bindDealToOrder($dealId, Crm\Order\Order $order)
	{
		$binding = $order->createDealBinding();
		$binding->setField('DEAL_ID', $dealId);
	}

	protected function extractDealFields(Crm\Order\Order $order) : array
	{
		$result = [
			'TITLE' => Main\Localization\Loc::getMessage('CRM_UPDATE_ORDER_DEAL_TITLE', ['#ORDER_ID#' => $order->getId()])
		];

		$company = $order->getContactCompanyCollection()->getPrimaryCompany();
		if ($company)
		{
			$result['COMPANY_ID'] = $company->getField('ENTITY_ID');
		}

		$contact = $order->getContactCompanyCollection()->getPrimaryContact();
		if ($contact)
		{
			$result['CONTACT_ID'] = $contact->getField('ENTITY_ID');
		}

		$result['CONTACT_IDS'] = [];

		/** @var Crm\Order\Contact $contact */
		foreach ($order->getContactCompanyCollection()->getContacts() as $contact)
		{
			$result['CONTACT_IDS'][] = $contact->getField('ENTITY_ID');
		}

		return $result;
	}

	public static function getUnbindingActiveOrdersCount() : int
	{
		$query = new Main\Entity\Query(Sale\Internals\OrderTable::getEntity());

		$query->registerRuntimeField(
			'',
			new Main\Entity\ExpressionField(
				'CNT',
				'COUNT(*)'
			)
		);

		$query->registerRuntimeField('',
			new Main\Entity\ReferenceField(
				'DEAL_BINDING',
				Crm\Binding\OrderDealTable::getEntity(),
				[
					'=ref.ORDER_ID' => 'this.ID',
				],
				[
					'join_type' => 'LEFT'
				]
			)
		);

		$query
			->setSelect(['CNT'])
			->whereNotIn(
				'STATUS_ID',
				[
					Crm\Order\OrderStatus::getFinalStatus(),
					Crm\Order\OrderStatus::getFinalUnsuccessfulStatus()
				]
			)
			->whereNull('DEAL_BINDING.ORDER_ID')
		;

		$data = $query->fetch();
		if ($data)
		{
			return $data['CNT'];
		}

		return 0;
	}

	protected static function getUnbindingActiveOrders() : Main\ORM\Query\Result
	{
		$query = new Main\Entity\Query(Sale\Internals\OrderTable::getEntity());
		$query->registerRuntimeField('',
			new Main\Entity\ReferenceField(
				'DEAL_BINDING',
				Crm\Binding\OrderDealTable::getEntity(),
				[
					'=ref.ORDER_ID' => 'this.ID',
				],
				[
					'join_type' => 'LEFT'
				]
			)
		);
		$query
			->setSelect(['ID'])
			->whereNotIn(
				'STATUS_ID',
				[
					Crm\Order\OrderStatus::getFinalStatus(),
					Crm\Order\OrderStatus::getFinalUnsuccessfulStatus()
				]
			)
			->whereNull('DEAL_BINDING.ORDER_ID')
			->setLimit(self::MAX_ORDERS_FOR_STEP)
		;

		return $query->exec();
	}

	protected function fillPayableCollection(Crm\Order\Order $order) : bool
	{
		$paymentCollection = $order->getPaymentCollection();
		if (
			$paymentCollection->count() === 0
			|| $paymentCollection->count() > 1
		)
		{
			return false;
		}

		/** @var Crm\Order\Payment $payment */
		$payment = $paymentCollection->current();
		if ($payment)
		{
			$payableCollection = $payment->getPayableItemCollection();

			/** @var Crm\Order\BasketItem $item */
			foreach ($order->getBasket() as $item)
			{
				$payableItem = $payableCollection->createItemByBasketItem($item);
				$payableItem->setField('QUANTITY', $item->getQuantity());
			}

			/** @var Crm\Order\Shipment $item */
			foreach ($order->getShipmentCollection()->getNotSystemItems() as $item)
			{
				$payableItem = $payableCollection->createItemByShipment($item);
				$payableItem->setField('QUANTITY', 1);
			}

			return true;
		}

		return false;
	}

	public static function getTitle()
	{
		return Main\Localization\Loc::getMessage('CRM_UPDATE_ORDER_DEAL_GENERATOR');
	}

}