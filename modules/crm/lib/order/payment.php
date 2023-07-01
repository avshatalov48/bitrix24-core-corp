<?php

namespace Bitrix\Crm\Order;

use Bitrix\Crm;
use Bitrix\Crm\Activity\Provider\Sms;
use Bitrix\Sale;
use Bitrix\Main;
use Bitrix\Sale\TradingPlatform\Landing\Landing;
use Bitrix\Crm\Activity;
use Bitrix\Crm\Workflow\PaymentWorkflow;
use Bitrix\Crm\Workflow\PaymentStage;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Payment
 * @package Bitrix\Crm\Order
 * @method Order getOrder()
 */
class Payment extends Sale\Payment
{
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
			$this->savePaymentStageOnCreate();
		}
		elseif ($this->fields->isChanged('SUM') || $this->fields->isChanged('CURRENCY') )
		{
			$this->updateTimelineCreationEntity();
		}

		if ($this->fields->isChanged('PAID'))
		{
			if ($this->isPaid() && Crm\Automation\Factory::isAutomationAvailable(\CCrmOwnerType::Order))
			{
				Crm\Automation\Trigger\PaymentTrigger::execute(
					[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getOrderId()]],
					['PAYMENT' => $this]
				);
			}

			if (!$isNew && !$this->getOrder()->isNew())
			{
				$timelineParams =  [
					'FIELDS' => $this->getFieldValues(),
					'SETTINGS' => [
						'FIELDS' => [
							'ORDER_PAID' => $this->getField('PAID'),
							'ORDER_DONE' => 'N'
						]
					],
					'BINDINGS' => $this->getTimelineBindings(),
					'ENTITY' => $this,
				];

				Crm\Timeline\OrderPaymentController::getInstance()->onPaid($this->getId(), $timelineParams);
				$this->savePaymentStageOnUpdate();
			}

			if ($this->isPaid())
			{
				Activity\Provider\Payment::addActivity($this);
				$this->sendOrderPaidSmsToClient();
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

		/** @var EntityBinding $binding */
		$binding = $this->getOrder()->getEntityBinding();
		if ($binding)
		{
			$bindings[] = [
				'ENTITY_TYPE_ID' => $binding->getOwnerTypeId(),
				'ENTITY_ID' => $binding->getOwnerId()
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
			Activity\Provider\Payment::onPaymentDeleted($this->getId());
			Crm\Integration\DocumentGeneratorManager::getInstance()->clearPaymentBindings($this->getId());
			PaymentWorkflow::createFrom($this)->resetStage();
		}

		return $deleteResult;
	}

	private function sendOrderPaidSmsToClient(): void
	{
		if (!Main\Loader::includeModule('landing'))
		{
			return;
		}

		$order = $this->getOrder();

		/** @var Contact|Company|null $entityCommunication */
		$entityCommunication = $order->getContactCompanyCollection()->getEntityCommunication();
		$phoneTo = $order->getContactCompanyCollection()->getEntityCommunicationPhone();

		if (
			$order->getTradeBindingCollection()->hasTradingPlatform(
				Landing::TRADING_PLATFORM_CODE,
				Landing::LANDING_STORE_STORE_V3
			)
			&& $entityCommunication
			&& $phoneTo
		)
		{
			Crm\MessageSender\MessageSender::send(
				[
					Crm\Integration\NotificationsManager::getSenderCode() => [
						'ACTIVITY_PROVIDER_TYPE_ID' => Activity\Provider\Notification::PROVIDER_TYPE_NOTIFICATION,
						'TEMPLATE_CODE' => 'ORDER_PAID',
						'PLACEHOLDERS' => [
							'NAME' => $entityCommunication->getCustomerName(),
						],
					],
					Crm\Integration\SmsManager::getSenderCode() => [
						'ACTIVITY_PROVIDER_TYPE_ID' => Sms::PROVIDER_TYPE_SALESCENTER_DELIVERY,
						'MESSAGE_BODY' => Main\Localization\Loc::getMessage(
							'CRM_PAYMENT_ORDER_PAID',
							[
								'#CUSTOMER_NAME#' => $entityCommunication->getCustomerName()
							]
						),
					]
				],
				[
					'COMMON_OPTIONS' => [
						'PHONE_NUMBER' => $phoneTo,
						'USER_ID' => $order->getField('RESPONSIBLE_ID'),
						'ADDITIONAL_FIELDS' => [
							'ENTITY_TYPE' => $entityCommunication::getEntityTypeName(),
							'ENTITY_TYPE_ID' => $entityCommunication::getEntityType(),
							'ENTITY_ID' => $entityCommunication->getField('ENTITY_ID'),
							'BINDINGS' => Crm\Order\BindingsMaker\ActivityBindingsMaker::makeByPayment(
								$this,
								[
									'extraBindings' => [
										[
											'TYPE_ID' => $entityCommunication::getEntityType(),
											'ID' => $entityCommunication->getField('ENTITY_ID')
										]
									]
								]
							),
						]
					]
				]
			);
		}
	}

	private function savePaymentStageOnCreate()
	{
		$initialStage = $this->isPaid() ? PaymentStage::PAID : PaymentStage::NOT_PAID;

		PaymentWorkflow::createFrom($this)->setStage($initialStage);
	}

	private function savePaymentStageOnUpdate()
	{
		if ($this->isPaid())
		{
			$nextStage = PaymentStage::PAID;
		}
		elseif ($this->isReturn())
		{
			$nextStage = PaymentStage::REFUND;
		}
		else
		{
			$nextStage = PaymentStage::CANCEL;
		}

		PaymentWorkflow::createFrom($this)->setStage($nextStage);
	}
}
