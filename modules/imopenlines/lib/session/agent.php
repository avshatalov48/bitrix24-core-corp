<?php
namespace Bitrix\ImOpenLines\Session;

use	\Bitrix\ImOpenLines\Chat,
	\Bitrix\ImOpenLines\Mail,
	\Bitrix\ImOpenLines\Queue,
	\Bitrix\ImOpenLines\Debug,
	\Bitrix\ImOpenLines\Common,
	\Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Session,
	\Bitrix\ImOpenLines\Log\ExecLog,
	\Bitrix\ImOpenLines\AutomaticAction,
	\Bitrix\ImOpenLines\Model\SessionTable,
	\Bitrix\ImOpenLines\Model\SessionCheckTable;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Config\Option,
	\Bitrix\Main\Type\DateTime,
	\Bitrix\Main\ORM\Fields\ExpressionField;

use \Bitrix\Pull;

/**
 * Class Agent
 * @package Bitrix\ImOpenLines\Session
 */
class Agent
{
	const TYPE_AGENT_HIT = 'hit';
	const TYPE_AGENT_CRON_OL = 'cronol';
	const TYPE_AGENT_B24 = 'b24';
	const TYPE_AGENT_CRON = 'cron';

	/**
	 * Returns the type of the agent run.
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function getTypeRunAgent()
	{
		$result = self::TYPE_AGENT_HIT;

		if(self::isExecModeCron())
		{
			$result = self::TYPE_AGENT_CRON_OL;
		}
		elseif(defined('BX24_HOST_NAME'))
		{
			$result = self::TYPE_AGENT_B24;
		}
		else
		{
			$agentsUseCrontab = Option::get("main", "agents_use_crontab", "N");

			if($agentsUseCrontab=="Y" || (defined("BX_CRONTAB_SUPPORT") && BX_CRONTAB_SUPPORT===true))
			{
				if(!defined("BX_CRONTAB") || BX_CRONTAB !== true)
				{
					$result = self::TYPE_AGENT_CRON;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the timeout time for agents, depending on the context.
	 *
	 * @return int
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function getTimeOutTransferToNextInQueue()
	{
		$time = 30;

		$type = self::getTypeRunAgent();

		switch ($type)
		{
			//agent on the hit
			case self::TYPE_AGENT_HIT:
				$time = 5;
				break;
			//agent on special cron open lines
			case self::TYPE_AGENT_CRON_OL:
				$time = 180;
				break;
			//the agent in the cloud-bitrix24
			case self::TYPE_AGENT_B24:
				$time = 50;
				break;
			//agent on cron
			case self::TYPE_AGENT_CRON:
				$time = 60;
				break;
		}

		return $time;
	}

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
	public static function transferToNextInQueue($nextExec = 0, $offset = 0)
	{
		Debug::addAgent('start ' . __METHOD__);

		$result = '\Bitrix\ImOpenLines\Session::transferToNextInQueueAgent(0);';

		if(!self::isCronCall() && self::isExecModeAgent() || self::isCronCall() && self::isExecModeCron())
		{
			ExecLog::setExecFunction(__METHOD__);

			if (!Session::getQueueFlagCache(Session::CACHE_QUEUE))
			{
				if(!Queue::isThereSessionTransfer())
				{
					Session::setQueueFlagCache(Session::CACHE_QUEUE);
				}
				else
				{
					Queue::transferToNextSession(self::getTimeOutTransferToNextInQueue());
				}
			}
		}

		Debug::addAgent('stop ' . __METHOD__);

		return $result;
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
	public static function closeByTime($nextExec = 0)
	{
		Debug::addAgent('start ' . __METHOD__);

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

		$configs = [];
		$chats = [];
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
			$fields = [];
			foreach($row as $key=>$value)
			{
				$key = str_replace('IMOPENLINES_MODEL_SESSION_CHECK_SESSION_', '', $key);
				$fields[$key] = $value;
			}

			if (!empty($fields['CONFIG_ID']) && empty($configs[$fields['CONFIG_ID']]))
			{
				$configs[$fields['CONFIG_ID']] = $configManager->get($fields['CONFIG_ID']);
			}

			if (!empty($fields['CHAT_ID']) && empty($chats[$fields['CHAT_ID']]))
			{
				$chats[$fields['CHAT_ID']] = new Chat($fields['CHAT_ID']);
			}

			if(!empty($fields) && !empty($configs[$fields['CONFIG_ID']]) && !empty($chats[$fields['CHAT_ID']]))
			{
				$session = new Session();
				$session->loadByArray($fields, $configs[$fields['CONFIG_ID']], $chats[$fields['CHAT_ID']]);
				$session->finish(true);
			}
		}

		if (Loader::includeModule('pull'))
		{
			Pull\Event::send();
		}

		ExecLog::setExecFunction(__METHOD__);

		Debug::addAgent('stop ' . __METHOD__);

		return '\Bitrix\ImOpenLines\Session::closeByTimeAgent(1);';
	}

	/**
	 * Send notification about unavailability of the operator.
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function sendMessageNoAnswer()
	{
		Debug::addAgent('start ' . __METHOD__);

		$result = '\Bitrix\ImOpenLines\Session\Agent::sendMessageNoAnswer();';

		if(!self::isCronCall() && self::isExecModeAgent() || self::isCronCall() && self::isExecModeCron())
		{
			ExecLog::setExecFunction(__METHOD__);

			if (!Session::getQueueFlagCache(Session::CACHE_NO_ANSWER))
			{
				if(!AutomaticAction\NoAnswer::isThereSessionNoAnswer())
				{
					Session::setQueueFlagCache(Session::CACHE_NO_ANSWER);
				}
				else
				{
					AutomaticAction\NoAnswer::sendMessageNoAnswer(self::getTimeOutTransferToNextInQueue());
				}
			}
		}

		Debug::addAgent('stop ' . __METHOD__);

		return $result;
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
	public static function mailByTime($nextExec = 0)
	{
		Debug::addAgent('start ' . __METHOD__);

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

		Debug::addAgent('stop ' . __METHOD__);

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
	public static function dismissedOperator($nextExec = 0)
	{
		Debug::addAgent('start ' . __METHOD__);

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

		Debug::addAgent('stop ' . __METHOD__);

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