<?php
namespace Bitrix\ImOpenLines\Session;

use \Bitrix\ImOpenLines\Chat,
	\Bitrix\ImOpenLines\Mail,
	\Bitrix\ImOpenLines\Common,
	\Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Session,
	\Bitrix\ImOpenLines\Log\ExecLog,
	\Bitrix\ImOpenLines\Model\SessionTable,
	\Bitrix\ImOpenLines\Model\SessionCheckTable;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Type\DateTime,
	\Bitrix\Main\Entity\ExpressionField;

use \Bitrix\Pull;

/**
 * Class Agent
 * @package Bitrix\ImOpenLines\Session
 */
class Agent
{
	/**
	 * The agent of change in charge at the chat.
	 *
	 * @param $nextExec
	 * @param int $offset
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function transferToNextInQueue($nextExec, $offset = 0)
	{
		$emptyResultReturn = '\Bitrix\ImOpenLines\Session::transferToNextInQueueAgent(0);';

		if (self::isCronCall() && self::isExecModeAgent() || !self::isCronCall() && self::isExecModeCron())
			return $emptyResultReturn;

		if (Session::getQueueFlagCache(Session::CACHE_QUEUE))
		{
			ExecLog::setExecFunction(__METHOD__);
			return $emptyResultReturn;
		}

		$configCount = SessionCheckTable::getList(array(
			'select' => array('CNT'),
			'runtime' => array(new ExpressionField('CNT', 'COUNT(*)')),
			'filter' => Array('!=DATE_QUEUE' => null),
		))->fetch();

		if ($configCount['CNT'] <= 0)
		{
			Session::setQueueFlagCache(Session::CACHE_QUEUE);
			ExecLog::setExecFunction(__METHOD__);
			return $emptyResultReturn;
		}

		$configs = Array();
		$chats = Array();
		$configManager = new Config();
		$newOffset = 0;

		$select = SessionTable::getSelectFieldsPerformance('SESSION');
		$res = SessionCheckTable::getList(Array(
			'select' => $select,
			'filter' => Array(
				'<=DATE_QUEUE' => new DateTime()
			),
			'order' => array('SESSION.DATE_CREATE'),
			'limit' => Common::getMaxSessionCount()+1,
			'offset' => $offset
		));

		$count = 0;
		while ($row = $res->fetch())
		{
			$count++;
			if($count <= Common::getMaxSessionCount())
			{
				$fields = Array();
				foreach($row as $key=>$value)
				{
					$key = str_replace('IMOPENLINES_MODEL_SESSION_CHECK_SESSION_', '', $key);
					$fields[$key] = $value;
				}

				if (!isset($configs[$fields['CONFIG_ID']]))
				{
					$configs[$fields['CONFIG_ID']] = $configManager->get($fields['CONFIG_ID']);
				}
				if (!isset($chats[$fields['CHAT_ID']]))
				{
					$chats[$fields['CHAT_ID']] = new Chat($fields['CHAT_ID']);
				}

				$session = new Session();
				$session->loadByArray($fields, $configs[$fields['CONFIG_ID']], $chats[$fields['CHAT_ID']]);
				$session->transferToNextInQueue(false);
			}
			else
			{
				$newOffset = $offset + Common::getMaxSessionCount();
			}
		}

		if (Loader::includeModule('pull'))
		{
			Pull\Event::send();
		}

		ExecLog::setExecFunction(__METHOD__);

		return '\Bitrix\ImOpenLines\Session::transferToNextInQueueAgent(1, ' . $newOffset . ');';
	}

	/**
	 * Session closing agent by time.
	 *
	 * @param $nextExec
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function closeByTime($nextExec)
	{
		$emptyResultReturn = '\Bitrix\ImOpenLines\Session::closeByTimeAgent(0);';

		if (self::isCronCall() && self::isExecModeAgent() || !self::isCronCall() && self::isExecModeCron())
			return $emptyResultReturn;

		if (Session::getQueueFlagCache(Session::CACHE_CLOSE))
		{
			ExecLog::setExecFunction(__METHOD__);
			return $emptyResultReturn;
		}

		$configCount = SessionCheckTable::getList(array(
			'select' => array('CNT'),
			'runtime' => array(new ExpressionField('CNT', 'COUNT(*)')),
			'filter' => array('!=DATE_CLOSE' => null)
		))->fetch();
		if ($configCount['CNT'] <= 0)
		{
			Session::setQueueFlagCache(Session::CACHE_CLOSE);
			ExecLog::setExecFunction(__METHOD__);
			return $emptyResultReturn;
		}

		$configs = Array();
		$chats = Array();
		$configManager = new Config();

		$select = SessionTable::getSelectFieldsPerformance('SESSION');
		$res = SessionCheckTable::getList(Array(
			'select' => $select,
			'filter' => Array(
				'<=DATE_CLOSE' => new DateTime()
			),
			'limit' => 100
		));
		while ($row = $res->fetch())
		{
			$fields = Array();
			foreach($row as $key=>$value)
			{
				$key = str_replace('IMOPENLINES_MODEL_SESSION_CHECK_SESSION_', '', $key);
				$fields[$key] = $value;
			}

			if (!isset($configs[$fields['CONFIG_ID']]))
			{
				$configs[$fields['CONFIG_ID']] = $configManager->get($fields['CONFIG_ID']);
			}

			if (!isset($chats[$fields['CHAT_ID']]))
			{
				$chats[$fields['CHAT_ID']] = new Chat($fields['CHAT_ID']);
			}

			$session = new Session();
			$session->loadByArray($fields, $configs[$fields['CONFIG_ID']], $chats[$fields['CHAT_ID']]);
			$session->finish(true);
		}

		if (Loader::includeModule('pull'))
		{
			Pull\Event::send();
		}

		ExecLog::setExecFunction(__METHOD__);

		return '\Bitrix\ImOpenLines\Session::closeByTimeAgent(1);';
	}

	/**
	 * The agent is sending mail messages at the time.
	 *
	 * @param $nextExec
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function mailByTime($nextExec)
	{
		$emptyResultReturn = '\Bitrix\ImOpenLines\Session::mailByTimeAgent(0);';

		if (self::isCronCall() && self::isExecModeAgent() || !self::isCronCall() && self::isExecModeCron())
			return $emptyResultReturn;

		if (Session::getQueueFlagCache(Session::CACHE_MAIL))
		{
			ExecLog::setExecFunction(__METHOD__);
			return $emptyResultReturn;
		}

		$configCount = SessionCheckTable::getList(array(
			'select' => array('CNT'),
			'runtime' => array(new ExpressionField('CNT', 'COUNT(*)')),
			'filter' => array('!=DATE_MAIL' => null)
		))->fetch();
		if ($configCount['CNT'] <= 0)
		{
			Session::setQueueFlagCache(Session::CACHE_MAIL);
			ExecLog::setExecFunction(__METHOD__);
			return $emptyResultReturn;
		}

		$res = SessionCheckTable::getList(Array(
			'select' => Array('SESSION_ID'),
			'filter' => Array(
				'<=DATE_MAIL' => new DateTime()
			),
			'limit' => 100
		));
		while ($row = $res->fetch())
		{
			Mail::sendOperatorAnswer($row['SESSION_ID']);
		}

		if (Loader::includeModule('pull'))
		{
			Pull\Event::send();
		}

		ExecLog::setExecFunction(__METHOD__);

		return '\Bitrix\ImOpenLines\Session::mailByTimeAgent(1);';
	}

	/**
	 * The agent returns to the queue of the session that were left on fired employees.
	 *
	 * @param $nextExec
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function dismissedOperator($nextExec)
	{
		$emptyResultReturn = '\Bitrix\ImOpenLines\Session::dismissedOperatorAgent(0);';

		if (self::isCronCall() && self::isExecModeAgent() || !self::isCronCall() && self::isExecModeCron())
			return $emptyResultReturn;

		$res = SessionCheckTable::getList(Array(
			'select' => Array('SESSION_ID', 'CHAT_ID' => 'SESSION.CHAT_ID', 'OPERATOR_ID' => 'SESSION.OPERATOR_ID', 'SESSION.OPERATOR.ID', 'SESSION.OPERATOR.ACTIVE', 'DATE_LAST_MESSAGE' => 'SESSION.DATE_LAST_MESSAGE'),
			'filter' => Array(
				'=DATE_QUEUE' => null,
				array(
					'LOGIC' => 'OR',
					array('SESSION.OPERATOR_ID' => null),
					array('SESSION.OPERATOR_ID' => 0),
					'SESSION.OPERATOR.ID' => null,
					'SESSION.OPERATOR.ACTIVE' => 'N',
				)
			),
			'limit' => 101
		));

		$count=0;
		while ($row = $res->fetch())
		{
			$count++;
			if($count<101)
			{
				if(empty(SessionCheckTable::getRowById($row['SESSION_ID'])['DATE_QUEUE']))
				{
					$chat = new Chat($row['CHAT_ID']);

					$timeException = new DateTime();
					$timeException->add('-7 DAY');

					if(empty($row['DATE_LAST_MESSAGE']) || $row['DATE_LAST_MESSAGE']->getTimestamp() > $timeException->getTimestamp())
					{
						if(empty($row['OPERATOR_ID']))
							$row['OPERATOR_ID'] = 0;

						$chat->transfer(Array(
							'FROM' => $row['OPERATOR_ID'],
							'TO' => 'queue',
							'MODE' => Chat::TRANSFER_MODE_AUTO,
						));
					}
					else
					{
						$chat->dismissedOperatorFinish();
					}
				}
			}
			else
			{
				if (self::isCronCall() && self::isExecModeCron())
				{
					return self::dismissedOperator(1);
				}
				else
				{
					\CAgent::AddAgent('\Bitrix\ImOpenLines\Session::dismissedOperatorAgent(1);', "imopenlines", "N", 60, "", "Y", ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL"));
				}
			}
		}

		if (Loader::includeModule('pull'))
		{
			Pull\Event::send();
		}

		ExecLog::setExecFunction(__METHOD__);

		if($nextExec == 0)
		{
			return $emptyResultReturn;
		}
	}

	/**
	 * Checks method has been called from cron exec ready script
	 *
	 * @return bool
	 */
	protected static function isCronCall()
	{
		return defined('IMOPENLINES_EXEC_CRON');
	}

	/**
	 * Checks current exec mode is agent
	 *
	 * @return bool
	 */
	protected static function isExecModeAgent()
	{
		return Common::getExecMode() == Common::MODE_AGENT;
	}

	/**
	 * Checks current exec mode is cron
	 *
	 * @return bool
	 */
	protected static function isExecModeCron()
	{
		return Common::getExecMode() == Common::MODE_CRON;
	}
}