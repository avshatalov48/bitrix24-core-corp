<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Data\EntityFieldsHelper;
use Bitrix\Crm\History\LeadStatusHistoryEntry;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class LeadController extends EntityController
{
	//region Event Names
	const ADD_EVENT_NAME = 'timeline_lead_add';
	const REMOVE_EVENT_NAME = 'timeline_lead_remove';
	const RESTORE_EVENT_NAME = 'timeline_lead_restore';
	//endregion

	public function onConvert($ownerID, array $params)
	{
		$this->onConvertImplementation($ownerID, $params);
	}

	//region EntityController
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Lead;
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
		if (is_array($fields))
		{
			$fieldsMap = $params['FIELDS_MAP'] ?? null;
			if (is_array($fieldsMap))
			{
				$fields = EntityFieldsHelper::replaceFieldNamesByMap($fields, $fieldsMap);
			}
		}
		else
		{
			$fields = self::getEntity($ownerID);
		}

		if(!is_array($fields))
		{
			return;
		}

		$settings = array();
		if(isset($fields['SOURCE_ID']))
		{
			$settings['SOURCE_ID'] = $fields['SOURCE_ID'];
		}

		$authorID = self::resolveCreatorID($fields);
		$historyEntryID = CreationEntry::create(
			array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => $authorID,
				'SETTINGS' => $settings,
				'BINDINGS' => array(
					array(
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
						'ENTITY_ID' => $ownerID
					)
				)
			)
		);

		$enableHistoryPush = $historyEntryID > 0;
		if($enableHistoryPush && Main\Loader::includeModule('pull'))
		{
			$pushParams = array('ID' => $ownerID);
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

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Lead, 0);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => self::ADD_EVENT_NAME,
					'params' => $pushParams,
				)
			);
		}

		$this->createManualOpportunityModificationEntryIfNeeded($ownerID, $authorID, $fields);
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

		$fieldsMap = $params['FIELDS_MAP'] ?? null;
		if (is_array($fieldsMap))
		{
			$currentFields = EntityFieldsHelper::replaceFieldNamesByMap($currentFields, $fieldsMap);
			$previousFields = EntityFieldsHelper::replaceFieldNamesByMap($previousFields, $fieldsMap);
		}

		$prevStatusID = isset($previousFields['STATUS_ID']) ? $previousFields['STATUS_ID'] : '';
		$curStatusID = isset($currentFields['STATUS_ID']) ? $currentFields['STATUS_ID'] : $prevStatusID;

		$authorID = self::resolveEditorID($currentFields);

		$historyEntryID = 0;
		if($prevStatusID !== $curStatusID)
		{
			$statusNames = \CCrmLead::GetStatusNames();
			$historyEntryID = ModificationEntry::create(
				array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
					'ENTITY_ID' => $ownerID,
					'AUTHOR_ID' => $authorID,
					'SETTINGS' => array(
						'FIELD' => 'STATUS_ID',
						'START' => $prevStatusID,
						'FINISH' => $curStatusID,
						'START_NAME' => isset($statusNames[$prevStatusID]) ? $statusNames[$prevStatusID] : $prevStatusID,
						'FINISH_NAME' => isset($statusNames[$curStatusID]) ? $statusNames[$curStatusID] : $curStatusID
					)
				)
			);
		}
		$this->sendPullEventOnAdd(new \Bitrix\Crm\ItemIdentifier(\CCrmOwnerType::Lead, $ownerID), $historyEntryID);

		$this->createManualOpportunityModificationEntryIfNeeded($ownerID, $authorID, $currentFields, $previousFields);
	}
	public function onDelete($ownerID, array $params)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		if(Main\Loader::includeModule('pull'))
		{
			$pushParams = array('ID' => $ownerID);

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Lead, 0);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => self::REMOVE_EVENT_NAME,
					'params' => $pushParams,
				)
			);
		}
	}
	public function onRestore($ownerID, array $params)
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
		if (is_array($fields))
		{
			$fieldsMap = $params['FIELDS_MAP'] ?? null;
			if (is_array($fieldsMap))
			{
				$fields = EntityFieldsHelper::replaceFieldNamesByMap($fields, $fieldsMap);
			}
		}
		else
		{
			$fields = self::getEntity($ownerID);
		}
		if(!is_array($fields))
		{
			return;
		}

		$historyEntryID = RestorationEntry::create([
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
			'ENTITY_ID' => $ownerID,
			'SETTINGS' => [],
			'BINDINGS' => [
				[
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
					'ENTITY_ID' => $ownerID,
				]
			],
		]);

		$enableHistoryPush = $historyEntryID > 0;
		if($enableHistoryPush && Main\Loader::includeModule('pull'))
		{
			$pushParams = array('ID' => $ownerID);
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

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Lead, 0);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => self::RESTORE_EVENT_NAME,
					'params' => $pushParams,
				)
			);
		}
	}

	public function getSupportedPullCommands()
	{
		return array(
			'add' => self::ADD_EVENT_NAME,
			'remove' => self::REMOVE_EVENT_NAME,
			'restore' => self::RESTORE_EVENT_NAME
		);
	}

	/**
	 * Register existed entity in retrospect mode.
	 * @param int $ownerID Entity ID
	 * @return void
	 */
	public function register($ownerID, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$enableCheck = isset($options['EXISTS_CHECK']) ? (bool)$options['EXISTS_CHECK'] : true;
		if($enableCheck && TimelineEntry::isAssociatedEntityExist(\CCrmOwnerType::Lead, $ownerID))
		{
			return;
		}

		$fields = self::getEntity($ownerID);
		if(!is_array($fields))
		{
			return;
		}

		//region Register Creation
		CreationEntry::create(
			array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => self::resolveCreatorID($fields),
				'CREATED' => isset($fields['DATE_CREATE']) ? DateTime::tryParse($fields['DATE_CREATE']) : null,
				'BINDINGS' => array(
					array(
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
						'ENTITY_ID' => $ownerID
					)
				)
			)
		);
		//endregion
		//region Register Status History
		$authorID = self::resolveEditorID($fields);
		$historyItems = LeadStatusHistoryEntry::getAll($ownerID);
		if(count($historyItems) > 1)
		{
			$initialItem = array_shift($historyItems);
			$statusNames = \CCrmLead::GetStatusNames();
			$prevStatusID = isset($initialItem['STATUS_ID']) ? $initialItem['STATUS_ID'] : '';
			foreach($historyItems as $item)
			{
				$curStatusID = isset($item['STATUS_ID']) ? $item['STATUS_ID'] : '';
				if($curStatusID === '')
				{
					continue;
				}

				if($prevStatusID !== '')
				{
					ModificationEntry::create(
						array(
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
							'ENTITY_ID' => $ownerID,
							'AUTHOR_ID' => $authorID,
							'SETTINGS' => array(
								'FIELD' => 'STATUS_ID',
								'START' => $prevStatusID,
								'FINISH' => $curStatusID,
								'START_NAME' => isset($statusNames[$prevStatusID]) ? $statusNames[$prevStatusID] : $prevStatusID,
								'FINISH_NAME' => isset($statusNames[$curStatusID]) ? $statusNames[$curStatusID] : $curStatusID
							)
						)
					);
				}
				$prevStatusID = $curStatusID;
			}
		}
		//endregion
		//region Register Live Feed Messages
		LiveFeed::registerEntityMessages(\CCrmOwnerType::Lead, $ownerID);
		//endregion
	}
	protected static function getEntity($ID)
	{
		$dbResult = \CCrmLead::GetListEx(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array(
				'ID', 'TITLE', 'STATUS_ID',
				'DATE_CREATE', 'DATE_MODIFY',
				'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID'
			)
		);
		return is_object($dbResult) ? $dbResult->Fetch() : null;
	}
	protected static function resolveCreatorID(array $fields)
	{
		$authorID = 0;
		if(isset($fields['CREATED_BY_ID']))
		{
			$authorID = (int)$fields['CREATED_BY_ID'];
		}

		if($authorID <= 0 && isset($fields['MODIFY_BY_ID']))
		{
			$authorID = (int)$fields['MODIFY_BY_ID'];
		}

		if($authorID <= 0 && isset($fields['ASSIGNED_BY_ID']))
		{
			$authorID = (int)$fields['ASSIGNED_BY_ID'];
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
		if(isset($fields['MODIFY_BY_ID']))
		{
			$authorID = (int)$fields['MODIFY_BY_ID'];
		}

		if($authorID <= 0 && isset($fields['CREATED_BY_ID']))
		{
			$authorID = (int)$fields['CREATED_BY_ID'];
		}

		if($authorID <= 0 && isset($fields['ASSIGNED_BY_ID']))
		{
			$authorID = (int)$fields['ASSIGNED_BY_ID'];
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
		$settings = isset($data['SETTINGS']) ? $data['SETTINGS'] : array();

		if($typeID === TimelineType::CREATION)
		{
			$data['TITLE'] =  Loc::getMessage('CRM_LEAD_CREATION');

			$associatedEntityTypeID = isset($data['ASSOCIATED_ENTITY_TYPE_ID'])
				? (int)$data['ASSOCIATED_ENTITY_TYPE_ID'] : \CCrmOwnerType::Lead;
			if($associatedEntityTypeID === \CCrmOwnerType::SuspendedLead)
			{
				$data['LEGEND'] = Loc::getMessage('CRM_LEAD_MOVING_TO_RECYCLEBIN');
			}
			else
			{
				$sourceID = (isset($settings['SOURCE_ID']) && is_string($settings['SOURCE_ID'])) ? $settings['SOURCE_ID'] : '';
				if($sourceID !== '')
				{
					$sourceList = \CCrmStatus::GetStatusList('SOURCE');
					if(isset($sourceList[$sourceID]))
					{
						$data['LEGEND'] = \CCrmLead::GetFieldCaption('SOURCE_ID').': '.$sourceList[$sourceID];
					}
				}
			}
			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::MODIFICATION)
		{
			$fieldName = isset($settings['FIELD']) ? $settings['FIELD'] : '';
			if($fieldName === 'STATUS_ID')
			{
				$data['TITLE'] =  Loc::getMessage('CRM_LEAD_MODIFICATION_STATUS_MSGVER_1');
				$data['START_NAME'] = isset($settings['START_NAME']) ? $settings['START_NAME'] : $settings['START'];
				$data['FINISH_NAME'] = isset($settings['FINISH_NAME']) ? $settings['FINISH_NAME'] : $settings['FINISH'];
			}
			if($fieldName === 'IS_MANUAL_OPPORTUNITY')
			{
				$data['TITLE'] =  Loc::getMessage('CRM_LEAD_MODIFICATION_IS_MANUAL_OPPORTUNITY');
				$data['START_NAME'] = isset($settings['START_NAME']) ? $settings['START_NAME'] : $settings['START'];
				$data['FINISH_NAME'] = isset($settings['FINISH_NAME']) ? $settings['FINISH_NAME'] : $settings['FINISH'];
				$data['START'] = $settings['START'];
				$data['FINISH'] = $settings['FINISH'];
			}
			$data['MODIFIED_FIELD'] = $fieldName;
			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::CONVERSION)
		{
			$data['TITLE'] =  Loc::getMessage('CRM_LEAD_CONVERSION');
			$entities = isset($settings['ENTITIES']) && is_array($settings['ENTITIES'])
				? $settings['ENTITIES'] : array();

			$entityInfos = array();
			foreach($entities as $entityData)
			{
				$entityTypeID = isset($entityData['ENTITY_TYPE_ID']) ? (int)$entityData['ENTITY_TYPE_ID'] : 0;
				$entityID = isset($entityData['ENTITY_ID']) ? (int)$entityData['ENTITY_ID'] : 0;

				if(\CCrmOwnerType::IsDefined($entityTypeID))
				{
					\CCrmOwnerType::TryGetEntityInfo($entityTypeID, $entityID, $entityInfo, false);
					$entityInfo['ENTITY_TYPE_ID'] = $entityTypeID;
					$entityInfo['ENTITY_ID'] = $entityID;
					$entityInfos[] = $entityInfo;
				}

			}
			$data['ENTITIES'] = $entityInfos;
			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::RESTORATION)
		{
			$data['TITLE'] =  Loc::getMessage('CRM_LEAD_RESTORATION');
		}
		return parent::prepareHistoryDataModel($data, $options);
	}
	//endregion
}
