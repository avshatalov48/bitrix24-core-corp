<?php

namespace Bitrix\Crm\Order;

use Bitrix\Crm;
use Bitrix\Crm\Timeline\DeliveryController;
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

			if (!$this->isSystem() && !$this->getOrder()->isNew())
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
					'BINDINGS' => $this->getTimelineBindings()
				];

				Crm\Timeline\OrderShipmentController::getInstance()->onDeducted($this->getId(), $timelineParams);
			}
		}

		if (!$this->isSystem()
			&& $isNew
			&& ($deliveryService = $this->getDelivery())
		)
		{
			if ($deliveryService instanceof Sale\Delivery\Services\Crm\ICrmActivityProvider)
			{
				$activity = $deliveryService->provideCrmActivity($this);

				$fields = [
					'TYPE_ID' => \CCrmActivityType::Provider,
					'ASSOCIATED_ENTITY_ID' => $this->getId(),
					'PROVIDER_ID' => 'CRM_DELIVERY',
					'PROVIDER_TYPE_ID' => 'DELIVERY',
					'SUBJECT' => $activity->getSubject() ? $activity->getSubject() : 'Delivery',
					'IS_HANDLEABLE' => $activity->isHandleable() ? 'Y' : 'N',
					'COMPLETED' => $activity->isCompleted() ? 'Y' : 'N',
					'STATUS' => $activity->getStatus(),
					'RESPONSIBLE_ID' => $activity->getResponsibleId(),
					'PRIORITY' => $activity->getPriority(),
					'AUTHOR_ID' => $activity->getAuthorId(),
					'BINDINGS' => $activity->getBindings(),
					'SETTINGS' => [
						'FIELDS' => $activity->getFields()
					],
				];

				$activityId = (int)\CCrmActivity::add($fields, false);

				if ($activityId > 0)
				{
					AddEventToStatFile(
						'sale',
						'deliveryActivityCreation',
						$activityId,
						$deliveryService->getName(),
						'delivery_service_name'
					);
				}
			}

			if ($deliveryService instanceof Sale\Delivery\Services\Crm\ICrmEstimationMessageProvider)
			{
				$estimationMessage = $deliveryService->provideCrmEstimationMessage($this);

				DeliveryController::getInstance()->createHistoryMessage(
					$this->getId(),
					$estimationMessage->getTypeId(),
					[
						'AUTHOR_ID' => $estimationMessage->getAuthorId(),
						'SETTINGS' => ['FIELDS' => $estimationMessage->getFields()],
						'BINDINGS' => $estimationMessage->getBindings()
					]
				);
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
	 * @return array
	 */
	private function getTimelineBindings() : array
	{
		$bindings = [
			[
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $this->getOrder()->getId()
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

		Crm\Timeline\OrderShipmentController::getInstance()->onCreate(
			$this->getId(),
			array('FIELDS' => $fields)
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