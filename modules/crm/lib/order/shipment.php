<?php

namespace Bitrix\Crm\Order;

use Bitrix\Crm;
use Bitrix\Crm\Activity\Provider\Delivery;
use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Shipment
 * @package Bitrix\Crm\Order
 * @method Order getOrder()
 */
class Shipment extends Sale\Shipment
{
	public static function create(Sale\ShipmentCollection $collection, Sale\Delivery\Services\Base $service = null)
	{
		/** \Bitrix\Crm\Order\Shipment $shipment */
		$shipment = parent::create($collection, $service);

		if (Sale\Configuration::useStoreControl())
		{
			$shipment->initField('IS_REALIZATION', 'Y');
		}

		return $shipment;
	}

	public static function getAvailableFields()
	{
		$fields = parent::getAvailableFields();
		$fields[] = 'IS_REALIZATION';

		return $fields;
	}

	/**
	 * @return array
	 */
	protected static function getFieldsMap()
	{
		$fieldsMap = Sale\Internals\ShipmentTable::getMap();
		$fieldsMap['IS_REALIZATION'] = [
			'data_type' => Crm\Order\Internals\ShipmentRealizationTable::class,
			'reference' => [
				'=this.ID' => 'ref.SHIPMENT_ID',
			]
		];

		return $fieldsMap;
	}

	protected function onFieldModify($name, $oldValue, $value)
	{
		if ($name === 'DEDUCTED')
		{
			if (
				$value === 'Y'
				&& $this->getField('IS_REALIZATION') === 'N'
				&& Sale\Configuration::useStoreControl()
			)
			{
				$this->setField('IS_REALIZATION', 'Y');
			}
		}

		return parent::onFieldModify($name, $oldValue, $value);
	}

	protected function updateInternal($id, array $data)
	{
		$isRealization = null;
		if (isset($data['IS_REALIZATION']))
		{
			$isRealization = $data['IS_REALIZATION'];
			unset($data['IS_REALIZATION']);
		}

		$result = parent::updateInternal($id, $data);
		if ($isRealization && $result->isSuccess())
		{
			$this->updateRealization($result->getId(), $isRealization);
		}

		return $result;
	}

	protected function addInternal(array $data)
	{
		$isRealization = null;
		if (isset($data['IS_REALIZATION']))
		{
			$isRealization = $data['IS_REALIZATION'];
			unset($data['IS_REALIZATION']);
		}

		$result = parent::addInternal($data);
		if ($isRealization && $result->isSuccess())
		{
			$this->addRealization($result->getId(), $isRealization);
		}

		return $result;
	}

	protected static function getParametersForLoad($id): array
	{
		$parameters = parent::getParametersForLoad($id);

		if (!isset($parameters['select']))
		{
			$parameters['select'] = ['*'];
		}

		$parameters['select']['IS_REALIZATION'] = 'SHIPMENT_REALIZATION.IS_REALIZATION';

		if (!isset($parameters['runtime']))
		{
			$parameters['runtime'] = [];
		}

		$parameters['runtime'][] = new Main\Entity\ReferenceField(
			'SHIPMENT_REALIZATION',
			Crm\Order\Internals\ShipmentRealizationTable::class,
			[
				'=this.ID' => 'ref.SHIPMENT_ID',
			],
			'left_join'
		);

		return $parameters;
	}

	/**
	 * @param $isNew
	 */
	protected function onAfterSave($isNew)
	{
		if (!$this->isSystem())
		{
			if ($isNew)
			{
				$this->addTimelineEntryOnCreate();

				$deliveryService = $this->getDelivery();
				$deliveryRequestHandler = $deliveryService ? $deliveryService->getDeliveryRequestHandler() : null;
				if ($deliveryRequestHandler && $deliveryRequestHandler->hasCallbackTrackingSupport())
				{
					Delivery::addActivity($this);
				}
			}
			else
			{
				if ($this->fields->isChanged('STATUS_ID'))
				{
					$this->addTimelineEntryOnStatusModify();
				}

				if ($this->fields->isChanged('PRICE_DELIVERY') || $this->fields->isChanged('CURRENCY') )
				{
					$this->updateTimelineCreationEntity();
				}
			}
		}

		$automationAvailable = Crm\Automation\Factory::isAutomationAvailable(\CCrmOwnerType::Order);

		if ($automationAvailable)
		{
			if (!$this->isSystem() && !$isNew && $this->isChanged())
			{
				Crm\Automation\Trigger\ShipmentChangedTrigger::execute(
					[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getField('ORDER_ID')]],
					['SHIPMENT' => $this]
				);
			}

			if ($this->fields->isChanged('ALLOW_DELIVERY') && $this->isAllowDelivery())
			{
				Crm\Automation\Trigger\AllowDeliveryTrigger::execute(
					[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getField('ORDER_ID')]],
					['SHIPMENT' => $this]
				);
			}

			if (
				$this->fields->isChanged('STATUS_ID')
				&& $this->getField('STATUS_ID') === DeliveryStatus::getFinalStatus()
			)
			{
				Crm\Automation\Trigger\ShipmentChangedTrigger::execute(
					[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getField('ORDER_ID')]],
					['SHIPMENT' => $this]
				);
			}

			if ($this->fields->isChanged('TRACKING_NUMBER') && !empty($this->getField('TRACKING_NUMBER')))
			{
				Crm\Automation\Trigger\FillTrackingNumberTrigger::execute(
					[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getField('ORDER_ID')]],
					['SHIPMENT' => $this]
				);
			}
		}

		if ($this->fields->isChanged('DEDUCTED'))
		{
			if ($automationAvailable && $this->getField('DEDUCTED') == "Y")
			{
				Crm\Automation\Trigger\DeductedTrigger::execute(
					[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getField('ORDER_ID')]],
					['SHIPMENT' => $this]
				);
			}

			if (!$isNew && !$this->isSystem() && !$this->getOrder()->isNew())
			{
				$timelineBindingsOptions = [];
				if (\Bitrix\Main\Config\Option::get('catalog', 'default_use_store_control', 'N') === 'Y')
				{
					$timelineBindingsOptions['withDeal'] = false;
				}

				$timelineParams = [
					'FIELDS' => $this->getFieldValues(),
					'SETTINGS' => [
						'CHANGED_ENTITY' => \CCrmOwnerType::OrderShipmentName,
						'FIELDS' => [
							'ORDER_DEDUCTED' => $this->getField('DEDUCTED'),
							'ORDER_DONE' => 'N'
						],
					],
					'BINDINGS' => BindingsMaker\TimelineBindingsMaker::makeByShipment($this, $timelineBindingsOptions)
				];

				Crm\Timeline\OrderShipmentController::getInstance()->onDeducted($this->getId(), $timelineParams);
			}
		}

		if(Main\Loader::includeModule('pull'))
		{
			\CPullWatch::AddToStack(
				'CRM_ENTITY_ORDER_SHIPMENT',
				[
					'module_id' => 'crm',
					'command' => 'onOrderShipmentSave',
					'params' => [
						'FIELDS' => $this->getFieldValues()
					]
				]
			);
		}

		parent::onAfterSave($isNew);
	}

	/**
	 * @return void;
	 */
	private function addTimelineEntryOnCreate()
	{
		$fields = $this->getFields()->getValues();
		$createdBy = $this->getOrder()->getField('CREATED_BY');
		if ($createdBy)
		{
			$fields['ORDER_CREATED_BY'] = $createdBy;
		}

		Crm\Timeline\OrderShipmentController::getInstance()->onCreate(
			$this->getId(),
			array('FIELDS' => $fields)
		);
	}

	/**
	 * @return void;
	 */
	private function addTimelineEntryOnStatusModify()
	{
		global $USER;

		$fields = $this->getFields();
		$originalValues  = $fields->getOriginalValues();

		$modifyParams = array(
			'PREVIOUS_FIELDS' => array('STATUS_ID' => $originalValues['STATUS_ID']),
			'CURRENT_FIELDS' => [
				'STATUS_ID' => $this->getField('STATUS_ID'),
				'MODIFY_BY' => (is_object($USER)) ? intval($USER->GetID()) : $fields['RESPONSIBLE_ID']
			],
			'ORDER_ID' => $fields['ORDER_ID']
		);

		Crm\Timeline\OrderShipmentController::getInstance()->onModify($this->getId(), $modifyParams);
	}

	/**
	 * @return void;
	 */
	private function updateTimelineCreationEntity()
	{
		$fields = $this->getFields();
		$selectedFields =[
			'DATE_INSERT_TIMESTAMP' => $fields['DATE_INSERT']->getTimestamp(),
			'PRICE_DELIVERY' => $fields['PRICE_DELIVERY'],
			'CURRENCY' => $fields['CURRENCY']
		];

		Crm\Timeline\OrderShipmentController::getInstance()->updateSettingFields(
			$this->getId(),
			Crm\Timeline\TimelineType::CREATION,
			$selectedFields
		);
	}

	public function delete()
	{
		$deleteResult = parent::delete();
		if ($deleteResult->isSuccess() && $this->getId() > 0)
		{
			$this->deleteRealization($this->getId());
			Crm\Timeline\TimelineEntry::deleteByOwner(\CCrmOwnerType::OrderShipment, $this->getId());
			Delivery::onShipmentDeleted($this->getId());
		}

		return $deleteResult;
	}

	private function addRealization(int $id, $isRealization): void
	{
		Crm\Order\Internals\ShipmentRealizationTable::add([
			'SHIPMENT_ID' => $id,
			'IS_REALIZATION' => $isRealization,
		]);
	}

	private function updateRealization(int $id, $isRealization): void
	{
		$shipmentRealization = Crm\Order\Internals\ShipmentRealizationTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=SHIPMENT_ID' => $id,
			],
			'limit' => 1,
		])->fetch();
		if ($shipmentRealization)
		{
			Crm\Order\Internals\ShipmentRealizationTable::update($shipmentRealization['ID'], [
				'IS_REALIZATION' => $isRealization,
			]);
		}
	}

	private function deleteRealization(int $id): void
	{
		$shipmentRealization = Crm\Order\Internals\ShipmentRealizationTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=SHIPMENT_ID' => $id,
			],
			'limit' => 1,
		])->fetch();
		if ($shipmentRealization)
		{
			Crm\Order\Internals\ShipmentRealizationTable::delete($shipmentRealization['ID']);
		}
	}
}
