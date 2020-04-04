<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\Activity;
use Bitrix\Crm\Integration;

Loc::loadMessages(__FILE__);

class ActivityController extends EntityController
{
	/** @var \CTextParser|null  */
	private static $parser = null;
	/** @var int|null  */
	private static $userID = null;
	/** @var  \CCrmPerms|null */
	private static $userPermissions = null;

	//region Singleton
	/** @var ActivityController|null */
	protected static $instance = null;
	/**
	 * @return ActivityController
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new ActivityController();
		}
		return self::$instance;
	}
	//endregion

	//region EntityController
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Activity;
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

		$bindings = \CCrmActivity::GetBindings($ownerID);
		if(!(is_array($bindings) && !empty($bindings)))
		{
			return;
		}

		$status = isset($fields['STATUS']) ? (int)$fields['STATUS'] : \CCrmActivityStatus::Undefined;
		$typeID = isset($fields['TYPE_ID']) ? (int)$fields['TYPE_ID'] : \CCrmActivityType::Undefined;
		$direction = isset($fields['DIRECTION']) ? (int)$fields['DIRECTION'] : \CCrmActivityDirection::Undefined;
		$providerID = isset($fields['PROVIDER_ID']) ? $fields['PROVIDER_ID'] : '';
		$authorID = self::resolveAuthorID($fields);

		$created = null;
		if(isset($params['PRESERVE_CREATION_TIME'])
			&& $params['PRESERVE_CREATION_TIME'] === true
			&& isset($fields['CREATED'])
		)
		{
			if($fields['CREATED'] instanceof DateTime)
			{
				$created = $fields['CREATED'];
			}
			else
			{
				$created = new DateTime($fields['CREATED'], Date::convertFormatToPhp(FORMAT_DATETIME));
			}
		}

		$historyEntryID = 0;
		if($typeID === \CCrmActivityType::Email)
		{
			//We must register all incoming emails and completed outcoming emails.
			if($status === \CCrmActivityStatus::Completed || $direction === \CCrmActivityDirection::Incoming)
			{
				$historyEntryID = ActivityEntry::create(
					array(
						'ACTIVITY_TYPE_ID' => $typeID,
						'ACTIVITY_PROVIDER_ID' => $providerID,
						'ENTITY_ID' => $ownerID,
						'AUTHOR_ID' => $authorID,
						'CREATED' => $created,
						'BINDINGS' => self::mapBindings($bindings)
					)
				);
			}
		}
		elseif($typeID === \CCrmActivityType::Call || $typeID === \CCrmActivityType::Meeting)
		{
			if($status === \CCrmActivityStatus::Completed)
			{
				$historyEntryID = ActivityEntry::create(
					array(
						'ACTIVITY_TYPE_ID' => $typeID,
						'ACTIVITY_PROVIDER_ID' => $providerID,
						'ENTITY_ID' => $ownerID,
						'AUTHOR_ID' => $authorID,
						'CREATED' => $created,
						'BINDINGS' => self::mapBindings($bindings)
					)
				);
			}
		}
		elseif($typeID === \CCrmActivityType::Task)
		{
			$historyEntryID = CreationEntry::create(
				array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
					'ENTITY_CLASS_NAME' => $providerID,
					'ENTITY_ID' => $ownerID,
					'AUTHOR_ID' => $authorID,
					'CREATED' => $created,
					'BINDINGS' => self::mapBindings($bindings)
				)
			);
		}
		elseif($typeID === \CCrmActivityType::Provider
			&& $status === \CCrmActivityStatus::Completed
			&& isset($fields['PROVIDER_ID'])
			&& self::isActivityProviderSupported($fields['PROVIDER_ID'])
		)
		{
			$historyEntryID = ActivityEntry::create(
				array(
					'ACTIVITY_TYPE_ID' => $typeID,
					'ACTIVITY_PROVIDER_ID' => $providerID,
					'ENTITY_ID' => $ownerID,
					'AUTHOR_ID' => $authorID,
					'CREATED' => $created,
					'BINDINGS' => self::mapBindings($bindings)
				)
			);
		}

		if(isset($params['ENABLE_PUSH']) && $params['ENABLE_PUSH'] === false)
		{
			return;
		}

		$enableHistoryPush = $historyEntryID > 0;
		$enableSchedulePush = self::isActivitySupported($fields) && $status === \CCrmActivityStatus::Waiting;

		if(($enableHistoryPush || $enableSchedulePush) && Main\Loader::includeModule('pull'))
		{
			$modelData = self::prepareEntityDataModel($ownerID, $fields);
			$pushParams = array('ENTITY' => $modelData);
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
			if($enableSchedulePush)
			{
				$pushParams['SCHEDULE_ITEM'] = self::prepareScheduleDataModel(
					$fields,
					array('ENABLE_USER_INFO' => true, 'ENABLE_MULTIFIELD_INFO' => true)
				);
			}

			foreach($bindings as $binding)
			{
				$tag = TimelineEntry::prepareEntityPushTag($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']);
				\CPullWatch::AddToStack(
					$tag,
					array(
						'module_id' => 'crm',
						'command' => 'timeline_activity_add',
						'params' => array_merge($pushParams, array('TAG' => $tag)),
					)
				);
			}
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
		$currentBindings = isset($params['CURRENT_BINDINGS']) && is_array($params['CURRENT_BINDINGS'])
			? $params['CURRENT_BINDINGS'] : array();
		$previousFields = isset($params['PREVIOUS_FIELDS']) && is_array($params['PREVIOUS_FIELDS'])
			? $params['PREVIOUS_FIELDS'] : array();

		$typeID = isset($currentFields['TYPE_ID']) ? (int)$currentFields['TYPE_ID'] : \CCrmActivityType::Undefined;
		$providerID = isset($currentFields['PROVIDER_ID']) ? $currentFields['PROVIDER_ID'] : '';
		$prevCompleted = isset($previousFields['COMPLETED']) && $previousFields['COMPLETED'] === 'Y';
		$curCompleted = isset($currentFields['COMPLETED']) && $currentFields['COMPLETED'] === 'Y';

		$authorID = self::resolveAuthorID($currentFields);

		$historyEntryID = 0;
		if(!$prevCompleted && $curCompleted)
		{
			if($typeID == \CCrmActivityType::Email)
			{
				//Email: Add success event only if there is no child emails
				if(!\CAllCrmActivity::HasChildren($ownerID))
				{
					$historyEntryID = MarkEntry::create(
						array(
							'MARK_TYPE_ID' => TimelineMarkType::SUCCESS,
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
							'ENTITY_CLASS_NAME' => $providerID,
							'ENTITY_ID' => $ownerID,
							'AUTHOR_ID' => $authorID,
							'BINDINGS' => self::mapBindings($currentBindings),
							'SETTINGS' => array('IS_REPLIED' => false)
						)
					);
				}
			}
			else if($typeID == \CCrmActivityType::Task)
			{
				$historyEntryID = MarkEntry::create(
					array(
						'MARK_TYPE_ID' => TimelineMarkType::SUCCESS,
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
						'ENTITY_CLASS_NAME' => $providerID,
						'ENTITY_ID' => $ownerID,
						'AUTHOR_ID' => $authorID,
						'BINDINGS' => self::mapBindings($currentBindings)
					)
				);
			}
			else
			{
				$historyEntryID = ActivityEntry::create(
					array(
						'ACTIVITY_TYPE_ID' => $typeID,
						'ACTIVITY_PROVIDER_ID' => $providerID,
						'ENTITY_ID' => $ownerID,
						'AUTHOR_ID' => $authorID,
						'BINDINGS' => self::mapBindings($currentBindings)
					)
				);
			}
		}
		elseif($prevCompleted && !$curCompleted)
		{
			//Add Renew event
			$historyEntryID = MarkEntry::create(
				array(
					'MARK_TYPE_ID' => TimelineMarkType::RENEW,
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
					'ENTITY_CLASS_NAME' => $providerID,
					'ENTITY_ID' => $ownerID,
					'AUTHOR_ID' => $authorID,
					'BINDINGS' => self::mapBindings($currentBindings)
				)
			);
		}

		$enableHistoryPush = $historyEntryID > 0;
		$enableSchedulePush = self::isActivitySupported($currentFields);
		$enableEntityPush = TimelineEntry::isAssociatedEntityExist(\CCrmOwnerType::Activity, $ownerID);

		if(($enableHistoryPush || $enableSchedulePush || $enableEntityPush)
			&& Main\Loader::includeModule('pull')
		)
		{
			$modelData = self::prepareEntityDataModel($ownerID, $currentFields);
			$pushParams = array('ENTITY' => $modelData);
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
			if($enableSchedulePush)
			{
				$pushParams['SCHEDULE_ITEM'] = self::prepareScheduleDataModel(
					$currentFields,
					array('ENABLE_USER_INFO' => true, 'ENABLE_MULTIFIELD_INFO' => true)
				);
			}

			foreach($currentBindings as $binding)
			{
				$tag = TimelineEntry::prepareEntityPushTag($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']);
				\CPullWatch::AddToStack(
					$tag,
					array(
						'module_id' => 'crm',
						'command' => 'timeline_activity_update',
						'params' => array_merge($pushParams, array('TAG' => $tag)),
					)
				);
			}
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

		$movedToRecycleBin = isset($params['MOVED_TO_RECYCLE_BIN']) && $params['MOVED_TO_RECYCLE_BIN'];
		if(!$movedToRecycleBin)
		{
			TimelineEntry::deleteByAssociatedEntity(\CCrmOwnerType::Activity, $ownerID);
		}

		$bindings = isset($params['BINDINGS']) && is_array($params['BINDINGS']) ? $params['BINDINGS'] : array();
		if(!empty($bindings) && Main\Loader::includeModule('pull'))
		{
			foreach($bindings as $binding)
			{
				$tag = TimelineEntry::prepareEntityPushTag($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']);
				\CPullWatch::AddToStack(
					$tag,
					array(
						'module_id' => 'crm',
						'command' => 'timeline_activity_delete',
						'params' => array('ENTITY_ID' => $ownerID, 'TAG' => $tag),
					)
				);
			}
		}
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
		if($enableCheck && TimelineEntry::isAssociatedEntityExist(\CCrmOwnerType::Activity, $ownerID))
		{
			return;
		}

		self::onCreate(
			$ownerID,
			array('ENABLE_PUSH' => false, 'PRESERVE_CREATION_TIME' => true)
		);
	}

	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$assocEntityTypeID = isset($data['ASSOCIATED_ENTITY_TYPE_ID']) ? (int)$data['ASSOCIATED_ENTITY_TYPE_ID'] : 0;
		if($assocEntityTypeID === \CCrmOwnerType::Activity)
		{
			$typeID = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : 0;
			$typeCategoryID = isset($data['TYPE_CATEGORY_ID']) ? (int)$data['TYPE_CATEGORY_ID'] : 0;
			$settings = isset($data['SETTINGS']) && is_array($data['SETTINGS']) ? $data['SETTINGS'] : array();

			if($typeID === TimelineType::MARK && $typeCategoryID === TimelineMarkType::SUCCESS)
			{
				$isReplied = null;
				if(isset($settings['IS_REPLIED']))
				{
					$isReplied = (bool)$settings['IS_REPLIED'];
				}
				else
				{
					$assocEntity = isset($data['ASSOCIATED_ENTITY']) && is_array($data['ASSOCIATED_ENTITY'])
						? $data['ASSOCIATED_ENTITY'] : array();

					$activityTypeID = isset($assocEntity['TYPE_ID']) ? (int)$assocEntity['TYPE_ID'] : 0;
					$activityDirection = isset($assocEntity['DIRECTION']) ? (int)$assocEntity['DIRECTION'] : 0;
					if($activityTypeID === \CCrmActivityType::Email
						&& $activityDirection === \CCrmActivityDirection::Incoming
					)
					{
						$isReplied = isset($settings['IS_REPLIED']) && $settings['IS_REPLIED'];
					}
				}

				if($isReplied === false)
				{
					$data['SUMMARY'] = Loc::getMessage('CRM_ACTIVITY_EMAIL_SKIPPED');
				}
			}
		}
		return parent::prepareHistoryDataModel($data, $options);
	}
	public function prepareSearchContent(array $params)
	{
		$assocEntityTypeID = isset($params['ASSOCIATED_ENTITY_TYPE_ID']) ? (int)$params['ASSOCIATED_ENTITY_TYPE_ID'] : 0;
		$assocEntityID = isset($params['ASSOCIATED_ENTITY_ID']) ? (int)$params['ASSOCIATED_ENTITY_ID'] : 0;
		if($assocEntityTypeID === \CCrmOwnerType::Activity && $assocEntityID > 0)
		{
			$builder = new Crm\Search\ActivitySearchContentBuilder();
			return Crm\Search\SearchEnvironment::prepareToken(
				$builder->getSearchContent($assocEntityID, array('skipEntityId' => true))
			);
		}
		return '';
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

	public static function isActivitySupported(array $fields)
	{
		$typeID = isset($fields['TYPE_ID']) ? (int)$fields['TYPE_ID'] : \CCrmActivityType::Undefined;
		if($typeID === \CCrmActivityType::Email
			|| $typeID === \CCrmActivityType::Call
			|| $typeID === \CCrmActivityType::Meeting
			|| $typeID === \CCrmActivityType::Task
		)
		{
			return true;
		}

		if($typeID === \CCrmActivityType::Provider)
		{
			$providerID = isset($fields['PROVIDER_ID']) ? $fields['PROVIDER_ID'] : '';
			if($providerID === Activity\Provider\WebForm::getId()
				|| $providerID === Activity\Provider\Wait::getId()
				|| $providerID === Activity\Provider\Request::getId()
				|| $providerID === Activity\Provider\OpenLine::getId()
				|| $providerID === Activity\Provider\RestApp::getId()
			)
			{
				return true;
			}
		}

		return false;
	}
	protected static function isActivityProviderSupported($providerID)
	{
		return(
			$providerID === Activity\Provider\WebForm::getId()
			|| $providerID === Activity\Provider\Wait::getId()
			|| $providerID === Activity\Provider\Request::getId()
			|| $providerID === Activity\Provider\OpenLine::getId()
			|| $providerID === Activity\Provider\Sms::getId()
			|| $providerID === Activity\Provider\RestApp::getId()
			|| $providerID === Activity\Provider\Visit::getId()
		);
	}

	public static function synchronizeBindings($ownerID, array $bindings)
	{
		TimelineEntry::synchronizeAssociatedEntityBindings(
			\CCrmOwnerType::Activity,
			$ownerID,
			self::mapBindings($bindings)
		);
	}
	//region Preparation of Display Data
	public static function prepareScheduleDataModel(array $data, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$permissions = self::getUsePermissions();

		\CCrmActivity::PrepareDescriptionFields(
			$data,
			array('ENABLE_HTML' => false, 'ENABLE_BBCODE' => false, 'LIMIT' => 512)
		);

		if(isset($data['DEADLINE']))
		{
			if(isset($data['DEADLINE']) && \CCrmDateTimeHelper::IsMaxDatabaseDate($data['DEADLINE']))
			{
				unset($data['DEADLINE']);
			}
			else
			{
				$data['DEADLINE_SERVER'] = date(
					'Y-m-d H:i:s',
					MakeTimeStamp($data['DEADLINE']) - \CTimeZone::GetOffset()
				);
			}
		}

		$ID = isset($data['ID']) ? (int)$data['ID'] : 0;
		if($ID > 0)
		{
			$communications = \CCrmActivity::PrepareCommunicationInfos(array($ID));
			if(isset($communications[$ID]))
			{
				$data['COMMUNICATION'] = $communications[$ID];
			}
		}

		$data['PERMISSIONS'] = array(
			'USER_ID' => self::getUserID(),
			'POSTPONE' => \CCrmActivity::CheckItemPostponePermission($data, $permissions),
			'COMPLETE' => \CCrmActivity::CheckItemCompletePermission($data, $permissions)
		);

		$typeID = isset($data['TYPE_ID']) ? $data['TYPE_ID'] : '';
		$providerID = isset($data['PROVIDER_ID']) ? $data['PROVIDER_ID'] : '';

		if($typeID === \CCrmActivityType::Call && $providerID === Activity\Provider\Call::ACTIVITY_PROVIDER_ID)
		{
			//LIKE VI_b298cc809d17d8ae.1475506018.843270c
			$originID = isset($data['ORIGIN_ID']) ? $data['ORIGIN_ID'] : '';
			if(strpos($originID, 'VI_') !== false)
			{
				$callId = substr($originID, 3);
				$callInfo = Integration\VoxImplantManager::getCallInfo($callId);
				if(is_array($callInfo))
				{
					$data['CALL_INFO'] = $callInfo;
				}
			}
		}
		else if($providerID === \Bitrix\Crm\Activity\Provider\OpenLine::getId())
		{
			$sessionID = isset($data['ASSOCIATED_ENTITY_ID']) ? (int)$data['ASSOCIATED_ENTITY_ID'] : 0;
			if($sessionID > 0)
			{
				$data['OPENLINE_INFO'] = array(
					'MESSAGES' => \Bitrix\Crm\Integration\OpenLineManager::getSessionMessages($sessionID, 3)
				);
			}
		}
		else if($providerID === \Bitrix\Crm\Activity\Provider\RestApp::getId())
		{
			$appTypeInfo = Activity\Provider\RestApp::getTypeInfo(
				$data['ASSOCIATED_ENTITY_ID'], $data['PROVIDER_TYPE_ID']
			);

			if ($appTypeInfo && $appTypeInfo['ICON_ID'] > 0)
			{
				$icon = \CFile::ResizeImageGet(
					$appTypeInfo['ICON_ID'],
					array('width' => 32, 'height' => 32),
					BX_RESIZE_IMAGE_EXACT
				);
				if ($icon)
				{
					$appTypeInfo['ICON_SRC'] = $icon['src'];
				}
			}
			$data['APP_TYPE'] = $appTypeInfo;
		}

		$model = array(
			'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
			'ASSOCIATED_ENTITY_ID' => $ID,
			'ASSOCIATED_ENTITY' => $data,
			'AUTHOR_ID' => isset($data['RESPONSIBLE_ID']) ? (int)$data['RESPONSIBLE_ID'] : 0
		);

		if(isset($options['ENABLE_USER_INFO']) && $options['ENABLE_USER_INFO'] === true)
		{
			self::prepareAuthorInfo($model);
		}

		if(isset($options['ENABLE_MULTIFIELD_INFO']) && $options['ENABLE_MULTIFIELD_INFO'] === true)
		{
			self::prepareMultiFieldInfo($model);
		}

		return $model;
	}
	public static function prepareEntityDataModel($ID, array $fields, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$typeID = isset($fields['TYPE_ID']) ? (int)$fields['TYPE_ID'] : 0;
		$providerID = isset($fields['PROVIDER_ID']) ? $fields['PROVIDER_ID'] : '';

		\CCrmActivity::PrepareDescriptionFields(
			$fields,
			array('ENABLE_HTML' => false, 'ENABLE_BBCODE' => false, 'LIMIT' => 512)
		);

		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? (int)$fields['OWNER_TYPE_ID'] : \CCrmOwnerType::Undefined;
		$ownerID = isset($fields['OWNER_ID']) ? (int)$fields['OWNER_ID'] : 0;
		if($ownerTypeID !== \CCrmOwnerType::Undefined
			&& $ownerID > 0
			&& \CCrmOwnerType::TryGetEntityInfo($ownerTypeID, $ownerID, $ownerInfo, false)
		)
		{
			$fields['OWNER_TITLE'] = isset($ownerInfo['TITLE']) ? $ownerInfo['TITLE'] : '';
			$fields['OWNER_URL'] = isset($ownerInfo['SHOW_URL']) ? $ownerInfo['SHOW_URL'] : '';
		}

		if(!isset($options['ENABLE_COMMUNICATIONS']) || $options['ENABLE_COMMUNICATIONS'])
		{
			$communications = \CCrmActivity::PrepareCommunicationInfos(array($ID));
			if(isset($communications[$ID]))
			{
				$fields['COMMUNICATION'] = $communications[$ID];
			}
		}
		if($typeID === \CCrmActivityType::Email)
		{
			$emailInfo = Activity\Provider\Email::prepareEmailInfo($fields);
			if(is_array($emailInfo))
			{
				$fields['EMAIL_INFO'] = $emailInfo;
			}
		}
		if($typeID === \CCrmActivityType::Call && $providerID === Activity\Provider\Call::ACTIVITY_PROVIDER_ID)
		{
			//LIKE VI_b298cc809d17d8ae.1475506018.843270c
			$originID = isset($fields['ORIGIN_ID']) ? $fields['ORIGIN_ID'] : '';
			if(strpos($originID, 'VI_') !== false)
			{
				$callId = substr($originID, 3);
				$callInfo = Integration\VoxImplantManager::getCallInfo($callId);
				if(is_array($callInfo))
				{
					$fields['CALL_INFO'] = $callInfo;
				}
			}
		}
		elseif($providerID === Activity\Provider\Sms::getId())
		{
			$smsFields = Integration\SmsManager::getMessageFields($fields['ASSOCIATED_ENTITY_ID']);
			if ($smsFields)
			{
				$fields['SMS_INFO'] = array(
					'id' => $smsFields['ID'],
					'senderId' => $smsFields['SENDER_ID'],
					'senderShortName' => Integration\SmsManager::getSenderShortName($smsFields['SENDER_ID']),
					'from' => $smsFields['MESSAGE_FROM'],
					'fromName' => Integration\SmsManager::getSenderFromName(
						$smsFields['SENDER_ID'],
						$smsFields['MESSAGE_FROM']
					),
					'statusId' => $smsFields['STATUS_ID']
				);
				if (Integration\SmsManager::isMessageErrorStatus($smsFields['STATUS_ID']))
				{
					$fields['SMS_INFO']['errorText'] = $smsFields['EXEC_ERROR'];
				}
			}
		}
		elseif($providerID === Activity\Provider\OpenLine::getId())
		{
			$sessionID = isset($fields['ASSOCIATED_ENTITY_ID']) ? (int)$fields['ASSOCIATED_ENTITY_ID'] : 0;
			if($sessionID > 0)
			{
				$fields['OPENLINE_INFO'] = array(
					'MESSAGES' => \Bitrix\Crm\Integration\OpenLineManager::getSessionMessages($sessionID, 3)
				);
			}
		}
		elseif($providerID === Activity\Provider\Visit::getId())
		{
			$recordLength = (int)$fields['PROVIDER_PARAMS']['RECORD_LENGTH'];
			$fields['VISIT_INFO'] = array(
				'RECORD_LENGTH' => $recordLength,
				'RECORD_LENGTH_FORMATTED_SHORT' => Activity\Provider\Visit::getFormattedLength($fields, 'SHORT'),
				'RECORD_LENGTH_FORMATTED_FULL' => Activity\Provider\Visit::getFormattedLength($fields, 'FULL'),
				'VK_PROFILE' => Activity\Provider\Visit::getVkProfile($fields)
			);
		}
		elseif($providerID === Activity\Provider\RestApp::getId())
		{
			$appTypeInfo = Activity\Provider\RestApp::getTypeInfo(
				$fields['ASSOCIATED_ENTITY_ID'], $fields['PROVIDER_TYPE_ID']
			);

			if ($appTypeInfo && $appTypeInfo['ICON_ID'] > 0)
			{
				$icon = \CFile::ResizeImageGet(
					$appTypeInfo['ICON_ID'],
					array('width' => 32, 'height' => 32),
					BX_RESIZE_IMAGE_EXACT
				);
				if ($icon)
				{
					$appTypeInfo['ICON_SRC'] = $icon['src'];
				}
			}
			$fields['APP_TYPE'] = $appTypeInfo;
		}

		if(isset($fields['STORAGE_ELEMENT_IDS']))
		{
			$mediaExtensions = array('wav', 'mp3', 'mp4');
			$storageTypeID = isset($fields['STORAGE_TYPE_ID'])
				? (int)$fields['STORAGE_TYPE_ID'] : Integration\StorageType::Undefined;

			$elementIDs = unserialize($fields['STORAGE_ELEMENT_IDS']);
			if(is_array($elementIDs) && !empty($elementIDs))
			{
				foreach($elementIDs as $elementID)
				{
					$info = Integration\StorageManager::getFileInfo(
						$elementID,
						$storageTypeID,
						false,
						array('OWNER_TYPE_ID' => \CCrmOwnerType::Activity, 'OWNER_ID' => $ID)
					);
					if(is_array($info))
					{
						$ext = GetFileExtension(strtolower($info['NAME']));

						if(in_array($ext, $mediaExtensions))
						{
							$fields['MEDIA_FILE_INFO'] = array(
								'URL' => $info['VIEW_URL'],
								'NAME' => $info['NAME'],
								'TYPE' => $ext === 'wav' ? 'audio/x-wav' : "audio/{$ext}"
							);
							if(isset($fields['CALL_INFO']) && isset($fields['CALL_INFO']['DURATION']))
							{
								$fields['MEDIA_FILE_INFO']['DURATION'] = $fields['CALL_INFO']['DURATION'];
							}
						}
						break;
					}
				}
			}
		}

		return $fields;
	}
	//endregion
	protected static function getEntity($ID)
	{
		$dbResult = \CCrmActivity::GetList(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array(
				'ID', 'TYPE_ID', 'PROVIDER_ID', 'OWNER_ID', 'OWNER_TYPE_ID',
				'AUTHOR_ID', 'EDITOR_ID', 'RESPONSIBLE_ID',
				'DIRECTION', 'SUBJECT', 'STATUS', 'DEADLINE', 'CREATED',
				'DESCRIPTION', 'DESCRIPTION_TYPE', 'ASSOCIATED_ENTITY_ID',
				'STORAGE_TYPE_ID', 'STORAGE_ELEMENT_IDS', 'ORIGIN_ID', 'SETTINGS'
			)
		);
		return is_object($dbResult) ? $dbResult->Fetch() : null;
	}
	protected static function resolveAuthorID(array $fields)
	{

		$authorID = 0;
		if(isset($fields['PROVIDER_ID'])
			&& $fields['PROVIDER_ID'] === Activity\Provider\OpenLine::getId()
			&& isset($fields['RESPONSIBLE_ID'])
		)
		{
			//HACK: OpenLine provider may not supply EDITOR_ID and AUTHOR_ID.
			$authorID = (int)$fields['RESPONSIBLE_ID'];
		}

		if($authorID <= 0 && isset($fields['EDITOR_ID']))
		{
			$authorID = (int)$fields['EDITOR_ID'];
		}
		if($authorID <= 0 && isset($fields['AUTHOR_ID']))
		{
			$authorID = (int)$fields['AUTHOR_ID'];
		}
		if($authorID <= 0 && isset($fields['RESPONSIBLE_ID']))
		{
			$authorID = (int)$fields['RESPONSIBLE_ID'];
		}
		if($authorID <= 0)
		{
			//Set portal admin as default author
			$authorID = 1;
		}
		return $authorID;
	}
	protected static function mapBindings(array $bindings)
	{
		return array_map(
			function($binding)
			{
				return array(
					'ENTITY_TYPE_ID' => $binding['OWNER_TYPE_ID'],
					'ENTITY_ID' => $binding['OWNER_ID']
				);
			},
			$bindings
		);
	}
}
