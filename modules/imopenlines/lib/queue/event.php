<?php
namespace Bitrix\ImOpenLines\Queue;

use \Bitrix\ImOpenLines,
	\Bitrix\ImOpenLines\Session,
	\Bitrix\ImOpenLines\QueueManager,
	\Bitrix\ImOpenLines\Model\QueueTable,
	\Bitrix\ImOpenLines\Model\ConfigTable,
	\Bitrix\ImOpenLines\Model\ConfigQueueTable,
	\Bitrix\ImOpenLines\Model\SessionCheckTable;

use \Bitrix\Main\Loader,
	\Bitrix\Main\UserTable;

use \Bitrix\Intranet\UserAbsence;

use \Bitrix\Iblock\SectionTable;

/**
 * Class Event
 * @package Bitrix\ImOpenLines\Queue
 */
class Event
{
	const COUNT_SESSIONS_DEFAULT = 1; //default session count to transfer
	const COUNT_SESSIONS_REALTIME = 5; //sessions count to transfer to new or returned operator

	private static $userFieldsUpdate = [];
	private static $linesUsersDelete = [];
	private static $linesDepartmentsDelete = [];
	private static $linesDepartmentsUpdate = [];

	//initialization
	/**
	 * @param $configLine
	 * @return bool|\Bitrix\ImOpenLines\Queue\Event\Evenly|\Bitrix\ImOpenLines\Queue\Event\All|\Bitrix\ImOpenLines\Queue\Event\Strictly
	 * @return Event\All|Event\Evenly|Event\Strictly|bool
	 */
	public static function initialization($configLine)
	{
		$result = false;

		$config = ConfigTable::getById($configLine)->fetch();

		if(!empty($config))
		{
			$result = self::initializationUsingConfiguration($config);
		}

		return $result;
	}

	/**
	 * @param $configLine
	 * @return bool|\Bitrix\ImOpenLines\Queue\Event\Evenly|\Bitrix\ImOpenLines\Queue\Event\All|\Bitrix\ImOpenLines\Queue\Event\Strictly
	 */
	public static function initializationUsingConfiguration($configLine)
	{
		$result = false;

		if(
			!empty($configLine) &&
			!empty($configLine['QUEUE_TYPE']) && !empty(ImOpenLines\Queue::$type[$configLine['QUEUE_TYPE']])
		)
		{
			$queue = "Bitrix\\ImOpenLines\\Queue\\Event\\" . ucfirst(mb_strtolower($configLine['QUEUE_TYPE']));

			$result = new $queue($configLine);
		}

		return $result;
	}
	//END initialization

	//Edit line
	/**
	 * Changing the queue type.
	 *
	 * @param \Bitrix\Main\Event $event
	 */
	public static function onQueueTypeChange(\Bitrix\Main\Event $event)
	{
		$eventData = $event->getParameters();

		if (!empty($eventData['line']))
		{
			$sessionList = SessionCheckTable::getList(
				[
					'select' => ['SESSION_ID'],
					'filter' => [
						'SESSION.CONFIG_ID' => $eventData['line'],
						'<SESSION.STATUS' => Session::STATUS_ANSWER,
						'!=SESSION.OPERATOR_FROM_CRM' => 'Y'
					]
				]
			)->fetchAll();

			foreach ($sessionList as $session)
			{
				ImOpenLines\Queue::returnSessionToQueue($session['SESSION_ID'], ImOpenLines\Queue::REASON_QUEUE_TYPE_CHANGED);
			}

			ImOpenLines\Debug::addQueueEvent( __METHOD__, $eventData['line'], 0, ['eventData' => $eventData, 'sessionList' => $sessionList]);
		}
	}
	//END Edit line

	//Return user to queue
	/**
	 * Added operator to the queue.
	 *
	 * @param \Bitrix\Main\Event $event
	 */
	public static function onQueueOperatorsAdd(\Bitrix\Main\Event $event)
	{
		$eventData = $event->getParameters();
		$eventData['line'] = (int)$eventData['line'];

		if (
			$eventData['line'] > 0 &&
			!empty($eventData['operators']) &&
			is_array($eventData['operators'])
		)
		{
			self::returnUserToQueue($eventData['operators'], $eventData['line']);

			ImOpenLines\Debug::addQueueEvent( __METHOD__, $eventData['line'], 0, ['eventData' => $eventData]);
		}
	}

	/**
	 * Start of working time.
	 *
	 * @param $data
	 */
	public static function onAfterTMDayStart($data)
	{
		$userId = $data['USER_ID'];

		if (!empty($userId) && is_numeric($userId) && ImOpenLines\Queue::isRealOperator($userId))
		{
			self::returnUserToAllQueues($data['USER_ID'], true);
			//ImOpenLines\KpiManager::operatorDayStart($userId);

			ImOpenLines\Debug::addQueueEvent( __METHOD__, 0, 0, ['data' => $data]);
		}
	}

	/**
	 * The working day continued after a pause.
	 *
	 * @param $data
	 */
	public static function onAfterTMDayContinue($data)
	{
		self::onAfterTMDayStart($data);
	}

	/**
	 * The event of end of holiday.
	 *
	 * @param \Bitrix\Main\Event $event
	 */
	public static function OnEndAbsence(\Bitrix\Main\Event $event)
	{
		$eventData = $event->getParameters();

		if(
			!empty($eventData['USER_ID']) &&
			!empty($eventData['ABSENCE_TYPE'])
		)
		{
			$isVacation = true;

			if(
				Loader::includeModule('intranet') &&
				!UserAbsence::isVacation($eventData['ABSENCE_TYPE'])
			)
			{
				$isVacation = false;
			}

			if (
				$isVacation === true &&
				ImOpenLines\Queue::isRealOperator($eventData['USER_ID'])
			)
			{
				self::returnUserToAllQueues($eventData['USER_ID']);
			}

			ImOpenLines\Debug::addQueueEvent( __METHOD__, 0, 0, ['eventData' => $eventData]);
		}
	}

	/**
	 * Send recent messages to operator in all queues he in when he return to work.
	 *
	 * @param $userId
	 * @param bool $checkTimeman
	 */
	protected static function returnUserToAllQueues($userId, $checkTimeman = false)
	{
		$filter = [
			'USER_ID' => $userId,
			'!==CONFIG.ID' => null
		];

		if ($checkTimeman)
		{
			$filter['CONFIG.CHECK_AVAILABLE'] = 'Y';
		}

		$queueList = QueueTable::getList(
			[
				'select' => ['CONFIG_ID'],
				'filter' => $filter,
				'order' => [
					'SORT' => 'ASC',
					'ID' => 'ASC'
				]
			]
		)->fetchAll();

		foreach ($queueList as $queue)
		{
			self::returnUserToQueue([$userId], $queue['CONFIG_ID']);
		}
	}

	/**
	 * Send recent messages to operator in current queue when he return to work.
	 *
	 * @param $userIds
	 * @param $lineId
	 */
	protected static function returnUserToQueue(array $userIds, $lineId): void
	{
		$configManager = self::initialization($lineId);
		if(!empty($configManager))
		{
			$configManager->returnUserToQueue($userIds);
		}
	}
	//END Return user to queue

	//Operator temporarily unavailable
	/**
	 * The working day is put on pause.
	 *
	 * @param $data
	 */
	public static function onAfterTMDayPause($data)
	{
		self::onAfterTMDayEnd($data);
	}

	/**
	 * The working day was over.
	 *
	 * @param $data
	 */
	public static function onAfterTMDayEnd($data): void
	{
		$userId = $data['USER_ID'];

		if (!empty($userId) && is_numeric($userId) && ImOpenLines\Queue::isRealOperator($userId))
		{
			$listLine = self::getLineIsSessionOperator($userId, true);

			if (!empty($listLine))
			{
				foreach ($listLine as $lineId)
				{
					if (!\Bitrix\ImOpenLines\Queue::isOperatorSingleInLine($lineId, $userId))
					{
						$configManager = self::initialization($lineId);
						if(!empty($configManager))
						{
							$configManager->returnNotAcceptedSessionsToQueue($userId, ImOpenLines\Queue::REASON_OPERATOR_DAY_END);
						}
					}
				}
			}

			//ImOpenLines\KpiManager::operatorDayEnd($userId);

			ImOpenLines\Debug::addQueueEvent( __METHOD__, 0, 0, ['data' => $data, 'listLine' => $listLine]);
		}
	}
	//END Operator temporarily unavailable


	//Removing an operator
	/**
	 * Remove an operator from the queue.
	 *
	 * @param \Bitrix\Main\Event $event
	 *
	 * @return bool
	 */
	public static function onQueueOperatorsDelete(\Bitrix\Main\Event $event)
	{
		$eventData = $event->getParameters();

		if (
			!empty($eventData['line']) &&
			is_array($eventData['operators']) &&
			count($eventData['operators']) > 0
		)
		{
			$configManager = self::initialization($eventData['line']);
			if(!empty($configManager))
			{
				$configManager->returnSessionsUsersToQueue($eventData['operators'], ImOpenLines\Queue::REASON_REMOVED_FROM_QUEUE);
			}

			ImOpenLines\Debug::addQueueEvent( __METHOD__, 0, 0, ['eventData' => $eventData]);
		}

		return true;
	}

	/**
	 * Changing the list of responsible persons in the open line queue.
	 *
	 * @param \Bitrix\Main\Event $event
	 * @return bool
	 */
	public static function OnQueueOperatorsChange(\Bitrix\Main\Event $event): bool
	{
		$eventData = $event->getParameters();

		//if amount of operators has been increased from 1, then we need to return his not accepted sessions to queue
		if (
			!empty($eventData['line']) &&
			is_array($eventData['operators_before']) &&
			is_array($eventData['operators_after']) &&
			count($eventData['operators_before']) <= 1 &&
			count($eventData['operators_after']) >= 2
		)
		{
			foreach ($eventData['operators_before'] as $singleOperatorId)
			{
				$queueInstance = self::initialization($eventData['line']);
				if(!empty($queueInstance))
				{
					$operatorActive = $queueInstance->isOperatorActive($singleOperatorId);
					if ($operatorActive !== true)
					{
						$queueInstance->returnNotAcceptedSessionsToQueue(
							$singleOperatorId,
							$operatorActive
						);
					}
				}
			}
		}

		return true;
	}

	/**
	 * Delete the user.
	 *
	 * @param $userId
	 *
	 * @return bool
	 */
	public static function onUserDelete($userId)
	{
		if (
			!empty($userId) &&
			is_numeric($userId) &&
			ImOpenLines\Queue::isRealOperator($userId)
		)
		{
			$linesOperator = self::getLineIsOperator($userId);
			$linesIsSessionOperator = self::getLineIsSessionOperatorNotLine($userId);

			if (!empty($linesIsSessionOperator))
			{
				foreach ($linesIsSessionOperator as $lineId)
				{
					$configManager = self::initialization($lineId);
					if(!empty($configManager))
					{
						$configManager->returnSessionsUsersToQueue([$userId], ImOpenLines\Queue::REASON_OPERATOR_DELETED);
					}
				}
			}

			if (!empty($linesOperator))
			{
				self::$linesUsersDelete[$userId] = $linesOperator;
				foreach ($linesOperator as $lineId)
				{
					$queueManager = new QueueManager($lineId);
					$queueManager->deleteItemsConfigQueue([['ENTITY_ID' => $userId, 'ENTITY_TYPE' => 'user']]);
				}
			}

			ImOpenLines\Debug::addQueueEvent( __METHOD__, 0, 0, ['userId' => $userId, 'linesOperator' => $linesOperator, 'linesIsSessionOperator' => $linesIsSessionOperator]);
		}

		return true;
	}

	/**
	 * @param $sectionId
	 */
	public static function OnBeforeDepartmentsDelete($sectionId)
	{
		if (Loader::includeModule('iblock'))
		{
			$idIblockStructure = QueueManager::getIdIblockStructure();

			if(!empty($idIblockStructure))
			{
				$res = SectionTable::getList([
					'filter' => [
						'=IBLOCK_ID' => $idIblockStructure,
						'=ID' => $sectionId
					]
				]);

				if($res->getSelectedRowsCount())
				{
					$lines = self::getLineIsDepartmentQueue([$sectionId]);

					if (!empty($lines))
					{
						self::$linesDepartmentsDelete[$sectionId] = $lines;
						foreach ($lines as $lineId)
						{
							$queueManager = new QueueManager($lineId);
							$queueManager->deleteItemsConfigQueue([['ENTITY_ID' => $sectionId, 'ENTITY_TYPE' => 'department']]);
						}
					}
				}
			}
		}
	}

	/**
	 * @param $fields
	 */
	public static function OnAfterDepartmentsDelete($fields)
	{
		if (!empty(self::$linesDepartmentsDelete[$fields['ID']]))
		{
			QueueManager::resetCacheStructureDepartments();
			foreach (self::$linesDepartmentsDelete[$fields['ID']] as $lineId)
			{
				$queueManager = new QueueManager($lineId);
				$queueManager->refresh();
			}

			unset(self::$linesDepartmentsDelete[$fields['ID']]);
		}
	}

	/**
	 * @param $fields
	 */
	public static function OnBeforeDepartmentsUpdate(&$fields)
	{
		if (
			Loader::includeModule('iblock') &&
			((int)$fields['IBLOCK_ID']) > 0 &&
			((int)$fields['ID']) > 0 &&
			(int)$fields['IBLOCK_ID'] === QueueManager::getIdIblockStructure()
		)
		{
			$raw = \CIBlockSection::GetList([], [
				'IBLOCK_ID' => $fields['IBLOCK_ID'],
				'ID' => $fields['ID'],
			],
			false,
			[
				'UF_HEAD',
				'IBLOCK_SECTION_ID'
			]);
			if ($oldFields = $raw->GetNext())
			{
				if(
					isset($fields['UF_HEAD']) &&
					(int)$fields['UF_HEAD'] !== (int)$oldFields['UF_HEAD']
				)
				{
					$lines = self::getLineIsDepartmentQueue([$fields['ID']]);
					if (!empty($lines))
					{
						self::$linesDepartmentsUpdate[$fields['ID']] = $lines;
					}
				}
				elseif(
					isset($fields['IBLOCK_SECTION_ID']) &&
					(int)$fields['IBLOCK_SECTION_ID'] !== (int)$oldFields['IBLOCK_SECTION_ID']
				)
				{
					$lines = self::getLineIsDepartmentQueue([$fields['IBLOCK_SECTION_ID'], $oldFields['IBLOCK_SECTION_ID']]);
					if (!empty($lines))
					{
						self::$linesDepartmentsUpdate[$fields['ID']] = $lines;
					}
				}
			}
		}
	}

	/**
	 * @param $fields
	 */
	public static function OnAfterDepartmentsUpdate(&$fields)
	{
		if(
			isset($fields['ID']) &&
			((int)$fields['ID']) > 0 &&
			!empty(self::$linesDepartmentsUpdate[$fields['ID']])
		)
		{
			QueueManager::resetCacheStructureDepartments();
			foreach (self::$linesDepartmentsUpdate[$fields['ID']] as $lineId)
			{
				$queueManager = new QueueManager($lineId);
				$queueManager->refresh();
			}
		}
	}

	/**
	 * @param $userId
	 */
	public static function OnAfterUserDelete($userId)
	{
		if (
			!empty($userId) &&
			is_numeric($userId) &&
			!empty(self::$linesUsersDelete[$userId])
		)
		{
			foreach (self::$linesUsersDelete[$userId] as $lineId)
			{
				$queueManager = new QueueManager($lineId);
				$queueManager->refresh();
			}

			unset(self::$linesUsersDelete[$userId]);
		}
	}

	/**
	 * @param $userFields
	 */
	public static function onUserAdd(&$userFields)
	{
		if(
			$userFields['RESULT'] &&
			(
				!isset($userFields['ACTIVE']) ||
				$userFields['ACTIVE'] === 'Y'
			) &&
			!empty($userFields['UF_DEPARTMENT'])
		)
		{
			$lines = self::getLineIsDepartmentQueue($userFields['UF_DEPARTMENT']);

			if (!empty($lines))
			{
				foreach ($lines as $lineId)
				{
					$queueManager = new QueueManager($lineId);
					$queueManager->refresh();
				}
			}
		}

		self::$userFieldsUpdate = false;
	}

	/**
	 * @param $userFields
	 */
	public static function onUserUpdateBefore(&$userFields)
	{
		if(
			isset($userFields['ACTIVE']) ||
			isset($userFields['UF_DEPARTMENT'])
		)
		{
			$usersManager = UserTable::getList([
				'select' => [
					'ID',
					'ACTIVE',
					'UF_DEPARTMENT'
				],
				'filter' => [
					'=ID' => $userFields['ID']
				]
			]);
			if ($user = $usersManager->fetch())
			{
				self::$userFieldsUpdate = $user;
			}
		}
	}

	/**
	 * User update.
	 *
	 * @param $userFields
	 */
	public static function onUserUpdate(&$userFields)
	{
		if(
			$userFields['RESULT'] === true &&
			!empty(self::$userFieldsUpdate)
		)
		{
			if (
				isset($userFields['ACTIVE']) &&
				isset(self::$userFieldsUpdate['ACTIVE']) &&
				self::$userFieldsUpdate['ACTIVE'] === 'Y' &&
				$userFields['ACTIVE'] === 'N'
			)
			{
				self::onUserDelete($userFields['ID']);
			}
			else
			{
				$changedDepartments = [];

				if(
					isset($userFields['ACTIVE']) &&
					isset(self::$userFieldsUpdate['ACTIVE']) &&
					self::$userFieldsUpdate['ACTIVE'] === 'N' &&
					$userFields['ACTIVE'] === 'Y'
				)
				{
					if(isset($userFields['UF_DEPARTMENT']))
					{
						$changedDepartments = $userFields['UF_DEPARTMENT'];
					}
					else
					{
						$changedDepartments = self::$userFieldsUpdate['UF_DEPARTMENT'];
					}
				}
				elseif(
					isset($userFields['UF_DEPARTMENT'])
					&& is_array($userFields['UF_DEPARTMENT'])
					&& isset(self::$userFieldsUpdate['UF_DEPARTMENT'])
					&& is_array(self::$userFieldsUpdate['UF_DEPARTMENT'])
					&& (
						!isset($userFields['ACTIVE'])
						|| $userFields['ACTIVE'] === 'Y'
					)
				)
				{
					$changedDepartments = array_merge(
						array_diff($userFields['UF_DEPARTMENT'], self::$userFieldsUpdate['UF_DEPARTMENT']),
						array_diff(self::$userFieldsUpdate['UF_DEPARTMENT'], $userFields['UF_DEPARTMENT'])
					);
				}

				if(!empty($changedDepartments))
				{
					$lines = self::getLineIsDepartmentQueue($changedDepartments);

					if (!empty($lines))
					{
						foreach ($lines as $lineId)
						{
							$queueManager = new QueueManager($lineId);
							$queueManager->refresh();
						}
					}
				}
			}
		}

		self::$userFieldsUpdate = false;
	}
	//END Removing an operator

	//Absence
	/**
	 * Start of vacation.
	 */
	public static function OnStartAbsence(\Bitrix\Main\Event $event)
	{
		$eventData = $event->getParameters();

		$userId = $eventData['USER_ID'];

		if(
			!empty($userId) &&
			is_numeric($userId) &&
			ImOpenLines\Queue::isRealOperator($userId) &&
			!empty($eventData['ABSENCE_TYPE']) &&
			!empty($eventData['DURATION'])
		)
		{
			$durationAbsenceDay = floor($eventData['DURATION']/86400);
			$isVacation = true;
			$listLine = [];

			if(Loader::includeModule('intranet') && !UserAbsence::isVacation($eventData['ABSENCE_TYPE']))
			{
				$isVacation = false;
			}

			if ($isVacation === true && $durationAbsenceDay > 0)
			{
				$listLine = self::getLineIsSessionOperator($userId);

				if (!empty($listLine))
				{
					foreach ($listLine as $lineId)
					{
						if (!\Bitrix\ImOpenLines\Queue::isOperatorSingleInLine($lineId, $userId))
						{
							$configManager = self::initialization($lineId);
							if(!empty($configManager))
							{
								$configManager->returnSessionsUsersToQueueIsStartAbsence(
									[$userId],
									$durationAbsenceDay,
									ImOpenLines\Queue::REASON_OPERATOR_ABSENT
								);
							}
						}
					}
				}
			}

			ImOpenLines\Debug::addQueueEvent(
				__METHOD__,
				0,
				0,
				[
					'eventData' => $eventData,
					'durationAbsenceDay' => $durationAbsenceDay,
					'isVacation' => $isVacation,
					'listLine' => $listLine
				]
			);
		}
	}
	//END Absence

	/**
	 * @param $userId
	 * @param false $checkTimeman
	 * @return array
	 */
	public static function getLineIsOperator($userId, $checkTimeman = false)
	{
		$result = [];

		if(
			!empty($userId) &&
			is_numeric($userId) &&
			ImOpenLines\Queue::isRealOperator($userId)
		)
		{
			$filterQueue = [
				'USER_ID' => $userId,
			];

			if($checkTimeman)
			{
				$filterQueue['=CONFIG.CHECK_AVAILABLE'] = 'Y';
			}

			$queueListManager = QueueTable::getList(
				[
					'select' => ['CONFIG_ID'],
					'filter' => $filterQueue,
					'order' => [
						'SORT' => 'ASC',
						'ID' => 'ASC'
					]
				]
			);

			while ($queue = $queueListManager->fetch())
			{
				$result[$queue['CONFIG_ID']] = $queue['CONFIG_ID'];
			}
		}

		return $result;
	}

	/**
	 * @param $userId
	 * @param false $checkTimeman
	 * @param array $excludeLine
	 * @return array
	 */
	public static function getLineIsSessionOperatorNotLine($userId, $checkTimeman = false, $excludeLine = [])
	{
		$result = [];

		if(
			!empty($userId) &&
			is_numeric($userId) &&
			ImOpenLines\Queue::isRealOperator($userId)
		)
		{
			$filterSession = [
				'=SESSION.OPERATOR_ID' => $userId
			];

			if($checkTimeman)
			{
				$filterSession['=SESSION.CONFIG.CHECK_AVAILABLE'] = 'Y';
			}

			if(!empty($excludeLine))
			{
				$filterSession['!=SESSION.CONFIG_ID'] = $excludeLine;
			}

			$sessionListManager = SessionCheckTable::getList(
				[
					'select' => [
						'CONFIG_ID' => 'SESSION.CONFIG_ID'
					],
					'filter' => $filterSession,
					'group' => [
						'SESSION.CONFIG_ID'
					]
				]
			);

			while ($queue = $sessionListManager->fetch())
			{
				$result[$queue['CONFIG_ID']] = $queue['CONFIG_ID'];
			}
		}

		return $result;
	}

	/**
	 * Return all lines where the user is an operator or has active dialogs.
	 *
	 * @param $userId
	 * @param bool $checkTimeman
	 * @return array
	 */
	public static function getLineIsSessionOperator($userId, $checkTimeman = false)
	{
		$linesOperator = self::getLineIsOperator($userId, $checkTimeman);
		$linesIsSessionOperator = self::getLineIsSessionOperatorNotLine($userId, $checkTimeman, $linesOperator);

		return array_merge($linesOperator, $linesIsSessionOperator);
	}

	/**
	 * @param $departments
	 * @param bool $recursion
	 * @param bool $includeCurrentDepartment
	 * @return array
	 */
	public static function getParentDepartments($departments, $recursion = true, $includeCurrentDepartment = true): array
	{
		$result = [];

		foreach ($departments as $department)
		{
			$subordinateDepartments = QueueManager::getParentDepartments($department, $recursion, $includeCurrentDepartment);

			if(!empty($subordinateDepartments))
			{
				foreach ($subordinateDepartments as $idDepartment=>$subordinateDepartment)
				{
					$result[$idDepartment] = $subordinateDepartment;
				}
			}
		}

		return $result;
	}

	/**
	 * @param $departments
	 * @param bool $recursion
	 * @return array
	 */
	public static function getLineIsDepartmentQueue($departments, $recursion = true)
	{
		$result = [];

		$fullDepartments = self::getParentDepartments($departments, $recursion);

		if($fullDepartments)
		{
			$queueListManager = ConfigQueueTable::getList(
				[
					'select' => ['CONFIG_ID'],
					'filter' => [
						'ENTITY_TYPE' => 'department',
						'ENTITY_ID' => array_keys($fullDepartments)
					],
					'order' => [
						'SORT' => 'ASC',
						'ID' => 'ASC'
					]
				]
			);

			while ($queue = $queueListManager->fetch())
			{
				$result[$queue['CONFIG_ID']] = $queue['CONFIG_ID'];
			}
		}

		return $result;
	}

	/**
	 * OnChatAnswer event handler for filling free slots.
	 *
	 * @param \Bitrix\Main\Event $event
	 */
	public static function checkFreeSlotOnChatAnswer(\Bitrix\Main\Event $event)
	{
		$eventData = $event->getParameters();
		$config = $eventData['RUNTIME_SESSION']->getConfig();

		$configManager = self::initializationUsingConfiguration($config);

		if(!empty($configManager))
		{
			$configManager->checkFreeSlotOnChatAnswer();
		}
	}

	/**
	 * OnChatSkip/OnChatMarkSpam/OnChatFinish event handler for filling free slots.
	 *
	 * @param \Bitrix\Main\Event $event
	 */
	public static function checkFreeSlotOnFinish(\Bitrix\Main\Event $event)
	{
		$eventData = $event->getParameters();

		if($eventData['RUNTIME_SESSION'] instanceof Session)
		{
			self::checkSessionFreeSlotOnFinish($eventData['RUNTIME_SESSION']);
		}
	}

	/**
	 * OnOperatorTransfer event handler for filling free slots.
	 *
	 * @param \Bitrix\Main\Event $event
	 */
	public static function checkFreeSlotOnOperatorTransfer(\Bitrix\Main\Event $event)
	{
		$eventData = $event->getParameters();

		if($eventData['SESSION'] instanceof Session && $eventData['TRANSFER']['MODE'] == ImOpenLines\Chat::TRANSFER_MODE_MANUAL)
		{
			self::checkSessionFreeSlotOnFinish($eventData['SESSION']);
		}
	}

	/**
	 * OnChatSkip/OnChatMarkSpam/OnChatFinish/OnOperatorTransfer event handler for filling free slots.
	 *
	 * @param Session $session
	 */
	public static function checkSessionFreeSlotOnFinish(Session $session)
	{
		$config = $session->getConfig();

		$configManager = self::initializationUsingConfiguration($config);

		if(!empty($configManager))
		{
			$configManager->checkFreeSlotOnChatFinish();
		}
	}

	/**
	 * Method for checking free slots by sending message data.
	 *
	 * @param array $messageData
	 */
	public static function checkFreeSlotBySendMessage($messageData): void
	{
		if ($messageData['AUTHOR_ID'] > 0)
		{
			//TODO: Replace with the method \Bitrix\ImOpenLines\Chat::parseLinesChatEntityId or \Bitrix\ImOpenLines\Chat::parseLiveChatEntityId
			list($connectorId, $lineId) = explode('|', $messageData['CHAT_ENTITY_ID']);

			$configManager = self::initialization($lineId);
			if(!empty($configManager))
			{
				$configManager->checkFreeSlotOnMessageSend($messageData);
			}
		}
	}
}