<?php

namespace Bitrix\Crm\Controller\Item;

use Bitrix\Crm\Rest\TypeCast\OrmTypeCast;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\Order\EventsHandler;
use Bitrix\Sale;
use Bitrix\Salescenter;
use Bitrix\Sale\PaySystem\Manager;

final class Payment extends Crm\Controller\Base
{
	private bool $previousSynchronizationStatus = true;

	public function listAction(
		int $entityId,
		int $entityTypeId,
		array $filter = [],
		array $order = []
	): ?array
	{
		/** @var Crm\Entity\PaymentDocumentsRepository $repository */
		$repository = Main\DI\ServiceLocator::getInstance()->get('crm.entity.paymentDocumentsRepository');

		$hasEntityPermission = Container::getInstance()->getUserPermissions()->checkReadPermissions(
			$entityTypeId,
			$entityId
		);
		if (!$hasEntityPermission)
		{
			$this->setAccessDenied();

			return null;
		}

		$preparedSelect = $this->getFieldsOnSelect();
		$preparedFilter = $this->prepareFilterFields($filter);
		$preparedOrder = $this->prepareOrderFields($order);

		$result = $repository->getPaymentDocumentsForEntityByFilter(
			$entityId,
			$entityTypeId,
			$preparedFilter,
			$preparedSelect,
			$preparedOrder
		);

		foreach ($result as $index => $resultItem)
		{
			$result[$index] = $this->castSelectFields($resultItem);
		}

		return $this->convertKeysToCamelCase($result);
	}

	/**
	 * @param int $id
	 * @return array|null
	 */
	public function getAction(int $id): ?array
	{
		$payment = Sale\Repository\PaymentRepository::getInstance()->getById($id);
		if (!$payment)
		{
			$this->addError(new Main\Error('Payment has not been found'));

			return null;
		}

		$hasPermission = Container::getInstance()->getUserPermissions()->checkReadPermissions(
			\CCrmOwnerType::Order,
			$payment->getOrderId()
		);
		if (!$hasPermission)
		{
			$this->setAccessDenied();

			return null;
		}

		$paymentItem = $payment->toArray();

		$fields = $this->getFieldsOnSelect();

		$filteredFields = array_filter(
			$paymentItem,
			static function ($key) use ($fields){
				return in_array($key, $fields, true);
			},
			ARRAY_FILTER_USE_KEY
		);

		return $this->convertKeysToCamelCase(
			$this->castSelectFields($filteredFields)
		);
	}

	private function castSelectFields(array $fields): array
	{
		return OrmTypeCast::getInstance()
			->setBoolCaster(new Crm\Dto\Caster\BoolYNCaster())
			->castRecord(
				Sale\Internals\PaymentTable::class,
				$fields
			);
	}

	public function addAction(int $entityId, int $entityTypeId, array $fields = []): ?int
	{
		if (!$this->canAddPayment($entityId, $entityTypeId))
		{
			$this->setAccessDenied();

			return null;
		}

		$builder = SalesCenter\Builder\Manager::getBuilder(
			Sale\Helpers\Order\Builder\SettingsContainer::BUILDER_SCENARIO_PAYMENT
		);

		if ($fields)
		{
			$fields = $this->convertKeysToUpper($fields);
		}

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
			$this->addError(new Main\Error('Payment not found'));

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

	public function updateAction(int $id, array $fields) : ?bool
	{
		$availableFields = $this->getFieldsOnUpdate();

		$fields = $this->convertKeysToUpper($fields);
		$fields = array_filter(
			$fields,
			static function($key) use ($availableFields) {
				return in_array($key, $availableFields, true);
			},
			ARRAY_FILTER_USE_KEY
		);

		if (!$fields)
		{
			$this->addError(new Main\Error('Empty fields'));

			return null;
		}

		return $this->updateInternal($id, $fields);
	}

	protected function updateInternal(int $id, array $fields) : ?bool
	{
		$payment = Sale\Repository\PaymentRepository::getInstance()->getById($id);

		if (!$payment)
		{
			$this->addError(new Main\Error('Payment has not been found'));

			return null;
		}

		$hasPermission = Container::getInstance()->getUserPermissions()->checkUpdatePermissions(
			\CCrmOwnerType::Order,
			$payment->getOrderId()
		);
		if (!$hasPermission)
		{
			$this->setAccessDenied();

			return null;
		}

		if (isset($fields['PAY_SYSTEM_ID']))
		{
			$paymentSystem = Manager::getObjectById((int)$fields['PAY_SYSTEM_ID']);
			if (!$paymentSystem)
			{
				$this->addError(new Main\Error('Payment system has not been found'));

				return null;
			}

			$fields['PAY_SYSTEM_NAME'] = $paymentSystem->getField('NAME');
		}

		$result = $payment->setFields($fields);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$result = $payment->getOrder()->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	public function deleteAction(int $id): ?bool
	{
		$payment = Sale\Repository\PaymentRepository::getInstance()->getById($id);

		if (!$payment)
		{
			$this->addError(new Main\Error('Payment has not been found'));

			return null;
		}

		$hasPermission = Container::getInstance()->getUserPermissions()->checkUpdatePermissions(
			\CCrmOwnerType::Order,
			$payment->getOrderId()
		);
		if (!$hasPermission)
		{
			$this->setAccessDenied();

			return null;
		}

		$result = $payment->delete();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$result = $payment->getOrder()->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	public function payAction(int $id): ?bool
	{
		return $this->updateInternal($id, ['PAID' => 'Y']);
	}

	public function unpayAction(int $id): ?bool
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

		$hasPermission = Container::getInstance()->getUserPermissions()->checkUpdatePermissions(
			$entityTypeId,
			$entityId
		);
		if (!$hasPermission)
		{
			$this->setAccessDenied();

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
				'CLIENT' => Salescenter\Integration\CrmManager::getInstance()->getClientInfo($entityTypeId, $entityId),
				'TRADING_PLATFORM' => $this->getTradePlatformId($entityTypeId),
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

	private function getTradePlatformId(int $entityTypeId) : ?int
	{
		$platform = Crm\Order\TradingPlatform\DynamicEntity::getInstanceByCode(
			Crm\Order\TradingPlatform\DynamicEntity::getCodeByEntityTypeId($entityTypeId)
		);

		if ($platform)
		{
			if (!$platform->isInstalled())
			{
				$platform->install();
			}

			if ($platform->isInstalled())
			{
				return $platform->getId();
			}
		}

		return null;
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
