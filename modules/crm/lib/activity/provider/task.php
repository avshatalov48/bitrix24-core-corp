<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\Activity;
use Bitrix\Crm\Integration;
use Bitrix\Tasks\Integration\CRM\TimeLineManager;

Loc::loadMessages(__FILE__);

class Task extends Activity\Provider\Base
{
	private static $creationParams = array();
	private static $deletionParams = array();

	private const LOCK_TYPE_UPDATE   = 'U';
	private const LOCK_TYPE_DELETE   = 'D';
	private const LOCK_TYPE_COMPLETE = 'C';

	private static $locked = array();

	private static function lockTask($taskId, $lockType)
	{
		self::$locked[$taskId] = $lockType;
	}

	private static function unlockTask($taskId)
	{
		unset(self::$locked[$taskId]);
	}

	private static function isTaskLocked($taskId, $lockType)
	{
		return isset(self::$locked[$taskId]) && self::$locked[$taskId] === $lockType;
	}

	public static function getId()
	{
		return 'TASKS';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_TASK_NAME');
	}

	public static function isActive()
	{
		return Main\ModuleManager::isModuleInstalled('tasks');
	}

	public static function getTypeId(array $activity)
	{
		return 'TASK';
	}

	/**
	 * @param string $action Action ADD or UPDATE.
	 * @param array $fields Activity fields.
	 * @param int $id Activity ID.
	 * @param null|array $params Additional parameters.
	 * @return Main\Result Check fields result.
	 */
	public static function checkFields($action, &$fields, $id, $params = null)
	{
		$previousFields = isset($params['PREVIOUS_FIELDS']) && is_array($params['PREVIOUS_FIELDS'])
			? $params['PREVIOUS_FIELDS'] : array();


		$result = new Main\Result();
		if (
			$action === self::ACTION_UPDATE
			&& isset($fields['COMPLETED'])
			&& $fields['COMPLETED'] === 'Y'
			&& empty($previousFields['END_TIME'])
		)
		{
			$end = new Main\Type\DateTime();
			$fields['END_TIME'] = $end->toString();
		}

		//Only END TIME can be taken for DEADLINE!
		if(isset($fields['END_TIME']))
		{
			if($fields['END_TIME'] !== '')
			{
				$fields['DEADLINE'] = $fields['END_TIME'];
			}
			else
			{
				$fields['~DEADLINE'] = \CCrmDateTimeHelper::GetMaxDatabaseDate();
			}
		}

		return $result;
	}

	public static function updateAssociatedEntity(
		$entityId,
		array $activity,
		array $options = array()
	)
	{
		// nasty, very nasty.
		$skipResponsibleChange = isset($options['SKIP_RESPONSIBLE_CHANGE']) && $options['SKIP_RESPONSIBLE_CHANGE'] === true;
		$skipDeadlineChange = isset($options['SKIP_DEADLINE_CHANGE']) && $options['SKIP_DEADLINE_CHANGE'] === true;
		$skipRenew = isset($options['SKIP_RENEW']) && $options['SKIP_RENEW'] === true;
		$skipSubject = isset($options['SKIP_SUBJECT']) && $options['SKIP_SUBJECT'] === true;
		$responsibleId = isset($activity['RESPONSIBLE_ID']) ? (int)$activity['RESPONSIBLE_ID'] : 0;
		$entityId = (int) $entityId;

		if ($responsibleId <= 0 || $entityId <= 0 || !Loader::includeModule('tasks'))
		{
			return false;
		}

		$itemIterator = \CTasks::getByID($entityId, false);
		$task = $itemIterator->fetch();

		$taskFields = array();
		if (!$skipSubject && isset($activity['SUBJECT']) && $activity['SUBJECT'] !== $task['TITLE'])
		{
			$taskFields['TITLE'] = $activity['SUBJECT'];
			$taskFields['DESCRIPTION'] = $task['DESCRIPTION']; //for TAGS save
		}
		if (!$skipDeadlineChange && isset($activity['END_TIME'] ))
		{
			$taskFields['DEADLINE'] = $activity['END_TIME'];
		}
		if (!$skipResponsibleChange && isset($activity['RESPONSIBLE_ID']))
		{
			$taskFields['RESPONSIBLE_ID'] = $activity['RESPONSIBLE_ID'];
		}

		$result = true;
		self::lockTask($entityId, self::LOCK_TYPE_UPDATE);
		if (!empty($taskFields))
		{
			$task = new \CTasks();
			$result = $task->update($entityId, $taskFields);
		}
		self::unlockTask($entityId);

		if (isset($activity['COMPLETED']))
		{
			self::lockTask($entityId, self::LOCK_TYPE_COMPLETE);
			try
			{
				$currentUser = isset($options['CURRENT_USER'])
					? (int)$options['CURRENT_USER']
					: \CCrmSecurityHelper::getCurrentUserID();

				$taskItem = \CTaskItem::getInstance($entityId, $currentUser > 0 ? $currentUser : 1);
				if($activity['COMPLETED'] === 'Y')
				{
					$taskItem->complete(['SKIP_ACCESS_CONTROL' => true]);
				}
				elseif (!$skipRenew)
				{
					$taskItem->renew(['SKIP_ACCESS_CONTROL' => true]);
				}
				$result = true;
			}
			catch (\TasksException $e)
			{
				$result = false;
			}
			self::unlockTask($entityId);
		}

		$updateResult = new Main\Result();

		if (!$result)
		{
			$updateResult->addError(new Main\Error('Failed.'));
		}
		elseif(isset($activity['COMPLETED'])
			&& $activity['COMPLETED'] === 'Y'
			&& Crm\Settings\ActivitySettings::getCurrent()->isDeadlineSyncEnabled()
		)
		{
			//Perform synchronization of 'DEADLINE' field
			$itemIterator = \CTasks::getByID($entityId, false);
			$taskFields = $itemIterator->fetch();
			if(is_array($taskFields) && !empty($taskFields['CLOSED_DATE']))
			{
				$activity['DEADLINE'] = $activity['END_TIME'] = $taskFields['CLOSED_DATE'];
				\CCrmActivity::update(
					$activity['ID'],
					$activity,
					false,
					true,
					array('SKIP_ASSOCIATED_ENTITY' => true, 'REGISTER_SONET_EVENT' => true)
				);
				\CCrmLiveFeed::syncTaskEvent($activity, $taskFields);
			}
		}

		return $updateResult;
	}

	public static function rebindAssociatedEntity($entityId, $oldOwnerTypeId, $newEntityTypeId, $oldOwnerId, $newOwnerId)
	{
		$entityId = (int)$entityId;
		if ($entityId <= 0 || !Loader::includeModule('tasks'))
		{
			return;
		}

		$task = \CTasks::getByID($entityId, false)->fetch();
		if(!is_array($task))
		{
			return;
		}

		$entityBindings = isset($task['UF_CRM_TASK']) && is_array($task['UF_CRM_TASK']) ? $task['UF_CRM_TASK'] : array();
		$entityIndex = -1;
		for($i = 0, $length = count($entityBindings); $i < $length; $i++)
		{
			$entityInfo = \CCrmOwnerType::ParseEntitySlug($entityBindings[$i]);
			if(is_array($entityInfo)
				&& $entityInfo['ENTITY_TYPE_ID'] === $oldOwnerTypeId
				&& $entityInfo['ENTITY_ID'] === $oldOwnerId
			)
			{
				$entityIndex = $i;
				break;
			}
		}

		if($entityIndex >= 0)
		{
			$entityBindings[$entityIndex] = \CCrmOwnerTypeAbbr::ResolveByTypeID($newEntityTypeId).'_'.$newOwnerId;

			$taskEntity = new \CTasks();
			$taskEntity->Update($entityId, ['UF_CRM_TASK' => $entityBindings]);
		}
	}

	public static function bindExternalEntity($entityId, array $bindings)
	{
		$entityId = (int) $entityId;
		if ($entityId <= 0 || !Loader::includeModule('tasks'))
		{
			return false;
		}

		$task = \CTasks::getByID($entityId, false)->fetch();
		if(!is_array($task))
		{
			return false;
		}

		$ownerMap = array_fill_keys(
			isset($task['UF_CRM_TASK']) && is_array($task['UF_CRM_TASK']) ? $task['UF_CRM_TASK'] : array(),
			true
		);

		foreach($bindings as $binding)
		{
			$ownerTypeID = isset($binding['OWNER_TYPE_ID']) ? (int)$binding['OWNER_TYPE_ID'] : 0;
			$ownerID = isset($binding['OWNER_ID']) ? (int)$binding['OWNER_ID'] : 0;

			if(!\CCrmOwnerType::IsDefined($ownerTypeID) || $ownerID <= 0)
			{
				continue;
			}

			$ownerKey = \CCrmOwnerTypeAbbr::ResolveByTypeID($ownerTypeID).'_'.$ownerID;
			if(!isset($ownerMap[$ownerKey]))
			{
				$ownerMap[$ownerKey] = true;
			}
		}

		$taskEntity = new \CTasks();
		$taskEntity->Update($entityId, ['UF_CRM_TASK'=>array_keys($ownerMap)]);

		return true;
	}

	public static function unbindExternalEntity($entityId)
	{
		$entityId = (int) $entityId;
		if ($entityId <= 0 || !Loader::includeModule('tasks'))
		{
			return false;
		}

		$task = \CTasks::getByID($entityId, false)->fetch();
		if(!(is_array($task) && isset($task['UF_CRM_TASK'])))
		{
			return false;
		}

		$taskEntity = new \CTasks();
		$taskEntity->Update($entityId, ['UF_CRM_TASK'=>[]]);

		return true;
	}

	public static function deleteAssociatedEntity($entityId, array $activity, array $options = array())
	{
		if (isset($options['SKIP_TASKS']) && $options['SKIP_TASKS'] === true)
			return new Main\Result();

		$entityId = (int) $entityId;
		if ($entityId <= 0 || !Loader::includeModule('tasks'))
		{
			return false;
		}

		self::lockTask($entityId, self::LOCK_TYPE_DELETE);
		\CTasks::delete($entityId);
		self::unlockTask($entityId);

		return new Main\Result();
	}

	//event listeners
	public static function onTaskDelete($taskId)
	{
		$taskId = (int)$taskId;
		if (self::isTaskLocked($taskId, self::LOCK_TYPE_DELETE))
		{
			return;
		}

		$iterator = \CCrmActivity::getList(
			array(),
			array(
				'=TYPE_ID' =>  \CCrmActivityType::Task,
				'=ASSOCIATED_ENTITY_ID' => $taskId,
				'CHECK_PERMISSIONS' => 'N'
			),
			false,
			false,
			array('ID')
		);

		while ($entity = $iterator->fetch())
		{
			\CCrmActivity::delete(
				$entity['ID'],
				false,
				true,
				array(
					'SKIP_ASSOCIATED_ENTITY' => true,
					'ENABLE_RECYCLE_BIN' => false
				)
			);
		}
	}

	public static function onTaskUpdate($taskId, &$currentTaskFields, &$previousTaskFields)
	{
		$taskId = (int)$taskId;
		if (self::isTaskLocked($taskId, self::LOCK_TYPE_UPDATE))
		{
			return false;
		}

		if ($taskId <= 0 || !Loader::includeModule('tasks'))
		{
			return false;
		}

		$itemIterator = \CTasks::getByID($taskId, false);
		$task = $itemIterator->fetch();
		if(!$task)
		{
			return false;
		}

		$isStatusChanged = (isset($currentTaskFields['STATUS']) && (string)$currentTaskFields['STATUS'] !== (string)$previousTaskFields['STATUS']);
		$listIterator = \CCrmActivity::getList(
			array(),
			array(
				'=TYPE_ID' =>  \CCrmActivityType::Task,
				'=ASSOCIATED_ENTITY_ID' => $taskId,
				'CHECK_PERMISSIONS' => 'N'
			)
		);

		if (self::isTaskLocked($taskId, self::LOCK_TYPE_COMPLETE))
		{
			if ($isStatusChanged && $activity = $listIterator->fetch())
			{
				self::setFromTask($taskId, $task, $activity);
				if (isset($activity['BINDINGS']) && count($activity['BINDINGS']) > 0)
				{
					Crm\Automation\Trigger\TaskStatusTrigger::execute($activity['BINDINGS'], ['TASK' => $task]);
				}
			}

			return false;
		}

		$isFound = false;
		$taskBindings = [];

		while($activity = $listIterator->fetch())
		{
			$isFound = true;
			self::setFromTask($taskId, $task, $activity);
			// Update activity if bindings are found otherwise delete unbound activity
			if(isset($activity['BINDINGS']) && count($activity['BINDINGS']) > 0)
			{
				$activity['COMMUNICATIONS'] = self::prepareCommunications($activity);
				\CCrmActivity::update($activity['ID'], $activity, false, true, array('SKIP_ASSOCIATED_ENTITY' => true, 'REGISTER_SONET_EVENT' => true));
				\CCrmLiveFeed::syncTaskEvent($activity, $task);
				$taskBindings = $activity['BINDINGS'];
			}
			else
			{
				\CCrmLiveFeed::revertTasksLogEvent(array(
					"ACTIVITY" => $activity,
					"TASK" => $task
				));

				$deletionParams = isset(self::$deletionParams[$taskId]) && is_array(self::$deletionParams[$taskId])
					? self::$deletionParams[$taskId] : array();
				unset(self::$deletionParams[$taskId]);

				\CCrmActivity::delete(
					$activity['ID'],
					false,
					true,
					array_merge($deletionParams, array('SKIP_ASSOCIATED_ENTITY' => true))
				);
			}

			/*
			\Bitrix\Crm\Timeline\TaskController::getInstance()->onModify(
				$activity['ID'],
				array(
					'CURRENT_TASK_FIELDS' => $currentTaskFields,
					'PREVIOUS_TASK_FIELDS' => $previousTaskFields,
					'BINDINGS' => isset($activity['BINDINGS']) ? $activity['BINDINGS'] : array()
				)
			);
			*/
		}

		if(!$isFound && !TimeLineManager::isNewIntegrationEnabled())
		{
			$activity = array();
			self::setFromTask($taskId, $task, $activity);
			if(isset($activity['BINDINGS']) && count($activity['BINDINGS']) > 0)
			{
				$creationParams = isset(self::$creationParams[$taskId]) && is_array(self::$creationParams[$taskId])
					? self::$creationParams[$taskId] : array();
				unset(self::$creationParams[$taskId]);

				//Stub for communication is required for activity list optimization (see \CCrmActivity::PrepareClientInfos)
				$activity['COMMUNICATIONS'] = self::prepareCommunications($activity);
				\CCrmActivity::add(
					$activity,
					false,
					true,
					array_merge($creationParams, array('SKIP_ASSOCIATED_ENTITY' => true, 'REGISTER_SONET_EVENT' => true))
				);
				$taskBindings = $activity['BINDINGS'];
			}
		}

		if ($isStatusChanged && $taskBindings)
		{
			Crm\Automation\Trigger\TaskStatusTrigger::execute($taskBindings, ['TASK' => $task]);
		}

		return true;
	}

	public static function createFromTask(
		$taskId,
		&$taskFields,
		$checkPerms = true,
		$regEvent = true)
	{
		$entityCount = \CCrmActivity::getList(
			array(),
			array(
				'=TYPE_ID' =>  \CCrmActivityType::Task,
				'=ASSOCIATED_ENTITY_ID' => $taskId,
				'CHECK_PERMISSIONS' => 'N'
			),
			array(),
			false,
			false
		);

		if(is_int($entityCount) && $entityCount > 0)
		{
			return false;
		}

		$activity = array();
		self::setFromTask($taskId, $taskFields, $activity);
		if(isset($activity['BINDINGS']) && count($activity['BINDINGS']) > 0)
		{
			//Stub for communication is required for activity list optimization (see \CCrmActivity::PrepareClientInfos)
			$activity['COMMUNICATIONS'] = self::prepareCommunications($activity);
			return \CCrmActivity::Add($activity, $checkPerms, $regEvent, array('SKIP_ASSOCIATED_ENTITY' => true, 'REGISTER_SONET_EVENT' => true));
		}

		return false;
	}

	public static function onBeforeTaskAdd(&$taskFields)
	{
		//Search for undefined or default title
		$title = isset($taskFields['TITLE']) ? trim($taskFields['TITLE']) : '';
		if($title !== '' && preg_match('/^\s*CRM\s*:\s*$/i', $title) !== 1)
		{
			return;
		}

		$taskOwners =  isset($taskFields['UF_CRM_TASK']) ? $taskFields['UF_CRM_TASK'] : array();
		if(!is_array($taskOwners))
		{
			$taskOwners  = array($taskOwners);
		}

		$ownerData = array();
		if(\CCrmActivity::tryResolveUserFieldOwners($taskOwners, $ownerData, \CCrmUserType::getTaskBindingField()))
		{
			$ownerInfo = $ownerData[0];
			$taskFields['TITLE'] = 'CRM: '.\CCrmOwnerType::getCaption(
					\CCrmOwnerType::resolveID($ownerInfo['OWNER_TYPE_NAME']),
					$ownerInfo['OWNER_ID']
				);
		}
	}

	public static function onTaskAdd($taskID, &$taskFields)
	{
		return self::createFromTask($taskID, $taskFields, false, true);
	}

	private static function setFromTask($taskId, &$taskFields, &$activity)
	{
		$isNew = !(isset($activity['ID']) && intval($activity['ID']) > 0);
		if($isNew)
		{
			$activity['TYPE_ID'] =  \CCrmActivityType::Task;
			$activity['ASSOCIATED_ENTITY_ID'] = $taskId;
			$activity['NOTIFY_TYPE'] = \CCrmActivityNotifyType::None;
			$activity['RESPONSIBLE_ID'] = 0;
			$activity['SUBJECT'] = '';

			if(isset($taskFields['CREATED_BY']))
			{
				$activity['AUTHOR_ID'] = intval($taskFields['CREATED_BY']);
			}
		}

		if(isset($taskFields['CHANGED_BY']))
		{
			$activity['EDITOR_ID'] = intval($taskFields['CHANGED_BY']);
		}

		if(isset($taskFields['RESPONSIBLE_ID']))
		{
			$activity['RESPONSIBLE_ID'] = intval($taskFields['RESPONSIBLE_ID']);
		}

		if(isset($taskFields['TITLE']))
		{
			$activity['SUBJECT'] = $taskFields['TITLE'];
		}

		if($isNew || isset($taskFields['PRIORITY']))
		{
			// Try to convert 'task priority' to 'crm activity priority'
			$priorityText = isset($taskFields['PRIORITY']) ? strval($taskFields['PRIORITY']) : '0';
			$priority = \CCrmActivityPriority::Low;
			if($priorityText === '1')
			{
				$priority = \CCrmActivityPriority::Medium;
			}
			elseif($priorityText === '2')
			{
				$priority = \CCrmActivityPriority::High;
			}

			$activity['PRIORITY'] = $priority;
		}

		if($isNew || isset($taskFields['STATUS']))
		{
			// Try to find status
			$completed = 'N';
			if(isset($taskFields['STATUS']))
			{
				$status = intval($taskFields['STATUS']);
				// COMPLETED: 5, DECLINED: 7
				if($status === 5 || $status === 7)
				{
					$completed = 'Y';
				}
			}
			$activity['COMPLETED'] = $completed;
			$activity['STATUS'] = $completed === 'Y' ? \CCrmActivityStatus::Completed : \CCrmActivityStatus::Waiting;
		}

		$start = null;
		$end = null;

		if(isset($taskFields['DATE_START']))
		{
			// Try to find start date
			if(!empty($taskFields['DATE_START']))
			{
				$start = $taskFields['DATE_START'];
			}
		}

		$isCompleted = isset($activity['COMPLETED']) && $activity['COMPLETED'] === 'Y';
		if(isset($taskFields['DEADLINE']) || isset($taskFields['CLOSED_DATE']))
		{
			if(Crm\Settings\ActivitySettings::getCurrent()->isDeadlineSyncEnabled())
			{
				if(!$isCompleted && !empty($taskFields['DEADLINE']))
				{
					$end = $taskFields['DEADLINE'];
				}
				elseif($isCompleted && !empty($taskFields['CLOSED_DATE']))
				{
					$end = $taskFields['CLOSED_DATE'];
				}
			}
			elseif(!empty($taskFields['DEADLINE']))
			{
				$end = $taskFields['DEADLINE'];
			}
		}

		$activity['START_TIME'] = $start !== null ? $start : '';
		$activity['END_TIME'] = $end !== null ? $end : '';
		if($activity['START_TIME'] === '' && $activity['END_TIME'] !== '')
		{
			$activity['START_TIME'] = $activity['END_TIME'];
		}
		elseif($isCompleted && $activity['END_TIME'] === '' && $activity['START_TIME'] !== '')
		{
			$activity['END_TIME'] = $activity['START_TIME'];
		}
		/*
		elseif(!$isCompleted && $activity['END_TIME'] === '')
		{
			$fields['~DEADLINE'] = \CCrmDateTimeHelper::GetMaxDatabaseDate();
		}
		*/

		if($isNew || isset($taskFields['DESCRIPTION']))
		{
			$description = isset($taskFields['DESCRIPTION']) ? $taskFields['DESCRIPTION'] : '';
			$descriptionType =
				isset($taskFields['DESCRIPTION_IN_BBCODE']) && $taskFields['DESCRIPTION_IN_BBCODE'] === 'Y'
					? \CCrmContentType::BBCode
					: \CCrmContentType::Html;

			if($description !== '' && $descriptionType === \CCrmContentType::Html)
			{
				$sanitizer = new \CBXSanitizer();
				$sanitizer->applyDoubleEncode(false);
				$sanitizer->setLevel(\CBXSanitizer::SECURE_LEVEL_MIDDLE);
				$description = $sanitizer->sanitizeHtml($description);
			}

			if($description === '')
			{
				//Ignore content type if description is empty
				$descriptionType = \CCrmContentType::PlainText;
			}

			$activity['DESCRIPTION'] = $description;
			$activity['DESCRIPTION_TYPE'] = $descriptionType;
		}

		$taskOwners =  isset($taskFields['UF_CRM_TASK']) ? $taskFields['UF_CRM_TASK'] : array();
		$ownerData = array();

		if(!is_array($taskOwners))
		{
			$taskOwners  = array($taskOwners);
		}

		$activity['BINDINGS'] = array();
		if(\CCrmActivity::tryResolveUserFieldOwners($taskOwners, $ownerData, \CCrmUserType::getTaskBindingField()))
		{
			$bindingMap = array();
			foreach($ownerData as $ownerInfo)
			{
				$ownerTypeId = \CCrmOwnerType::resolveID($ownerInfo['OWNER_TYPE_NAME']);
				$ownerId = (int)$ownerInfo['OWNER_ID'];

				$bindingMap["{$ownerTypeId}_{$ownerId}"] = array(
					'OWNER_TYPE_ID' => $ownerTypeId,
					'OWNER_ID' => $ownerId
				);
				$bindingMap = array_merge(
					$bindingMap,
					\CCrmActivity::getSubsidiaryEntityBindingMap($ownerTypeId, $ownerId)
				);
			}
			$bindings = array_values($bindingMap);
			if(count($bindings) > 1)
			{
				//Lead and Deals will be at beginning of list for take activity ownership
				usort(
					$bindings,
					function($a, $b)
					{
						if($a['OWNER_TYPE_ID'] == $b['OWNER_TYPE_ID'])
						{
							return 0;
						}
						return $a['OWNER_TYPE_ID'] > $b['OWNER_TYPE_ID'] ? 1 : -1;
					}
				);
			}
			$activity['BINDINGS'] = $bindings;

		}

		if(!empty($activity['BINDINGS']))
		{
			//Check for owner change
			$ownerTypeId = isset($activity['OWNER_TYPE_ID']) ? (int)$activity['OWNER_TYPE_ID'] : \CCrmOwnerType::Undefined;
			$ownerId = isset($activity['OWNER_ID']) ? (int)$activity['OWNER_ID'] : 0;
			$ownerIsFound = false;
			foreach($activity['BINDINGS'] as $binding)
			{
				if($binding['OWNER_TYPE_ID'] === $ownerTypeId && $binding['OWNER_ID'] === $ownerId)
				{
					$ownerIsFound = true;
					break;
				}
			}

			if(!$ownerIsFound)
			{
				$binding = $activity['BINDINGS'][0];
				$activity['OWNER_TYPE_ID'] = $binding['OWNER_TYPE_ID'];
				$activity['OWNER_ID'] = $binding['OWNER_ID'];
			}


		}
	}

	public static function createLiveFeedLog($entityId, array $activity, array &$logFields)
	{
		$entityId = (int) $entityId;
		$activityId = isset($activity['ID']) ? (int)$activity['ID'] : 0;
		if ($entityId <= 0 || !Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$eventId = 0;

		$dbTask = \CTasks::getByID($entityId, false);
		if ($task = $dbTask->fetch())
		{
			$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->getUserFieldValue("TASKS_TASK", "UF_TASK_WEBDAV_FILES", $task["ID"], LANGUAGE_ID);
			if ($ufDocID)
			{
				$logFields["UF_SONET_LOG_DOC"] = $ufDocID;
			}
		}

		if ($task)
		{
			$rsLog = \CSocNetLog::getList(
				array(),
				array(
					"EVENT_ID" => "tasks",
					"SOURCE_ID" => $task["ID"]
				),
				array("ID")
			);
			if ($arLog = $rsLog->fetch())
			{
				$eventId = (int)\CCrmLiveFeed::convertTasksLogEvent(array(
					"LOG_ID" => $arLog["ID"],
					"ACTIVITY_ID" => $activityId,
					"PARENTS" => (!empty($logFields['PARENTS']) ? $logFields['PARENTS'] : array())
				));
			}
			elseif (!empty($task['GROUP_ID']))
			{
				$arSite = array();
				$rsGroupSite = \CSocNetGroup::getSite(intval($task['GROUP_ID']));
				if ($rsGroupSite)
				{
					while($arGroupSite = $rsGroupSite->fetch())
					{
						$arSite[] = $arGroupSite["LID"];
					}
				}
				if (!empty($arSite))
				{
					$logFields['SITE_ID'] = $arSite;
				}
			}
		}

		if ($eventId === 0)
		{
			if ($task['CREATED_BY'] > 0)
			{
				$logFields['USER_ID'] = $task['CREATED_BY'];
			}
			$eventId = \CCrmLiveFeed::createLogEvent($logFields, \CCrmLiveFeedEvent::Add, array('ACTIVITY_PROVIDER_ID' => self::getId()));
		}

		if ($task && $eventId > 0)
		{
			$arTaskParticipant = \CTaskNotifications::getRecipientsIDs($task, false);// don't exclude current user

			$arSocnetRights = \CTaskNotifications::__userIDs2Rights($arTaskParticipant);

			if (
				isset($task['GROUP_ID'])
				&& intval($task['GROUP_ID']) > 0
			)
			{
				$arSocnetRights = array_merge(
					$arSocnetRights,
					array('SG'.$task['GROUP_ID'])
				);
			}

			\CSocNetLogRights::DeleteByLogID($eventId);
			\CSocNetLogRights::Add($eventId, $arSocnetRights);
		}

		return $eventId;
	}

	/**
	 * @param int $entityId Associated entity id.
	 * @param array $activity Activity data.
	 * @param int $userId Target user id.
	 * @return null|bool
	 */
	public static function checkCompletePermission($entityId, array $activity, $userId)
	{
		$entityId = (int)$entityId;
		if($entityId <= 0)
		{
			return null;
		}

		return (isset($activity['COMPLETED']) && $activity['COMPLETED'] === 'Y')
			? Integration\TaskManager::checkRenewPermission($entityId, $userId)
			: Integration\TaskManager::checkCompletePermission($entityId, $userId);
	}

	public static function checkPostponePermission($entityId, array $activity, $userId)
	{
		$entityId = (int)$entityId;
		if($entityId <= 0)
		{
			return null;
		}

		return \Bitrix\Crm\Integration\TaskManager::checkUpdatePermission($entityId, $userId);
	}

	public static function tryPostpone($offset, array $fields, array &$updateFields, $checkPermissions = true)
	{
		if(!is_numeric($offset))
		{
			$offset = (int)$offset;
		}

		if(!Loader::includeModule('tasks'))
		{
			return false;
		}

		$entityID = isset($fields['ASSOCIATED_ENTITY_ID']) ? (int)$fields['ASSOCIATED_ENTITY_ID'] : 0;
		if($entityID <= 0)
		{
			return false;
		}

		if($checkPermissions && !\Bitrix\Crm\Integration\TaskManager::checkUpdatePermission($entityID))
		{
			return false;
		}

		$entry = new \CTasks();
		$dbResult = $entry->GetByID($entityID, false);
		$fields = $dbResult->Fetch();
		if(!is_array($fields))
		{
			return false;
		}

		$deadline = isset($fields['DEADLINE']) ? $fields['DEADLINE'] : '';
		if($deadline === '')
		{
			return false;
		}

		$now = time() + \CTimeZone::GetOffset();
		$result = $entry->update(
			$entityID,
			array('DEADLINE' => FormatDate('FULL', MakeTimeStamp($deadline) + $offset, $now))
		);

		$updateFields = array();
		return $result;
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @return bool
	 */
	public static function canUseLiveFeedEvents($providerTypeId = null)
	{
		return true;
	}

	private static function prepareCommunications(array $activity)
	{
		$bindings = isset($activity['BINDINGS']) && is_array($activity['BINDINGS']) ? $activity['BINDINGS'] : array();
		if(empty($bindings))
		{
			return array();
		}

		$communications = array();
		foreach($bindings as $binding)
		{
			$ownerID = isset($binding['OWNER_ID']) ? (int)$binding['OWNER_ID'] : 0;
			$ownerTypeID = isset($binding['OWNER_TYPE_ID']) ? (int)$binding['OWNER_TYPE_ID'] : 0;
			if($ownerID > 0
				&& ($ownerTypeID === \CCrmOwnerType::Contact || $ownerTypeID === \CCrmOwnerType::Company || $ownerTypeID === \CCrmOwnerType::Lead))
			{
				$communication = array('ENTITY_ID' => $ownerID, 'ENTITY_TYPE_ID' => $ownerTypeID);
				\CCrmActivity::PrepareCommunicationInfo($communication);

				if(isset($communication['ENTITY_SETTINGS']))
				{
					$communications[] = $communication;
					break;
				}
			}
		}

		return $communications;
	}

	protected static function resolveActivityID($associatedEntityID)
	{
		$dbResult = \CCrmActivity::getList(
			array(),
			array(
				'=TYPE_ID' =>  \CCrmActivityType::Task,
				'=ASSOCIATED_ENTITY_ID' => $associatedEntityID,
				'CHECK_PERMISSIONS' => 'N'
			),
			false,
			false,
			array('ID')
		);

		$fields = $dbResult->Fetch();
		return is_array($fields) ? (int)$fields['ID'] : 0;
	}

	public static function processMovingToRecycleBin(array $activityFields, array $params = null)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$activityTypeID = isset($activityFields['TYPE_ID'])
			? (int)$activityFields['TYPE_ID'] : \CCrmActivityType::Undefined;

		$associatedEntityID = isset($activityFields['ASSOCIATED_ENTITY_ID'])
			? (int)$activityFields['ASSOCIATED_ENTITY_ID'] : 0;

		$providerId = $activityFields['PROVIDER_ID'] ?? null;

		$result = new Main\Result();
		if(
			$associatedEntityID > 0
			&& (
				$activityTypeID === \CCrmActivityType::Task ||
				(
					$activityTypeID === \CCrmActivityType::Provider && $providerId === Activity\Provider\Tasks\Task::getId()
				)
			)
		)
		{
			if(isset($params['deletionParams']) && is_array($params['deletionParams']))
			{
				self::$deletionParams[$associatedEntityID] = $params['deletionParams'];
			}

			if(self::unbindExternalEntity($associatedEntityID))
			{
				$result->setData(array('isDeleted' => true));
			}
		}
		return $result;
	}

	public static function processRestorationFromRecycleBin(array $activityFields, array $params = null)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$activityTypeID = isset($activityFields['TYPE_ID'])
			? (int)$activityFields['TYPE_ID'] : \CCrmActivityType::Undefined;

		$associatedEntityID = isset($activityFields['ASSOCIATED_ENTITY_ID'])
			? (int)$activityFields['ASSOCIATED_ENTITY_ID'] : 0;

		$bindings = isset($activityFields['BINDINGS']) && is_array($activityFields['BINDINGS'])
			? $activityFields['BINDINGS'] : array();

		$providerId = $activityFields['PROVIDER_ID'] ?? null;
		$result = new Main\Result();
		if(
			$associatedEntityID > 0 && !empty($bindings)
			&& (
				$activityTypeID === \CCrmActivityType::Task ||
				(
					$activityTypeID === \CCrmActivityType::Provider && $providerId === Activity\Provider\Tasks\Task::getId()
				)
			)
		)
		{
			if(isset($params['creationParams']) && is_array($params['creationParams']))
			{
				self::$creationParams[$associatedEntityID] = $params['creationParams'];
			}

			self::bindExternalEntity($associatedEntityID, $bindings);
			$createActivityID = self::resolveActivityID($associatedEntityID);
			if($createActivityID > 0)
			{
				$result->setData(array('isCreated' => true, 'entityId' => $createActivityID));
			}
		}
		return $result;
	}
}