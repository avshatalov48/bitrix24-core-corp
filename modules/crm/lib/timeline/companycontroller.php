<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Data\EntityFieldsHelper;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class CompanyController extends EntityController
{
	//region Event Names
	const ADD_EVENT_NAME = 'timeline_company_add';
	const REMOVE_EVENT_NAME = 'timeline_company_remove';
	const RESTORE_EVENT_NAME = 'timeline_company_restore';
	//endregion

	//region EntityController
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Company;
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
		if(isset($fields['LEAD_ID']) && $fields['LEAD_ID'] > 0)
		{
			$settings['BASE'] = array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
				'ENTITY_ID' => (int)$fields['LEAD_ID']
			);
		}

		$historyEntryID = CreationEntry::create(
			array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => self::resolveCreatorID($fields),
				'SETTINGS' => $settings,
				'BINDINGS' => array(
					array(
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
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

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Company, 0);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => 'timeline_company_add',
					'params' => $pushParams,
				)
			);
		}
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

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Company, 0);
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

	/**
	 * @deprecated Relation events in timeline are no longer supported
	 */
	public function onLink($ownerID, array $params)
	{
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
		if(!is_array($fields))
		{
			$fields = self::getEntity($ownerID);
		}
		if(!is_array($fields))
		{
			return;
		}

		$historyEntryID = RestorationEntry::create(
			array(
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => self::resolveCreatorID($fields),
				'SETTINGS' => array(),
				'BINDINGS' => array(
					array(
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
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

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(\CCrmOwnerType::Company, 0);
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
		if($enableCheck && TimelineEntry::isAssociatedEntityExist(\CCrmOwnerType::Company, $ownerID))
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
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => self::resolveCreatorID($fields),
				'CREATED' => isset($fields['DATE_CREATE']) ? DateTime::tryParse($fields['DATE_CREATE']) : null,
				'BINDINGS' => array(
					array(
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
						'ENTITY_ID' => $ownerID
					)
				)
			)
		);
		//endregion
		//region Register Live Feed Messages
		LiveFeed::registerEntityMessages(\CCrmOwnerType::Company, $ownerID);
		//endregion
	}
	protected static function getEntity($ID)
	{
		$dbResult = \CCrmCompany::GetListEx(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array(
				'ID', 'TITLE', 'LEAD_ID',
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
	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$typeID = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : TimelineType::UNDEFINED;
		$settings = isset($data['SETTINGS']) ? $data['SETTINGS'] : array();

		if($typeID === TimelineType::CREATION)
		{
			$data['TITLE'] =  Loc::getMessage('CRM_COMPANY_CREATION');

			$associatedEntityTypeID = isset($data['ASSOCIATED_ENTITY_TYPE_ID'])
				? (int)$data['ASSOCIATED_ENTITY_TYPE_ID'] : \CCrmOwnerType::Company;
			if($associatedEntityTypeID === \CCrmOwnerType::SuspendedCompany)
			{
				$data['LEGEND'] = Loc::getMessage('CRM_COMPANY_MOVING_TO_RECYCLEBIN');
			}
			else
			{
				$base = isset($settings['BASE']) ? $settings['BASE'] : null;
				if(is_array($base))
				{
					$entityTypeID = isset($base['ENTITY_TYPE_ID']) ? $base['ENTITY_TYPE_ID'] : 0;
					$entityID = isset($base['ENTITY_ID']) ? $base['ENTITY_ID'] : 0;
					if(\CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0)
					{
						$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
						$data['BASE'] = array('CAPTION' => Loc::getMessage("CRM_COMPANY_BASE_CAPTION_{$entityTypeName}"));
						if(\CCrmOwnerType::TryGetEntityInfo($entityTypeID, $entityID, $baseEntityInfo, false))
						{
							$data['BASE']['ENTITY_INFO'] = $baseEntityInfo;
						}
					}
				}
			}
			unset($data['SETTINGS']);
		}
		elseif($typeID === TimelineType::RESTORATION)
		{
			$data['TITLE'] =  Loc::getMessage('CRM_COMPANY_RESTORATION');
		}
		return parent::prepareHistoryDataModel($data, $options);
	}
	//endregion
}
