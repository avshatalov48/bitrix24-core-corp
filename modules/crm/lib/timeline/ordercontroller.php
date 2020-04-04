<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Order\OrderStatus;
use Bitrix\Main;
use Bitrix\Crm\Order\Order;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Order\DealBinding;

Loc::loadMessages(__FILE__);

class OrderController extends EntityController
{
	//region Singleton
	/** @var OrderController|null */
	protected static $instance = null;
	/**
	 * @return OrderController
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new OrderController();
		}
		return self::$instance;
	}
	//endregion
	//region EntityController
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Order;
	}

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
			'PRICE' => $fields['PRICE'],
			'CURRENCY' => $fields['CURRENCY']
		];

		if ($fields['DATE_INSERT'] instanceof Main\Type\Date)
		{
			$settingFields['DATE_INSERT_TIMESTAMP'] = $fields['DATE_INSERT']->getTimestamp();
		}

		$settings = ['FIELDS' => $settingFields];

		if(isset($fields['LEAD_ID']) && $fields['LEAD_ID'] > 0)
		{
			$settings['BASE'] = array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
				'ENTITY_ID' => (int)$fields['LEAD_ID']
			);
		}

		if(isset($fields['DEAL_ID']) && $fields['DEAL_ID'] > 0)
		{
			$settings['BASE'] = array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID' => (int)$fields['DEAL_ID']
			);
		}

		$authorID = self::resolveCreatorID($fields);
		$historyEntryID = CreationEntry::create(
			array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => $settings,
				'BINDINGS' => array(
					array(
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
						'ENTITY_ID' => $ownerID
					)
				)
			)
		);

		$enableHistoryPush = $historyEntryID > 0;
		if($enableHistoryPush && Main\Loader::includeModule('pull'))
		{
			$pushParams = array();
			if($enableHistoryPush)
			{
				$historyFields = TimelineEntry::getByID($historyEntryID);
				if(is_array($historyFields))
				{
					$pushParams['HISTORY_ITEM'] = $this->prepareHistoryDataModel(
						$historyFields,
						array('ENABLE_USER_INFO' => true)
					);
				}
			}

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Order, $ownerID);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => 'timeline_order_add',
					'params' => $pushParams,
				)
			);
		}
	}
	public function onCancel($ownerID, array $params)
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
		$value = ($fields['CANCELED'] === 'Y') ? 'Y' : 'N';

		$historyEntryID = null;
		$authorID = self::resolveEditorID($params);

		if (!empty($fields['REASON_CANCELED']) && strlen($fields['REASON_CANCELED']) > 0 && $value === 'Y')
		{
			$historyEntryID = ModificationEntry::create(
				array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
					'ENTITY_ID' => $ownerID,
					'AUTHOR_ID' => $authorID,
					'TEXT' => $fields['REASON_CANCELED'],
					'SETTINGS' => array(
						'FIELD' => 'REASON_CANCELED',
					)
				)
			);
		}

		$enableHistoryPush = $historyEntryID > 0;
		if(($enableHistoryPush) && Main\Loader::includeModule('pull'))
		{
			$pushParams = array();
			if($enableHistoryPush)
			{
				$historyFields = TimelineEntry::getByID($historyEntryID);
				if(is_array($historyFields))
				{
					$pushParams['HISTORY_ITEM'] = $this->prepareHistoryDataModel(
						$historyFields,
						array('ENABLE_USER_INFO' => true)
					);
				}
			}

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Order, $ownerID);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => 'timeline_activity_add',
					'params' => $pushParams,
				)
			);
		}
	}

	public function afterModifyExternalEntity($ownerID, array $params)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$historyEntryID = MarkEntry::create(
			array(
				'MARK_TYPE_ID'=>$params['TYPE'] == TimelineMarkType::SUCCESS? TimelineMarkType::SUCCESS:TimelineMarkType::FAILED,
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => intval($GLOBALS["USER"]->GetID()),
				'SETTINGS' => ['MESSAGE'=>$params['MESSAGE']<>''?$params['MESSAGE']:'']
			)
		);

		$enableHistoryPush = $historyEntryID > 0;
		if(($enableHistoryPush) && Main\Loader::includeModule('pull'))
		{
			$pushParams = array();
			if($enableHistoryPush)
			{
				$historyFields = TimelineEntry::getByID($historyEntryID);
				if(is_array($historyFields))
				{
					$pushParams['HISTORY_ITEM'] = $this->prepareHistoryDataModel(
						$historyFields,
						array('ENABLE_USER_INFO' => true)
					);
				}
			}

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Order, $ownerID);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => 'timeline_activity_add',
					'params' => $pushParams,
				)
			);
		}
	}

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

		$currentFields = isset($params['CURRENT_FIELDS']) && is_array($params['CURRENT_FIELDS'])
			? $params['CURRENT_FIELDS'] : array();
		$previousFields = isset($params['PREVIOUS_FIELDS']) && is_array($params['PREVIOUS_FIELDS'])
			? $params['PREVIOUS_FIELDS'] : array();

		$historyEntryID = null;

		if (isset($currentFields['STATUS_ID']))
		{
			$historyEntryID = $this->onStatusModify($ownerID, $currentFields, $previousFields);
		}
		elseif (isset($currentFields['PAYED']))
		{
			$historyEntryID = $this->onPay($ownerID, $currentFields, $previousFields);
		}
		elseif (isset($currentFields['DEDUCTED']))
		{
			$historyEntryID = $this->onDeduct($ownerID, $currentFields, $previousFields);
		}
		$enableHistoryPush = $historyEntryID > 0;
		if(($enableHistoryPush) && Main\Loader::includeModule('pull'))
		{
			$pushParams = array();
			if($enableHistoryPush)
			{
				$historyFields = TimelineEntry::getByID($historyEntryID);
				if(is_array($historyFields))
				{
					$pushParams['HISTORY_ITEM'] = $this->prepareHistoryDataModel(
						$historyFields,
						array('ENABLE_USER_INFO' => true)
					);
				}
			}

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Order, $ownerID);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => 'timeline_activity_add',
					'params' => $pushParams,
				)
			);
		}
	}

	/**
	 * @param $ownerId
	 * @param $dealId
	 * @param array $params
	 */
	public function notifyBindingDeal($ownerId, $dealId, array $params)
	{
		if(!is_int($ownerId))
		{
			$ownerId = (int)$ownerId;
		}
		if($ownerId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		if(!is_int($dealId))
		{
			$dealId = (int)$dealId;
		}
		if($dealId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'dealID');
		}

		$settings = is_array($params['SETTINGS']) ? $params['SETTINGS'] : [];
		$orderFields = is_array($params['ORDER_FIELDS']) ? $params['ORDER_FIELDS'] : [];
		$authorId = self::resolveCreatorID($orderFields);
		if (!empty($settings))
		{
			OrderEntry::create([
				'ENTITY_ID' => $ownerId,
				'TYPE_CATEGORY_ID' => TimelineType::MODIFICATION,
				'AUTHOR_ID' => $authorId,
				'BINDINGS' => [
					['ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,	'ENTITY_ID' => $dealId]
				],
				'SETTINGS' => $settings
			]);
		}
	}

	/**
	 * @param $ownerId
	 * @param $dealId
	 * @param array $params
	 */
	public function onBindingDealCreation($ownerId, $dealId, array $params)
	{
		if(!is_int($ownerId))
		{
			$ownerId = (int)$ownerId;
		}
		if($ownerId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		if(!is_int($dealId))
		{
			$dealId = (int)$dealId;
		}
		if($dealId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'dealID');
		}

		$orderFields = is_array($params['ORDER_FIELDS']) ? $params['ORDER_FIELDS'] : [];
		$authorId = self::resolveCreatorID($orderFields);

		OrderEntry::create([
			'ENTITY_ID' => $ownerId,
			'TYPE_CATEGORY_ID' => TimelineType::CREATION,
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => [
				'FIELDS' => [
					'PAID' => $orderFields['PAYED'],
					'DONE' => ($orderFields['STATUS_ID'] === OrderStatus::getFinalStatus()) ? 'Y' : 'N',
					'CANCELED' =>$orderFields['CANCELED'],
				]
			],
			'BINDINGS' => [
				['ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,	'ENTITY_ID' => $dealId]
			],
		]);

		LinkEntry::create([
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
			'ENTITY_ID' => $ownerId,
			'AUTHOR_ID' => $authorId,
			'BINDINGS' => [
				['ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,	'ENTITY_ID' => $dealId]
			]
		]);

		if ($params['IS_NEW_DEAL'] === 'Y')
		{
			ConversionEntry::create([
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $ownerId,
				'AUTHOR_ID' => $authorId,
				'SETTINGS' => [
					'ENTITIES' => [
						[
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
							'ENTITY_ID' => $dealId
						]
					]
				]
			]);
		}
		else
		{
			LinkEntry::create([
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID' => $dealId,
				'AUTHOR_ID' => $authorId,
				'BINDINGS' => [
					['ENTITY_TYPE_ID' => \CCrmOwnerType::Order,	'ENTITY_ID' => $ownerId]
				]
			]);
		}
	}

	/**
	 * @param $ownerId
	 * @param $dealId
	 * @param array $params
	 */
	public function onRebindingDeal($ownerId, $dealId, array $params)
	{
		if(!is_int($ownerId))
		{
			$ownerId = (int)$ownerId;
		}
		if($ownerId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		if(!is_int($dealId))
		{
			$dealId = (int)$dealId;
		}
		if($dealId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'dealID');
		}

		$orderFields = is_array($params['ORDER_FIELDS']) ? $params['ORDER_FIELDS'] : [];
		$authorId = self::resolveCreatorID($orderFields);
		LinkEntry::create([
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
			'ENTITY_ID' => $ownerId,
			'AUTHOR_ID' => $authorId,
			'BINDINGS' => [
				['ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,	'ENTITY_ID' => $dealId]
			]
		]);

		LinkEntry::create([
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
			'ENTITY_ID' => $dealId,
			'AUTHOR_ID' => $authorId,
			'BINDINGS' => [
				['ENTITY_TYPE_ID' => \CCrmOwnerType::Order,	'ENTITY_ID' => $ownerId]
			]
		]);
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
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'TYPE_ID' => $entryTypeID,
			],
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
			else
			{
				$row['SETTINGS'] = $settings;
				$items = array($row['ID'] => $row);
				TimelineManager::prepareDisplayData($items);
				if(Main\Loader::includeModule('pull') && \CPullOptions::GetQueueServerStatus())
				{
					$tag = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Order, $ownerID);
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

		return $result;
	}

	protected static function getEntity($ID)
	{
		$resultDB = Order::getList(
			array(
				'filter' => array('=ID' => $ID),
				'select' => array('ID', 'DATE_INSERT', 'CREATED_BY')
			)
		);
		return is_object($resultDB) ? $resultDB->fetch() : null;
	}
	protected static function resolveCreatorID(array $fields)
	{
		$authorID = 0;
		if(isset($fields['CREATED_BY']))
		{
			$authorID = (int)$fields['CREATED_BY'];
		}

		if($authorID <= 0 && isset($fields['RESPONSIBLE_ID']))
		{
			$authorID = (int)$fields['RESPONSIBLE_ID'];
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

		if(isset($fields['RESPONSIBLE_ID']))
		{
			$authorID = (int)$fields['RESPONSIBLE_ID'];
		}

		if(isset($fields['EMP_PAYED_ID']))
		{
			$authorID = (int)$fields['CREATED_BY'];
		}

		if(isset($fields['EMP_CANCELED_ID']))
		{
			$authorID = (int)$fields['CREATED_BY'];
		}

		if(isset($fields['EMP_STATUS_ID']))
		{
			$authorID = (int)$fields['EMP_STATUS_ID'];
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
		$settings = is_array($data['SETTINGS']) ? $data['SETTINGS'] : [];
		if($typeID === TimelineType::CREATION)
		{
			$fields = $settings['FIELDS'];
			$data['TITLE'] = Loc::getMessage('CRM_ORDER_CREATION');
			$title = htmlspecialcharsbx($data['ASSOCIATED_ENTITY']['TITLE']);
			if (!empty($fields['DATE_INSERT_TIMESTAMP']))
			{
				$dateInsert = \CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp($fields['DATE_INSERT_TIMESTAMP'],'SHORT'));
			}
			if (empty($dateInsert))
			{
				$dateInsert = \CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($data['DATE_INSERT']),'SHORT'));
			}

			$data['ASSOCIATED_ENTITY']['HTML_TITLE'] = Loc::getMessage(
				'CRM_ORDER_CREATION_TITLE',
					[
						'#TITLE#' => $title,
						'#DATE_INSERT#' => $dateInsert,
					]
			);
			if (!empty($fields['PRICE']) && !empty($fields['CURRENCY']))
			{
				$data['ASSOCIATED_ENTITY']['HTML_TITLE'] .= " ".Loc::getMessage(
					'CRM_ORDER_CREATION_MESSAGE_SUM',
					['#PRICE_WITH_CURRENCY#' => \CCrmCurrency::MoneyToString($fields['PRICE'], $fields['CURRENCY'])]
				);
			}
			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::MODIFICATION)
		{
			$fieldName = isset($settings['FIELD']) ? $settings['FIELD'] : '';
			if($fieldName === 'STATUS_ID')
			{
				$data['TITLE'] = Loc::getMessage('CRM_ORDER_MODIFICATION_STATUS');
				$data['START_NAME'] = isset($settings['START_NAME']) ? $settings['START_NAME'] : $settings['START'];
				$data['FINISH_NAME'] = isset($settings['FINISH_NAME']) ? $settings['FINISH_NAME'] : $settings['FINISH'];
			}
			elseif ($fieldName === 'CANCELED')
			{
				if ($settings['VALUE'] === 'Y')
				{
					$data['TITLE'] = Loc::getMessage('CRM_ORDER_MODIFICATION_CANCELED');
					$data['START_NAME'] = Loc::getMessage('CRM_ORDER_ACTIVE');
					$data['FINISH_NAME'] = Loc::getMessage('CRM_ORDER_CANCELLED');
				}
				else
				{
					$data['TITLE'] = Loc::getMessage('CRM_ORDER_MODIFICATION_RESTORED');
					$data['START_NAME'] = Loc::getMessage('CRM_ORDER_CANCELLED');
					$data['FINISH_NAME'] = Loc::getMessage('CRM_ORDER_ACTIVE');
				}
			}
			elseif ($fieldName === 'REASON_CANCELED')
			{
				$data['TITLE'] = Loc::getMessage('CRM_ORDER_MODIFICATION_REASON_CANCELED');
				if (!empty($data['COMMENT']))
				{
					$data['FINISH_NAME'] = $data['COMMENT'];
				}
			}
			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::CONVERSION)
		{
			$data['TITLE'] =  Loc::getMessage('CRM_ORDER_CONVERSION');
			$entities = isset($settings['ENTITIES']) && is_array($settings['ENTITIES'])
				? $settings['ENTITIES'] : array();

			$entityInfos = array();
			foreach($entities as $entityData)
			{
				$entityTypeID = isset($entityData['ENTITY_TYPE_ID']) ? (int)$entityData['ENTITY_TYPE_ID'] : 0;
				$entityID = isset($entityData['ENTITY_ID']) ? (int)$entityData['ENTITY_ID'] : 0;

				if(\CCrmOwnerType::TryGetEntityInfo($entityTypeID, $entityID, $entityInfo, false))
				{
					$entityInfo['ENTITY_TYPE_ID'] = $entityTypeID;
					$entityInfo['ENTITY_ID'] = $entityID;
					$entityInfos[] = $entityInfo;
				}
			}
			$data['ENTITIES'] = $entityInfos;
			unset($data['SETTINGS']);

		}
		elseif($typeID === TimelineType::MARK)
		{
			$data['MESSAGE'] = isset($settings['MESSAGE']) ? $settings['MESSAGE'] : '';
			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::ORDER)
		{
			$data['TITLE'] = \CCrmOwnerType::GetDescription(\CCrmOwnerType::Order);
			$data = array_merge($data, $settings);
			unset($data['SETTINGS']);
		}
		return parent::prepareHistoryDataModel($data, $options);
	}

	/**
	 * @param $ownerID
	 * @param $currentFields
	 * @param $previousFields
	 *
	 * @return int
	 */
	protected function onStatusModify($ownerID, $currentFields, $previousFields)
	{
		$historyEntryID = null;
		$prevStageID = isset($previousFields['STATUS_ID']) ? $previousFields['STATUS_ID'] : '';
		$currentStageID = isset($currentFields['STATUS_ID']) ? $currentFields['STATUS_ID'] : $prevStageID;

		$authorID = self::resolveEditorID($currentFields);
		if ($prevStageID !== $currentStageID)
		{
			$stageNames = OrderStatus::getListInCrmFormat();
			$historyEntryID = ModificationEntry::create(
				array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
					'ENTITY_ID' => $ownerID,
					'AUTHOR_ID' => $authorID,
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
	/** @ToDo Payment and shipment message */
	/**
	 * @param $ownerID
	 * @param $currentFields
	 * @param $previousFields
	 *
	 * @return int
	 */
	protected function onPay($ownerID, $currentFields, $previousFields)
	{
		return null;
	}
	/**
	 * @param $ownerID
	 * @param $currentFields
	 * @param $previousFields
	 *
	 * @return int
	 */
	protected function onDeduct($ownerID, $currentFields, $previousFields)
	{
		return null;
	}
}