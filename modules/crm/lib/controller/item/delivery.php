<?php

namespace Bitrix\Crm\Controller\Item;

use Bitrix\Crm\Rest\TypeCast\OrmTypeCast;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Sale;

final class Delivery extends Crm\Controller\Base
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

		$result = $repository->getDeliveryDocumentsForEntityByFilter(
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

	public function getAction(int $id): ?array
	{
		$shipment = Sale\Repository\ShipmentRepository::getInstance()->getById($id);
		if (!$shipment)
		{
			$this->addError(new Main\Error('Delivery has not been found'));

			return null;
		}

		$hasPermission = Container::getInstance()->getUserPermissions()->checkReadPermissions(
			\CCrmOwnerType::Order,
			$shipment->getOrder()->getId()
		);
		if (!$hasPermission)
		{
			$this->setAccessDenied();

			return null;
		}

		$shipmentItem = $shipment->toArray();

		$fields = $this->getFieldsOnSelect();

		$filteredFields = array_filter(
			$shipmentItem,
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
				Sale\Internals\ShipmentTable::class,
				$fields
			);
	}

	private function getFieldsOnSelect() : array
	{
		return [
			'ID',
			'ACCOUNT_NUMBER',
			'DEDUCTED',
			'DATE_DEDUCTED',
			'DELIVERY_ID',
			'PRICE_DELIVERY',
			'CURRENCY',
			'DELIVERY_NAME',
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
		return ['sale'];
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
