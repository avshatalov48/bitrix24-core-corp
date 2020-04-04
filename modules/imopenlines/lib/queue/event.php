<?php
namespace Bitrix\ImOpenLines\Queue;

use \Bitrix\ImOpenLines,
	\Bitrix\ImOpenLines\Session,
	\Bitrix\ImOpenLines\Model\QueueTable,
	\Bitrix\ImOpenLines\Model\ConfigTable,
	\Bitrix\ImOpenLines\Model\SessionCheckTable;

use \Bitrix\Main\Loader;

use \Bitrix\Intranet\UserAbsence;

/**
 * Class Event
 * @package Bitrix\ImOpenLines\Queue
 */
class Event
{
	const COUNT_SESSIONS_DEFAULT = 1; //default session count to transfer
	const COUNT_SESSIONS_REALTIME = 5; //sessions count to transfer to new or returned operator

	//initialization
	/**
	 * @param array $configLine
	 * @return bool|\Bitrix\ImOpenLines\Queue\Event\Evenly|\Bitrix\ImOpenLines\Queue\Event\All|\Bitrix\ImOpenLines\Queue\Event\Strictly
	 * @return Event\All|Event\Evenly|Event\Strictly|bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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
	 * @param array $configLine
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
			$queue = "Bitrix\\ImOpenLines\\Queue\\Event\\" . ucfirst(strtolower($configLine['QUEUE_TYPE']));

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
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onQueueTypeChange(\Bitrix\Main\Event $event)
	{
		$eventData = $event->getParameters();

		if (!empty($eventData['line']))
		{
			$sessionList = SessionCheckTable::getList(
				[
					'select' => array('SESSION_ID'),
					'filter' => array(
						'SESSION.CONFIG_ID' => $eventData['line'],
						'<SESSION.STATUS' => Session::STATUS_ANSWER,
						'!=SESSION.OPERATOR_FROM_CRM' => 'Y'
					)
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
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onQueueOperatorsAdd(\Bitrix\Main\Event $event)
	{
		$eventData = $event->getParameters();
		$eventData['line'] = intval($eventData['line']);

		if ($eventData['line'] > 0 && !empty($eventData['operators']) && is_array($eventData['operators']))
		{
			self::returnUserToQueue($eventData['operators'], $eventData['line']);

			ImOpenLines\Debug::addQueueEvent( __METHOD__, $eventData['line'], 0, ['eventData' => $eventData]);
		}
	}

	/**
	 * Start of working time.
	 *
	 * @param $data
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onAfterTMDayContinue($data)
	{
		self::onAfterTMDayStart($data);
	}

	/**
	 * The event of end of holiday.
	 *
	 * @param \Bitrix\Main\Event $event
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function OnEndAbsence(\Bitrix\Main\Event $event)
	{
		$eventData = $event->getParameters();

		if(!empty($eventData['USER_ID']) && !empty($eventData['ABSENCE_TYPE']))
		{
			$isVacation = true;

			if(Loader::includeModule('intranet') && !UserAbsence::isVacation($eventData['ABSENCE_TYPE']))
			{
				$isVacation = false;
			}

			if ($isVacation === true && ImOpenLines\Queue::isRealOperator($eventData['USER_ID']))
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
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected static function returnUserToAllQueues($userId, $checkTimeman = false)
	{
		$filter = [
			'USER_ID' => $userId
		];

		if ($checkTimeman)
		{
			$filter['CONFIG.CHECK_AVAILABLE'] = 'Y';
		}

		$queueList = QueueTable::getList(
			[
				'select' => ['CONFIG_ID'],
				'filter' => $filter
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
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected static function returnUserToQueue(array $userIds, $lineId)
	{
		self::initialization($lineId)->returnUserToQueue($userIds);
	}
	//END Return user to queue



	//Operator temporarily unavailable
	/**
	 * The working day is put on pause.
	 *
	 * @param $data
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onAfterTMDayPause($data)
	{
		self::onAfterTMDayEnd($data);
	}

	/**
	 * The working day was over.
	 *
	 * @param $data
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onAfterTMDayEnd($data)
	{
		$userId = $data['USER_ID'];

		if (!empty($userId) && is_numeric($userId) && ImOpenLines\Queue::isRealOperator($userId))
		{
			$listLine = self::getLineIsSessionOperator($userId, true);

			if (!empty($listLine))
			{
				foreach ($listLine as $lineId)
				{
					self::initialization($lineId)->returnNotAcceptedSessionsToQueue($userId, ImOpenLines\Queue::REASON_OPERATOR_DAY_END);
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
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onQueueOperatorsDelete(\Bitrix\Main\Event $event)
	{
		$eventData = $event->getParameters();

		if (is_array($eventData['operators']) && count($eventData['operators']) > 0 && !empty($eventData['line']))
		{
			self::initialization($eventData['line'])->returnSessionsUsersToQueue($eventData['operators'], ImOpenLines\Queue::REASON_REMOVED_FROM_QUEUE);

			ImOpenLines\Debug::addQueueEvent( __METHOD__, 0, 0, ['eventData' => $eventData]);
		}

		return true;
	}

	/**
	 * Delete the user.
	 *
	 * @param $userId
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onUserDelete($userId)
	{
		if (!empty($userId) && is_numeric($userId) && ImOpenLines\Queue::isRealOperator($userId))
		{
			$listLine = self::getLineIsSessionOperator($userId);

			if (!empty($listLine))
			{
				foreach ($listLine as $lineId)
				{
					self::initialization($lineId)->returnSessionsUsersToQueue([$userId], ImOpenLines\Queue::REASON_OPERATOR_DELETED);
				}
			}

			ImOpenLines\Debug::addQueueEvent( __METHOD__, 0, 0, ['userId' => $userId, 'listLine' => $listLine]);
		}

		return true;
	}

	/**
	 * User update. Checking is disabled.
	 *
	 * @param $userFields
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onUserUpdate(&$userFields)
	{
		if ($userFields['ACTIVE'] == 'N')
		{
			self::onUserDelete($userFields['ID']);
		}
	}
	//END Removing an operator

	//Absence
	/**
	 * Start of vacation.
	 *
	 * @param \Bitrix\Main\Event $event
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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
						self::initialization($lineId)->returnSessionsUsersToQueueIsStartAbsence([$userId], $durationAbsenceDay, ImOpenLines\Queue::REASON_OPERATOR_ABSENT);
					}
				}
			}

			ImOpenLines\Debug::addQueueEvent( __METHOD__, 0, 0, ['eventData' => $eventData, 'durationAbsenceDay' => $durationAbsenceDay, 'isVacation' => $isVacation, 'listLine' => $listLine]);
		}
	}
	//END Absence

	/**
	 * Return all lines where the user is an operator or has active dialogs.
	 *
	 * @param $userId
	 * @param bool $checkTimeman
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getLineIsSessionOperator($userId, $checkTimeman = false)
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
					'filter' => $filterQueue
				]
			);

			while ($queue = $queueListManager->fetch())
			{
				$result[$queue['CONFIG_ID']] = $queue['CONFIG_ID'];
			}

			$filterSession = [
				'=SESSION.OPERATOR_ID' => $userId
			];

			if($checkTimeman)
			{
				$filterSession['=SESSION.CONFIG.CHECK_AVAILABLE'] = 'Y';
			}

			if(!empty($result))
			{
				$filterSession['!=SESSION.CONFIG_ID'] = $result;
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
	 * OnChatAnswer event handler for filling free slots.
	 *
	 * @param \Bitrix\Main\Event $event
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function checkFreeSlotOnChatAnswer(\Bitrix\Main\Event $event)
	{
		$eventData = $event->getParameters();
		$config = $eventData['RUNTIME_SESSION']->getConfig();

		self::initializationUsingConfiguration($config)->checkFreeSlotOnChatAnswer();
	}

	/**
	 * OnChatSkip/OnChatMarkSpam/OnChatFinish event handler for filling free slots.
	 *
	 * @param \Bitrix\Main\Event $event
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function checkSessionFreeSlotOnFinish(Session $session)
	{
		$config = $session->getConfig();

		self::initializationUsingConfiguration($config)->checkFreeSlotOnChatFinish();
	}

	/**
	 * Method for checking free slots by sending message data.
	 *
	 * @param array $messageData
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function checkFreeSlotBySendMessage($messageData)
	{
		if ($messageData['AUTHOR_ID'] > 0)
		{
			list($connectorId, $lineId) = explode('|', $messageData['CHAT_ENTITY_ID']);

			self::initialization($lineId)->checkFreeSlotOnMessageSend($messageData);
		}
	}
}