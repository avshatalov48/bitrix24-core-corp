<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm;
use Bitrix\Crm\Pseudoactivity\WaitEntry;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Main;

class WaitController extends EntityController
{
	/** @var int|null  */
	private static $userID = null;
	/** @var  \CCrmPerms|null */
	private static $userPermissions = null;

	//region EntityController
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Wait;
	}

	public function onCreate($ID, array $params)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}
		if($ID <= 0)
		{
			throw new Main\ArgumentException('ID must be greater than zero.', 'ID');
		}

		$fields = isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : null;
		if(!is_array($fields))
		{
			$fields = WaitEntry::getByID($ID);
		}
		if(!is_array($fields))
		{
			return;
		}

		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? (int)$fields['OWNER_TYPE_ID'] : 0;
		$ownerID = isset($fields['OWNER_ID']) ? (int)$fields['OWNER_ID'] : 0;

		if(Main\Loader::includeModule('pull'))
		{
			$pushParams = array(
				'ENTITY' => self::prepareEntityDataModel($ID, $fields),
				'SCHEDULE_ITEM' => self::prepareScheduleDataModel(
					$fields,
					array('ENABLE_USER_INFO' => true)
				)
			);

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag($ownerTypeID, $ownerID);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => 'timeline_wait_add',
					'params' => $pushParams,
				)
			);
		}
	}
	public function onModify($ID, array $params)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}
		if($ID <= 0)
		{
			throw new Main\ArgumentException('ID must be greater than zero.', 'ID');
		}

		$currentFields = isset($params['CURRENT_FIELDS']) && is_array($params['CURRENT_FIELDS'])
			? $params['CURRENT_FIELDS'] : array();
		$previousFields = isset($params['PREVIOUS_FIELDS']) && is_array($params['PREVIOUS_FIELDS'])
			? $params['PREVIOUS_FIELDS'] : array();

		if(Main\Loader::includeModule('pull'))
		{
			$ownerTypeID = isset($previousFields['OWNER_TYPE_ID']) ? (int)$previousFields['OWNER_TYPE_ID'] : 0;
			$ownerID = isset($previousFields['OWNER_ID']) ? (int)$previousFields['OWNER_ID'] : 0;

			$historyEntryID = 0;
			if(isset($currentFields['COMPLETED']))
			{
				$curCompleted = $currentFields['COMPLETED'] === 'Y';
				$prevCompleted = isset($previousFields['COMPLETED']) && $previousFields['COMPLETED'] === 'Y';
				if(!$prevCompleted && $curCompleted)
				{
					$authorID = isset($params['USER_ID']) && $params['USER_ID'] > 0
						? (int)$params['USER_ID']
						: \CCrmSecurityHelper::GetCurrentUserID();

					$historyEntryID = \Bitrix\Crm\Timeline\WaitEntry::create(
						array(
							'ENTITY_ID' => $ID,
							'AUTHOR_ID' => $authorID,
							'BINDINGS' => array(
								array(
									'ENTITY_TYPE_ID' => $ownerTypeID,
									'ENTITY_ID' => $ownerID
								)
							)
						)
					);
				}
			}

			$pushParams = array(
				'ENTITY' => self::prepareEntityDataModel($ID, $currentFields),
				'SCHEDULE_ITEM' => self::prepareScheduleDataModel(
					$currentFields,
					array('ENABLE_USER_INFO' => true)
				)
			);

			if($historyEntryID > 0)
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

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag($ownerTypeID, $ownerID);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => 'timeline_wait_update',
					'params' => $pushParams,
				)
			);
		}
	}
	public function onDelete($ownerID, array $params)
	{
	}
	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		return parent::prepareHistoryDataModel($data, $options);
	}
	public function prepareSearchContent(array $params)
	{
		$assocEntityID = isset($params['ASSOCIATED_ENTITY_ID']) ? (int)$params['ASSOCIATED_ENTITY_ID'] : 0;
		if($assocEntityID <= 0)
		{
			return '';
		}

		$result = '';

		$associatedEntityFields = Crm\Pseudoactivity\Entity\WaitTable::getById($assocEntityID)->fetch();
		if(is_array($associatedEntityFields))
		{
			$fields = self::prepareEntityDataModel(
				$assocEntityID,
				$associatedEntityFields
			);

			if(isset($fields['DESCRIPTION_RAW']))
			{
				$result = $fields['DESCRIPTION_RAW'];
			}
		}
		return $result;
	}
	//endregion

	public static function getUsePermissions()
	{
		if(self::$userPermissions === null)
		{
			self::$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}
		return self::$userPermissions;
	}

	public static function getUserID()
	{
		if(self::$userID === null)
		{
			self::$userID  = \CCrmSecurityHelper::GetCurrentUserID();
		}
		return self::$userID;
	}

	public static function prepareEntityDataModel($ID, array $fields, array $options = null)
	{
		$description = isset($fields['DESCRIPTION']) ? $fields['DESCRIPTION'] : '';
		$fields['DESCRIPTION_BBCODE'] = '';
		$fields['DESCRIPTION_HTML'] = preg_replace("/[\r\n]+/u", "<br/>", htmlspecialcharsbx($description));
		$fields['DESCRIPTION_RAW'] = $description;
		unset($fields['DESCRIPTION']);

		return $fields;
	}

	public static function prepareScheduleDataModel(array $data, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$description = isset($data['DESCRIPTION']) ? $data['DESCRIPTION'] : '';
		$data['DESCRIPTION_BBCODE'] = '';
		$data['DESCRIPTION_HTML'] = preg_replace("/[\r\n]+/u", "<br/>", htmlspecialcharsbx($description));
		$data['DESCRIPTION_RAW'] = $description;

		if(isset($data['END_TIME']))
		{
			$deadlineTimestamp = MakeTimeStamp($data['END_TIME']) - \CTimeZone::GetOffset();
			$data['DEADLINE_SERVER'] = date(
				'Y-m-d H:i:s',
				$deadlineTimestamp
			);
			$sort = [$deadlineTimestamp, (int)$data['ID']];
		}
		else
		{
			$sort = [PHP_INT_MAX, (int)$data['ID']];
		}

		$ownerTypeID = isset($data['OWNER_TYPE_ID']) ? (int)$data['OWNER_TYPE_ID'] : 0;
		$ownerID = isset($data['OWNER_ID']) ? (int)$data['OWNER_ID'] : 0;
		$canUpdate = EntityAuthorization::checkUpdatePermission($ownerTypeID, $ownerID, self::getUsePermissions());

		$data['PERMISSIONS'] = array(
			'USER_ID' => self::getUserID(),
			'POSTPONE' => $canUpdate,
			'COMPLETE' => $canUpdate
		);

		$model = array(
			'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Wait,
			'ASSOCIATED_ENTITY_ID' => isset($data['ID']) ? (int)$data['ID'] : 0,
			'ASSOCIATED_ENTITY' => $data,
			'AUTHOR_ID' => isset($data['AUTHOR_ID']) ? (int)$data['AUTHOR_ID'] : 0,
			'sort' => $sort,
		);

		if(isset($options['ENABLE_USER_INFO']) && $options['ENABLE_USER_INFO'] === true)
		{
			self::prepareAuthorInfo($model);
		}
		return $model;
	}
}
