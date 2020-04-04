<?php

namespace Bitrix\Crm\Order;

use Bitrix\Crm;
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
	 * @throws Main\ArgumentException
	 */
	protected function onAfterSave($isNew)
	{
		if (!$this->isSystem())
		{
			if ($isNew)
			{
				$this->addTimelineEntryOnCreate();
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

		if ($this->fields->isChanged('ALLOW_DELIVERY') && $this->isAllowDelivery())
		{
			Crm\Automation\Trigger\AllowDeliveryTrigger::execute(
				[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getField('ORDER_ID')]],
				['SHIPMENT' => $this]
			);
		}

		if ($this->fields->isChanged('DEDUCTED') && $this->getField('DEDUCTED') == "Y")
		{
			Crm\Automation\Trigger\DeductedTrigger::execute(
				[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getField('ORDER_ID')]],
				['SHIPMENT' => $this]
			);
		}

		if(Main\Loader::includeModule('pull'))
		{
			\CPullWatch::AddToStack(
				'CRM_ENTITY_ORDER_SHIPMENT',
				array(
					'module_id' => 'crm',
					'command' => 'onOrderShipmentSave',
					'params' => array(
						'FIELDS' => $this->getFieldValues()
					)
				)
			);
		}
	}

	/**
	 * @throws Main\ArgumentException
	 * @return void;
	 */
	private function addTimelineEntryOnCreate()
	{
		Crm\Timeline\OrderShipmentController::getInstance()->onCreate(
			$this->getId(),
			array('FIELDS' => $this->getFields()->getValues())
		);
	}

	/**
	 * @throws Main\ArgumentException
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
	 * @throws Main\ArgumentException
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
}