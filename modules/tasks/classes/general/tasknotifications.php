<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Internals\Counter\Template\TaskCounter;
use Bitrix\Tasks\Internals\Notification\Task\ThrottleTable;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

class CTaskNotifications
{
	const PUSH_MESSAGE_MAX_LENGTH = 255;

	// im delivery buffer
	private static $bufferize = false;
	private static $buffer = array();

	// additional data cache
	private static $cacheData = false;
	private static $cache = array();

	// enable\disable notifications
	private static $suppressIM = false;

	########################
	# main actions

	/**
	 * @deprecated
	 * @use \Bitrix\Tasks\Internals\Notification\Controller::onTaskCreated
	 */
	public static function sendAddMessage($arFields, $arParams = array())
	{
		$taskId = (int)($arFields['ID'] ?? null);
		if ($taskId <= 0)
		{
			return;
		}
		$task = \Bitrix\Tasks\Internals\Registry\TaskRegistry::getInstance()->getObject($taskId, true);
		if (!$task)
		{
			return;
		}
		$controller = new \Bitrix\Tasks\Internals\Notification\Controller();
		$controller->onTaskCreated($task, $arParams);
		$controller->push();
	}

	/**
	 * @deprecated
	 * @use \Bitrix\Tasks\Internals\Notification\Controller::onTaskUpdated
	 */
	public static function sendUpdateMessage($arFields, $arTask, $bSpawnedByAgent = false, array $parameters = array())
	{
		$task = \Bitrix\Tasks\Internals\Registry\TaskRegistry::getInstance()
			->drop((int)$arTask['ID'])
			->getObject((int)$arTask['ID'], true);

		if (!$task)
		{
			return;
		}

		$controller = new \Bitrix\Tasks\Internals\Notification\Controller();
		$controller->onTaskUpdated($task, $arFields, $arTask, ['spawned_by_agent' => $bSpawnedByAgent]);
		$controller->push();
	}

	/**
	 * @deprecated
	 * @use \Bitrix\Tasks\Internals\Notification\Controller::onTaskDeleted
	 */
	public static function SendDeleteMessage($arFields, bool $safeDelete = false, ?TaskObject $task = null): void
	{
		if ($task === null)
		{
			return;
		}

		$controller = new \Bitrix\Tasks\Internals\Notification\Controller();
		$controller->onTaskDeleted($task, $safeDelete);
		$controller->push();
	}

	/**
	 * @deprecated
	 * @use \Bitrix\Tasks\Internals\Notification\Controller::onTaskStatusChanged
	 */
	public static function SendStatusMessage($arTask, $status, $arFields = array())
	{
		$task = \Bitrix\Tasks\Internals\Registry\TaskRegistry::getInstance()->getObject($arTask['ID'], true);
		if (!$task)
		{
			return;
		}
		$controller = new \Bitrix\Tasks\Internals\Notification\Controller();
		$controller->onTaskStatusChanged($task, (int)$status, $arFields);
		$controller->push();
	}

	/**
	 * @deprecated
	 * @use \Bitrix\Tasks\Internals\Notification\Controller::onTaskExpiresSoon
	 */
	public static function sendExpiredSoonMessage(array $taskData): void
	{
		$task = \Bitrix\Tasks\Internals\Registry\TaskRegistry::getInstance()->getObject($taskData['ID'], true);
		if (!$task)
		{
			return;
		}
		$controller = new \Bitrix\Tasks\Internals\Notification\Controller();
		$controller->onTaskExpiresSoon($task);
		$controller->push();
	}

	/**
	 * @deprecated
	 * @use \Bitrix\Tasks\Internals\Notification\Controller::onTaskExpired
	 */
	public static function sendExpiredMessage(array $taskData): void
	{
		$task = \Bitrix\Tasks\Internals\Registry\TaskRegistry::getInstance()->getObject($taskData['ID'], true);
		if (!$task)
		{
			return;
		}
		$controller = new \Bitrix\Tasks\Internals\Notification\Controller();
		$controller->onTaskExpired($task);
		$controller->push();
	}

	/**
	 * @deprecated
	 * @use \Bitrix\Tasks\Internals\Notification\Controller::onTaskPingSend
	 */
	public static function sendPingStatusMessage(array $taskData, int $authorId): void
	{
		$task = \Bitrix\Tasks\Internals\Registry\TaskRegistry::getInstance()->getObject($taskData['ID'], true);
		if (!$task)
		{
			return;
		}
		$controller = new \Bitrix\Tasks\Internals\Notification\Controller();
		$controller->onTaskPingSend($task, $authorId);
		$controller->push();
	}

	############################
	# low-level action functions

	public static function sendMessageEx(
		$taskId,
		$fromUser,
		array $toUsers,
		array $messages = [],
		array $parameters = []
	): bool
	{
		if (!isset($parameters['IS_ON_BACKGROUND_JOB']) || $parameters['IS_ON_BACKGROUND_JOB'] === 'Y')
		{
			Bitrix\Tasks\Internals\Notification\Event\EventHandler::addEvent(
				'message',
				[
					'TASK_ID' => $taskId,
					'FROM_USER' => $fromUser,
					'TO_USERS' => $toUsers,
					'MESSAGES' => $messages,
					'PARAMETERS' => $parameters,
				]
			);
			return true;
		}

		if (!IsModuleInstalled('im') || !CModule::IncludeModule('im'))
		{
			return false;
		}

		if (!$fromUser || (string)$messages['INSTANT'] === '')
		{
			return false;
		}

		if (
			!isset($parameters['EXCLUDE_USERS_WITH_MUTE'])
			|| (isset($parameters['EXCLUDE_USERS_WITH_MUTE']) && $parameters['EXCLUDE_USERS_WITH_MUTE'] === 'Y')
		)
		{
			$toUsers = static::excludeUsersWithMute($toUsers, $taskId);
		}
		unset($parameters['EXCLUDE_USERS_WITH_MUTE']);

		if (empty($toUsers))
		{
			return false;
		}

		$entityCode = 'TASK';
		if ((string)($parameters['ENTITY_CODE'] ?? null) !== '')
		{
			$entityCode = $parameters['ENTITY_CODE'];
			unset($parameters['ENTITY_CODE']);
		}

//		$allowNotCommentNotifications = false;
//		if (
//			isset($parameters['ENTITY_OPERATION']) &&
//			$parameters['ENTITY_OPERATION'] == 'ADD' &&
//			$entityCode == 'TASK'
//		)
//		{
//			$allowNotCommentNotifications = true;
//		}
//
//		// disable all non comments notifications
//		if (!$allowNotCommentNotifications && $entityCode !== 'COMMENT')
//		{
//			return false;
//		}

		if (!isset($messages['EMAIL']))
		{
			$messages['EMAIL'] = $messages['INSTANT'];
		}

		$eventData = $parameters['EVENT_DATA'] ?? null;
		$notifyEvent = ($parameters['NOTIFY_EVENT'] ?? null);
		$callbacks = ($parameters['CALLBACK'] ?? null);

		unset($parameters['EVENT_DATA'], $parameters['NOTIFY_EVENT'], $parameters['CALLBACK']);

		$notifyType = null;
		if (array_key_exists('NOTIFY_TYPE', $parameters))
		{
			$notifyType = $parameters['NOTIFY_TYPE'];
			unset($parameters['NOTIFY_TYPE']);
		}

		$pushParams = null;
		if (array_key_exists('PUSH_PARAMS', $parameters))
		{
			$pushParams = $parameters['PUSH_PARAMS'];
			unset($parameters['PUSH_PARAMS']);
		}

		$entityOperation = 'ADD';
		if ((string)($parameters['ENTITY_OPERATION'] ?? null) !== '')
		{
			$entityOperation = $parameters['ENTITY_OPERATION'];
			unset($parameters['ENTITY_OPERATION']);
		}

		$params = [
			'FROM_USER_ID' => $fromUser,
			'TO_USER_IDS' => $toUsers,
			'TASK_ID' => (int)$taskId,
			'MESSAGE' => $messages,
			'EVENT_DATA' => $eventData,
			'NOTIFY_EVENT' => $notifyEvent,
			'ENTITY_CODE' => $entityCode,
			'ENTITY_OPERATION' => $entityOperation,
			'CALLBACK' => $callbacks,
			'ADDITIONAL_DATA' => $parameters,
		];

		if ($notifyType)
		{
			$params['NOTIFY_TYPE'] = $notifyType;
		}
		if ($pushParams)
		{
			$params['PUSH_PARAMS'] = $pushParams;
		}

		self::addToNotificationBuffer($params);

		if (!self::$bufferize)
		{
			self::flushNotificationBuffer(false);
		}

		return true;
	}

	private static function excludeUsersWithMute(array $users, int $taskId): array
	{
		$resultUsers = [];

		$emailUsers = array_column(
			\Bitrix\Main\UserTable::getList([
				'select' => ['ID'],
				'filter' => [
					'ID' => $users,
					'=EXTERNAL_AUTH_ID' => 'email',
				],
			])->fetchAll(),
			'ID'
		);
		$emailUsers = array_map('intval', $emailUsers);

		foreach ($users as $userId)
		{
			if (
				in_array((int)$userId, $emailUsers, true)
				|| !UserOption::isOptionSet($taskId, $userId, UserOption\Option::MUTED)
			)
			{
				$resultUsers[] = $userId;
			}
		}

		return $resultUsers;
	}

	public static function isCrmTask(array $task)
	{
		return (
			isset($task)
			&& isset($task["UF_CRM_TASK"])
			&& (
				(
					is_array($task["UF_CRM_TASK"])
					&& (
						isset($task["UF_CRM_TASK"][0])
						&& $task["UF_CRM_TASK"][0] <> ''
					)
				)
				||
				(
					!is_array($task["UF_CRM_TASK"])
					&& $task["UF_CRM_TASK"] <> ''
				)
			)
		);
	}

	public static function getSonetLogFilter($taskId, $crm)
	{
		$filter = array();

		if (!$crm)
		{
			$filter = array(
				"EVENT_ID" => "tasks",
				"SOURCE_ID" => $taskId
			);
		}
		elseif (\Bitrix\Main\Loader::includeModule("crm"))
		{
			$res = CCrmActivity::getList(
				array(),
				array(
					'TYPE_ID' => CCrmActivityType::Task,
					'ASSOCIATED_ENTITY_ID' => $taskId,
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				false,
				array('ID')
			);

			if ($activity = $res->fetch())
			{
				$filter = array(
					"EVENT_ID" => "crm_activity_add",
					"ENTITY_ID" => $activity
				);
			}
		}

		return $filter;
	}

	public static function setSonetLogRights(array $params, array $fields, array $task): void
	{
		$logId = (int)$params['LOG_ID'];
		$effectiveUserId = (int)$params['EFFECTIVE_USER_ID'];

		if ($logId <= 0 || $effectiveUserId <= 0)
		{
			return;
		}

		// Get current rights
		$currentRights = [];
		$rightsResult = CSocNetLogRights::getList([], ['LOG_ID' => $logId]);
		while ($right = $rightsResult->fetch())
		{
			$currentRights[] = $right['GROUP_CODE'];
		}

		// If author changes the task and author doesn't have
		// access to task yet, don't give access to him.
		$authorId = (isset($fields['CREATED_BY']) ? (int)$fields['CREATED_BY'] : (int)$task['CREATED_BY']);
		$authorHasAccess = in_array('U'.$authorId, $currentRights, true);
		$authorMustBeExcluded = ($authorId === $effectiveUserId) && !$authorHasAccess;

		$taskParticipants = CTaskNotifications::getRecipientsIDs(
			$fields, // Only new tasks' participiants should view log event, fixed due to http://jabber.bx/view.php?id=34504
			false, // don't exclude current user
			true // exclude additional recipients (because there are previous members of task)
		);
		$logCanViewedBy = ($authorMustBeExcluded ? array_diff($taskParticipants, [$authorId]) : $taskParticipants);
		$logCanViewedBy = array_unique(array_map('intval', array_filter($logCanViewedBy)));
		$newRights = CTaskNotifications::__UserIDs2Rights($logCanViewedBy);

		$oldGroupId = $task['GROUP_ID'];
		$newGroupId = ($fields['GROUP_ID'] ?? null);
		$groupChanged = (isset($newGroupId, $oldGroupId) && $newGroupId && (int)$newGroupId !== (int)$oldGroupId);

		// If rights really changed, update them
		if (
			$groupChanged
			|| !empty(array_diff($currentRights, $newRights))
			|| !empty(array_diff($newRights, $currentRights))
		)
		{
			$groupRights = [];
			if (isset($newGroupId))
			{
				$groupRights = self::prepareRightsCodesForViewInGroupLiveFeed($logId, $newGroupId);
			}
			else if (isset($oldGroupId))
			{
				$groupRights = self::prepareRightsCodesForViewInGroupLiveFeed($logId, $oldGroupId);
			}

			CSocNetLogRights::deleteByLogID($logId);

			foreach ($logCanViewedBy as $userId)
			{
				$code = CTaskNotifications::__UserIDs2Rights([$userId]);
				$follow = !UserOption::isOptionSet($task['ID'], $userId, UserOption\Option::MUTED);

				CSocNetLogRights::add($logId, $code, false, $follow);
			}
			if (!empty($groupRights))
			{
				CSocNetLogRights::add($logId, $groupRights);
			}
		}
	}

	########################
	# throttle functions

	public static function throttleRelease(): void
	{
		$items = ThrottleTable::getUpdateMessages();
		if (is_array($items) && !empty($items))
		{
			$cacheAutoClearingWasDisabled = \CTasks::disableCacheAutoClear();
			$notificationAutoDeliveryWasDisabled = \CTaskNotifications::disableAutoDeliver();

			// this function may be called on agent
			// DO NOT relay on global user as an author, use field AUTHOR_ID instead
			foreach ($items as $item)
			{
				self::SendUpdateMessage(
					$item['STATE_LAST'],
					$item['STATE_ORIG'],
					false,
					[
						'AUTHOR_ID' => $item['AUTHOR_ID'],
						'IGNORE_AUTHOR' => isset($item['IGNORE_RECIPIENTS'][$item['AUTHOR_ID']]),
					]
				);
			}

			if ($notificationAutoDeliveryWasDisabled)
			{
				\CTaskNotifications::enableAutoDeliver();
			}
			if ($cacheAutoClearingWasDisabled)
			{
				\CTasks::enableCacheAutoClear();
			}
		}
	}

	########################
	# buffer-deal functions

	protected static function addToNotificationBuffer(array $message)
	{
		if(self::$suppressIM) // im notifications disabled
		{
			return;
		}

		self::$buffer[] = $message;
	}

	private static function initBuffer()
	{
		if (!is_array(self::$buffer) || empty(self::$buffer))
		{
			self::$buffer = [];
		}
	}

	private static function getUsersFromBuffer(): array
	{
		$users = [];
		foreach(self::$buffer as $i => $message)
		{
			if(is_array($message['TO_USER_IDS']))
			{
				foreach ($message['TO_USER_IDS'] as $userId)
				{
					$users[$userId] = true;
				}
			}
		}
		return self::getUsers(array_keys($users));
	}

	private static function flushNotificationBuffer($doGrouping = true)
	{
		self::initBuffer();

		if (empty(self::$buffer))
		{
			return;
		}

		// get all users
		$users = self::getUsersFromBuffer();

		$sites = \Bitrix\Tasks\Util\Site::getPair();

		$byUser = [];
		$mailed = [];
		$type = null;

		foreach(self::$buffer as $i => $message)
		{
			if(!is_array($message['TO_USER_IDS']))
			{
				continue;
			}

			// $skipImPush = false;
			// if ($message['ENTITY_OPERATION'] == 'ADD' && $message['ENTITY_CODE'] == 'TASK')
			// {
			// 	$skipImPush = true;
			// }

			foreach($message['TO_USER_IDS'] as $userId)
			{
				if(!isset($users[$userId])) // no user found for that id
				{
					continue;
				}

				// determine notify event here, if it was not given
				if((string) $message['NOTIFY_EVENT'] == '')
				{
					$notifyEvent = 'manage';

					if (($message['ADDITIONAL_DATA']['TASK_ASSIGNED_TO'] ?? null) !== null)
					{
						if ($userId == $message['ADDITIONAL_DATA']['TASK_ASSIGNED_TO'])
						{
							$notifyEvent = 'task_assigned';
						}
					}

					$message['NOTIFY_EVENT'] = $notifyEvent;
				}

				if(\Bitrix\Tasks\Integration\Mail\User::isEmail($users[$userId])) // must send message to email users separately
				{
					if(!isset($mailed[$i]))
					{
						$mMessage = $message;
						$mMessage['TO_USER_IDS'] = array();

						$mailed[$i] = $mMessage;
					}
					$mailed[$i]['TO_USER_IDS'][] = $userId;
				}
				else
				{
					// if (!$skipImPush)
					// {
					$byUser[$userId][$message['TASK_ID']] = $message;
					// }
				}
			}
		}

		// send regular messages
		foreach($byUser as $userId => $messages)
		{
			$unGroupped = array();

			if(
				count($messages) > 1 && $doGrouping
			) // new way
			{
				// send for each action type, notification type and author separately
				$deepGrouping = array();

				foreach($messages as $taskId => $message)
				{
					// we do not group entities that differ from 'TASK' and NOTIFY_EVENTS that differ from 'manage'
					if($message['ENTITY_CODE'] != 'TASK' || $message['NOTIFY_EVENT'] != 'manage')
					{
						$unGroupped[$taskId] = $message;
						continue;
					}

					// if type is unknown, let it be "update"
					$possibleTypes = [
						'TASK_ADD',
						'TASK_UPDATE',
						'TASK_DELETE',
						'TASK_STATUS_CHANGED_MESSAGE',
						'TASK_EXPIRED_SOON',
						'TASK_EXPIRED',
						'TASK_PINGED_STATUS',
					];
					$type = (string)($message['EVENT_DATA']['ACTION'] ?? null);
					$type = ($type !== '' ? $type : 'TASK_UPDATE');

					if (!in_array($type, $possibleTypes, true))
					{
						// unknown action type. nothing to report about
						continue;
					}

					$fromUserId = $message['FROM_USER_ID'];
					if((string) $fromUserId == '') // empty author is not allowed
					{
						continue;
					}

					$deepGrouping[$type][$fromUserId][$message['NOTIFY_EVENT']][] = $taskId;
				}

				if(!empty($deepGrouping))
				{
					foreach($deepGrouping as $type => $byAuthor)
					{
						foreach($byAuthor as $authorId => $byEvent)
						{
							foreach($byEvent as $event => $taskIds)
							{
								$path = CTaskNotifications::getNotificationPathMultiple($users[$userId], $taskIds, true);

								$instantTemplate = self::getGenderMessage($authorId, 'TASKS_TASKS_'.$type.'_MESSAGE');
								$emailTemplate = self::getGenderMessage($authorId, 'TASKS_TASKS_'.$type.'_MESSAGE_EMAIL');
								$pushTemplate = self::getGenderMessage($authorId, 'TASKS_TASKS_'.$type.'_MESSAGE_PUSH');

								$instant = self::placeLinkAnchor($instantTemplate, $path, 'BBCODE');
								$email = self::placeLinkAnchor($emailTemplate, $path, 'EMAIL');
								$push = self::placeLinkAnchor($pushTemplate, $path, 'NONE');
								$push = self::placeUserName($push, $authorId);

								$imNotificationTag = (new \Bitrix\Tasks\Integration\IM\Notification\Tag())
									->setTasksIds($taskIds)
									->setUserId($userId)
									->setEntityCode('TASKS');

								$arMessageFields = array(
									"TO_USER_ID" => $userId,
									"FROM_USER_ID" => $authorId,
									"NOTIFY_TYPE" => IM_NOTIFY_FROM,
									"NOTIFY_MODULE" => 'tasks',
									"NOTIFY_EVENT" => $event,
									"NOTIFY_MESSAGE" => $instant,
									"NOTIFY_MESSAGE_OUT" => $email,
									"NOTIFY_TAG" => $imNotificationTag->getNameWithSignature(),

									// push
									"PUSH_MESSAGE" => mb_substr($push, 0, self::PUSH_MESSAGE_MAX_LENGTH),
								);

								\Bitrix\Tasks\Integration\Im::notifyAdd($arMessageFields);
							}
						}
					}
				}
			}
			else // old way
			{
				$unGroupped = $messages;
			}

			// send each message separately
			foreach($unGroupped as $taskId => $message)
			{
				$pathToTask = self::getNotificationPath($users[$userId], $taskId, true, $sites);
				$pathToTask = self::addParameters($pathToTask, ($message['ADDITIONAL_DATA']['TASK_URL'] ?? null));

				$message['ENTITY_CODE'] = mb_strtoupper($message['ENTITY_CODE']);

				// replace #TASK_URL_BEGIN# placeholder
				$message['MESSAGE']['INSTANT'] = self::placeLinkAnchor($message['MESSAGE']['INSTANT'], $pathToTask, 'BBCODE');
				$message['MESSAGE']['EMAIL'] = self::placeLinkAnchor($message['MESSAGE']['EMAIL'], $pathToTask, 'EMAIL');
				if((string) $message['MESSAGE']['PUSH'] != '')
				{
					$message['MESSAGE']['PUSH'] = self::placeLinkAnchor($message['MESSAGE']['PUSH'], $pathToTask, 'NONE');
				}

				// replace #TASK_TITLE# placeholder, if any
				if(is_array($message['ADDITIONAL_DATA']['TASK_DATA'] ?? null))
				{
					$taskData = $message['ADDITIONAL_DATA']['TASK_DATA'];
					$taskTitle = CTaskNotifications::formatTaskName($taskData["ID"], $taskData["TITLE"], $taskData["GROUP_ID"]);

					$message['MESSAGE']['INSTANT'] = str_replace('#TASK_TITLE#', $taskTitle, $message['MESSAGE']['INSTANT']);
					$message['MESSAGE']['EMAIL'] = str_replace('#TASK_TITLE#', strip_tags($taskTitle), $message['MESSAGE']['INSTANT']);
					if((string) $message['MESSAGE']['PUSH'] != '')
					{
						$message['MESSAGE']['PUSH'] = str_replace('#TASK_TITLE#', $taskTitle, $message['MESSAGE']['PUSH']);
					}
				}

				$message['TO_USER_IDS'] = array($userId);

				// message callbacks here
				if(is_callable($message['CALLBACK']['BEFORE_SEND'] ?? null))
				{
					$message = call_user_func_array($message['CALLBACK']['BEFORE_SEND'], array($message));
				}

				// event process here
				if(!static::fireMessageEvent($message))
				{
					continue;
				}

				$userId = $message['TO_USER_IDS'][0]; // it may have changed on event

				// make IM parameters
				$actionName = ((string)($message['EVENT_DATA']['ACTION'] ?? null) !== '' ? $message['EVENT_DATA']['ACTION'] : 'TASK_UPDATE');

				$imNotificationTag = (new \Bitrix\Tasks\Integration\IM\Notification\Tag())
					->setTasksIds(array($taskId))
					->setUserId($userId)
					->setEntityCode($message['ENTITY_CODE'])
					->setActionName($actionName);

				if('COMMENT' == $message['ENTITY_CODE'])
				{
					$imNotificationTag->setEntityId(intval($message['EVENT_DATA']['MESSAGE_ID']));
				}

				$arMessageFields = array(
					"TO_USER_ID" => $userId,
					"FROM_USER_ID" => $message['FROM_USER_ID'],
					"NOTIFY_TYPE" => isset($message['NOTIFY_TYPE']) ? $message['NOTIFY_TYPE'] : IM_NOTIFY_FROM,
					"NOTIFY_MODULE" => "tasks",
					"NOTIFY_EVENT" => $message['NOTIFY_EVENT'],
					"NOTIFY_TAG" => $imNotificationTag->getName(),
					"NOTIFY_SUB_TAG" => $imNotificationTag->getSubName(),
					"NOTIFY_MESSAGE" => $message['MESSAGE']['INSTANT'],
					"NOTIFY_MESSAGE_OUT" => $message['MESSAGE']['EMAIL'],
					"PARAMS" => array(
						"taskId" => $message['TASK_ID'],
						"operation" => $message['ENTITY_OPERATION']
					),
//					"NOTIFY_ONLY_FLASH" => "Y",
//					"NOTIFY_LINK" => $pathToTask
				);

				if((string)($message['ADDITIONAL_DATA']['NOTIFY_ANSWER'] ?? null))
				{
					// enabling notify answer for desktop
					$arMessageFields['NOTIFY_ANSWER'] = 'Y';
				}

				if ((string)$message['MESSAGE']['PUSH'] !== '')
				{
					// add push message
					$arMessageFields['PUSH_MESSAGE'] = self::placeLinkAnchor($message['MESSAGE']['PUSH'], $pathToTask);

					// user should be able to open the task window to see the changes ...
					// see /mobile/install/components/bitrix/mobile.rtc/templates/.default/script.js for handling details
					$arMessageFields['PUSH_PARAMS'] = [
						'ACTION' => 'tasks',
						'TAG' => $imNotificationTag->getName(),
						'ADVANCED_PARAMS' => [],
					];

					if ((string)($message['ADDITIONAL_DATA']['NOTIFY_ANSWER'] ?? null))
					{
						// ... and open an answer dialog in mobile
						$arMessageFields['PUSH_PARAMS'] = array_merge(
							$arMessageFields['PUSH_PARAMS'],
							[
								'CATEGORY' => 'ANSWER',
								'URL' => SITE_DIR . 'mobile/ajax.php?mobile_action=task_answer',
								'PARAMS' => [
									'TASK_ID' => $taskId,
								],
							]
						);
					}

					if (
						array_key_exists('PUSH_PARAMS', $message)
						&& is_string($message['PUSH_PARAMS']['SENDER_NAME'])
					)
					{
						$arMessageFields['PUSH_PARAMS']['ADVANCED_PARAMS'] = [
							'senderName' => $message['PUSH_PARAMS']['SENDER_NAME'],
							'senderMessage' => $arMessageFields['PUSH_MESSAGE'],
						];
					}

					$pushData = [];
					if ($type !== 'TASK_DELETE')
					{
						$oldData = ($message['ADDITIONAL_DATA']['TASK_DATA'] ?? []);
						$newData = ($message['EVENT_DATA']['arFields'] ?? []);
						$pushData = static::preparePushData($taskId, $userId, array_merge($oldData, $newData));
					}

					$arMessageFields['PUSH_PARAMS']['ADVANCED_PARAMS'] = array_merge(
						$arMessageFields['PUSH_PARAMS']['ADVANCED_PARAMS'],
						[
							'group' => 'tasks_task',
							'data' => $pushData,
						]
					);
				}

				\Bitrix\Tasks\Integration\IM::notifyAdd($arMessageFields);
			}
		}

		// send email messages
		foreach($mailed as $message)
		{
			if (!is_array($sites["INTRANET"]))
			{
				continue;
			}
			self::notifyByMail($message, $sites["INTRANET"]);
		}

		self::$buffer = array();
	}

	protected static function fireMessageEvent(array &$message)
	{
		if(!is_array($message['EVENT_DATA']))
		{
			$message['EVENT_DATA'] = array();
		}

		$message['EVENT_DATA']['fromUserID']      =& $message['FROM_USER_ID'];
		$message['EVENT_DATA']['arRecipientsIDs'] =& $message['TO_USER_IDS'];
		$message['EVENT_DATA']['message']         =& $message['MESSAGE']['INSTANT'];
		$message['EVENT_DATA']['message_email']   =& $message['MESSAGE']['EMAIL'];
		$message['EVENT_DATA']['message_push']    =& $message['MESSAGE']['PUSH'];

		$skipMessage = false;
		foreach(GetModuleEvents('tasks', 'OnBeforeTaskNotificationSend', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($message['EVENT_DATA'])) === false)
			{
				$skipMessage = true;
				break;
			}
		}

		return !$skipMessage;
	}

	private static function preparePushData(int $taskId, int $userId, array $taskData): array
	{
		$counter = (new TaskCounter($userId))->getMobileRowCounter($taskId);
		unset($counter['counters']);

		$pushData = [
			'id' => (string)$taskId,
			'counter' => (new TaskCounter($userId))->getMobileRowCounter($taskId),
		];

		$data = self::getTaskData($taskId);
		if (array_key_exists('ACTIVITY_DATE', $data))
		{
			$pushData['activityDate'] = self::prepareDate($userId, $data['ACTIVITY_DATE']);
		}

		if (array_key_exists('TITLE', $taskData))
		{
			$pushData['title'] = $taskData['TITLE'];
		}
		if (array_key_exists('DEADLINE', $taskData) && isset($taskData['DEADLINE']))
		{
			$pushData['deadline'] = self::prepareDate($userId, $taskData['DEADLINE']);
		}
		if (
			array_key_exists('REAL_STATUS', $taskData)
			|| array_key_exists('STATUS', $taskData)
		)
		{
			$pushData['status'] = ($taskData['REAL_STATUS'] ?? $taskData['STATUS']);
		}
		if (array_key_exists('GROUP_ID', $taskData))
		{
			$groupId = $taskData['GROUP_ID'];

			$pushData['groupId'] = $groupId;
			$pushData['group'] = [];

			if ($groupId > 0)
			{
				$groupData = SocialNetwork\Group::getGroupData($groupId);
				$pushData['group'] = [
					'id' => $groupId,
					'name' => $groupData['NAME'],
					'image' => $groupData['IMAGE'],
				];
			}
		}
		if (array_key_exists('CREATED_BY', $taskData))
		{
			$pushData['creator'] = [
				'id' => $taskData['CREATED_BY'],
				'icon' => self::getUserAvatar($taskData['CREATED_BY']),
			];
		}
		if (array_key_exists('RESPONSIBLE_ID', $taskData))
		{
			$pushData['responsible'] = [
				'id' => $taskData['RESPONSIBLE_ID'],
				'icon' => self::getUserAvatar($taskData['RESPONSIBLE_ID']),
			];
		}
		if (array_key_exists('ACCOMPLICES', $taskData))
		{
			$pushData['accomplices'] = $taskData['ACCOMPLICES'];
		}
		if (array_key_exists('AUDITORS', $taskData))
		{
			$pushData['auditors'] = $taskData['AUDITORS'];
		}

		$map = [
			'id' => 1,
			'title' => 2,
			'deadline' => 3,
			'activityDate' => 4,
			'status' => 5,

			'groupId' => 20,
			'group' => 21,
			'image' => 22,
			'name' => 23,

			'creator' => 30,
			'responsible' => 31,
			'icon' => 32,

			'accomplices' => 41,
			'auditors' => 42,

			'counter' => 50,
			'counters' => 51,
			'color' => 52,
			'value' => 53,
			'expired' => 54,
			'new_comments' => 55,
			'project_expired' => 56,
			'project_new_comments' => 57,
		];
		$pushData = self::convertFields($pushData, $map);

		return $pushData;
	}

	private static function convertFields(array $pushData, array $map)
	{
		$result = [];

		foreach ($pushData as $key => $value)
		{
			$index = ($map[$key] ?? $key);

			if (is_array($value))
			{
				$result[$index] = self::convertFields($value, $map);
			}
			else
			{
				$result[$index] = ($value ?? '');
			}
		}

		return $result;
	}

	private static function prepareDate(int $userId, ?string $date): string
	{
		$result = '';

		if (!$date)
		{
			return $result;
		}

		$localOffset = (new \DateTime())->getOffset();
		$currentUserOffset = \CTimeZone::GetOffset(null, true);
		$targetUserOffset = \CTimeZone::GetOffset($userId, true);
		$offset = $localOffset + $targetUserOffset;
		$newOffset = ($offset > 0 ? '+' : '') . UI::formatTimeAmount($offset, 'HH:MI');

		if ($newDate = new DateTime($date))
		{
			$newDate->addSecond(-$currentUserOffset);
			$newDate->addSecond($targetUserOffset);
			$result = mb_substr($newDate->format('c'), 0, -6) . $newOffset;
		}

		return $result;
	}

	private static function getTaskData(int $taskId): array
	{
		static $cache = [];

		if (!array_key_exists($taskId, $cache))
		{
			$cache[$taskId] = [];

			$taskResult = TaskTable::getList([
				'select' => ['ACTIVITY_DATE'],
				'filter' => ['ID' => $taskId],
			]);
			if ($task = $taskResult->fetch())
			{
				$cache[$taskId] = $task;
			}
		}

		return $cache[$taskId];
	}

	private static function getUserAvatar(int $userId): string
	{
		static $cache = [];

		if (!array_key_exists($userId, $cache))
		{
			$users = User::getData([$userId], ['ID', 'PERSONAL_PHOTO']);
			$user = $users[$userId];

			$cache[$userId] = UI\Avatar::getPerson($user['PERSONAL_PHOTO']);
		}

		return $cache[$userId];
	}

	########################
	# event handlers

	// this is for making notifications work when using "ilike"
	// see CRatings::AddRatingVote() and CIMEvent::OnAddRatingVote() for the context of usage
	public static function OnGetRatingContentOwner($params)
	{
		if(intval($params['ENTITY_ID']) && $params['ENTITY_TYPE_ID'] == 'TASK')
		{
			[ $oTaskItems, $rsData ] = CTaskItem::fetchList(User::getAdminId(), [], array('=ID' => $params['ENTITY_ID']), [], [ 'ID', 'CREATED_BY' ]);
			unset($rsData);

			if($oTaskItems[0] instanceof CTaskItem)
			{
				try
				{
					$data = $oTaskItems[0]->getData(false);
				}
				catch (TasksException $e)
				{
					return false;
				}

				if(intval($data['CREATED_BY']))
					return intval($data['CREATED_BY']);
			}
		}

		return false;
	}

	// this is for replacing the default message when user presses "ilike" button
	// see CIMEvent::GetMessageRatingVote() for the context of usage
	public static function OnGetMessageRatingVote(&$params, &$forEmail)
	{
		static $intranetInstalled = null;

		if ($intranetInstalled === null)
		{
			$intranetInstalled = \Bitrix\Main\ModuleManager::isModuleInstalled('intranet');
		}

		if($params['ENTITY_TYPE_ID'] == 'TASK' && !$forEmail)
		{
			$type = (
				$params['VALUE'] >= 0
					? ($intranetInstalled ? 'REACT' : 'LIKE')
					: 'DISLIKE'
			);

			$genderSuffix = '';
			if (
				$type == 'REACT'
				&& !empty($params['USER_ID'])
				&& intval($params['USER_ID']) > 0
			)
			{
				$res = \Bitrix\Main\UserTable::getList(array(
					'filter' => array(
						'ID' => intval($params['USER_ID'])
					),
					'select' => array('PERSONAL_GENDER')
				));
				if ($userFields = $res->fetch())
				{
					switch ($userFields['PERSONAL_GENDER'])
					{
						case "M":
						case "F":
							$genderSuffix = '_'.$userFields['PERSONAL_GENDER'];
							break;
						default:
							$genderSuffix = '';
					}
				}
			}

			$langMessage = GetMessage('TASKS_NOTIFICATIONS_I_'.$type.'_TASK'.$genderSuffix);
			if((string) $langMessage != '')
			{
				$taskTitle = self::formatTaskName($params['ENTITY_ID'], $params['ENTITY_TITLE']);

				$params['MESSAGE'] = str_replace(
					'#LINK#',
					(string) $params['ENTITY_LINK'] != '' ? '<a href="'.$params['ENTITY_LINK'].'" class="bx-notifier-item-action">'.$taskTitle.'</a>': '<i>'.$taskTitle.'</i>', $langMessage);
			}

			if ($intranetInstalled)
			{
				$params['MESSAGE'] .= "\n".str_replace("#REACTION#", \CRatingsComponentsMain::getRatingLikeMessage(!empty($params['REACTION']) ? $params['REACTION'] : ''), Bitrix\Main\Localization\Loc::getMessage("TASKS_NOTIFICATIONS_I_REACTION"));
			}
		}
	}

	// this is for processing action "answer" when getting comment notification
	public static function OnAnswerNotify($module, $tag, $text, $arNotify)
	{
		if ($module == "tasks" && (string) $text != '')
		{
			$tagData = self::parseImNotificationTag($tag);

			if($tagData['ENTITY'] == 'COMMENT')
			{
				if(!CModule::IncludeModule('forum') || !$GLOBALS['USER'] || !method_exists($GLOBALS['USER'], 'GetId'))
				{
					throw new SystemException(); // this will break json and make notify window glow red :)
				}
				else
				{
					try
					{
						if (self::addAnswer($tagData['TASK_ID'], $text))
						{
							return Loc::getMessage('TASKS_IM_ANSWER_SUCCESS');
						}
					}
					catch(\TasksException | CTaskAssertException $e)
					{
						$message = unserialize($e->getMessage(), ['allowed_classes' => false]);

						return array(
							'result' => false,
							'text' => $message[0]
						);
					}
				}
			}
		}
	}

	/**
	 * @deprecated
	 * @use \Bitrix\Tasks\Internals\Notification\Controller::onNotificationReply
	 */
	public static function addAnswer($taskId, $text)
	{
		$task = \Bitrix\Tasks\Internals\Registry\TaskRegistry::getInstance()->getObject((int)$taskId, true);
		if (!$task)
		{
			return;
		}
		$controller = new \Bitrix\Tasks\Internals\Notification\Controller();
		$controller->onNotificationReply($task, $text);
		$controller->push();
	}

	########################
	# formatters

	/**
	 * @param $taskId
	 * @param $title
	 * @param int $groupId
	 * @param bool $bUrl
	 * @return string
	 *
	 * @access private
	 */
	private static function formatTaskName($taskId, $title, $groupId = 0, $bUrl = false)
	{
		$name = '[#' . $taskId . '] ';

		if ($bUrl)
			$name .= '[URL=#PATH_TO_TASK#]';

		$name .= $title;

		if ($bUrl)
			$name .= '[/URL]';

		if ($groupId && CModule::IncludeModule('socialnetwork'))
		{
			$arGroup = self::getSocNetGroup($groupId);

			if (is_string($arGroup['NAME']) && ($arGroup['NAME'] !== ''))
				$name .= ' (' . GetMessage('TASKS_NOTIFICATIONS_IN_GROUP') . ' ' . $arGroup['NAME'] . ')';
		}

		return ($name);
	}

	private static function parseImNotificationTag($tag)
	{
		[ $module, $entity, $id, $userId ] = explode('|', $tag);

		return array(
			'ENTITY' => $entity,
			'TASK_ID' => $id,
		);
	}

	public static function getGenderMessage($userId, $messageCode)
	{
		$user = CTaskNotifications::getUser($userId);

		if (is_array($user) && ($user['PERSONAL_GENDER'] === 'M' || $user['PERSONAL_GENDER'] === 'F'))
		{
			$message = GetMessage($messageCode . '_' . $user['PERSONAL_GENDER']);

			if((string)$message === '') // no gender message?
			{
				$message = GetMessage($messageCode.'_N');
			}
		}
		else
		{
			// no gender? try to get neutral
			$message = GetMessage($messageCode.'_N');
			if((string)$message === '') // no neutral message? fall back to Male gender
			{
				$message = GetMessage($messageCode . '_M');
			}
		}

		return $message;
	}

	public static function cropMessage($template, array $replaces = array(), $length = false)
	{
		if($length === false)
		{
			$result = str_replace(array_keys($replaces), $replaces, $template);
		}
		else
		{
			$left = $length - mb_strlen(preg_replace('/#[a-zA-Z_0-9]+#/', '', $template));
			$result = $template;

			// todo: make more clever algorithm here
			foreach($replaces as $placeHolder => $value)
			{
				$fullValue = $value;
				$placeHolder = '#'.$placeHolder.'#';

				if ($left <= 0)
				{
					$result = str_replace($placeHolder, '', $result);
					continue;
				}

				if (mb_strlen($value) > $left)
				{
					$value = mb_substr($value, 0, $left - 3).'...';
				}

				$result = str_replace($placeHolder, $value, $result);
				$left -= mb_strlen($fullValue);
			}
		}

		return $result;
	}

	private static function placeUserName($message, $userId)
	{
		return str_replace('#USER_NAME#', CUser::FormatName(CSite::GetNameFormat(false), self::getUser($userId)), $message);
	}

	protected static function placeLinkAnchor($message, $url, $mode = 'NONE')
	{
		if(
			$mode === 'BBCODE'
			&& !empty($url)
		)
		{
			$message = str_replace(
				array(
					'#TASK_URL_BEGIN#',
					'#URL_END#'
				),
				array(
					"[URL=".$url."]",
					"[/URL]"
				),
				$message
			);
		}
		else
		{
			$message = str_replace(
				array(
					'#TASK_URL_BEGIN#',
					'#URL_END#'
				),
				array(
					'',
					''
				),
				$message
			);

			if(
				$mode === 'EMAIL'
				&& !empty($url)
			)
			{
				$message .= ' #BR# '.GetMessage('TASKS_MESSAGE_LINK_GENERAL').': '.$url; // #BR# will be converted to \n by IM
			}
		}

		return $message;
	}

	/**
	 * IM notification BBCODE support:
	 * HTML, VIDEO, SMILE - NO
	 * ALL STANDARD - YES
	 * ADDITIONAL: USER - YES
	 */
	public static function clearNotificationText($text)
	{
		return preg_replace(
			array(
				'|\[DISK\sFILE\sID=[n]*\d+\]|',
				'|\[DOCUMENT\sID=\d+\]|'
			),
			'',
			$text
		);
	}

	protected static function addParameters($url, $parameters = array())
	{
		if(!is_array($parameters))
		{
			$parameters = array();
		}

		if(is_array($parameters['PARAMETERS'] ?? null))
		{
			$url = CHTTP::urlAddParams($url, $parameters['PARAMETERS']);
		}

		if((string)($parameters['HASH'] ?? null) != '')
		{
			$url .= '#'.$parameters['HASH'];
		}

		return $url;
	}

	########################
	# static data getters

	/**
	 * Returns notificaton path for a set of tasks
	 */
	protected static function getNotificationPathMultiple(array $arUser, array $taskIds, $bUseServerName = true)
	{
		$sites = \Bitrix\Tasks\Util\Site::getPair();

		if(self::checkUserIsIntranet($arUser["ID"]))
		{
			$site = $sites['INTRANET'];
		}
		else
		{
			$site = $sites['EXTRANET'];
		}

		// detect site name
		$serverName = '';
		if($bUseServerName)
		{
			$serverName =
				\Bitrix\Tasks\V2\Internal\DI\Container::getInstance()
					->getUrlService()
					->getHostUrl((string)$site['SERVER_NAME'])
			;
		}

		$pathTemplate = COption::GetOptionString('tasks', 'paths_task_user', '', $site['SITE_ID']);
		if((string) $pathTemplate == '')
		{
			$pathTemplate = "/company/personal/user/#user_id#/tasks/";
		}
		$url = $serverName.CComponentEngine::MakePathFromTemplate(
			$pathTemplate,
			array(
				'user_id' => $arUser['ID'],
				'USER_ID' => $arUser['ID'],
			)
		);

		return $url;
	}

	public static function getNotificationPath($arUser, $taskID, $bUseServerName = true, $arSites = array())
	{
		if(!is_array($arUser) || !intval($taskID))
		{
			return false;
		}

		static $siteCache = array();

		$siteID = false;
		$arTask = static::getTaskBaseByTaskId($taskID);

		if (is_array($arTask) && !empty($arTask))
		{
			if(!is_array($arSites) || empty($arSites))
			{
				$arSites = \Bitrix\Tasks\Util\Site::getPair();
			}

			// we have extranet and the current user is an extranet user
			$bExtranet = 	\Bitrix\Tasks\Integration\Extranet\User::isExtranet($arUser["ID"]);
			// task is in a group
			$useGroup = 	$arTask['GROUP_ID'] && self::checkUserCanViewGroupExtended($arUser['ID'], $arTask['GROUP_ID']);

			// detect site id
			if($bExtranet)
			{
				$siteID = (string) CExtranet::GetExtranetSiteID();
			}
			else
			{
				if($useGroup)
				{
					$groupSiteList = self::getSocNetGroupSiteList($arTask['GROUP_ID']);
					foreach($groupSiteList as $groupSite)
					{
						if (
							isset($arSites['EXTRANET']['SITE_ID'])
							&& $groupSite['LID'] == $arSites['EXTRANET']['SITE_ID']
						)
						{
							continue;
						}

						$siteID = $groupSite['LID'];
						$siteCache[$groupSite['LID']] = $groupSite;
						break;
					}
				}
				else
				{
					$userData = static::getUser($arUser['ID']);
					if (isset($userData['LID']))
					{
						$siteID = $userData['LID'];
					}
				}

				if(!$siteID) // still not detected, use just intranet site
				{
					if(isset($arSites['INTRANET']['SITE_ID']))
						$siteID = $arSites['INTRANET']['SITE_ID'];
					else
						$siteID = (string) SITE_ID;
				}
			}

			// get site
			if(!isset($siteCache[$siteID]))
			{
				if((string) $siteID != '')
				{
					$siteCache[$siteID] = \Bitrix\Main\SiteTable::getList(array(
						'filter' => array('=LID' => $siteID),
						'select' => array('SITE_ID' => 'LID', 'DIR', 'SERVER_NAME'),
						'limit' => 1
					))->fetch();
				}
			}

			if(!is_array($siteCache[$siteID]))
			{
				return false;// still no site??? abort!
			}

			// choose template
			if ($useGroup)
			{
				$pathTemplate = str_replace(
					array('#group_id#', '#GROUP_ID#'),
					$arTask["GROUP_ID"],
					CTasksTools::GetOptionPathTaskGroupEntry(
						$siteID,
						$siteCache[$siteID]['DIR'] . "workgroups/group/#group_id#/tasks/task/view/#task_id#/"
					)
				);
				$workgroupsPage = Option::get('socialnetwork', 'workgroups_page', $siteCache[$siteID]['DIR'] . 'workgroups/', $siteID);
				$pathTemplate = '#GROUPS_PATH#' . mb_substr($pathTemplate, mb_strlen($workgroupsPage), mb_strlen($pathTemplate) - mb_strlen($workgroupsPage));
				$processed = CSocNetLogTools::ProcessPath(array("TASK_URL" => $pathTemplate), $arUser['ID'], $siteID);
				$pathTemplate = $processed['URLS']['TASK_URL'];
			}
			else
			{
				$pathTemplate = CTasksTools::GetOptionPathTaskUserEntry(
					$siteID,
					$siteCache[$siteID]['DIR'] . ($bExtranet ? 'contacts' : 'company') . "/personal/user/#user_id#/tasks/task/view/#task_id#/"
				);
			}

			// detect site name
			$serverName = '';
			if($bUseServerName)
			{
				$serverName =
					\Bitrix\Tasks\V2\Internal\DI\Container::getInstance()
						->getUrlService()
						->getHostUrl((string)$siteCache[$siteID]['SERVER_NAME'])
				;
			}

			$strUrl = $serverName
				. CComponentEngine::MakePathFromTemplate(
					$pathTemplate,
					array(
						'user_id' => $arUser['ID'],
						'USER_ID' => $arUser['ID'],
						'task_id' => $taskID,
						'TASK_ID' => $taskID,
						'action'  => 'view'
					)
				);

			return ($strUrl);
		}

		return false;
	}

	private static function prepareRightsCodesForViewInGroupLiveFeed($logID, $groupId)
	{
		$arRights = array();

		if ($groupId)
			$arRights = array('SG' . $groupId);

		return ($arRights);
	}

	private static function checkUserIsIntranet($userId)
	{
		if(!isset(self::$cache['INTRANET_USERS'][$userId]) || !self::$cacheData)
		{
			self::$cache['INTRANET_USERS'][$userId] = CTasksTools::IsIntranetUser($userId);
		}

		return self::$cache['INTRANET_USERS'][$userId];
	}

	private static function getSocNetGroupSiteList($id)
	{
		if(!CModule::IncludeModule('socialnetwork'))
		{
			return array();
		}

		$bitrix24Installed = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24');
		$extranetInstalled = \Bitrix\Main\ModuleManager::isModuleInstalled('extranet');

		if(!isset(self::$cache['GROUP_SITE_LIST'][$id]) || !self::$cacheData)
		{
			self::$cache['GROUP_SITE_LIST'][$id] = array();
			$res = CSocNetGroup::GetSite($id);
			while($item = $res->fetch())
			{
				if (
					$item['ACTIVE'] == 'N'
					|| (
						!$extranetInstalled
						&& (
							(
								$bitrix24Installed
								&& $item['LID'] == 'ex'
							)
							|| $item['LID'] === Option::get('extranet', 'extranet_site') // extranet uninstalled with 'Save data' option
						)
					)
				)
				{
					continue;
				}

				self::$cache['GROUP_SITE_LIST'][$id][] = $item;
			}
		}

		return self::$cache['GROUP_SITE_LIST'][$id];
	}

	private static function getSocNetGroup($id)
	{
		if(!CModule::IncludeModule('socialnetwork'))
		{
			return array();
		}

		if(!isset(self::$cache['GROUPS'][$id]) || !self::$cacheData)
		{
			$item = CSocNetGroup::GetList(array(), array('ID' => $id), false, false, array('ID', 'NAME'))->fetch();
			if(!empty($item))
			{
				if (!empty($item['NAME']))
				{
					$item['NAME'] = \Bitrix\Main\Text\Emoji::decode($item['NAME']);
				}
				self::$cache['GROUPS'][$id] = $item;
			}
		}

		return self::$cache['GROUPS'][$id];
	}

	private static function checkUserCanViewGroupExtended($userId, $groupId)
	{
		if(!isset(self::$cache['GROUP_ACCESS_EXT'][$groupId][$userId]) || !self::$cacheData)
		{
			self::$cache['GROUP_ACCESS_EXT'][$groupId][$userId] = CTasksTools::HasUserReadAccessToGroup($userId, $groupId);
		}

		return self::$cache['GROUP_ACCESS_EXT'][$groupId][$userId];
	}

	/**
	 * @access private
	 */
	public static function getUsers(array $ids = array())
	{
		if(empty($ids))
		{
			return array();
		}

		if (
			!isset(self::$cache['USER'])
			|| !is_array(self::$cache['USER'])
			|| !self::$cacheData
		)
		{
			self::$cache['USER'] = [];
		}

		$absent = array_diff($ids, array_keys(self::$cache['USER']));

		if(!empty($absent))
		{
			$res = CUser::GetList(
				'ID',
				'ASC',
				array('ID' => implode('|', $absent)),
				array('FIELDS' => array('NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'EMAIL', 'ID', 'PERSONAL_GENDER', 'EXTERNAL_AUTH_ID', 'LID'))
			);
			while($item = $res->fetch())
			{
				self::$cache['USER'][$item['ID']] = $item;
			}
		}

		$ids = array_flip($ids);
		foreach($ids as $userId => $void)
		{
			$ids[$userId] = self::$cache['USER'][$userId];
		}

		return $ids;
	}

	/**
	 * @access private
	 */
	public static function getUser($id)
	{
		if(!intval($id))
		{
			return false;
		}

		$users = CTaskNotifications::getUsers(array($id));
		return $users[$id];
	}

	private static function getTaskBaseByTaskId($taskId)
	{
		if(!isset(static::$cache['TASK2GROUP']) || !is_array(static::$cache['TASK2GROUP']))
		{
			static::$cache['TASK2GROUP'] = array();
		}

		if(!isset(static::$cache['TASK2GROUP'][$taskId]))
		{
			$item = CTasks::getList(
				[],
				[ 'ID' => $taskId ],
				[ 'ID', 'GROUP_ID' ],
				[ 'USER_ID' => User::getAdminId() ]
			)->fetch();
			if(is_array($item) && !empty($item))
			{
				static::$cache['TASK2GROUP'][$taskId] = $item;
			}
		}

		return (static::$cache['TASK2GROUP'][$taskId] ?? null);
	}

	########################
	# mode togglers

	/**
	 * Enable sending messages to IM
	 */
	public static function enableInstantNotifications()
	{
		if(!self::$suppressIM) // already enabled
		{
			return false;
		}

		self::$suppressIM = false;
	}

	/**
	 * Disable sending messages to IM
	 */
	public static function disableInstantNotifications()
	{
		if(self::$suppressIM) // already disabled
		{
			return false;
		}

		self::$suppressIM = true;
	}

	private static function enableStaticCache()
	{
		if(self::$cacheData) // already enabled
		{
			return false;
		}

		self::$cacheData = true;
		self::clearStaticCache();

		return true;
	}

	private static function disableStaticCache()
	{
		if(!self::$cacheData) // already disabled
		{
			return false;
		}

		self::$cacheData = true;
		self::clearStaticCache();

		return true;
	}

	private static function clearStaticCache()
	{
		self::$cache = array();
	}

	public static function disableAutoDeliver()
	{
		if(self::$bufferize) // already disabled
		{
			return false;
		}

		self::$bufferize = true;
		self::enableStaticCache();
		return true;
	}

	public static function enableAutoDeliver($flushNow = true)
	{
		self::$bufferize = false;
		if($flushNow)
		{
			self::flushNotificationBuffer();
		}
		self::disableStaticCache();
	}

	########################
	# deprecated

	/**
	 * @deprecated
	 */
	public static function __UserIDs2Rights($arUserIDs)
	{
		$arUserIDs = array_unique(array_filter($arUserIDs));
		$arRights = array();
		foreach($arUserIDs as $userID)
			$arRights[] = "U".$userID;

		return $arRights;
	}

	/**
	 * @deprecated
	 */
	public static function __Fields2Names($arFields)
	{

		$arFields = array_unique(array_filter($arFields));
		$arNames = array();
		$locMap = [
			'NEW_FILES' => 'FILES',
			'DELETED_FILES' => 'FILES',
			'START_DATE_PLAN' => 'START_DATE_PLAN',
			'END_DATE_PLAN' => 'END_DATE_PLAN',
		];
		foreach($arFields as $field)
		{
			$field = $locMap[$field] ?? $field;
			$message = Loc::getMessage('TASKS_SONET_LOG_' . $field);
			if(empty($message))
			{
				$message = Loc::getMessage('TASKS_SONET_LOG_' . $field . '_MSGVER_1');
			}

			$arNames[] = $message;
		}

		return array_unique(array_filter($arNames));
	}

	/**
	 * @deprecated
	 */
	public static function GetRecipientsIDs($arFields, $bExcludeCurrent = true, $bExcludeAdditionalRecipients = false, $currentUserId = false)
	{
		$currentUserIDFound = null;
		if ($bExcludeAdditionalRecipients)
		{
			$arFields['ADDITIONAL_RECIPIENTS'] = [];
		}

		if ( ! isset($arFields['ADDITIONAL_RECIPIENTS']) )
		{
			$arFields['ADDITIONAL_RECIPIENTS'] = [];
		}

		if ( ! isset($arFields['IGNORE_RECIPIENTS']) || ! is_array($arFields['IGNORE_RECIPIENTS']) )
		{
			$arFields['IGNORE_RECIPIENTS'] = [];
		}

		$arRecipientsIDs = array_unique(
			array_filter(
				array_merge(
					array($arFields["CREATED_BY"], $arFields["RESPONSIBLE_ID"]),
					(array) ($arFields["ACCOMPLICES"] ?? []),
					(array) ($arFields["AUDITORS"] ?? []),
					(array) ($arFields['ADDITIONAL_RECIPIENTS'] ?? [])
					)));

		if (!empty($arFields['IGNORE_RECIPIENTS']))
		{
			foreach ($arRecipientsIDs as $key => $value)
			{
				if (in_array($value, $arFields['IGNORE_RECIPIENTS']))
				{
					unset($arRecipientsIDs[$key]);
				}
			}
		}

		if ($bExcludeCurrent)
		{
			if($currentUserId !== false)
			{
				$currentUserIDFound = $currentUserId;
			}
			elseif(User::getId())
			{
				$currentUserIDFound = User::getId();
			}

			if($currentUserIDFound)
			{
				$currentUserPos = array_search($currentUserIDFound, $arRecipientsIDs);
				if ($currentUserPos !== false)
				{
					unset($arRecipientsIDs[$currentUserPos]);
				}
			}
		}

		return $arRecipientsIDs;
	}

	private static function notifyByMail(array $message, array $site)
	{
		if (
			!is_array($message)
			|| !isset($message["ENTITY_CODE"])
			|| !isset($message["FROM_USER_ID"])
			|| !isset($message["TASK_ID"])
			|| !isset($message["TO_USER_IDS"])
			|| !is_array($message["TO_USER_IDS"])
			|| empty($message["TO_USER_IDS"])
		)
		{
			return false;
		}

		if (!\Bitrix\Tasks\Integration\Mail::isInstalled())
		{
			return false;
		}

		if(!is_array($message["TO_USER_IDS"]) || empty($message["TO_USER_IDS"]))
		{
			return false;
		}

		// ids
		$authorId = (int)$message["FROM_USER_ID"];
		$taskId = (int)$message["TASK_ID"];

		// check event type
		$entityCode = trim($message["ENTITY_CODE"]);
		$entityOperation = trim($message["ENTITY_OPERATION"]);

		// site detect
		if(!is_array($site) || empty($site) || empty($site["SITE_ID"]))
		{
			$site = \Bitrix\Tasks\Util\Site::get(SITE_ID);
		}
		if(empty($site["SITE_ID"])) // no way, this cant be true
		{
			return false;
		}
		$siteId = $site["SITE_ID"];

		// event type
		$eventId = false;
		$threadMessageId = false;
		$prevFields = array();
		$commentId = 0;
		$taskTitle = '';
		$subjPrefix = '';
		if($entityCode === 'TASK')
		{
			if($entityOperation === 'ADD' || $entityOperation === 'UPDATE')
			{
				$eventId = 'TASKS_TASK_'.$entityOperation.'_EMAIL';
				$threadMessageId = \Bitrix\Tasks\Integration\Mail::formatThreadId('TASK_'.$taskId, $siteId);
			}

			if($entityOperation === 'UPDATE')
			{
				$threadMessageId = \Bitrix\Tasks\Integration\Mail::formatThreadId(
					sprintf('TASK_UPDATE_%u_%x%x', $taskId, time(), rand(0, 0xffffff)),
					$siteId
				);

				$prevFields = $message["EVENT_DATA"]['arChanges'];
				$subjPrefix = \Bitrix\Tasks\Integration\Mail::getSubjectPrefix();
			}

			if($message["EVENT_DATA"]["arFields"])
			{
				$taskTitle = trim($message["EVENT_DATA"]["arFields"]['TITLE']);
			}
		}
		elseif($entityCode === 'COMMENT')
		{
			if($entityOperation === 'ADD')
			{
				$eventId = 'TASKS_TASK_COMMENT_ADD_EMAIL';

				$commentId = $message["EVENT_DATA"]['MESSAGE_ID'];
				if(!$commentId)
				{
					// unable to identify comment id, exit
					return false;
				}

				$threadMessageId = \Bitrix\Tasks\Integration\Mail::formatThreadId('TASK_COMMENT_'.$commentId, $siteId);
				$subjPrefix = \Bitrix\Tasks\Integration\Mail::getSubjectPrefix();
			}

			if($message["ADDITIONAL_DATA"]['TASK_DATA'])
			{
				$taskTitle = trim($message["ADDITIONAL_DATA"]['TASK_DATA']['TITLE']);
			}
		}
		if($eventId === false)
		{
			return false; // unknown action
		}

		// email letter data
		$pathToTask = \Bitrix\Tasks\Integration\Mail\Task::getDefaultPublicPath($taskId);

		$users = static::getUsers(array_merge(array($authorId), $message["TO_USER_IDS"]));
		foreach($users as $i => $user)
		{
			$users[$i]['NAME_FORMATTED'] = User::formatName($users[$i], $siteId);
		}

		$receiversData = \Bitrix\Tasks\Integration\Mail\User::getData($message["TO_USER_IDS"], $siteId);
		if(empty($receiversData))
		{
			return false; // nowhere to send
		}

		foreach ($receiversData as $userId => $arUser)
		{
			$email = $arUser["EMAIL"];
			$nameFormatted = str_replace(array('<', '>', '"'), '', $arUser["NAME_FORMATTED"]);

			$replyTo = \Bitrix\Tasks\Integration\Mail\Task::getReplyTo(
				$userId,
				$taskId,
				$pathToTask,
				$siteId
			);
			if ($replyTo != '')
			{
				$authorName = str_replace(array('<', '>', '"'), '', $users[$authorId]['NAME_FORMATTED']);

				$e = array(
					"=Reply-To" => $authorName.' <'.$replyTo.'>',
					"=Message-Id" => $threadMessageId,
					"EMAIL_FROM" => $authorName.' <'.\Bitrix\Tasks\Integration\Mail::getDefaultEmailFrom($siteId).'>',
					"EMAIL_TO" => (!empty($nameFormatted) ? ''.$nameFormatted.' <'.$email.'>' : $email),

					"TASK_ID" => $taskId,
					"TASK_COMMENT_ID" => $commentId,
					"TASK_TITLE" => $taskTitle,
					"TASK_PREVIOUS_FIELDS" => \Bitrix\Tasks\Util\Type::serializeArray($prevFields),

					"RECIPIENT_ID" => $userId,
					"USER_ID" => User::getAdminId(),

					"URL" => $pathToTask,
					"SUBJECT" => $subjPrefix.$taskTitle
				);

				if (!('TASK' === $entityCode && 'ADD' === $entityOperation))
				{
					$e['=In-Reply-To'] = \Bitrix\Tasks\Integration\Mail::formatThreadId('TASK_'.$taskId, $siteId);
				}

				CEvent::Send(
					$eventId,
					$siteId,
					$e
				);
			}
		}
	}
}
