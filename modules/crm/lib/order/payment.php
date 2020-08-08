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
	private const STATUS_CODE_ERROR = 'ERROR';

	/**
	 * @param $isNew
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
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

		if ($this->fields->isChanged('PAID'))
		{
			if ($this->isPaid() && Crm\Automation\Factory::canUseAutomation())
			{
				Crm\Automation\Trigger\PaymentTrigger::execute(
					[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getOrderId()]],
					['PAYMENT' => $this]
				);
			}

			if (!$this->getOrder()->isNew())
			{
				$timelineParams =  [
					'FIELDS' => $this->getFieldValues(),
					'SETTINGS' => [
						'CHANGED_ENTITY' => \CCrmOwnerType::OrderPaymentName,
						'FIELDS' => [
							'ORDER_PAID' => $this->getField('PAID'),
							'ORDER_DONE' => 'N'
						]
					],
					'BINDINGS' => $this->getTimelineBindings()
				];

				Crm\Timeline\OrderPaymentController::getInstance()->onPaid($this->getId(), $timelineParams);
			}
		}

		if(Main\Loader::includeModule('pull'))
		{
			\CPullWatch::AddToStack(
				'CRM_ENTITY_ORDER_PAYMENT',
				[
					'module_id' => 'crm',
					'command' => 'onOrderPaymentSave',
					'params' => [
						'FIELDS' => $this->getFieldValues()
					]
				]
			);
		}
	}

	/**
	 * @return array
	 */
	private function getTimelineBindings() : array
	{
		$bindings = [
			[
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $this->getOrderId()
			]
		];

		if ($this->getOrder()->getDealbinding())
		{
			/** @var DealBinding $dealBindings */
			$dealBindings = $this->getOrder()->getDealBinding();

			$bindings[] = [
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID' => $dealBindings->getDealId()
			];
		}

		return $bindings;
	}

	/**
	 * @throws Main\ArgumentException
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

		Crm\Timeline\OrderPaymentController::getInstance()->onCreate(
			$this->getId(),
			array('FIELDS' => $fields)
		);
	}

	/**
	 * @throws Main\ArgumentException
	 * @return void;
	 */
	private function updateTimelineCreationEntity()
	{
		$fields = $this->getFields();
		$selectedFields = [
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

	/**
	 * @return Sale\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectNotFoundException
	 */
	public function delete()
	{
		$deleteResult = parent::delete();
		if ($deleteResult->isSuccess() && (int)$this->getId() > 0)
		{
			Crm\Timeline\TimelineEntry::deleteByOwner(\CCrmOwnerType::OrderPayment, $this->getId());
		}

		return $deleteResult;
	}

	/**
	 * @param Main\Event $event
	 * @throws Main\ArgumentException
	 * @noinspection PhpUnused
	 */
	public static function onSalePsInitiatePayError(Main\Event $event): void
	{
		/** @var Payment $payment */
		$payment = $event->getParameter('payment');
		if (!($payment instanceof self))
		{
			return;
		}

		$errors = $event->getParameter('errors');

		$timelineParams =  [
			'FIELDS' => $payment->getFieldValues(),
			'SETTINGS' => [
				'CHANGED_ENTITY' => \CCrmOwnerType::OrderPaymentName,
				'FIELDS' => [
					'PAY_SYSTEM_NAME' => $payment->getPaymentSystemName(),
					'STATUS_CODE' => self::STATUS_CODE_ERROR,
					'STATUS_DESCRIPTION' => implode("\n", $errors),
				]
			],
			'BINDINGS' => $payment->getTimelineBindings()
		];

		Crm\Timeline\OrderPaymentController::getInstance()->onPaid($payment->getId(), $timelineParams);
	}
}