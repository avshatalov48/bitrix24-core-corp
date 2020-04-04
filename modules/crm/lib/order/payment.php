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
 * Class Payment
 * @package Bitrix\Crm\Order
 */
class Payment extends Sale\Payment
{
	/**
	 * @param $isNew
	 * @throws Main\ArgumentException
	 */
	protected function onAfterSave($isNew)
	{
		if ($isNew)
		{
			$this->addTimelineEntryOnCreate();
		}
		elseif ($this->fields->isChanged('SUM') || $this->fields->isChanged('CURRENCY') )
		{
			$this->updateTimelineCreationEntity();
		}

		if ($this->fields->isChanged('PAID') && $this->isPaid())
		{
			Crm\Automation\Trigger\PaymentTrigger::execute(
				[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getOrderId()]],
				['PAYMENT' => $this]
			);
		}

		if(Main\Loader::includeModule('pull'))
		{
			\CPullWatch::AddToStack(
				'CRM_ENTITY_ORDER_PAYMENT',
				array(
					'module_id' => 'crm',
					'command' => 'onOrderPaymentSave',
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
		Crm\Timeline\OrderPaymentController::getInstance()->onCreate(
			$this->getId(),
			array('FIELDS' => $this->getFields()->getValues())
		);
	}

	/**
	 * @throws Main\ArgumentException
	 * @return void;
	 */
	private function updateTimelineCreationEntity()
	{
		$fields = $this->getFields();
		$selectedFields =[
			'DATE_BILL_TIMESTAMP' => $fields['DATE_BILL']->getTimestamp(),
			'SUM' => $fields['SUM'],
			'CURRENCY' => $fields['CURRENCY']
		];

		Crm\Timeline\OrderPaymentController::getInstance()->updateSettingFields(
			$this->getId(),
			Crm\Timeline\TimelineType::CREATION,
			$selectedFields
		);
	}
}