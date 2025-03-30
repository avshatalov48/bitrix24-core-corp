<?php
/*
 * CRM Activity
 */
IncludeModuleLangFile(__FILE__);
//@codingStandardsIgnoreFile

use Bitrix\Crm;
use Bitrix\Crm\Activity\FastSearch;
use Bitrix\Crm\Activity\FillActLightCounter;
use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeRepo;
use Bitrix\Crm\Activity\Provider;
use Bitrix\Crm\Activity\Provider\Eventable\PingOffset;
use Bitrix\Crm\Activity\Provider\Eventable\PingQueue;
use Bitrix\Crm\Activity\Provider\ToDo\ToDo;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Automation\Trigger\ResourceBookingTrigger;
use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Entity\Traits\EntityFieldsNormalizer;
use Bitrix\Crm\Integration\Disk\HiddenStorage;
use Bitrix\Crm\Integration\StorageFileType;
use Bitrix\Crm\Integration\StorageManager;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings;
use Bitrix\Crm\Settings\ActivitySettings;
use Bitrix\Disk\SpecificFolder;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;

class CAllCrmActivity
{
	use EntityFieldsNormalizer;
	const CACHE_NAME = 'CRM_ACTIVITY_CACHE';
	const TABLE_ALIAS = 'A';
	const UF_ENTITY_TYPE = 'CRM_ACTIVITY';
	const UF_SUSPENDED_ENTITY_TYPE = 'CRM_ACTIVITY_SPD';
	//const UF_WEBDAV_FIELD_NAME = 'UF_CRM_ACTIVITY_WDAV';
	const COMMUNICATION_TABLE_ALIAS = 'AC';
	private const ACTIVITY_DEFAULT = 'EVENT';

	private static $FIELDS = null;
	private static $FIELD_INFOS = null;
	private static $COMM_FIELD_INFOS = null;
	private static $FIELDS_CACHE = array();
	private static $COMMUNICATION_FIELDS = null;

	private static $USER_PERMISSIONS = null;
	private static $STORAGE_TYPE_ID = StorageType::Undefined;
	protected static $errors = array();
	private static $URN_REGEX = '/\[\s*(?:CRM\s*\:)\s*(?P<urn>[0-9]+\s*[-]\s*[0-9A-Z]+)\s*\]/i';
	private static $URN_BODY_REGEX = '/\[\s*(?:msg\s*\:)\s*(?P<urn>[0-9]+\s*[-]\s*[0-9A-Z]+)\s*\]/iu';
	private static $URN_BODY_HTML_ENTITY_REGEX = '/\&\#91\;\s*(?:msg\s*\:)\s*(?P<urn>[0-9]+\s*[-]\s*[0-9A-Z]+)\s*\&\#93\;/iu';
	protected static $LAST_UPDATE_FIELDS = null;

	private static $IGNORE_CALENDAR_EVENTS = false;
	private static $CURRENT_DAY_TIME_STAMP = null;
	private static $NEXT_DAY_TIME_STAMP = null;
	private static $CLIENT_INFOS = null;
	protected static $PROVIDERS = null;
	protected const TABLE_NAME = CCrmActivity::TABLE_NAME;

	protected static function getInstance(): self
	{
		return new static();
	}

	// CRUD -->
	public static function Add(&$arFields, $checkPerms = true, $regEvent = true, $options = array())
	{
		global $DB, $USER_FIELD_MANAGER;
		if(!is_array($options))
		{
			$options = array();
		}

		$isRestoration = isset($options['IS_RESTORATION']) && $options['IS_RESTORATION'];

		self::appendOwnerFromBindings($arFields);

		if(!isset($arFields['STORAGE_TYPE_ID']))
		{
			$arFields['STORAGE_TYPE_ID'] = StorageType::getDefaultTypeID();
		}

		$params = [
			'INITIATED_BY_CALENDAR' => (bool) ($options['INITIATED_BY_CALENDAR'] ?? false),
		];

		if(isset($options['PRESERVE_CREATION_TIME']))
		{
			$params['PRESERVE_CREATION_TIME'] = $options['PRESERVE_CREATION_TIME'];
		}

		if (!self::rebuildFieldsForDataBase('ADD', $arFields, 0, $params))
		{
			return false;
		}

		$storageElementIDs = isset($arFields['STORAGE_ELEMENT_IDS']) ? $arFields['STORAGE_ELEMENT_IDS'] : array();
		$storageElementsSerialized = false;
		if(is_array($storageElementIDs))
		{
			$storageElementIDs = self::NormalizeStorageElementIDs($storageElementIDs);
			$arFields['STORAGE_ELEMENT_IDS'] = serialize($storageElementIDs);
			$storageElementsSerialized = true;
		}

		if(isset($arFields['SETTINGS']) && is_array($arFields['SETTINGS']))
		{
			$arFields['SETTINGS'] = serialize($arFields['SETTINGS']);
		}

		if(isset($arFields['PROVIDER_PARAMS']))
		{
			$arFields['PROVIDER_PARAMS'] = serialize($arFields['PROVIDER_PARAMS']);
		}

		if (isset($arFields['PARENT_ID']) && $arFields['PARENT_ID'] > 0)
		{
			$parent = $DB->query(sprintf(
				'SELECT ID, THREAD_ID FROM %s WHERE ID = %u',
				\CCrmActivity::TABLE_NAME, $arFields['PARENT_ID']
			))->fetch();
		}

		if (!empty($parent))
		{
			$arFields['PARENT_ID'] = $parent['ID'];
			$arFields['THREAD_ID'] = $parent['THREAD_ID'];
		}
		else
		{
			$arFields['PARENT_ID'] = 0;
			$arFields['THREAD_ID'] = 0;
		}

		self::NormalizeDateTimeFields($arFields);

		$beforeEvents = GetModuleEvents('crm', 'OnBeforeCrmActivityAdd');
		while ($arEvent = $beforeEvents->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array(&$arFields));
		}

		$originalSubject = null;
		if (isset($arFields['SUBJECT']))
		{
			$originalSubject = $arFields['SUBJECT'];
			$arFields['SUBJECT'] = \Bitrix\Main\Text\Emoji::encode($arFields['SUBJECT']);
		}
		$originalDescription = null;
		if (isset($arFields['DESCRIPTION']))
		{
			$originalDescription = $arFields['DESCRIPTION'];
			$arFields['DESCRIPTION'] = \Bitrix\Main\Text\Emoji::encode($arFields['DESCRIPTION']);
		}

		self::getInstance()->normalizeEntityFields($arFields);

		$arFields['OWNER_ID'] = (int)$arFields['OWNER_ID'];
		$arFields['OWNER_TYPE_ID'] = (int)$arFields['OWNER_TYPE_ID'];

		$ID = $DB->Add(
			CCrmActivity::TABLE_NAME,
			$arFields,
			['DESCRIPTION', 'STORAGE_ELEMENT_IDS', 'SETTINGS', 'PROVIDER_PARAMS', 'PROVIDER_DATA']
		);
		if(is_string($ID) && $ID !== '')
		{
			//MS SQL RETURNS STRING INSTEAD INT
			$ID = intval($ID);
		}

		if($ID === false)
		{
			self::RegisterError(array('text' => "DB connection was lost."));
			return false;
		}
		if (!is_null($originalSubject))
		{
			$arFields['SUBJECT'] = $originalSubject;
		}
		if (!is_null($originalDescription))
		{
			$arFields['DESCRIPTION'] = $originalDescription;
		}

		if ($arFields['PARENT_ID'] == 0)
		{
			$DB->query(sprintf(
				'UPDATE %s SET THREAD_ID = ID WHERE ID = %u',
				\CCrmActivity::TABLE_NAME, $ID
			));
		}

		$isIncomingChannel = (($arFields['IS_INCOMING_CHANNEL'] ?? null) === 'Y');
		if ($isIncomingChannel)
		{
			\Bitrix\Crm\Activity\IncomingChannel::getInstance()->register($ID, (int)$arFields['RESPONSIBLE_ID'], $arFields['COMPLETED'] === 'Y');
		}

		$arFields['ID'] = $ID;
		$arFields['SETTINGS'] = isset($arFields['SETTINGS']) ? unserialize($arFields['SETTINGS'], ['allowed_classes' => false]) : array();
		$arFields['PROVIDER_PARAMS'] = isset($arFields['PROVIDER_PARAMS']) ? unserialize($arFields['PROVIDER_PARAMS'], ['allowed_classes' => false]) : array();

		CCrmActivity::DoSaveElementIDs($ID, $arFields['STORAGE_TYPE_ID'], $storageElementIDs);

		$arBindings = isset($arFields['BINDINGS']) && is_array($arFields['BINDINGS']) ? $arFields['BINDINGS'] : array();

		$isOwnerInBindings = false;
		$ownerID = intval($arFields['OWNER_ID']);
		$ownerTypeID = intval($arFields['OWNER_TYPE_ID']);
		foreach($arBindings as $arBinding)
		{
			$curOwnerTypeID = isset($arBinding['OWNER_TYPE_ID']) ? intval($arBinding['OWNER_TYPE_ID']) : 0;
			$curOwnerID = isset($arBinding['OWNER_ID']) ? intval($arBinding['OWNER_ID']) : 0;

			if($curOwnerTypeID === $ownerTypeID && $curOwnerID === $ownerID)
			{
				$isOwnerInBindings = true;
				break;
			}
		}

		if(!$isOwnerInBindings)
		{
			$arBindings[] = array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerID
			);
			$arFields['BINDINGS'][] = array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerID
			);
		}

		self::SaveBindings($ID, $arBindings, false, false, false);
		if(isset($arFields['COMMUNICATIONS']) && is_array($arFields['COMMUNICATIONS']))
		{
			self::SaveCommunications($ID, $arFields['COMMUNICATIONS'], $arFields, false, false);
		}

		$completed = isset($arFields['COMPLETED']) && $arFields['COMPLETED'] === 'Y';
		if($completed && isset($arFields['STATUS']))
		{
			$completed = $arFields['STATUS'] == CCrmActivityStatus::Completed;
		}
		$deadline = isset($arFields['DEADLINE']) ? $arFields['DEADLINE'] : '';
		if($completed && $deadline !== '')
		{
			$deadline = \Bitrix\Main\Type\DateTime::createFromUserTime($deadline);
			$deadline->setTime(0, 0, 0);
			foreach ($arBindings as $arBinding)
			{
				$curOwnerTypeID = isset($arBinding['OWNER_TYPE_ID']) ? intval($arBinding['OWNER_TYPE_ID']) : 0;
				$curOwnerID = isset($arBinding['OWNER_ID']) ? intval($arBinding['OWNER_ID']) : 0;
				if ($curOwnerID > 0 && !CCrmDateTimeHelper::IsMaxDatabaseDate($deadline))
				{
					if ($curOwnerTypeID === CCrmOwnerType::Deal)
					{
						Bitrix\Crm\Statistics\DealActivityStatisticEntry::register($curOwnerID, null, array('DATE' => $deadline));
					}
					elseif ($curOwnerTypeID === CCrmOwnerType::Lead)
					{
						Bitrix\Crm\Statistics\LeadActivityStatisticEntry::register($curOwnerID, null, array('DATE' => $deadline));
					}
				}
			}
			unset($arBinding);
		}

		$arPingOffsets = self::fetchActivityPingOffsets($arFields);

		$lightTimeDate = (new FillActLightCounter())->onActAdd($arFields, $arPingOffsets);
		$arFields['LIGHT_COUNTER_AT'] = $lightTimeDate;

		if (!$completed)
		{
			\Bitrix\Crm\Activity\UncompletedActivity::synchronizeForUncompletedActivityChange(
				\Bitrix\Crm\Activity\UncompletedActivityChange::create(
					$ID,
					[],
					[],
					$arFields,
					$arBindings,
					null,
					$lightTimeDate
				)
			);
		}

		\Bitrix\Crm\Activity\CommunicationStatistics::registerActivity($arFields);
		if ($deadline)
		{
			\Bitrix\Crm\Statistics\ActivityStatisticEntry::register($ID, $arFields);
		}

		//region Search content index
		Bitrix\Crm\Search\SearchContentBuilderFactory::create(CCrmOwnerType::Activity)->build($ID);
		//endregion

		if(!$isRestoration && $regEvent)
		{
			self::registerAddEvent($arBindings, $arFields, false);
		}

		// Synchronize user activity -->
		$responsibleID = isset($arFields['RESPONSIBLE_ID']) ? intval($arFields['RESPONSIBLE_ID']) : 0;
		if($responsibleID > 0)
		{
			foreach($arBindings as $arBinding)
			{
				self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], $responsibleID);
				self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], 0);
			}
		}
		\Bitrix\Crm\Counter\Monitor::getInstance()->onActivityAdd($arFields, $arBindings, $lightTimeDate);

		FastSearch\Sync\Monitor::getInstance()->onActivityAdd(
			FastSearch\Sync\ActivityChangeSet::build(null, $arFields)
		);
		// <-- Synchronize user activity

		$provider = self::GetActivityProvider($arFields);
		$providerTypeId = isset($arFields['PROVIDER_TYPE_ID']) ? (string) $arFields['PROVIDER_TYPE_ID'] : null;
		if (
			$provider !== null
			&& $provider::canAddCalendarEvents($providerTypeId)
			&& !$provider::skipCalendarSync($arFields, $options)
		)
		{
			$skipCalendarEvent = isset($options['SKIP_CALENDAR_EVENT']) ? (bool)$options['SKIP_CALENDAR_EVENT'] : null;
			$calendarEventId =  isset($arFields['CALENDAR_EVENT_ID']) ? (int)$arFields['CALENDAR_EVENT_ID'] : 0;
			$isCompleted = isset($arFields['COMPLETED']) && $arFields['COMPLETED'] === 'Y';
			if (
				!$skipCalendarEvent
				&& $calendarEventId <= 0
				&& (!$isCompleted || $provider::canKeepCompletedInCalendar($providerTypeId))
			)
			{
				if (!self::isLocationFeatureEnabled())
				{
					unset($arFields['LOCATION']);
				}

				$eventID = self::SaveCalendarEvent($arFields);
				if(is_int($eventID) && $eventID > 0)
				{
					self::SetCalendarEventId($eventID, $ID);

					$arFields['CALENDAR_EVENT_ID'] = $eventID;
				}
			}
		}

		if (!$completed && !empty($arPingOffsets))
		{
			PingOffset::getInstance()->register($ID, $arPingOffsets);
			if ($provider === null || $provider::needSynchronizePingQueue($arFields))
			{
				PingQueue::getInstance()->register($ID, false, $arFields['DEADLINE']);
			}
		}

		if(!$isRestoration)
		{
			\Bitrix\Crm\Timeline\ActivityController::getInstance()->onCreate(
				$ID,
				array(
					'FIELDS' => $arFields,
					'PRESERVE_CREATION_TIME' => isset($options['PRESERVE_CREATION_TIME']) && $options['PRESERVE_CREATION_TIME'] === true,
					'CURRENT_USER' => $options['CURRENT_USER'] ?? null,
				)
			);
		}

		if($storageElementsSerialized)
		{
			$arFields['STORAGE_ELEMENT_IDS'] = $storageElementIDs;
		}

		if(is_int($ID) && $ID > 0)
		{
			/*
			 * Messages are linked by the owner of the mailbox
			 */
			if (isset($arFields['TYPE_ID']) && $arFields['TYPE_ID'] == CCrmActivityType::Email)
			{
				$USER_FIELD_MANAGER->update(static::UF_ENTITY_TYPE, $ID, $arFields, $arFields['AUTHOR_ID']);
			}
			else
			{
				$USER_FIELD_MANAGER->update(static::UF_ENTITY_TYPE, $ID, $arFields, $arFields['EDITOR_ID']);
			}

			if ($provider !== null && $provider::canUseLiveFeedEvents($providerTypeId) === false)
				$options['REGISTER_SONET_EVENT'] = false;

			if(isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true)
			{
				self::RegisterLiveFeedEvent($arFields);
				if ($responsibleID > 0 && Settings\Crm::isLiveFeedRecordsGenerationEnabled())
				{
					CCrmSonetSubscription::RegisterSubscription(
						CCrmOwnerType::Activity,
						$ID,
						CCrmSonetSubscriptionType::Responsibility,
						$responsibleID
					);
				}
			}

			if($provider !== null)
			{
				$provider::onAfterAdd(
					$arFields,
					[
						'IS_RESTORATION' => $isRestoration,
					],
				);
			}


			//Crm\Activity\Provider\ProviderManager::processRestorationFromRecycleBin(
			\Bitrix\Crm\Activity\Provider\ProviderManager::processCreation(
				$arFields,
				array('BINDINGS' => $arBindings, 'IS_RESTORATION' => $isRestoration)
			);

			\Bitrix\Crm\Pseudoactivity\WaitEntry::processActivityCreation(
				$arFields,
				array('BINDINGS' => $arBindings)
			);

			$rsEvents = GetModuleEvents('crm', 'OnActivityAdd');
			while ($arEvent = $rsEvents->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array($ID, &$arFields));
			}

			if($arFields['COMPLETED'] === 'Y')
			{
				Crm\Ml\Scoring::queuePredictionUpdate($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID'], [
					'EVENT_TYPE' => Crm\Ml\Scoring::EVENT_ACTIVITY,
					'ASSOCIATED_ACTIVITY_ID'=> $ID
				]);
			}
		}

		return $ID;
	}

	public static function Update($ID, $arFields, $checkPerms = true, $regEvent = true, $options = array())
	{
		self::$LAST_UPDATE_FIELDS = null;

		global $DB, $USER_FIELD_MANAGER;
		if(!is_array($options))
		{
			$options = array();
		}

		$ID = (int)$ID;

		$arPrevEntity = self::GetByID($ID, false);

		if(!$arPrevEntity)
		{
			return false; // is not exists
		}

		$checkFieldParams = ['PREVIOUS_FIELDS' => $arPrevEntity];
		if (isset($options['CURRENT_USER']))
		{
			$checkFieldParams['CURRENT_USER'] = $options['CURRENT_USER'];
		}
		if (isset($options['INITIATED_BY_CALENDAR']))
		{
			$checkFieldParams['INITIATED_BY_CALENDAR'] = $options['INITIATED_BY_CALENDAR'];
		}
		if (!self::rebuildFieldsForDataBase('UPDATE', $arFields, $ID, $checkFieldParams))
		{
			return false;
		}

		$arPrevBindings = self::GetBindings($ID);
		$arRecordBindings = array();

		$storageElementIDs = isset($arFields['STORAGE_ELEMENT_IDS']) ? $arFields['STORAGE_ELEMENT_IDS'] : null;
		$storageElementsSerialized = false;
		if(is_array($storageElementIDs))
		{
			$storageElementIDs = self::NormalizeStorageElementIDs($storageElementIDs);
			$arFields['STORAGE_ELEMENT_IDS'] = serialize($storageElementIDs);
			$storageElementsSerialized = true;
		}
		elseif($storageElementIDs !== null)
		{
			//Skip Storage Elements Processing - Treat As Not Changed
			$storageElementIDs = null;
		}

		if(isset($arFields['STORAGE_ELEMENT_IDS']))
		{
			$arRecordBindings['STORAGE_ELEMENT_IDS'] = $arFields['STORAGE_ELEMENT_IDS'];
		}

		$settings = $arFields['SETTINGS'] ?? null;

		if (isset($arFields['SETTINGS']))
		{
			if (is_array($arFields['SETTINGS']))
			{
				$arFields['SETTINGS'] = serialize($arFields['SETTINGS']);
			}

			$arRecordBindings['SETTINGS'] = $settings;
		}

		if(isset($arFields['DESCRIPTION']))
		{
			$arRecordBindings['DESCRIPTION'] = $arFields['DESCRIPTION'];
		}
		if(isset($arFields['PROVIDER_PARAMS']))
		{
			$arFields['PROVIDER_PARAMS'] = serialize($arFields['PROVIDER_PARAMS']);
			$arRecordBindings['PROVIDER_PARAMS'] = $arFields['PROVIDER_PARAMS'];
		}
		if(isset($arFields['PROVIDER_DATA']))
		{
			$arRecordBindings['PROVIDER_DATA'] = $arFields['PROVIDER_DATA'];
		}

		$arBindings = (isset($arFields['BINDINGS']) && is_array($arFields['BINDINGS'])) ? $arFields['BINDINGS'] : null;
		if(is_array($arBindings))
		{
			$bindingQty = count($arBindings);
			if ($bindingQty === 1)
			{
				// Change activity ownership if only one binding defined
				$arBinding = $arBindings[0];
				$arFields['OWNER_ID'] = $arBinding['OWNER_ID'];
				$arFields['OWNER_TYPE_ID'] = $arBinding['OWNER_TYPE_ID'];
			}
			elseif ($bindingQty === 0)
			{
				// Clear activity ownership if only no bindings are defined
				$arFields['OWNER_ID'] = 0;
				$arFields['OWNER_TYPE_ID'] = CCrmOwnerType::Undefined;
			}
			else
			{
				self::appendOwnerFromBindings($arFields);
			}
		}

		$needSynchronizePingQueue = false;
		$needRegisterUncompletedActivities = false;
		$pingOffsets = [];
		if (isset($arFields['PING_OFFSETS']) && is_array($arFields['PING_OFFSETS'])) // only if PING_OFFSETS was really changed
		{
			$pingOffsets = $arFields['PING_OFFSETS'];
			PingOffset::getInstance()->register($ID, $pingOffsets);
			$needSynchronizePingQueue = true;
			$needRegisterUncompletedActivities = true;
		}
		else
		{
			$pingOffsets = PingOffset::getInstance()->getOffsetsByActivityId($ID);
		}

		if (isset($arFields['PARENT_ID']) && $arFields['PARENT_ID'] > 0 && $arPrevEntity['PARENT_ID'] == 0)
		{
			$parent = $DB->query(sprintf(
				'SELECT ID, THREAD_ID FROM %s WHERE ID = %u',
				\CCrmActivity::TABLE_NAME, $arFields['PARENT_ID']
			))->fetch();
		}

		if (!empty($parent))
		{
			$arFields['PARENT_ID'] = $parent['ID'];
			$arFields['THREAD_ID'] = $parent['THREAD_ID'];
		}
		else
		{
			unset($arFields['PARENT_ID'], $arFields['THREAD_ID']);
		}

		self::NormalizeDateTimeFields($arFields);

		if(isset($arFields['ID']))
		{
			unset($arFields['ID']);
		}

		if (isset($arFields['SUBJECT']))
		{
			$arFields['SUBJECT'] = \Bitrix\Main\Text\Emoji::encode($arFields['SUBJECT']);
		}
		if (isset($arFields['DESCRIPTION']))
		{
			$arFields['DESCRIPTION'] = \Bitrix\Main\Text\Emoji::encode($arFields['DESCRIPTION']);
		}

		$beforeEvents = GetModuleEvents('crm', 'OnBeforeCrmActivityUpdate');
		while ($arEvent = $beforeEvents->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array($ID, &$arFields));
		}

		self::getInstance()->normalizeEntityFields($arFields);
		$sql = 'UPDATE '.CCrmActivity::TABLE_NAME.' SET '.$DB->PrepareUpdate(CCrmActivity::TABLE_NAME, $arFields).' WHERE ID = '.$ID;

		if(!empty($arRecordBindings))
		{
			$DB->QueryBind($sql, $arRecordBindings, false);
		}
		else
		{
			$DB->Query($sql);
		}

		self::$LAST_UPDATE_FIELDS = $arFields;

		if (isset($arFields['PARENT_ID']) && $arFields['PARENT_ID'] > 0 && $arPrevEntity['PARENT_ID'] == 0)
		{
			$DB->query(sprintf(
				'UPDATE %s SET THREAD_ID = %u WHERE THREAD_ID = %u',
				\CCrmActivity::TABLE_NAME, $arFields['THREAD_ID'], $ID
			));
		}

		$arFields['SETTINGS'] = isset($arFields['SETTINGS']) ? unserialize($arFields['SETTINGS'], ['allowed_classes' => false]) : array();
		$arFields['PROVIDER_PARAMS'] = isset($arFields['PROVIDER_PARAMS']) ? unserialize($arFields['PROVIDER_PARAMS'], ['allowed_classes' => false]) : array();

		CCrmEntityHelper::RemoveCached(self::CACHE_NAME, $ID);

		$storageTypeID = isset($arFields['STORAGE_TYPE_ID']) ? intval($arFields['STORAGE_TYPE_ID']) : StorageType::Undefined;
		if($storageTypeID === StorageType::Undefined)
		{
			$storageTypeID = isset($arPrevEntity['STORAGE_TYPE_ID']) ? intval($arPrevEntity['STORAGE_TYPE_ID']) : self::GetDefaultStorageTypeID();
		}

		if(is_array($storageElementIDs))
		{
			CCrmActivity::DoSaveElementIDs($ID, $storageTypeID, $storageElementIDs);
		}

		$arCurEntity = self::GetByID($ID, false);
		if(!$arCurEntity)
		{
			return false; // is not exists
		}

		if(is_array($arBindings))
		{
			$bindingsChanged = !self::IsBindingsEquals($arBindings, $arPrevBindings);
			if($bindingsChanged)
			{
				self::SaveBindings($ID, $arBindings, false, false, false);
				$needRegisterUncompletedActivities = true;
			}
		}
		else
		{
			$arBindings = $arPrevBindings;
			$bindingsChanged = false;
		}

		if(isset($arFields['COMMUNICATIONS']) && is_array($arFields['COMMUNICATIONS']))
		{
			self::SaveCommunications($ID, $arFields['COMMUNICATIONS'], $arFields, false, false);
		}
		$responsibleID = (int)(
			$arFields['RESPONSIBLE_ID']
			?? $arPrevEntity['RESPONSIBLE_ID']
			?? 0
		);

		$prevCompleted = isset($arPrevEntity['COMPLETED']) && $arPrevEntity['COMPLETED'] === 'Y';
		$prevCompletedForStatistics = $prevCompleted;
		if($prevCompleted && isset($arPrevEntity['STATUS']))
		{
			$prevCompletedForStatistics = $arPrevEntity['STATUS'] == CCrmActivityStatus::Completed;
		}

		$curCompleted = isset($arCurEntity['COMPLETED']) && $arCurEntity['COMPLETED'] === 'Y';
		$curCompletedForStatistics = $curCompleted;
		if($curCompleted && isset($arCurEntity['STATUS']))
		{
			$curCompletedForStatistics = $arCurEntity['STATUS'] == CCrmActivityStatus::Completed;
		}

		$incomingChannel = \Bitrix\Crm\Activity\IncomingChannel::getInstance();
		// IS_INCOMING_CHANNEL changed
		if (isset($arFields['IS_INCOMING_CHANNEL']))
		{
			$arPrevEntity['IS_INCOMING_CHANNEL'] = $incomingChannel->isIncomingChannel($ID) ? 'Y' : 'N';
			if ($arFields['IS_INCOMING_CHANNEL'] === 'Y')
			{
				$incomingChannel->register($ID, $responsibleID, $curCompleted);
			}
			if ($arFields['IS_INCOMING_CHANNEL'] === 'N')
			{
				$incomingChannel->unregister($ID);
			}
			$arCurEntity['IS_INCOMING_CHANNEL'] = $arFields['IS_INCOMING_CHANNEL'];
			$needRegisterUncompletedActivities = true;
		}
		else
		{
			$arCurEntity['IS_INCOMING_CHANNEL'] = $incomingChannel->isIncomingChannel($ID) ? 'Y' : 'N';
			$arPrevEntity['IS_INCOMING_CHANNEL'] = $arCurEntity['IS_INCOMING_CHANNEL'];
			// RESPONSIBLE_ID changed
			if (isset($arFields['RESPONSIBLE_ID']) && $responsibleID != $arPrevEntity['RESPONSIBLE_ID'])
			{
				if ($arCurEntity['IS_INCOMING_CHANNEL'] === 'Y')
				{
					$incomingChannel->register($ID, $responsibleID, $curCompleted);
				}
				$needRegisterUncompletedActivities = true;
			}
			// COMPLETED changed
			elseif ($prevCompleted != $curCompleted)
			{
				if ($arCurEntity['IS_INCOMING_CHANNEL'] === 'Y')
				{
					$incomingChannel->register($ID, $responsibleID, $curCompleted);
				}
				$needSynchronizePingQueue = true;
				$needRegisterUncompletedActivities = true;
			}
		}

		$prevDeadline = $arPrevEntity['DEADLINE'] ?? '';
		$curDeadline = $arCurEntity['DEADLINE'] ?? '';

		if ($prevDeadline !== $curDeadline)
		{
			$needSynchronizePingQueue = true;
			$needRegisterUncompletedActivities = true;
		}

		if (
			($arCurEntity['NOTIFY_TYPE'] !== $arPrevEntity['NOTIFY_TYPE'])
			||  ($arCurEntity['NOTIFY_VALUE'] !== $arPrevEntity['NOTIFY_VALUE'])
		)
		{
			$needRegisterUncompletedActivities = true;
		}

		if (
			$prevCompletedForStatistics
			&& $prevDeadline
			&& ($bindingsChanged || $prevCompletedForStatistics != $curCompletedForStatistics || $prevDeadline !== $curDeadline)
		)
		{
			$deadline = \Bitrix\Main\Type\DateTime::createFromUserTime($prevDeadline);
			$deadline->setTime(0, 0, 0);
			foreach ($arPrevBindings as $arBinding)
			{
				$curOwnerTypeID = isset($arBinding['OWNER_TYPE_ID']) ? intval($arBinding['OWNER_TYPE_ID']) : 0;
				$curOwnerID = isset($arBinding['OWNER_ID']) ? intval($arBinding['OWNER_ID']) : 0;
				if ($curOwnerID > 0 && !CCrmDateTimeHelper::IsMaxDatabaseDate($deadline))
				{
					if ($curOwnerTypeID === CCrmOwnerType::Deal)
					{
						Bitrix\Crm\Statistics\DealActivityStatisticEntry::register($curOwnerID, null, array('DATE' => $deadline));
					}
					elseif ($curOwnerTypeID === CCrmOwnerType::Lead)
					{
						Bitrix\Crm\Statistics\LeadActivityStatisticEntry::register($curOwnerID, null, array('DATE' => $deadline));
					}
				}
			}
		}

		if ($curCompletedForStatistics && $curDeadline !== '')
		{
			$deadline = \Bitrix\Main\Type\DateTime::createFromUserTime($curDeadline);
			$deadline->setTime(0, 0, 0);
			foreach ($arBindings as $arBinding)
			{
				$curOwnerTypeID = isset($arBinding['OWNER_TYPE_ID']) ? (int)$arBinding['OWNER_TYPE_ID'] : 0;
				$curOwnerID = isset($arBinding['OWNER_ID']) ? (int)$arBinding['OWNER_ID'] : 0;
				if ($curOwnerID > 0 && !CCrmDateTimeHelper::IsMaxDatabaseDate($deadline))
				{
					if ($curOwnerTypeID === CCrmOwnerType::Deal)
					{
						Bitrix\Crm\Statistics\DealActivityStatisticEntry::register($curOwnerID, null, array('DATE' => $deadline));
					}
					elseif ($curOwnerTypeID === CCrmOwnerType::Lead)
					{
						Bitrix\Crm\Statistics\LeadActivityStatisticEntry::register($curOwnerID, null, array('DATE' => $deadline));
					}
				}
			}
		}

		$arCurEntity['BINDINGS'] = $arBindings;
		$arPrevEntity['BINDINGS'] = $arPrevBindings;
		\Bitrix\Crm\Activity\CommunicationStatistics::updateActivity($arCurEntity, $arPrevEntity);
		if ($curDeadline)
		{
			\Bitrix\Crm\Statistics\ActivityStatisticEntry::register($ID, $arCurEntity);
		}
		\Bitrix\Crm\Integration\Channel\ActivityChannelBinding::synchronize($ID, $arCurEntity);

		//region Search content index
		Bitrix\Crm\Search\SearchContentBuilderFactory::create(CCrmOwnerType::Activity)->build($ID);
		//endregion

		// Synchronize user activity -->
		$arSyncKeys = array();

		$oldLightTimeDate = self::queryLightTimeDateByActId($ID);

		$lightTimeDate = (new FillActLightCounter())->onActUpdate($arCurEntity, $arPrevEntity, $pingOffsets);
		$arCurEntity['LIGHT_COUNTER_AT'] = $lightTimeDate;

		if ($needRegisterUncompletedActivities)
		{
			$bindingsToRegister = $arPrevBindings;
			if ($bindingsChanged)
			{
				$bindingsToRegister = array_merge($bindingsToRegister, $arBindings);
			}
			$usersToRegister = [0, $arPrevEntity['RESPONSIBLE_ID']];
			if (isset($arFields['RESPONSIBLE_ID']))
			{
				$usersToRegister[] = $arFields['RESPONSIBLE_ID'];
			}


			\Bitrix\Crm\Activity\UncompletedActivity::synchronizeForUncompletedActivityChange(
				\Bitrix\Crm\Activity\UncompletedActivityChange::create(
					$ID,
					$arPrevEntity,
					$arPrevBindings,
					$arCurEntity,
					$arBindings,
					$oldLightTimeDate,
					$lightTimeDate
				)
			);
		}

		foreach($arBindings as $arBinding)
		{
			if($responsibleID > 0)
			{
				$arSyncKeys[] = "{$arBinding['OWNER_TYPE_ID']}_{$arBinding['OWNER_ID']}_{$responsibleID}";
				self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], $responsibleID);
			}
			self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], 0);
			$arSyncKeys[] = "{$arBinding['OWNER_TYPE_ID']}_{$arBinding['OWNER_ID']}";
		}

		$prevResponsibleID = isset($arPrevEntity['RESPONSIBLE_ID']) ? intval($arPrevEntity['RESPONSIBLE_ID']) : 0;
		if(!empty($arPrevBindings))
		{
			foreach($arPrevBindings as $arBinding)
			{
				if($prevResponsibleID > 0 && !in_array("{$arBinding['OWNER_TYPE_ID']}_{$arBinding['OWNER_ID']}_{$prevResponsibleID}", $arSyncKeys, true))
				{
					self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], $prevResponsibleID);
				}
				if(!in_array("{$arBinding['OWNER_TYPE_ID']}_{$arBinding['OWNER_ID']}", $arSyncKeys, true))
				{
					self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], 0);
				}
			}
		}
		// <-- Synchronize user activity

		Crm\Activity\Provider\ProviderManager::syncBadgesOnActivityUpdate(
			$ID,
			$arCurEntity,
			$bindingsChanged ? array_intersect($arPrevBindings, $arBindings) : $arPrevBindings,
		);

		if (!empty($options['MOVE_BINDINGS_MAP']))
		{
			$sourceIdentifier = $options['MOVE_BINDINGS_MAP']['source'] ?? null;
			if ($sourceIdentifier instanceof Crm\ItemIdentifier)
			{
				Crm\Activity\Provider\ProviderManager::syncBadgesOnBindingsChange(
					$ID,
					[],
					[
						[
							'OWNER_TYPE_ID' => $sourceIdentifier->getEntityTypeId(),
							'OWNER_ID' => $sourceIdentifier->getEntityId()
						],
					],
				);
			}
		}

		\Bitrix\Crm\Counter\Monitor::getInstance()->onActivityUpdate(
			$arPrevEntity,
			$arCurEntity,
			$arPrevBindings,
			$arBindings,
			$oldLightTimeDate,
			$lightTimeDate
		);

		FastSearch\Sync\Monitor::getInstance()->onActivityUpdate(
			FastSearch\Sync\ActivityChangeSet::build($arPrevEntity, $arCurEntity)
		);

		if($regEvent)
		{
			foreach($arBindings as $arBinding)
			{
				self::RegisterUpdateEvent($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], $arCurEntity, $arPrevEntity, false);
			}
			unset($arBinding);
		}

		$providerTypeId = isset($arCurEntity['PROVIDER_TYPE_ID']) ? (string) $arCurEntity['PROVIDER_TYPE_ID'] : null;
		$skipAssocEntity = isset($options['SKIP_ASSOCIATED_ENTITY']) ? (bool)$options['SKIP_ASSOCIATED_ENTITY'] : false;
		$associatedEntityId = isset($arCurEntity['ASSOCIATED_ENTITY_ID']) ? (int)$arCurEntity['ASSOCIATED_ENTITY_ID'] : 0;
		$provider = self::GetActivityProvider($arCurEntity);

		if(!$skipAssocEntity && $provider !== null && $associatedEntityId > 0)
		{
			$provider::updateAssociatedEntity($associatedEntityId, $arCurEntity, $options);
		}

		if (
			$provider !== null
			&& $provider::canAddCalendarEvents($providerTypeId)
			&& !$provider::skipCalendarSync($arFields, $options)
		)
		{
			$skipCalendarEvent = isset($options['SKIP_CALENDAR_EVENT']) ? (bool)$options['SKIP_CALENDAR_EVENT'] : null;
			$completed = isset($arCurEntity['COMPLETED']) ? $arCurEntity['COMPLETED'] === 'Y' : false;

			if (!$skipCalendarEvent)
			{
				$eventID = 0;
				if (!$completed || $provider::canKeepCompletedInCalendar($providerTypeId))
				{
					$arCurEntity['BINDINGS'] = $arBindings;

					if (!empty($arFields['SECTION_ID']))
					{
						$arCurEntity['SECTION_ID'] = $arFields['SECTION_ID'];
					}
					if (isset($arFields['ATTENDEES_CODES']) && is_array($arFields['ATTENDEES_CODES']))
					{
						$arCurEntity['ATTENDEES_CODES'] = $arFields['ATTENDEES_CODES'];
					}
					if (isset($arFields['IS_MEETING']))
					{
						$arCurEntity['IS_MEETING'] = $arFields['IS_MEETING'];
					}
					if (isset($arFields['UF_CRM_CAL_EVENT']) && is_array($arFields['UF_CRM_CAL_EVENT']))
					{
						$arCurEntity['UF_CRM_CAL_EVENT'] = $arFields['UF_CRM_CAL_EVENT'];
					}
					if (isset($arFields['DESCRIPTION']))
					{
						$arCurEntity['DESCRIPTION'] = $arFields['DESCRIPTION'];
					}
					if (isset($arFields['ENRICHED_DESCRIPTION']))
					{
						$arCurEntity['ENRICHED_DESCRIPTION'] = $arFields['ENRICHED_DESCRIPTION'];
					}
					if (isset($arFields['PREV_ENRICHED_DESCRIPTION']))
					{
						$arCurEntity['PREV_ENRICHED_DESCRIPTION'] = $arFields['PREV_ENRICHED_DESCRIPTION'];
					}
					if (isset($arFields['SETTINGS']['ADDRESS_FORMATTED']))
					{
						$arCurEntity['LOCATION'] = $arFields['SETTINGS']['ADDRESS_FORMATTED'];
					}
					elseif (isset($arFields['LOCATION']))
					{
						if (self::isLocationFeatureEnabled())
						{
							$arCurEntity['LOCATION'] = $arFields['LOCATION'];
						}
						elseif (isset($arPrevEntity['LOCATION']))
						{
							$arCurEntity['LOCATION'] = $arPrevEntity['LOCATION'];
						}
					}
					else
					{
						$arCurEntity['LOCATION'] = '';
					}

					if (isset($arFields['SETTINGS']['COLOR']))
					{
						$arCurEntity['SETTINGS']['COLOR'] = $arFields['SETTINGS']['COLOR'];
					}

					if (isset($arFields['CALENDAR_ADDITIONAL_DESCRIPTION_DATA']))
					{
						$arCurEntity['CALENDAR_ADDITIONAL_DESCRIPTION_DATA'] = $arFields['CALENDAR_ADDITIONAL_DESCRIPTION_DATA'];
					}

					$eventID = self::SaveCalendarEvent($arCurEntity, $arPrevEntity);
				}
				else
				{
					self::DeleteCalendarEvent($arCurEntity);
				}

				if (is_int($eventID))
				{
					self::SetCalendarEventId($eventID, $ID);

					$arCurEntity['CALENDAR_EVENT_ID'] = $eventID;
				}
			}
		}

		if ($needSynchronizePingQueue)
		{
			if ($provider === null || $provider::needSynchronizePingQueue($arCurEntity))
			{
				PingQueue::getInstance()->register($ID, $curCompleted, $curDeadline);
			}
			else
			{
				PingQueue::getInstance()->unregister($ID);
			}
		}

		$registerSonetEvent = isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true;
		$isSonetEventRegistred = false;

		if ($provider !== null && $provider::canUseLiveFeedEvents($providerTypeId) === false)
		{
			$registerSonetEvent = false;
			$isSonetEventRegistred = true;
		}

		if (!array_key_exists('LIGHT_COUNTER_AT', $arCurEntity))
		{
			/** @var ActCounterLightTimeRepo $lightCounterRepo */
			$lightCounterRepo = ServiceLocator::getInstance()->get('crm.activity.actcounterlighttimerepo');
			$arCurEntity['LIGHT_COUNTER_AT'] = $lightCounterRepo->queryLightTimeByActivityId($arCurEntity['ID']);
		}

		\Bitrix\Crm\Timeline\ActivityController::getInstance()->onModify(
			$ID,
			[
				'CURRENT_FIELDS' => $arCurEntity,
				'CURRENT_BINDINGS' => $arBindings,
				'PREVIOUS_FIELDS' => $arPrevEntity,
				'CURRENT_USER' => $options['CURRENT_USER'] ?? null,
				'ADDITIONAL_PARAMS' => [
					'CUSTOM_CREATION_TIME' => $options['CUSTOM_CREATION_TIME'] ?? null,
					'MOVE_BINDINGS_MAP' => $options['MOVE_BINDINGS_MAP'] ?? [],
				],
			],
		);

		if($registerSonetEvent)
		{
			$isSonetEventSynchronized = self::SynchronizeLiveFeedEvent(
				$ID,
				array(
					'PROCESS_BINDINGS' => $bindingsChanged,
					'BINDINGS' => $bindingsChanged ? $arBindings : null,
					'REFRESH_DATE' => isset($arFields['COMPLETED']) && $arFields['COMPLETED'] !== $arPrevEntity['COMPLETED'],
					'START_RESPONSIBLE_ID' => $arPrevEntity['RESPONSIBLE_ID'],
					'FINAL_RESPONSIBLE_ID' => $responsibleID,
					'EDITOR_ID' => (intval($arFields["EDITOR_ID"]) > 0 ? $arFields["EDITOR_ID"] : CCrmSecurityHelper::GetCurrentUserID()),
					'TYPE_ID' => self::GetActivityType($arCurEntity),
					'SUBJECT' => (isset($arFields['SUBJECT']) ? $arFields['SUBJECT'] : $arPrevEntity['SUBJECT'])
				)
			);

			if(!$isSonetEventSynchronized)
			{
				$itemFields = self::GetByID($ID);
				if(is_array($itemFields))
				{
					$itemFields['BINDINGS'] = $arBindings;
					$sonetEventID = self::RegisterLiveFeedEvent($itemFields);
					$isSonetEventRegistred = is_int($sonetEventID) && $sonetEventID > 0;

					if ($responsibleID > 0 && Settings\Crm::isLiveFeedRecordsGenerationEnabled())
					{
						CCrmSonetSubscription::RegisterSubscription(
							CCrmOwnerType::Activity,
							$ID,
							CCrmSonetSubscriptionType::Responsibility,
							$responsibleID
						);
					}
				}
			}
		}

		if (
			!$isSonetEventRegistred
			&& $responsibleID !== $prevResponsibleID
			&& Settings\Crm::isLiveFeedRecordsGenerationEnabled()
		)
		{
			CCrmSonetSubscription::ReplaceSubscriptionByEntity(
				CCrmOwnerType::Activity,
				$ID,
				CCrmSonetSubscriptionType::Responsibility,
				$responsibleID,
				$prevResponsibleID,
				$registerSonetEvent
			);
		}

		if($storageElementsSerialized)
		{
			$arFields['STORAGE_ELEMENT_IDS'] = $storageElementIDs;
		}

		$USER_FIELD_MANAGER->update(static::UF_ENTITY_TYPE, $ID, $arFields, $arFields['EDITOR_ID']);

		if ($provider !== null)
		{
			$provider::onAfterUpdate(
				$ID,
				$arFields,
				$arPrevEntity,
				$arCurEntity,
			);
		}

		$rsEvents = GetModuleEvents('crm', 'OnActivityUpdate');
		while ($arEvent = $rsEvents->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array($ID, &$arFields));
		}

		$event = new \Bitrix\Main\Event(
			'crm', 'OnActivityModified',
			array(
				'before'  => $arPrevEntity,
				'current' => $arCurEntity,
			)
		);
		$event->send();

		if(($arFields['COMPLETED'] ?? null) === 'Y')
		{
			$ownerId = $arFields['OWNER_ID'] ?? $arPrevEntity['OWNER_ID'];
			$ownerTypeId = $arFields['OWNER_TYPE_ID'] ?? $arPrevEntity['OWNER_TYPE_ID'];
			Crm\Ml\Scoring::queuePredictionUpdate($ownerTypeId, $ownerId, [
				'EVENT_TYPE' => Crm\Ml\Scoring::EVENT_ACTIVITY,
				'ASSOCIATED_ACTIVITY_ID'=> $ID
			]);
		}

		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$GLOBALS["CACHE_MANAGER"]->ClearByTag("CRM_ACTIVITY_".$ID);
		}

		return true;
	}

	protected static function appendOwnerFromBindings(array &$fields): void
	{
		if (
			(empty($fields['OWNER_ID']) || empty($fields['OWNER_TYPE_ID']))
			&& !empty($fields['BINDINGS'])
			&& is_array($fields['BINDINGS'])
		)
		{
			$bindingsFound = false;
			foreach ($fields['BINDINGS'] as $binding)
			{
				if ($binding['OWNER_TYPE_ID'] != CCrmOwnerType::Contact && $binding['OWNER_TYPE_ID'] != CCrmOwnerType::Company)
				{
					$fields['OWNER_ID'] = $binding['OWNER_ID'];
					$fields['OWNER_TYPE_ID'] = $binding['OWNER_TYPE_ID'];
					$bindingsFound = true;
				}
			}
			if (!$bindingsFound)
			{
				$fields['OWNER_ID'] = $fields['BINDINGS'][0]['OWNER_ID'];
				$fields['OWNER_TYPE_ID'] = $fields['BINDINGS'][0]['OWNER_TYPE_ID'];
			}
		}
	}

	public static function Delete($ID, $checkPerms = true, $regEvent = true, $options = array())
	{
		global $USER_FIELD_MANAGER;
		$ID = intval($ID);
		if(!is_array($options))
		{
			$options = array();
		}

		$movedToRecycleBin = isset($options['MOVED_TO_RECYCLE_BIN']) && $options['MOVED_TO_RECYCLE_BIN'];

		$events = GetModuleEvents('crm', 'OnBeforeActivityDelete');
		while ($event = $events->Fetch())
		{
			if (ExecuteModuleEventEx($event, array($ID)) === false)
			{
				return false;
			}
		}

		$ary = (isset($options['ACTUAL_ITEM']) && is_array($options['ACTUAL_ITEM']))
			? $options['ACTUAL_ITEM']
			: self::GetByID($ID, $checkPerms);

		if(!is_array($ary))
		{
			return false; //is not found
		}

		$incomingChannel = \Bitrix\Crm\Activity\IncomingChannel::getInstance();
		$isIncomingChannel = $incomingChannel->isIncomingChannel($ID);
		$ary['IS_INCOMING_CHANNEL'] = $isIncomingChannel ? 'Y' : 'N';
		$pingOffset = PingOffset::getInstance();
		$pingQueue = PingQueue::getInstance();
		if(!$movedToRecycleBin && \Bitrix\Crm\Recycling\ActivityController::isEnabled())
		{
			$enableRecycleBin = (isset($options['ENABLE_RECYCLE_BIN']) && $options['ENABLE_RECYCLE_BIN'])
				|| \Bitrix\Crm\Settings\ActivitySettings::getCurrent()->isRecycleBinEnabled();

			if($enableRecycleBin)
			{
				$provider = self::GetActivityProvider($ary);

				if ($provider)
				{
					$ary['SUBJECT'] = $provider::getActivityTitle($ary);
				}

				$moveToBinOpts = [
					'FIELDS' => $ary
				];

				if (isset($options['RECYCLE_BIN_FORCE_USER_ID']))
				{
					$moveToBinOpts['FORCE_USER_ID'] = $options['RECYCLE_BIN_FORCE_USER_ID'];
				}

				$recycleBinResult = \Bitrix\Crm\Recycling\ActivityController::getInstance()->moveToBin(
					$ID,
					$moveToBinOpts,
				);

				if($recycleBinResult->isSuccess())
				{
					$movedToRecycleBin = true;

					$resultData = $recycleBinResult->getData();
					if(is_array($resultData) && isset($resultData['isDeleted']) && $resultData['isDeleted'])
					{
						$incomingChannel->unregister($ID);
						$pingOffset->unregister($ID);
						$pingQueue->unregister($ID);

						return true;
					}
				}
			}
		}

		if($movedToRecycleBin)
		{
			$options['SKIP_FILES'] = true;
		}

		$arBindings = isset($options['ACTUAL_BINDINGS']) && is_array($options['ACTUAL_BINDINGS'])
			? $options['ACTUAL_BINDINGS']
			: self::GetBindings($ID);

		if (is_array($arBindings))
		{
			$monitor = Crm\Service\Timeline\Monitor::getInstance();
			foreach ($arBindings as $singleBinding)
			{
				$itemIdentifier = Crm\ItemIdentifier::createFromArray($singleBinding);
				if ($itemIdentifier)
				{
					$monitor->onActivityRemoveIfSuitable($itemIdentifier, $ID);
				}
			}
		}

		if(!self::InnerDelete($ID, $options))
		{
			return false;
		}

		$USER_FIELD_MANAGER->Delete(static::UF_ENTITY_TYPE, $ID);
		$incomingChannel->unregister($ID);
		$pingOffset->unregister($ID);
		$pingQueue->unregister($ID);

		$responsibleID = isset($ary['RESPONSIBLE_ID']) ? (int)$ary['RESPONSIBLE_ID'] : 0;

		if (is_array($arBindings))
		{

			$oldLightTimeDate = self::queryLightTimeDateByActId($ID);

			(new FillActLightCounter())->onActDelete($ary);

			\Bitrix\Crm\Activity\UncompletedActivity::synchronizeForUncompletedActivityChange(
				\Bitrix\Crm\Activity\UncompletedActivityChange::create(
					$ID,
					$ary,
					$arBindings,
					[],
					[],
					$oldLightTimeDate,
					null
				)
			);
		}
		// Synchronize user activity -->
		$skipUserActivitySync = isset($options['SKIP_USER_ACTIVITY_SYNC']) ? $options['SKIP_USER_ACTIVITY_SYNC'] : false;
		if(!$skipUserActivitySync && is_array($arBindings))
		{
			foreach($arBindings as $arBinding)
			{
				if($responsibleID > 0)
				{
					self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], $responsibleID);
				}
				self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], 0);
			}
		}
		// <-- Synchronize user activity

		if(is_array($arBindings))
		{
			if($regEvent)
			{
				foreach($arBindings as $arBinding)
				{
					self::RegisterRemoveEvent(
						$arBinding['OWNER_TYPE_ID'],
						$arBinding['OWNER_ID'],
						$ary,
						$checkPerms,
						CCrmSecurityHelper::GetCurrentUserID()
					);
				}
				unset($arBinding);
			}

			$skipStatistics = isset($options['SKIP_STATISTICS']) ? $options['SKIP_STATISTICS'] : false;
			if(!$skipStatistics)
			{
				$completed = isset($ary['COMPLETED']) && $ary['COMPLETED'] === 'Y';
				$deadline = isset($ary['DEADLINE']) ? $ary['DEADLINE'] : '';
				if($completed && $deadline)
				{
					$deadline = \Bitrix\Main\Type\DateTime::createFromUserTime($deadline);
					$deadline->setTime(0, 0, 0);
					foreach ($arBindings as $arBinding)
					{
						$curOwnerTypeID = isset($arBinding['OWNER_TYPE_ID']) ? intval($arBinding['OWNER_TYPE_ID']) : 0;
						$curOwnerID = isset($arBinding['OWNER_ID']) ? intval($arBinding['OWNER_ID']) : 0;
						if ($curOwnerID > 0 && !CCrmDateTimeHelper::IsMaxDatabaseDate($deadline))
						{
							if ($curOwnerTypeID === CCrmOwnerType::Deal)
							{
								Bitrix\Crm\Statistics\DealActivityStatisticEntry::register($curOwnerID, null, array('DATE' => $deadline));
							}
							elseif ($curOwnerTypeID === CCrmOwnerType::Lead)
							{
								Bitrix\Crm\Statistics\LeadActivityStatisticEntry::register($curOwnerID, null, array('DATE' => $deadline));
							}
						}
					}
					unset($arBinding);
				}

				$ary['BINDINGS'] = $arBindings;
				\Bitrix\Crm\Activity\CommunicationStatistics::unregisterActivity($ary);
			}
		}

		$skipAssocEntity = isset($options['SKIP_ASSOCIATED_ENTITY']) ? (bool)$options['SKIP_ASSOCIATED_ENTITY'] : false;
		$associatedEntityId = isset($ary['ASSOCIATED_ENTITY_ID']) ? (int)$ary['ASSOCIATED_ENTITY_ID'] : 0;
		$providerTypeId = isset($ary['PROVIDER_TYPE_ID']) ? (string) $ary['PROVIDER_TYPE_ID'] : null;
		$provider = self::GetActivityProvider($ary);

		if(!$skipAssocEntity && $provider !== null && $associatedEntityId > 0)
		{
			$provider::deleteAssociatedEntity($associatedEntityId, $ary, $options);
		}

		if ($provider !== null && $provider::canUseCalendarEvents($providerTypeId))
		{
			$skipCalendarEvent = isset($options['SKIP_CALENDAR_EVENT']) ? (bool)$options['SKIP_CALENDAR_EVENT'] : null;

			if (!$skipCalendarEvent)
			{
				self::DeleteCalendarEvent($ary);
			}
		}

		\Bitrix\Crm\Statistics\ActivityStatisticEntry::unregister($ID);
		\Bitrix\Crm\Integration\Channel\ActivityChannelBinding::unregisterAll($ID);

		if (isset($recycleBinResult))
		{
			$recycleBinEntityId = $recycleBinResult->getData()['recyclingEntityId'];
		}
		else
		{
			$recycleBinEntityId = Crm\Recycling\ActivityController::getInstance()->getRecyclingEntityId($ID);
		}

		\Bitrix\Crm\Timeline\ActivityController::getInstance()->onDelete(
			$ID,
			[
				'FIELDS' => $ary,
				'BINDINGS' => $arBindings,
				'MOVED_TO_RECYCLE_BIN' => $movedToRecycleBin,
				'RECYCLE_BIN_ENTITY_ID' => $recycleBinEntityId,
			]
		);
		if (is_array($arBindings))
		{
			\Bitrix\Crm\Counter\Monitor::getInstance()->onActivityDelete($ary, $arBindings, $oldLightTimeDate);
		}

		FastSearch\Sync\Monitor::getInstance()->onActivityDelete($ID, $ary);

		\Bitrix\Crm\Ml\Scoring::onActivityDelete($ID);

		if(!$movedToRecycleBin)
		{
			self::UnregisterLiveFeedEvent($ID);
			CCrmSonetSubscription::UnRegisterSubscriptionByEntity(CCrmOwnerType::Activity, $ID);
		}

		if ($provider !== null)
		{
			$provider::onAfterDelete($ID, $ary);
		}

		$rsEvents = GetModuleEvents('crm', 'OnActivityDelete');
		while ($arEvent = $rsEvents->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array($ID));
		}

		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$GLOBALS["CACHE_MANAGER"]->ClearByTag("CRM_ACTIVITY_".$ID);
		}

		return true;
	}
	// <-- CRUD
	//Service -->
	protected static function InnerDelete($ID, $options = array())
	{
		global $DB;

		$ID = intval($ID);
		if($ID <= 0)
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return false;
		}

		CCrmEntityHelper::RemoveCached(self::CACHE_NAME, $ID);

		$actualItem = (isset($options['ACTUAL_ITEM']) && is_array($options['ACTUAL_ITEM']))
			? $options['ACTUAL_ITEM'] : null;
		$movedToRecycleBin = isset($options['MOVED_TO_RECYCLE_BIN']) && $options['MOVED_TO_RECYCLE_BIN'];

		$result = true;
		if(!(isset($options['SKIP_BINDINGS']) && $options['SKIP_BINDINGS']))
		{
			$result = self::DeleteBindings($ID, false);
		}

		if($result && !(isset($options['SKIP_COMMUNICATIONS']) && $options['SKIP_COMMUNICATIONS']))
		{
			$result = self::DeleteCommunications($ID);
		}

		$skipFiles = $movedToRecycleBin || (isset($options['SKIP_FILES']) && $options['SKIP_FILES']);
		if($result && !$skipFiles)
		{
			$result = self::DeleteStorageElements($ID, $actualItem);
			if($result)
			{
				$result = $DB->Query(
					'DELETE FROM '.CCrmActivity::ELEMENT_TABLE_NAME.' WHERE ACTIVITY_ID = '.$ID
				);
			}
		}

		if($result)
		{
			$result = $DB->Query('DELETE FROM '.CCrmActivity::TABLE_NAME.' WHERE ID = '.$ID, true) !== false;
		}

		return $result;
	}
	protected static function NormalizeStorageElementIDs(&$arElementIDs)
	{
		$result = array();
		foreach($arElementIDs as $elementID)
		{
			$result[] = intval($elementID);
		}

		return array_unique($result, SORT_NUMERIC);
	}
	protected static function NormalizeDateTimeFields(&$arFields)
	{
		global $DB;

		//With format 'MM/DD/YYYY H:MI:SS TT' call MakeTimeStamp("01/01/1970 01:00 PM") will not work.;
		if(isset($arFields['START_TIME']))
		{
			$arFields['START_TIME'] = CCrmDateTimeHelper::NormalizeDateTime($arFields['START_TIME']);
		}

		if(isset($arFields['END_TIME']))
		{
			$arFields['END_TIME'] = CCrmDateTimeHelper::NormalizeDateTime($arFields['END_TIME']);
		}

		if(isset($arFields['DEADLINE']))
		{
			$arFields['DEADLINE'] = CCrmDateTimeHelper::NormalizeDateTime($arFields['DEADLINE']);
		}

		$offset = isset($arFields['TIME_ZONE_OFFSET']) ? (int)$arFields['TIME_ZONE_OFFSET'] : 0;
		if($offset !== 0)
		{
			CTimeZone::Disable();

			if(isset($arFields['START_TIME']))
			{
				$arFields['~START_TIME'] = $DB->CharToDateFunction(
					CCrmDateTimeHelper::SubtractOffset($arFields['START_TIME'], $offset)
				);
				unset($arFields['START_TIME']);
			}

			if(isset($arFields['END_TIME']))
			{
				$arFields['~END_TIME'] = $DB->CharToDateFunction(
					CCrmDateTimeHelper::SubtractOffset($arFields['END_TIME'], $offset)
				);
				unset($arFields['END_TIME']);
			}

			if(isset($arFields['DEADLINE']))
			{
				$arFields['~DEADLINE'] = $DB->CharToDateFunction(
					CCrmDateTimeHelper::SubtractOffset($arFields['DEADLINE'], $offset)
				);
				unset($arFields['DEADLINE']);
			}

			CTimeZone::Enable();
		}
	}
	public static function GetFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'OWNER_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Immutable)
				),
				'OWNER_TYPE_ID' => array(
					'TYPE' => 'crm_enum_ownertype',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Immutable)
				),
				'TYPE_ID' => array(
					'TYPE' => 'crm_enum_activitytype',
					'ATTRIBUTES' => array(
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					)
				),
				'PROVIDER_ID' => array('TYPE' => 'string'),
				'PROVIDER_TYPE_ID' => array('TYPE' => 'string'),
				'PROVIDER_GROUP_ID' => array('TYPE' => 'string'),
				'ASSOCIATED_ENTITY_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'SUBJECT' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'START_TIME' => array('TYPE' => 'datetime'),
				'END_TIME' => array('TYPE' => 'datetime'),
				'DEADLINE' => array('TYPE' => 'datetime'),
				'COMPLETED' => array('TYPE' => 'char'),
				'STATUS' => array('TYPE' => 'crm_enum_activitystatus'),
				'RESPONSIBLE_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'PRIORITY' => array('TYPE' => 'crm_enum_activitypriority'),
				'NOTIFY_TYPE' => array('TYPE' => 'crm_enum_activitynotifytype'),
				'NOTIFY_VALUE' => array('TYPE' => 'integer'),
				'DESCRIPTION' => array('TYPE' => 'string'),
				'DESCRIPTION_TYPE' => array('TYPE' => 'crm_enum_contenttype'),
				'DIRECTION' => array('TYPE' => 'crm_enum_activitydirection'),
				'LOCATION' => array('TYPE' => 'string'),
				'CREATED' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'AUTHOR_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'LAST_UPDATED' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'EDITOR_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'SETTINGS' => array('TYPE' => 'object'),
				'ORIGIN_ID' => array('TYPE' => 'string'),
				'ORIGINATOR_ID' => array('TYPE' => 'string'),
				'RESULT_STATUS' => array('TYPE' => 'integer'),
				'RESULT_STREAM' => array('TYPE' => 'integer'),
				'RESULT_SOURCE_ID' => array('TYPE' => 'string'),
				'PROVIDER_PARAMS' => array('TYPE' => 'object'),
				'PROVIDER_DATA' => array('TYPE' => 'string'),
				'RESULT_MARK' => array('TYPE' => 'integer'),
				'RESULT_VALUE' => array('TYPE' => 'double'),
				'RESULT_SUM' => array('TYPE' => 'double'),
				'RESULT_CURRENCY_ID' => array('TYPE' => 'string'),
				'AUTOCOMPLETE_RULE' => array('TYPE' => 'integer'),
			);
		}
		return self::$FIELD_INFOS;
	}
	public static function GetCommunicationFieldsInfo()
	{
		if(!self::$COMM_FIELD_INFOS)
		{
			self::$COMM_FIELD_INFOS = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'ACTIVITY_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'ENTITY_ID' => array('TYPE' => 'integer'),
				'ENTITY_TYPE_ID' => array('TYPE' => 'integer'),
				'TYPE' => array('TYPE' => 'string'),
				'VALUE' => array('TYPE' => 'string'),
				'OWNER_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
				),
				'OWNER_TYPE_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
				),
				'ENTITY_SETTINGS' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
				)
			);
		}
		return self::$COMM_FIELD_INFOS;
	}
	protected  static function GetFields()
	{
		if(!isset(self::$FIELDS))
		{
			$responsibleJoin = 'LEFT JOIN b_user U ON A.RESPONSIBLE_ID = U.ID';
			$bindingTableName = CCrmActivity::BINDING_TABLE_NAME;
			$bindingJoin = "INNER JOIN {$bindingTableName} BT ON A.ID = BT.ACTIVITY_ID";

			self::$FIELDS = array(
				'ID' => array('FIELD' => 'A.ID', 'TYPE' => 'int'),
				'OWNER_ID' => array('FIELD' => 'A.OWNER_ID', 'TYPE' => 'int'),
				'OWNER_TYPE_ID' => array('FIELD' => 'A.OWNER_TYPE_ID', 'TYPE' => 'int'),
				'BINDING_OWNER_ID' => array('FIELD' => 'BT.OWNER_ID', 'TYPE' => 'int', 'FROM' => $bindingJoin, 'DEFAULT' => 'N'),
				'BINDING_OWNER_TYPE_ID' => array('FIELD' => 'BT.OWNER_TYPE_ID', 'TYPE' => 'int', 'FROM' => $bindingJoin, 'DEFAULT' => 'N'),
				'TYPE_ID' => array('FIELD' => 'A.TYPE_ID', 'TYPE' => 'int'),
				'PROVIDER_ID' => array('FIELD' => 'A.PROVIDER_ID', 'TYPE' => 'string'),
				'PROVIDER_TYPE_ID' => array('FIELD' => 'A.PROVIDER_TYPE_ID', 'TYPE' => 'string'),
				'PROVIDER_GROUP_ID' => array('FIELD' => 'A.PROVIDER_GROUP_ID', 'TYPE' => 'string'),
				'CALENDAR_EVENT_ID' => array('FIELD' => 'A.CALENDAR_EVENT_ID', 'TYPE' => 'int'),
				'PARENT_ID' => array('FIELD' => 'A.PARENT_ID', 'TYPE' => 'int'),
				'THREAD_ID' => array('FIELD' => 'A.THREAD_ID', 'TYPE' => 'int'),
				'ASSOCIATED_ENTITY_ID' => array('FIELD' => 'A.ASSOCIATED_ENTITY_ID', 'TYPE' => 'int'),
				'URN' => array('FIELD' => 'A.URN', 'TYPE' => 'string'),
				'SUBJECT' => array('FIELD' => 'A.SUBJECT', 'TYPE' => 'string'),
				'CREATED' => array('FIELD' => 'A.CREATED', 'TYPE' => 'datetime'),
				'LAST_UPDATED' => array('FIELD' => 'A.LAST_UPDATED', 'TYPE' => 'datetime'),
				'START_TIME' => array('FIELD' => 'A.START_TIME', 'TYPE' => 'datetime'),
				'END_TIME' => array('FIELD' => 'A.END_TIME', 'TYPE' => 'datetime'),
				'DEADLINE' => array('FIELD' => 'A.DEADLINE', 'TYPE' => 'datetime'),
				'COMPLETED' => array('FIELD' => 'A.COMPLETED', 'TYPE' => 'char'),
				'STATUS' => array('FIELD' => 'A.STATUS', 'TYPE' => 'int'),
				'RESPONSIBLE_ID' => array('FIELD' => 'A.RESPONSIBLE_ID', 'TYPE' => 'int'),
				'RESPONSIBLE_LOGIN' => array('FIELD' => 'U.LOGIN', 'TYPE' => 'string', 'FROM' => $responsibleJoin),
				'RESPONSIBLE_NAME' => array('FIELD' => 'U.NAME', 'TYPE' => 'string', 'FROM' => $responsibleJoin),
				'RESPONSIBLE_LAST_NAME' => array('FIELD' => 'U.LAST_NAME', 'TYPE' => 'string', 'FROM' => $responsibleJoin),
				'RESPONSIBLE_SECOND_NAME' => array('FIELD' => 'U.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $responsibleJoin),
				'RESPONSIBLE_PERSONAL_GENDER' => array('FIELD' => 'U.PERSONAL_GENDER', 'TYPE' => 'char', 'FROM' => $responsibleJoin),
				'RESPONSIBLE_PERSONAL_PHOTO' => array('FIELD' => 'U.PERSONAL_PHOTO', 'TYPE' => 'int', 'FROM' => $responsibleJoin),
				'PRIORITY' => array('FIELD' => 'A.PRIORITY', 'TYPE' => 'int'),
				'NOTIFY_TYPE' => array('FIELD' => 'A.NOTIFY_TYPE', 'TYPE' => 'int'),
				'NOTIFY_VALUE' => array('FIELD' => 'A.NOTIFY_VALUE', 'TYPE' => 'int'),
				'DESCRIPTION' => array('FIELD' => 'A.DESCRIPTION', 'TYPE' => 'string'),
				'DESCRIPTION_TYPE' => array('FIELD' => 'A.DESCRIPTION_TYPE', 'TYPE' => 'int'),
				'DIRECTION' => array('FIELD' => 'A.DIRECTION', 'TYPE' => 'int'),
				'LOCATION' => array('FIELD' => 'A.LOCATION', 'TYPE' => 'string'),
				'STORAGE_TYPE_ID' => array('FIELD' => 'A.STORAGE_TYPE_ID', 'TYPE' => 'int'),
				'STORAGE_ELEMENT_IDS' => array('FIELD' => 'A.STORAGE_ELEMENT_IDS', 'TYPE' => 'string'),
				'SETTINGS' => array('FIELD' => 'A.SETTINGS', 'TYPE' => 'string'),
				'ORIGINATOR_ID' => array('FIELD' => 'A.ORIGINATOR_ID', 'TYPE' => 'string'),
				'ORIGIN_ID' => array('FIELD' => 'A.ORIGIN_ID', 'TYPE' => 'string'),
				'AUTHOR_ID' => array('FIELD' => 'A.AUTHOR_ID', 'TYPE' => 'int'),
				'EDITOR_ID' => array('FIELD' => 'A.EDITOR_ID', 'TYPE' => 'int'),
				'PROVIDER_PARAMS' => array('FIELD' => 'A.PROVIDER_PARAMS', 'TYPE' => 'string'),
				'PROVIDER_DATA' => array('FIELD' => 'A.PROVIDER_DATA', 'TYPE' => 'string'),
				'RESULT_MARK' => array('FIELD' => 'A.RESULT_MARK', 'TYPE' => 'int'),
				'RESULT_VALUE' => array('FIELD' => 'A.RESULT_VALUE', 'TYPE' => 'double'),
				'RESULT_SUM' => array('FIELD' => 'A.RESULT_SUM', 'TYPE' => 'double'),
				'RESULT_CURRENCY_ID' => array('FIELD' => 'A.RESULT_CURRENCY_ID', 'TYPE' => 'string'),
				'RESULT_STATUS' => array('FIELD' => 'A.RESULT_STATUS', 'TYPE' => 'int'),
				'RESULT_STREAM' => array('FIELD' => 'A.RESULT_STREAM', 'TYPE' => 'int'),
				'RESULT_SOURCE_ID' => array('FIELD' => 'A.RESULT_SOURCE_ID', 'TYPE' => 'string'),
				'AUTOCOMPLETE_RULE' => array('FIELD' => 'A.AUTOCOMPLETE_RULE', 'TYPE' => 'int'),
			);
		}

		$arFields = self::$FIELDS;
		CCrmActivity::CreateLogicalField('TYPE_NAME', $arFields);
		return $arFields;
	}

	public static function CheckStorageElementExists($activityID, $storageTypeID, $elementID)
	{
		global $DB;
		$activityID = (int)$activityID;
		$storageTypeID = (int)$storageTypeID;
		$elementID = (int)$elementID;

		$dbResult = $DB->Query(
			'SELECT 1 FROM '.CCrmActivity::ELEMENT_TABLE_NAME.' WHERE ACTIVITY_ID = '.$activityID.' AND STORAGE_TYPE_ID = '.$storageTypeID.' AND ELEMENT_ID = '.$elementID
		);
		return is_array($dbResult->Fetch());
	}
	public static function HandleStorageElementDeletion($storageTypeID, $elementID)
	{
		global $DB;

		$storageTypeID = (int)$storageTypeID;
		$elementID = (int)$elementID;

		$dbResult = $DB->Query(
			'SELECT ACTIVITY_ID FROM '.CCrmActivity::ELEMENT_TABLE_NAME.' WHERE STORAGE_TYPE_ID = '.$storageTypeID.' AND ELEMENT_ID = '.$elementID
		);

		while($arResult = $dbResult->Fetch())
		{
			$entityID = isset($arResult['ACTIVITY_ID']) ? (int)$arResult['ACTIVITY_ID'] : 0;
			if($entityID <= 0)
			{
				continue;
			}

			$dbEntity = self::GetList(
				array(),
				array('ID' => $entityID),
				false,
				false,
				array('STORAGE_TYPE_ID', 'STORAGE_ELEMENT_IDS')
			);

			$arEntity = $dbEntity->Fetch();
			if(!is_array($arEntity))
			{
				continue;
			}

			$arEntity['STORAGE_TYPE_ID'] = isset($arEntity['STORAGE_TYPE_ID'])
				? (int)$arEntity['STORAGE_TYPE_ID'] : $storageTypeID;
			self::PrepareStorageElementIDs($arEntity);
			if(!empty($arEntity['STORAGE_ELEMENT_IDS']))
			{
				$arEntity['STORAGE_ELEMENT_IDS'] = array_diff($arEntity['STORAGE_ELEMENT_IDS'], array($elementID));
			}

			self::Update($entityID, $arEntity, false, true);
		}
	}

	private static function rebuildFieldsForDataBase($action, &$fields, $ID, $params = null)
	{
		global $DB, $USER_FIELD_MANAGER, $APPLICATION;
		self::ClearErrors();

		if(!(is_array($fields) && count($fields) > 0))
		{
			self::RegisterError(array('text' => 'Fields is not specified.'));
			return false;
		}
		$prevFields = null;

		if(!is_array($params))
		{
			$params = array();
		}

		if($action == 'ADD')
		{
			// Validation
			if (!isset($fields['OWNER_ID']))
			{
				self::RegisterError(array('text' => 'OWNER_ID is not assigned.'));
			}

			if (!isset($fields['OWNER_TYPE_ID']))
			{
				self::RegisterError(array('text' => 'OWNER_TYPE_ID is not assigned.'));
			}

			if (isset($fields['PROVIDER_ID']) && empty($fields['TYPE_ID']))
				$fields['TYPE_ID'] = CCrmActivityType::Provider;

			if (!isset($fields['TYPE_ID']))
			{
				self::RegisterError(array('text' => 'TYPE_ID is not assigned.'));
			}
			elseif(!CCrmActivityType::IsDefined($fields['TYPE_ID']))
			{
				self::RegisterError(array('text' => 'TYPE_ID is not supported.'));
			}

			if ((int)$fields['TYPE_ID'] === CCrmActivityType::Provider && ($provider = self::GetActivityProvider($fields)) === null)
			{
				self::RegisterError(array('text' => 'Provider for custom activity is not found.'));
			}

			if (!isset($fields['SUBJECT']))
			{
				self::RegisterError(array('text' => 'SUBJECT is not assigned.'));
			}

			if (!isset($fields['RESPONSIBLE_ID']))
			{
				self::RegisterError(array('text' => 'RESPONSIBLE_ID is not assigned.'));
			}

			if (!isset($fields['NOTIFY_TYPE']))
			{
				$fields['NOTIFY_TYPE'] = CCrmActivityNotifyType::None;
			}

			if ($fields['NOTIFY_TYPE'] == CCrmActivityNotifyType::None)
			{
				$fields['NOTIFY_VALUE'] = 0;
			}
			elseif (!isset($fields['NOTIFY_VALUE']))
			{
				self::RegisterError(array('text' => 'NOTIFY_VALUE is not assigned.'));
			}

			if(isset($fields['COMPLETED']))
			{
				$completed = mb_strtoupper(strval($fields['COMPLETED']));
				if(!($completed == 'Y' || $completed == 'N'))
				{
					$completed = intval($fields['COMPLETED']) > 0 ? 'Y' : 'N';
				}
				$fields['COMPLETED'] = $completed;
			}
			else
			{
				$fields['COMPLETED'] = 'N';
			}

			if (!isset($fields['STATUS']))
			{
				$fields['STATUS'] = $fields['COMPLETED'] === 'Y'
					? CCrmActivityStatus::Completed
					: CCrmActivityStatus::Waiting;
			}

			if (!isset($fields['IS_HANDLEABLE']))
			{
				$fields['IS_HANDLEABLE'] = $fields['COMPLETED'] === 'N' ? 'Y' : 'N';
			}

			//region CREATED & LAST_UPDATED
			unset($fields['~CREATED'], $fields['LAST_UPDATED'], $fields['~LAST_UPDATED']);
			if(!(isset($params['PRESERVE_CREATION_TIME']) && $params['PRESERVE_CREATION_TIME'] === true))
			{
				unset($fields['CREATED']);
			}

			if(isset($fields['CREATED']))
			{
				$fields['LAST_UPDATED'] = $fields['CREATED'];
			}
			else
			{
				$fields['~CREATED'] = $fields['~LAST_UPDATED'] = $DB->CurrentTimeFunction();
			}
			//endregion

			if(!isset($fields['AUTHOR_ID']))
			{
				$currentUserId = CCrmPerms::GetCurrentUserID();
				$fields['AUTHOR_ID'] = $currentUserId > 0 ? $currentUserId : $fields['RESPONSIBLE_ID'];
			}

			if(!isset($fields['EDITOR_ID']))
			{
				$fields['EDITOR_ID'] = $fields['AUTHOR_ID'];
			}

			if (!isset($fields['~END_TIME']) && !isset($fields['END_TIME']) && isset($fields['START_TIME']))
			{
				$fields['END_TIME'] = $fields['START_TIME'];
			}
			elseif (!isset($fields['~START_TIME']) && !isset($fields['START_TIME']) && isset($fields['END_TIME']))
			{
				$fields['START_TIME'] = $fields['END_TIME'];
			}

			unset($fields['DEADLINE'], $fields['~DEADLINE']);

			if (!isset($fields['ASSOCIATED_ENTITY_ID']))
			{
				$fields['ASSOCIATED_ENTITY_ID'] = 0;
			}

			if (!isset($fields['PRIORITY']))
			{
				$fields['PRIORITY'] = CCrmActivityPriority::Low;
			}

			if (!isset($fields['DIRECTION']))
			{
				$fields['DIRECTION'] = CCrmActivityDirection::Undefined;
			}

			if (!isset($fields['DESCRIPTION_TYPE']))
			{
				$fields['DESCRIPTION_TYPE'] = CCrmContentType::PlainText;
			}

			if(!isset($fields['STORAGE_TYPE_ID']))
			{
				$fields['STORAGE_TYPE_ID'] = self::GetDefaultStorageTypeID();
			}

			if(!isset($fields['PARENT_ID']))
			{
				$fields['PARENT_ID'] = 0;
			}
		}
		else//if($action == 'UPDATE')
		{
			$prevFields = isset($params['PREVIOUS_FIELDS']) && is_array($params['PREVIOUS_FIELDS'])
				? $params['PREVIOUS_FIELDS'] : null;

			$currentUserID = isset($params['CURRENT_USER'])
				? (int)$params['CURRENT_USER'] : CCrmPerms::GetCurrentUserID();

			if(!is_array($prevFields) && !self::Exists($ID, false))
			{
				self::RegisterError(array('text' => "Could not find CrmActivity(ID = $ID)"));
			}

			if(isset($fields['COMPLETED']))
			{
				$completed = mb_strtoupper(strval($fields['COMPLETED']));
				if(!($completed == 'Y' || $completed == 'N'))
				{
					$completed = (int)$fields['COMPLETED'] > 0 ? 'Y' : 'N';
				}

				$fields['COMPLETED'] = $completed;

				//Adjust "STATUS" field according to "COMPLETED" field
				if($fields['COMPLETED'] === 'N')
				{
					//There are no options
					$fields['STATUS'] = CCrmActivityStatus::Waiting;
				}
				else
				{
					if(!isset($fields['STATUS']))
					{
						$fields['STATUS'] =
							isset($prevFields['STATUS']) && (int)$prevFields['STATUS'] === CCrmActivityStatus::AutoCompleted
								? CCrmActivityStatus::AutoCompleted
								: CCrmActivityStatus::Completed;
					}
					elseif((int)$fields['STATUS'] === CCrmActivityStatus::Waiting)
					{
						$fields['STATUS'] = CCrmActivityStatus::Completed;
					}
				}

				if (!isset($fields['IS_HANDLEABLE']))
				{
					$fields['IS_HANDLEABLE'] = $fields['COMPLETED'] === 'N' ? 'Y' : 'N';
				}
			}

			// Default settings
			if (isset($fields['CREATED']))
			{
				unset($fields['CREATED']);
			}
			if (isset($fields['LAST_UPDATED']))
			{
				unset($fields['LAST_UPDATED']);
			}
			$fields['~LAST_UPDATED'] = $DB->CurrentTimeFunction();

			/**
			 * The EDITOR_ID update for mail items is excluded,
			 * since the data of mail messages can be supplemented on the hits of different users.
			 */
			if (
				!isset($fields['EDITOR_ID'])
				&& ($fields['PROVIDER_ID'] ?? null) !== Provider\Email::getId()
			)
			{
				$userID = $fields['AUTHOR_ID'] ?? 0;
				if ($userID <= 0)
				{
					$userID = $currentUserID;
				}
				$fields['EDITOR_ID'] = $userID > 0 ? $userID : $fields['RESPONSIBLE_ID'];
			}
			unset($fields['AUTHOR_ID']);

			// TYPE_ID -->
			if(isset($fields['TYPE_ID']))
			{
				unset($fields['TYPE_ID']);
			}
			// <-- TYPE_ID
			if (isset($fields['PROVIDER_ID']))
			{
				unset($fields['PROVIDER_ID']);
			}

			unset($fields['DEADLINE'], $fields['~DEADLINE']);
		}

		$dateFieldsToCheck = [
			'START_DATE',
			'END_TIME',
			'DEADLINE',
			'CREATED',
			'LAST_UPDATED',
		];
		foreach ($dateFieldsToCheck as $dateField)
		{
			if (
				isset($fields[$dateField])
				&& $fields[$dateField] != ''
				&& !CheckDateTime($fields[$dateField])
			)
			{
				self::registerError([
					'text' => Loc::getMessage(
						'CRM_ERROR_FIELD_INCORRECT',
						[
							'%FIELD_NAME%' => Loc::getMessage('CRM_ACTIVITY_FIELD_' . $dateField)
						]
					),
				]);
			}
		}

		$provider = self::GetActivityProvider($action === 'ADD' ? $fields : $prevFields);
		if ($provider !== null)
		{
			$result = $provider::checkFields($action, $fields, $ID, $params);
			if (!$result->isSuccess())
			{
				/** @var Bitrix\Main\Error $error */
				foreach ($result->getErrorCollection() as $error)
				{
					self::RegisterError(array('text' => $error->getMessage()));
				}
			}

			if (empty($fields['PROVIDER_ID']))
			{
				$fields['PROVIDER_ID'] = $provider::getId();
			}

			if (empty($fields['PROVIDER_TYPE_ID']))
			{
				$fields['PROVIDER_TYPE_ID'] = $provider::getTypeId($action === 'ADD' ? $fields : $prevFields);
			}
		}

		if (isset($fields['PROVIDER_PARAMS']) && !is_array($fields['PROVIDER_PARAMS']))
			$fields['PROVIDER_PARAMS'] = array();

		//DEADLINE
		if($action == 'ADD' && !isset($fields['DEADLINE']) && !isset($fields['~DEADLINE']))
		{
			if(isset($fields['START_TIME']) && $fields['START_TIME'] !== '')
			{
				$fields['DEADLINE'] = $fields['START_TIME'];
			}
			elseif(isset($fields['~START_TIME']) && $fields['~START_TIME'] !== '')
			{
				$fields['~DEADLINE'] = $fields['~START_TIME'];
			}
			else
			{
				$fields['~DEADLINE'] = CCrmDateTimeHelper::GetMaxDatabaseDate();
			}
		}

		if ($action == 'ADD' && empty($fields['DEADLINE'] ?? null) && empty($fields['~DEADLINE'] ?? null))
		{
			unset($fields['DEADLINE']);
			$fields['~DEADLINE'] = CCrmDateTimeHelper::GetMaxDatabaseDate();
		}

		// incoming channel activity cannot have a deadline
		if (($fields['IS_INCOMING_CHANNEL'] ?? null) === 'Y')
		{
			unset($fields['DEADLINE']);
			$fields['~DEADLINE'] = CCrmDateTimeHelper::GetMaxDatabaseDate();
		}

		if (
			$action !== 'ADD'
			&& !isset($fields['IS_INCOMING_CHANNEL'])
			&& \Bitrix\Crm\Activity\IncomingChannel::getInstance()->isIncomingChannel($ID)
		)
		{
			unset($fields['DEADLINE']);
		}

		if (empty($params['DISABLE_USER_FIELD_CHECK']))
		{
			if (!$USER_FIELD_MANAGER->checkFields(static::UF_ENTITY_TYPE, $ID, $fields, false, empty($params['DISABLE_REQUIRED_USER_FIELD_CHECK'])))
			{
				$error = $APPLICATION->getException();
				self::registerError(array('text' => $error->getMessage()));
			}
		}

		return self::GetErrorCount() == 0;
	}
	public static function DeleteBindings($activityID, $registerBindingsChanges = true)
	{
		$activityID = intval($activityID);
		if($activityID <= 0)
		{
			return false;
		}

		global $DB;

		if ($registerBindingsChanges)
		{
			\Bitrix\Crm\Activity\UncompletedActivity::synchronizeForActivity($activityID);
		}

		$affectedBindings =
			Crm\ActivityBindingTable::query()
				->setSelect(['OWNER_TYPE_ID', 'OWNER_ID'])
				->where('ACTIVITY_ID', $activityID)
				->fetchCollection()
		;
		$affectedBindingsArray = array_map(
			fn(Crm\EO_ActivityBinding $binding): array => $binding->collectValues(),
			$affectedBindings->getAll(),
		);

		$DB->Query(
			'DELETE FROM '.CCrmActivity::BINDING_TABLE_NAME.' WHERE ACTIVITY_ID = '.$activityID
		);

		if ($registerBindingsChanges)
		{
			\Bitrix\Crm\Counter\Monitor::getInstance()->onChangeActivityBindings(
				$activityID,
				$affectedBindingsArray,
				[]
			);
		}

		Crm\Activity\Provider\ProviderManager::syncBadgesOnBindingsChange(
			$activityID,
			[],
			$affectedBindingsArray,
		);

		$monitor = Crm\Service\Timeline\Monitor::getInstance();
		foreach ($affectedBindings as $binding)
		{
			if (\CCrmOwnerType::IsDefined($binding->getOwnerTypeId()) && $binding->getOwnerId() > 0)
			{
				$monitor->onActivityRemoveIfSuitable(new Crm\ItemIdentifier($binding->getOwnerTypeId(), $binding->getOwnerId()), $activityID);
			}
		}

		return true;
	}
	public static function DeleteCommunications($activityID)
	{
		$activityID = intval($activityID);
		if($activityID <= 0)
		{
			return false;
		}

		global $DB;
		$commTableName = CCrmActivity::COMMUNICATION_TABLE_NAME;

		$DB->Query(
			"DELETE FROM {$commTableName} WHERE ACTIVITY_ID = {$activityID}"
		);

		return true;
	}
	public static function DeleteStorageElements($ID, array $arFields = null)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return false;
		}

		if(!is_array($arFields))
		{
			$dbResult = self::GetList(
				array(),
				array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('STORAGE_TYPE_ID', 'STORAGE_ELEMENT_IDS')
			);

			$arFields = $dbResult->Fetch();
			if(!is_array($arFields))
			{
				self::RegisterError(array('text' => "Could not find activity with ID '{$ID}'."));
				return false;
			}
		}

		$storageTypeID = isset($arFields['STORAGE_TYPE_ID'])
			? intval($arFields['STORAGE_TYPE_ID']) : StorageType::Undefined;

		self::PrepareStorageElementIDs($arFields);
		$storageElementIDs = isset($arFields['STORAGE_ELEMENT_IDS']) ? $arFields['STORAGE_ELEMENT_IDS'] : array();

		self::DoDeleteStorageElements($storageTypeID, $storageElementIDs);
		CCrmActivity::DoDeleteElementIDs($ID);

		return true;
	}
	public static function DoDeleteStorageElements($storageTypeID, array $storageElementIDs)
	{
		if (empty($storageElementIDs))
		{
			return;
		}

		if ($storageTypeID === StorageType::File)
		{
			foreach ($storageElementIDs as $storageElementID)
			{
				CFile::Delete($storageElementID);
			}
		}
		elseif($storageTypeID === StorageType::Disk)
		{
			\Bitrix\Main\Loader::includeModule('disk');

			$codeMap = [
				StorageFileType::getFolderXmlID(StorageFileType::EmailAttachment) => true,
				StorageFileType::getFolderXmlID(StorageFileType::CallRecord) => true,
				StorageFileType::getFolderXmlID(StorageFileType::Rest) => true
			];

			foreach ($storageElementIDs as $storageElementID)
			{
				$file = \Bitrix\Disk\File::loadById($storageElementID);
				if ($file === null)
				{
					continue;
				}

				$folder = $file->getParent();
				if ($folder === null)
				{
					continue;
				}

				$parentFolder = $folder->getParent();
				$parentFolderCode = isset($parentFolder) ? $parentFolder->getCode() : null;

				if (
					(
						isset($codeMap[$folder->getXmlId()])
						|| $folder->getCode() === SpecificFolder::CODE_FOR_UPLOADED_FILES
						|| $parentFolderCode === HiddenStorage::FOLDER_CODE_ACTIVITY
					)
					&& $file->countAttachedObjects() == 0
				)
				{
					$file->delete(\Bitrix\Disk\SystemUser::SYSTEM_USER_ID);
				}
			}
		}
	}
	protected static function RegisterError($arMsg)
	{
		if(is_array($arMsg) && isset($arMsg['text']))
		{
			self::$errors[] = $arMsg['text'];
			$GLOBALS['APPLICATION']->ThrowException(new CAdminException(array($arMsg)));
		}
	}
	private static function ClearErrors()
	{
		self::$errors = array();
	}

	/**
	 * @return \Bitrix\Crm\Activity\Provider\Base[] - List of providers.
	 */
	public static function GetProviders()
	{
		if (static::$PROVIDERS === null)
		{
			static::$PROVIDERS = \Bitrix\Crm\Activity\Provider\ProviderManager::getProviders();
		}
		return static::$PROVIDERS;
	}

	/**
	 * @param array $activity - Activity fields.
	 * @return null|Provider\Base
	 */
	public static function GetActivityProvider(array $activity)
	{
		return self::activityProvider($activity);
	}

	/**
	 * return activity provider even if is disabled
	 * @param array $activity
	 * @return Provider\Base|null
	 */
	public static function GetActivityProviderSafelyByDisabled(array $activity)
	{
		return self::activityProvider($activity, true);
	}

	/**
	 * @param array $activity
	 * @param bool $withInactiveProviders
	 * @return Provider\Base|null
	 */
	private static function activityProvider(array $activity, bool $withInactiveProviders = false)
	{
		$providerId = $activity['PROVIDER_ID'] ?? null;

		if ($withInactiveProviders)
		{
			$provider = $providerId ? self::GetProviderByIdSafelyByDisabled($providerId) : null;
		}
		else
		{
			$provider = $providerId ? self::GetProviderById($providerId) : null;
		}

		if ($provider === null && !empty($activity['TYPE_ID']))
		{
			$provider = self::GetProviderByType($activity['TYPE_ID']);
		}

		return $provider;
	}

	/**
	 * @param string $providerId Provider id.
	 * @return null|\Bitrix\Crm\Activity\Provider\Base
	 */
	public static function GetProviderById($providerId)
	{
		$providerId = (string) $providerId;
		$providers = static::GetProviders();

		return array_key_exists($providerId, $providers) ? $providers[$providerId] : null;
	}

	/**
	 * return activity provider even if is disabled
	 * @param string $providerId Provider id.
	 * @return null|\Bitrix\Crm\Activity\Provider\Base
	 */
	public static function GetProviderByIdSafelyByDisabled(string $providerId)
	{
		$providers = Provider\ProviderManager::getAllProviders();

		return array_key_exists($providerId, $providers) ? $providers[$providerId] : null;
	}

	/**
	 * Get compatible providers.
	 * @param int $typeId Activity type id.
	 * @return null|\Bitrix\Crm\Activity\Provider\Base
	 */
	public static function GetProviderByType($typeId)
	{
		$typeId = (int) $typeId;
		$provider = null;
		switch ($typeId)
		{
			case CCrmActivityType::Meeting:
				$provider = \Bitrix\Crm\Activity\Provider\Meeting::className();
				break;
			case CCrmActivityType::Call:
				$provider = \Bitrix\Crm\Activity\Provider\Call::className();
				break;
			case CCrmActivityType::Task:
				$provider = \Bitrix\Crm\Activity\Provider\Task::className();
				break;
			case CCrmActivityType::Email:
				$provider = \Bitrix\Crm\Activity\Provider\Email::className();
				break;
		}
		return $provider;
	}

	// <-- Service
	// Contract -->
	public static function GetByID($ID, $checkPerms = true)
	{
		$ID = intval($ID);

		if($ID <= 0)
		{
			return null;
		}

		$res = CCrmEntityHelper::GetCached(self::CACHE_NAME, $ID);
		if (is_array($res))
		{
			return $res;
		}

		$filter = array('ID' => $ID);
		if(!$checkPerms)
		{
			$filter['CHECK_PERMISSIONS'] = 'N';
		}

		$dbRes = self::GetList(array(), $filter);

		if(is_array($res = $dbRes->Fetch()))
		{
			CCrmEntityHelper::SetCached(self::CACHE_NAME, $ID, $res);
		}

		return $res;
	}
	public static function GetByOriginID($originID, $checkPerms = true)
	{
		$originID = strval($originID);
		if($originID === '')
		{
			return false;
		}

		$filter = array('ORIGIN_ID' => $originID);
		if(!$checkPerms)
		{
			$filter['CHECK_PERMISSIONS'] = 'N';
		}
		$dbRes = self::GetList(array(), $filter);
		return is_object($dbRes) ? $dbRes->Fetch() : false;
	}

	public static function GetByCalendarEventId($calendarEventId, $checkPerms = true)
	{
		$filter = array('=CALENDAR_EVENT_ID' => $calendarEventId);
		if(!$checkPerms)
		{
			$filter['CHECK_PERMISSIONS'] = 'N';
		}
		$dbRes = self::GetList(array(), $filter);
		return $dbRes->Fetch();
	}

	public static function GetIDByOrigin($originID)
	{
		$originID = strval($originID);
		if($originID === '')
		{
			return 0;
		}

		$dbRes = self::GetList(array(), array('ORIGIN_ID' => $originID, 'CHECK_PERMISSIONS'=> 'N'), false, false, array('ID'));
		$res = is_object($dbRes) ? $dbRes->Fetch() : null;
		return is_array($res) ? intval($res['ID']) : 0;
	}
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		$lb = new CCrmEntityListBuilder(
			CCrmActivity::DB_TYPE,
			CCrmActivity::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields(),
			static::UF_ENTITY_TYPE,
			'',
			array('CAllCrmActivity', 'BuildPermSql'),
			array('CAllCrmActivity', '__AfterPrepareSql')
		);

		if(!is_array($arSelectFields))
		{
			$arSelectFields = [];
		}

		if (
			in_array('IS_INCOMING_CHANNEL', $arSelectFields, true)
			&& !in_array('ID', $arSelectFields, true)
		)
		{
			$arSelectFields[] = 'ID';
		}

		$result = $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
		return (is_object($result) && is_subclass_of($result, 'CAllDBResult'))
			? new CCrmActivityDbResult($result, $arSelectFields)
			: $result;
	}
	public static function GetCount($arFilter, array $arOptions = null)
	{
		if($arOptions === null)
		{
			$arOptions = array();
		}

		$result = self::GetList(array(), $arFilter, array(), false, array(), $arOptions);
		return is_int($result) ? $result : 0;
	}
	public static function BuildPermSql($aliasPrefix = 'A', $permType = 'READ', $arOptions = array())
	{
		if(!(is_string($aliasPrefix) && $aliasPrefix !== ''))
		{
			$aliasPrefix = 'A';
		}

		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$userPermissions = isset($arOptions['PERMS']) ? $arOptions['PERMS'] : null;
		$userID =
			($userPermissions !== null && is_object($userPermissions))
				? $userPermissions->GetUserID()
				: Container::getInstance()->getContext()->getUserId()
		;
		if (CCrmPerms::IsAdmin($userID))
		{
			return '';
		}

		if(!CCrmPerms::IsAccessEnabled($userPermissions))
		{
			// User does not have permissions at all.
			return false;
		}

		$entitiesSql = array();
		$permOptions = array_merge(array('IDENTITY_COLUMN' => 'OWNER_ID'), $arOptions);
		unset($permOptions['RAW_QUERY']);

		//Ignore RESTRICT_BY_IDS. We can not apply filter by activity ID for Lead, Deal, Contact or Company
		unset($permOptions['RESTRICT_BY_IDS']);

		$entitiesSql[(string)CCrmOwnerType::Lead] = CCrmLead::BuildPermSql($aliasPrefix, $permType, $permOptions);
		$entitiesSql[(string)CCrmOwnerType::Deal] = CCrmDeal::BuildPermSql($aliasPrefix, $permType, $permOptions);
		$entitiesSql[(string)CCrmOwnerType::Contact] = CCrmContact::BuildPermSql($aliasPrefix, $permType, $permOptions);
		$entitiesSql[(string)CCrmOwnerType::Company] = CCrmCompany::BuildPermSql($aliasPrefix, $permType, $permOptions);
		$entitiesSql[(string)CCrmOwnerType::Order] =
			CCrmPerms::BuildSql(CCrmOwnerType::OrderName, $aliasPrefix, $permType, $permOptions);
		$entitiesSql[(string)CCrmOwnerType::Quote] =
			CCrmPerms::BuildSql(CCrmOwnerType::QuoteName, $aliasPrefix, $permType, $permOptions);

		$userPermissions = Container::getInstance()->getUserPermissions($userID);
		$typesMap = Container::getInstance()->getTypesMap();

		foreach ($typesMap->getFactories() as $factory)
		{
			if (array_key_exists((string)$factory->getEntityTypeId(), $entitiesSql))
			{
				continue;
			}

			if (!$userPermissions->canReadType($factory->getEntityTypeId()))
			{
				continue;
			}

			$entityTypesHelper = new Crm\Category\PermissionEntityTypeHelper($factory->getEntityTypeId());
			$entitiesSql[(string)$factory->getEntityTypeId()] = CCrmPerms::BuildSqlForEntitySet(
				$entityTypesHelper->getAllPermissionEntityTypesForEntity(),
				$aliasPrefix,
				$permType,
				$permOptions
			);
		}

		//Invoice does not have activities
		//$entitiesSql[strval(CCrmOwnerType::Invoice)] = CCrmInvoice::BuildPermSql($aliasPrefix, $permType, $permOptions);

		foreach($entitiesSql as $entityTypeID => $entitySql)
		{
			if(!is_string($entitySql))
			{
				//If $entityPermSql is not string - access denied. Clear permission SQL and related records will be ignored.
				unset($entitiesSql[$entityTypeID]);
				continue;
			}

			if($entitySql !== '')
			{
				$entitiesSql[$entityTypeID] = '('.$aliasPrefix.'.OWNER_TYPE_ID = '.$entityTypeID.' AND ('.$entitySql.') )';
			}
			else
			{
				// No permissions check - fetch all related records
				$entitiesSql[$entityTypeID] = '('.$aliasPrefix.'.OWNER_TYPE_ID = '.$entityTypeID.')';
			}
		}

		//If $entitiesSql is empty - user does not have permissions at all.
		if(empty($entitiesSql))
		{
			return false;
		}

		$userID = CCrmSecurityHelper::GetCurrentUserID();
		if($userID > 0)
		{
			//Allow responsible user to view activity without permissions check.
			$sql = $aliasPrefix.'.RESPONSIBLE_ID = '.$userID.' OR '.implode(' OR ', $entitiesSql);
		}
		else
		{
			$sql = implode(' OR ', $entitiesSql);
		}

		if(isset($arOptions['RAW_QUERY']) && $arOptions['RAW_QUERY'] === true)
		{
			$tableName = \CCrmActivity::TABLE_NAME;
			$sql = "SELECT {$aliasPrefix}.ID FROM {$tableName} {$aliasPrefix} WHERE {$sql}";
		}

		return $sql;
	}
	public static function __AfterPrepareSql(/*CCrmEntityListBuilder*/ $sender, $arOrder, $arFilter, $arGroupBy, $arSelectFields)
	{
		$sqlData = array('FROM' => array(), 'WHERE' => array());
		if(isset($arFilter['SEARCH_CONTENT']) && $arFilter['SEARCH_CONTENT'] !== '')
		{
			$tableAlias = $sender->GetTableAlias();
			$queryWhere = new CSQLWhere();
			$queryWhere->SetFields(
				array(
					'SEARCH_CONTENT' => array(
						'FIELD_NAME' => "{$tableAlias}.SEARCH_CONTENT",
						'FIELD_TYPE' => 'string',
						'JOIN' => false
					)
				)
			);
			$options = [];
			if (isset($arFilter['__ENABLE_SEARCH_CONTENT_PHONE_DETECTION']))
			{
				$options['ENABLE_PHONE_DETECTION'] = $arFilter['__ENABLE_SEARCH_CONTENT_PHONE_DETECTION'];
				unset($arFilter['__ENABLE_SEARCH_CONTENT_PHONE_DETECTION']);
			}
			$query = $queryWhere->GetQuery(
				Crm\Search\SearchEnvironment::prepareEntityFilter(
					CCrmOwnerType::Activity,
					array(
						'SEARCH_CONTENT' => Crm\Search\SearchEnvironment::prepareSearchContent($arFilter['SEARCH_CONTENT'], $options)
					)
				)
			);
			if($query !== '')
			{
				$sqlData['WHERE'][] = $query;
			}
		}

		if(isset($arFilter['BINDINGS']))
		{
			$sql = CCrmActivity::PrepareBindingsFilterSql($arFilter['BINDINGS'], $sender->GetTableAlias());
			if($sql !== '')
			{
				$sqlData['FROM'][] = $sql;
			}
		}

		$result = array();
		if(!empty($sqlData['FROM']))
		{
			$result['FROM'] = implode(' ', $sqlData['FROM']);
		}
		if(!empty($sqlData['WHERE']))
		{
			$result['WHERE'] = implode(' AND ', $sqlData['WHERE']);
		}

		return !empty($result) ? $result : false;
	}
	protected static function PrepareAssociationsSave(&$arNew, &$arOld, &$arAdd, &$arDelete)
	{
		foreach($arNew as $arNewItem)
		{
			$ID = isset($arNewItem['ID']) ? intval($arNewItem['ID']) : 0;
			if($ID <= 0)
			{
				$arAdd[] = $arNewItem;
				continue;
			}
		}

		foreach($arOld as $arOldItem)
		{
			$oldID = intval($arOldItem['ID']);
			$found = false;
			foreach($arNew as $arNewItem)
			{
				if((isset($arNewItem['ID']) ? intval($arNewItem['ID']) : 0) === $oldID)
				{
					$found = true;
					break;
				}
			}

			if(!$found)
			{
				$arDelete[] = $arOldItem;
			}
		}

	}
	public static function SaveBindings($ID, $arBindings, $registerEvents = true, $checkPerms = true, $registerBindingsChanges = true)
	{
		$result = array();
		foreach($arBindings as $arBinding)
		{
			$ownerID =  isset($arBinding['OWNER_ID']) ? (int)$arBinding['OWNER_ID'] : 0;
			$ownerTypeID =  isset($arBinding['OWNER_TYPE_ID']) ? (int)$arBinding['OWNER_TYPE_ID'] : 0;

			if($ownerID > 0 && CCrmOwnerType::IsDefined($ownerTypeID))
			{
				$key = "{$ownerTypeID}_{$ownerID}";
				if(!isset($result[$key]))
				{
					$arBinding['ACTIVITY_ID'] = $ID;
					$result[$key] = $arBinding;
				}
			}
		}

		$effectiveBindings = array_values($result);
		CCrmActivity::DoSaveBindings($ID, $effectiveBindings, $registerBindingsChanges);
		Crm\Timeline\ActivityController::synchronizeBindings($ID, $effectiveBindings);
	}
	public static function GetBindings($ID)
	{
		global $DB;

		$ID = intval($ID);
		if($ID <= 0)
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return false;
		}

		$dbRes = $DB->Query(
			'SELECT ID, OWNER_ID, OWNER_TYPE_ID FROM '.CCrmActivity::BINDING_TABLE_NAME.' WHERE ACTIVITY_ID = '.$DB->ForSql($ID)
		);

		$result = array();
		while($arRes = $dbRes->Fetch())
		{
			$result[] = $arRes;
		}
		return $result;
	}
	public static function GetSubsidiaryEntityBindingMap($ownerTypeID, $ownerID)
	{
		$map = array();
		if($ownerTypeID === CCrmOwnerType::Lead)
		{
			$entityInfos = Crm\Entity\Lead::getSubsidiaryEntities($ownerID);
			foreach($entityInfos as $entityInfo)
			{
				$map["{$entityInfo['ENTITY_TYPE_ID']}_{$entityInfo['ENTITY_ID']}"] = array(
					'OWNER_TYPE_ID' => $entityInfo['ENTITY_TYPE_ID'],
					'OWNER_ID' => $entityInfo['ENTITY_ID']
				);
			}
		}
		return $map;
	}

	public static function PrepareBindingChanges(array $origin, array $current, array &$added, array &$removed)
	{
		$originMap = array();
		foreach($origin as $binding)
		{
			$entityTypeID = isset($binding['OWNER_TYPE_ID']) ? (int)$binding['OWNER_TYPE_ID'] : 0;
			$entityID = isset($binding['OWNER_ID']) ? (int)$binding['OWNER_ID'] : 0;
			if($entityTypeID <= 0 || $entityID <= 0)
			{
				continue;
			}

			$originMap["{$entityTypeID}:{$entityID}"] = $binding;
		}

		$currentMap = array();
		foreach($current as $binding)
		{
			$entityTypeID = isset($binding['OWNER_TYPE_ID']) ? (int)$binding['OWNER_TYPE_ID'] : 0;
			$entityID = isset($binding['OWNER_ID']) ? (int)$binding['OWNER_ID'] : 0;
			if($entityTypeID <= 0 || $entityID <= 0)
			{
				continue;
			}

			$currentMap["{$entityTypeID}:{$entityID}"] = $binding;
		}

		$originKeys = array_keys($originMap);
		$currentKeys = array_keys($currentMap);

		$removed = array();
		foreach(array_diff($originKeys, $currentKeys) as $key)
		{
			$removed[] = $originMap[$key];
		}

		$added = array();
		foreach(array_diff($currentKeys, $originKeys) as $key)
		{
			$added[] = $currentMap[$key];
		}
	}

	public static function GetBoundIDs($ownerTypeID, $ownerID)
	{
		global $DB;

		$ownerTypeID = intval($ownerTypeID);
		$ownerID = intval($ownerID);

		$bindingTableName = CCrmActivity::BINDING_TABLE_NAME;

		$dbRes = $DB->Query(
			"SELECT ACTIVITY_ID FROM {$bindingTableName} WHERE OWNER_ID = {$ownerID} AND OWNER_TYPE_ID = {$ownerTypeID}"
		);

		$result = array();
		while($arRes = $dbRes->Fetch())
		{
			$result[] = intval($arRes['ACTIVITY_ID']);
		}
		return $result;
	}
	public static function IsBindingsEquals(array $first, array $second)
	{
		if(count($first) !== count($second))
		{
			return false;
		}

		foreach($first as $firstItem)
		{
			$firstOwnerTypeID = isset($firstItem['OWNER_TYPE_ID']) ? (int)$firstItem['OWNER_TYPE_ID'] : 0;
			$firstOwnerID = isset($firstItem['OWNER_ID']) ? (int)$firstItem['OWNER_ID'] : 0;
			$found = false;
			foreach($second as $secondItem)
			{
				$secondOwnerTypeID = isset($secondItem['OWNER_TYPE_ID']) ? (int)$secondItem['OWNER_TYPE_ID'] : 0;
				$secondOwnerID = isset($secondItem['OWNER_ID']) ? (int)$secondItem['OWNER_ID'] : 0;
				if($firstOwnerTypeID === $secondOwnerTypeID && $firstOwnerID === $secondOwnerID)
				{
					$found = true;
					break;
				}
			}
			if(!$found)
			{
				return false;
			}
		}
		return true;
	}
	public static function Rebind($ownerTypeID, $oldOwnerID, $newOwnerID)
	{
		global $DB;

		$ownerTypeID = intval($ownerTypeID);
		$oldOwnerID = intval($oldOwnerID);
		$newOwnerID = intval($newOwnerID);

		$tableName = CCrmActivity::TABLE_NAME;
		$bindingTableName = CCrmActivity::BINDING_TABLE_NAME;
		$communicationTableName = CCrmActivity::COMMUNICATION_TABLE_NAME;

		$items = array();
		$responsibleIDs = array();
		$sql= "SELECT A.ID, A.TYPE_ID, A.PROVIDER_ID, A.ASSOCIATED_ENTITY_ID, A.RESPONSIBLE_ID FROM {$bindingTableName} B INNER JOIN {$tableName} A ON A.ID = B.ACTIVITY_ID AND B.OWNER_TYPE_ID = {$ownerTypeID} AND B.OWNER_ID = {$oldOwnerID}";
		$dbResult = $DB->Query($sql);
		if(is_object($dbResult))
		{
			while($item = $dbResult->Fetch())
			{
				$items[] = $item;
				if(isset($item['RESPONSIBLE_ID']))
				{
					$responsibleIDs[] = (int)$item['RESPONSIBLE_ID'];
				}
			}
		}

		if(empty($items))
		{
			return;
		}

		$enableCalendarEvents = false;
		$sql =  "SELECT B.ACTIVITY_ID FROM {$bindingTableName} B INNER JOIN {$tableName} A ON A.ID = B.ACTIVITY_ID AND B.OWNER_TYPE_ID = {$ownerTypeID} AND B.OWNER_ID = {$oldOwnerID} WHERE A.CALENDAR_EVENT_ID > 0";
		CSqlUtil::PrepareSelectTop($sql, 1, CCrmActivity::DB_TYPE);
		$dbResult = $DB->Query($sql);
		if(is_object($dbResult) && is_array($dbResult->Fetch()))
		{
			$enableCalendarEvents = true;
		}

		$comm = array('ENTITY_ID'=> $newOwnerID, 'ENTITY_TYPE_ID' => $ownerTypeID);
		self::PrepareCommunicationSettings($comm);
		$entityCommSettings = isset($comm['ENTITY_SETTINGS']) ? $DB->ForSql(serialize($comm['ENTITY_SETTINGS'])) : '';

		$DB->Query(
			"UPDATE {$communicationTableName} SET ENTITY_ID = {$newOwnerID}, ENTITY_SETTINGS = '{$entityCommSettings}' WHERE ENTITY_TYPE_ID = {$ownerTypeID} AND ENTITY_ID = {$oldOwnerID}"
		);

		$DB->Query(
			"UPDATE {$communicationTableName} SET OWNER_ID = {$newOwnerID} WHERE OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID = {$oldOwnerID}"
		);

		$DB->Query(
			"UPDATE {$bindingTableName} SET OWNER_ID = {$newOwnerID} WHERE OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID = {$oldOwnerID}"
		);

		$DB->Query(
			"UPDATE {$tableName} SET OWNER_ID = {$newOwnerID} WHERE OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID = {$oldOwnerID}"
		);

		foreach($items as $item)
		{
			$associatedEntityID = isset($item['ASSOCIATED_ENTITY_ID']) ? (int)$item['ASSOCIATED_ENTITY_ID'] : 0;
			$provider = \CCrmActivity::GetActivityProvider($item);
			if ($provider && $associatedEntityID > 0)
			{
				$provider::rebindAssociatedEntity($associatedEntityID, $ownerTypeID, $ownerTypeID, $oldOwnerID, $newOwnerID);
			}

			\Bitrix\Crm\Counter\Monitor::getInstance()->onChangeActivitySingleBinding(
				(int)$item['ID'],
				[
					'OWNER_TYPE_ID' => $ownerTypeID,
					'OWNER_ID' => $oldOwnerID,
				],
				[
					'OWNER_TYPE_ID' => $ownerTypeID,
					'OWNER_ID' => $newOwnerID,
				]
			);

			Crm\Activity\Provider\ProviderManager::syncBadgesOnBindingsChange(
				(int)$item['ID'],
				[
					['OWNER_TYPE_ID' => $ownerTypeID, 'OWNER_ID' => $newOwnerID],
				],
				[
					['OWNER_TYPE_ID' => $ownerTypeID, 'OWNER_ID' => $oldOwnerID],
				],
			);
		}

		$responsibleIDs = array_unique($responsibleIDs);

		\Bitrix\Crm\Activity\UncompletedActivity::synchronizeForBindingsAndResponsibles(
			[
				['OWNER_TYPE_ID' => $ownerTypeID, 'OWNER_ID' => $oldOwnerID],
				['OWNER_TYPE_ID' => $ownerTypeID, 'OWNER_ID' => $newOwnerID],
			],
			array_merge([0], $responsibleIDs)
		);
		if(!empty($responsibleIDs))
		{
			foreach($responsibleIDs as $responsibleID)
			{
				self::SynchronizeUserActivity($ownerTypeID, $oldOwnerID, $responsibleID);
				self::SynchronizeUserActivity($ownerTypeID, $newOwnerID, $responsibleID);
			}
		}

		if($enableCalendarEvents)
		{
			self::ChangeCalendarEventOwner($ownerTypeID, $oldOwnerID, $ownerTypeID, $newOwnerID);
		}
		self::SynchronizeUserActivity($ownerTypeID, $oldOwnerID, 0);
		self::SynchronizeUserActivity($ownerTypeID, $newOwnerID, 0);
		\Bitrix\Crm\Activity\CommunicationStatistics::rebuild($ownerTypeID, array($newOwnerID));

		if (\CCrmOwnerType::IsDefined($ownerTypeID))
		{
			$monitor = Crm\Service\Timeline\Monitor::getInstance();
			if ($oldOwnerID > 0)
			{
				$monitor->onActivityRemove(
					new Crm\ItemIdentifier($ownerTypeID, $oldOwnerID)
				);
			}
			if ($newOwnerID > 0)
			{
				$monitor->onActivityAdd(
					new Crm\ItemIdentifier($ownerTypeID, $newOwnerID)
				);
			}
		}
	}

	public static function RebindElementIDs($oldID, $newID)
	{
		if(!is_int($oldID))
		{
			$oldID = (int)$oldID;
		}

		if($oldID <= 0)
		{
			throw new \Bitrix\Main\ArgumentException('Must be greater than zero.', 'oldID');
		}

		if(!is_int($newID))
		{
			$newID = (int)$newID;
		}

		if($newID <= 0)
		{
			throw new \Bitrix\Main\ArgumentException('Must be greater than zero.', 'newID');
		}

		$tableName = \CCrmActivity::ELEMENT_TABLE_NAME;
		\Bitrix\Main\Application::getInstance()->getConnection()->queryExecute("
			UPDATE {$tableName} SET ACTIVITY_ID = {$newID} WHERE ACTIVITY_ID = '{$oldID}'
		");
	}

	protected static function ChangeCalendarEventOwner($oldOwnerTypeID, $oldOwnerID, $newOwnerTypeID, $newOwnerID)
	{
		if(!(IsModuleInstalled('calendar') && CModule::IncludeModule('calendar')))
		{
			return;
		}

		$oldSlug = Crm\UserField\Types\ElementType::getValueByIdentifier(new Crm\ItemIdentifier(
			$oldOwnerTypeID,
			$oldOwnerID
		));
		$events = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					'=UF_CRM_CAL_EVENT' => $oldSlug,
					'=DELETED' => 'N'
				),
				'arSelect' => array('ID'),
				'getUserfields' => true,
				'checkPermissions' => false
			)
		);

		if(!is_array($events))
		{
			return;
		}

		$newSlug = Crm\UserField\Types\ElementType::getValueByIdentifier(new Crm\ItemIdentifier(
			$newOwnerTypeID,
			$newOwnerID
		));
		foreach($events as $event)
		{
			if(!(isset($event['UF_CRM_CAL_EVENT']) && is_array($event['UF_CRM_CAL_EVENT'])))
			{
				continue;
			}

			for($i = 0, $length = count($event['UF_CRM_CAL_EVENT']); $i < $length; $i++)
			{
				if($event['UF_CRM_CAL_EVENT'][$i] !== $oldSlug)
				{
					continue;
				}

				$event['UF_CRM_CAL_EVENT'][$i] = $newSlug;
				CCalendarEvent::UpdateUserFields(
					$event['ID'],
					array('UF_CRM_CAL_EVENT' => $event['UF_CRM_CAL_EVENT'])
				);
				break;
			}
		}
	}

	public static function ChangeOwner($oldOwnerTypeID, $oldOwnerID, $newOwnerTypeID, $newOwnerID)
	{
		global $DB;

		$oldOwnerTypeID = intval($oldOwnerTypeID);
		$oldOwnerID = intval($oldOwnerID);

		$newOwnerTypeID = intval($newOwnerTypeID);
		$newOwnerID = intval($newOwnerID);

		$tableName = CCrmActivity::TABLE_NAME;
		$bindingTableName = CCrmActivity::BINDING_TABLE_NAME;
		$communicationTableName = CCrmActivity::COMMUNICATION_TABLE_NAME;

		$sql= "SELECT ID FROM ".CCrmActivity::BINDING_TABLE_NAME." WHERE OWNER_TYPE_ID = {$oldOwnerTypeID} AND OWNER_ID = {$oldOwnerID}";
		CSqlUtil::PrepareSelectTop($sql, 1, CCrmActivity::DB_TYPE);
		$dbResult = $DB->Query($sql);
		if(!(is_object($dbResult) && is_array($dbResult->Fetch())))
		{
			return;
		}

		$responsibleIDs = [];
		$activityIDs = [];
		$sql =  "SELECT B.ACTIVITY_ID, A.RESPONSIBLE_ID FROM {$bindingTableName} B INNER JOIN {$tableName} A ON A.ID = B.ACTIVITY_ID AND B.OWNER_TYPE_ID = {$oldOwnerTypeID} AND B.OWNER_ID = {$oldOwnerID}";
		$dbResult = $DB->Query($sql);
		if(is_object($dbResult))
		{
			while($fields = $dbResult->Fetch())
			{
				$singleResponsibleId = (int)$fields['RESPONSIBLE_ID'];
				if (!isset($responsibleIDs[$singleResponsibleId]))
				{
					$responsibleIDs[$singleResponsibleId] = $singleResponsibleId;
				}

				$singleActivityId = (int)$fields['ACTIVITY_ID'];
				if (!isset($activityIDs[$singleActivityId]))
				{
					$activityIDs[$singleActivityId] = $singleActivityId;
				}
			}
		}

		$comm = array('ENTITY_ID'=> $newOwnerID, 'ENTITY_TYPE_ID' => $newOwnerTypeID);
		self::PrepareCommunicationSettings($comm);
		$entityCommSettings = isset($comm['ENTITY_SETTINGS']) ? $DB->ForSql(serialize($comm['ENTITY_SETTINGS'])) : '';

		$DB->Query(
			"UPDATE {$communicationTableName} SET ENTITY_TYPE_ID = {$newOwnerTypeID}, ENTITY_ID = {$newOwnerID}, ENTITY_SETTINGS = '{$entityCommSettings}' WHERE ENTITY_TYPE_ID = {$oldOwnerTypeID} AND ENTITY_ID = {$oldOwnerID}"
		);

		$DB->Query(
			"UPDATE {$communicationTableName} SET OWNER_TYPE_ID = {$newOwnerTypeID}, OWNER_ID = {$newOwnerID} WHERE OWNER_TYPE_ID = {$oldOwnerTypeID} AND OWNER_ID = {$oldOwnerID}"
		);

		$DB->Query(
			"UPDATE {$bindingTableName} SET OWNER_TYPE_ID = {$newOwnerTypeID}, OWNER_ID = {$newOwnerID} WHERE OWNER_TYPE_ID = {$oldOwnerTypeID} AND OWNER_ID = {$oldOwnerID}"
		);

		$DB->Query(
			"UPDATE {$tableName} SET OWNER_TYPE_ID = {$newOwnerTypeID}, OWNER_ID = {$newOwnerID} WHERE OWNER_TYPE_ID = {$oldOwnerTypeID} AND OWNER_ID = {$oldOwnerID}"
		);

		if(!empty($responsibleIDs))
		{
			foreach($responsibleIDs as $responsibleID)
			{
				self::SynchronizeUserActivity($oldOwnerTypeID, $oldOwnerID, $responsibleID);
				self::SynchronizeUserActivity($newOwnerTypeID, $newOwnerID, $responsibleID);
			}
		}
		self::SynchronizeUserActivity($oldOwnerTypeID, $oldOwnerID, 0);
		self::SynchronizeUserActivity($newOwnerTypeID, $newOwnerID, 0);
		\Bitrix\Crm\Activity\CommunicationStatistics::rebuild($newOwnerTypeID, array($newOwnerID));

		foreach ($activityIDs as $singleActivityId)
		{
			Crm\Activity\Provider\ProviderManager::syncBadgesOnBindingsChange(
				$singleActivityId,
				[
					['OWNER_TYPE_ID' => $newOwnerTypeID, 'OWNER_ID' => $newOwnerID],
				],
				[
					['OWNER_TYPE_ID' => $oldOwnerTypeID, 'OWNER_ID' => $oldOwnerID],
				],
			);
		}

		$monitor = Crm\Service\Timeline\Monitor::getInstance();
		if (\CCrmOwnerType::IsDefined($oldOwnerTypeID) && $oldOwnerID > 0)
		{
			$monitor->onActivityRemove(new Crm\ItemIdentifier($oldOwnerTypeID, $oldOwnerID));
		}
		if (\CCrmOwnerType::IsDefined($newOwnerTypeID) && $newOwnerID > 0)
		{
			$monitor->onActivityAdd(new Crm\ItemIdentifier($newOwnerTypeID, $newOwnerID));
		}
	}

	public static function AttachBinding($srcOwnerTypeID, $srcOwnerID, $targOwnerTypeID, $targOwnerID)
	{
		$dbResult = \Bitrix\Main\Application::getConnection()->query(
			"SELECT a.ID, a.RESPONSIBLE_ID
				FROM b_crm_act a INNER JOIN b_crm_act_bind b ON a.ID = b.ACTIVITY_ID
				WHERE b.OWNER_TYPE_ID = {$srcOwnerTypeID} AND b.OWNER_ID = {$srcOwnerID}"
		);

		$itemFields = array();
		while($fields = $dbResult->fetch())
		{
			$itemFields[] = $fields;
		}

		$processed = 0;
		$responsibleMap = array();
		foreach($itemFields as $fields)
		{
			$itemID = (int)$fields['ID'];
			if($itemID <= 0)
			{
				continue;
			}

			$isBound = false;
			$bindings = self::GetBindings($itemID);
			$oldBindings = $bindings;
			foreach($bindings as $binding)
			{
				if($binding['OWNER_TYPE_ID'] == $targOwnerTypeID && $binding['OWNER_ID'] == $targOwnerID)
				{
					$isBound = true;
					break;
				}
			}

			if($isBound)
			{
				continue;
			}

			$bindings[] = array('OWNER_TYPE_ID' => $targOwnerTypeID, 'OWNER_ID' => $targOwnerID);
			self::SaveBindings($itemID, $bindings, false, false, false);
			$processed++;

			$responsibleID = isset($fields['RESPONSIBLE_ID']) ? (int)$fields['RESPONSIBLE_ID'] : 0;
			if($responsibleID > 0)
			{
				$responsibleMap[$responsibleID] = true;
			}

			\Bitrix\Crm\Counter\Monitor::getInstance()->onChangeActivityBindings(
				$itemID,
				$oldBindings,
				$bindings
			);
		}

		if($processed === 0)
		{
			return;
		}

		$responsibleIDs = array_keys($responsibleMap);
		\Bitrix\Crm\Activity\UncompletedActivity::synchronizeForBindingsAndResponsibles(
			[
				['OWNER_TYPE_ID' => $targOwnerTypeID, 'OWNER_ID' => $targOwnerID]
			],
			array_merge($responsibleIDs, [0])
		);

		if(!empty($responsibleIDs))
		{
			foreach($responsibleIDs as $responsibleID)
			{
				self::SynchronizeUserActivity($targOwnerTypeID, $targOwnerID, $responsibleID);
			}
		}
		self::SynchronizeUserActivity($targOwnerTypeID, $targOwnerID, 0);
		\Bitrix\Crm\Activity\CommunicationStatistics::rebuild($targOwnerTypeID, array($targOwnerID));
	}

	public static function DetachBinding($srcOwnerTypeID, $srcOwnerID, $targOwnerTypeID, $targOwnerID)
	{
		$dbResult = \Bitrix\Main\Application::getConnection()->query(
			"SELECT a.ID, a.RESPONSIBLE_ID
				FROM b_crm_act a INNER JOIN b_crm_act_bind b ON a.ID = b.ACTIVITY_ID
				WHERE b.OWNER_TYPE_ID = {$srcOwnerTypeID} AND b.OWNER_ID = {$srcOwnerID}"
		);

		$itemFields = array();
		while($fields = $dbResult->fetch())
		{
			$itemFields[] = $fields;
		}

		$processed = 0;
		$responsibleMap = array();
		foreach($itemFields as $fields)
		{
			$itemID = (int)$fields['ID'];
			if($itemID <= 0)
			{
				continue;
			}

			$bindingIndex = -1;
			$bindings = self::GetBindings($itemID);
			$oldBindings = $bindings;
			for($i = 0, $length = count($bindings); $i < $length; $i++)
			{
				$binding = $bindings[$i];
				if($binding['OWNER_TYPE_ID'] == $targOwnerTypeID && $binding['OWNER_ID'] == $targOwnerID)
				{
					$bindingIndex = $i;
					break;
				}
			}

			if($bindingIndex < 0)
			{
				continue;
			}

			array_splice($bindings, $bindingIndex, 1);
			self::SaveBindings($itemID, $bindings, false, false, false);
			$processed++;

			$responsibleID = isset($fields['RESPONSIBLE_ID']) ? (int)$fields['RESPONSIBLE_ID'] : 0;
			if($responsibleID > 0)
			{
				$responsibleMap[$responsibleID] = true;
			}

			\Bitrix\Crm\Counter\Monitor::getInstance()->onChangeActivityBindings(
				$itemID,
				$oldBindings,
				$bindings
			);
		}

		if($processed === 0)
		{
			return;
		}

		$responsibleIDs = array_keys($responsibleMap);
		\Bitrix\Crm\Activity\UncompletedActivity::synchronizeForBindingsAndResponsibles(
			[
				['OWNER_TYPE_ID' => $targOwnerTypeID, 'OWNER_ID' => $targOwnerID]
			],
			array_merge($responsibleIDs, [0])
		);

		if(!empty($responsibleIDs))
		{
			foreach($responsibleIDs as $responsibleID)
			{
				self::SynchronizeUserActivity($targOwnerTypeID, $targOwnerID, $responsibleID);
			}
		}
		self::SynchronizeUserActivity($targOwnerTypeID, $targOwnerID, 0);
		\Bitrix\Crm\Activity\CommunicationStatistics::rebuild($targOwnerTypeID, array($targOwnerID));
	}

	private static function PrepareCommunicationSettings(&$arComm, $arFields = null)
	{
		$commEntityID = isset($arComm['ENTITY_ID']) ? intval($arComm['ENTITY_ID']) : 0;
		$commEntityTypeID = isset($arComm['ENTITY_TYPE_ID']) ? intval($arComm['ENTITY_TYPE_ID']) : 0;

		if($commEntityID > 0 && $commEntityTypeID > 0)
		{
			if($commEntityTypeID === CCrmOwnerType::Lead)
			{
				$arLead = is_array($arFields) ? $arFields : CCrmLead::GetByID($commEntityID, false);
				if(!is_array($arLead))
				{
					$arComm['ENTITY_SETTINGS'] = array();
					return false;
				}

				$arComm['ENTITY_SETTINGS'] =
					array(
						'HONORIFIC' => isset($arLead['HONORIFIC']) ? $arLead['HONORIFIC'] : '',
						'NAME' => isset($arLead['NAME']) ? $arLead['NAME'] : '',
						'SECOND_NAME' => isset($arLead['SECOND_NAME']) ? $arLead['SECOND_NAME'] : '',
						'LAST_NAME' => isset($arLead['LAST_NAME']) ? $arLead['LAST_NAME'] : '',
						'LEAD_TITLE' => isset($arLead['TITLE']) ? $arLead['TITLE'] : ''
					);
				return true;
			}
			elseif($commEntityTypeID === CCrmOwnerType::Deal)
			{
				$arLead = is_array($arFields) ? $arFields : CCrmDeal::GetByID($commEntityID, false);
				if(!is_array($arLead))
				{
					$arComm['ENTITY_SETTINGS'] = [];
					return false;
				}

				$arComm['ENTITY_SETTINGS'] = [
					'DEAL_TITLE' => isset($arLead['TITLE']) ? $arLead['TITLE'] : ''
				];
				return true;
			}
			elseif($commEntityTypeID === CCrmOwnerType::Contact)
			{
				$arContact = is_array($arFields) ? $arFields : CCrmContact::GetByID($commEntityID, false);
				if(!is_array($arContact))
				{
					$arComm['ENTITY_SETTINGS'] = array();
					return false;
				}

				$arComm['ENTITY_SETTINGS'] = array(
					'HONORIFIC' => isset($arContact['HONORIFIC']) ? $arContact['HONORIFIC'] : '',
					'NAME' => isset($arContact['NAME']) ? $arContact['NAME'] : '',
					'SECOND_NAME' => isset($arContact['SECOND_NAME']) ? $arContact['SECOND_NAME'] : '',
					'LAST_NAME' => isset($arContact['LAST_NAME']) ? $arContact['LAST_NAME'] : ''
				);

				$arCompany = isset($arContact['COMPANY_ID']) ? CCrmCompany::GetByID($arContact['COMPANY_ID'], false) : null;
				if($arCompany && isset($arCompany['TITLE']))
				{
					$arComm['ENTITY_SETTINGS']['COMPANY_TITLE'] = $arCompany['TITLE'];
					$arComm['ENTITY_SETTINGS']['COMPANY_ID'] = $arCompany['ID'];
				}
				return true;
			}
			elseif($commEntityTypeID === CCrmOwnerType::Company)
			{
				$arCompany = is_array($arFields) ? $arFields : CCrmCompany::GetByID($commEntityID, false);
				if(!is_array($arCompany))
				{
					$arComm['ENTITY_SETTINGS'] = array();
					return false;
				}
				$arComm['ENTITY_SETTINGS'] = array('COMPANY_TITLE' => isset($arCompany['TITLE']) ? $arCompany['TITLE'] : '');
				return true;
			}
		}

		$arComm['ENTITY_SETTINGS'] = array();
		return false;
	}
	public static function SaveCommunications($ID, $arComms, $arFields = array(), $registerEvents = true, $checkPerms = true)
	{
		if(empty($arFields))
		{
			$arFields = self::GetByID($ID, false);
		}

		$ownerID = isset($arFields['OWNER_ID']) ? $arFields['OWNER_ID'] : 0;
		$ownerTypeID = isset($arFields['OWNER_TYPE_ID']) ? $arFields['OWNER_TYPE_ID'] : 0;
		foreach($arComms as &$arComm)
		{
			if(!isset($arComm['ENTITY_SETTINGS']))
			{
				self::PrepareCommunicationSettings($arComm);
			}
			$arComm['ENTITY_SETTINGS'] = serialize($arComm['ENTITY_SETTINGS']);
			$arComm['ACTIVITY_ID'] = $ID;
			$arComm['OWNER_ID'] = $ownerID;
			$arComm['OWNER_TYPE_ID'] = $ownerTypeID;
		}
		unset($arComm);

		CCrmActivity::DoSaveCommunications($ID, $arComms, $arFields, $registerEvents, $checkPerms);
	}

	public static function GetCommunications($activityID, $top = 0, array $options = null)
	{
		$activityID = intval($activityID);
		if($activityID <= 0)
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return false;
		}

		global $DB;
		$commTableName = CCrmActivity::COMMUNICATION_TABLE_NAME;
		$sql = "SELECT ID, TYPE, VALUE, ENTITY_ID, ENTITY_TYPE_ID, ENTITY_SETTINGS FROM {$commTableName} WHERE ACTIVITY_ID = {$activityID} ORDER BY ID ASC";
		$top = intval($top);
		if($top > 0)
		{
			CSqlUtil::PrepareSelectTop($sql, $top, CCrmActivity::DB_TYPE);
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$enableSettings = !isset($options['ENABLE_SETTINGS']) || $options['ENABLE_SETTINGS'];

		$dbRes = $DB->Query($sql);
		$result = array();
		while($arRes = $dbRes->Fetch())
		{
			if($enableSettings)
			{
				$arRes['ENTITY_SETTINGS'] = isset($arRes['ENTITY_SETTINGS']) && $arRes['ENTITY_SETTINGS'] !== '' ? unserialize($arRes['ENTITY_SETTINGS'], ['allowed_classes' => false]) : array();
			}
			else
			{
				unset($arRes['ENTITY_SETTINGS']);
			}
			$result[] = $arRes;
		}
		return $result;
	}

	public static function PrepareCommunicationInfos(array $activityIDs, array $options = array())
	{
		$activityIDs = array_filter($activityIDs);
		if(empty($activityIDs))
		{
			return array();
		}

		$enableSettings = isset($options['ENABLE_PERMISSION_CHECK']) && $options['ENABLE_PERMISSION_CHECK'];
		$userPermissions = isset($options['USER_PERMISSIONS']) ? $options['USER_PERMISSIONS'] : CCrmPerms::GetCurrentUserPermissions();

		$nameTemplate = \Bitrix\Crm\Format\PersonNameFormatter::getFormat();
		$condition = implode(',', $activityIDs);
		$dbResult = \Bitrix\Main\Application::getConnection()->query(
			"SELECT c1.* FROM b_crm_act_comm c1
				INNER JOIN (SELECT MIN(ID) ID FROM b_crm_act_comm WHERE ACTIVITY_ID IN ({$condition}) GROUP BY ACTIVITY_ID) c2
					ON c1.ID = c2.ID"
		);

		$results = array();
		while($comm = $dbResult->fetch())
		{
			$ID = (int)$comm['ACTIVITY_ID'];
			$entityID = isset($comm['ENTITY_ID']) ? (int)$comm['ENTITY_ID'] : 0;
			$entityTypeID = isset($comm['ENTITY_TYPE_ID']) ? (int)$comm['ENTITY_TYPE_ID'] : 0;

			if($entityID <= 0 || $entityTypeID <= 0)
			{
				$entityID = isset($comm['OWNER_ID']) ? (int)$comm['OWNER_ID'] : 0;
				$entityTypeID = isset($comm['OWNER_TYPE_ID']) ? (int)$comm['OWNER_TYPE_ID'] : 0;
			}

			if($enableSettings
				&& !Bitrix\Crm\Security\EntityAuthorization::checkReadPermission($entityTypeID, $entityID, $userPermissions)
			)
			{
				$results[$ID] = array(
					'ENTITY_ID' => $entityID,
					'ENTITY_TYPE_ID' => $entityTypeID,
					'TYPE' => isset($comm['TYPE']) ? $comm['TYPE'] : '',
					'IS_HIDDEN' => true,
					'TITLE' => GetMessage('CRM_ACTIVITY_HIDDEN_CLIENT')
				);
				continue;
			}

			if(isset($comm['ENTITY_SETTINGS']))
			{
				$settings = unserialize($comm['ENTITY_SETTINGS'], ['allowed_classes' => false]);
			}
			else
			{
				//Settings is missing. We are going to try underway refueling.
				self::PrepareCommunicationSettings($comm);
				$settings = $comm['ENTITY_SETTINGS'];
			}

			if($comm['TYPE'] === Crm\CommunicationType::PHONE_NAME && $comm['VALUE'])
				$formattedValue = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($comm['VALUE'])->format();
			else
				$formattedValue = $comm['VALUE'];

			$info = array(
				'ENTITY_ID' => $entityID,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'TYPE' => isset($comm['TYPE']) ? $comm['TYPE'] : '',
				'VALUE' => isset($comm['VALUE']) ? $comm['VALUE'] : '',
				'FORMATTED_VALUE' => $formattedValue,
				'TITLE' => ''
			);

			$info['SHOW_NAME'] = (
				($settings['NAME'] ?? '') !== ''
				|| ($settings['LAST_NAME'] ?? '') !== ''
				|| ($settings['COMPANY_TITLE'] ?? '') !== ''
			);

			if($entityTypeID === CCrmOwnerType::Lead)
			{
				if(isset($settings['NAME']) || isset($settings['LAST_NAME']))
				{
					$info['TITLE'] = CCrmLead::PrepareFormattedName(
						array(
							'HONORIFIC' => isset($settings['HONORIFIC']) ? $settings['HONORIFIC'] : '',
							'NAME' => isset($settings['NAME']) ? $settings['NAME'] : '',
							'LAST_NAME' => isset($settings['LAST_NAME']) ? $settings['LAST_NAME'] : '',
							'SECOND_NAME' => isset($settings['SECOND_NAME']) ? $settings['SECOND_NAME'] : ''
						),
						$nameTemplate
					);
				}
				else
				{
					$info['TITLE'] = isset($settings['LEAD_TITLE']) ? $settings['LEAD_TITLE'] : '';
				}
			}
			elseif($entityTypeID === CCrmOwnerType::Company)
			{
				$info['TITLE'] = isset($settings['COMPANY_TITLE']) ? $settings['COMPANY_TITLE'] : '';
			}
			elseif($entityTypeID === CCrmOwnerType::Contact)
			{
				$info['TITLE'] = CCrmContact::PrepareFormattedName(
					array(
						'HONORIFIC' => isset($settings['HONORIFIC']) ? $settings['HONORIFIC'] : '',
						'NAME' => isset($settings['NAME']) ? $settings['NAME'] : '',
						'LAST_NAME' => isset($settings['LAST_NAME']) ? $settings['LAST_NAME'] : '',
						'SECOND_NAME' => isset($settings['SECOND_NAME']) ? $settings['SECOND_NAME'] : ''
					),
					$nameTemplate
				);
			}

			$info['SHOW_URL'] = CCrmOwnerType::GetEntityShowPath($entityTypeID, $entityID, false);
			$results[$ID] = $info;
		}
		return $results;
	}

	public static function ResetEntityCommunicationSettings($entityTypeID, $entityID)
	{
		CCrmActivity::DoResetEntityCommunicationSettings($entityTypeID, $entityID);
	}
	protected static function SaveEntityCommunicationSettings($entityTypeID, $entityID, array $settings)
	{
		$settings = serialize($settings);
		CCrmActivity::DoSaveEntityCommunicationSettings($entityTypeID, $entityID, $settings);
	}
	public static function PrepareClientInfos($IDs, $arOptions = null)
	{
		$nameTemplate = is_array($arOptions) && isset($arOptions['NAME_TEMPLATE'])
			&& is_string($arOptions['NAME_TEMPLATE']) && $arOptions['NAME_TEMPLATE'] !== ''
			? $arOptions['NAME_TEMPLATE'] : \Bitrix\Crm\Format\PersonNameFormatter::getFormat();

		$result = array();
		if(!is_array(self::$CLIENT_INFOS) || empty(self::$CLIENT_INFOS))
		{
			$selectIDs = $IDs;
		}
		else
		{
			$selectIDs = array();
			foreach($IDs as $ID)
			{
				if(!isset(self::$CLIENT_INFOS[$ID]))
				{
					$selectIDs[] = $ID;
				}
				else
				{
					$info = self::$CLIENT_INFOS[$ID];
					if(isset($info['NAME_DATA']) && $nameTemplate !== $info['NAME_DATA']['NAME_TEMPLATE'])
					{
						$info['NAME_DATA']['NAME_TEMPLATE'] = $nameTemplate;
						$ownerTypeID = isset($info['ENTITY_TYPE_ID']) ? $info['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
						if($ownerTypeID === CCrmOwnerType::Lead)
						{
							$info['TITLE'] = CCrmLead::PrepareFormattedName(
								array(
									'HONORIFIC' => isset($info['NAME_DATA']['HONORIFIC']) ? $info['NAME_DATA']['HONORIFIC'] : '',
									'NAME' => isset($info['NAME_DATA']['NAME']) ? $info['NAME_DATA']['NAME'] : '',
									'LAST_NAME' => isset($info['NAME_DATA']['LAST_NAME']) ? $info['NAME_DATA']['LAST_NAME'] : '',
									'SECOND_NAME' => isset($info['NAME_DATA']['SECOND_NAME']) ? $info['NAME_DATA']['SECOND_NAME'] : ''
								),
								$nameTemplate
							);
						}
						else//if($ownerTypeID === CCrmOwnerType::Contact)
						{
							$info['TITLE'] = CCrmContact::PrepareFormattedName(
								array(
									'HONORIFIC' => isset($info['NAME_DATA']['HONORIFIC']) ? $info['NAME_DATA']['HONORIFIC'] : '',
									'NAME' => isset($info['NAME_DATA']['NAME']) ? $info['NAME_DATA']['NAME'] : '',
									'LAST_NAME' => isset($info['NAME_DATA']['LAST_NAME']) ? $info['NAME_DATA']['LAST_NAME'] : '',
									'SECOND_NAME' => isset($info['NAME_DATA']['SECOND_NAME']) ? $info['NAME_DATA']['SECOND_NAME'] : ''
								),
								$nameTemplate
							);
						}
					}
					$result[$ID] = $info;
				}
			}
		}

		if(!empty($selectIDs))
		{
			global $DB;
			$condition = implode(',', $selectIDs);
			$dbResult = $DB->Query("SELECT A.ID ACTIVITY_ID, A.OWNER_TYPE_ID, A.OWNER_ID, C3.ENTITY_ID, C3.ENTITY_TYPE_ID, C3.ENTITY_SETTINGS
				FROM b_crm_act A LEFT OUTER JOIN(
					SELECT C2.ID, C2.ACTIVITY_ID, C2.ENTITY_ID, C2.ENTITY_TYPE_ID, C2.ENTITY_SETTINGS
						FROM (SELECT ACTIVITY_ID, MIN(ID) ID FROM b_crm_act_comm WHERE ACTIVITY_ID IN({$condition}) GROUP BY ACTIVITY_ID) C1
							INNER JOIN b_crm_act_comm C2 ON C1.ID = C2.ID) C3 ON C3.ACTIVITY_ID = A.ID
				WHERE A.ID IN({$condition})");

			if(is_object($dbResult))
			{
				if(self::$CLIENT_INFOS === null)
				{
					self::$CLIENT_INFOS = array();
				}

				while($comm = $dbResult->Fetch())
				{
					$ID = intval($comm['ACTIVITY_ID']);
					$entityID = isset($comm['ENTITY_ID']) ? intval($comm['ENTITY_ID']) : 0;
					$entityTypeID = isset($comm['ENTITY_TYPE_ID']) ? intval($comm['ENTITY_TYPE_ID']) : 0;

					$isExists = ($entityID > 0 && $entityTypeID > 0);
					if(!$isExists)
					{
						$entityID = isset($comm['OWNER_ID']) ? intval($comm['OWNER_ID']) : 0;
						$entityTypeID = isset($comm['OWNER_TYPE_ID']) ? intval($comm['OWNER_TYPE_ID']) : 0;
					}

					if($entityID <= 0 || $entityTypeID <= 0 || $entityTypeID === CCrmOwnerType::Deal)
					{
						continue;
					}

					$info = array(
						'ENTITY_ID' => $entityID,
						'ENTITY_TYPE_ID' => $entityTypeID,
						'TITLE' => '',
						'SHOW_URL' => CCrmOwnerType::GetEntityShowPath($entityTypeID, $entityID, false)
					);

					$settings = isset($comm['ENTITY_SETTINGS']) ? unserialize($comm['ENTITY_SETTINGS'], ['allowed_classes' => false]) : array();
					if(empty($settings))
					{
						$customComm = array('ENTITY_ID' => $entityID, 'ENTITY_TYPE_ID' => $entityTypeID);
						self::PrepareCommunicationSettings($customComm);
						if(isset($customComm['ENTITY_SETTINGS']))
						{
							$settings = $customComm['ENTITY_SETTINGS'];
							if($isExists)
							{
								self::SaveEntityCommunicationSettings($entityTypeID, $entityID, $settings);
							}
							else
							{
								self::SaveCommunications(
									$ID,
									array(
										array(
											'ENTITY_ID' => $entityID,
											'ENTITY_TYPE_ID' => $entityTypeID,
											'ENTITY_SETTINGS' => $settings
										)
									)
								);
							}
						}
					}

					if($entityTypeID === CCrmOwnerType::Lead)
					{
						$info['TITLE'] = isset($settings['LEAD_TITLE']) ? $settings['LEAD_TITLE'] : '';
					}
					elseif($entityTypeID === CCrmOwnerType::Company)
					{
						$info['TITLE'] = isset($settings['COMPANY_TITLE']) ? $settings['COMPANY_TITLE'] : '';
					}
					elseif($entityTypeID === CCrmOwnerType::Contact)
					{
						$info['TITLE'] = CCrmContact::PrepareFormattedName(
							array(
								'HONORIFIC' => isset($settings['HONORIFIC']) ? $settings['HONORIFIC'] : '',
								'NAME' => isset($settings['NAME']) ? $settings['NAME'] : '',
								'LAST_NAME' => isset($settings['LAST_NAME']) ? $settings['LAST_NAME'] : '',
								'SECOND_NAME' => isset($settings['SECOND_NAME']) ? $settings['SECOND_NAME'] : ''
							),
							$nameTemplate
						);

						$info['NAME_DATA'] = array(
							'NAME_TEMPLATE' => $nameTemplate,
							'HONORIFIC' => isset($settings['HONORIFIC']) ? $settings['HONORIFIC'] : '',
							'NAME' => isset($settings['NAME']) ? $settings['NAME'] : '',
							'LAST_NAME' => isset($settings['LAST_NAME']) ? $settings['LAST_NAME'] : '',
							'SECOND_NAME' => isset($settings['SECOND_NAME']) ? $settings['SECOND_NAME'] : ''
						);
					}

					$result[$ID] = self::$CLIENT_INFOS[$ID] = $info;
				}
			}
		}
		return $result;
	}

	protected static function GetCommunicationFields()
	{
		if(!isset(self::$COMMUNICATION_FIELDS))
		{
			self::$COMMUNICATION_FIELDS = array(
				'ID' => array('FIELD' => 'AC.ID', 'TYPE' => 'int'),
				'ACTIVITY_ID' => array('FIELD' => 'AC.ACTIVITY_ID', 'TYPE' => 'int'),
				'OWNER_ID' => array('FIELD' => 'AC.OWNER_ID', 'TYPE' => 'int'),
				'OWNER_TYPE_ID' => array('FIELD' => 'AC.OWNER_TYPE_ID', 'TYPE' => 'int'),
				'TYPE' => array('FIELD' => 'AC.TYPE', 'TYPE' => 'string'),
				'VALUE' => array('FIELD' => 'AC.VALUE', 'TYPE' => 'string'),
				'ENTITY_ID' => array('FIELD' => 'AC.ENTITY_ID', 'TYPE' => 'int'),
				'ENTITY_TYPE_ID' => array('FIELD' => 'AC.ENTITY_TYPE_ID', 'TYPE' => 'int'),
				'ENTITY_SETTINGS' => array('FIELD' => 'AC.ENTITY_SETTINGS', 'TYPE' => 'string'),
			);
		}

		return self::$COMMUNICATION_FIELDS;
	}

	public static function GetCommunicationList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		$lb = new CCrmEntityListBuilder(
			CCrmActivity::DB_TYPE,
			CCrmActivity::COMMUNICATION_TABLE_NAME,
			self::COMMUNICATION_TABLE_ALIAS,
			self::GetCommunicationFields(),
			'',
			'',
			array(),
			array()
		);

		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}

	public static function PrepareCommunications($entityType, $entityID, $communicationType)
	{
		$entityType = mb_strtoupper(strval($entityType));
		$entityID = intval($entityID);
		$communicationType = mb_strtoupper($communicationType);
		if($communicationType === '')
		{
			$communicationType = 'PHONE';
		}

		$dbResFields = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => $entityType, 'ELEMENT_ID' => $entityID, 'TYPE_ID' =>  $communicationType)
		);

		$result = array();
		while($arField = $dbResFields->Fetch())
		{
			if(empty($arField['VALUE']))
			{
				continue;
			}

			$result[] = array(
				'ENTITY_ID' => $entityID,
				'ENTITY_TYPE' => $entityType,
				'TYPE' => $communicationType,
				'VALUE' => $arField['VALUE'],
				'VALUE_TYPE' => $arField['VALUE_TYPE']
			);
		}

		return $result;
	}
	public static function GetCommunicationTitle($arComm, $enabledEmptyNameStub = true): string
	{
		self::PrepareCommunicationInfo($arComm, null, $enabledEmptyNameStub);
		return isset($arComm['TITLE']) ? $arComm['TITLE'] : '';
	}
	public static function PrepareCommunicationInfo(&$arComm, $arFields = null, $enabledEmptyNameStub = true)
	{
		if(!isset($arComm['ENTITY_SETTINGS']))
		{
			if(!self::PrepareCommunicationSettings($arComm, $arFields))
			{
				$arComm['TITLE'] = '';
				$arComm['DESCRIPTION'] = '';
				return false;
			}
		}

		$title = '';
		$description = '';
		$entityTypeID = isset($arComm['ENTITY_TYPE_ID']) ? intval($arComm['ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
		if($entityTypeID === CCrmOwnerType::Lead)
		{
			$honorific = '';
			$name = '';
			$secondName = '';
			$lastName = '';
			$leadTitle = '';

			if(is_array(($arComm['ENTITY_SETTINGS'])))
			{
				$settings = $arComm['ENTITY_SETTINGS'];

				$honorific = isset($settings['HONORIFIC']) ? $settings['HONORIFIC'] : '';
				$name = isset($settings['NAME']) ? $settings['NAME'] : '';
				$secondName = isset($settings['SECOND_NAME']) ? $settings['SECOND_NAME'] : '';
				$lastName = isset($settings['LAST_NAME']) ? $settings['LAST_NAME'] : '';
				$leadTitle = isset($settings['LEAD_TITLE']) ? $settings['LEAD_TITLE'] : '';
			}
			else
			{
				$arEntity = CCrmLead::GetByID($arComm['ENTITY_ID']);
				if($arEntity)
				{
					$honorific = isset($arEntity['HONORIFIC']) ? $arEntity['HONORIFIC'] : '';
					$name = isset($arEntity['NAME']) ? $arEntity['NAME'] : '';
					$secondName = isset($arEntity['SECOND_NAME']) ? $arEntity['SECOND_NAME'] : '';
					$lastName = isset($arEntity['LAST_NAME']) ? $arEntity['LAST_NAME'] : '';
					$leadTitle = isset($arEntity['TITLE']) ? $arEntity['TITLE'] : '';
				}
			}

			if($name === '' && $secondName === '' && $lastName === '')
			{
				$title = $leadTitle;
			}
			else
			{
				$title = CCrmLead::PrepareFormattedName(
					array(
						'HONORIFIC' => $honorific,
						'NAME' => $name,
						'SECOND_NAME' => $secondName,
						'LAST_NAME' => $lastName
					)
				);
				$description = $leadTitle;
			}
		}
		if($entityTypeID === CCrmOwnerType::Deal)
		{
			$dealTitle = '';

			if(is_array(($arComm['ENTITY_SETTINGS'])))
			{
				$settings = $arComm['ENTITY_SETTINGS'];
				$dealTitle = isset($settings['DEAL_TITLE']) ? $settings['DEAL_TITLE'] : '';
			}
			else
			{
				$arEntity = CCrmDeal::GetByID($arComm['ENTITY_ID']);
				if($arEntity)
				{
					$dealTitle = isset($arEntity['TITLE']) ? $arEntity['TITLE'] : '';
				}
			}
			$title = $dealTitle;
		}
		elseif($entityTypeID === CCrmOwnerType::Contact)
		{
			// Empty TYPE is person to person communiation, empty ENTITY_ID is unbound communication - no method to build title
			if (!(empty($arComm['TYPE']) && intval($arComm['ENTITY_ID']) === 0))
			{
				$honorific = '';
				$name = '';
				$secondName = '';
				$lastName = '';
				$companyTitle = '';

				if(is_array($arComm['ENTITY_SETTINGS']) && !empty($arComm['ENTITY_SETTINGS']))
				{
					$settings = $arComm['ENTITY_SETTINGS'];

					$honorific = isset($settings['HONORIFIC']) ? $settings['HONORIFIC'] : '';
					$name = isset($settings['NAME']) ? $settings['NAME'] : '';
					$secondName = isset($settings['SECOND_NAME']) ? $settings['SECOND_NAME'] : '';
					$lastName = isset($settings['LAST_NAME']) ? $settings['LAST_NAME'] : '';
					$companyTitle = isset($settings['COMPANY_TITLE']) ? $settings['COMPANY_TITLE'] : '';
				}
				else
				{
					$arEntity = CCrmContact::GetByID($arComm['ENTITY_ID']);
					if($arEntity)
					{
						$honorific = isset($arEntity['HONORIFIC']) ? $arEntity['HONORIFIC'] : '';
						$name = isset($arEntity['NAME']) ? $arEntity['NAME'] : '';
						$secondName = isset($arEntity['SECOND_NAME']) ? $arEntity['SECOND_NAME'] : '';
						$lastName = isset($arEntity['LAST_NAME']) ? $arEntity['LAST_NAME'] : '';
						$companyTitle = isset($arEntity['COMPANY_TITLE']) ? $arEntity['COMPANY_TITLE'] : '';
					}
				}

				$title = CCrmContact::PrepareFormattedName(
					array(
						'HONORIFIC' => $honorific,
						'NAME' => $name,
						'SECOND_NAME' => $secondName,
						'LAST_NAME' => $lastName
					),
					'',
					$enabledEmptyNameStub
				);

				$description = $companyTitle;
			}
		}
		elseif($entityTypeID === CCrmOwnerType::Company)
		{
			if(is_array($arComm['ENTITY_SETTINGS']) && !empty($arComm['ENTITY_SETTINGS']))
			{
				$settings = $arComm['ENTITY_SETTINGS'];
				$title = isset($settings['COMPANY_TITLE']) ? $settings['COMPANY_TITLE'] : '';
			}
			else
			{
				$arEntity = CCrmCompany::GetByID($arComm['ENTITY_ID']);
				if($arEntity)
				{
					$title = isset($arEntity['TITLE']) ? $arEntity['TITLE'] : '';
				}
			}
		}

		$arComm['TITLE'] = $title;
		$arComm['DESCRIPTION'] = $description;
		return true;
	}
	public static function PrepareStorageElementInfo(&$arFields)
	{
		$storageTypeID = isset($arFields['STORAGE_TYPE_ID']) ? (int)$arFields['STORAGE_TYPE_ID'] : StorageType::Undefined;
		if(!StorageType::IsDefined($storageTypeID))
		{
			$storageTypeID = self::GetDefaultStorageTypeID();
		}

		$storageElementIDs = isset($arFields['STORAGE_ELEMENT_IDS']) && is_array($arFields['STORAGE_ELEMENT_IDS'])
			? $arFields['STORAGE_ELEMENT_IDS'] : array();

		if($storageTypeID === StorageType::File)
		{
			$arFields['FILES'] = array();
			foreach($storageElementIDs as $fileID)
			{
				$arData = CFile::GetFileArray($fileID);
				if(is_array($arData))
				{
					$arFields['FILES'][] = array(
						'fileID' => $arData['ID'],
						'fileName' => $arData['ORIGINAL_NAME'] ?: $arData['FILE_NAME'],
						'fileURL' =>  CCrmUrlUtil::UrnEncode($arData['SRC']),
						'fileSize' => $arData['FILE_SIZE']
					);
				}
			}
		}
		elseif($storageTypeID === StorageType::WebDav)
		{
			$infos = array();
			foreach($storageElementIDs as $elementID)
			{
				$infos[] = \CCrmWebDavHelper::GetElementInfo($elementID, false);
			}
			$arFields['WEBDAV_ELEMENTS'] = &$infos;
			unset($infos);
		}
		elseif($storageTypeID === StorageType::Disk)
		{
			$infos = [];
			foreach($storageElementIDs as $elementID)
			{
				$diskFileInfo = Bitrix\Crm\Integration\DiskManager::getFileInfo(
					$elementID,
					false,
					['OWNER_TYPE_ID' => CCrmOwnerType::Activity, 'OWNER_ID' => $arFields['ID'] ?? null]
				);

				if ($diskFileInfo)
				{
					$infos[] = $diskFileInfo;
				}
			}

			$arFields['DISK_FILES'] = &$infos;
			unset($infos);
		}
	}

	public static function SaveRecentlyUsedCommunication($arComm, $userID = 0)
	{
		$userID = intval($userID);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$communicationType = isset($arComm['TYPE']) ? $arComm['TYPE'] : '';
		$entityTypeID = isset($arComm['ENTITY_TYPE_ID']) ? intval($arComm['ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			$entityTypeName = isset($arComm['ENTITY_TYPE']) ? $arComm['ENTITY_TYPE'] : '';
			if($entityTypeName !== '')
			{
				$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
			}
		}

		$entityID = isset($arComm['ENTITY_ID']) ? intval($arComm['ENTITY_ID']) : 0;
		$value = isset($arComm['VALUE']) ? $arComm['VALUE'] : '';

		if(!CCrmOwnerType::IsDefined($entityTypeID) || $entityID <= 0)
		{
			return false;
		}

		$optionName = $communicationType !== '' ? 'lru_'.mb_strtolower($communicationType) : 'lru_person';

		$ary = CUserOptions::GetOption('crm_activity', $optionName, array(), $userID);
		$qty = count($ary);
		if($qty > 0)
		{
			for($i = 0; $i < $qty; $i++)
			{
				$item = $ary[$i];
				if($item['VALUE'] === $value
					&& $item['ENTITY_ID'] === $entityID
					&& $item['ENTITY_TYPE_ID'] === $entityTypeID)
				{
					// already exists
					return true;
				}
			}

			if($qty >= 20)
			{
				array_shift($ary);
			}
		}

		$entitySettings = isset($arComm['ENTITY_SETTINGS'])
			? $arComm['ENTITY_SETTINGS'] : null;
		if(!is_array($entitySettings))
		{
			self::PrepareCommunicationSettings($arComm);
			$entitySettings = $arComm['ENTITY_SETTINGS'];
		}

		$ary[] = array(
			'TYPE' => $communicationType,
			'VALUE' => $value,
			'ENTITY_ID' => $entityID,
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_SETTINGS' => $entitySettings
		);

		CUserOptions::SetOption('crm_activity', $optionName, $ary);
		return true;
	}
	public static function GetRecentlyUsedCommunications($communicationType, $userID = 0)
	{
		$communicationType = strval($communicationType);
		$userID = intval($userID);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$optionName = $communicationType !== '' ? 'lru_'.mb_strtolower($communicationType) : 'lru_person';
		return CUserOptions::GetOption('crm_activity', $optionName, array(), $userID);
	}
	public static function PrepareStorageElementIDs(&$arFields)
	{
		if(isset($arFields['STORAGE_ELEMENT_IDS']) && is_array($arFields['STORAGE_ELEMENT_IDS']))
		{
			return;
		}

		if(isset($arFields['~STORAGE_ELEMENT_IDS']))
		{
			$field = $arFields['~STORAGE_ELEMENT_IDS'];
		}
		elseif(isset($arFields['STORAGE_ELEMENT_IDS']))
		{
			$field = $arFields['STORAGE_ELEMENT_IDS'];
		}
		else
		{
			$field = '';
		}

		if(is_array($field))
		{
			$result = $field;
		}
		elseif(is_numeric($field))
		{
			$ID = (int)$field;
			if($ID <= 0)
			{
				$ID = isset($arFields['ID']) ? (int)$arFields['ID'] : (isset($arFields['~ID']) ? (int)$arFields['~ID'] : 0);
			}

			if($ID <= 0)
			{
				$result = array();
			}
			else
			{
				$result = self::LoadElementIDs($ID);
				$arUpdateFields = array('STORAGE_ELEMENT_IDS' => serialize($result));
				$table = CCrmActivity::TABLE_NAME;
				global $DB;
				$DB->QueryBind(
					'UPDATE '.$table.' SET '.$DB->PrepareUpdate($table, $arUpdateFields).' WHERE ID = '.$ID,
					$arUpdateFields,
					false
				);
			}
		}
		elseif(is_string($field) && $field !== '')
		{
			$result = unserialize($field, ['allowed_classes' => false]);
		}
		else
		{
			$result = array();
		}

		$arFields['~STORAGE_ELEMENT_IDS'] = $arFields['STORAGE_ELEMENT_IDS'] = &$result;
		unset($result);
	}
	public static function Exists($ID, $checkPerms = true)
	{
		$filter = array('ID'=> $ID);
		if(!$checkPerms)
		{
			$filter['CHECK_PERMISSIONS'] = 'N';
		}

		$dbRes = CCrmActivity::GetList(array(), $filter, false, false, array('ID'));
		return is_array($dbRes->Fetch());
	}

	public static function Complete($ID, $completed = true, $options = [])
	{
		$ID = (int)($ID ?? 0);
		if ($ID <= 0)
		{
			self::RegisterError(['text' => 'Invalid arguments are supplied.']);

			return false;
		}

		if (is_string($completed))
		{
			$completed = mb_strtoupper($completed) === 'Y' ? 'Y' : 'N';
		}
		else
		{
			$completed = ((bool)$completed) ? 'Y' : 'N';
		}

		$dbRes = CCrmActivity::GetList(
			[],
			['ID'=> $ID, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID', 'COMPLETED', 'PROVIDER_ID', 'PROVIDER_PARAMS']
		);
		$fields = $dbRes->Fetch();
		if (!is_array($fields))
		{
			return false;
		}

		if (isset($fields['COMPLETED']) && $fields['COMPLETED'] === $completed)
		{
			return true;
		}

		$skipBeforeHandler = $options['SKIP_BEFORE_HANDLER'] ?? false;
		if (!$skipBeforeHandler)
		{
			$provider = self::GetActivityProvider($fields);
			if ($provider !== null)
			{
				$provider::onBeforeComplete($ID, $fields, ['CURRENT_USER' => $options['CURRENT_USER'] ?? \CCrmSecurityHelper::GetCurrentUserID()]);
			}
		}

		return self::Update($ID, ['COMPLETED' => $completed], true, true, $options);
	}

	public static function SetAutoCompleted($ID, $options = array())
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return false;
		}

		return self::Update($ID, array('COMPLETED' => 'Y', 'STATUS' => CCrmActivityStatus::AutoCompleted), true, true, $options);
	}

	public static function SetAutoCompletedByOwner($ownerTypeId, $ownerId, array $providerIds = null, array $options = null)
	{
		$ownerTypeId = (int)$ownerTypeId;
		$ownerId = (int)$ownerId;
		if ($ownerId <= 0 || $ownerTypeId <= 0)
		{
			return;
		}

		$filter = [
			'=COMPLETED' => 'N',
			'=BINDINGS.OWNER_TYPE_ID' => $ownerTypeId,
			'=BINDINGS.OWNER_ID' => $ownerId,
		];
		if (is_array($providerIds) && !empty($providerIds))
		{
			$filter['@PROVIDER_ID']	= $providerIds;
		}

		$fields = ActivityTable::getList([
			'select' => ['ID'],
			'filter' => $filter,
		])->fetchAll();
		$activityIds = array_column($fields, 'ID');
		if (empty($activityIds))
		{
			return;
		}

		// additional filter
		if (ActivitySettings::getValue(ActivitySettings::CHECK_BINDINGS_BEFORE_AUTO_COMPLETE))
		{
			$bindings = Crm\ActivityBindingTable::getList([
				'select' => ['ACTIVITY_ID', 'OWNER_TYPE_ID', 'OWNER_ID'],
				'filter' => ['@ACTIVITY_ID' => $activityIds],
			])->fetchAll();
			$notClosedBindings = array_filter(
				$bindings,
				static fn($item) => !ComparerBase::isClosed(new Bitrix\Crm\ItemIdentifier($item['OWNER_TYPE_ID'], $item['OWNER_ID']))
			);
			$filteredActivityIds = array_unique(array_column($notClosedBindings, 'ACTIVITY_ID'));
			$activityIds = array_diff($activityIds, $filteredActivityIds);
		}

		foreach ($activityIds as $activityId)
		{
			self::SetAutoCompleted($activityId, $options);
		}
	}

	public static function SetPriority($ID, $priority, $options = array())
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return false;
		}

		$priority = intval($priority);
		return self::Update($ID, array('PRIORITY' => $priority), true, true, $options);
	}

	public static function PostponeToDate(
		array $activityFields,
		\Bitrix\Main\Type\DateTime $desiredDeadline,
		bool $useNegativeOffset = false
	): void
	{
		$currentDeadline = DateTime::createFromUserTime($activityFields['DEADLINE']);
		$offset = $desiredDeadline->getTimestamp() - $currentDeadline->getTimestamp();

		$params = [
			'FIELDS' => $activityFields,
			'USE_NEGATIVE_OFFSET' => $useNegativeOffset,
		];

		self::Postpone($activityFields['ID'], $offset, $params);
	}

	public static function Postpone($ID, $offset, $params = null)
	{
		$ID = (int)$ID;
		if($ID <= 0)
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return array();
		}

		$offset = (int)$offset;
		if($offset <= 0 && !$params['USE_NEGATIVE_OFFSET'])
		{
			return array();
		}

		if(!is_array($params))
		{
			$params = array();
		}

		$fields = isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : array();
		if(!(isset($fields['PROVIDER_ID'])
			&& isset($fields['TYPE_ID'])
			&& isset($fields['START_TIME'])
			&& isset($fields['END_TIME']))
		)
		{
			$dbResult = self::GetList(
				array(),
				array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'START_TIME', 'END_TIME', 'PROVIDER_ID', 'TYPE_ID', 'ASSOCIATED_ENTITY_ID')
			);

			$fields = $dbResult->Fetch();
			if(!is_array($fields))
			{
				self::RegisterError(array('text' => 'Activity is not found.'));
				return array();
			}
		}

		$provider = self::GetActivityProvider($fields);
		if($provider === null)
		{
			self::RegisterError(array('text' => 'Could not find provider.'));
			return array();
		}

		$updateFields = array();
		if(!$provider::tryPostpone($offset, $fields, $updateFields))
		{
			self::RegisterError(array('text' => 'Postpone denied by provider.'));
			return array();
		}

		if(!empty($updateFields))
		{
			if (!empty($fields['CALENDAR_EVENT_ID']))
			{
				$updateFields['CALENDAR_EVENT_ID'] = $fields['CALENDAR_EVENT_ID'];
			}
			self::Update($ID, $updateFields);
		}

		$dbResult = self::GetList(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'START_TIME', 'END_TIME', 'DEADLINE')
		);

		$updateFields = $dbResult->Fetch();
		return $updateFields;
	}

	public static function GetLastErrorMessage()
	{
		return ($c = count(self::$errors)) > 0 ? self::$errors[$c - 1] : '';
	}
	public static function GetErrorMessages()
	{
		return self::$errors;
	}
	public static function GetErrorCount()
	{
		return count(self::$errors);
	}
	public static function TryResolveUserFieldOwners(&$arUsefFieldData, &$arOwnerData, $arField = null)
	{
		$parsed = 0;

		$defaultTypeName = '';
		if(is_array($arField)
			&& isset($arField['USER_TYPE_ID']) && $arField['USER_TYPE_ID'] === 'crm'
			&& isset($arField['SETTINGS']) && is_array($arField['SETTINGS']))
		{
			foreach($arField['SETTINGS'] as $k => $v)
			{
				if($v !== 'Y')
				{
					continue;
				}

				if($defaultTypeName === '')
				{
					$defaultTypeName = $k;
					continue;
				}

				// There is more than one type enabled
				$defaultTypeName = '';
				break;
			}
		}

		foreach($arUsefFieldData as $value)
		{
			$value = strval($value);
			if($value === '')
			{
				continue;
			}

			$ownerTypeName = '';
			$ownerID = 0;
			$parseResult = \CCrmOwnerType::ParseEntitySlug(mb_strtoupper(trim($value)));
			if(is_array($parseResult))
			{
				if ($parseResult['ENTITY_TYPE_ID'] === \CCrmOwnerType::Order && !\CCrmSaleHelper::isWithOrdersMode())
				{
					continue;
				}

				$ownerTypeName = \CCrmOwnerType::ResolveName($parseResult['ENTITY_TYPE_ID']);
				$ownerID = $parseResult['ENTITY_ID'];
			}
			elseif($defaultTypeName !== '')
			{
				$ownerTypeName = $defaultTypeName;
				$ownerID = intval($value);
			}

			if($ownerTypeName === '' || $ownerID <= 0)
			{
				continue;
			}

			$arOwnerData[] = array(
				'OWNER_TYPE_NAME' => $ownerTypeName,
				'OWNER_ID' => $ownerID
			);

			$parsed++;
		}
		return $parsed > 0;
	}
	public static function CreateFromCalendarEvent(
		$eventID,
		&$arEventFields,
		$checkPerms = true,
		$regEvent = true)
	{
		$eventID = (int)$eventID;
		if ($eventID <= 0 && isset($arEventFields['ID']))
		{
			$eventID = (int)$arEventFields['ID'];
		}

		if ($eventID <= 0)
		{
			return false;
		}

		$entityCount = self::GetList(array(), array('=CALENDAR_EVENT_ID' => $eventID), array(), false, false);

		if ($entityCount > 0)
		{
			return false;
		}

		$arFields = array();
		self::SetFromCalendarEvent($eventID, $arEventFields, $arFields);
		if(isset($arFields['BINDINGS']) && count($arFields['BINDINGS']) > 0)
		{
			return self::Add($arFields, $checkPerms, $regEvent);
		}
	}
	// Event handlers -->
	/**
	 * @deprecated
	 * @param $taskID
	 * @param $arTaskFields
	 * @param bool $checkPerms
	 * @param bool $regEvent
	 * @return bool|int
	 */
	public static function CreateFromTask($taskID, &$arTaskFields, $checkPerms = true, $regEvent = true)
	{
		return \Bitrix\Crm\Activity\Provider\Task::createFromTask($taskID, $arTaskFields, $checkPerms, $regEvent);
	}
	public static function CreateFromDealEvent(&$arDeal)
	{
		$dealID = intval($arDeal['ID']);
		$originID = "DEAL_EVENT_{$dealID}";
		if(self::GetByOriginID($originID) !== false)
		{
			return false; //Already exists
		}

		$now = time() + CTimeZone::GetOffset();
		$typeID = $arDeal['EVENT_ID'] === 'PHONE' ? CCrmActivityType::Call : CCrmActivityType::Activity;
		$subject = GetMessage($typeID === CCrmActivityType::Call ? 'CRM_ACTIVITY_FROM_DEAL_EVENT_CALL' : 'CRM_ACTIVITY_FROM_DEAL_EVENT_INFO');

		$date = $now;
		if(isset($arDeal['EVENT_DATE']))
		{
			$date = MakeTimeStamp($arDeal['EVENT_DATE']);
		}
		elseif(isset($arDeal['DATE_MODIFY']))
		{
			$date = MakeTimeStamp($arDeal['DATE_MODIFY']);
		}
		elseif(isset($arDeal['DATE_CREATE']))
		{
			$date = MakeTimeStamp($arDeal['DATE_CREATE']);
		}

		$dateFmt = ConvertTimeStamp($date, 'FULL', SITE_ID);

		$responsibleID = 0;
		if(isset($arDeal['ASSIGNED_BY_ID']))
		{
			$responsibleID = intval($arDeal['ASSIGNED_BY_ID']);
		}
		elseif(isset($arDeal['MODIFY_BY_ID']))
		{
			$responsibleID = intval($arDeal['MODIFY_BY_ID']);
		}
		elseif(isset($arDeal['CREATED_BY_ID']))
		{
			$responsibleID = intval($arDeal['CREATED_BY_ID']);
		}

		$arFields = array(
			'TYPE_ID' => $typeID,
			'OWNER_TYPE_ID' => CCrmOwnerType::Deal,
			'OWNER_ID' => $dealID,
			'SUBJECT' => $subject,
			'START_TIME' => $dateFmt,
			'END_TIME' => $dateFmt,
			'COMPLETED' => ($date <= $now) ? 'Y' : 'N',
			'RESPONSIBLE_ID' => $responsibleID,
			'PRIORITY' => CCrmActivityPriority::Medium,
			'DESCRIPTION' => isset($arDeal['EVENT_DESCRIPTION']) ? $arDeal['EVENT_DESCRIPTION'] : '',
			'LOCATION' => '',
			'DIRECTION' => $typeID === CCrmActivityType::Call ? CCrmActivityDirection::Outgoing : CCrmActivityDirection::Undefined,
			'NOTIFY_TYPE' => CCrmActivityNotifyType::None,
			'BINDINGS' => array(
				array(
					'OWNER_TYPE_ID' => CCrmOwnerType::Deal,
					'OWNER_ID' => $arDeal['ID']
				)
			),
			'ORIGIN_ID' => $originID,
			'SETTINGS' => array()
		);

		return self::Add($arFields, false, false);
	}
	// <-- Contract
	private static function SetFromCalendarEvent($eventID, &$arEventFields, &$arFields)
	{
		$isNew = !(isset($arFields['ID']) && (int)$arFields['ID'] > 0);
		$isCall = (isset($arFields['TYPE_ID']) && (int)$arFields['TYPE_ID'] === CCrmActivityType::Call);

		if ($arEventFields['EVENT_TYPE'] === '#shared_crm#')
		{
			return;
		}

		if ($arEventFields['EVENT_TYPE'] !== '#resourcebooking#')
		{
			$arFields['CALENDAR_EVENT_ID'] = $eventID;

			$arEventOwners = array();
			if(isset($arEventFields['UF_CRM_CAL_EVENT']))
			{
				$arEventOwners = $arEventFields['UF_CRM_CAL_EVENT'];
			}
			else
			{
				//Try to load if not found CRM bindings
				$arReloadedEventFields = \Bitrix\Crm\Integration\Calendar::getEvent($eventID);
				if(isset($arReloadedEventFields['UF_CRM_CAL_EVENT']))
				{
					$arEventOwners = $arReloadedEventFields['UF_CRM_CAL_EVENT'];
				}
			}

			if(!is_array($arEventOwners))
			{
				$arEventOwners = array($arEventOwners);
			}

			$arOwnerData = array();
			self::TryResolveUserFieldOwners($arEventOwners, $arOwnerData, CCrmUserType::GetCalendarEventBindingField());
			if(!$isNew)
			{
				self::EnrichWithNotRemovableBindings($arFields['ID'], $arOwnerData);
			}
			if(!empty($arOwnerData))
			{
				$arFields['OWNER_TYPE_ID'] = CCrmOwnerType::ResolveID($arOwnerData[0]['OWNER_TYPE_NAME']);
				$arFields['OWNER_ID'] = $arOwnerData[0]['OWNER_ID'];
				$arFields['COMMUNICATIONS'] = array();

				foreach($arOwnerData as &$arOwnerInfo)
				{
					$ownerTypeId = CCrmOwnerType::ResolveID($arOwnerInfo['OWNER_TYPE_NAME']);
					$arFields['BINDINGS'][] = array(
						'OWNER_TYPE_ID' => $ownerTypeId,
						'OWNER_ID' => $arOwnerInfo['OWNER_ID']
					);

					if (CCrmOwnerType::IsClient($ownerTypeId))
					{
						$arFields['COMMUNICATIONS'][] = array(
							'TYPE' => $isCall ? CCrmFieldMulti::PHONE : '',
							'VALUE' => '',
							'ENTITY_ID' => $arOwnerInfo['OWNER_ID'],
							'ENTITY_TYPE_ID' => $ownerTypeId
						);
					}
				}
				unset($arOwnerInfo);

				if ($isCall && count($arFields['COMMUNICATIONS']) > 1)
				{
					$arFields['COMMUNICATIONS'] = array_slice($arFields['COMMUNICATIONS'], 0, 1);
				}
			}
			else
			{
				$arFields['OWNER_TYPE_ID'] = 0;
				$arFields['OWNER_ID'] = 0;
				$arFields['BINDINGS'] = array();
			}

			//TODO: [tag: MEETING_MULTIPLE] remove next line to apply Meeting multiple communications
			unset($arFields['COMMUNICATIONS']);

			if($isNew)
			{
				$arFields['TYPE_ID'] = CCrmActivityType::Provider;
				$arFields['PROVIDER_ID'] = ToDo::PROVIDER_ID;

				//Not completed for new activities. Do not change existed activities.
				$arFields['COMPLETED'] = 'N';
			}

			if($isNew || isset($arEventFields['NAME']))
			{
				$arFields['SUBJECT'] = isset($arEventFields['NAME']) ? $arEventFields['NAME'] : '';
			}

			$fromTs = CCalendar::Timestamp($arEventFields['DATE_FROM'], false);
			$toTs = CCalendar::Timestamp($arEventFields['DATE_TO'], false);

			if ($arEventFields['DT_SKIP_TIME'] !== "Y")
			{
				$fromTs -= $arEventFields['~USER_OFFSET_FROM'];
				$toTs -= $arEventFields['~USER_OFFSET_TO'];
			}

			$userID = isset($arEventFields['OWNER_ID']) ? (int)$arEventFields['OWNER_ID'] : 0;
			if($userID <= 0 && isset($arEventFields['CREATED_BY']))
			{
				$userID = (int)$arEventFields['CREATED_BY'];
			}

			global $USER;
			if ($userID > 0 && !$USER?->getId())
			{
				//Try to use event owner timezone strictly. In case of work under agent.
				$arFields['TIME_ZONE_OFFSET'] = CTimeZone::GetOffset($userID, true);
			}

			$arFields['START_TIME'] = CCalendar::Date($fromTs);
			$arFields['END_TIME'] = CCalendar::Date($toTs);

			if($isNew || isset($arEventFields['CREATED_BY']))
			{
				$arFields['RESPONSIBLE_ID'] = isset($arEventFields['CREATED_BY']) ? intval($arEventFields['CREATED_BY']) : 0;
			}

			if($isNew || isset($arEventFields['IMPORTANCE']))
			{
				$arFields['PRIORITY'] = CCrmActivityPriority::FromCalendarEventImportance(isset($arEventFields['IMPORTANCE']) ? $arEventFields['IMPORTANCE'] : '');
			}

			if($isNew || isset($arEventFields['DESCRIPTION']))
			{
				$arFields['DESCRIPTION'] = isset($arEventFields['DESCRIPTION']) ? $arEventFields['DESCRIPTION'] : '';
				$arFields['DESCRIPTION'] = CTextParser::clearAllTags($arFields['DESCRIPTION']);
				$arFields['DESCRIPTION_TYPE'] = CCrmContentType::PlainText;
			}

			if($isNew || isset($arEventFields['LOCATION']))
			{
				$arFields['LOCATION'] = isset($arEventFields['LOCATION']) ? $arEventFields['LOCATION'] : '';
			}

			if($isNew || isset($arEventFields['REMIND']))
			{
				$remindData = isset($arEventFields['REMIND']) ? $arEventFields['REMIND'] : array();
				if(is_string($remindData))
				{
					if($remindData !== '')
					{
						$remindData = unserialize($remindData, ['allowed_classes' => false]);
					}

					if(!is_array($remindData))
					{
						$remindData = array();
					}
				}

				if(empty($remindData))
				{
					$arFields['NOTIFY_TYPE'] = CCrmActivityNotifyType::None;
				}
				else
				{
					$remindInfo = $remindData[0];
					$remindType = CCrmActivityNotifyType::FromCalendarEventRemind(isset($remindInfo['type']) ? $remindInfo['type'] : '');
					$remindValue = isset($remindInfo['count']) ? intval($remindInfo['count']) : 0;
					if($remindType !== CCrmActivityNotifyType::None && $remindValue > 0)
					{
						$arFields['NOTIFY_TYPE'] = $remindType;
						$arFields['NOTIFY_VALUE'] = $remindValue;
					}
				}
			}

			if (isset($arEventFields['SECTION_ID'], $arFields['SETTINGS']['CALENDAR_SECTION_ID']))
			{
				$arFields['SETTINGS']['CALENDAR_SECTION_ID'] = (int) $arEventFields['SECTION_ID'];
			}
		}
		else
		{
			$arEventOwners = $arOwnerData = [];
			if(isset($arEventFields['UF_CRM_CAL_EVENT']))
			{
				$arEventOwners = (array) $arEventFields['UF_CRM_CAL_EVENT'];
			}
			self::TryResolveUserFieldOwners(
				$arEventOwners, $arOwnerData,
				CCrmUserType::GetCalendarEventBindingField()
			);
			if(!$isNew)
			{
				self::EnrichWithNotRemovableBindings($arFields['ID'], $arOwnerData);
			}

			if (!empty($arOwnerData))
			{
				$bindings = array_map(function ($v) {
					$v['OWNER_TYPE_ID'] = CCrmOwnerType::ResolveID($v['OWNER_TYPE_NAME']);
					return $v;
				}, $arOwnerData);

				ResourceBookingTrigger::execute($bindings, ['event' => $arEventFields]);
			}
		}
	}
	// Event handlers -->
	public static function OnTaskAdd($taskID, &$arTaskFields)
	{
	}

	/**
	 * @param array $arTaskFields Task fields.
	 */
	public static function OnBeforeTaskAdd(&$arTaskFields)
	{
		\Bitrix\Crm\Activity\Provider\Task::onBeforeTaskAdd($arTaskFields);
	}

	/**
	 * @param int $taskID Task id.
	 * @param array $arTaskFields Task fields.
	 */
	public static function OnTaskUpdate($taskID, &$arCurrentTaskFields, &$arPreviousTaskFields)
	{
		\Bitrix\Crm\Activity\Provider\Tasks\Task::onTriggered($taskID, $arCurrentTaskFields, $arPreviousTaskFields);
		\Bitrix\Crm\Activity\Provider\Task::onTaskUpdate($taskID, $arCurrentTaskFields, $arPreviousTaskFields);
	}

	/**
	 * @deprecated
	 * @param int $taskID Task id.
	 */
	public static function OnTaskDelete($taskID)
	{
		\Bitrix\Crm\Activity\Provider\Task::onTaskDelete($taskID);
	}

	public static function OnCalendarEventEdit($arFields, $bNew, $userId)
	{
		if (self::$IGNORE_CALENDAR_EVENTS)
		{
			return;
		}

		$eventID = isset($arFields['ID']) ? (int)$arFields['ID'] : 0;
		if ($eventID > 0)
		{
			$events = CCalendarEvent::GetList(
				[
					'arFilter' => [
						"ID" => $eventID,
						"DELETED" => "N"
					],
					'parseRecursion' => false,
					'fetchAttendees' => false,
					'checkPermissions' => false,
					'setDefaultLimit' => false,
					'userId' => $userId
				]
			);
			$arEventFields = ($events && is_array($events[0])) ? $events[0] : null;

			$dbEntities = self::GetList(
				[],
				[
					'=CALENDAR_EVENT_ID' => $eventID,
					'CHECK_PERMISSIONS' => 'N'
				]
			);
			$arEntity = $dbEntities->Fetch();

			if (is_array($arEntity))
			{
				if ($arEventFields && $arEventFields['EVENT_TYPE'] === '#shared_crm#')
				{
					return;
				}

				self::SetFromCalendarEvent($eventID, $arEventFields, $arEntity);
				// Update activity if bindings are found overwise delete unbound activity
				if (isset($arEntity['BINDINGS']) && !empty($arEntity['BINDINGS']))
				{
					self::Update(
						$arEntity['ID'],
						$arEntity,
						false,
						true,
						[
							'SKIP_CALENDAR_EVENT' => true,
							'REGISTER_SONET_EVENT' => true,
							'CURRENT_USER' => $userId > 0 ? $userId : null,
							'INITIATED_BY_CALENDAR' => true,
						]
					);
				}
				else
				{
					self::Delete($arEntity['ID'], false, true, array('SKIP_CALENDAR_EVENT' => true));
				}
			}
			else
			{
				$arFields = [];
				self::SetFromCalendarEvent($eventID, $arEventFields, $arFields);
				if (isset($arFields['BINDINGS']) && !empty($arFields['BINDINGS']))
				{
					self::Add(
						$arFields,
						false,
						true, [
							'SKIP_CALENDAR_EVENT' => true,
							'REGISTER_SONET_EVENT' => true,
							'INITIATED_BY_CALENDAR' => true,
						]
					);
				}
			}
		}
	}
	public static function OnCalendarEventDelete($eventID, $arEventFields)
	{
		if(self::$IGNORE_CALENDAR_EVENTS)
		{
			return;
		}

		$dbEntities = self::GetList(
			[],
			['=CALENDAR_EVENT_ID' => $eventID]
		);
		while($arEntity = $dbEntities->Fetch())
		{
			if ($arEntity['PROVIDER_ID'] === "CRM_CALENDAR_SHARING")
			{
				continue;
			}

			self::Delete($arEntity['ID'], false, true, array('SKIP_CALENDAR_EVENT' => true));
		}
	}
	// <-- Event handlers
	public static function DeleteByOwner($ownerTypeID, $ownerID, array $delOptions = [])
	{
		$ownerID = (int)$ownerID;
		$ownerTypeID = (int)$ownerTypeID;
		if($ownerID <= 0 || $ownerTypeID <= 0)
		{
			return;
		}

		$connection = \Bitrix\Main\Application::getConnection();

		Crm\Activity\UncompletedActivity::unregister(new \Bitrix\Crm\ItemIdentifier($ownerTypeID, $ownerID));

		//region Delete unbound items
		$deleteMap = array();
		$dbResult = $connection->query(/** @lang MySQL */
			"SELECT ACTIVITY_ID FROM b_crm_act_bind
				WHERE OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID = {$ownerID}"
		);
		while($fields = $dbResult->fetch())
		{
			$deleteMap[$fields['ACTIVITY_ID']] = true;
		}

		$connection->queryExecute(/** @lang MySQL */
			"DELETE FROM b_crm_act_bind
				WHERE OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID = {$ownerID}"
		);

		$sliceSize = 200;
		$itemIDs = array_keys($deleteMap);
		$existedActivityIds = [];
		while(!empty($itemIDs))
		{
			$conditionSql = implode(',', array_splice($itemIDs, 0, $sliceSize));
			if($conditionSql === '')
			{
				break;
			}

			$dbResult = $connection->query(/** @lang MySQL */
				"SELECT ACTIVITY_ID FROM b_crm_act_bind
					WHERE ACTIVITY_ID IN ({$conditionSql})"
			);
			while($fields = $dbResult->fetch())
			{
				unset($deleteMap[$fields['ACTIVITY_ID']]);
				$existedActivityIds[] = (int)$fields['ACTIVITY_ID'];
			}
		}

		$itemIDs = array_keys($deleteMap);
		$delOptions = array_merge($delOptions, [
			'SKIP_BINDINGS' => true,
			'SKIP_USER_ACTIVITY_SYNC' => true,
			'SKIP_STATISTICS' => true
		]);

		if(ActivitySettings::getValue(ActivitySettings::KEEP_UNBOUND_TASKS))
		{
			$delOptions['SKIP_TASKS'] = true;
		}
		foreach($itemIDs as $itemID)
		{
			self::Delete($itemID, false, false, $delOptions);
		}
		//endregion
		//region Clear Nearest Activities and Statistics
		$connection->queryExecute(/** @lang MySQL */
			"DELETE FROM b_crm_usr_act
				WHERE OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID = {$ownerID}"
		);

		\Bitrix\Crm\Activity\CommunicationStatistics::unregisterByOwner($ownerTypeID, $ownerID);
		//endregion
		//region Update items ownership if required
		$updateMap = array();
		$dbResult = $connection->query(/** @lang MySQL */
			"SELECT ID FROM b_crm_act
				WHERE OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID = {$ownerID}"
		);

		while($fields = $dbResult->fetch())
		{
			$updateMap[$fields['ID']] = true;
		}

		$itemIDs = array_keys($updateMap);
		while(!empty($itemIDs))
		{
			$conditionSql = implode(',', array_splice($itemIDs, 0, $sliceSize));
			if($conditionSql === '')
			{
				break;
			}

			$connection->queryExecute($connection->getSqlHelper()->prepareCorrelatedUpdate(
				'b_crm_act',
				'a1',
				[
					'OWNER_ID' => 'a2.OWNER_ID',
					'OWNER_TYPE_ID' => 'a2.OWNER_TYPE_ID',
				],
				"(SELECT ACTIVITY_ID, OWNER_ID, OWNER_TYPE_ID FROM b_crm_act_bind b1
					INNER JOIN (SELECT MIN(ID) ID FROM b_crm_act_bind
						WHERE ACTIVITY_ID IN ({$conditionSql}) GROUP BY ACTIVITY_ID
					) b2 ON b1.ID = b2.ID
				) a2",
				'a1.ID = a2.ACTIVITY_ID'
			));

			foreach ($existedActivityIds as $existedActivityId)
			{
				$bindings = self::GetBindings($existedActivityId);
				$oldBindings = $bindings;
				$oldBindings[] = [
					'OWNER_TYPE_ID' => $ownerTypeID,
					'OWNER_ID' => $ownerID,
				];
				\Bitrix\Crm\Counter\Monitor::getInstance()->onChangeActivityBindings(
					$existedActivityId,
					$oldBindings,
					$bindings
				);
			}
		}
		//endregion
	}
	public static function DeleteBindingsByOwner($ownerTypeID, $ownerID)
	{
		$ownerID = intval($ownerID);
		$ownerTypeID = intval($ownerTypeID);
		if($ownerID <= 0 || $ownerTypeID <= 0)
		{
			return array();
		}

		$bindingTableName = CCrmActivity::BINDING_TABLE_NAME;
		global $DB;

		$dbRes = $DB->Query(
			"SELECT ACTIVITY_ID FROM {$bindingTableName} WHERE OWNER_ID = {$ownerID} AND OWNER_TYPE_ID = {$ownerTypeID}"
		);

		$processedIDs = array();
		if(is_object($dbRes))
		{
			while($arRes = $dbRes->Fetch())
			{
				$processedIDs[] = intval($arRes['ACTIVITY_ID']);
				Crm\Activity\UncompletedActivity::synchronizeForActivity((int)$arRes['ACTIVITY_ID'], [
					[
						'OWNER_TYPE_ID' => $ownerTypeID,
						'OWNER_ID' => $ownerID,
					]
				]);
			}
		}

		if(!empty($processedIDs))
		{
			$DB->Query(
				"DELETE FROM {$bindingTableName} WHERE OWNER_ID = {$ownerID} AND OWNER_TYPE_ID = {$ownerTypeID}"
			);

			foreach ($processedIDs as $activityId)
			{
				\Bitrix\Crm\Counter\Monitor::getInstance()->onChangeActivitySingleBinding(
					$activityId,
					[
						'OWNER_TYPE_ID' => $ownerTypeID,
						'OWNER_ID' => $ownerID,
					],
					[]
				);

				Crm\Activity\Provider\ProviderManager::syncBadgesOnBindingsChange(
					$activityId,
					[],
					[
						['OWNER_TYPE_ID' => $ownerTypeID, 'OWNER_ID' => $ownerID]
					],
				);
			}

			if (\CCrmOwnerType::IsDefined($ownerTypeID))
			{
				Crm\Service\Timeline\Monitor::getInstance()->onActivityRemove(new Crm\ItemIdentifier($ownerTypeID, $ownerID));
			}
		}

		return $processedIDs;
	}
	public static function DeleteUnbound($arBindings = null)
	{
		$tableName = CCrmActivity::TABLE_NAME;
		$bindingTableName = CCrmActivity::BINDING_TABLE_NAME;
		global $DB;
		$dbRes = $DB->Query(
			"SELECT ID FROM {$tableName} WHERE ID NOT IN (SELECT ACTIVITY_ID FROM {$bindingTableName})"
		);

		$delOptions = array(
			'SKIP_BINDINGS' => true,
			'SKIP_USER_ACTIVITY_SYNC' => true,
			'SKIP_STATISTICS' => true
		);

		if(ActivitySettings::getValue(ActivitySettings::KEEP_UNBOUND_TASKS))
		{
			$delOptions['SKIP_TASKS'] = true;
		}

		if(is_array($arBindings) && !empty($arBindings))
		{
			$delOptions['ACTUAL_BINDINGS'] = $arBindings;
		}

		$processedIDs = array();
		$responsibleIDs = array();
		while($arRes = $dbRes->Fetch())
		{
			$itemID = intval($arRes['ID']);

			$item = self::GetByID($itemID, false);
			if(!is_array($item))
			{
				continue;
			}

			$processedIDs[] = $itemID;
			$responsibleID = isset($item['RESPONSIBLE_ID']) ? intval($item['RESPONSIBLE_ID']) : 0;
			if($responsibleID > 0 && !in_array($responsibleID, $responsibleIDs, true))
			{
				$responsibleIDs[] = $responsibleID;
			}

			$delOptions['ACTUAL_ITEM'] = $item;
			self::Delete($itemID, false, false, $delOptions);
		}

		// Synchronize user activity -->
		if(is_array($arBindings) && !empty($arBindings))
		{
			foreach($arBindings as &$arBinding)
			{
				foreach($responsibleIDs as $responsibleID)
				{
					self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], $responsibleID);
				}
				self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], 0);
				\Bitrix\Crm\Activity\CommunicationStatistics::rebuild(
					$arBinding['OWNER_TYPE_ID'],
					array($arBinding['OWNER_ID'])
				);
			}
			unset($arBinding);
		}
		// <-- Synchronize user activity

		return $processedIDs;
	}
	private static function ResolveEventTypeName($entityTypeID, $default = self::ACTIVITY_DEFAULT, string $providerId = '')
	{
		$entityTypeID = intval($entityTypeID);

		if($entityTypeID === CCrmActivityType::Call)
		{
			return 'CALL';
		}
		elseif($entityTypeID === CCrmActivityType::Meeting)
		{
			return 'MEETING';
		}
		elseif (
			$entityTypeID === CCrmActivityType::Task
			|| (
				$entityTypeID === CCrmActivityType::Provider
				&& $providerId === Crm\Activity\Provider\Tasks\Task::getId()
			)
		)
		{
			return 'TASK';
		}
		elseif($entityTypeID === CCrmActivityType::Email)
		{
			return 'EMAIL';
		}

		return $default;
	}
	private static function PrepareUpdateEvent($fieldName, $arNewRow, $arOldRow, &$arEvents)
	{
		$fieldName = mb_strtoupper(strval($fieldName));

		if($fieldName === '')
		{
			return false;
		}

		$typeID = self::GetActivityType($arNewRow);

		$changed = false;
		$oldText = $newText = '';

		if($fieldName === 'NOTIFY')
		{
			$oldType = isset($arOldRow['NOTIFY_TYPE']) ? intval($arOldRow['NOTIFY_TYPE']) : CCrmActivityNotifyType::None;
			$newType = isset($arNewRow['NOTIFY_TYPE']) ? intval($arNewRow['NOTIFY_TYPE']) : CCrmActivityNotifyType::None;

			$oldVal = isset($arOldRow['NOTIFY_VALUE']) ? intval($arOldRow['NOTIFY_VALUE']) : 0;
			$newVal = isset($arNewRow['NOTIFY_VALUE']) ? intval($arNewRow['NOTIFY_VALUE']) : 0;

			if($oldType !== $newType || $oldVal !== $newVal)
			{
				$changed = true;

				$oldText =
					$oldType === CCrmActivityNotifyType::None
						? CCrmActivityNotifyType::ResolveDescription(CCrmActivityNotifyType::None)
						: (strval($oldVal).' '.CCrmActivityNotifyType::ResolveDescription($oldType));

				$newText =
					$newType === CCrmActivityNotifyType::None
						? CCrmActivityNotifyType::ResolveDescription(CCrmActivityNotifyType::None)
						: (strval($newVal).' '.CCrmActivityNotifyType::ResolveDescription($newType));
			}
		}
		else
		{
			$old = isset($arOldRow[$fieldName]) ? strval($arOldRow[$fieldName]) : '';
			$new = isset($arNewRow[$fieldName]) ? strval($arNewRow[$fieldName]) : '';

			if(strcmp($old, $new) !== 0)
			{
				$changed = true;

				$oldText = $old;
				$newText = $new;

				if($fieldName === 'COMPLETED')
				{
					$oldText = CCrmActivityStatus::ResolveDescription(
						$old === 'Y' ? CCrmActivityStatus::Completed : CCrmActivityStatus::Waiting,
						self::GetActivityType($arOldRow)
					);

					$newText = CCrmActivityStatus::ResolveDescription(
						$new === 'Y' ? CCrmActivityStatus::Completed : CCrmActivityStatus::Waiting,
						self::GetActivityType($arNewRow)
					);
				}
				elseif($fieldName === 'PRIORITY')
				{
					$oldText = CCrmActivityPriority::ResolveDescription($old);
					$newText = CCrmActivityPriority::ResolveDescription($new);
				}
				elseif($fieldName === 'DIRECTION')
				{
					$oldText = CCrmActivityDirection::ResolveDescription($old, $typeID);
					$newText = CCrmActivityDirection::ResolveDescription($new, $typeID);
				}
				elseif($fieldName === 'RESPONSIBLE_ID')
				{
					$oldID = intval($old);
					$arOldUser = array();

					$newID = intval($new);
					$arNewUser = array();

					$dbUser = CUser::GetList(
						'id',
						'asc',
						array('ID'=> "{$oldID}|{$newID}"),
						array(
							'FIELDS'=> array(
								'ID',
								'LOGIN',
								'EMAIL',
								'NAME',
								'LAST_NAME',
								'SECOND_NAME',
								'TITLE'
							)
						)
					);

					while (is_array($arUser = $dbUser->Fetch()))
					{
						$userID = intval($arUser['ID']);
						if($userID === $oldID)
						{
							$arOldUser = $arUser;
						}
						elseif($userID === $newID)
						{
							$arNewUser = $arUser;
						}
					}

					$template = CSite::GetNameFormat(false);
					$oldText = CUser::FormatName($template, $arOldUser);
					$newText = CUser::FormatName($template, $arNewUser);
				}
				elseif($fieldName === 'LOCATION' && \Bitrix\Main\Loader::includeModule('calendar'))
				{
					$oldText = \CCalendar::GetTextLocation($oldText);
					$newText = \CCalendar::GetTextLocation($newText);
				}
				elseif($fieldName === 'DESCRIPTION')
				{
					$parser = new CTextParser();
					if ($arOldRow['DESCRIPTION_TYPE'] == CCrmContentType::BBCode)
					{
						$oldText = $parser->convertText($oldText);
					}
					if ($arNewRow['DESCRIPTION_TYPE'] == CCrmContentType::BBCode)
					{
						$newText = $parser->convertText($newText);
					}
				}
			}
		}

		if($changed)
		{
			$providerID = $arNewRow['PROVIDER_ID'] ?? '';
			$typeName = self::ResolveEventTypeName($typeID, self::ACTIVITY_DEFAULT, $providerID);
			$name = isset($arNewRow['SUBJECT']) ? strval($arNewRow['SUBJECT']) : '';
			if($name === '')
			{
				$name = "[{$arNewRow['ID']}]";
			}

			$arEvents[] = array(
				'EVENT_NAME' => GetMessage(
					"CRM_ACTIVITY_CHANGE_{$typeName}_{$fieldName}",
					array('#NAME#'=> $name)
				),
				'EVENT_TEXT_1' => $oldText !== '' ? $oldText : GetMessage('CRM_ACTIVITY_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => $newText !== '' ? $newText : GetMessage('CRM_ACTIVITY_FIELD_COMPARE_EMPTY'),
				'USER_ID' => isset($arNewRow['EDITOR_ID']) ? $arNewRow['EDITOR_ID'] : 0
			);
		}

		return $changed;
	}

	private static function registerAddEvent(array $bindings, array $fields, bool $checkPerms): void
	{
		$eventId = self::createAddEvent($bindings[0]['OWNER_TYPE_ID'], $bindings[0]['OWNER_ID'], $fields, false);

		if (empty($eventId))
		{
			return;
		}

		unset($bindings[0]);

		if (empty($bindings))
		{
			return;
		}

		$CCrmEvent = new CCrmEvent();
		$arEvents = [];
		foreach ($bindings as $key => $arBinding)
		{
			$arEvents[$key]['ENTITY_TYPE'] = CCrmOwnerType::ResolveName($arBinding['OWNER_TYPE_ID']);
			$arEvents[$key]['ENTITY_ID'] = $arBinding['OWNER_ID'];
			$arEvents[$key]['ENTITY_FIELD'] = 'ACTIVITIES';
			$arEvents[$key]['USER_ID']  = (int)($fields['EDITOR_ID'] ?? CCrmSecurityHelper::GetCurrentUserID());
		}

		$CCrmEvent->AddRelation($eventId, $arEvents, $checkPerms);
	}

	private static function createAddEvent(int $ownerTypeId, int $ownerId, array $row, bool $checkPerms): ?int
	{
		$typeID = self::GetActivityType($row);
		$providerID = $row['PROVIDER_ID'] ?? '';
		$typeName = self::ResolveEventTypeName($typeID, self::ACTIVITY_DEFAULT, $providerID);

		$subject = $row['SUBJECT'] ?? '';
		$location = $row['LOCATION'] ?? '';
		$description = $row['DESCRIPTION'] ?? '';
		$descriptionType = isset($row['DESCRIPTION_TYPE']) ? (int)$row['DESCRIPTION_TYPE'] : CCrmContentType::PlainText;

		$eventText = '';
		if($subject !== '' && $providerID !== ToDo::getId())
		{
			$eventText .= GetMessage('CRM_ACTIVITY_SUBJECT').': '.$subject.PHP_EOL;
		}

		if($location !== '')
		{
			$eventText .= GetMessage('CRM_ACTIVITY_LOCATION').': '.$location.PHP_EOL;
		}

		if($descriptionType === CCrmContentType::Html)
		{
			$eventText .= $description;
		}
		elseif($descriptionType === CCrmContentType::BBCode)
		{
			$parser = new CTextParser();
			$eventText .= $parser->convertText($description);
		}
		elseif($descriptionType === CCrmContentType::PlainText)
		{
			$eventText .= $description;
		}

		if ($providerID === ToDo::getId())
		{
			$subject = ToDo::getActivityTitle($row);
			if (empty($eventText))
			{
				$eventText = $subject;
			}
			else
			{
				$eventText = $subject . ': ' . $eventText;
			}
		}

		$arEvent = [
			'EVENT_NAME' => Loc::getMessage("CRM_ACTIVITY_{$typeName}_ADD"),
			'EVENT_TEXT_1' => $eventText,
			'EVENT_TEXT_2' => '',
			'USER_ID' => (int)($row['EDITOR_ID'] ?? 0),
		];

		return self::createEvent($ownerTypeId, $ownerId, $arEvent, $checkPerms);
	}

	private static function RegisterUpdateEvent($ownerTypeID, $ownerID, $arNewRow, $arOldRow, $checkPerms)
	{
		$arEvents = array();

		$providerId = $arNewRow['PROVIDER_ID'] ?? $arOldRow['PROVIDER_ID'];
		if ($providerId !== ToDo::getId())
		{
			self::PrepareUpdateEvent('SUBJECT', $arNewRow, $arOldRow, $arEvents);
		}
		self::PrepareUpdateEvent('START_TIME', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('END_TIME', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('COMPLETED', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('PRIORITY', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('NOTIFY', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('DESCRIPTION', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('LOCATION', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('DIRECTION', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('RESPONSIBLE_ID', $arNewRow, $arOldRow, $arEvents);

		return count($arEvents) > 0 ? self::RegisterEvents($ownerTypeID, $ownerID, $arEvents, $checkPerms) : false;
	}

	private static function RegisterRemoveEvent($ownerTypeID, $ownerID, $arRow, $checkPerms, $userID = 0)
	{
		$typeID = self::GetActivityType($arRow);
		$providerID = $arRow['PROVIDER_ID'] ?? '';
		$typeName = self::ResolveEventTypeName($typeID, self::ACTIVITY_DEFAULT, $providerID);

		$name = isset($arRow['SUBJECT']) ? strval($arRow['SUBJECT']) : '';
		if($name === '')
		{
			$name = "[{$arRow['ID']}]";
		}

		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}

		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$description = $arRow['DESCRIPTION'] ?? '';
		if ($arRow['DESCRIPTION_TYPE'] == CCrmContentType::BBCode)
		{
			$parser = new \CTextParser();
			$description = $parser->convertText($description);
		}
		if ($providerID === ToDo::getId())
		{
			$name = ToDo::getActivityTitle($arRow);
			if (!empty($description))
			{
				$name .= ': ' . $description;
			}
		}

		return self::RegisterEvents(
			$ownerTypeID,
			$ownerID,
			array(
				array(
					'EVENT_NAME' => GetMessage("CRM_ACTIVITY_{$typeName}_REMOVE"),
					'EVENT_TEXT_1' => $name,
					'EVENT_TEXT_2' => '',
					'USER_ID' => $userID
				)
			),
			$checkPerms
		);
	}
	private static function GetEventName($arFields)
	{
		if(!is_array($arFields))
		{
			return '';
		}

		$name = isset($arFields['SUBJECT']) ? strval($arFields['SUBJECT']) : '';
		if($name === '' && isset($arFields['ID']))
		{
			$name = "[{$arFields['ID']}]";
		}

		return $name;
	}
	private static function GetEventMessageSuffix($arFields)
	{
		$typeID = self::GetActivityType($arFields);
		return self::ResolveEventTypeName($typeID, '');
	}
	protected static function RegisterFileEvent($ID, $arFields, $fileInfo, $eventType, $checkPerms = true)
	{
		if(!is_array($fileInfo))
		{
			return false;
		}

		// 'TYPE_ID' and SUBJECT are required for event registration
		if(!is_array($arFields) || count($arFields) === 0 || !isset($arFields['TYPE_ID']) || !isset($arFields['SUBJECT']))
		{
			$dbRes = self::GetList(
				array(),
				array('=ID' => $ID),
				false,
				false,
				array('TYPE_ID', 'SUBJECT')
			);

			$arFields = $dbRes->Fetch();
			if(!$arFields)
			{
				self::RegisterError(array('text' => 'Activity not found.'));
				return false;
			}
		}

		$eventType = mb_strtoupper(strval($eventType));
		if($eventType === '')
		{
			$eventType = 'ADD';
		}

		$suffix = self::GetEventMessageSuffix($arFields);
		if($suffix !== '')
		{
			$suffix = "_{$suffix}";
		}

		$eventName = GetMessage(
			"CRM_ACTIVITY_FILE_{$eventType}{$suffix}",
			array('#NAME#' => self::GetEventName($arFields))
		);

		$arBindings = isset($arFields['BINDINGS']) ? $arFields['BINDINGS'] : self::GetBindings($ID);
		foreach($arBindings as &$arBinding)
		{
			self::RegisterEvents(
				$arBinding['OWNER_TYPE_ID'],
				$arBinding['OWNER_ID'],
				array(
					array(
						'EVENT_NAME' => $eventName,
						'EVENT_TEXT_1' => $fileInfo['FILE_NAME'],
						'EVENT_TEXT_2' => '',
						'USER_ID' => isset($arFields['EDITOR_ID']) ? $arFields['EDITOR_ID'] : 0
					)
				),
				$checkPerms
			);

		}
		unset($arBinding);

		return true;
	}
	protected static function RegisterCommunicationEvent($ID, $arFields, $arComm, $eventType, $checkPerms = true)
	{
		if(!is_array($arComm))
		{
			return false;
		}

		// 'TYPE_ID' and SUBJECT are required for event registration
		if(!is_array($arFields) || count($arFields) === 0 || !isset($arFields['TYPE_ID']) || !isset($arFields['SUBJECT']))
		{
			$dbRes = self::GetList(array(), array('=ID' => $ID), false, false, array('TYPE_ID', 'SUBJECT'));
			$arFields = $dbRes->Fetch();
			if(!$arFields)
			{
				self::RegisterError(array('text' => 'Activity not found.'));
				return false;
			}
		}

		$eventType = mb_strtoupper(strval($eventType));
		if($eventType === '')
		{
			$eventType = 'ADD';
		}

		$suffix = self::GetEventMessageSuffix($arFields);
		if($suffix !== '')
		{
			$suffix = "_{$suffix}";
		}

		$communicationType = $arComm['TYPE'] ?? '';
		$eventName = GetMessage(
			"CRM_ACTIVITY_COMM_{$communicationType}_{$eventType}{$suffix}",
			array('#NAME#' => self::GetEventName($arFields))
		);

		if ($eventName !== '')
		{
			$arBindings = $arFields['BINDINGS'] ?? self::GetBindings($ID);
			foreach($arBindings as &$arBinding)
			{
				self::RegisterEvents(
					$arBinding['OWNER_TYPE_ID'],
					$arBinding['OWNER_ID'],
					array(
						array(
							'EVENT_NAME' => $eventName,
							'EVENT_TEXT_1' => $arComm['VALUE'] ?? '',
							'EVENT_TEXT_2' => '',
							'USER_ID' => $arFields['EDITOR_ID'] ?? 0
						)
					),
					$checkPerms
				);
			}
			unset($arBinding);
		}

		return true;
	}
	public static function GetActivityType(&$arFields)
	{
		return is_array($arFields) && isset($arFields['TYPE_ID']) ? intval($arFields['TYPE_ID']) : CCrmActivityType::Undefined;
	}
	protected static function RegisterEvents($ownerTypeID, $ownerID, $arEvents, $checkPerms)
	{
		foreach($arEvents as $arEvent)
		{
			self::createEvent($ownerTypeID, $ownerID, $arEvent, $checkPerms);
		}

		return true;
	}

	private static function createEvent(int $ownerTypeId, int $ownerId, array $event, bool $checkPerms): ?int
	{
		$CCrmEvent = new CCrmEvent();

		$event['EVENT_TYPE'] = 1;
		$event['ENTITY_TYPE'] = CCrmOwnerType::ResolveName($ownerTypeId);
		$event['ENTITY_ID'] = $ownerId;
		$event['ENTITY_FIELD'] = 'ACTIVITIES';

		if(!isset($event['USER_ID']) || $event['USER_ID'] <= 0)
		{
			$event['USER_ID']  = \Bitrix\Crm\Service\Container::getInstance()->getContext()->getUserId();
		}

		$result = $CCrmEvent->Add($event, $checkPerms);
		if (!$result)
		{
			return null;
		}

		return $result;
	}

	protected static function GetUserPermissions()
	{
		if(self::$USER_PERMISSIONS === null)
		{
			self::$USER_PERMISSIONS = CCrmPerms::GetCurrentUserPermissions();
		}

		return self::$USER_PERMISSIONS;
	}
	public static function CheckUpdatePermission($ownerType, $ownerID, $userPermissions = null)
	{
		$ownerTypeName = is_numeric($ownerType)
			? CCrmOwnerType::ResolveName((int)$ownerType)
			: mb_strtoupper(strval($ownerType));

		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		$permissionEntityType = CCrmPerms::ResolvePermissionEntityType($ownerTypeName, $ownerID);
		return CCrmAuthorizationHelper::CheckUpdatePermission($permissionEntityType, $ownerID, $userPermissions);
	}
	public static function CheckItemUpdatePermission(array $fields, $userPermissions = null)
	{
		static $cache = [];

		$ID = isset($fields['ID']) ? (int)$fields['ID'] : 0;
		if($ID <=  0)
		{
			return false;
		}
		if (isset($cache[$ID]))
		{
			return $cache[$ID];
		}

		$bindings = self::GetBindings($ID);
		if(is_array($bindings) && !empty($bindings))
		{
			foreach($bindings as &$binding)
			{
				if(self::CheckUpdatePermission($binding['OWNER_TYPE_ID'], $binding['OWNER_ID'], $userPermissions))
				{
					$cache[$ID] = true;

					return true;
				}
			}
			unset($binding);
			$cache[$ID] = false;

			return false;
		}

		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? (int)$fields['OWNER_TYPE_ID'] : CCrmOwnerType::Undefined;
		$ownerID = isset($fields['OWNER_ID']) ? (int)$fields['OWNER_ID'] : 0;

		$result = $ownerID > 0
			&& CCrmOwnerType::IsDefined($ownerTypeID)
			&& self::CheckUpdatePermission($ownerTypeID, $ownerID, $userPermissions);

		$cache[$ID] = $result;

		return $result;
	}
	public static function CheckCompletePermission($ownerType, $ownerID, $userPermissions = null, $params = null)
	{
		$ownerTypeName = is_numeric($ownerType)
			? CCrmOwnerType::ResolveName((int)$ownerType)
			: mb_strtoupper(strval($ownerType));

		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		if(is_array($params))
		{
			$ID = isset($params['ID']) ? $params['ID'] : 0;
			$fields = isset($params['FIELDS']) ? $params['FIELDS'] : null;
			if((!is_array($fields) || !isset($fields['TYPE_ID']) || !isset($fields['PROVIDER_ID']) )&& $ID > 0)
			{
				$fields = self::GetByID($ID, false);
			}

			if(is_array($fields))
			{
				$provider = self::GetActivityProvider($fields);
				if ($provider !== null)
				{
					$associatedEntityID = isset($fields['ASSOCIATED_ENTITY_ID']) ? (int)$fields['ASSOCIATED_ENTITY_ID'] : 0;
					$result = $provider::checkCompletePermission($associatedEntityID, $fields, $userPermissions->GetUserID());
					if (is_bool($result))
					{
						return $result;
					}
				}
			}
		}

		$permissionEntityType = CCrmPerms::ResolvePermissionEntityType($ownerTypeName, $ownerID);
		return CCrmAuthorizationHelper::CheckUpdatePermission($permissionEntityType, $ownerID, $userPermissions);
	}
	public static function CheckItemCompletePermission(array $fields, $userPermissions = null)
	{
		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		$provider = self::GetActivityProvider($fields);
		if ($provider !== null)
		{
			$associatedEntityID = isset($fields['ASSOCIATED_ENTITY_ID']) ? (int)$fields['ASSOCIATED_ENTITY_ID'] : 0;
			$result = $provider::checkCompletePermission($associatedEntityID, $fields, $userPermissions->GetUserID());
			if (is_bool($result))
			{
				return $result;
			}
		}

		return self::CheckItemUpdatePermission($fields, $userPermissions);
	}
	public static function CheckDeletePermission($ownerType, $ownerID, $userPermissions = null)
	{
		$ownerTypeName = is_numeric($ownerType)
			? CCrmOwnerType::ResolveName((int)$ownerType)
			: mb_strtoupper(strval($ownerType));

		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		$permissionEntityType = CCrmPerms::ResolvePermissionEntityType($ownerTypeName, $ownerID);
		return CCrmAuthorizationHelper::CheckDeletePermission($permissionEntityType, $ownerID, $userPermissions);
	}
	public static function CheckItemDeletePermission(array $fields, $userPermissions = null)
	{
		$ID = isset($fields['ID']) ? (int)$fields['ID'] : 0;
		if($ID <=  0)
		{
			return false;
		}

		$bindings = self::GetBindings($ID);
		if(is_array($bindings) && !empty($bindings))
		{
			foreach($bindings as &$binding)
			{
				if(!self::CheckDeletePermission($binding['OWNER_TYPE_ID'], $binding['OWNER_ID'], $userPermissions))
				{
					return false;
				}
			}
			unset($binding);
			return true;
		}

		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? (int)$fields['OWNER_TYPE_ID'] : CCrmOwnerType::Undefined;
		$ownerID = isset($fields['OWNER_ID']) ? (int)$fields['OWNER_ID'] : 0;

		return $ownerID > 0
			&& CCrmOwnerType::IsDefined($ownerTypeID)
			&& self::CheckDeletePermission($ownerTypeID, $ownerID, $userPermissions);
	}
	public static function CheckReadPermission($ownerType, $ownerID, $userPermissions = null)
	{
		$ownerTypeName = is_numeric($ownerType)
			? CCrmOwnerType::ResolveName((int)$ownerType)
			: mb_strtoupper(strval($ownerType));

		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		$permissionEntityType = CCrmPerms::ResolvePermissionEntityType($ownerTypeName, $ownerID);
		return CCrmAuthorizationHelper::CheckReadPermission($permissionEntityType, $ownerID, $userPermissions);
	}
	public static function CheckItemPostponePermission(array $fields, $userPermissions = null)
	{
		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		$provider = self::GetActivityProvider($fields);
		if ($provider !== null)
		{
			$associatedEntityID = isset($fields['ASSOCIATED_ENTITY_ID']) ? (int)$fields['ASSOCIATED_ENTITY_ID'] : 0;
			$result = $provider::checkPostponePermission($associatedEntityID, $fields, $userPermissions->GetUserID());
			if (is_bool($result))
			{
				return $result;
			}
		}

		return self::CheckItemUpdatePermission($fields, $userPermissions);
	}
	protected static function ReadContactCommunication(&$arRes, $communicationType)
	{
		$item = array(
			'ENTITY_ID' => $arRes['ELEMENT_ID'],
			'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
			'TYPE' => $communicationType,
			'VALUE' => $arRes['VALUE'],
			'ENTITY_SETTINGS' => array(
				'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
				'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
				'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : '',
				'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
				'COMPANY_TITLE' => isset($arRes['COMPANY_TITLE']) ? $arRes['COMPANY_TITLE'] : '',
				'IMAGE_FILE_ID' => isset($arRes['PHOTO']) && $arRes['PHOTO'] > 0 ? $arRes['PHOTO'] : 0,
			)
		);

		self::PrepareCommunicationInfo($item);
		return $item;
	}
	protected static function ReadCompanyCommunication(&$arRes, $communicationType)
	{
		$item = array(
			'ENTITY_ID' => $arRes['ELEMENT_ID'],
			'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
			'TYPE' => $communicationType,
			'VALUE' => $arRes['VALUE'],
			'ENTITY_SETTINGS' => array(
				'COMPANY_TITLE' => $arRes['COMPANY_TITLE'],
				'IMAGE_FILE_ID' => isset($arRes['LOGO']) && $arRes['LOGO'] > 0 ? $arRes['LOGO'] : 0,
			)
		);

		self::PrepareCommunicationInfo($item);
		return $item;
	}
	protected static function ReadLeadCommunication(&$arRes, $communicationType)
	{
		$item = array(
			'ENTITY_ID' => $arRes['ELEMENT_ID'],
			'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
			'TYPE' => $communicationType,
			'VALUE' => $arRes['VALUE'],
			'ENTITY_SETTINGS' => array(
				'LEAD_TITLE' => isset($arRes['LEAD_TITLE']) ? $arRes['LEAD_TITLE'] : '',
				'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
				'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
				'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : '',
				'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
			)
		);

		self::PrepareCommunicationInfo($item);
		return $item;
	}
	protected static function CreateLogicalField($fieldName, &$arFields)
	{
		global $DB;

		$fieldName = strval($fieldName);

		if($fieldName === 'TYPE_NAME')
		{
			if(isset(self::$FIELDS_CACHE[LANGUAGE_ID]) && isset(self::$FIELDS_CACHE[LANGUAGE_ID]['TYPE_NAME']))
			{
				$arFields['TYPE_NAME'] = self::$FIELDS_CACHE[LANGUAGE_ID]['TYPE_NAME'];
				return;
			}

			$arTypeDescr = CCrmActivityType::GetAllDescriptions();
			if(count($arTypeDescr) == 0)
			{
				return;
			}

			$sql = 'CASE '.self::TABLE_ALIAS.'.TYPE_ID';
			foreach($arTypeDescr as $typeID=>&$typeDescr)
			{
				$sql .= " WHEN {$typeID} THEN '{$DB->ForSql($typeDescr)}'";
			}
			unset($typeDescr);
			$sql .= ' END';

			if(!isset(self::$FIELDS_CACHE[LANGUAGE_ID]))
			{
				self::$FIELDS_CACHE[LANGUAGE_ID] = array();
			}
			$arFields['TYPE_NAME'] = self::$FIELDS_CACHE[LANGUAGE_ID]['TYPE_NAME'] = array('FIELD' => $sql, 'TYPE' => 'string');
		}
	}
	public static function GetCommunicationsByOwner($entityType, $entityID, $communicationType)
	{
		global $DB;
		$entityType = mb_strtoupper(strval($entityType));
		$entityTypeID =  CCrmOwnerType::ResolveID($entityType);
		$entityID = intval($entityID);
		$communicationType = strval($communicationType);

		$commTableName = CCrmActivity::COMMUNICATION_TABLE_NAME;
		$sql = "SELECT ID, ENTITY_ID, ENTITY_TYPE_ID, VALUE FROM {$commTableName} WHERE OWNER_ID = {$entityID} AND OWNER_TYPE_ID = {$entityTypeID} AND TYPE = '{$DB->ForSql($communicationType)}' ORDER BY ID DESC";

		$dbRes = $DB->Query(
			$sql
		);

		$result = array();
		while($arRes = $dbRes->Fetch())
		{
			$result[] = array(
				'ENTITY_ID' => $arRes['ENTITY_ID'],
				'ENTITY_TYPE_ID' => $arRes['ENTITY_TYPE_ID'],
				'ENTITY_TYPE' => CCrmOwnerType::ResolveName($arRes['ENTITY_TYPE_ID']),
				'TYPE' => $communicationType,
				'VALUE' => $arRes['VALUE']
			);
		}
		return $result;
	}

	public static function FindContactCommunications($needle, $communicationType, $top = 50)
	{
		$needle = trim($needle);
		$communicationType = trim($communicationType);
		$top = intval($top);

		if($needle === '')
		{
			return array();
		}

		global $DB;
		$fieldMultiTableName = CCrmActivity::FIELD_MULTI_TABLE_NAME;
		$contactTableName = CCrmContact::TABLE_NAME;
		$companyTableName = CCrmCompany::TABLE_NAME;
		$result = array();

		$needleSql = $DB->ForSqlLike($needle.'%');
		$firstNameSql = '';
		$lastNameSql = '';

		$personFormatID = \Bitrix\Crm\Format\PersonNameFormatter::getFormatID();

		$nameParts = array();
		\Bitrix\Crm\Format\PersonNameFormatter::tryParseName(
			$needle,
			$personFormatID,
			$nameParts
		);

		if(isset($nameParts['NAME'])
			&& $nameParts['NAME'] !== ''
			&& isset($nameParts['LAST_NAME'])
			&& $nameParts['LAST_NAME'] !== ''
		)
		{
			$firstNameSql = $DB->ForSqlLike($nameParts['NAME'].'%');
			$lastNameSql = $DB->ForSqlLike($nameParts['LAST_NAME'].'%');
		}

		if($communicationType === '')
		{
			if($firstNameSql !== '' && $lastNameSql !== '')
			{
				if($personFormatID === \Bitrix\Crm\Format\PersonNameFormatter::FirstSecondLast
					|| $personFormatID === \Bitrix\Crm\Format\PersonNameFormatter::LastFirstSecond
				)
				{
					$sql  = "SELECT C.ID AS ELEMENT_ID, '' AS VALUE_TYPE, '' AS VALUE, C.NAME, C.SECOND_NAME, C.LAST_NAME, C.HONORIFIC, C.PHOTO, CO.TITLE COMPANY_TITLE FROM {$contactTableName} C LEFT OUTER JOIN {$companyTableName} CO ON C.COMPANY_ID = CO.ID WHERE C.NAME LIKE '{$firstNameSql}' AND (C.LAST_NAME LIKE '{$lastNameSql}' OR C.SECOND_NAME LIKE '{$lastNameSql}')";
				}
				else
				{
					$sql  = "SELECT C.ID AS ELEMENT_ID, '' AS VALUE_TYPE, '' AS VALUE, C.NAME, C.SECOND_NAME, C.LAST_NAME, C.HONORIFIC, C.PHOTO, CO.TITLE COMPANY_TITLE FROM {$contactTableName} C LEFT OUTER JOIN {$companyTableName} CO ON C.COMPANY_ID = CO.ID WHERE C.NAME LIKE '{$firstNameSql}' AND C.LAST_NAME LIKE '{$lastNameSql}'";
				}
			}
			else
			{
				$sql  = "SELECT C.ID AS ELEMENT_ID, '' AS VALUE_TYPE, '' AS VALUE, C.NAME, C.SECOND_NAME, C.LAST_NAME, C.HONORIFIC, C.PHOTO, CO.TITLE COMPANY_TITLE FROM {$contactTableName} C LEFT OUTER JOIN {$companyTableName} CO ON C.COMPANY_ID = CO.ID WHERE C.NAME LIKE '{$needleSql}' OR C.LAST_NAME LIKE '{$needleSql}'";
			}

			if($top > 0)
			{
				$sql = $DB->TopSql($sql, $top);
			}

			$dbRes = $DB->Query(
				$sql
			);

			while($arRes = $dbRes->Fetch())
			{
				$result[] = CAllCrmActivity::ReadContactCommunication($arRes, $communicationType);
			}

			return $result;
		}

		//Search by Name
		if($firstNameSql !== '' && $lastNameSql !== '')
		{
			if($personFormatID === \Bitrix\Crm\Format\PersonNameFormatter::FirstSecondLast
				|| $personFormatID === \Bitrix\Crm\Format\PersonNameFormatter::LastFirstSecond
			)
			{
				$sql  = "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, C.NAME, C.SECOND_NAME, C.LAST_NAME, C.HONORIFIC, C.PHOTO, CO.TITLE COMPANY_TITLE FROM {$fieldMultiTableName} FM INNER JOIN {$contactTableName} C ON FM.ELEMENT_ID = C.ID AND FM.ENTITY_ID = 'CONTACT' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' AND C.NAME LIKE '{$firstNameSql}' AND (C.LAST_NAME LIKE '{$lastNameSql}' OR C.SECOND_NAME LIKE '{$lastNameSql}') LEFT OUTER JOIN {$companyTableName} CO ON C.COMPANY_ID = CO.ID";
			}
			else
			{
				$sql  = "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, C.NAME, C.SECOND_NAME, C.LAST_NAME, C.HONORIFIC, C.PHOTO, CO.TITLE COMPANY_TITLE FROM {$fieldMultiTableName} FM INNER JOIN {$contactTableName} C ON FM.ELEMENT_ID = C.ID AND FM.ENTITY_ID = 'CONTACT' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' AND C.NAME LIKE '{$firstNameSql}' AND C.LAST_NAME LIKE '{$lastNameSql}' LEFT OUTER JOIN {$companyTableName} CO ON C.COMPANY_ID = CO.ID";
			}
		}
		else
		{
			$sql  = "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, C.NAME, C.SECOND_NAME, C.LAST_NAME, C.HONORIFIC, C.PHOTO, CO.TITLE COMPANY_TITLE FROM {$fieldMultiTableName} FM INNER JOIN {$contactTableName} C ON FM.ELEMENT_ID = C.ID AND FM.ENTITY_ID = 'CONTACT' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' AND (C.NAME LIKE '{$needleSql}' OR C.LAST_NAME LIKE '{$needleSql}') LEFT OUTER JOIN {$companyTableName} CO ON C.COMPANY_ID = CO.ID";
		}
		if($top > 0)
		{
			$sql = $DB->TopSql($sql, $top);
		}

		$dbRes = $DB->Query(
			$sql
		);

		while($arRes = $dbRes->Fetch())
		{
			$result[] = CAllCrmActivity::ReadContactCommunication($arRes, $communicationType);
		}

		//Search By Communication
		$sql  = "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, C.NAME, C.SECOND_NAME, C.LAST_NAME, C.HONORIFIC, C.PHOTO, CO.TITLE COMPANY_TITLE FROM {$fieldMultiTableName} FM INNER JOIN {$contactTableName} C ON FM.ELEMENT_ID = C.ID AND FM.ENTITY_ID = 'CONTACT' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' AND FM.VALUE LIKE '{$needleSql}' LEFT OUTER JOIN {$companyTableName} CO ON C.COMPANY_ID = CO.ID";
		if($top > 0)
		{
			$sql = $DB->TopSql($sql, $top);
		}

		$dbRes = $DB->Query(
			$sql
		);

		while($arRes = $dbRes->Fetch())
		{
			$result[] = CAllCrmActivity::ReadContactCommunication($arRes, $communicationType);
		}

		return $result;
	}
	public static function FindCompanyCommunications($needle, $communicationType, $top = 50)
	{
		$needle = strval($needle);
		$communicationType = strval($communicationType);
		$top = intval($top);

		if($needle === '')
		{
			return array();
		}

		global $DB;
		$fieldMultiTableName = CCrmActivity::FIELD_MULTI_TABLE_NAME;
		$companyTableName = CCrmCompany::TABLE_NAME;
		$result = array();

		$needleSql = $DB->ForSqlLike($needle.'%');

		if($communicationType === '')
		{
			//Search by FULL_NAME
			$sql  = "SELECT CO.ID AS ELEMENT_ID, '' AS VALUE_TYPE, '' AS VALUE, CO.TITLE AS COMPANY_TITLE, CO.LOGO FROM {$companyTableName} CO WHERE CO.TITLE LIKE '{$needleSql}'";
			if($top > 0)
			{
				$sql = $DB->TopSql($sql, $top);
			}

			$dbRes = $DB->Query(
				$sql
			);

			while($arRes = $dbRes->Fetch())
			{
				$result[] = CAllCrmActivity::ReadCompanyCommunication($arRes, $communicationType);
			}

			return $result;
		}

		//Search by Title
		$sql  = "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, CO.TITLE AS COMPANY_TITLE, CO.LOGO FROM {$fieldMultiTableName} FM INNER JOIN {$companyTableName} CO ON FM.ELEMENT_ID = CO.ID AND FM.ENTITY_ID = 'COMPANY' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' AND CO.TITLE LIKE '{$needleSql}'";
		if($top > 0)
		{
			$sql = $DB->TopSql($sql, $top);
		}

		$dbRes = $DB->Query(
			$sql
		);

		while($arRes = $dbRes->Fetch())
		{
			$result[] = CAllCrmActivity::ReadCompanyCommunication($arRes, $communicationType);
		}

		//Search by VALUE
		$sql  = "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, CO.TITLE AS COMPANY_TITLE, CO.LOGO FROM {$fieldMultiTableName} FM INNER JOIN {$companyTableName} CO ON FM.ELEMENT_ID = CO.ID AND FM.ENTITY_ID = 'COMPANY' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' AND FM.VALUE LIKE '{$DB->ForSqlLike($needle.'%')}'";
		if($top > 0)
		{
			$sql = $DB->TopSql($sql, $top);
		}

		$dbRes = $DB->Query(
			$sql
		);

		while($arRes = $dbRes->Fetch())
		{
			$result[] = CAllCrmActivity::ReadCompanyCommunication($arRes, $communicationType);
		}

		return $result;
	}
	public static function FindLeadCommunications($needle, $communicationType, $top = 50)
	{
		$needle = strval($needle);
		$communicationType = strval($communicationType);

		if($needle === '')
		{
			return array();
		}

		global $DB;
		$fieldMultiTableName = CCrmActivity::FIELD_MULTI_TABLE_NAME;
		$leadTableName = CCrmLead::TABLE_NAME;
		$result = array();

		$needleSql = $DB->ForSqlLike($needle.'%');
		$firstNameSql = '';
		$lastNameSql = '';

		$nameParts = array();
		\Bitrix\Crm\Format\PersonNameFormatter::tryParseName(
			$needle,
			\Bitrix\Crm\Format\PersonNameFormatter::getFormatID(),
			$nameParts
		);

		if(isset($nameParts['NAME'])
			&& $nameParts['NAME'] !== ''
			&& isset($nameParts['LAST_NAME'])
			&& $nameParts['LAST_NAME'] !== ''
		)
		{
			$firstNameSql = $DB->ForSqlLike($nameParts['NAME'].'%');
			$lastNameSql = $DB->ForSqlLike($nameParts['LAST_NAME'].'%');
		}

		if($communicationType === '')
		{
			//Search by TITLE and FULL_NAME
			if($firstNameSql !== '' && $lastNameSql !== '')
			{
				$sql  = "SELECT L.ID AS ELEMENT_ID, '' AS VALUE_TYPE, '' AS VALUE, L.NAME, L.SECOND_NAME, L.LAST_NAME, L.HONORIFIC, L.TITLE AS LEAD_TITLE FROM {$leadTableName} L WHERE L.TITLE LIKE '{$needleSql}' OR (L.NAME LIKE '{$firstNameSql}' AND L.LAST_NAME LIKE '{$lastNameSql}')";
			}
			else
			{
				$sql  = "SELECT L.ID AS ELEMENT_ID, '' AS VALUE_TYPE, '' AS VALUE, L.NAME, L.SECOND_NAME, L.LAST_NAME, L.HONORIFIC, L.TITLE AS LEAD_TITLE FROM {$leadTableName} L WHERE L.TITLE LIKE '{$needleSql}' OR L.NAME LIKE '{$needleSql}' OR L.LAST_NAME LIKE '{$needleSql}'";
			}
			if($top > 0)
			{
				$sql = $DB->TopSql($sql, $top);
			}

			$dbRes = $DB->Query(
				$sql
			);

			while($arRes = $dbRes->Fetch())
			{
				$result[] = CAllCrmActivity::ReadLeadCommunication($arRes, $communicationType);
			}

			return $result;
		}

		//Search by Name
		if($firstNameSql !== '' && $lastNameSql !== '')
		{
			$sql  = "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, L.NAME, L.SECOND_NAME, L.LAST_NAME, L.HONORIFIC, L.TITLE AS LEAD_TITLE FROM {$fieldMultiTableName} FM INNER JOIN {$leadTableName} L ON FM.ELEMENT_ID = L.ID AND FM.ENTITY_ID = 'LEAD' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' AND L.TITLE LIKE '{$needleSql}' OR (L.NAME LIKE '{$firstNameSql}' AND L.LAST_NAME LIKE '{$lastNameSql}')";
		}
		else
		{
			$sql  = "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, L.NAME, L.SECOND_NAME, L.LAST_NAME, L.HONORIFIC, L.TITLE AS LEAD_TITLE FROM {$fieldMultiTableName} FM INNER JOIN {$leadTableName} L ON FM.ELEMENT_ID = L.ID AND FM.ENTITY_ID = 'LEAD' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' AND (L.TITLE LIKE '{$needleSql}' OR L.NAME LIKE '{$needleSql}' OR L.LAST_NAME LIKE '{$needleSql}')";
		}
		if($top > 0)
		{
			$sql = $DB->TopSql($sql, $top);
		}

		$dbRes = $DB->Query(
			$sql
		);

		while($arRes = $dbRes->Fetch())
		{
			$result[] = CAllCrmActivity::ReadLeadCommunication($arRes, $communicationType);
		}

		//Search by VALUE
		$sql  = "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, L.NAME, L.SECOND_NAME, L.LAST_NAME, L.HONORIFIC, L.TITLE AS LEAD_TITLE FROM {$fieldMultiTableName} FM INNER JOIN {$leadTableName} L ON FM.ELEMENT_ID = L.ID AND FM.ENTITY_ID = 'LEAD' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' AND FM.VALUE LIKE '{$needleSql}'";
		if($top > 0)
		{
			$sql = $DB->TopSql($sql, $top);
		}

		$dbRes = $DB->Query(
			$sql
		);

		while($arRes = $dbRes->Fetch())
		{
			$result[] = CAllCrmActivity::ReadLeadCommunication($arRes, $communicationType);
		}

		return $result;
	}
	public static function GetCompanyCommunications($companyID, $communicationType)
	{
		global $DB;
		$companyID = intval($companyID);

		$fieldMultiTableName = CCrmActivity::FIELD_MULTI_TABLE_NAME;
		$contactTableName = CCrmContact::TABLE_NAME;
		$contactCompanyTableName = CCrmContact::COMPANY_TABLE_NAME;
		$companyTableName = CCrmCompany::TABLE_NAME;

		if ($communicationType !== '')
		{
			$sql = "SELECT
						FM.ELEMENT_ID,
						FM.VALUE_TYPE,
						FM.VALUE,
						C.NAME,
						C.SECOND_NAME,
						C.LAST_NAME,
						CO.TITLE COMPANY_TITLE
					FROM {$fieldMultiTableName} FM
					INNER JOIN {$contactTableName} C
						ON FM.ELEMENT_ID = C.ID AND FM.ENTITY_ID = 'CONTACT' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}'
					INNER JOIN {$contactCompanyTableName} CC
						ON C.ID = CC.CONTACT_ID AND CC.COMPANY_ID = {$companyID}
					INNER JOIN {$companyTableName} CO
						ON CC.COMPANY_ID = CO.ID";
		}
		else
		{
			$sql = "SELECT
						C.ID AS ELEMENT_ID,
						'' AS VALUE_TYPE,
						'' AS VALUE,
						C.NAME,
						C.SECOND_NAME,
						C.LAST_NAME,
						CO.TITLE COMPANY_TITLE
					FROM {$contactTableName} C
					INNER JOIN {$contactCompanyTableName} CC
						ON C.ID = CC.CONTACT_ID AND CC.COMPANY_ID = {$companyID}
					INNER JOIN {$companyTableName} CO
						ON CC.COMPANY_ID = CO.ID";
		}

		$dbRes = $DB->Query(
			$sql
		);

		$result = array();
		while($arRes = $dbRes->Fetch())
		{
			$result[] = array(
				'ENTITY_ID' => $arRes['ELEMENT_ID'],
				'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
				'ENTITY_TYPE' => CCrmOwnerType::ResolveName(CCrmOwnerType::Contact),
				'TYPE' => $communicationType,
				'VALUE' => $arRes['VALUE'],
				'VALUE_TYPE' => $arRes['VALUE_TYPE'],
				'ENTITY_SETTINGS' => array(
					'NAME' => $arRes['NAME'],
					'SECOND_NAME' => $arRes['SECOND_NAME'],
					'LAST_NAME' => $arRes['LAST_NAME'],
					'COMPANY_TITLE' => $arRes['COMPANY_TITLE']
				)
			);
		}
		return $result;
	}
	public static function GetStorageTypeID($ID)
	{
		$ID = intval($ID);
		$dbRes = CCrmActivity::GetList(array(), array('ID'=> $ID), false, false, array('STORAGE_TYPE_ID'));
		$arRes = $dbRes->Fetch();
		return is_array($arRes) && isset($arRes['STORAGE_TYPE_ID']) ? intval($arRes['STORAGE_TYPE_ID']) : StorageType::Undefined;
	}
	public static function GetDefaultStorageTypeID()
	{
		if(self::$STORAGE_TYPE_ID === StorageType::Undefined)
		{
			self::$STORAGE_TYPE_ID = intval(CUserOptions::GetOption('crm', 'activity_storage_type_id', StorageType::Undefined));
			if(self::$STORAGE_TYPE_ID === StorageType::Undefined
				|| !StorageType::isDefined(self::$STORAGE_TYPE_ID))
			{
				self::$STORAGE_TYPE_ID = StorageType::getDefaultTypeID();
			}
		}
		return self::$STORAGE_TYPE_ID;
	}
	public static function SetDefaultStorageTypeID($storageTypeID)
	{
		$storageTypeID = (int)$storageTypeID;

		if(!StorageType::isDefined($storageTypeID))
		{
			$storageTypeID = StorageType::getDefaultTypeID();
		}

		if($storageTypeID === self::$STORAGE_TYPE_ID)
		{
			return;
		}

		self::$STORAGE_TYPE_ID = $storageTypeID;
		CUserOptions::SetOption('crm', 'activity_storage_type_id', $storageTypeID);
	}
	public static function PrepareUrn(&$arFields)
	{
		if(!is_array($arFields))
		{
			return '';
		}

		$ID = isset($arFields['ID']) ? intval($arFields['ID']) : 0;
		if($ID <= 0)
		{
			return '';
		}

		// URN: [ID]-[CHECK_WORD]-[OWNER_ID]-[OWNER_TYPE_ID]
		$urn = "{$ID}-".randString(6, 'ABCDEFGHIJKLNMOPQRSTUVWXYZ0123456789');

		//$ownerID = isset($arFields['OWNER_ID']) ? intval($arFields['OWNER_ID']) : 0;
		//$ownerTypeID = isset($arFields['OWNER_TYPE_ID']) ? intval($arFields['OWNER_TYPE_ID']) : 0;
		//if($ownerID > 0 && $ownerTypeID > 0)
		//{
		//	$urn .= "-{$ownerID}-{$ownerTypeID}";
		//}

		return $urn;
	}
	public static function InjectUrnInMessage(&$messageData, $urn, $codeAllocation = false)
	{
		if(!is_array($messageData) || empty($messageData))
		{
			return false;
		}

		if($codeAllocation === false)
		{
			$codeAllocation = CCrmEMailCodeAllocation::GetCurrent();
		}

		if($codeAllocation === CCrmEMailCodeAllocation::Subject)
		{
			$messageData['SUBJECT'] = CCrmActivity::InjectUrnInSubject(
				$urn,
				isset($messageData['SUBJECT']) ? $messageData['SUBJECT'] : ''
			);

			return true;
		}
		elseif($codeAllocation === CCrmEMailCodeAllocation::Body)
		{
			$messageData['BODY'] = CCrmActivity::InjectUrnInBody(
				$urn,
				isset($messageData['BODY']) ? $messageData['BODY'] : '',
				isset($messageData['BODY_TYPE']) ? $messageData['BODY_TYPE'] : 'html'
			);
			return true;
		}

		return false;
	}
	public static function InjectUrnInSubject($urn, $str)
	{
		$urn = strval($urn);
		$str = strval($str);

		if($urn === '')
		{
			return $str;
		}

		if($str !== '')
		{
			$str = rtrim(preg_replace(self::$URN_REGEX, '', $str));
		}

		if($str === '')
		{
			return "[CRM:{$urn}]";
		}

		return "{$str} [CRM:{$urn}]";
	}
	public static function InjectUrnInBody($urn, $str, $type = 'html')
	{
		$urn = strval($urn);
		$str = strval($str);
		$type = mb_strtolower(strval($type));
		if($type === '')
		{
			$type = 'html';
		}

		if($urn === '')
		{
			return $str;
		}

		$slug = '[msg:'.mb_strtolower($urn).']';
		if($type === 'html')
		{
			//URN already encoded
			$str = rtrim(preg_replace(self::$URN_BODY_HTML_ENTITY_REGEX, '', $str));
			if($str !== '')
			{
				$index = mb_stripos($str, '</body>');
				if($index === false)
				{
					$index = mb_stripos($str, '</html>');
				}

				if($index === false)
				{
					$str .= '<br/>';
					$str .= $slug;
				}
				else
				{
					$str = mb_substr($str, 0, $index).'<br/>'.$slug.mb_substr($str, $index);
				}
			}

		}
		else
		{
			$str = rtrim(preg_replace(self::$URN_BODY_REGEX, '', $str));
			if($str !== '')
			{
				$str .= CCrmEMail::GetEOL();
				$str .= $slug;
			}

		}

		return $str;
	}
	public static function ExtractUrnFromMessage(&$messageData, $codeAllocation = false)
	{
		if(!is_array($messageData) || empty($messageData))
		{
			return '';
		}

		if($codeAllocation === false)
		{
			$codeAllocation = CCrmEMailCodeAllocation::GetCurrent();
		}

		$subject = isset($messageData['SUBJECT']) ? $messageData['SUBJECT'] : '';
		$body = isset($messageData['BODY']) ? $messageData['BODY'] : '';

		$result = '';
		if($codeAllocation === CCrmEMailCodeAllocation::Subject)
		{
			$result = CCrmActivity::ExtractUrnFromSubject($subject);
			if($result === '')
			{
				$result = CCrmActivity::ExtractUrnFromBody($body);
			}
		}
		elseif($codeAllocation === CCrmEMailCodeAllocation::Body)
		{
			$result = CCrmActivity::ExtractUrnFromBody($body);
			if($result === '')
			{
				$result = CCrmActivity::ExtractUrnFromSubject($subject);
			}
		}
		return $result;
	}
	public static function ExtractUrnFromSubject($str)
	{
		$str = strval($str);

		if($str === '')
		{
			return '';
		}

		$matches = array();
		if(preg_match(self::$URN_REGEX, $str, $matches) !== 1)
		{
			return '';
		}
		return isset($matches['urn']) ? $matches['urn'] : '';
	}
	public static function ExtractUrnFromBody($str)
	{
		$str = strval($str);

		if($str === '')
		{
			return '';
		}

		$matches = array();
		if(preg_match(self::$URN_BODY_REGEX, $str, $matches) !== 1)
		{
			return '';
		}
		return isset($matches['urn']) ? $matches['urn'] : '';
	}
	public static function ClearUrn($str)
	{
		$str = strval($str);

		if($str === '')
		{
			return $str;
		}

		return rtrim(preg_replace(self::$URN_REGEX, '', $str));
	}
	public static function ParseUrn($urn)
	{
		$urn = strval($urn);

		$result = array(
			'URN' => $urn,
			'ID' => 0,
			'CHECK_WORD' => ''
		);

		if($urn !== '')
		{
			$ary =  explode('-', $urn);
			if(count($ary) > 1)
			{
				$result['ID'] = intval($ary[0]);
				$result['CHECK_WORD'] = $ary[1];
			}
		}

		return $result;
	}
	public static function GetNearest($ownerTypeID, $ownerID, $userID)
	{
		global $DB;
		$userID = (int)$userID;

		if (\Bitrix\Main\Config\Option::get('crm', 'enable_any_incoming_act', 'Y') === 'Y')
		{
			$query = Crm\Activity\Entity\EntityUncompletedActivityTable::query()
				->where('ENTITY_TYPE_ID', (int)$ownerTypeID)
				->where('ENTITY_ID', (int)$ownerID)
				->setOrder(['MIN_DEADLINE' => 'ASC'])
				->setLimit(1)
				->setSelect(['ACTIVITY_ID', 'MIN_DEADLINE'])
			;
			if($userID > 0)
			{
				$query->where('RESPONSIBLE_ID', $userID);
			}
			if ($result = $query->fetch())
			{
				return [
					'ID' => $result['ACTIVITY_ID'],
					'DEADLINE' => $result['MIN_DEADLINE'] instanceof DateTime ? $result['MIN_DEADLINE']->toString() : false,
				];
			}

			return false;
		}

		$tableName = CCrmActivity::TABLE_NAME;
		$bindingTableName = CCrmActivity::BINDING_TABLE_NAME;
		$deadline = $DB->DateToCharFunction('a.DEADLINE', 'FULL');

		if($userID > 0)
		{
			$sql = "SELECT a.ID, {$deadline} AS DEADLINE_FORMATTED, a.DEADLINE FROM {$tableName} a INNER JOIN {$bindingTableName} b ON a.ID = b.ACTIVITY_ID AND a.COMPLETED = 'N' AND a.RESPONSIBLE_ID = {$userID} AND a.DEADLINE IS NOT NULL AND b.OWNER_TYPE_ID = {$ownerTypeID} AND b.OWNER_ID = {$ownerID} ORDER BY a.DEADLINE ASC";
		}
		else
		{
			$sql = "SELECT a.ID, {$deadline} AS DEADLINE_FORMATTED, a.DEADLINE FROM {$tableName} a INNER JOIN {$bindingTableName} b ON a.ID = b.ACTIVITY_ID AND a.COMPLETED = 'N' AND a.DEADLINE IS NOT NULL AND b.OWNER_TYPE_ID = {$ownerTypeID} AND b.OWNER_ID = {$ownerID} ORDER BY a.DEADLINE ASC";
		}

		$dbResult = $DB->Query(
			$DB->TopSql($sql, 1)
		);

		$arResult = $dbResult ? $dbResult->Fetch() : null;
		if($arResult)
		{
			$arResult['DEADLINE'] = $arResult['DEADLINE_FORMATTED'];
			unset($arResult['DEADLINE_FORMATTED']);
		}

		return $arResult;
	}
	public static function SynchronizeUserActivity($ownerTypeID, $ownerID, $userID)
	{
		$ownerTypeID = intval($ownerTypeID);
		$ownerID = intval($ownerID);
		$userID = intval($userID);

		if($userID < 0)
		{
			$userID = 0;
		}

		if($ownerTypeID <= CCrmOwnerType::Undefined || $ownerID <= 0)
		{
			return;
		}

		$arResult = CCrmActivity::GetNearest($ownerTypeID, $ownerID, $userID);
		if(is_array($arResult))
		{
			$activityID = isset($arResult['ID']) ? intval($arResult['ID']) : 0;
			$deadline = isset($arResult['DEADLINE']) ? $arResult['DEADLINE'] : '';
		}
		else
		{
			$activityID = 0;
			$deadline = '';
		}

		if($activityID > 0 && $deadline !== '' && CheckDateTime($deadline))
		{
			CCrmActivity::DoSaveNearestUserActivity(
				array(
					'USER_ID' => $userID,
					'OWNER_ID' => $ownerID,
					'OWNER_TYPE_ID' => $ownerTypeID,
					'ACTIVITY_ID' => $activityID,
					'ACTIVITY_TIME' => $deadline,
					'SORT' => ($userID > 0 ? '1' : '0').date('YmdHis', MakeTimeStamp($deadline) - CTimeZone::GetOffset())
				)
			);
		}
		else
		{
			global $DB;
			$tableName = CCrmActivity::USER_ACTIVITY_TABLE_NAME;
			$DB->Query(
				"DELETE FROM {$tableName} WHERE USER_ID = {$userID} AND OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID = {$ownerID}"
			);
		}

		$counter = new CCrmUserCounter($userID, CCrmUserCounter::CurrentActivies);
		$counter->Synchronize();
	}
	public static function MakeRawFiles($storageTypeID, array $arFileIDs)
	{
		return \Bitrix\Crm\Integration\StorageManager::makeFileArray($arFileIDs, $storageTypeID);
	}
	protected static function SaveCalendarEvent(&$arFields, $prevFields = [])
	{
		$responsibleID = (int)($arFields['RESPONSIBLE_ID'] ?? 0);
		$provider = self::GetActivityProvider($arFields);
		$providerTypeId = isset($arFields['PROVIDER_TYPE_ID']) ? (string) $arFields['PROVIDER_TYPE_ID'] : null;
		$completed = (($arFields['COMPLETED'] ?? null) === 'Y');

		if (
			$provider === null
			|| $responsibleID <= 0
			|| !\Bitrix\Main\Loader::includeModule('calendar')
		)
		{
			return false;
		}

		$enrichedDescription = $arFields['ENRICHED_DESCRIPTION'] ?? null;
		$description = $arFields['DESCRIPTION'] ?? '';
		$description = $enrichedDescription ?? $description;
		$prevEnrichedDescription = $arFields['PREV_ENRICHED_DESCRIPTION'] ?? null;

		$arCalEventFields = [
			'CAL_TYPE' => 'user',
			'OWNER_ID' => $responsibleID,
			'NAME' => $arFields['SUBJECT'] ?? '',
			'DATE_FROM' => $arFields['START_TIME'] ?? '',
			'DATE_TO' => $arFields['END_TIME'] ?? '',
			'IMPORTANCE' => CCrmActivityPriority::ToCalendarEventImportance(
				(int)($arFields['PRIORITY'] ?? CCrmActivityPriority::Low)
			),
			'DESCRIPTION' => $description,
			'SKIP_TIME' => false,
		];

		if (!empty($arFields['SECTION_ID']))
		{
			if (self::isCalendarSectionAvailable((int)$arFields['AUTHOR_ID'], (int)$arFields['SECTION_ID']))
			{
				$arCalEventFields['SECTION_ID'] = (int)$arFields['SECTION_ID'];
			}

			unset($arFields['SECTION_ID']);
		}
		elseif (!empty($arFields['SETTINGS']['CALENDAR_SECTION_ID']))
		{
			$arCalEventFields['SECTION_ID'] = (int)$arFields['SETTINGS']['CALENDAR_SECTION_ID'];
		}

		if (isset($arFields['ATTENDEES_CODES']))
		{
			if (is_array($arFields['ATTENDEES_CODES']))
			{
				$arCalEventFields['ATTENDEES_CODES'] = $arFields['ATTENDEES_CODES'];
			}
			unset($arFields['ATTENDEES_CODES']);
		}

		if (isset($arFields['UF_CRM_CAL_EVENT']))
		{
			if (is_array($arFields['UF_CRM_CAL_EVENT']))
			{
				$arCalEventFields['UF_CRM_CAL_EVENT'] = $arFields['UF_CRM_CAL_EVENT'];
			}
			unset($arFields['UF_CRM_CAL_EVENT']);
		}

		if (isset($arFields['IS_MEETING']))
		{
			$arCalEventFields['IS_MEETING'] = $arFields['IS_MEETING'];

			$meetingHostUserId = Container::getInstance()->getContext()->getUserId();
			$arCalEventFields['MEETING_HOST'] = $meetingHostUserId > 0 ? $meetingHostUserId : $responsibleID;

			if ($arCalEventFields['IS_MEETING'] === true)
			{
				$arCalEventFields['MEETING'] = [
					'NOTIFY' => true,
					'REINVITE' => false,
					'ALLOW_INVITE' => false,
					'MEETING_CREATOR' => $responsibleID,
					'HIDE_GUESTS' => true,
				];
			}

			unset($arFields['IS_MEETING']);
		}

		if (isset($arFields['LOCATION']))
		{
			$arCalEventFields['LOCATION'] = $arFields['LOCATION'];
			unset($arFields['LOCATION']);
		}

		if (isset($arFields['SETTINGS']['ADDRESS_FORMATTED']))
		{
			$arCalEventFields['LOCATION'] = $arFields['SETTINGS']['ADDRESS_FORMATTED'];
		}

		if (isset($arFields['SETTINGS']['COLOR']))
		{
			$colorSettingsProvider = new Crm\Activity\ToDo\ColorSettings\ColorSettingsProvider();
			$colorSettings =  $colorSettingsProvider->getByColorId($arFields['SETTINGS']['COLOR']);
			if ($colorSettings)
			{
				$arCalEventFields['COLOR'] = $colorSettings['color'];
			}
		}

		if (empty($prevFields) && !empty($arFields['PING_OFFSETS'])) // only for create new calendar event
		{
			$arCalEventFields['REMIND'] = [];
			foreach ($arFields['PING_OFFSETS'] as $offset)
			{
				if (!is_numeric($offset) || $offset < 0)
				{
					continue;
				}

				$arCalEventFields['REMIND'][] = [
					'type' => 'min',
					'count' => $offset,
				];
			}
		}

		//convert current user time to calendar owner time
		if ($userTzName = \CCalendar::GetUserTimezoneName($responsibleID, true))
		{
			$userTz = new DateTimeZone($userTzName);
			$format = \Bitrix\Main\Type\DateTime::getFormat();

			if (isset($arFields['START_TIME']))
			{
				$startTime = \Bitrix\Main\Type\DateTime::createFromUserTime($arFields['START_TIME']);
				$startTime->setTimeZone($userTz);
				$arCalEventFields['DATE_FROM'] = $startTime->format($format);
				$arCalEventFields['TZ_FROM'] = $userTzName;
			}
			if (isset($arFields['END_TIME']))
			{
				$endTime = \Bitrix\Main\Type\DateTime::createFromUserTime($arFields['END_TIME']);
				$endTime->setTimeZone($userTz);
				$arCalEventFields['DATE_TO'] = $endTime->format($format);
				$arCalEventFields['TZ_TO'] = $userTzName;
			}
		}

		$arCalEventFields['SECTIONS'] = [\CCalendar::GetCrmSection($responsibleID, true)];

		$calendarEventId = isset($arFields['CALENDAR_EVENT_ID']) ? (int)$arFields['CALENDAR_EVENT_ID'] : 0;

		if($calendarEventId > 0)
		{
			$arPresentEventFields = \Bitrix\Crm\Integration\Calendar::getEvent($calendarEventId);
			if(is_array($arPresentEventFields))
			{
				$presentResponsibleID = isset($arPresentEventFields['OWNER_ID']) ? (int)$arPresentEventFields['OWNER_ID'] : 0;
				if($presentResponsibleID === $responsibleID)
				{
					$arCalEventFields['ID'] = $calendarEventId;
				}

				if(!empty($arPresentEventFields['RRULE']))
				{
					$arCalEventFields['RRULE'] = CCalendarEvent::ParseRRULE($arPresentEventFields['RRULE']);
				}
			}

			if (
				!empty($prevEnrichedDescription)
				&& trim($description) !== trim($prevEnrichedDescription)
			)
			{
				$culture = \Bitrix\Main\Context::getCurrent()?->getCulture();
				$date = new DateTime();
				if ($culture)
				{
					$date->format($culture->getShortDateFormat() . ' ' . $culture->getShortTimeFormat());
				}
				$descriptionSubtitle = Loc::getMessage(
					'CRM_ACTIVITY_CALENDAR_SUBTITLE',
					['#DATE#' => $date]
				);
				$arCalEventFields['DESCRIPTION'] =
					$arPresentEventFields['DESCRIPTION']
					. PHP_EOL
					. PHP_EOL
					. $descriptionSubtitle
					. PHP_EOL
					. $arCalEventFields['DESCRIPTION']
				;
			}
		}
		if(isset($arFields['NOTIFY_TYPE']) && (int)$arFields['NOTIFY_TYPE'] !== CCrmActivityNotifyType::None)
		{
			$arCalEventFields['REMIND'] = [
				[
					'type' => CCrmActivityNotifyType::ToCalendarEventRemind($arFields['NOTIFY_TYPE']),
					'count' => isset($arFields['NOTIFY_VALUE']) ? (int)$arFields['NOTIFY_VALUE'] : 15
				]
			];
		}

		// Clear notification for completed activity
		if ($completed)
		{
			$arCalEventFields['REMIND'] = [];
		}

		self::$IGNORE_CALENDAR_EVENTS = true;

		$result = \CCalendar::SaveEvent([
			'arFields' => $arCalEventFields,
			'userId' => $responsibleID,
			'autoDetectSection' => true,
			'autoCreateSection' => true
		]);

		$ownerID = (int)$arFields['OWNER_ID'];
		$ownerTypeID = (int)$arFields['OWNER_TYPE_ID'];
		$arBindings = $arFields['BINDINGS'] ?? [];
		if(empty($arBindings) && $ownerID > 0 && $ownerTypeID > 0)
		{
			$arBindings[] = [
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerID
			];
		}

		$entityID = (int)$result;
		if($entityID > 0)
		{
			if(!empty($arBindings))
			{
				$arUserFields = $arCalEventFields['UF_CRM_CAL_EVENT'] ?? [];
				foreach($arBindings as &$arBinding)
				{
					$entityTypeName = CCrmOwnerType::ResolveName($arBinding['OWNER_TYPE_ID']);
					if (Crm\UserField\UserFieldManager::isEnabledInCalendarUserField($entityTypeName))
					{
						$arUserFields[] = Crm\UserField\Types\ElementType::getValueByIdentifier(
							new Crm\ItemIdentifier(
								$arBinding['OWNER_TYPE_ID'],
								$arBinding['OWNER_ID'],
							)
						);
					}
				}
				unset($arBinding);

				CCalendarEvent::UpdateUserFields(
					$entityID,
					array('UF_CRM_CAL_EVENT' => $arUserFields)
				);
			}

			if ($calendarEventId > 0 && $calendarEventId !== $entityID)
			{
				if ($provider::canKeepReassignedInCalendar($providerTypeId))
				{
					/* TODO: remove bindings?
					CCalendarEvent::UpdateUserFields($entityID, array('UF_CRM_CAL_EVENT' => null));
					*/
				}
				else
				{
					CCalendarEvent::Delete(array('id' => $calendarEventId, 'bMarkDeleted' => true));
				}
			}
		}
		self::$IGNORE_CALENDAR_EVENTS = false;
		return $result;
	}

	protected static function isCalendarSectionAvailable(int $userId, int $sectionId): bool
	{
		$availableSections = \Bitrix\Crm\Integration\Calendar::getSectionListAvailableForUser($userId);
		foreach ($availableSections as $section)
		{
			if ((int)$section['ID'] === $sectionId)
			{
				return true;
			}
		}

		return false;
	}

	public static function SetCalendarEventId($eventId, $activityId)
	{
		global $DB;

		$eventId = (int) $eventId;
		$activityId = (int) $activityId;

		$toUpdate = array('CALENDAR_EVENT_ID' => $eventId);

		$DB->Query(
			'UPDATE '.CCrmActivity::TABLE_NAME.' SET '.$DB->PrepareUpdate(CCrmActivity::TABLE_NAME, $toUpdate).' WHERE ID = '.$activityId
		);

		return true;
	}

	protected static function DeleteCalendarEvent(&$arFields)
	{
		$provider = self::GetActivityProvider($arFields);
		$providerTypeId = isset($arFields['PROVIDER_TYPE_ID']) ? (string) $arFields['PROVIDER_TYPE_ID'] : null;

		if ($provider === null  || !CModule::IncludeModule('calendar'))
		{
			return false;
		}

		$calendarEventId = isset($arFields['CALENDAR_EVENT_ID']) ? (int)$arFields['CALENDAR_EVENT_ID'] : 0;

		if ($calendarEventId <= 0)
		{
			return false;
		}

		self::$IGNORE_CALENDAR_EVENTS = true;
		CCalendarEvent::Delete(array('id' => $calendarEventId, 'bMarkDeleted' => true));
		self::$IGNORE_CALENDAR_EVENTS = false;
		return true;
	}

	/**
	 * @deprecated use \Bitrix\Crm\Activity\Provider\Task::updateAssociatedEntity
	 * @param array $arFields Activity data.
	 * @return \Bitrix\Main\Result|bool
	 */
	protected static function SaveTask(&$arFields)
	{
		$typeID = self::GetActivityType($arFields);
		$associatedEntityID = isset($arFields['ASSOCIATED_ENTITY_ID']) ? (int)$arFields['ASSOCIATED_ENTITY_ID'] : 0;

		if ($typeID !== CCrmActivityType::Task || $associatedEntityID <= 0)
			return false;

		//compatible
		$result = \Bitrix\Crm\Activity\Provider\Task::updateAssociatedEntity($associatedEntityID, $arFields);
		return $result->isSuccess();
	}

	/**
	 * @deprecated
	 * @param array $arFields Activity data.
	 * @return bool
	 */
	protected static function DeleteTask(&$arFields)
	{
		$typeID = self::GetActivityType($arFields);
		$associatedEntityID =  isset($arFields['ASSOCIATED_ENTITY_ID']) ? (int)$arFields['ASSOCIATED_ENTITY_ID'] : 0;
		if($typeID !== CCrmActivityType::Task || $associatedEntityID <= 0)
		{
			return false;
		}

		//compatible
		$result = \Bitrix\Crm\Activity\Provider\Task::deleteAssociatedEntity($associatedEntityID, $arFields);
		return $result->isSuccess();
	}
	public static function RefreshCalendarBindings()
	{
		if (!(IsModuleInstalled('calendar') && CModule::IncludeModule('calendar')))
		{
			return false;
		}

		global $DB;
		$dbResult = $DB->Query(
			'SELECT OWNER_ID, OWNER_TYPE_ID, ASSOCIATED_ENTITY_ID FROM '.CCrmActivity::TABLE_NAME.' WHERE OWNER_ID > 0 AND OWNER_TYPE_ID > 0 AND ASSOCIATED_ENTITY_ID > 0 AND TYPE_ID IN ('.CCrmActivityType::Call.', '.CCrmActivityType::Meeting.')'
		);

		if(!$dbResult)
		{
			return false;
		}

		while($arResult = $dbResult->Fetch())
		{
			$ownerID = intval($arResult['OWNER_ID']);
			$ownerTypeID = intval($arResult['OWNER_TYPE_ID']);
			$assocEntityID = intval($arResult['ASSOCIATED_ENTITY_ID']);

			if($ownerID > 0 && $ownerTypeID > 0 && $assocEntityID > 0)
			{
				CCalendarEvent::UpdateUserFields(
					$assocEntityID,
					[
						'UF_CRM_CAL_EVENT' => [
							Crm\UserField\Types\ElementType::getValueByIdentifier(new Crm\ItemIdentifier(
								$ownerTypeID,
								$ownerID,
							)),
						]
					]
				);
			}
		}

		return true;
	}

	public static function NotifyMulti($userId, $schemeTypeID, $count, $tag = '')
	{
		if($userId <= 0)
		{
			return false;
		}

		if($schemeTypeID === CCrmNotifierSchemeType::IncomingEmail)
		{
			//This path opens correctly both in the web and in the mobile version
			$url = '/crm/deal/details/';
			$uri = new Uri($url);
			$absoluteUrl = $uri->toAbsolute()->getLocator();

			if ($tag == '')
			{
				$tag = sprintf('crm_email_%s', md5($url));
			}

			$getMessageCallback = static function (string $url) use ($count) {
				$code = 'CRM_ACTIVITY_NOTIFY_MESSAGE_INCOMING_MULTI';
				$replace = [
					'#COUNT#' => $count,
					'#VIEW_URL#' => $url,
				];

				return static fn (?string $languageId = null) =>
					Loc::getMessage($code, $replace, $languageId)
				;
			};

			return CCrmNotifier::Notify(
				$userId,
				$getMessageCallback($url),
				$getMessageCallback($absoluteUrl),
				$schemeTypeID,
				$tag
			);
		}

		return false;
	}

	public static function Notify($fields, $schemeTypeID, $tag = '', $isForced = false, $doNotInformUsers = [])
	{
		if(!is_array($fields))
		{
			return false;
		}

		$responsibleID = $fields['RESPONSIBLE_ID'] ? (int) $fields['RESPONSIBLE_ID'] : 0;

		if($responsibleID <= 0)
		{
			return false;
		}

		if (in_array($responsibleID, $doNotInformUsers))
		{
			return false;
		}

		if ($isForced)
		{
			global $USER;
			if ($responsibleID == $USER?->getId())
			{
				return false;
			};
		}

		if($schemeTypeID === CCrmNotifierSchemeType::IncomingEmail)
		{
			$communicationEntityTypeId = $fields['OWNER_TYPE_ID'] ? intval($fields['OWNER_TYPE_ID']) : 0;
			$communicationEntityId = $fields['OWNER_ID'] ? intval($fields['OWNER_ID']) : 0;

			$url = htmlspecialcharsbx(
				CCrmOwnerType::GetEntityShowPath(
					$communicationEntityTypeId,
					$communicationEntityId
				)
			);

			$entityTitle = self::GetCommunicationTitle([
				'ENTITY_TYPE_ID' => $communicationEntityTypeId,
				'ENTITY_ID' => $communicationEntityId,
			],
				true
			);

			if($url === '')
			{
				return false;
			}

			$uri = new Uri($url);
			$absoluteUrl = $uri->toAbsolute()->getLocator();

			if ($tag == '')
			{
				$tag = sprintf('crm_email_%s', md5($url));
			}

			$getMessageCallback = static function (string $url) use (
				$entityTitle,
				$fields,
			){
				if ($fields['SUBJECT'])
				{
					$code = 'CRM_ACTIVITY_NOTIFY_MESSAGE_INCOMING_1';
					$replace = [
						'#ENTITY_TITLE#' => $entityTitle,
						'#SUBJECT#' => $fields['SUBJECT'],
					];
				}
				else
				{
					$code = 'CRM_ACTIVITY_NOTIFY_MESSAGE_INCOMING_EMPTY_SUBJECT_1';
					$replace = [
						'#ENTITY_TITLE#' => $entityTitle,
					];
				}

				$replace['#VIEW_URL#'] = $url;

				return static fn (?string $languageId = null) =>
					Loc::getMessage($code, $replace, $languageId)
				;
			};

			return CCrmNotifier::Notify(
				$responsibleID,
				$getMessageCallback($url),
				$getMessageCallback($absoluteUrl),
				$schemeTypeID,
				$tag
			);
		}

		return false;
	}

	public static function PrepareJoin($userID, $ownerTypeID, $ownerAlias, $alias = '', $userAlias = '', $respAlias = '')
	{
		$userID = intval($userID);
		$ownerTypeID = intval($ownerTypeID);
		$ownerAlias = strval($ownerAlias);
		if($ownerAlias === '')
		{
			$ownerAlias = 'L';
		}

		$alias = strval($alias);
		if($alias === '')
		{
			$alias = 'A';
		}

		$userAlias = strval($userAlias);
		if($userAlias === '')
		{
			$userAlias = 'UA';
		}

		$respAlias = strval($respAlias);

		// Zero user is intended for nearest activity in general.
		$userTableName = CCrmActivity::USER_ACTIVITY_TABLE_NAME;
		$activityTableName = CCrmActivity::TABLE_NAME;
		if($respAlias !== '')
		{
			return "LEFT JOIN {$userTableName} {$userAlias} ON {$userAlias}.USER_ID = {$userID} AND {$userAlias}.OWNER_ID = {$ownerAlias}.ID AND {$userAlias}.OWNER_TYPE_ID = {$ownerTypeID} LEFT JOIN {$activityTableName} {$alias} ON {$alias}.ID = {$userAlias}.ACTIVITY_ID LEFT JOIN b_user {$respAlias} ON {$alias}.RESPONSIBLE_ID = {$respAlias}.ID";
		}
		else
		{
			return "LEFT JOIN {$userTableName} {$userAlias} ON {$userAlias}.USER_ID = {$userID} AND {$userAlias}.OWNER_ID = {$ownerAlias}.ID AND {$userAlias}.OWNER_TYPE_ID = {$ownerTypeID} LEFT JOIN {$activityTableName} {$alias} ON {$alias}.ID = {$userAlias}.ACTIVITY_ID";
		}
	}
	public static function IsCurrentDay($time)
	{
		if(self::$CURRENT_DAY_TIME_STAMP === null || self::$NEXT_DAY_TIME_STAMP === null)
		{
			$t = time() + CTimeZone::GetOffset();
			self::$CURRENT_DAY_TIME_STAMP = mktime(0, 0, 0, date('n', $t), date('j', $t), date('Y', $t));
			$t += 86400;
			self::$NEXT_DAY_TIME_STAMP = mktime(0, 0, 0, date('n', $t), date('j', $t), date('Y', $t));
		}

		return $time >= self::$CURRENT_DAY_TIME_STAMP && $time < self::$NEXT_DAY_TIME_STAMP;
	}
	public static function GetCurrentQuantity($userID, $ownerTypeID)
	{
		$userID = intval($userID);
		$ownerTypeID = intval($ownerTypeID);
		if($userID <= 0 || $ownerTypeID <= 0)
		{
			return 0;
		}

		$currentDay = time() + CTimeZone::GetOffset();
		$currentDayEnd = ConvertTimeStamp(mktime(23, 59, 59, date('n', $currentDay), date('j', $currentDay), date('Y', $currentDay)), 'FULL', SITE_ID);

		global $DB;
		$currentDayEnd = $DB->CharToDateFunction($DB->ForSql($currentDayEnd), 'FULL');
		$activityTable = CCrmActivity::USER_ACTIVITY_TABLE_NAME;
		$sql = "SELECT COUNT(DISTINCT a.OWNER_ID) AS CNT FROM {$activityTable} a WHERE a.USER_ID = {$userID} AND a.OWNER_TYPE_ID = {$ownerTypeID} AND a.ACTIVITY_TIME <= {$currentDayEnd}";

		$dbResult = $DB->Query(
			$sql
		);
		$result = $dbResult->Fetch();
		return is_array($result) ? intval($result['CNT']) : 0;
	}
	public static function GetDefaultCommunicationValue($ownerTypeID, $ownerID, $commType)
	{
		$dbMultiFields = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => CCrmOwnerType::ResolveName($ownerTypeID), 'ELEMENT_ID' => $ownerID, 'TYPE_ID' =>  $commType)
		);

		$multiField = $dbMultiFields->Fetch();
		return is_array($multiField) ? $multiField['VALUE'] : '';
	}
	private static function RegisterLiveFeedEvent(&$arFields)
	{
		static $contextUserId = false;

		$ID = isset($arFields['ID']) ? intval($arFields['ID']) : 0;
		if($ID <= 0)
		{
			$arFields['ERROR'] = 'Could not find activity ID.';
			return false;
		}

		$ownerTypeID = isset($arFields['OWNER_TYPE_ID']) ? intval($arFields['OWNER_TYPE_ID']) : CCrmOwnerType::Undefined;
		if(!CCrmOwnerType::IsDefined($ownerTypeID))
		{
			$arFields['ERROR'] = 'Could not find owner type ID.';
			return false;
		}

		$ownerID = isset($arFields['OWNER_ID']) ? intval($arFields['OWNER_ID']) : 0;
		if($ownerID <= 0)
		{
			$arFields['ERROR'] = 'Could not find owner ID.';
			return false;
		}

		$arOwners = array(
			array(
				"OWNER_TYPE_ID" => $ownerTypeID,
				"OWNER_ID" => $ownerID
			)
		);

		$authorID = isset($arFields['AUTHOR_ID']) ? intval($arFields['AUTHOR_ID']) : 0;
		$editorID = isset($arFields['EDITOR_ID']) ? intval($arFields['EDITOR_ID']) : 0;
		$userID = $authorID > 0 ? $authorID : $editorID;
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		// Params are not assigned - we will use current activity only.
		$liveFeedFields = array(
			'ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
			'ENTITY_ID' => $ID,
			'USER_ID' => $userID,
			'MESSAGE' => '',
			'TITLE' => '',
			'LOG_RIGHTS' => (!empty($arFields["RESPONSIBLE_ID"]) && intval($arFields["RESPONSIBLE_ID"]) > 0 ? array('U'.$arFields["RESPONSIBLE_ID"]) : false)
		);

		$bindings = isset($arFields['BINDINGS']) && is_array($arFields['BINDINGS']) ? $arFields['BINDINGS'] : array();
		if(!empty($bindings))
		{
			$liveFeedFields['PARENTS'] = $bindings;
			$liveFeedFields['PARENT_OPTIONS'] = array(
				'ENTITY_TYPE_ID_KEY' => 'OWNER_TYPE_ID',
				'ENTITY_ID_KEY' => 'OWNER_ID'
			);

			$ownerInfoOptions = array(
				'ENTITY_TYPE_ID_KEY' => 'OWNER_TYPE_ID',
				'ENTITY_ID_KEY' => 'OWNER_ID',
				'ADDITIONAL_DATA' => array('LEVEL' => 2)
			);

			$additionalParents = array();
			foreach($bindings as &$binding)
			{
				$ownerTypeID = isset($binding['OWNER_TYPE_ID']) ? intval($binding['OWNER_TYPE_ID']) : CCrmOwnerType::Undefined;
				$ownerID = isset($binding['OWNER_ID']) ? intval($binding['OWNER_ID']) : 0;

				if (
					$ownerTypeID != CCrmOwnerType::Undefined
					&& $ownerID > 0
				)
				{
					$arOwners[] = array(
						"OWNER_TYPE_ID" => $ownerTypeID,
						"OWNER_ID" => $ownerID
					);
				}

				if(
					$ownerTypeID === CCrmOwnerType::Contact
					&& $ownerID > 0
				)
				{
					$owners = array();
					if(CCrmOwnerType::TryGetOwnerInfos(CCrmOwnerType::Contact, $ownerID, $owners, $ownerInfoOptions))
					{
						$additionalParents = array_merge($additionalParents, $owners);
					}
				}
			}
			unset($binding);
			if(!empty($additionalParents))
			{
				$liveFeedFields['PARENTS'] = array_merge($bindings, $additionalParents);
			}

		}

		self::PrepareStorageElementIDs($arFields);
		$arStorageElementID = $arFields["STORAGE_ELEMENT_IDS"];
		if (!empty($arStorageElementID))
		{
			if ($arFields["STORAGE_TYPE_ID"] == StorageType::WebDav)
			{
				$liveFeedFields["UF_SONET_LOG_DOC"] = $arStorageElementID;
			}
			else if ($arFields["STORAGE_TYPE_ID"] == StorageType::Disk)
			{
				$liveFeedFields["UF_SONET_LOG_DOC"] = array();
				//We have to add prefix Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX to file ID
				foreach($arStorageElementID as $elementID)
				{
					$liveFeedFields["UF_SONET_LOG_DOC"][] = "n{$elementID}";
				}
			}
			else
			{
				$liveFeedFields["UF_SONET_LOG_FILE"] = $arStorageElementID;
			}
		}

		if ($arFields["TYPE_ID"] == CCrmActivityType::Email)
		{
			if ($contextUserId === false)
			{
				$res = \Bitrix\Main\UserGroupTable::getList(array(
					'order' => array(
						'USER_ID' => 'ASC'
					),
					'filter' => array(
						'GROUP_ID' => 1,
						'=USER.ACTIVE' => 'Y'
					),
					'select' => array('USER_ID'),
					'limit' => 1
				));
				if ($userGroupFields = $res->fetch())
				{
					// hack: for UF CheckFields(), agent call
					$contextUserId = $liveFeedFields['CONTEXT_USER_ID'] = $userGroupFields['USER_ID'];
				}
			}
			else
			{
				$liveFeedFields['CONTEXT_USER_ID'] = $contextUserId;
			}
		}

		$eventID = 0;
		$associatedEntityId = isset($arFields['ASSOCIATED_ENTITY_ID']) ? (int)$arFields['ASSOCIATED_ENTITY_ID'] : 0;
		$provider = self::GetActivityProvider($arFields);
		if ($provider !== null && Settings\Crm::isLiveFeedRecordsGenerationEnabled())
		{
			$eventID = $provider::createLiveFeedLog($associatedEntityId, $arFields, $liveFeedFields);
		}

		if ($eventID === 0)
		{
			$arOptions = [];
			if (isset($arFields['PROVIDER_ID']))
			{
				$arOptions['ACTIVITY_PROVIDER_ID'] = $arFields['PROVIDER_ID'];
			}
			$eventID = CCrmLiveFeed::CreateLogEvent($liveFeedFields, CCrmLiveFeedEvent::Add, $arOptions);
		}

		if ($eventID === false && isset($liveFeedFields['ERROR']))
		{
			$arFields['ERROR'] = $liveFeedFields['ERROR'];
		}
		else
		{
			if (
				intval($arFields["RESPONSIBLE_ID"]) > 0
				&& $arFields["RESPONSIBLE_ID"] != $userID
				&& CModule::IncludeModule("im")
			)
			{
				$bHasPermissions = false;
				$perms = CCrmPerms::GetUserPermissions($arFields["RESPONSIBLE_ID"]);
				foreach ($arOwners as $arOwner)
				{
					if (CCrmActivity::CheckReadPermission($arOwner["OWNER_TYPE_ID"], $arOwner["OWNER_ID"], $perms))
					{
						$bHasPermissions = true;
						break;
					}
				}

				switch ($arFields['TYPE_ID'])
				{
					case CCrmActivityType::Call:
						$type = 'CALL';
						break;
					case CCrmActivityType::Meeting:
						$type = 'MEETING';
						break;
					default:
						$type = false;
				}

				if ($type)
				{
					$url = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Activity, $ID);
					$absoluteUrl = CCrmUrlUtil::ToAbsoluteUrl($url);

					$notifyMessage = static fn (?string $languageId = null) =>
						Loc::getMessage(
							"CRM_ACTIVITY_{$type}_RESPONSIBLE_IM_NOTIFY",
							[ "#title#" => '<a href="' . $url . '">' . htmlspecialcharsbx($arFields['SUBJECT']) . '</a>' ],
							$languageId,
						)
					;

					$notifyMessageOut = static function (?string $languageId = null) use ($arFields, $type, $absoluteUrl) {
						$message = Loc::getMessage(
							"CRM_ACTIVITY_{$type}_RESPONSIBLE_IM_NOTIFY",
							[ "#title#" => htmlspecialcharsbx($arFields['SUBJECT']) ],
							$languageId,
						);

						return "{$message} ({$absoluteUrl})";
					};

					$arMessageFields = array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"TO_USER_ID" => $arFields["RESPONSIBLE_ID"],
						"FROM_USER_ID" => $userID,
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "crm",
						"LOG_ID" => $eventID,
						"NOTIFY_EVENT" => "changeAssignedBy",
						"NOTIFY_TAG" => "CRM|ACTIVITY|".$ID,
						"NOTIFY_MESSAGE" => $notifyMessage,
						"NOTIFY_MESSAGE_OUT" => $notifyMessageOut,
					);

					if(!$bHasPermissions)
					{
						//TODO: Add  message 'Need for permissions'
						$message = static fn (?string $languageId = null) =>
							Loc::getMessage(
								"CRM_ACTIVITY_{$type}_RESPONSIBLE_IM_NOTIFY",
								[ "#title#" => htmlspecialcharsbx($arFields['SUBJECT']) ],
								$languageId,
							)
						;

						$arMessageFields["NOTIFY_MESSAGE"] = $message;
						$arMessageFields["NOTIFY_MESSAGE_OUT"] = $message;
					}

					CIMNotify::Add($arMessageFields);
				}
			}
		}

		return $eventID;
	}
	private static function SynchronizeLiveFeedEvent($activityID, $params)
	{
		if (\Bitrix\Crm\DbHelper::isPgSqlDb())
		{
			return true;
		}

		if (!Crm\Integration\Socialnetwork\Livefeed\AvailabilityHelper::isAvailable())
		{
			return false;
		}

		if(!is_array($params))
		{
			$params = array();
		}

		$processBindings = isset($params['PROCESS_BINDINGS']) ? (bool)$params['PROCESS_BINDINGS'] : false;
		$bindings = isset($params['BINDINGS']) && is_array($params['BINDINGS']) ? $params['BINDINGS'] : array();
		$hasBindings = !empty($bindings);
		if($processBindings)
		{
			CCrmSonetRelation::UnRegisterRelationsByEntity(CCrmOwnerType::Activity, $activityID, array('QUICK' => $hasBindings));
		}

		$arOwners = ($hasBindings ? $bindings : self::GetBindings($activityID));

		$slEntities = CCrmLiveFeed::GetLogEvents(
			array(),
			array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ENTITY_ID' => $activityID
			),
			array('ID', 'EVENT_ID')
		);

		if(empty($slEntities))
		{
			return false;
		}

		global $DB;
		foreach($slEntities as &$slEntity)
		{
			$slID = intval($slEntity['ID']);
			$slEventType = $slEntity['EVENT_ID'];

			if(isset($params['REFRESH_DATE']) ? (bool)$params['REFRESH_DATE'] : false)
			{
				//Update LOG_UPDATE for force event to rise in global feed
				//Update LOG_DATE for force event to rise in entity feed
				CCrmLiveFeed::UpdateLogEvent(
					$slID,
					array(
						'=LOG_UPDATE' => $DB->CurrentTimeFunction(),
						'=LOG_DATE' => $DB->CurrentTimeFunction(),
						'LOG_RIGHTS' => (intval($params['START_RESPONSIBLE_ID']) != intval($params['FINAL_RESPONSIBLE_ID']) ? array('U'.$params['FINAL_RESPONSIBLE_ID']) : false)
					)
				);
			}
			else
			{
				//HACK: FAKE UPDATE FOR INVALIDATE CACHE
				CCrmLiveFeed::UpdateLogEvent(
					$slID,
					array(
						'ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
						'ENTITY_ID' => $activityID,
						'LOG_RIGHTS' => (intval($params['START_RESPONSIBLE_ID']) != intval($params['FINAL_RESPONSIBLE_ID']) ? array('U'.$params['FINAL_RESPONSIBLE_ID']) : false)
					)
				);
			}

			$userID = (intval($params['EDITOR_ID']) > 0 ? $params['EDITOR_ID'] : CCrmSecurityHelper::GetCurrentUserID());
			if (
				intval($params['START_RESPONSIBLE_ID']) != intval($params['FINAL_RESPONSIBLE_ID'])
				&& CModule::IncludeModule("im")
			)
			{
				switch ($params['TYPE_ID'])
				{
					case CCrmActivityType::Call:
						$type = 'CALL';
						break;
					case CCrmActivityType::Meeting:
						$type = 'MEETING';
						break;
					default:
						$type = false;
				}

				if ($type)
				{
					$url = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Activity, $activityID);
					$absoluteUrl = CCrmUrlUtil::ToAbsoluteUrl($url);

					$arMessageFields = array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"FROM_USER_ID" => $userID,
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "crm",
						"LOG_ID" => $slID,
						//"NOTIFY_EVENT" => "activity_add",
						"NOTIFY_EVENT" => "changeAssignedBy",
						"NOTIFY_TAG" => "CRM|ACTIVITY|".$activityID
					);

					if (intval($params['START_RESPONSIBLE_ID']) != $userID)
					{
						$bHasPermissions = false;
						$perms = CCrmPerms::GetUserPermissions($params['START_RESPONSIBLE_ID']);
						foreach ($arOwners as $arOwner)
						{
							if (CCrmActivity::CheckReadPermission($arOwner["OWNER_TYPE_ID"], $arOwner["OWNER_ID"], $perms))
							{
								$bHasPermissions = true;
								break;
							}
						}

						if ($bHasPermissions)
						{
							$arMessageFields["TO_USER_ID"] = $params['START_RESPONSIBLE_ID'];
							$arMessageFields["NOTIFY_MESSAGE"] = static fn (?string $languageId = null) =>
								Loc::getMessage(
									"CRM_ACTIVITY_{$type}_NOT_RESPONSIBLE_IM_NOTIFY",
									[ "#title#" => '<a href="' . $url . '">' . htmlspecialcharsbx($params['SUBJECT']) . '</a>' ],
									$languageId,
								)
							;

							$arMessageFields["NOTIFY_MESSAGE_OUT"] = static function (?string $languageId = null) use ($type, $params, $absoluteUrl) {
								$message = Loc::getMessage(
									"CRM_ACTIVITY_{$type}_NOT_RESPONSIBLE_IM_NOTIFY",
									[ "#title#" => htmlspecialcharsbx($params['SUBJECT']) ],
									$languageId,
								);

								return "{$message} ({$absoluteUrl})";
							};

							CIMNotify::Add($arMessageFields);
						}
					}

					if (intval($params['FINAL_RESPONSIBLE_ID']) != $userID)
					{
						$bHasPermissions = false;
						$perms = CCrmPerms::GetUserPermissions($params['FINAL_RESPONSIBLE_ID']);
						foreach ($arOwners as $arOwner)
						{
							if (CCrmActivity::CheckReadPermission($arOwner["OWNER_TYPE_ID"], $arOwner["OWNER_ID"], $perms))
							{
								$bHasPermissions = true;
								break;
							}
						}

						$arMessageFields["TO_USER_ID"] = $params['FINAL_RESPONSIBLE_ID'];
						if ($bHasPermissions)
						{
							$arMessageFields["NOTIFY_MESSAGE"] = static fn (?string $languageId = null) =>
								Loc::getMessage(
									"CRM_ACTIVITY_{$type}_RESPONSIBLE_IM_NOTIFY",
									[ "#title#" => '<a href="' . $url . '">' . htmlspecialcharsbx($params['SUBJECT']) . '</a>' ],
									$languageId,
								)
							;
							$arMessageFields["NOTIFY_MESSAGE_OUT"] = static function (?string $languageId = null) use ($type, $params, $absoluteUrl){
								$message = Loc::getMessage(
									"CRM_ACTIVITY_{$type}_RESPONSIBLE_IM_NOTIFY",
									[ "#title#" => htmlspecialcharsbx($params['SUBJECT']) ],
									$languageId,
								);

								return "{$message} ({$absoluteUrl})";
							};
						}
						else
						{
							//TODO: Add  message 'Need for permissions'
							$message = static fn (?string $languageId = null) =>
								Loc::getMessage(
									"CRM_ACTIVITY_{$type}_RESPONSIBLE_IM_NOTIFY",
									[ "#title#" => htmlspecialcharsbx($params['SUBJECT']) ],
									$languageId,
								)
							;

							$arMessageFields["NOTIFY_MESSAGE"] = $message;
							$arMessageFields["NOTIFY_MESSAGE_OUT"] = $message;
						}

						CIMNotify::Add($arMessageFields);
					}
				}
			}

			if ($processBindings && $hasBindings && Settings\Crm::isLiveFeedRecordsGenerationEnabled())
			{
				CCrmSonetRelation::RegisterRelationBundle(
					$slID,
					$slEventType,
					CCrmOwnerType::Activity,
					$activityID,
					$bindings,
					array(
						'ENTITY_TYPE_ID_KEY' => 'OWNER_TYPE_ID',
						'ENTITY_ID_KEY' => 'OWNER_ID',
						'TYPE_ID' => CCrmSonetRelationType::Ownership
					)
				);
			}
		}
		unset($slEntity);
		return true;
	}
	private static function UnregisterLiveFeedEvent($activityID)
	{
		if (\Bitrix\Crm\DbHelper::isPgSqlDb())
		{
			return;
		}

		$slEntities = CCrmLiveFeed::GetLogEvents(
			array(),
			array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ENTITY_ID' => $activityID
			),
			array('ID')
		);

		$options = array('UNREGISTER_RELATION' => false);
		foreach($slEntities as &$slEntity)
		{
			CCrmLiveFeed::DeleteLogEvent($slEntity['ID'], $options);
		}
		unset($slEntity);
		CCrmSonetRelation::UnRegisterRelationsByEntity(CCrmOwnerType::Activity, $activityID);
	}
	public static function OnBeforeIntantMessangerChatAdd(\Bitrix\Main\Entity\Event $event)
	{
		$result = new \Bitrix\Main\Entity\EventResult();

		$fields = $event->getParameter('fields');
		$entityType = isset($fields['ENTITY_TYPE']) ? $fields['ENTITY_TYPE'] : '';
		$m = null;
		if(preg_match('/^CRM_([A-Z]+)$/i', $entityType, $m) === 1)
		{
			$entityTypeName = isset($m[1]) ? $m[1] : '';
			$ownerTypeID = CCrmOwnerType::ResolveID($entityTypeName);
			$ownerID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;
			$ownerInfo = null;
			if(CCrmOwnerType::IsDefined($ownerTypeID)
				&& $ownerID > 0
				&& CCrmOwnerType::TryGetInfo($ownerTypeID, $ownerID, $ownerInfo, false))
			{
				$changedFields['TITLE'] = $ownerInfo['CAPTION'];
				$changedFields['AVATAR'] = $ownerInfo['IMAGE_ID'];
				$result->modifyFields($changedFields);
			}
		}
		return $result;
	}
	protected static function GetMaxDbDate()
	{
		return '';
	}
	public static function AddEmailSignature(&$message, $contentType = 0)
	{
		return Bitrix\Crm\Integration\Bitrix24Email::addSignature($message, $contentType);
	}
	public static function LoadElementIDs($ID)
	{
		$ID = (int)$ID;
		if($ID <= 0)
		{
			return array();
		}

		global $DB;
		$result = array();
		$table = CCrmActivity::ELEMENT_TABLE_NAME;
		$dbResult = $DB->Query("SELECT ELEMENT_ID FROM {$table} WHERE ACTIVITY_ID = {$ID}");
		while($arResult = $dbResult->Fetch())
		{
			$elementID = isset($arResult['ELEMENT_ID']) ? (int)$arResult['ELEMENT_ID'] : 0;
			if($elementID > 0)
			{
				$result[] = $elementID;
			}
		}
		return $result;
	}
	public static function GetEntityList($entityTypeID, $userID, $sortOrder, array $filter, $navParams = false, array $options = null)
	{
		$entityTypeID = (int)$entityTypeID;
		$userID = (int)$userID;

		$userIDs = array(0);
		if($userID > 0)
		{
			$userIDs[] = $userID;
		}

		if($options === null)
		{
			$options = array();
		}

		$lb = null;
		$fieldOptions = isset($options['FIELD_OPTIONS']) && is_array($options['FIELD_OPTIONS'])
			? $options['FIELD_OPTIONS'] : null;
		if($entityTypeID === CCrmOwnerType::Lead)
		{
			$lb = CCrmLead::CreateListBuilder($fieldOptions);
		}
		else if($entityTypeID === CCrmOwnerType::Deal)
		{
			$lb = CCrmDeal::CreateListBuilder($fieldOptions);
		}
		else if($entityTypeID === CCrmOwnerType::Contact)
		{
			$lb = CCrmContact::CreateListBuilder($fieldOptions);
		}
		else if($entityTypeID === CCrmOwnerType::Company)
		{
			$lb = CCrmCompany::CreateListBuilder($fieldOptions);
		}
		else if($entityTypeID === CCrmOwnerType::Order)
		{
			$lb = CCrmCompany::CreateListBuilder($fieldOptions);
		}

		if(!$lb)
		{
			return null;
		}

		$fields = $lb->GetFields();
		$entityAlias = $lb->GetTableAlias();
		$join = 'LEFT JOIN '.CCrmActivity::USER_ACTIVITY_TABLE_NAME.' UA ON UA.USER_ID IN ('.implode(',', $userIDs).') AND UA.OWNER_ID = '.$entityAlias.'.ID AND UA.OWNER_TYPE_ID = '.$entityTypeID;
		$fields['ACTIVITY_USER_ID'] = array('FIELD' => 'MAX(UA.USER_ID)', 'TYPE' => 'int', 'FROM'=> $join);
		$fields['ACTIVITY_SORT'] = array('FIELD' => 'MAX(UA.SORT)', 'TYPE' => 'string', 'FROM'=> $join);
		$lb->SetFields($fields);

		$sortOrder = mb_strtoupper($sortOrder);
		if($sortOrder !== 'DESC' && $sortOrder !== 'ASC')
		{
			$sortOrder = 'ASC';
		}

		$options = array_merge(
			$options,
			array(
				'PERMISSION_SQL_TYPE' => 'FROM',
				'PERMISSION_SQL_UNION' => 'DISTINCT',
				'ENABLE_GROUPING_COUNT' => false
			)
		);

		return $lb->Prepare(
			array('ACTIVITY_USER_ID' => 'DESC', 'ACTIVITY_SORT' => $sortOrder, 'ID' => $sortOrder),
			$filter,
			array('ID'),
			$navParams,
			array('ID'),
			$options
		);
	}
	public static function HasChildren($ID)
	{
		if($ID <= 0)
		{
			return false;
		}

		global $DB;
		$fields = $DB->query(
			sprintf(
				'SELECT ID FROM %s WHERE PARENT_ID = %u LIMIT 1',
				\CCrmActivity::TABLE_NAME,
				$ID
			)
		)->fetch();
		return is_array($fields);
	}
	public static function PrepareDescriptionFields(array &$fields, array $options = null)
	{

		if(!is_array($options))
		{
			$options = array();
		}

		$enableHtml = !isset($options['ENABLE_HTML']) || $options['ENABLE_HTML'] === true;
		$enableBBCode = !isset($options['ENABLE_BBCODE']) || $options['ENABLE_BBCODE'] === true;
		$limit = isset($options['LIMIT']) ? (int)$options['LIMIT'] : 0;

		$description = isset($fields['DESCRIPTION']) ? $fields['DESCRIPTION'] : '';
		$descriptionType = isset($fields['DESCRIPTION_TYPE']) ? (int)$fields['DESCRIPTION_TYPE'] : \CCrmContentType::PlainText;


		if($descriptionType === \CCrmContentType::Html)
		{
			if($enableBBCode)
			{
				$fields['DESCRIPTION_BBCODE'] = '';
			}

			if($enableHtml)
			{
				$fields['DESCRIPTION_HTML'] = $description;
			}

			$description = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $description);
			$description = preg_replace('/<blockquote[^>]*>.*?<\/blockquote>/is', '', $description);
			$description = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $description);
			$fields['DESCRIPTION_RAW'] = strip_tags(
					preg_replace(
						'/(<br[^>]*>)+/isu',
						"\n",
						html_entity_decode($description, ENT_QUOTES)
					)
			);
		}
		elseif($descriptionType === \CCrmContentType::BBCode)
		{
			$parser = new \CTextParser();
			$parser->allow['SMILES'] = 'N';

			if($enableBBCode)
			{
				$fields['DESCRIPTION_BBCODE'] = $description;
			}

			$descriptionHtml = $parser->convertText($description);
			if($enableHtml)
			{
				$fields['DESCRIPTION_HTML'] = $descriptionHtml;
			}

			$fields['DESCRIPTION_RAW'] = strip_tags(
				preg_replace(
					array(
						'/(<br[^>]*>)+/isu',
						'/(&nbsp;)+/isu',
						'/\[[0-9a-z\W\=]+\]/iUsu'
					),
					array(
						"\n",
						" ",
						""
					),
					html_entity_decode($descriptionHtml, ENT_QUOTES)
				)
			);
		}
		else//if($descriptionType === CCrmContentType::PlainText)
		{
			if($enableBBCode)
			{
				$fields['DESCRIPTION_BBCODE'] = '';
			}

			if($enableHtml)
			{
				$fields['DESCRIPTION_HTML'] = preg_replace("/[\r\n]+/u", "<br/>", htmlspecialcharsbx($description));
			}

			$fields['DESCRIPTION_RAW'] = $description;
		}
		unset($fields['DESCRIPTION']);

		if($limit > 0 && mb_strlen($fields['DESCRIPTION_RAW']) > $limit)
		{
			$fields['DESCRIPTION_RAW'] = mb_substr($fields['DESCRIPTION_RAW'], 0, $limit);
		}

		$fields['DESCRIPTION_RAW'] = \Bitrix\Main\Text\Emoji::decode($fields['DESCRIPTION_RAW']);

		if(isset($fields['SUBJECT']))
		{
			$fields['SUBJECT'] = \Bitrix\Main\Text\Emoji::decode($fields['SUBJECT']);
		}
	}

	/**
	 * Append existed bindings for entity types which are not stored in calendar uf
	 *
	 * @param $id
	 * @param $arOwnerData
	 */
	protected static function EnrichWithNotRemovableBindings($id, &$arOwnerData): void
	{
		$existedBindings = self::GetBindings($id);
		foreach ($existedBindings as $i => $binding)
		{
			if (
				(int)$binding['OWNER_TYPE_ID'] === \CCrmOwnerType::Deal
				|| CCrmOwnerType::IsClient($binding['OWNER_TYPE_ID'])
			)
			{
				unset($existedBindings[$i]);
				continue;
			}
			$existedBindings[$i]['OWNER_TYPE_NAME'] = CCrmOwnerType::ResolveName($binding['OWNER_TYPE_ID']);
		}
		$arOwnerData = array_merge($arOwnerData, array_values($existedBindings));
	}

	protected static function fetchActivityPingOffsets(array $arFields): array
	{
		$arPingOffsets = isset($arFields['PING_OFFSETS']) && is_array($arFields['PING_OFFSETS'])
			? $arFields['PING_OFFSETS']
			: null;
		if (is_null($arPingOffsets))
		{
			$provider = self::GetActivityProvider($arFields);
			if ($provider !== null)
			{
				$categoryId = Container::getInstance()->getFactory($arFields['OWNER_TYPE_ID'])?->getItemCategoryId($arFields['OWNER_ID']);
				$arPingOffsets = $provider::getDefaultPingOffsets(
					[
						'entityTypeId' => (int)$arFields['OWNER_TYPE_ID'],
						'categoryId' => (int)($categoryId ?? 0),
					]
				);
			}
		}

		return $arPingOffsets;
	}

	private static function queryLightTimeDateByActId(int $ID): ?DateTime
	{
		/** @var ActCounterLightTimeRepo $lightCounterRepo */
		$lightCounterRepo = ServiceLocator::getInstance()->get('crm.activity.actcounterlighttimerepo');
		return $lightCounterRepo->queryLightTimeByActivityId($ID);
	}

	/**
	 * @param array $activity - activity fields
	 *
	 * @return int[]|null
	 */
	final public static function extractStorageElementIds(array $activity): ?array
	{
		if (!isset($activity['STORAGE_ELEMENT_IDS']))
		{
			return null;
		}

		if (is_string($activity['STORAGE_ELEMENT_IDS']))
		{
			$storageElementIds = unserialize($activity['STORAGE_ELEMENT_IDS'], ['allowed_classes' => false]);
		}
		elseif (is_array($activity['STORAGE_ELEMENT_IDS']))
		{
			$storageElementIds = $activity['STORAGE_ELEMENT_IDS'];
		}
		else
		{
			$storageElementIds = [];
		}

		$intStorageElementIds = array_map('intval', $storageElementIds);
		$validIdStorageElementIds = array_filter($intStorageElementIds, fn(int $id) => $id > 0);

		// reindex array
		return array_values($validIdStorageElementIds);
	}

	private static function isLocationFeatureEnabled(): bool
	{
		return \Bitrix\Crm\Integration\Bitrix24Manager::isFeatureEnabled('calendar_location');
	}
}

class CCrmActivityType
{
	public const Undefined = 0;
	public const Meeting = 1;
	public const Call = 2;
	public const Task = 3;
	public const Email = 4;
	public const Activity = 5; // General type for import of calendar events and etc.
	public const Provider = 6; //General type for entities are controlled by various providers.

	public static function IsDefined($typeID)
	{
		$typeID = intval($typeID);
		return $typeID > self::Undefined && $typeID <= self::Provider;
	}

	private static $ALL_DESCRIPTIONS = null;

	public static function GetAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS)
		{
			self::$ALL_DESCRIPTIONS = array(
				self::Undefined => '',
				self::Meeting   => GetMessage('CRM_ACTIVITY_TYPE_MEETING'),
				self::Call      => GetMessage('CRM_ACTIVITY_TYPE_CALL'),
				self::Task      => GetMessage('CRM_ACTIVITY_TYPE_TASK'),
				self::Email     => GetMessage('CRM_ACTIVITY_TYPE_EMAIL'),
				self::Activity  => GetMessage('CRM_ACTIVITY_TYPE_ACTIVITY'),
				self::Provider  => GetMessage('CRM_ACTIVITY_TYPE_PROVIDER'),
			);
		}

		return self::$ALL_DESCRIPTIONS;
	}

	public static function ResolveDescription($typeID)
	{
		$typeID = intval($typeID);
		$all = self::GetAllDescriptions();
		return isset($all[$typeID]) ? $all[$typeID] : $all[self::Undefined];
	}

	public static function PrepareListItems()
	{
		$descriptions = self::GetAllDescriptions();

		return CCrmEnumeration::PrepareListItems($descriptions, [self::Undefined]);
	}

	public static function PrepareFilterItems()
	{
		$descriptions = self::GetAllDescriptions();

		return CCrmEnumeration::PrepareFilterItems($descriptions, [self::Undefined]);
	}
}

class CCrmActivityStatus
{
	const Undefined = 0;
	const Waiting = 1;
	const Completed = 2;
	const AutoCompleted = 3;

	private static $ALL_DESCRIPTIONS = null;

	public static function GetAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS)
		{
			self::$ALL_DESCRIPTIONS = array(
				self::Undefined => '',
				self::Waiting => GetMessage('CRM_ACTIVITY_STATUS_WAITING'),
				self::Completed => GetMessage('CRM_ACTIVITY_STATUS_COMPLETED'),
				self::AutoCompleted => GetMessage('CRM_ACTIVITY_STATUS_AUTO_COMPLETED')
			);
		}

		return self::$ALL_DESCRIPTIONS;
	}

	public static function ResolveDescription($statusID, $typeID)
	{
		$statusID = intval($statusID);
		//$typeID = intval($typeID); //RESERVED

		$all = self::GetAllDescriptions();
		return isset($all[$statusID]) ? $all[$statusID] : $all[self::Undefined];
	}

	public static function PrepareListItems($typeID)
	{
		//$typeID = intval($typeID); //RESERVED
		$descriptions = self::GetAllDescriptions();

		return CCrmEnumeration::PrepareListItems($descriptions, [self::Undefined]);
	}
}

class CCrmActivityDirection
{
	public const Undefined = 0;
	public const Incoming = 1;
	public const Outgoing = 2;

	public const IncomingName = 'INCOMING';
	public const OutgoingName = 'OUTGOING';

	private static $ALL_DESCRIPTIONS = array();

	public static function IsDefined($typeId): bool
	{
		return in_array((int)$typeId, [self::Incoming, self::Outgoing], true);
	}

	public static function ResolveName($typeId): string
	{
		return match ((int)$typeId)
		{
			self::Incoming => self::IncomingName,
			self::Outgoing => self::OutgoingName,
			default => '',
		};
	}

	public static function GetAllDescriptions($typeID = CCrmActivityType::Undefined)
	{
		if(!isset(self::$ALL_DESCRIPTIONS[$typeID]))
		{
			$typeID = intval($typeID);

			$incomingID = 'CRM_ACTIVITY_DIRECTION_INCOMING';
			$outgoingID = 'CRM_ACTIVITY_DIRECTION_OUTGOING';

			if($typeID === CCrmActivityType::Email)
			{
				$incomingID = 'CRM_ACTIVITY_EMAIL_DIRECTION_INCOMING';
				$outgoingID = 'CRM_ACTIVITY_EMAIL_DIRECTION_OUTGOING';
			}
			elseif($typeID === CCrmActivityType::Call)
			{
				$incomingID = 'CRM_ACTIVITY_CALL_DIRECTION_INCOMING';
				$outgoingID = 'CRM_ACTIVITY_CALL_DIRECTION_OUTGOING';
			}

			self::$ALL_DESCRIPTIONS[$typeID] = array(
				self::Undefined => '',
				self::Incoming => GetMessage($incomingID),
				self::Outgoing => GetMessage($outgoingID)
			);
		}

		return self::$ALL_DESCRIPTIONS[$typeID];
	}

	public static function ResolveDescription($directionID, $typeID)
	{
		$directionID = intval($directionID);
		$typeID = intval($typeID);
		$all = self::GetAllDescriptions($typeID);

		return isset($all[$directionID]) ? $all[$directionID] : $all[self::Undefined];
	}

	public static function PrepareListItems($typeID)
	{
		$typeID = intval($typeID);
		$all = self::GetAllDescriptions($typeID);

		return array(
			array('text' => $all[self::Incoming], 'value' => strval(self::Incoming)),
			array('text' => $all[self::Outgoing], 'value' => strval(self::Outgoing)),
		);
	}
}

class CCrmActivityPriority
{
	const None = 0;
	const Low = 1;
	const Medium = 2;
	const High = 3;

	private static $ALL_DESCRIPTIONS = null;

	public static function GetAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS)
		{
			self::$ALL_DESCRIPTIONS = array(
				self::None => '',
				self::Low => GetMessage('CRM_PRIORITY_LOW'),
				self::Medium => GetMessage('CRM_PRIORITY_MEDIUM'),
				self::High => GetMessage('CRM_PRIORITY_HIGH')
			);
		}

		return self::$ALL_DESCRIPTIONS;
	}

	public static function PrepareListItems()
	{
		$descriptions = self::GetAllDescriptions();

		return CCrmEnumeration::PrepareListItems($descriptions, array(self::None));
	}

	public static function PrepareFilterItems()
	{
		$descriptions = self::GetAllDescriptions();

		return CCrmEnumeration::PrepareFilterItems($descriptions, array(self::None));
	}

	public static function ResolveDescription($priorityID)
	{
		$priorityID = intval($priorityID);
		$all = self::GetAllDescriptions();
		return  isset($all[$priorityID]) ? $all[$priorityID] : $all[self::None];
	}

	public static function ToCalendarEventImportance($priorityID)
	{
		$priorityID = intval($priorityID);
		if($priorityID === CCrmActivityPriority::Low)
		{
			return 'low';
		}
		elseif($priorityID === CCrmActivityPriority::High)
		{
			return 'high';
		}

		return 'normal';
	}

	public static function FromCalendarEventImportance($importance)
	{
		$importance = mb_strtolower(trim(strval($importance)));
		if($importance === '')
		{
			return CCrmActivityPriority::Medium;
		}

		if($importance === 'low')
		{
			return CCrmActivityPriority::Low;
		}
		elseif($importance === 'high')
		{
			return CCrmActivityPriority::High;
		}

		return CCrmActivityPriority::Medium;
	}
}

class CCrmActivityNotifyType
{
	const None = 0;
	const Min = 1;
	const Hour = 2;
	const Day = 3;

	private static $ALL_DESCRIPTIONS = null;

	public static function GetAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS)
		{
			self::$ALL_DESCRIPTIONS = array(
				self::None => '',
				self::Min => GetMessage('CRM_NOTIFY_TYPE_MIN'),
				self::Hour => GetMessage('CRM_NOTIFY_TYPE_HOUR'),
				self::Day => GetMessage('CRM_NOTIFY_TYPE_DAY')
			);
		}

		return self::$ALL_DESCRIPTIONS;
	}

	public static function PrepareListItems()
	{
		$descriptions = self::GetAllDescriptions();

		return CCrmEnumeration::PrepareListItems($descriptions, [self::None]);
	}

	public static function ResolveDescription($notifyTypeID)
	{
		$notifyTypeID = intval($notifyTypeID);
		$all = self::GetAllDescriptions();
		return  isset($all[$notifyTypeID]) ? $all[$notifyTypeID] : $all[self::None];
	}

	public static function ToCalendarEventRemind($notifyType)
	{
		$notifyType = intval($notifyType);

		$result = 'min';
		if($notifyType == self::Hour)
		{
			$result = 'hour';
		}
		elseif($notifyType == self::Day)
		{
			$result = 'day';
		}

		return $result;
	}

	public static function FromCalendarEventRemind($type)
	{
		$type = mb_strtolower(strval($type));

		if($type === 'min')
		{
			return CCrmActivityNotifyType::Min;
		}
		elseif($type === 'hour')
		{
			return CCrmActivityNotifyType::Hour;
		}
		elseif($type === 'day')
		{
			return CCrmActivityNotifyType::Day;
		}

		return CCrmActivityNotifyType::None;
	}
}

/**
 * @deprecated Please use \Bitrix\Crm\Integration\StorageType
 */
class CCrmActivityStorageType
{
	const Undefined = 0;
	const File = 1;
	const WebDav = 2;
	const Disk = 3;

	public static function IsDefined($typeID)
	{
		$typeID = intval($typeID);
		return $typeID > self::Undefined && $typeID <= self::Disk;
	}
}

class CCrmContentType
{
	const Undefined = 0;
	const PlainText = 1;
	const BBCode = 2;
	const Html = 3;

	const PlainTextName = 'PLAIN_TEXT';
	const BBCodeName = 'BBCODE';
	const HtmlName = 'HTML';

	public static function IsDefined($typeID)
	{
		$typeID = intval($typeID);
		return $typeID >= self::PlainText && $typeID <= self::Html;
	}

	public static function ResolveTypeID($typeName)
	{
		$typeName = strval($typeName);
		switch($typeName)
		{
			case self::PlainTextName:
				return self::PlainText;
			case self::BBCodeName:
				return self::BBCode;
			case self::HtmlName:
				return self::Html;
		}
		return self::Undefined;
	}

	public static function resolveName($typeId)
	{
		if (!is_numeric($typeId))
			return '';

		$typeId = intval($typeId);
		if ($typeId <= 0)
			return '';

		switch ($typeId)
		{
			case self::PlainText:
				return self::PlainTextName;
			case self::BBCode:
				return self::BBCodeName;
			case self::Html:
				return self::HtmlName;
		}

		return '';
	}

	private static $ALL_DESCRIPTIONS = null;
	public static function GetAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS)
		{
			self::$ALL_DESCRIPTIONS = array(
				self::Undefined => '',
				self::PlainText => 'Plain text',
				self::BBCode => 'bbCode',
				self::Html => 'HTML',
			);
		}

		return self::$ALL_DESCRIPTIONS;
	}

	public static function getAll(): array
	{
		return [
			self::PlainText,
			self::BBCode,
			self::Html,
		];
	}
}

class CCrmActivityEmailSender
{
	const ERR_CANT_LOAD_SUBSCRIBE = -1;
	const ERR_INVALID_DATA = -2;
	const ERR_INVALID_EMAIL = -3;
	const ERR_CANT_FIND_EMAIL_FROM = -4;
	const ERR_CANT_FIND_EMAIL_TO = -5;
	const ERR_CANT_ADD_POSTING = -6;
	const ERR_CANT_UPDATE_ACTIVITY = -7;
	const ERR_CANT_SAVE_POSTING_FILE = -8;
	const ERR_GENERAL = -100;

	public static function TrySendEmail($ID, &$arFields, &$arErrors)
	{
		if (!CModule::IncludeModule('subscribe'))
		{
			$arErrors[] = array('CODE' => self::ERR_CANT_LOAD_SUBSCRIBE);
			return false;
		}

		$ID = intval($ID);
		if($ID <= 0 && isset($arFields['ID']))
		{
			$ID = intval($arFields['ID']);
		}

		if($ID <= 0 || !is_array($arFields))
		{
			$arErrors[] = array('CODE' => self::ERR_INVALID_DATA);
			return false;
		}

		$typeID = isset($arFields['TYPE_ID']) ? intval($arFields['TYPE_ID']) : CCrmActivityType::Undefined;
		if($typeID !== CCrmActivityType::Email)
		{
			$arErrors[] = array('CODE' => self::ERR_INVALID_DATA);
			return false;
		}

		$urn = CCrmActivity::PrepareUrn($arFields);
		if(!($urn !== ''
			&& CCrmActivity::Update($ID, array('URN'=> $urn), false, false)))
		{
			$arErrors[] = array('CODE' => self::ERR_CANT_UPDATE_ACTIVITY);
			return false;
		}

		$messageId = sprintf(
			'<crm.activity.%s@%s>', $urn,
			defined('BX24_HOST_NAME') ? BX24_HOST_NAME : (
				defined('SITE_SERVER_NAME') && SITE_SERVER_NAME
					? SITE_SERVER_NAME : \COption::getOptionString('main', 'server_name', '')
			)
		);

		$settings = isset($arFields['SETTINGS']) && is_array($arFields['SETTINGS']) ? $arFields['SETTINGS'] : array();

		// Creating Email -->

		if (CModule::includeModule('mail'))
		{
			$res = \Bitrix\Mail\MailboxTable::getList(array(
				'select' => array('*', 'LANG_CHARSET' => 'SITE.CULTURE.CHARSET'),
				'filter' => array(
					'=LID'    => SITE_ID,
					'=ACTIVE' => 'Y',
					array(
						'LOGIC' => 'OR',
						'=USER_ID' => $arFields['RESPONSIBLE_ID'],
						array(
							'USER_ID'      => 0,
							'=SERVER_TYPE' => 'imap',
						),
					),
				),
				'order' => array('TIMESTAMP_X' => 'ASC'), // @TODO: order by ID
			));

			while ($mailbox = $res->fetch())
			{
				if (!empty($mailbox['OPTIONS']['flags']) && in_array('crm_connect', (array) $mailbox['OPTIONS']['flags']))
				{
					$mailbox['EMAIL_FROM'] = null;
					if (check_email($mailbox['NAME'], true))
						$mailbox['EMAIL_FROM'] = mb_strtolower($mailbox['NAME']);
					elseif(check_email($mailbox['LOGIN'], true))
						$mailbox['EMAIL_FROM'] = mb_strtolower($mailbox['LOGIN']);

					if ($mailbox['USER_ID'] > 0)
						$userImap = $mailbox;
					else
						$crmImap = $mailbox;
				}
			}

			$defaultFrom = \Bitrix\Mail\User::getDefaultEmailFrom();
		}

		$crmEmail = \CCrmMailHelper::extractEmail(\COption::getOptionString('crm', 'mail', ''));

		$from  = '';
		$reply = '';
		$to    = array();
		$cc    = '';

		if (isset($settings['MESSAGE_FROM']))
			$from = trim(strval($settings['MESSAGE_FROM']));

		if ($from == '')
		{
			if (!empty($userImap))
			{
				$from = $userImap['EMAIL_FROM'] ?: $defaultFrom;
				$userImap['need_sync'] = true;
			}
			elseif (!empty($crmImap))
			{
				$from = $crmImap['EMAIL_FROM'] ?: $defaultFrom;
				$crmImap['need_sync'] = true;
			}
			else
			{
				$from = $crmEmail;
				$cc   = $crmEmail;
			}

			if ($from == '')
				$arErrors[] = array('CODE' => self::ERR_CANT_FIND_EMAIL_FROM);
		}
		else
		{
			$fromAddresses = explode(',', $from);
			foreach ($fromAddresses as $fromAddress)
			{
				if (!check_email($fromAddress))
				{
					$arErrors[] = array(
						'CODE' => self::ERR_INVALID_EMAIL,
						'DATA' => array('EMAIL' => $fromAddress)
					);
					continue;
				}

				// copied from check_email
				if (preg_match('/.*?[<\[\(](.+?)[>\]\)].*/i', $fromAddress, $matches))
					$fromAddress = $matches[1];
				$fromList[] = mb_strtolower(trim($fromAddress));
			}

			if (!empty($userImap['EMAIL_FROM']) && in_array($userImap['EMAIL_FROM'], $fromList))
				$userImap['need_sync'] = true;
			if (!empty($crmImap['EMAIL_FROM']) && in_array($crmImap['EMAIL_FROM'], $fromList))
				$crmImap['need_sync'] = true;

			$cc = join(', ', array_diff(
				$fromList,
				array(
					!empty($userImap['EMAIL_FROM']) ? $userImap['EMAIL_FROM'] : '',
					!empty($crmImap['EMAIL_FROM']) ? $crmImap['EMAIL_FROM'] : '',
				)
			));

			if (empty($userImap['need_sync']) && empty($crmImap['need_sync']))
			{
				if (!empty($userImap['EMAIL_FROM']))
					$reply = join(', ', $fromList) . ', ' . $userImap['EMAIL_FROM'];
				else if (!empty($crmImap['EMAIL_FROM']))
					$reply = join(', ', $fromList) . ', ' . $crmImap['EMAIL_FROM'];
				else if ($crmEmail != '' && !in_array($crmEmail, $fromList))
					$reply = join(', ', $fromList) . ', ' . $crmEmail;
			}
		}

		//Save user email in settings -->
		if($from !== CUserOptions::GetOption('crm', 'activity_email_addresser', ''))
		{
			CUserOptions::SetOption('crm', 'activity_email_addresser', $from);
		}
		//<-- Save user email in settings


		$to = array();
		$commData = isset($arFields['COMMUNICATIONS']) ? $arFields['COMMUNICATIONS'] : array();
		foreach($commData as &$commDatum)
		{
			$commType = isset($commDatum['TYPE'])? mb_strtoupper(strval($commDatum['TYPE'])) : '';
			$commValue = isset($commDatum['VALUE']) ? strval($commDatum['VALUE']) : '';

			if($commType !== 'EMAIL' || $commValue === '')
			{
				continue;
			}

			if(!check_email($commValue))
			{
				$arErrors[] = array(
					'CODE' => self::ERR_INVALID_EMAIL,
					'DATA' => array('EMAIL' => $commValue)
				);
				continue;
			}

			$to[] = mb_strtolower(trim($commValue));
		}
		unset($commDatum);

		if(count($to) == 0)
		{
			$arErrors[] = array('CODE' => self::ERR_CANT_FIND_EMAIL_TO);
		}

		if(!empty($arErrors))
		{
			return false;
		}

		// Try to resolve posting charset -->
		$postingCharset = '';
		$siteCharset = defined('LANG_CHARSET') ? LANG_CHARSET : (defined('SITE_CHARSET') ? SITE_CHARSET : 'windows-1251');
		$arSupportedCharset = explode(',', COption::GetOptionString('subscribe', 'posting_charset'));
		if(count($arSupportedCharset) === 0)
		{
			$postingCharset = $siteCharset;
		}
		else
		{
			foreach($arSupportedCharset as $curCharset)
			{
				if(strcasecmp($curCharset, $siteCharset) === 0)
				{
					$postingCharset = $curCharset;
					break;
				}
			}

			if($postingCharset === '')
			{
				$postingCharset = $arSupportedCharset[0];
			}
		}
		//<-- Try to resolve posting charset
		$subject = isset($arFields['SUBJECT']) ? $arFields['SUBJECT'] : '';
		$description = isset($arFields['DESCRIPTION']) ? $arFields['DESCRIPTION'] : '';
		$descriptionType = isset($arFields['DESCRIPTION_TYPE']) ? intval($arFields['DESCRIPTION_TYPE']) : CCrmContentType::PlainText;

		$descriptionHtml = '';
		if($descriptionType === CCrmContentType::Html)
		{
			$descriptionHtml = $description;
		}
		elseif($descriptionType === CCrmContentType::BBCode)
		{
			$parser = new CTextParser();
			$parser->allow['SMILES'] = 'N';
			$descriptionHtml = '<html><body>'.$parser->convertText($description).'</body></html>';
		}
		elseif($descriptionType === CCrmContentType::PlainText)
		{
			$descriptionHtml = htmlspecialcharsbx($description);
		}

		if (isset($settings['DISABLE_SENDING_MESSAGE_COPY']) && $settings['DISABLE_SENDING_MESSAGE_COPY'] === 'Y')
		{
			$cc = '';
		}

		$postingData = array(
			'STATUS' => 'D',
			'FROM_FIELD' => $from,
			'TO_FIELD' => $cc,
			'BCC_FIELD' => implode(',', $to),
			'SUBJECT' => $subject,
			'BODY_TYPE' => 'html',
			'BODY' => $descriptionHtml,
			'DIRECT_SEND' => 'Y',
			'SUBSCR_FORMAT' => 'html',
			'CHARSET' => $postingCharset
		);

		CCrmActivity::InjectUrnInMessage(
			$postingData,
			$urn,
			CCrmEMailCodeAllocation::GetCurrent()
		);

		$posting = new CPosting();
		$postingID = $posting->Add($postingData);
		if($postingID === false)
		{
			$arErrors[] = array('CODE' => self::ERR_CANT_ADD_POSTING, 'MESSAGE' => $posting->LAST_ERROR);
			return false;
		}

		$arUpdateFields = array(
			'COMPLETED' => 'Y',
			'ASSOCIATED_ENTITY_ID'=> $postingID,
			'SETTINGS' => $settings
		);

		$arUpdateFields['SETTINGS']['MESSAGE_HEADERS'] = array('Message-Id' => $messageId);
		if ($reply != '')
			$arUpdateFields['SETTINGS']['MESSAGE_HEADERS']['Reply-To'] = $reply;

		$arUpdateFields['SETTINGS']['IS_MESSAGE_SENT'] = true;

		if(!CCrmActivity::Update($ID, $arUpdateFields, false, false))
		{
			$arErrors[] = array('CODE' => self::ERR_CANT_UPDATE_ACTIVITY);
			return false;
		}
		// <-- Creating Email

		$arFields['COMPLETED'] = $arUpdateFields['COMPLETED'];
		$arFields['ASSOCIATED_ENTITY_ID'] = $arUpdateFields['ASSOCIATED_ENTITY_ID'];
		$arFields['SETTINGS'] = $arUpdateFields['SETTINGS'];

		// Attaching files -->
		$storageTypeID = isset($arFields['STORAGE_TYPE_ID'])
			? intval($arFields['STORAGE_TYPE_ID']) : StorageType::Undefined;
		$storageElementsID = isset($arFields['STORAGE_ELEMENT_IDS'])
			&& is_array($arFields['STORAGE_ELEMENT_IDS'])
			? $arFields['STORAGE_ELEMENT_IDS'] : array();

		$hostname = \COption::getOptionString('main', 'server_name', 'localhost');
		if (defined('BX24_HOST_NAME') && BX24_HOST_NAME != '')
			$hostname = BX24_HOST_NAME;
		else if (defined('SITE_SERVER_NAME') && SITE_SERVER_NAME != '')
			$hostname = SITE_SERVER_NAME;

		$arRawFiles = StorageManager::makeFileArray($storageElementsID, $storageTypeID);
		foreach($arRawFiles as $arRawFile)
		{
			$contentId = sprintf(
				'bxacid.%s@%s.crm',
				hash('crc32b', $arRawFile['external_id'].$arRawFile['size'].$arRawFile['name']),
				hash('crc32b', $hostname)
			);

			$arRawFile['name'] = sprintf('%s_%s', $arRawFile['name'], $contentId);
			if(!$posting->SaveFile($postingID, $arRawFile))
			{
				$arErrors[] = array('CODE' => self::ERR_CANT_SAVE_POSTING_FILE, 'MESSAGE' => $posting->LAST_ERROR);
				return false;
			}
		}
		// <-- Attaching files

		if (!empty($userImap['need_sync']) || !empty($crmImap['need_sync']))
		{
			$attachments = array();
			foreach ($arRawFiles as $item)
			{
				$attachments[] = array(
					'ID'           => $item['external_id'],
					'NAME'         => $item['ORIGINAL_NAME'] ?: $item['name'],
					'PATH'         => $item['tmp_name'],
					'CONTENT_TYPE' => $item['type'],
				);
			}

			class_exists('Bitrix\Mail\Helper');

			$rcpt = array();
			foreach ($to as $item)
				$rcpt[] = \Bitrix\Mail\DummyMail::encodeHeaderFrom($item, SITE_CHARSET);
			$rcpt = join(', ', $rcpt);

			$outgoing = new \Bitrix\Mail\DummyMail(array(
				'CONTENT_TYPE' => 'html',
				'CHARSET'      => SITE_CHARSET,
				'HEADER'       => array(
					'From'       => $from,
					'To'         => $rcpt,
					'Subject'    => $subject,
					'Message-Id' => $messageId,
				),
				'BODY'         => $descriptionHtml,
				'ATTACHMENT'   => $attachments
			));

			if (!empty($userImap['need_sync']))
				\Bitrix\Mail\Helper::addImapMessage($userImap, (string) $outgoing, $err);
			if (!empty($crmImap['need_sync']))
				\Bitrix\Mail\Helper::addImapMessage($crmImap, (string) $outgoing, $err);
		}

		// Sending Email -->
		if($posting->ChangeStatus($postingID, 'P'))
		{
			$rsAgents = CAgent::GetList(
				array('ID'=>'DESC'),
				array(
					'MODULE_ID' => 'subscribe',
					'NAME' => 'CPosting::AutoSend('.$postingID.',%',
				)
			);

			if(!$rsAgents->Fetch())
			{
				CAgent::AddAgent('CPosting::AutoSend('.$postingID.',true);', 'subscribe', 'N', 0);
			}
		}

		// Try add event to entity
		$CCrmEvent = new CCrmEvent();

		$bindings = \CCrmActivity::GetBindings($ID);
		if(!empty($bindings))
		{
			$eventText  = '';
			$eventText .= GetMessage('CRM_ACTIVITY_EMAIL_SUBJECT').': '.$subject."\n\r";
			$eventText .= GetMessage('CRM_ACTIVITY_EMAIL_FROM').': '.$from."\n\r";
			$eventText .= GetMessage('CRM_ACTIVITY_EMAIL_TO').': '.implode(',', $to)."\n\r\n\r";
			$eventText .= $description;

			$eventBindings = array();
			foreach($bindings as $item)
			{
				$bindingEntityID = $item['OWNER_ID'];
				$bindingEntityTypeID = $item['OWNER_TYPE_ID'];
				$bindingEntityTypeName = \CCrmOwnerType::resolveName($bindingEntityTypeID);

				$eventBindings["{$bindingEntityTypeName}_{$bindingEntityID}"] = array(
					'ENTITY_TYPE' => $bindingEntityTypeName,
					'ENTITY_ID' => $bindingEntityID
				);
			}

			$CCrmEvent->Add(
				array(
					'ENTITY' => $eventBindings,
					'EVENT_ID' => 'MESSAGE',
					'EVENT_TEXT_1' => $eventText,
					'FILES' => $arRawFiles
				)
			);
		}
		// <-- Sending Email
		return true;
	}
}
