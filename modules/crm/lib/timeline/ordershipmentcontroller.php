<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Order\DeliveryStatus;
use Bitrix\Crm\Order\Shipment;
use Bitrix\Main;
use Bitrix\Crm\Order\OrderShipmentStatus;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OrderShipmentController extends EntityController
{
	//region Singleton
	/** @var OrderShipmentController|null */
	protected static $instance = null;
	/**
	 * @return OrderShipmentController
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new OrderShipmentController();
		}
		return self::$instance;
	}
	//endregion
	//region EntityController
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::OrderShipment;
	}

	/**
	 * @param $ownerID
	 * @param array $params
	 *
	 * @throws Main\ArgumentException
	 */
	public function onCreate($ownerID, array $params)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$fields = isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : null;
		if(!is_array($fields))
		{
			$fields = self::getEntity($ownerID);
		}
		if(!is_array($fields))
		{
			return;
		}

		$settingFields = [
			'PRICE_DELIVERY' => $fields['PRICE_DELIVERY'],
			'CURRENCY' => $fields['CURRENCY']
		];

		if ($fields['DATE_INSERT'] instanceof Main\Type\Date)
		{
			$settingFields['DATE_INSERT_TIMESTAMP'] = $fields['DATE_INSERT']->getTimestamp();
		}

		$settings = ['FIELDS' => $settingFields];

		$orderId = (isset($fields['ORDER_ID']) && (int)$fields['ORDER_ID'] > 0) ? (int)$fields['ORDER_ID'] : 0;
		if($orderId > 0)
		{
			$settings['BASE'] = array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => (int)$fields['ORDER_ID']
			);
		}

		$authorID = self::resolveCreatorID($fields);

		$bindings = array(
			array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::OrderShipment,
				'ENTITY_ID' => $ownerID
			)
		);

		if ($orderId > 0)
		{
			$bindings[] = array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $orderId
			);

			$tag = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Order, $orderId);
		}
		else
		{
			$tag = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::OrderShipment, $ownerID);
		}

		$historyEntryID = CreationEntry::create(
			array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::OrderShipment,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => $settings,
				'BINDINGS' => $bindings
			)
		);

		if($historyEntryID > 0)
		{
			self::pushHistoryEntry($historyEntryID, $tag,'timeline_order_shipment_add');
		}
	}

	/**
	 * @param $ownerID
	 * @param array $params
	 *
	 * @throws Main\ArgumentException
	 */
	public function onModify($ownerID, array $params)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$historyEntryID = null;
		$orderId = (isset($params['ORDER_ID']) && (int)$params['ORDER_ID'] > 0) ? (int)$params['ORDER_ID'] : 0;

		if (isset($params['CURRENT_FIELDS']['STATUS_ID']))
		{
			$historyEntryID = $this->onStatusModify($ownerID, $params, $orderId);
		}

		if($historyEntryID > 0)
		{
			if ($orderId > 0)
			{
				$tag = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Order, $orderId);
			}
			else
			{
				$tag = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::OrderShipment, $ownerID);
			}
			self::pushHistoryEntry($historyEntryID, $tag, 'timeline_activity_add');
		}
	}
	public function updateSettingFields($ownerID, $entryTypeID, array $fields)
	{
		$result = new Main\Result();
		$ownerID = (int)$ownerID;
		$entryTypeID = (int)$entryTypeID;
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$timelineData = Entity\TimelineTable::getList([
			'filter' => [
				'ASSOCIATED_ENTITY_ID' => $ownerID,
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::OrderShipment,
				'TYPE_ID' => $entryTypeID,
			]
		]);
		while ($row = $timelineData->fetch())
		{
			$settings = $row['SETTINGS'];
			$settings['FIELDS'] = $fields;
			$r = Entity\TimelineTable::update($row['ID'], ['SETTINGS' => $settings]);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
			elseif (is_array($settings['BASE']))
			{
				$baseOwnerId = (int)$settings['BASE']['ENTITY_ID'];
				$baseOwnerTypeId = (int)$settings['BASE']['ENTITY_TYPE_ID'];
				if ($baseOwnerId > 0 && \CCrmOwnerType::IsDefined($baseOwnerTypeId))
				{
					$row['SETTINGS'] = $settings;
					$items = array($row['ID'] => $row);
					TimelineManager::prepareDisplayData($items);
					if(Main\Loader::includeModule('pull') && \CPullOptions::GetQueueServerStatus())
					{
						$tag = TimelineEntry::prepareEntityPushTag($baseOwnerTypeId, $baseOwnerId);
						\CPullWatch::AddToStack(
							$tag,
							array(
								'module_id' => 'crm',
								'command' => 'timeline_item_update',
								'params' => array('ENTITY_ID' => $row['ID'], 'TAG' => $tag, 'HISTORY_ITEM' => $items[$row['ID']]),
							)
						);
					}
				}
			}
		}

		return $result;
	}
	/**
	 * @param $ID
	 *
	 * @return array|null
	 */
	protected static function getEntity($ID)
	{
		$shipment = Shipment::getList(	array(
			'filter' => array('ID' => $ID),
			'select' => array(
				'ORDER_CREATED_BY' => 'ORDER.CREATE_BY',
				'ORDER_ACCOUNT_NUMBER' => 'ORDER.ACCOUNT_NUMBER',
				'RESPONSIBLE_ID','ACCOUNT_NUMBER', 'DATE_INSERT', 'ORDER_ID'
			)
		));

		return is_object($shipment) ? $shipment->getFields() : null;
	}
	protected static function resolveCreatorID(array $fields)
	{
		$authorID = 0;

		if ($authorID <= 0 && isset($fields['RESPONSIBLE_ID']))
		{
			$authorID = (int)$fields['RESPONSIBLE_ID'];
		}

		if ($authorID <= 0 && isset($fields['ORDER_CREATED_BY']))
		{
			$authorID = (int)$fields['ORDER_CREATED_BY'];
		}

		if($authorID <= 0)
		{
			//Set portal admin as default creator
			$authorID = 1;
		}

		return $authorID;
	}
	protected static function resolveEditorID(array $fields)
	{
		$authorID = 0;

		if (isset($fields['RESPONSIBLE_ID']))
		{
			$authorID = (int)$fields['RESPONSIBLE_ID'];
		}

		if (isset($fields['MODIFY_BY']))
		{
			$authorID = (int)$fields['MODIFY_BY'];
		}


		if($authorID <= 0)
		{
			//Set portal admin as default editor
			$authorID = 1;
		}

		return $authorID;
	}
	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$typeID = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : TimelineType::UNDEFINED;
		$settings = $data['SETTINGS'];
		if($typeID === TimelineType::CREATION)
		{
			$base = isset($settings['BASE']) ? $settings['BASE'] : null;
			$data['TITLE'] = Loc::getMessage('CRM_ORDER_SHIPMENT_CREATION');

			if(is_array($base))
			{
				$entityTypeID = isset($base['ENTITY_TYPE_ID']) ? $base['ENTITY_TYPE_ID'] : 0;
				$caption = Loc::getMessage("CRM_SHIPMENT_BASE_CAPTION_BASED_ON_ORDER");

				$entityID = isset($base['ENTITY_ID']) ? $base['ENTITY_ID'] : 0;
				if(\CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0)
				{
					$data['BASE']['CAPTION'] = $caption;
					if(\CCrmOwnerType::TryGetEntityInfo(\CCrmOwnerType::Order, $entityID, $baseEntityInfo, false))
					{
						$data['BASE']['ENTITY_INFO'] = $baseEntityInfo;
					}
				}
			}

			$fields = $settings['FIELDS'];
			$title = $data['ASSOCIATED_ENTITY']['TITLE'];
			if (!empty($fields['DATE_INSERT_TIMESTAMP']))
			{
				$dateInsert = \CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp($fields['DATE_INSERT_TIMESTAMP'],'SHORT'));
			}
			if (empty($dateInsert))
			{
				$dateInsert = \CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($data['DATE_INSERT']),'SHORT'));
			}

			$data['ASSOCIATED_ENTITY']['HTML_TITLE'] = Loc::getMessage(
				'CRM_SHIPMENT_CREATION_MESSAGE',
				[
					'#ACCOUNT_NUMBER#' => $title,
					'#DATE_INSERT#' => $dateInsert,
				]
			);
			if (!empty($fields['PRICE_DELIVERY']) && !empty($fields['CURRENCY']))
			{
				$data['ASSOCIATED_ENTITY']['HTML_TITLE'] .= " ".Loc::getMessage(
					'CRM_SHIPMENT_CREATION_MESSAGE_PRICE_DELIVERY',
					['#PRICE_WITH_CURRENCY#' => \CCrmCurrency::MoneyToString($fields['PRICE_DELIVERY'], $fields['CURRENCY'])]
				);
			}

			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::MODIFICATION)
		{
			$fieldName = isset($settings['FIELD']) ? $settings['FIELD'] : '';
			if($fieldName === 'STATUS_ID')
			{
				$data['TITLE'] = Loc::getMessage(
					'CRM_ORDER_SHIPMENT_MODIFICATION_STATUS',
					array('#ID#' => $data['ASSOCIATED_ENTITY_ID'])
				);
				$data['START_NAME'] = isset($settings['START_NAME']) ? $settings['START_NAME'] : $settings['START'];
				$data['FINISH_NAME'] = isset($settings['FINISH_NAME']) ? $settings['FINISH_NAME'] : $settings['FINISH'];
			}
			unset($data['SETTINGS']);
		}
		return parent::prepareHistoryDataModel($data, $options);
	}

	/**
	 * @param int $ownerID
	 * @param array $params
	 * @param int $orderId
	 *
	 * @return int
	 */
	protected function onStatusModify($ownerID, array $params, $orderId = null)
	{
		$currentFields = isset($params['CURRENT_FIELDS']) && is_array($params['CURRENT_FIELDS'])
			? $params['CURRENT_FIELDS'] : array();
		$previousFields = isset($params['PREVIOUS_FIELDS']) && is_array($params['PREVIOUS_FIELDS'])
			? $params['PREVIOUS_FIELDS'] : array();

		$historyEntryID = null;
		$prevStageID = isset($previousFields['STATUS_ID']) ? $previousFields['STATUS_ID'] : '';
		$currentStageID = isset($currentFields['STATUS_ID']) ? $currentFields['STATUS_ID'] : $prevStageID;

		$authorID = self::resolveEditorID($currentFields);
		if (strlen($prevStageID) > 0 && $prevStageID !== $currentStageID)
		{
			$stageNames = DeliveryStatus::getListInCrmFormat();

			$bindings = array(
				array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::OrderShipment,
					'ENTITY_ID' => $ownerID
				)
			);

			if ($orderId > 0)
			{
				$bindings[] = array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
					'ENTITY_ID' => $orderId
				);
			}
			$historyEntryID = ModificationEntry::create(
				array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::OrderShipment,
					'ENTITY_ID' => $ownerID,
					'AUTHOR_ID' => $authorID,
					'BINDINGS' => $bindings,
					'SETTINGS' => array(
						'FIELD' => 'STATUS_ID',
						'START' => $prevStageID,
						'FINISH' => $currentStageID,
						'START_NAME' => isset($stageNames[$prevStageID]['NAME']) ? $stageNames[$prevStageID]['NAME'] : $prevStageID,
						'FINISH_NAME' => isset($stageNames[$currentStageID]['NAME']) ? $stageNames[$currentStageID]['NAME'] : $currentStageID
					)
				)
			);
		}

		return (int)$historyEntryID;
	}
}