<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main\Loader;

class TimelineManager
{
	/**
	 * @param array $item
	 * @return EntityController|null
	 */
	public static function resolveController(array $item)
	{
		$typeID = isset($item['TYPE_ID']) ? (int)$item['TYPE_ID'] : 0;
		$assocEntityTypeID = isset($item['ASSOCIATED_ENTITY_TYPE_ID'])
			? (int)$item['ASSOCIATED_ENTITY_TYPE_ID'] : 0;

		if($typeID === TimelineType::WAIT)
		{
			return WaitController::getInstance();
		}

		if($typeID === TimelineType::BIZPROC)
		{
			return BizprocController::getInstance();
		}

		if($typeID === TimelineType::SENDER)
		{
			$senderRecipientControllerClass = '\Bitrix\Sender\Integration\Crm\Timeline\RecipientController';
			if (Loader::includeModule('sender') && class_exists($senderRecipientControllerClass))
			{
				/** @var \Bitrix\Sender\Integration\Crm\Timeline\RecipientController $senderRecipientControllerClass */
				return $senderRecipientControllerClass::getInstance();
			}
			else
			{
				return null;
			}
		}

		if($typeID === TimelineType::COMMENT)
		{
			return CommentController::getInstance();
		}

		if($typeID === TimelineType::EXTERNAL_NOTICE)
		{
			return ExternalNoticeController::getInstance();
		}

		if($typeID === TimelineType::DOCUMENT)
		{
			return DocumentController::getInstance();
		}

		if($typeID === TimelineType::DELIVERY)
		{
			return DeliveryController::getInstance();
		}

		if($assocEntityTypeID === \CCrmOwnerType::Activity)
		{
			if($typeID === TimelineType::MODIFICATION)
			{
				$settings = isset($item['SETTINGS']) && is_array($item['SETTINGS']) ? $item['SETTINGS'] : array();
				$entity = isset($settings['ENTITY']) && is_array($settings['ENTITY']) ? $settings['ENTITY'] : array();
				$activityTypeID = isset($entity['TYPE_ID']) ? (int)$entity['TYPE_ID'] : 0;

				if($activityTypeID === \CCrmActivityType::Task)
				{
					return TaskController::getInstance();
				}
			}

			return ActivityController::getInstance();
		}
		elseif($assocEntityTypeID === \CCrmOwnerType::Lead || $assocEntityTypeID === \CCrmOwnerType::SuspendedLead)
		{
			return LeadController::getInstance();
		}
		elseif($assocEntityTypeID === \CCrmOwnerType::Contact || $assocEntityTypeID === \CCrmOwnerType::SuspendedContact)
		{
			return ContactController::getInstance();
		}
		elseif($assocEntityTypeID === \CCrmOwnerType::Company || $assocEntityTypeID === \CCrmOwnerType::SuspendedCompany)
		{
			return CompanyController::getInstance();
		}
		elseif($assocEntityTypeID === \CCrmOwnerType::Deal || $assocEntityTypeID === \CCrmOwnerType::SuspendedDeal)
		{
			return DealController::getInstance();
		}
		elseif($assocEntityTypeID === \CCrmOwnerType::DealRecurring)
		{
			return DealRecurringController::getInstance();
		}
		elseif($assocEntityTypeID === \CCrmOwnerType::Order)
		{
			return OrderController::getInstance();
		}
		elseif($assocEntityTypeID === \CCrmOwnerType::OrderPayment)
		{
			return OrderPaymentController::getInstance();
		}
		elseif($assocEntityTypeID === \CCrmOwnerType::OrderShipment)
		{
			return OrderShipmentController::getInstance();
		}
		elseif($assocEntityTypeID === \CCrmOwnerType::OrderCheck)
		{
			return OrderCheckController::getInstance();
		}
		elseif($assocEntityTypeID === \CCrmOwnerType::Scoring)
		{
			return ScoringController::getInstance();
		}
		elseif(\CCrmOwnerType::isPossibleDynamicTypeId($assocEntityTypeID))
		{
			return DynamicController::getInstance($assocEntityTypeID);
		}
		elseif ($assocEntityTypeID === \CCrmOwnerType::Quote)
		{
			return QuoteController::getInstance();
		}

		return null;
	}
	public static function prepareItemDisplayData(array &$item)
	{
		$items = array($item);
		self::prepareDisplayData($items);
		$item = $items[0];
	}
	public static function prepareDisplayData(array &$items, $userID = 0, $userPermissions = null)
	{
		$entityMap = array();
		foreach($items as $ID => $item)
		{
			if(!is_array($item))
			{
				continue;
			}

			$assocEntityTypeID = isset($item['ASSOCIATED_ENTITY_TYPE_ID']) ? (int)$item['ASSOCIATED_ENTITY_TYPE_ID'] : 0;
			$assocEntityID = isset($item['ASSOCIATED_ENTITY_ID']) ? (int)$item['ASSOCIATED_ENTITY_ID'] : 0;

			if($assocEntityTypeID === \CCrmOwnerType::Undefined)
			{
				continue;
			}

			if(!isset($entityMap[$assocEntityTypeID]))
			{
				$entityMap[$assocEntityTypeID] = array();
			}

			if(!isset($entityMap[$assocEntityTypeID][$assocEntityID]))
			{
				$entityMap[$assocEntityTypeID][$assocEntityID] = array('ITEM_IDS' => array());
			}

			$entityMap[$assocEntityTypeID][$assocEntityID]['ITEM_IDS'][] = $ID;
		}

		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		if($userPermissions === null)
		{
			$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}

		foreach($entityMap as $entityTypeID => $entityInfos)
		{
			if($entityTypeID === \CCrmOwnerType::Wait)
			{
				$entityIDs = array_keys($entityInfos);
				$dbResult = \Bitrix\Crm\Pseudoactivity\Entity\WaitTable::getList(
					array('filter' => array('@ID' => $entityIDs))
				);

				while($fields = $dbResult->fetch())
				{
					$assocEntityID = (int)$fields['ID'];
					if(isset($entityInfos[$assocEntityID]))
					{
						$itemIDs = isset($entityInfos[$assocEntityID]['ITEM_IDS'])
							? $entityInfos[$assocEntityID]['ITEM_IDS'] : array();

						$fields = WaitController::prepareEntityDataModel(
							$assocEntityID,
							$fields
						);

						foreach($itemIDs as $itemID)
						{
							$items[$itemID]['ASSOCIATED_ENTITY'] = $fields;
						}
					}
				}
			}
			elseif($entityTypeID === \CCrmOwnerType::Activity)
			{
				$activityIDs = array_keys($entityInfos);
				$dbResult = \CCrmActivity::GetList(
					array(),
					array('@ID' => $activityIDs, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array(
						'ID', 'OWNER_ID', 'OWNER_TYPE_ID', 'TYPE_ID', 'RESPONSIBLE_ID',
						'PROVIDER_ID', 'PROVIDER_TYPE_ID', 'PROVIDER_PARAMS',
						'ASSOCIATED_ENTITY_ID', 'DIRECTION', 'SUBJECT', 'STATUS', 'DEADLINE',
						'DESCRIPTION', 'DESCRIPTION_TYPE', 'ASSOCIATED_ENTITY_ID',
						'STORAGE_TYPE_ID', 'STORAGE_ELEMENT_IDS', 'ORIGIN_ID', 'SETTINGS'
					)
				);
				while($fields = $dbResult->Fetch())
				{
					$assocEntityID = (int)$fields['ID'];
					if(!isset($entityInfos[$assocEntityID]))
					{
						continue;
					}

					$responsibleID = isset($fields['RESPONSIBLE_ID']) ? (int)$fields['RESPONSIBLE_ID'] : 0;
					$isPermitted = $responsibleID === $userID
						|| \CCrmActivity::CheckReadPermission($fields['OWNER_TYPE_ID'], $fields['OWNER_ID'], $userPermissions);

					$itemIDs = isset($entityInfos[$assocEntityID]['ITEM_IDS'])
						? $entityInfos[$assocEntityID]['ITEM_IDS'] : array();

					if($isPermitted)
					{
						$fields = ActivityController::prepareEntityDataModel(
							$assocEntityID,
							$fields,
							array('ENABLE_COMMUNICATIONS' => false)
						);

						foreach($itemIDs as $itemID)
						{
							$items[$itemID]['ASSOCIATED_ENTITY'] = $fields;
						}
					}
					else
					{
						foreach($itemIDs as $itemID)
						{
							unset($items[$itemID]);
						}
					}
				}

				$communications = \CCrmActivity::PrepareCommunicationInfos(
					$activityIDs,
					array(
						'ENABLE_PERMISSION_CHECK' => true,
						'USER_PERMISSIONS' => $userPermissions
					)
				);
				foreach($communications as $assocEntityID => $info)
				{
					if(isset($entityInfos[$assocEntityID]))
					{
						$itemIDs = isset($entityInfos[$assocEntityID]['ITEM_IDS'])
							? $entityInfos[$assocEntityID]['ITEM_IDS'] : array();

						foreach($itemIDs as $itemID)
						{
							if(isset($items[$itemID]) && isset($items[$itemID]['ASSOCIATED_ENTITY']))
							{
								$items[$itemID]['ASSOCIATED_ENTITY']['COMMUNICATION'] = $info;
							}
						}
					}
				}
			}
			elseif ($entityTypeID === \CCrmOwnerType::Order)
			{
				$orderIds = array_keys($entityInfos);
				$orderPaymentMap = [];
				$paymentsData = \Bitrix\Crm\Order\Payment::getList(
					array(
						'filter' => array('=ORDER_ID' => $orderIds),
						'select' => array('ID', 'ORDER_ID', 'SUM', 'CURRENCY', 'PAY_SYSTEM_NAME')
					)
				);
				while ($payment = $paymentsData->fetch())
				{
					$payment['SHOW_URL'] = \CComponentEngine::MakePathFromTemplate(
						\Bitrix\Main\Config\Option::get('crm', 'path_to_order_payment_details'),
						array('payment_id' => $payment['ID'])
					);
					$payment['SUM_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString($payment['SUM'], $payment['CURRENCY']);
					$orderPaymentMap[$payment['ORDER_ID']][$payment['ID']] = $payment;
				}

				$orderShipmentMap = [];
				$shipmentsData = \Bitrix\Crm\Order\Shipment::getList(
					array(
						'filter' => array(
							'=ORDER_ID' => $orderIds,
							'SYSTEM' => 'N'
						),
						'select' => array('ID', 'ORDER_ID', 'PRICE_DELIVERY', 'CURRENCY', 'DELIVERY_NAME')
					)
				);
				while ($shipment = $shipmentsData->fetch())
				{
					$shipment['SHOW_URL'] = \CComponentEngine::MakePathFromTemplate(
						\Bitrix\Main\Config\Option::get('crm', 'path_to_order_shipment_details'),
						array('shipment_id' => $shipment['ID'])
					);
					$shipment['PRICE_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString($shipment['PRICE'], $shipment['CURRENCY']);
					$orderShipmentMap[$shipment['ORDER_ID']][$shipment['ID']] = $shipment;
				}

				\CCrmOwnerType::PrepareEntityInfoBatch($entityTypeID, $entityInfos, false);
				foreach($entityInfos as $entityID => $entityInfo)
				{
					if (empty($entityInfo['ITEM_IDS']) || !is_array($entityInfo['ITEM_IDS']))
					{
						continue;
					}
					if (!empty($orderPaymentMap[$entityID]))
					{
						$entityInfo['PAYMENTS_INFO'] = $orderPaymentMap[$entityID];
					}

					if (!empty($orderShipmentMap[$entityID]))
					{
						$entityInfo['SHIPMENTS_INFO'] = $orderShipmentMap[$entityID];
					}

					foreach($entityInfo['ITEM_IDS'] as $itemID)
					{
						$items[$itemID]['ASSOCIATED_ENTITY'] = $entityInfo;
					}
				}
			}
			elseif ($entityTypeID === \CCrmOwnerType::OrderCheck)
			{
				if (\Bitrix\Main\Loader::includeModule('sale'))
				{
					$checkIds = array_keys($entityInfos);
					$checkDB = \Bitrix\Sale\Cashbox\CheckManager::getList(
						array(
							'filter' => array('=ID' => $checkIds),
							'select' => array('ID', 'DATE_CREATE', 'ORDER_ID', 'SUM', 'CURRENCY')
						)
					);

					while ($check = $checkDB->fetch())
					{
						$check['SHOW_URL'] = \CComponentEngine::MakePathFromTemplate(
							\Bitrix\Main\Config\Option::get('crm', 'path_to_order_check_details'),
							array('check_id' => $check['ID'])
						);

						$listLink = \CComponentEngine::MakePathFromTemplate(
							\Bitrix\Main\Config\Option::get('crm', 'path_to_order_details'),
							array('order_id' => $check['ORDER_ID'])
						);

						$uri = new \Bitrix\Main\Web\Uri($listLink);
						$uri->addParams(['tab' => 'check']);
						$check['LIST_URL'] = $uri->getUri();
						if ($check['DATE_CREATE'] instanceof \Bitrix\Main\Type\Date)
						{
							$culture = \Bitrix\Main\Context::getCurrent()->getCulture();
							$check['DATE_CREATE_FORMATTED'] =  FormatDate($culture->getLongDateFormat(), $check['DATE_CREATE']->getTimestamp());
						}

						$check['SUM_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString($check['SUM'], $check['CURRENCY']);
						$entityInfos[$check['ID']] = array_merge($entityInfos[$check['ID']], $check);
					}

					foreach($entityInfos as $entityID => $entityInfo)
					{
						$itemIDs = isset($entityInfo['ITEM_IDS']) ? $entityInfo['ITEM_IDS'] : array();
						foreach($itemIDs as $itemID)
						{
							$items[$itemID]['ASSOCIATED_ENTITY'] = $entityInfo;
						}
					}
				}
			}
			else
			{
				if ($entityTypeID === \CCrmOwnerType::DealRecurring)
				{
					$entityTypeID = \CCrmOwnerType::Deal;
				}
				\CCrmOwnerType::PrepareEntityInfoBatch($entityTypeID, $entityInfos, false);
				foreach($entityInfos as $entityID => $entityInfo)
				{
					$itemIDs = isset($entityInfo['ITEM_IDS']) ? $entityInfo['ITEM_IDS'] : array();
					foreach($itemIDs as $itemID)
					{
						$items[$itemID]['ASSOCIATED_ENTITY'] = $entityInfo;
					}
				}
			}
		}

		$defaultController = new EntityController();
		foreach($items as $ID => &$item)
		{
			if(!is_array($item))
			{
				continue;
			}

			$controller = self::resolveController($item);
			if(!$controller)
			{
				$controller = $defaultController;
			}
			$item = $controller->prepareHistoryDataModel($item);
		}
		unset($item);

		EntityController::prepareAuthorInfoBulk($items);
	}

	/**
	 * Unbind events from old entity of one type and bind them to new entity of another type.
	 * @param integer $oldEntityTypeID Old Entity Type ID.
	 * @param integer $oldEntityID Old Old Entity ID.
	 * @param integer $newEntityTypeID New Entity Type ID.
	 * @param integer $newEntityID New Entity ID.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function transferOwnership($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID)
	{
		Entity\TimelineBindingTable::transferOwnership($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID);
	}

	/**
	 * Transfer events from old associated entity of one type to new entity of another type.
	 * @param integer $oldEntityTypeID Old Entity Type ID.
	 * @param integer $oldEntityID Old Old Entity ID.
	 * @param integer $newEntityTypeID New Entity Type ID.
	 * @param integer $newEntityID New Entity ID.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function transferAssociation($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID)
	{
		Entity\TimelineBindingTable::transferAssociation($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID);
	}

	/**
	 * Get Entity Type ID that should be ignored in timeline
	 * @return array
	 */
	public static function getIgnoredEntityTypeIDs()
	{
		return array(
			\CCrmOwnerType::SuspendedLead,
			\CCrmOwnerType::SuspendedDeal,
			\CCrmOwnerType::SuspendedContact,
			\CCrmOwnerType::SuspendedCompany,
			\CCrmOwnerType::SuspendedActivity
		);
	}
}