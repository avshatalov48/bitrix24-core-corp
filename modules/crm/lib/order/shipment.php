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
 */
class Shipment extends Sale\Shipment
{
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

		if ($this->fields->isChanged('DEDUCTED'))
		{
			if ($this->getField('DEDUCTED') == "Y")
			{
				Crm\Automation\Trigger\DeductedTrigger::execute(
					[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getField('ORDER_ID')]],
					['SHIPMENT' => $this]
				);
			}

			if (!$isNew && !$this->isSystem() && !$this->getOrder()->isNew())
			{
				$timelineParams = [
					'FIELDS' => $this->getFieldValues(),
					'SETTINGS' => [
						'CHANGED_ENTITY' => \CCrmOwnerType::OrderShipmentName,
						'FIELDS' => [
							'ORDER_DEDUCTED' => $this->getField('DEDUCTED'),
							'ORDER_DONE' => 'N'
						],
					],
					'BINDINGS' => BindingsMaker\TimelineBindingsMaker::makeByShipment($this)
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
		if ($deleteResult->isSuccess() && (int)$this->getId() > 0)
		{
			Crm\Timeline\TimelineEntry::deleteByOwner(\CCrmOwnerType::OrderShipment, $this->getId());
		}

		return $deleteResult;
	}
}
