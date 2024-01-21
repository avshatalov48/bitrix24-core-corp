<?php

namespace Bitrix\Crm\Controller\Item;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Sale;

final class Delivery extends Crm\Controller\Base
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
			$this->addError(new Main\Error(Loc::getMessage('CRM_CONTROLLER_ITEM_SHIPMENT_NO_PERMISSION')));

			return null;
		}

		$preparedSelect = $this->getFieldsOnSelect();
		$preparedFilter = $this->prepareFilterFields($filter);
		$preparedOrder = $this->prepareOrderFields($order);

		return $repository->getDeliveryDocumentsForEntityByFilter(
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
		$shipment = Sale\Repository\ShipmentRepository::getInstance()->getById($id);
		if (!$shipment || !$this->hasPermissionForShipment($shipment))
		{
			$this->addError(new Main\Error(Loc::getMessage('CRM_CONTROLLER_ITEM_SHIPMENT_NO_PERMISSION')));

			return null;
		}

		$shipmentItem = $shipment->toArray();

		$fields = $this->getFieldsOnSelect();

		return array_filter(
			$shipmentItem,
			static function ($key) use ($fields){
				return in_array($key, $fields, true);
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	private function hasPermissionForShipment(Sale\Shipment $shipment) : bool
	{
		/** @var Crm\Entity\PaymentDocumentsRepository $repository */
		$repository = Main\DI\ServiceLocator::getInstance()->get('crm.entity.paymentDocumentsRepository');

		return $repository->checkShipmentPermission($shipment);
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
}