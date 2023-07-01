<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm;
use Bitrix\Crm\Activity;
use Bitrix\Crm\Integration;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Timeline\Entity\NoteTable;
use Bitrix\Main;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class ActivityController extends EntityController
{
	private const MAX_SIMULTANEOUS_PULL_EVENT_COUNT = 10;
	/** @var \CTextParser|null  */
	private static $parser = null;
	/** @var int|null  */
	private static $userID = null;
	/** @var  \CCrmPerms|null */
	private static $userPermissions = null;

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
			if($status === \CCrmActivityStatus::Completed)
			{
				$historyEntryID = ActivityEntry::create(
					array(
						'ACTIVITY_TYPE_ID' => $typeID,
						'ACTIVITY_PROVIDER_ID' => $providerID,
						'ENTITY_ID' => $ownerID,
						'AUTHOR_ID' => $authorID,
						'CREATED' => new DateTime(),
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
			$timelineParams = [
				'ACTIVITY_TYPE_ID' => $typeID,
				'ACTIVITY_PROVIDER_ID' => $providerID,
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => $authorID,
				'CREATED' => $created,
				'BINDINGS' => self::mapBindings($bindings),
			];

			$provider = \CCrmActivity::GetProviderById($providerID);
			if (!is_null($provider) && $provider::isTask())
			{
				$taskId = $fields['ASSOCIATED_ENTITY_ID'];
				$timelineParams['SOURCE_ID'] = $taskId;
			}
			$historyEntryID = ActivityEntry::create($timelineParams);
		}
		elseif($typeID === \CCrmActivityType::Provider
			&& isset($fields['PROVIDER_ID'])
			&& $fields['PROVIDER_ID'] === Activity\Provider\ToDo::getId()
		)
		{
			$logMessageController = LogMessageController::getInstance();
			foreach ($bindings as $binding)
			{
				$logMessageController->onCreate(
					[
						'ENTITY_TYPE_ID' => $binding['OWNER_TYPE_ID'],
						'ENTITY_ID' => $binding['OWNER_ID'],
						'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
						'ASSOCIATED_ENTITY_ID' => $ownerID,
						'SETTINGS' => [
							'ACTIVITY_DATA' => [
								'DESCRIPTION' => $fields['DESCRIPTION'],
								'ASSOCIATED_ENTITY_ID' => $fields['ASSOCIATED_ENTITY_ID'],
								'DEADLINE_TIMESTAMP' => $fields['DEADLINE'] ? (DateTime::createFromUserTime($fields['DEADLINE'])->getTimestamp()) : null,
							]
						]
					],
					LogMessageType::TODO_CREATED,
					$params['CURRENT_USER'] ?? null
				);
			}


		}

		if(isset($params['ENABLE_PUSH']) && $params['ENABLE_PUSH'] === false)
		{
			return;
		}

		$enableHistoryPush = $historyEntryID > 0;
		$enableSchedulePush = self::isActivitySupported($fields) && $status === \CCrmActivityStatus::Waiting;

		if (!isset($fields['CREATED']))
		{
			$fields['CREATED'] = (new DateTime())->toString();
		}
		$pullEventData = [$ownerID => $fields];

		if ($enableSchedulePush)
		{
			\Bitrix\Crm\Timeline\EntityController::loadCommunicationsAndMultifields(
				$pullEventData,
				Crm\Service\Container::getInstance()
					->getUserPermissions($params['CURRENT_USER'] ?? null)
					->getCrmPermissions(),
				[
					'ENABLE_PERMISSION_CHECK' => false,
				]
			);
		}

		foreach($bindings as $binding)
		{
			$entityItemIdentifier = Crm\ItemIdentifier::createFromArray($binding);
			if (!$entityItemIdentifier)
			{
				continue;
			}

			if ($enableSchedulePush)
			{
				$this->sendPullEventOnAddScheduled(
					$entityItemIdentifier,
					$pullEventData[$ownerID],
					$params['CURRENT_USER'] ?? null
				);
			}
			if ($enableHistoryPush)
			{
				$this->sendPullEventOnAdd(
					$entityItemIdentifier,
					$historyEntryID,
					$params['CURRENT_USER'] ?? null
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
		$additionalParams = isset($params['ADDITIONAL_PARAMS']) && is_array($params['ADDITIONAL_PARAMS'])
			? $params['ADDITIONAL_PARAMS'] : [];

		$typeID = isset($currentFields['TYPE_ID']) ? (int)$currentFields['TYPE_ID'] : \CCrmActivityType::Undefined;
		$providerID = isset($currentFields['PROVIDER_ID']) ? $currentFields['PROVIDER_ID'] : '';
		$prevCompleted = isset($previousFields['COMPLETED']) && $previousFields['COMPLETED'] === 'Y';
		$curCompleted = isset($currentFields['COMPLETED']) && $currentFields['COMPLETED'] === 'Y';

		$authorID = self::resolveAuthorID($currentFields);

		$historyEntryID = 0;
		if(!$prevCompleted && $curCompleted)
		{
			if (
				$typeID === \CCrmActivityType::Provider && $providerID === Activity\Provider\Tasks\Comment::getId()
			)
			{
				// do nothing...
			}
			elseif ($typeID == \CCrmActivityType::Task)
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
				$historyData = [
					'ACTIVITY_TYPE_ID' => $typeID,
					'ACTIVITY_PROVIDER_ID' => $providerID,
					'ENTITY_ID' => $ownerID,
					'AUTHOR_ID' => $authorID,
					'BINDINGS' => self::mapBindings($currentBindings),
				];
				$provider = \CCrmActivity::GetProviderById($providerID);
				if (!is_null($provider) && $provider::isTask())
				{
					$taskId = $currentFields['ASSOCIATED_ENTITY_ID'];
					$historyData['SOURCE_ID'] = $taskId;
				}

				// workaround to correct sort timeline history of completed calls
				// when it automatically close
				if (
					isset($additionalParams['CUSTOM_CREATION_TIME'])
					&& Crm\Settings\Crm::isUniversalActivityScenarioEnabled()
				)
				{
					$historyData['CREATED'] = DateTime::createFromUserTime($additionalParams['CUSTOM_CREATION_TIME'])->add('-1 second');
				}

				if (
					isset($params['CURRENT_FIELDS']['SETTINGS']['MISSED_CALL'])
					&& $params['CURRENT_FIELDS']['SETTINGS']['MISSED_CALL'] === true
					&& !Crm\Settings\Crm::isUniversalActivityScenarioEnabled()
				)
				{
					$historyData['CREATED'] = DateTime::createFromUserTime($params['CURRENT_FIELDS']['CREATED']);
				}

				$historyEntryID = ActivityEntry::create($historyData);
			}
		}
		elseif($prevCompleted && !$curCompleted)
		{
			if(
				$typeID == \CCrmActivityType::Provider
				&& in_array(
					$providerID,
					[
						Activity\Provider\ToDo::getId(),
						Activity\Provider\ConfigurableRestApp::getId(),
						Activity\Provider\Zoom::getId(),
						Activity\Provider\Tasks\Task::getId(),
						Activity\Provider\Tasks\Comment::getId(),
					]
				)
			)
			{
				// do nothing
			}
			else
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
		}

		$enableHistoryPush = $historyEntryID > 0;
		$enableActivityPush = self::isActivitySupported($currentFields);

		$pullEventData = [$ownerID => $currentFields];

		if ($enableActivityPush)
		{
			\Bitrix\Crm\Timeline\EntityController::loadCommunicationsAndMultifields(
				$pullEventData,
				Crm\Service\Container::getInstance()
					->getUserPermissions($params['CURRENT_USER'] ?? null)
					->getCrmPermissions(),
				[
					'ENABLE_PERMISSION_CHECK' => false,
				]
			);
		}

		foreach($currentBindings as $binding)
		{
			$entityItemIdentifier = Crm\ItemIdentifier::createFromArray($binding);
			if (!$entityItemIdentifier)
			{
				continue;
			}

			if (!$prevCompleted && $curCompleted && $enableHistoryPush && $enableActivityPush)
			// if activity has been completed and timeline history item has been produced,
			// need actually move one to another on the timeline instead of separate remove and add events:
			{
				$this->sendPullEventOnMove(
					$entityItemIdentifier,
					$ownerID,
					$historyEntryID,
					$params['CURRENT_USER'] ?? null
				);
			}
			elseif (!$prevCompleted && $curCompleted && $enableActivityPush)
				// if activity has been completed and timeline history item has not been produced,
				// need remove completed activity from timeline:
			{
				$this->sendPullEventOnDeleteScheduled($entityItemIdentifier, $ownerID);
			}
			else
			{
				if ($enableActivityPush)
				{
					if ($prevCompleted && !$curCompleted)
						// if activity has been marked as not completed
						// need to create new scheduled activity instead of update existed:
					{
						$this->sendPullEventOnAddScheduled(
							$entityItemIdentifier,
							$pullEventData[$ownerID],
							$params['CURRENT_USER'] ?? null
						);
					}
					else
					{
						// just send pull update event with new data
						$this->notifyTimelinesAboutActivityUpdateForBindings(
							$pullEventData[$ownerID],
							[
								$binding,
							],
							$params['CURRENT_USER'] ?? null
						);
					}
				}
				if ($enableHistoryPush)
				{
					$this->sendPullEventOnAdd(
						$entityItemIdentifier,
						$historyEntryID,
						$params['CURRENT_USER'] ?? null
					);
				}
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

		$bindings = isset($params['BINDINGS']) && is_array($params['BINDINGS']) ? $params['BINDINGS'] : [];
		foreach($bindings as $binding)
		{
			$this->sendPullEventOnDeleteScheduled(
				new Crm\ItemIdentifier($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']),
				$ownerID
			);
		}

		$movedToRecycleBin = isset($params['MOVED_TO_RECYCLE_BIN']) && $params['MOVED_TO_RECYCLE_BIN'];
		$associatedEntityId = $ownerID;
		if ($movedToRecycleBin)
		{
			$associatedEntityId = $params['RECYCLE_BIN_ENTITY_ID'] ?? null;
		}

		if ($associatedEntityId)
		{
			$timelineEntriesIds = TimelineEntry::getEntriesIdsByAssociatedEntity(
				$movedToRecycleBin ? \CCrmOwnerType::SuspendedActivity : \CCrmOwnerType::Activity,
				$associatedEntityId,
				self::MAX_SIMULTANEOUS_PULL_EVENT_COUNT
			);
			foreach ($timelineEntriesIds as $timelineEntryId)
			{
				foreach ($bindings as $binding)
				{
					$entityItemIdentifier = Crm\ItemIdentifier::createFromArray($binding);
					if ($entityItemIdentifier)
					{
						$this->sendPullEventOnDelete(
							$entityItemIdentifier,
							$timelineEntryId
						);
					}
				}
			}
		}

		if(!$movedToRecycleBin)
		{
			TimelineEntry::deleteByAssociatedEntity(\CCrmOwnerType::Activity, $ownerID);
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

			if(
				$typeCategoryID === \CCrmActivityType::Provider
				&& isset($data['ASSOCIATED_ENTITY']['PROVIDER_ID'])
				&& self::isActivityProviderSupported($data['ASSOCIATED_ENTITY']['PROVIDER_ID'])
			)
			{
				$provider = \CAllCrmActivity::GetProviderById($data['ASSOCIATED_ENTITY']['PROVIDER_ID']);
				$providerData = class_exists($provider) ? $provider::prepareHistoryItemData($data) : null;
				if (is_array($providerData))
				{
					$data['PROVIDER_DATA'] = $providerData;
				}
			}

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

	/**
	 * @param array $fields
	 * @return bool
	 */
	public static function isActivitySupported(array $fields): bool
	{
		$typeId = (isset($fields['TYPE_ID']) ? (int)$fields['TYPE_ID'] : \CCrmActivityType::Undefined);
		if(in_array($typeId, [
			\CCrmActivityType::Email,
			\CCrmActivityType::Call,
			\CCrmActivityType::Meeting,
			\CCrmActivityType::Task,
		], true))
		{
			return true;
		}

		if($typeId === \CCrmActivityType::Provider)
		{
			$providerId = ($fields['PROVIDER_ID'] ?? '');
			return in_array($providerId, self::getActivityProviders(), true);
		}

		return false;
	}

	/**
	 * @return array
	 */
	private static function getActivityProviders(): array
	{
		return [
			Activity\Provider\WebForm::getId(),
			Activity\Provider\Wait::getId(),
			Activity\Provider\Request::getId(),
			Activity\Provider\OpenLine::getId(),
			Activity\Provider\RestApp::getId(),
			Activity\Provider\Delivery::getId(),
			Activity\Provider\Zoom::getId(),
			Activity\Provider\CallTracker::getId(),
			Activity\Provider\StoreDocument::getId(),
			Activity\Provider\Document::getId(),
			Activity\Provider\SignDocument::getId(),
			Activity\Provider\ToDo::getId(),
			Activity\Provider\Payment::getId(),
			Activity\Provider\ConfigurableRestApp::getId(),
			Activity\Provider\CalendarSharing::getId(),
			Activity\Provider\Tasks\Comment::getId(),
			Activity\Provider\Tasks\Task::getId(),
		];
	}

	protected static function isActivityProviderSupported($providerID)
	{
		return(
			$providerID === Activity\Provider\WebForm::getId()
			|| $providerID === Activity\Provider\Wait::getId()
			|| $providerID === Activity\Provider\Request::getId()
			|| $providerID === Activity\Provider\OpenLine::getId()
			|| $providerID === Activity\Provider\Sms::getId()
			|| $providerID === Activity\Provider\Notification::getId()
			|| $providerID === Activity\Provider\RestApp::getId()
			|| $providerID === Activity\Provider\Visit::getId()
			|| $providerID === Activity\Provider\Zoom::getId()
			|| $providerID === Activity\Provider\Document::getId()
			|| $providerID === Activity\Provider\SignDocument::getId()
			|| $providerID === Activity\Provider\ToDo::getId()
			|| $providerID === Activity\Provider\ConfigurableRestApp::getId()
			|| $providerID === Activity\Provider\CalendarSharing::getId()
			|| $providerID === Activity\Provider\Tasks\Comment::getId()
			|| $providerID === Activity\Provider\Tasks\Task::getId()
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

		$userId = $options['CURRENT_USER'] ?? self::getUserID();
		$permissions = \CCrmPerms::GetUserPermissions($userId);

		\CCrmActivity::PrepareDescriptionFields(
			$data,
			array('ENABLE_HTML' => false, 'ENABLE_BBCODE' => false, 'LIMIT' => 512)
		);

		$sort = [];
		$createdTimestamp = MakeTimeStamp($data['CREATED']) - \CTimeZone::GetOffset();
		$data['CREATED_SERVER'] = date(
			'Y-m-d H:i:s',
			$createdTimestamp
		);
		if ($data['IS_INCOMING_CHANNEL'] === 'Y')
		{
			// incoming channel activities have a negative timestamp because they must be first in the list
			// and must be sorted in reverse order:
			$sort = [-$createdTimestamp, (int)$data['ID']];
		}
		elseif(isset($data['DEADLINE']))
		{
			if(isset($data['DEADLINE']) && \CCrmDateTimeHelper::IsMaxDatabaseDate($data['DEADLINE']))
			{
				unset($data['DEADLINE']);
				$sort = [PHP_INT_MAX, (int)$data['ID']];
			}
			else
			{
				$deadlineTimestamp = MakeTimeStamp($data['DEADLINE']) - \CTimeZone::GetOffset();
				$data['DEADLINE_SERVER'] = date(
					'Y-m-d H:i:s',
					$deadlineTimestamp
				);
				$sort = [$deadlineTimestamp, (int)$data['ID']];
			}
		}
		else
		{
			$sort = [PHP_INT_MAX, (int)$data['ID']];
		}

		$lightCounterAt = null;
		if (isset($data['LIGHT_COUNTER_AT']))
		{
			$lightCounterAt = $data['LIGHT_COUNTER_AT'];
		}
		else
		{
			$lightCounterAt = ServiceLocator::getInstance()->get('crm.activity.actcounterlighttimerepo')->queryLightTimeByActivityId((int)$data['ID']);
		}
		if ($lightCounterAt instanceof DateTime)
		{
			$data['LIGHT_TIME_SERVER'] = date(
				'Y-m-d H:i:s',
				$lightCounterAt->getTimestamp()
			);
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
			'USER_ID' => $userId,
			'POSTPONE' => \CCrmActivity::CheckItemPostponePermission($data, $permissions),
			'COMPLETE' => \CCrmActivity::CheckItemCompletePermission($data, $permissions)
		);

		$typeID = isset($data['TYPE_ID']) ? $data['TYPE_ID'] : '';
		$providerID = isset($data['PROVIDER_ID']) ? $data['PROVIDER_ID'] : '';

		if($typeID === \CCrmActivityType::Call && $providerID === Activity\Provider\Call::ACTIVITY_PROVIDER_ID)
		{
			//LIKE VI_b298cc809d17d8ae.1475506018.843270c
			$originID = isset($data['ORIGIN_ID']) ? $data['ORIGIN_ID'] : '';
			if(mb_strpos($originID, 'VI_') !== false)
			{
				$callId = mb_substr($originID, 3);
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
					'MESSAGES' => \Bitrix\Crm\Integration\OpenLineManager::getSessionMessages($sessionID, 5)
				);
			}
		}
		elseif($providerID === Activity\Provider\Zoom::getId())
		{
			$conferenceId = isset($data['ASSOCIATED_ENTITY_ID']) ? (int)$data['ASSOCIATED_ENTITY_ID'] : 0;
			if ($conferenceId > 0 && Main\Loader::includeModule('socialservices'))
			{
				$conference = \Bitrix\Socialservices\ZoomMeetingTable::getRowById($conferenceId);
				if ($conference !== null)
				{
					$data['ZOOM_INFO'] = array(
						'CONF_START_TIME' => $conference['CONFERENCE_STARTED']->format('Y-m-d H:i:s'),
						'CONF_URL' => $conference['SHORT_LINK'],
						'DURATION' => $conference['DURATION'],
						'TOPIC' => $conference['TITLE'],
					);
				}
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
		else if($providerID === \Bitrix\Crm\Activity\Provider\CallTracker::getId())
		{
			\Bitrix\Crm\Activity\Provider\CallTracker::modifyScheduleEntityData($data, $options);
		}
		else if($providerID === \Bitrix\Crm\Activity\Provider\Delivery::getId())
		{
			$data['DELIVERY_INFO'] = \Bitrix\Crm\Activity\Provider\Delivery::getDeliveryInfo($data['ID']);
		}

		$model = array(
			'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
			'ASSOCIATED_ENTITY_ID' => $ID,
			'ASSOCIATED_ENTITY' => $data,
			'AUTHOR_ID' => isset($data['RESPONSIBLE_ID']) ? (int)$data['RESPONSIBLE_ID'] : 0,
			'sort' => $sort,
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

		if ($providerID !== Activity\Provider\ToDo::getId())
		{
			$notLimitedDescriptionProviders = [
				Activity\Provider\Call::getId(),
				Activity\Provider\Meeting::getId(),
			];
			$descriptionLimit = in_array($providerID, $notLimitedDescriptionProviders) ? 0 : 512;

			\CCrmActivity::PrepareDescriptionFields(
				$fields,
				['ENABLE_HTML' => false, 'ENABLE_BBCODE' => false, 'LIMIT' => $descriptionLimit]
			);
		}

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
			if(mb_strpos($originID, 'VI_') !== false)
			{
				$callId = mb_substr($originID, 3);
				$callInfo = Integration\VoxImplantManager::getCallInfo($callId);
				if(is_array($callInfo))
				{
					$fields['CALL_INFO'] = $callInfo;
				}
			}
		}
		elseif($providerID === Activity\Provider\Sms::getId())
		{
			// first, check original message fields
			$smsFields = Integration\SmsManager::getMessageFields($fields['ASSOCIATED_ENTITY_ID']);
			if (!$smsFields)
			{
				$smsFields = $fields['SETTINGS']['ORIGINAL_MESSAGE']; // check message fields stored in CRM
			}

			if (!empty($smsFields) && is_array($smsFields))
			{
				$fields['SMS_INFO'] = [
					'id' => $smsFields['ID'],
					'senderId' => $smsFields['SENDER_ID'],
					'senderShortName' => Integration\SmsManager::getSenderShortName($smsFields['SENDER_ID']),
					'from' => $smsFields['MESSAGE_FROM'],
					'fromName' => Integration\SmsManager::getSenderFromName(
						$smsFields['SENDER_ID'],
						$smsFields['MESSAGE_FROM']
					),
					'statusId' => $smsFields['STATUS_ID'],
					'errorText' => $smsFields['EXEC_ERROR'],
				];
			}
		}
		elseif($providerID === Activity\Provider\Notification::getId())
		{
			$fields['MESSAGE_INFO'] = Integration\NotificationsManager::getMessageByInfoId(
				(int)$fields['ASSOCIATED_ENTITY_ID']
			);
			$fields['PULL_TAG_NAME'] = Integration\NotificationsManager::getPullTagName();
		}
		elseif($providerID === Activity\Provider\Zoom::getId())
		{
			$conferenceId = isset($fields['ASSOCIATED_ENTITY_ID']) ? (int)$fields['ASSOCIATED_ENTITY_ID'] : 0;
			if ($conferenceId > 0 && Main\Loader::includeModule('socialservices'))
			{
				$conference = \Bitrix\Socialservices\ZoomMeetingTable::getRowById($conferenceId);
				if ($conference !== null)
				{
					$fields['ZOOM_INFO'] = array(
						'RECORDINGS' => \Bitrix\SocialServices\Integration\Zoom\Recording::getRecordings($conferenceId)->getData(),
						'CONF_START_TIME' => $conference['CONFERENCE_STARTED']->format('Y-m-d H:i:s'),
						'CONF_URL' => $conference['SHORT_LINK'],
						'DURATION' => $conference['DURATION'],
						'TOPIC' => $conference['TITLE'],
						'HAS_RECORDING' => $conference['HAS_RECORDING']
					);
				}

				$fields['ZOOM_INFO']['PROVIDER_TYPE_ID'] = $fields['PROVIDER_TYPE_ID'];
			}
		}
		elseif($providerID === Activity\Provider\OpenLine::getId())
		{
			$sessionID = isset($fields['ASSOCIATED_ENTITY_ID']) ? (int)$fields['ASSOCIATED_ENTITY_ID'] : 0;
			if($sessionID > 0)
			{
				$fields['OPENLINE_INFO'] = array(
					'MESSAGES' => \Bitrix\Crm\Integration\OpenLineManager::getSessionMessages($sessionID, 5)
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
		elseif($providerID === \Bitrix\Crm\Activity\Provider\CallTracker::getId())
		{
			\Bitrix\Crm\Activity\Provider\CallTracker::modifyTimelineEntityData($ID, $fields, $options);
		}
		else if($providerID === \Bitrix\Crm\Activity\Provider\Delivery::getId())
		{
			$fields['DELIVERY_INFO'] = \Bitrix\Crm\Activity\Provider\Delivery::getDeliveryInfo($fields['ID']);
		}

		if(isset($fields['STORAGE_ELEMENT_IDS']))
		{
			$mediaExtensions = array('wav', 'mp3', 'mp4');
			$storageTypeID = isset($fields['STORAGE_TYPE_ID'])
				? (int)$fields['STORAGE_TYPE_ID'] : Integration\StorageType::Undefined;

			$elementIDs = unserialize($fields['STORAGE_ELEMENT_IDS'], ['allowed_classes' => false]);
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
						$ext = GetFileExtension(mb_strtolower($info['NAME']));

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
				'STORAGE_TYPE_ID', 'STORAGE_ELEMENT_IDS', 'ORIGIN_ID', 'SETTINGS',
				'IS_INCOMING_CHANNEL',
				'LIGHT_COUNTER_AT',
			)
		);
		return is_object($dbResult) ? $dbResult->Fetch() : null;
	}
	public static function resolveAuthorID(array $fields)
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

	/**
	 * Send pull event if item needs to be moved from one stream to another
	 *
	 * @param ItemIdentifier $itemIdentifier
	 * @param int $fromActivityId
	 * @param int $toTimelineEntryId
	 * @param int|null $userId
	 * @return void
	 */
	protected function sendPullEventOnMove(
		ItemIdentifier $itemIdentifier,
		int $fromActivityId,
		int $toTimelineEntryId,
		int $userId = null
	)
	{
		if (!Container::getInstance()->getTimelinePusher()->isDetailsPageChannelActive($itemIdentifier))
		{
			return;
		}

		$itemTo = $this->createItemByTimelineEntryId(
			new Context($itemIdentifier, Context::PULL, $userId),
			$toTimelineEntryId
		);
		if ($itemTo)
		{
			(new Crm\Service\Timeline\Item\Pusher($itemTo))->sendMoveEvent(
				Crm\Service\Timeline\Item\Pusher::STREAM_SCHEDULED,
				Crm\Service\Timeline\Item\Model::getScheduledActivityModelId($fromActivityId)
			);
		}
	}

	/**
	 * Send pull event about scheduled item creation
	 *
	 * @param ItemIdentifier $itemIdentifier
	 * @param array $scheduledData
	 * @param int|null $userId
	 * @return void
	 */
	public function sendPullEventOnAddScheduled(ItemIdentifier $itemIdentifier, array $scheduledData, int $userId = null): void
	{
		if (!Container::getInstance()->getTimelinePusher()->isDetailsPageChannelActive($itemIdentifier))
		{
			return;
		}

		$item = $this->createItemByScheduledData(
			new Context($itemIdentifier, Context::PULL, $userId),
			$scheduledData
		);
		if ($item)
		{
			(new Crm\Service\Timeline\Item\Pusher($item))->sendAddEvent();
		}
	}

	/**
	 * Send pull event about scheduled item modification
	 *
	 * @param ItemIdentifier $itemIdentifier
	 * @param array $scheduledData
	 * @param int|null $userId
	 * @return void
	 */
	public function sendPullEventOnUpdateScheduled(ItemIdentifier $itemIdentifier, array $scheduledData, int $userId = null): void
	{
		if (!Container::getInstance()->getTimelinePusher()->isDetailsPageChannelActive($itemIdentifier))
		{
			return;
		}

		$item = $this->createItemByScheduledData(
			new Context($itemIdentifier, Context::PULL, $userId),
			$scheduledData
		);
		if ($item)
		{
			(new Crm\Service\Timeline\Item\Pusher($item))->sendUpdateEvent();
		}
	}

	/**
	 * Send pull event about scheduled item deletion
	 *
	 * @param ItemIdentifier $itemIdentifier
	 * @param int $scheduledActivityId
	 * @param int|null $userId
	 * @return void
	 */
	public function sendPullEventOnDeleteScheduled(ItemIdentifier $itemIdentifier, int $scheduledActivityId, int $userId = null): void
	{
		if (!Container::getInstance()->getTimelinePusher()->isDetailsPageChannelActive($itemIdentifier))
		{
			return;
		}

		$item = Container::getInstance()->getTimelineScheduledItemFactory()::createEmptyItem(
			new Context($itemIdentifier, Context::PULL, $userId),
			$scheduledActivityId
		);

		(new \Bitrix\Crm\Service\Timeline\Item\Pusher($item))->sendDeleteEvent();
	}

	/**
	 * Create timeline Item object by array with activity data
	 *
	 * @param Context $context
	 * @param int $timelineEntryId
	 * @return \Bitrix\Crm\Service\Timeline\Item|null
	 */
	protected function createItemByScheduledData(Context $context, array $scheduledData):
		?\Bitrix\Crm\Service\Timeline\Item
	{
		if (empty($scheduledData))
		{
			return null;
		}

		return Container::getInstance()->getTimelineScheduledItemFactory()::createItem(
			$context,
			$scheduledData
		);
	}

	final public function notifyTimelinesAboutActivityUpdate(
		array $activity,
		?int $userId = null,
		bool $forceUpdateHistoryItems = false
	): void
	{
		$bindings = \CCrmActivity::GetBindings($activity['ID']);
		if (!$bindings)
		{
			return;
		}

		$this->notifyTimelinesAboutActivityUpdateForBindings($activity, $bindings, $userId, $forceUpdateHistoryItems);
	}

	protected function notifyTimelinesAboutActivityUpdateForBindings(array $activity, array $bindings, ?int $userId = null, bool $forceUpdateHistoryItems = false): void
	{
		$identifiers = array_map(
			fn(array $binding) => ItemIdentifier::createFromArray($binding),
			$bindings,
		);
		$identifiers = array_filter($identifiers);

		if (empty($identifiers))
		{
			return;
		}

		$activityId = (int)$activity['ID'];

		if (!array_key_exists('IS_INCOMING_CHANNEL', $activity))
		{
			$activity['IS_INCOMING_CHANNEL'] = \Bitrix\Crm\Activity\IncomingChannel::getInstance()->isIncomingChannel($activityId) ? 'Y' : 'N';
		}
		if (!array_key_exists('LIGHT_COUNTER_AT', $activity))
		{
			$activity['LIGHT_COUNTER_AT'] = ServiceLocator::getInstance()->get('crm.activity.actcounterlighttimerepo')->queryLightTimeByActivityId($activityId);
		}

		$isCompleted = ($activity['COMPLETED'] ?? 'N') === 'Y';
		if ($isCompleted || $forceUpdateHistoryItems)
		{
			$timelineEntryIds = TimelineEntry::getEntriesIdsByAssociatedEntity(
				\CCrmOwnerType::Activity,
				$activityId,
				self::MAX_SIMULTANEOUS_PULL_EVENT_COUNT,
			);

			foreach ($timelineEntryIds as $timelineEntryId)
			{
				foreach ($identifiers as $identifier)
				{
					$this->sendPullEventOnUpdate($identifier, $timelineEntryId, $userId);
				}
			}
		}

		if (!$isCompleted)
		{
			$scheduledData = $activity;
			$items = [$activityId => &$scheduledData];

			self::loadCommunicationsAndMultifields(
				$items,
				Crm\Service\Container::getInstance()->getUserPermissions($userId)->getCrmPermissions(),
				[
					'ENABLE_PERMISSION_CHECK' => false,
				]
			);
			$items = NoteTable::loadForItems($items, NoteTable::NOTE_TYPE_ACTIVITY);

			foreach ($identifiers as $identifier)
			{
				$this->sendPullEventOnUpdateScheduled($identifier, $scheduledData, $userId);
			}
		}
	}
}