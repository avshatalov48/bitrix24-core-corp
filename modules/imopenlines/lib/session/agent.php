<?php
namespace Bitrix\ImOpenLines\Session;

use Bitrix\ImOpenLines\Crm;
use Bitrix\ImOpenLines\Chat;
use Bitrix\ImOpenLines\Im;
use Bitrix\ImOpenLines\Mail;
use Bitrix\ImOpenLines\Queue;
use Bitrix\ImOpenLines\Debug;
use Bitrix\ImOpenLines\Common;
use Bitrix\ImOpenLines\Config;
use Bitrix\ImOpenLines\Session;
use Bitrix\ImOpenLines\Log\ExecLog;
use Bitrix\ImOpenLines\AutomaticAction;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\ImOpenLines\Model\SessionCheckTable;
use Bitrix\Imopenlines\Model\UserRelationTable;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\ORM\Fields\ExpressionField;

use Bitrix\Pull;

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
	 */
	public static function transferToNextInQueue($nextExec = 0, $offset = 0)
	{
		Debug::addAgent('start ' . __METHOD__);

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

		return __METHOD__. '(0);';
	}

	/**
	 * Session closing agent by time.
	 *
	 * @param $nextExec
	 * @return string
	 */
	public static function closeByTime($nextExec = 0)
	{
		Debug::addAgent('start ' . __METHOD__);

		$emptyResultReturn = __METHOD__. '(0);';

		if (self::isCronCall() && self::isExecModeAgent() || !self::isCronCall() && self::isExecModeCron())
		{
			return $emptyResultReturn;
		}

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

		return __METHOD__. '(1);';
	}

	/**
	 * Send notification about unavailability of the operator.
	 *
	 * @return string
	 */
	public static function sendMessageNoAnswer()
	{
		Debug::addAgent('start ' . __METHOD__);

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

		return __METHOD__. '();';
	}

	/**
	 * The agent is sending mail messages at the time.
	 *
	 * @param $nextExec
	 * @return string
	 */
	public static function mailByTime($nextExec = 0)
	{
		Debug::addAgent('start ' . __METHOD__);

		$emptyResultReturn = __METHOD__. '(0);';

		if (self::isCronCall() && self::isExecModeAgent() || !self::isCronCall() && self::isExecModeCron())
		{
			return $emptyResultReturn;
		}

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

		return __METHOD__. '(1);';
	}

	/**
	 * The agent returns to the queue of the session that were left on fired employees.
	 *
	 * @param $nextExec
	 * @return string
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
	 * @return string
	 */
	public static function sendAutomaticMessage()
	{
		Debug::addAgent('start ' . __METHOD__);

		if (
			(
				!self::isCronCall()
				&& self::isExecModeAgent()
			)
			||
			(
				self::isCronCall()
				&& self::isExecModeCron()
			)
		)
		{
			ExecLog::setExecFunction(__METHOD__);

			if(AutomaticAction\Messages::isActualMessagesForSend())
			{
				AutomaticAction\Messages::sendMessages(self::getTimeOutTransferToNextInQueue());
			}
		}

		Debug::addAgent('stop ' . __METHOD__);

		return __METHOD__ .'();';
	}

	/**
	 * Agent clears broken session data.
	 * @return string
	 */
	public static function deleteBrokenSession(): string
	{
		$sessList = \Bitrix\ImOpenLines\Model\SessionTable::getList([
			'select' => ['ID', 'CHAT_ID'],
			'filter' => ['=CONFIG.ID' => null]
		]);
		while ($session = $sessList->fetch())
		{
			Im::chatHide($session['CHAT_ID']);
			Session::deleteSession($session['ID']);
		}

		$checkList = \Bitrix\ImOpenLines\Model\SessionCheckTable::getList([
			'filter' => ['=SESSION.ID' => null]
		]);
		while ($session = $checkList->fetch())
		{
			\Bitrix\ImOpenLines\Model\SessionCheckTable::delete($session['SESSION_ID']);
		}

		if (\Bitrix\Main\Loader::includeModule('pull'))
		{
			\Bitrix\Pull\Event::send();
		}

		return __METHOD__. '();';
	}

	/**
	 * @param string $oldUserCode
	 * @param string $userId
	 * @return string
	 */
	protected static function convertUserCode(string $oldUserCode, string $userId): string
	{
		$parsedUserCode = Session\Common::parseUserCode($oldUserCode);
		$parsedUserCode['CONNECTOR_USER_ID'] = $userId;
		return Session\Common::combineUserCode($parsedUserCode);
	}

	/**
	 * @param int $replaceId
	 * @param int $searchId
	 * @return string
	 */
	public static function replacementUserAgent(int $replaceId, int $searchId): string
	{
		$chatIds = [];
		$oldUserCode = [];
		$newUserCode = [];

		$sessionRaw = SessionTable::getList([
			'select' => ['ID', 'USER_CODE', 'CHAT_ID'],
			'filter' => ['USER_ID' => $searchId],
		]);

		while ($sessionRow = $sessionRaw->fetch())
		{
			if (empty($newUserCode[$sessionRow['USER_CODE']]))
			{
				$newUserCode[$sessionRow['USER_CODE']] = self::convertUserCode($sessionRow['USER_CODE'], $replaceId);
			}
			$resultUpdate = SessionTable::update(
				$sessionRow['ID'],
				[
					'USER_ID' => $replaceId,
					'USER_CODE' => $newUserCode[$sessionRow['USER_CODE']],
				]
			);

			if ($resultUpdate->isSuccess())
			{
				$chatIds[$sessionRow['CHAT_ID']] = $sessionRow['CHAT_ID'];
				$oldUserCode[$sessionRow['USER_CODE']] = $sessionRow['USER_CODE'];
			}
		}

		if (!empty($chatIds))
		{
			$chat = new \CIMChat(0);

			foreach ($chatIds as $chatId)
			{
				if (!empty($chatId))
				{
					$chat->AddUser($chatId, $replaceId, false, true);
					$chat->DeleteUser($chatId, $searchId, false, true);

					$chatImol = new Chat($chatId);
					if ($chatImol->isDataLoaded())
					{
						$entityId = $chatImol->getData('ENTITY_ID');

						if(empty($newUserCode[$entityId]))
						{
							$newUserCode[$entityId] = self::convertUserCode($entityId, $replaceId);
						}
						$chatImol->updateChatLineData($newUserCode[$entityId]);
					}
				}
			}
		}

		if (!empty($oldUserCode))
		{
			foreach ($oldUserCode as $userCode)
			{
				if (!empty($userCode))
				{
					if(empty($newUserCode[$userCode]))
					{
						$newUserCode[$userCode] = self::convertUserCode($userCode, $replaceId);
					}

					Crm\Agent::addUniqueReplacementUserCodeAgent($userCode, $newUserCode[$userCode]);

					$relation = UserRelationTable::getByPrimary($userCode);

					if ($resultUserRelation = $relation->fetch())
					{
						UserRelationTable::delete($userCode);

						if ($resultUserRelation['CHAT_ID'])
						{
							$relationNew = UserRelationTable::getByPrimary($newUserCode[$userCode]);
							if ($relationNew->fetch())
							{
								UserRelationTable::delete($newUserCode[$userCode]);
							}

							UserRelationTable::add(
								[
									'USER_CODE' => $newUserCode[$userCode],
									'USER_ID' => $replaceId,
									'CHAT_ID' => $resultUserRelation['CHAT_ID'],
								]
							);
						}
					}
				}
			}
		}

		$userRaw = Main\UserTable::getList([
			'filter' => ['ID' => $searchId],
			'select' => ['XML_ID'],
		]);

		if ($xmlId = $userRaw->fetch()['XML_ID'])
		{
			$cUser = new \CUser;
			$cUser->Update($searchId, ['XML_ID' => 'bad' . time() . $xmlId]);
		}

		return '';
	}

	/**
	 * Agent sets correct status for closed sessions
	 *
	 * @return string
	 */
	public static function correctionStatusClosedSessionsAgent(): string
	{
		$query = new Query(SessionTable::getEntity());
		$query->setSelect([
			'ID',
			'CHECK_SESSION_ID' => 'CHECK.SESSION_ID'
		]);
		$query->setFilter([
			'<STATUS' => Session::STATUS_CLOSE,
			'CLOSED' => 'Y'
		]);
		$query->setLimit(100);

		$sessionManager = $query->exec();
		while ($session = $sessionManager->fetch())
		{
			$resultSessionUpdate = SessionTable::update($session['ID'], ['STATUS' => Session::STATUS_CLOSE]);

			if ($resultSessionUpdate->isSuccess())
			{
				if ($session['CHECK_SESSION_ID'] > 0)
				{
					SessionCheckTable::delete($session['CHECK_SESSION_ID']);
				}
			}
		}

		return __METHOD__ . '();';
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