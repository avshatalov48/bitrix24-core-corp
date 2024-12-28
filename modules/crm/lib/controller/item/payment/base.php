<?php

namespace Bitrix\Crm\Controller\Item\Payment;

use Bitrix\Crm;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Main;

abstract class Base extends Crm\Controller\Base
{
	private bool $previousSynchronizationStatus = true;

	abstract protected function getEntityType() : string;

	protected function recalculatePaymentSum(Crm\Order\Payment $payment) : void
	{
		$sum = 0;

		/** @var Sale\PayableItem $item */
		foreach ($payment->getPayableItemCollection() as $item)
		{
			$sum += $item->getQuantity() * $item->getPrice();
		}

		$payment->setField('SUM', $sum);
	}

	public function listAction(
		int $paymentId,
		array $filter = [],
		array $order = []
	): ?array
	{
		$payment = $this->getPaymentById($paymentId);
		if (!$payment)
		{
			$this->addError(new Main\Error('Payment has not been found'));

			return null;
		}

		if (!$this->canViewPayment($payment))
		{
			$this->setAccessDenied();

			return null;
		}

		$select = $this->getSelectFields();

		$preparedFilter = $this->prepareFilterFields($filter);
		$preparedFilter = $this->addNecessaryFilters($preparedFilter, $paymentId);

		$preparedOrder = $this->prepareOrderFields($order);

		$dbRes = Crm\Order\PayableItemCollection::getList(
			[
				'select' => $select,
				'filter' => $preparedFilter,
				'order' => $preparedOrder,
			]
		);

		$typeCaster = Crm\Rest\TypeCast\OrmTypeCast::getInstance();

		return array_map(
			function ($fields) use ($typeCaster)
			{
				return $typeCaster->castRecord(Sale\Internals\PayableItemTable::class, $fields);
			},
			$dbRes->fetchAll()
		);
	}

	/**
	 * @param int $id - ID from b_sale_order_payment_item
	 * @return ?bool
	 */
	public function deleteAction(int $id): ?bool
	{
		$payment = $this->getPaymentByPayableId($id);
		if (!$payment)
		{
			$this->addError(new Main\Error('Payable item has not been found'));

			return null;
		}

		if (!$this->canEditPayment($payment))
		{
			$this->setAccessDenied();

			return null;
		}

		$payableItem = $payment->getPayableItemCollection()->getItemById($id);

		/** @var Sale\PayableItem $payableItem */
		$result = $payableItem->delete();
		if (!$result->isSuccess())
		{
			$this->addError(new Main\Error('Internal delete error'));

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

	protected function getPaymentByPayableId(int $id): ?Crm\Order\Payment
	{
		$item = Crm\Order\PayableItemCollection::getList([
			'select' => ['PAYMENT_ID'],
			'filter' => [
				'=ID' => $id,
				'=ENTITY_TYPE' => $this->getEntityType()
			]
		])->fetch();

		if ($item)
		{
			return $this->getPaymentById($item['PAYMENT_ID']);
		}

		return null;
	}

	protected function getPaymentById($paymentId) : ?Crm\Order\Payment
	{
		/** @var Crm\Order\Payment $payment */
		$payment = Sale\Repository\PaymentRepository::getInstance()->getById($paymentId);

		return $payment;
	}

	protected function canEditPayment(Crm\Order\Payment $payment) : bool
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();

		return
			!$payment->isPaid()
			&& Crm\Order\Permissions\Payment::checkUpdatePermission($payment->getId(), $userPermissions)
		;
	}

	protected function canViewPayment(Crm\Order\Payment $payment) : bool
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();

		return
			Crm\Order\Permissions\Payment::checkReadPermission($payment->getId(), $userPermissions)
		;
	}

	private function getSelectFields() : array
	{
		return [
			'ID',
			'PAYMENT_ID',
			'QUANTITY',
			'ENTITY_ID',
		];
	}

	protected function prepareFilterFields(array $filter): array
	{
		if (!$filter)
		{
			return [];
		}

		return $this->convertKeysToUpper($filter);
	}

	private function addNecessaryFilters(array $filter, int $paymentId) : array
	{
		$filter['=PAYMENT_ID'] = $paymentId;
		$filter['=ENTITY_TYPE'] = $this->getEntityType();

		return $filter;
	}

	private function prepareOrderFields(array $order): array
	{
		if (!$order)
		{
			return ['ID' => 'ASC'];
		}

		return $this->convertKeysToUpper($order);
	}

	protected function getRequiredModules() : array
	{
		return ['salescenter', 'sale'];
	}

	protected function processBeforeAction(Main\Engine\Action $action)
	{
		$result = parent::processBeforeAction($action);

		$this->disableEntitySynchronization();

		return $result;
	}

	protected function processAfterAction(Main\Engine\Action $action, $result)
	{
		parent::processAfterAction($action, $result);

		$this->enableEntitySynchronization();
	}

	private function enableEntitySynchronization() : void
	{
		Crm\Order\Configuration::setEnabledEntitySynchronization($this->previousSynchronizationStatus);
	}

	private function disableEntitySynchronization() : void
	{
		$this->previousSynchronizationStatus = Crm\Order\Configuration::isEnabledEntitySynchronization();

		Crm\Order\Configuration::setEnabledEntitySynchronization(false);
	}
}
