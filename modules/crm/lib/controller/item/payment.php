<?php

namespace Bitrix\Crm\Controller\Item;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\Order\EventsHandler;
use Bitrix\Sale;
use Bitrix\Salescenter;

final class Payment extends Crm\Controller\Base
{
	/**
	 * @param int $entityId
	 * @param int $entityTypeId
	 * @param array $filter
	 * @param array $order
	 * @return array|null
	 */
	public function listAction(
		int $entityId,
		int $entityTypeId,
		array $filter = [],
		array $order = []
	): ?array
	{
		/** @var Crm\Entity\PaymentDocumentsRepository $repository */
		$repository = Main\DI\ServiceLocator::getInstance()->get('crm.entity.paymentDocumentsRepository');

		if (!$repository->checkPermission($entityTypeId, $entityId))
		{
			$this->addError(new Main\Error(Loc::getMessage('CRM_CONTROLLER_ITEM_PAYMENT_NO_PERMISSION')));

			return null;
		}

		$preparedSelect = $this->getFieldsOnSelect();
		$preparedFilter = $this->prepareFilterFields($filter);
		$preparedOrder = $this->prepareOrderFields($order);

		return $repository->getPaymentDocumentsForEntityByFilter(
			$entityId,
			$entityTypeId,
			$preparedFilter,
			$preparedSelect,
			$preparedOrder
		);
	}

	/**
	 * @param int $id
	 * @return array|null
	 */
	public function getAction(int $id): ?array
	{
		$payment = Sale\Repository\PaymentRepository::getInstance()->getById($id);
		if (!$payment || !$this->hasPermissionForPayment($payment))
		{
			$this->addError(new Main\Error(Loc::getMessage('CRM_CONTROLLER_ITEM_PAYMENT_NO_PERMISSION')));

			return null;
		}

		$paymentItem = $payment->toArray();

		$fields = $this->getFieldsOnSelect();

		return array_filter(
			$paymentItem,
			static function ($key) use ($fields){
				return in_array($key, $fields, true);
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	public function addAction(int $entityId, int $entityTypeId, array $fields = []): ?int
	{
		if (!$this->canAddPayment($entityId, $entityTypeId))
		{
			return null;
		}

		$builder = SalesCenter\Builder\Manager::getBuilder(
			Sale\Helpers\Order\Builder\SettingsContainer::BUILDER_SCENARIO_PAYMENT
		);

		$builderFields = $this->getFieldsOnAddForBuilder($entityId, $entityTypeId, $fields);

		$order = $builder->build($builderFields)->getOrder();
		if ($order === null)
		{
			$this->addErrors($builder->getErrorsContainer()->getErrors());

			return null;
		}

		$payment = $this->getNewPayment($order);
		if (!$payment)
		{
			return null;
		}

		$needEnableAutofillInPayment = false;

		if ($this->isAutofillInPaymentEnabled())
		{
			$this->disableAutofillInPayment();

			$needEnableAutofillInPayment = true;
		}

		$result = $order->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		if ($needEnableAutofillInPayment)
		{
			$this->enableAutofillInPayment();
		}

		return $payment->getId();
	}

	public function updateAction(int $id, array $fields) : bool
	{
		$availableFields = $this->getFieldsOnUpdate();

		$fields = $this->convertValuesToUpper($fields);
		$fields = array_filter(
			$fields,
			static function($key) use ($availableFields) {
				return in_array($key, $availableFields, true);
			},
			ARRAY_FILTER_USE_KEY
		);

		if (!$fields)
		{
			return false;
		}

		return $this->updateInternal($id, $fields);
	}

	protected function updateInternal(int $id, array $fields)
	{
		$payment = Sale\Repository\PaymentRepository::getInstance()->getById($id);

		if (!$payment || !$this->hasPermissionForPayment($payment))
		{
			$this->addError(new Main\Error(Loc::getMessage('CRM_CONTROLLER_ITEM_PAYMENT_NO_PERMISSION')));

			return false;
		}

		$payment = Sale\Repository\PaymentRepository::getInstance()->getById($id);

		$result = $payment->setFields($fields);
		if ($result->isSuccess())
		{
			$result = $payment->getOrder()->save();
			if ($result->isSuccess())
			{
				return true;
			}
		}

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return false;
	}

	/**
	 * @param int $id
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectNotFoundException
	 */
	public function deleteAction(int $id): bool
	{
		$payment = Sale\Repository\PaymentRepository::getInstance()->getById($id);

		if (!$payment || !$this->hasPermissionForPayment($payment))
		{
			$this->addError(new Main\Error(Loc::getMessage('CRM_CONTROLLER_ITEM_PAYMENT_NO_PERMISSION')));

			return false;
		}

		$result = $payment->delete();
		if ($result->isSuccess())
		{
			$result = $payment->getOrder()->save();
			if ($result->isSuccess())
			{
				return true;
			}
		}

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return false;
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public function payAction(int $id): bool
	{
		return $this->updateInternal($id, ['PAID' => 'Y']);
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public function unpayAction(int $id): bool
	{
		return $this->updateInternal($id, ['PAID' => 'N']);
	}

	private function canAddPayment(int $entityId, int $entityTypeId)
	{
		if (SalesCenter\Integration\Bitrix24Manager::getInstance()->isPaymentsLimitReached())
		{
			$this->addError(
				new Main\Error(Loc::getMessage('CRM_CONTROLLER_ITEM_PAYMENT_LIMIT_REACHED'))
			);

			return false;
		}

		$repository = Main\DI\ServiceLocator::getInstance()->get('crm.entity.paymentDocumentsRepository');

		if (!$repository->checkPermission($entityTypeId, $entityId))
		{
			$this->addError(new Main\Error(Loc::getMessage('CRM_CONTROLLER_ITEM_PAYMENT_NO_PERMISSION')));

			return false;
		}

		$factory = Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			$this->addError(new Main\Error(
					Loc::getMessage('CRM_CONTROLLER_ITEM_PAYMENT_UNKNOWN_ENTITY_TYPE_ID', ['#ENTITY_TYPE_ID#' => $entityTypeId]))
			);

			return false;
		}

		if (!$factory->isPaymentsEnabled())
		{
			$this->addError(new Main\Error(Loc::getMessage('CRM_CONTROLLER_ITEM_PAYMENT_DISABLED')));

			return false;
		}

		$item = $factory->getItem($entityId);
		if ($item === null)
		{
			$this->addError(new Main\Error(
				Loc::getMessage('CRM_CONTROLLER_ITEM_PAYMENT_ENTITY_NOT_FOUND', ['#ENTITY_ID#' => $entityId]))
			);

			return false;
		}

		return true;
	}

	private function hasPermissionForPayment(Sale\Payment $payment) : bool
	{
		/** @var Crm\Entity\PaymentDocumentsRepository $repository */
		$repository = Main\DI\ServiceLocator::getInstance()->get('crm.entity.paymentDocumentsRepository');

		return $repository->checkPaymentPermission($payment);
	}

	private function getFieldsOnAddForBuilder(int $entityId, int $entityTypeId, array $fields) : array
	{
		$paymentInfo = [
			'SUM' => 0,
		];

		if ($fields)
		{
			foreach ($this->getFieldsOnAdd() as $fieldName)
			{
				if (!empty($fields[$fieldName]))
				{
					$paymentInfo[$fieldName] = $fields[$fieldName];
				}
			}
		}

		$result = [
			'PAYMENT' => [$paymentInfo]
		];

		$orderIds = Crm\Binding\OrderEntityTable::getOrderIdsByOwner($entityId, $entityTypeId);
		if (empty($orderIds))
		{
			$item = Crm\Service\Container::getInstance()->getFactory($entityTypeId)->getItem($entityId);

			$result += [
				'OWNER_ID' => $entityId,
				'OWNER_TYPE_ID' => $entityTypeId,
				'CURRENCY' => \CCurrencyLang::GetFormatDescription($item->getCurrencyId())['CURRENCY'],
			];
		}
		else
		{
			$result += [
				'ID' => current($orderIds)
			];
		}

		return $result;
	}

	private function getNewPayment(Sale\Order $order) :? Sale\Payment
	{
		/** @var Crm\Order\Payment $payment */
		foreach ($order->getPaymentCollection() as $payment)
		{
			if ($payment->getId() === 0)
			{
				return $payment;
			}
		}

		return null;
	}

	private function getFieldsOnSelect() : array
	{
		return [
			'ID',
			'ACCOUNT_NUMBER',
			'PAID',
			'DATE_PAID',
			'EMP_PAID_ID',
			'PAY_SYSTEM_ID',
			'SUM',
			'CURRENCY',
			'PAY_SYSTEM_NAME',
		];
	}

	private function getFieldsOnUpdate() : array
	{
		return [
			'PAID',
			'PAY_SYSTEM_ID',
		];
	}

	private function getFieldsOnAdd() : array
	{
		return [
			'PAY_SYSTEM_ID',
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

	protected function prepareOrderFields(array $order): array
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

	private function isAutofillInPaymentEnabled() : bool
	{
		return EventsHandler\Payment::isExecutionEnabled() === true;
	}

	private function disableAutofillInPayment() : void
	{
		EventsHandler\Payment::disableExecution();
	}

	private function enableAutofillInPayment() : void
	{
		EventsHandler\Payment::enableExecution();
	}
}