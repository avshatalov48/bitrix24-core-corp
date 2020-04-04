<?php
namespace Bitrix\ImOpenLines\Queue;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Type\DateTime,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\Im;

use \Bitrix\ImOpenLines,
	\Bitrix\ImOpenLines\Log,
	\Bitrix\ImOpenLines\Chat,
	\Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Session,
	\Bitrix\ImOpenLines\Tools\Lock,
	\Bitrix\ImOpenLines\Model\SessionCheckTable;

Loc::loadMessages(__FILE__);

/**
 * Class Queue
 * @package Bitrix\ImOpenLines\Queue
 */
abstract class Queue
{
	const PREFIX_KEY_LOCK = 'imol_transfer_chat_id_';

	/**Session*/
	protected $sessionManager = null;
	protected $session = [];
	protected $config = [];
	/**Chat*/
	protected $chat = null;

	/**
	 * Queue constructor.
	 * @param Session $session
	 */
	function __construct($session)
	{
		$this->sessionManager = $session;
		$this->session = $session->getData();
		$this->config = $session->getConfig();
		$this->chat = $session->getChat();
	}

	/**
	 * @return string
	 */
	private function getKeyLock()
	{
		return self::PREFIX_KEY_LOCK . $this->chat->getData('ID');
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function startLock()
	{
		return Lock::getInstance()->set($this->getKeyLock());
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function stopLock()
	{
		return Lock::getInstance()->delete($this->getKeyLock());
	}

	/**
	 * @return DateTime
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getNewDateNoAnswer()
	{
		$dateNoAnswer = SessionCheckTable::getById($this->session['ID'])->fetch()['DATE_NO_ANSWER'];

		if($this->session['SEND_NO_ANSWER_TEXT'] != 'Y' && empty($dateNoAnswer))
		{
			$dateNoAnswer = new DateTime();
			$dateNoAnswer->add($this->config['NO_ANSWER_TIME'] . ' SECONDS');
		}

		return $dateNoAnswer;
	}

	/**
	 * Check for unallocated sessions.
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isUndistributedSession()
	{
		$result = false;

		$count = SessionCheckTable::getCount([
			'=UNDISTRIBUTED' => 'Y',
			'=SESSION.CONFIG_ID' => $this->config['ID'],
			'!=DATE_QUEUE' => NULL
		]);

		if($count>0)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * Basic check that the operator is active.
	 *
	 * @param $userId
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isOperatorActive($userId)
	{
		return ImOpenLines\Queue::isOperatorActive($userId, Config::isTimeManActive(), $this->config['CHECK_AVAILABLE']);
	}

	/**
	 * Check the ability to send a dialog to the operator.
	 *
	 * @param $userId
	 * @param int $currentOperator
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isOperatorAvailable($userId, $currentOperator = 0)
	{
		$result = false;

		if($this->isOperatorActive($userId))
		{
			if($userId != $currentOperator)
			{
				$freeCountChatOperator = ImOpenLines\Queue::getCountFreeSlotOperator($userId, $this->config['ID'], $this->config["MAX_CHAT"], $this->config["TYPE_MAX_CHAT"]);

				if($freeCountChatOperator > 0)
				{
					$result = true;
				}
			}
			else
			{
				$result = true;
			}
		}

		return $result;
	}

	abstract public function getOperatorsQueue($currentOperator = 0);

	/**
	 * @param int $operatorId
	 * @param \Bitrix\ImOpenLines\Crm $crmManager
	 * @param bool $isGroupByChat
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function createSession($operatorId = 0, $crmManager = null, $isGroupByChat = false)
	{
		$result = [
			'OPERATOR_ID' => 0,
			'QUEUE_HISTORY' => [],
			'OPERATOR_LIST' => [],
			'DATE_OPERATOR' => null,
			'DATE_QUEUE' => null,
			'DATE_NO_ANSWER' => null,
			'JOIN_BOT' => false,
			'UNDISTRIBUTED' => false,
		];

		if(empty($operatorId))
		{
			$result['DATE_QUEUE'] = new DateTime();

			//Bot
			if($this->config['ACTIVE'] != 'N' &&
				$this->config['WELCOME_BOT_ENABLE'] == 'Y' &&
				$this->config['WELCOME_BOT_ID'] > 0 &&
				(
					$this->config['WELCOME_BOT_JOIN'] == Config::BOT_JOIN_ALWAYS ||
					$this->chat->isNowCreated()
				))
			{
				$result['JOIN_BOT'] = true;

				$operatorId = $this->config['WELCOME_BOT_ID'];

				if ($this->config['WELCOME_BOT_TIME'] > 0)
				{
					$result['DATE_QUEUE']->add($this->config['WELCOME_BOT_TIME'] . ' SECONDS');
				}
				else
				{
					$result['DATE_QUEUE'] = null;
				}
			}
			//Operator
			else
			{
				$result['DATE_QUEUE']->add($this->config['QUEUE_TIME'] . ' SECONDS');

				if($this->sessionManager->checkWorkTime())
				{
					$result['DATE_NO_ANSWER'] = (new DateTime())->add($this->config['NO_ANSWER_TIME'] . ' SECONDS');
				}

				//CRM
				if ($crmManager && $isGroupByChat == false && $this->config['CRM'] == 'Y' && $crmManager->isLoaded() && $this->config['CRM_FORWARD'] == 'Y')
				{
					$crmManager->search();

					$crmOperatorId = $crmManager->getOperatorId();

					if($crmOperatorId > 0)
					{
						if($this->isActiveCrmUser($crmOperatorId))
						{
							$operatorId = $crmOperatorId;
						}
					}
				}
				//END CRM

				$undistributedSession = $this->isUndistributedSession();

				//Queue
				if(empty($operatorId) && !$undistributedSession)
				{
					$resultOperatorQueue = $this->getOperatorsQueue();

					if($resultOperatorQueue['RESULT'])
					{
						$operatorId = $resultOperatorQueue['OPERATOR_ID'];
						$result['OPERATOR_LIST'] = $resultOperatorQueue['OPERATOR_LIST'];
						$result['QUEUE_HISTORY'] = $resultOperatorQueue['QUEUE_HISTORY'];
					}
					else
					{
						$result['UNDISTRIBUTED'] = true;

					}
					$result['DATE_QUEUE'] = $resultOperatorQueue['DATE_QUEUE'];
				}

				if(empty($operatorId) && $undistributedSession)
				{
					$result['UNDISTRIBUTED'] = true;
					$result['DATE_QUEUE'] = new DateTime();
				}

				if(!empty($operatorId))
				{
					$result['DATE_OPERATOR'] = new DateTime();
				}
			}
		}
		else
		{
			$result['DATE_OPERATOR'] = new DateTime();
		}

		if(!empty($operatorId))
		{
			$result['OPERATOR_ID'] = $operatorId;
			if(empty($result['OPERATOR_LIST']))
			{
				$result['OPERATOR_LIST'] = [$operatorId];
			}
			if(empty($result['QUEUE_HISTORY']))
			{
				$result['QUEUE_HISTORY'][$operatorId] = true;
			}
		}

		return $result;
	}

	/**
	 * Transfer the dialog to the next operator.
	 *
	 * @param bool $manual
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function transferToNext($manual = true)
	{
		$result = false;

		ImOpenLines\Debug::addQueue($this->config['ID'], $this->session['ID'], 'start' . __METHOD__, ['manual' => $manual]);

		if($this->startLock())
		{
			$resultOperatorQueue = $this->getOperatorsQueue($this->session['OPERATOR_ID']);

			if($manual && $resultOperatorQueue['RESULT'] != true)
			{
				self::sendMessageSkipAlone($this->session['CHAT_ID']);
			}
			else
			{
				$updateSessionCheck = [
					'REASON_RETURN' => ImOpenLines\Queue::REASON_DEFAULT
				];

				if($this->session['OPERATOR_ID'] != $resultOperatorQueue['OPERATOR_ID'])
				{
					$this->chat->transfer([
						'FROM' => $this->session['OPERATOR_ID'],
						'TO' => $resultOperatorQueue['OPERATOR_ID'],
						'MODE' => Chat::TRANSFER_MODE_AUTO,
						'LEAVE' => $this->config['WELCOME_BOT_LEFT'] == Config::BOT_LEFT_CLOSE && Im\User::getInstance($this->session['OPERATOR_ID'])->isBot()? 'N':'Y'
					]);
				}

				if($resultOperatorQueue['RESULT'] == true)
				{
					$updateSessionCheck['UNDISTRIBUTED'] = 'N';

					$result = true;
				}
				else
				{
					$updateSessionCheck['UNDISTRIBUTED'] = 'Y';
				}

				$updateSessionCheck['DATE_QUEUE'] = $resultOperatorQueue['DATE_QUEUE'];

				$reasonReturn = SessionCheckTable::getById($this->session['ID'])->fetch()['REASON_RETURN'];

				SessionCheckTable::update($this->session['ID'], $updateSessionCheck);

				if($this->session['OPERATOR_ID'] != $resultOperatorQueue['OPERATOR_ID'])
				{
					$updateSession = [
						'OPERATOR_ID' => $resultOperatorQueue['OPERATOR_ID'],
						'QUEUE_HISTORY' => $resultOperatorQueue['QUEUE_HISTORY']
					];

					$this->sessionManager->update($updateSession);
				}

				ImOpenLines\Debug::addQueue($this->config['ID'], $this->session['ID'],__METHOD__, ['resultOperatorQueue' => $resultOperatorQueue, 'reasonReturn' => $reasonReturn]);
			}

			$this->stopLock();
		}

		ImOpenLines\Debug::addQueue($this->config['ID'], $this->session['ID'], 'stop' . __METHOD__);

		return $result;
	}

	/**
	 * Check the operator responsible for CRM on the possibility of transfer of chat.
	 *
	 * @param $userId
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isActiveCrmUser($userId)
	{
		return $this->isOperatorActive($userId);
	}

	/**
	 * Directing a conversation to a queue when an operator is not available.
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function transferOperatorNotAvailable()
	{
		$reasonReturn = ImOpenLines\Queue::REASON_OPERATOR_NOT_AVAILABLE;

		if(
			!empty($this->session['OPERATOR_ID']) &&
			$this->session['PAUSE'] != 'Y'
		)
		{
			if(!$this->isOperatorActive($this->session['OPERATOR_ID']))
			{
				ImOpenLines\Queue::returnSessionToQueue($this->session['ID'], $reasonReturn);
			}
		}
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function transferOperatorOffline()
	{
		ImOpenLines\Im::addMessage([
			"TO_CHAT_ID" => $this->session['CHAT_ID'],
			'MESSAGE' => Loc::getMessage('IMOL_QUEUE_SESSION_TRANSFER_OPERATOR_OFFLINE'),
			'SYSTEM' => 'Y',
			'SKIP_COMMAND' => 'Y'
		]);

		return $this->transferToNext(false);
	}

	/**
	 * @param $chatId
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function sendMessageSkipAlone($chatId)
	{
		ImOpenLines\Im::addMessage([
			"TO_CHAT_ID" => $chatId,
			'MESSAGE' => Loc::getMessage('IMOL_QUEUE_SESSION_SKIP_ALONE'),
			'SYSTEM' => 'Y',
			'SKIP_COMMAND' => 'Y'
		]);
	}
}