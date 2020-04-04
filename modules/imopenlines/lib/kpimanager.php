<?php

namespace Bitrix\ImOpenLines;

use Bitrix\ImOpenLines\Model\SessionCheckTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

use Bitrix\ImOpenLines\Model\SessionKpiMessagesTable;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\ImOpenLines\Model\ConfigTable;
use Bitrix\ImOpenLines\Model\QueueTable;

Loc::loadMessages(__FILE__);

class KpiManager
{
	protected $sessionId;

	/**
	 * KpiManager constructor.
	 *
	 * @param $sessionId
	 */
	public function __construct($sessionId)
	{
		$this->sessionId = $sessionId;
	}

	/**
	 * Return whole list of messages for current session
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getSessionMessages()
	{
		$filter = array(
			'SESSION_ID' => $this->sessionId
		);
		$messages = SessionKpiMessagesTable::getList(
			array(
				'filter' => $filter,
				'cache' => array('ttl' => 3600)
			)
		);

		return $messages->fetchAll();
	}

	/**
	 * Return a kpi message about first message in session.
	 *
	 * @return array|false
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getFirstMessage()
	{
		$filter = array(
			'=SESSION_ID' => $this->sessionId,
			'=IS_FIRST_MESSAGE' => 'Y'
		);
		$message = SessionKpiMessagesTable::getList(
			array(
				'filter' => $filter,
				'cache' => array('ttl' => 86400)
			)
		);

		return $message->fetch();
	}

	/**
	 * Return last kpi message in session we have to answer.
	 * There can be only one not answered message at a time
	 *
	 * @return array|false
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getNotAnsweredMessage()
	{
		$filter = array(
			'SESSION_ID' => $this->sessionId,
			'TIME_ANSWER' => null
		);
		$message = SessionKpiMessagesTable::getList(
			array(
				'filter' => $filter,
				//'cache' => array('ttl' => 3600)
			)
		);

		return $message->fetch();
	}

	/**
	 * @return array|false
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getLastMessage()
	{
		$filter = array(
			'SESSION_ID' => $this->sessionId
		);
		$message = SessionKpiMessagesTable::getList(
			array(
				'order' => array('ID' => 'DESC'),
				'filter' => $filter,
				'limit' => 1,
				'cache' => array('ttl' => 3600)
			)
		);

		return $message->fetch();
	}

	/**
	 * Return list of current expired, not answered messages
	 *
	 * @param bool $includeNoticed
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getExpiredMessages($includeNoticed = true)
	{
		$filter = array(
			'TIME_ANSWER' => null,
			'TIME_STOP' => null,
			'<TIME_EXPIRED' => DateTime::createFromTimestamp(time()),
			'SESSION_ID' => $this->sessionId,
		);

		if (!$includeNoticed)
		{
			$filter['IS_SENT_EXPIRED_NOTIFICATION'] = 'N';
		}

		$messages = SessionKpiMessagesTable::getList(array('filter' => $filter));

		return $messages->fetchAll();
	}

	/**
	 * Return list of expired messages for all line sessions
	 *
	 * @param $lineId
	 * @param bool $includeNoticed
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getLineExpiredMessages($lineId, $includeNoticed = true)
	{
		$select = array('*');
		$filter = array(
			'SESSION.CONFIG_ID' => $lineId,
			'TIME_ANSWER' => null,
			'TIME_STOP' => null,
			'<TIME_EXPIRED' => DateTime::createFromTimestamp(time()),
		);

		if (!$includeNoticed)
		{
			$filter['IS_SENT_EXPIRED_NOTIFICATION'] = 'N';
		}

		$expiredMessages = SessionKpiMessagesTable::getList(
			array(
				'select' => $select,
				'filter' => $filter
			)
		)->fetchAll();

		return $expiredMessages;
	}

	/**
	 * Return list of lines with their expired messages
	 *
	 * @param bool $includeNoticed
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getLinesWithExpiredMessages($includeNoticed = true)
	{
		$expiredMessages = array();
		$lineFilter = array(
			'LOGIC' => 'OR',
			array(
				'ACTIVE' => 'Y',
				'>KPI_FIRST_ANSWER_TIME' => '0'
			),
			array(
				'ACTIVE' => 'Y',
				'>KPI_FURTHER_ANSWER_TIME' => '0'
			)

		);
		$lines = ConfigTable::getList(array('filter' => $lineFilter))->fetchAll();

		foreach ($lines as $line)
		{
			$messages = self::getLineExpiredMessages($line['ID'], $includeNoticed);
			if (!empty($messages))
			{
				$expiredMessages[$line['ID']] = array(
					'MESSAGES' => $messages,
					'CONFIG' => $line,
				);
			}
		}

		return $expiredMessages;
	}

	/**
	 * Add new kpi message for current session in case last session kpi message was answered
	 *
	 * @param $fields
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function addMessage($fields)
	{
		$result = false;
		$lastMessage = $this->getLastMessage();

		if (!is_null($lastMessage['TIME_ANSWER']) || $lastMessage === false)
		{
			$addFields = array(
				'SESSION_ID' => $this->sessionId,
				'MESSAGE_ID' => $fields['MESSAGE_ID'],
				'IS_FIRST_MESSAGE' => $lastMessage === false ? 'Y' : 'N'
			);

			if (!empty($fields['LINE_ID']))
			{
				$orm = ConfigTable::getById($fields['LINE_ID']);
				$config = $orm->fetch();

				if (!empty($config))
				{
					$isActiveOperator = $this->checkOperatorActivity($fields['OPERATOR_ID'], $fields['LINE_ID']);
					if ($isActiveOperator)
					{
						$addFields['TIME_STOP'] = null;
					}
					else
					{
						$addFields['TIME_STOP'] = DateTime::createFromTimestamp(time());
						$addFields['TIME_STOP_HISTORY'][] = [
							'TIME_PAUSE' => $addFields['TIME_STOP'],
							'TIME_CONTINUE' => null
						];
					}

					if ($config)
					{
						if ($addFields['IS_FIRST_MESSAGE'] == 'Y')
						{
							if (intval($config['KPI_FIRST_ANSWER_TIME']) > 0)
							{
								$addFields['TIME_EXPIRED'] = DateTime::createFromTimestamp(time() + $config['KPI_FIRST_ANSWER_TIME']);
							}
						}
						else
						{
							if (intval($config['KPI_FURTHER_ANSWER_TIME']) > 0)
							{
								$addFields['TIME_EXPIRED'] = DateTime::createFromTimestamp(time() + $config['KPI_FURTHER_ANSWER_TIME']);
							}
						}
					}
				}
			}

			$result = SessionKpiMessagesTable::add($addFields);
		}

		return $result;
	}

	/**
	 * @param $kpiMessageId
	 * @param $fields
	 *
	 * @return \Bitrix\Main\ORM\Data\UpdateResult
	 * @throws \Exception
	 */
	public function updateMessage($kpiMessageId, $fields)
	{
		$update = SessionKpiMessagesTable::update($kpiMessageId, $fields);

		return $update;
	}

	/**
	 * Delete all kpi messages for current session
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function deleteSessionMessages()
	{
		$messages = $this->getSessionMessages();

		foreach ($messages as $message)
		{
			SessionKpiMessagesTable::delete($message['ID']);
		}
	}

	/**
	 * Stop message answer timer
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function stopTimer()
	{
		$currentMessage = $this->getNotAnsweredMessage();
		if (is_null($currentMessage['TIME_STOP']))
		{
			$updateFields = array(
				'TIME_STOP' => DateTime::createFromTimestamp(time())
			);
			$updateFields['TIME_STOP_HISTORY'][] = [
				'TIME_PAUSE' => $updateFields['TIME_STOP'],
				'TIME_CONTINUE' => null
			];

			$this->updateMessage($currentMessage['ID'], $updateFields);
		}
	}

	/**
	 * Enable message answer timer with stopped timer
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function startTimer()
	{
		$currentMessage = $this->getNotAnsweredMessage();
		if (!is_null($currentMessage['TIME_STOP']))
		{
			$updateFields = array(
				'TIME_STOP' => null
			);

			if (!is_null($currentMessage['TIME_EXPIRED']) && $currentMessage['IS_SENT_EXPIRED_NOTIFICATION'] == 'N')
			{
				$timeExpiredTimestamp = DateTime::createFromUserTime($currentMessage['TIME_EXPIRED'])->getTimestamp();
				$timeStopTimestamp = DateTime::createFromUserTime($currentMessage['TIME_STOP'])->getTimestamp();
				$timeExpired = DateTime::createFromTimestamp($timeExpiredTimestamp + time() - $timeStopTimestamp);
				if ($timeExpired->getTimestamp() >= time())
				{
					$updateFields['TIME_EXPIRED'] = DateTime::createFromTimestamp($timeExpiredTimestamp + time() - $timeStopTimestamp);
				}
			}

			if (!empty($currentMessage['TIME_STOP_HISTORY']) && is_array($currentMessage['TIME_STOP_HISTORY']))
			{
				$currentTimeHistory = array_pop($currentMessage['TIME_STOP_HISTORY']);
				if (is_null($currentTimeHistory['TIME_CONTINUE']))
				{
					$currentTimeHistory['TIME_CONTINUE'] = DateTime::createFromTimestamp(time());
					$currentMessage['TIME_STOP_HISTORY'][] = $currentTimeHistory;
					$updateFields['TIME_STOP_HISTORY'] = $currentMessage['TIME_STOP_HISTORY'];
				}
			}

			$this->updateMessage($currentMessage['ID'], $updateFields);
		}
	}

	/**
	 * Calculate full answer time for first message
	 *
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getFirstMessageAnswerTime()
	{
		$firstMessage = $this->getFirstMessage();
		$result = $this->getMessageAnswerTime($firstMessage);

		return $result;
	}

	/**
	 * Calculate full answer time for session
	 *
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getFullAnswerTime()
	{
		$result = 0;
		$messages = $this->getSessionMessages();

		foreach ($messages as $message)
		{
			$result += $this->getMessageAnswerTime($message);
		}

		return $result;
	}

	/**
	 * Calculate average answer time for session
	 *
	 * @return float|int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getAverageAnswerTime()
	{
		$result = 0;
		$messages = $this->getSessionMessages();

		foreach ($messages as $message)
		{
			$result += $this->getMessageAnswerTime($message);
		}

		$result = count($messages) > 0 ? intval($result/count($messages)) : 0;

		return $result;
	}

	/**
	 * Calculate max answer time for session
	 *
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getMaxAnswerTime()
	{
		$result = 0;
		$messages = $this->getSessionMessages();

		foreach ($messages as $message)
		{
			$messageAnswerTime = $this->getMessageAnswerTime($message);

			if ($messageAnswerTime > $result)
			{
				$result = $messageAnswerTime;
			}
		}

		return $result;
	}

	/**
	 * Calculate message full current answer time - including all stop time
	 *
	 * @param $message
	 *
	 * @return int
	 */
	protected function getMessageAnswerTime($message)
	{
		$result = 0;
		if (!empty($message))
		{
			$timeStop = 0;

			if (!empty($message['TIME_STOP_HISTORY']))
			{
				foreach ($message['TIME_STOP_HISTORY'] as $history)
				{
					$timeContinue = !empty($history['TIME_CONTINUE']) ? DateTime::createFromUserTime($history['TIME_CONTINUE'])->getTimestamp() : time();
					$timeStop += $timeContinue - DateTime::createFromUserTime($history['TIME_PAUSE'])->getTimestamp();
				}
			}

			$answerTimestamp = !empty($message['TIME_ANSWER']) ? DateTime::createFromUserTime($message['TIME_ANSWER'])->getTimestamp() : time();
			$result = $answerTimestamp - DateTime::createFromUserTime($message['TIME_RECEIVED'])->getTimestamp() - $timeStop;
		}

		return $result;
	}

	/**
	 * @param $sessionId
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function setSessionLastKpiMessageAnswered($sessionId)
	{
		$kpi = new self($sessionId);
		$notAnsweredMessage = $kpi->getNotAnsweredMessage();
		if (!empty($notAnsweredMessage))
		{
			//$kpi->stopTimer();
			$kpi->updateMessage(
				$notAnsweredMessage['ID'],
				array(
					'TIME_ANSWER' => DateTime::createFromTimestamp(time()),
				)
			);
		}
	}

	/**
	 * Method for sending notification message for current users
	 *
	 * @param array $notificationUserList
	 * @param string $message
	 *
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected static function sendExpiredNotification($notificationUserList, $message)
	{
		if (Loader::includeModule('im'))
		{
			foreach($notificationUserList as $userId)
			{
				$notifyFields = array(
					"TO_USER_ID" => $userId,
					"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
					"NOTIFY_MODULE" => "imopenlines",
					"NOTIFY_EVENT" => "default",
					"NOTIFY_MESSAGE" => $message,
					"RECENT_ADD" => "Y"
				);

				\CIMNotify::Add($notifyFields);
			}
		}
	}

	/**
	 * Operator day start event handler
	 *
	 * @param $operatorId
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function operatorDayStart($operatorId)
	{
		$activeSessions = self::getOperatorActiveKpiSessions($operatorId);

		if (!empty($activeSessions))
		{
			foreach ($activeSessions as $sessionId)
			{
				$kpi = new self($sessionId);
				$kpi->startTimer();
			}
		}
	}

	/**
	 * Operator day finish event handler
	 *
	 * @param $operatorId
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function operatorDayEnd($operatorId)
	{
		$activeSessions = self::getOperatorActiveKpiSessions($operatorId);

		if (!empty($activeSessions))
		{
			foreach ($activeSessions as $sessionId)
			{
				$kpi = new self($sessionId);
				$kpi->stopTimer();
			}
		}
	}

	/**
	 * Start timer for all actual line sessions
	 *
	 * @param $lineId
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected static function startLineSessionsTimers($lineId)
	{
		$sessionList = SessionKpiMessagesTable::getList(
			array(
				'select' => array('ID' => 'SESSION_ID'),
				'filter' => array(
					'=SESSION.CONFIG_ID' => $lineId,
					'<SESSION.STATUS' => Session::STATUS_WAIT_CLIENT,
					'TIME_ANSWER' => null
				),
				'group' => array('SESSION_ID')
			)
		)->fetchAll();

		foreach ($sessionList as $session)
		{
			$kpi = new self($session['ID']);
			$kpi->startTimer();
		}

		return true;
	}

	/**
	 * Stop timer for all actual line sessions
	 *
	 * @param $lineId
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected static function stopLineSessionsTimers($lineId)
	{
		$sessionList = SessionKpiMessagesTable::getList(
			array(
				'select' => array('ID' => 'SESSION_ID'),
				'filter' => array(
					'=SESSION.CONFIG_ID' => $lineId,
					'<SESSION.STATUS' => Session::STATUS_WAIT_CLIENT,
					'TIME_ANSWER' => null,
					'TIME_STOP' => null
				),
				'group' => array('SESSION_ID')
			)
		)->fetchAll();

		foreach ($sessionList as $session)
		{
			$kpi = new self($session['ID']);
			$kpi->stopTimer();
		}

		return true;
	}

	/**
	 * @param $operatorId
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected static function getOperatorActiveKpiSessions($operatorId)
	{
		$result = [];

		$filterQueue = [
			'CONFIG.KPI_CHECK_OPERATOR_ACTIVITY' => 'Y',
			'CONFIG.ACTIVE' => 'Y',
			'>CONFIG.KPI_FURTHER_ANSWER_TIME' => 0,
			'USER_ID' => $operatorId
			//'CONFIG.CHECK_AVAILABLE' => 'Y'
		];
		$queueListManager = QueueTable::getList(
			[
				'select' => ['CONFIG_ID'],
				'filter' => $filterQueue
			]
		);

		$configList = [];
		while ($queue = $queueListManager->fetch())
		{
			$configList[$queue['CONFIG_ID']] = $queue['CONFIG_ID'];
		}

		if (!empty($configList))
		{
			$filterKpiMessages = [
				'=OPERATOR_ID' => $operatorId,
				'CONFIG_ID' => $configList,
				'><STATUS' => [Session::STATUS_ANSWER, Session::STATUS_OPERATOR]
			];

			$sessionList = SessionTable::getList(
				[
					'select' => ['ID'],
					'filter' => $filterKpiMessages
				]
			);

			while ($session = $sessionList->fetch())
			{
				$result[] = $session['ID'];
			}
		}

		return $result;
	}

	/**
	 * @param $messageId
	 *
	 * @return mixed
	 */
	protected static function setMessageSentExpiredNotification($messageId)
	{
		$result = SessionKpiMessagesTable::update($messageId, array('IS_SENT_EXPIRED_NOTIFICATION' => 'Y'));

		return $result;
	}

	/**
	 * @param $operatorId
	 * @param null $lineId
	 *
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function checkOperatorActivity($operatorId, $lineId = null)
	{
		/*$isTimeMan = 'N';

		if (!empty($lineId))
		{
			$config = ConfigTable::getById($lineId)->fetch();
			$isTimeMan = !empty($config['CHECK_AVAILABLE']) ? $config['CHECK_AVAILABLE'] : 'N';
		}

		$result = Queue::isOperatorActive($operatorId, $isTimeMan) && Queue::isOperatorOnline($operatorId);*/

		//TODO - isOperatorAbsent(on vacation), isOperatorOnline, isOperatorActive (general activity)

		$result = true; //now we're not stopping time because of line or operator not active
		return $result;
	}

	/**
	 * Return list of fields to replace in notification message
	 *
	 * @return array
	 */
	protected static function getKpiMessageSearchFields()
	{
		return array(
			'#DIALOG#',
			'#OPERATOR#'
		);
	}

	/**
	 * Return list of fields for replace in notification message
	 *
	 * @param $message
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected static function getKpiMessageReplaceFields($message)
	{
		$session = SessionTable::getById($message['SESSION_ID'])->Fetch();
		$user = \CUser::GetByID($session['OPERATOR_ID'])->Fetch();
		$userName = $user['LAST_NAME'] . ' ' . $user['NAME'];
		$operatorName = !empty(trim($userName)) ? $userName : '';
		$result = array(
			'[url=/online/?IM_HISTORY=imol|'.$message['SESSION_ID'].']' . $message['SESSION_ID'] . '[/url]',
			$operatorName
		);

		return $result;
	}

	/**
	 * Add worktime agents for lines with worktime and kpi check
	 */
	public static function checkWorkTime()
	{
		$lineList = ConfigTable::getList(
			array(
				'select' => array('ID', 'WORKTIME_FROM', 'WORKTIME_TO'),
				'filter' => array(
					'LOGIC' => 'OR',
					array(
						'>KPI_FIRST_ANSWER_TIME' => 0,
						'CHECK_AVAILABLE' => 'Y',
						'ACTIVE' => 'Y'
					),
					array(
						'>KPI_FURTHER_ANSWER_TIME' => 0,
						'CHECK_AVAILABLE' => 'Y',
						'ACTIVE' => 'Y'
					)
				),
			)
		)->fetchAll();

		foreach ($lineList as $line)
		{
			\CAgent::AddAgent('\\Bitrix\\ImOpenLines\\KpiManager::startLineSessionsTimers('.$line['ID'].')', "imopenlines", "N", 0, "", "Y", \ConvertTimeStamp($line['WORKTIME_FROM'], "FULL"));
			\CAgent::AddAgent('\\Bitrix\\ImOpenLines\\KpiManager::stopLineSessionsTimers('.$line['ID'].')', "imopenlines", "N", 0, "", "Y", \ConvertTimeStamp($line['WORKTIME_TO'], "FULL"));
		}
	}

	/**
	 * Agent for adding worktime agents for lines with worktime and kpi check
	 *
	 * @return string
	 */
	public static function checkWorkTimeAgent()
	{
		self::checkWorkTime();

		return '\\Bitrix\\ImOpenLines\\KpiManager::checkWorkTimeAgent()';
	}

	/**
	 * Agent for sending expired notification messages for all lines, taking account of line settings
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function setExpiredMessagesAgent()
	{
		$lines = self::getLinesWithExpiredMessages(false);
		$search = self::getKpiMessageSearchFields();

		foreach ($lines as $line)
		{
			$firstAnswerAlert = ($line['CONFIG']['KPI_FIRST_ANSWER_ALERT'] == 'Y');
			$furtherAnswerAlert = ($line['CONFIG']['KPI_FURTHER_ANSWER_ALERT'] == 'Y');

			foreach ($line['MESSAGES'] as $message)
			{
				if ($firstAnswerAlert || $furtherAnswerAlert)
				{
					$replace = self::getKpiMessageReplaceFields($message);

					if ($message['IS_FIRST_MESSAGE'] == 'Y' && $firstAnswerAlert)
					{
						$text = str_replace($search, $replace, $line['CONFIG']['KPI_FIRST_ANSWER_TEXT']);
						self::sendExpiredNotification($line['CONFIG']['KPI_FIRST_ANSWER_LIST'], $text);
						self::setMessageSentExpiredNotification($message['ID']);
					}
					elseif ($message['IS_FIRST_MESSAGE'] == 'N' && $furtherAnswerAlert)
					{
						$text = str_replace($search, $replace, $line['CONFIG']['KPI_FURTHER_ANSWER_TEXT']);
						self::sendExpiredNotification($line['CONFIG']['KPI_FURTHER_ANSWER_LIST'], $text);
						self::setMessageSentExpiredNotification($message['ID']);
					}
				}
			}
		}

		return '\\Bitrix\\ImOpenLines\\KpiManager::setExpiredMessagesAgent();';
	}
}