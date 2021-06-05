<?php
namespace Bitrix\ImOpenLines\Queue;

use \Bitrix\Main\Event,
	\Bitrix\Main\Loader,
	\Bitrix\Main\EventResult,
	\Bitrix\Main\Type\DateTime,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\Im;

use \Bitrix\ImOpenLines,
	\Bitrix\ImOpenLines\Log,
	\Bitrix\ImOpenLines\Chat,
	\Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Session,
	\Bitrix\ImOpenLines\Tools\Lock,
	\Bitrix\ImOpenLines\AutomaticAction,
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

	protected $cacheRemoveSession = [];

	/**
	 * @param $parameters
	 * @return array
	 */
	protected static function sendEventOnBeforeSessionTransfer($parameters): array
	{
		$result = $parameters['newOperatorQueue'];

		//Event
		$event = new Event('imopenlines', 'OnBeforeSessionTransfer', $parameters);
		$event->send();

		foreach($event->getResults() as $eventResult)
		{
			$errorEvent = false;
			$errorsMessageEvent = [];

			if ((int)$eventResult->getType() === EventResult::SUCCESS)
			{
				$newValues = $eventResult->getParameters();
				if (!empty($newValues['newOperatorQueue']))
				{
					if(!isset($newValues['newOperatorQueue']['RESULT']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = 'The event handler must pass the value [newOperatorQueue][RESULT]';
					}
					elseif (!is_bool($newValues['newOperatorQueue']['RESULT']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = '[newOperatorQueue][RESULT] only the bool type is allowed';
					}
					if(!isset($newValues['newOperatorQueue']['OPERATOR_ID']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = 'The event handler must pass the value [newOperatorQueue][OPERATOR_ID]';
					}
					elseif(
						!is_numeric($newValues['newOperatorQueue']['OPERATOR_ID']) &&
						(
							mb_strpos($newValues['newOperatorQueue']['OPERATOR_ID'], 'queue') !== 0 ||
							!is_numeric(mb_substr($newValues['newOperatorQueue']['OPERATOR_ID'], 5))
						)
					)
					{
						$errorEvent = true;
						$errorsMessageEvent[] = '[newOperatorQueue][OPERATOR_ID] only a number is allowed, including 0 or a number with the "queue" prefix';
					}
					if(!isset($newValues['newOperatorQueue']['OPERATOR_LIST']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = 'The event handler must pass the value [newOperatorQueue][OPERATOR_LIST]';
					}
					elseif(!is_array($newValues['newOperatorQueue']['OPERATOR_LIST']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = '[newOperatorQueue][OPERATOR_LIST] only an array is allowed, including an empty array []';
					}
					else
					{
						foreach ($newValues['newOperatorQueue']['OPERATOR_LIST'] as $operator)
						{
							if(empty($operator))
							{
								$errorEvent = true;
								$errorsMessageEvent[] = '[newOperatorQueue][OPERATOR_LIST] each value must not be empty';
							}
							elseif(!is_numeric($operator))
							{
								$errorEvent = true;
								$errorsMessageEvent[] = '[newOperatorQueue][OPERATOR_LIST] each value must be a digit';
							}
						}
					}
					if(!isset($newValues['newOperatorQueue']['DATE_QUEUE']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = 'The event handler must pass the value [newOperatorQueue][DATE_QUEUE]';
					}
					elseif(
						!($newValues['newOperatorQueue']['DATE_QUEUE'] instanceof DateTime) &&
						!empty($newValues['newOperatorQueue']['DATE_QUEUE'])
					)
					{
						$errorEvent = true;
						$errorsMessageEvent[] = '[newOperatorQueue][DATE_QUEUE] a valid value is only an object of the "\Bitrix\Main\Type\DateTime" class or "false"';
					}
					elseif(
						empty($newValues['newOperatorQueue']['DATE_QUEUE']) &&
						(
							empty($newValues['newOperatorQueue']['OPERATOR_ID']) &&
							empty($newValues['newOperatorQueue']['OPERATOR_LIST'])
						)
					)
					{
						$errorEvent = true;
						$errorsMessageEvent[] = '[newOperatorQueue][DATE_QUEUE] if an "empty" value is passed, including "false", then [newOperatorQueue][OPERATOR_ID] or [newOperatorQueue][OPERATOR_LIST] must have non-empty values';
					}
					if(!isset($newValues['newOperatorQueue']['QUEUE_HISTORY']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = 'The event handler must pass the value [newOperatorQueue][QUEUE_HISTORY]';
					}
					elseif (!is_array($newValues['newOperatorQueue']['QUEUE_HISTORY']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = '[newOperatorQueue][QUEUE_HISTORY] only an array is allowed, including an empty array []';
					}

					if($errorEvent === false)
					{
						$result = $newValues['newOperatorQueue'];
					}
				}
				else
				{
					$errorEvent = true;
					$errorsMessageEvent[] = 'The event handler returned an empty "newOperatorQueue" value';
				}
			}
			else
			{
				$errorEvent = true;
				$errorsMessageEvent[] = 'The result of the processing of the event returned with an error';
			}

			if($errorEvent === true)
			{
				$eventError = new Event('imopenlines', 'OnErrorEventBeforeSessionTransfer',
					[
						'errors' => $errorsMessageEvent,
						'eventResult' => $eventResult
					]);
				$eventError->send();
			}
		}

		return $result;
	}

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
	 * @param bool $ignorePause
	 * @return bool|string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isOperatorActive($userId, bool $ignorePause = false)
	{
		return ImOpenLines\Queue::isOperatorActive($userId, $this->config['CHECK_AVAILABLE'], $ignorePause);
	}

	/**
	 * Are there any available operators in the line.
	 *
	 * @param bool $ignorePause
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isOperatorsActiveLine(bool $ignorePause = false): bool
	{
		return ImOpenLines\Queue::isOperatorsActiveLine($this->config['ID'], $this->config['CHECK_AVAILABLE'], $ignorePause);
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

		if($this->isOperatorActive($userId) === true)
		{
			if((int)$userId !== (int)$currentOperator)
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
	 * Returns the default queue time
	 *
	 * @return int
	 */
	public function getQueueTime()
	{
		$queueTime = ImOpenLines\Queue::UNDISTRIBUTED_QUEUE_TIME;

		if($this->config['QUEUE_TIME'] > 0)
		{
			$queueTime = $this->config['QUEUE_TIME'];
		}

		return $queueTime;
	}

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
		$defaultQueueTime = $this->getQueueTime();

		$result = [
			'OPERATOR_ID' => 0,
			'QUEUE_HISTORY' => [],
			'OPERATOR_LIST' => [],
			'DATE_OPERATOR' => null,
			'DATE_QUEUE' => null,
			'DATE_NO_ANSWER' => null,
			'JOIN_BOT' => false,
			'UNDISTRIBUTED' => false,

			'OPERATOR_CRM' => false,
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
				$result['DATE_QUEUE']->add($defaultQueueTime . ' SECONDS');

				$result['DATE_NO_ANSWER'] = (new DateTime())->add($this->config['NO_ANSWER_TIME'] . ' SECONDS');

				//CRM
				if ($crmManager && $isGroupByChat == false && $this->config['CRM'] == 'Y' && $crmManager->isLoaded() && $this->config['CRM_FORWARD'] == 'Y')
				{
					$crmManager->search();

					$crmOperatorId = $crmManager->getOperatorId();

					if(
						$crmOperatorId !== null &&
						$crmOperatorId > 0 &&
						$this->isActiveCrmUser($crmOperatorId) === true
					)
					{
						$operatorId = $crmOperatorId;

						$result['DATE_QUEUE'] = null;
						$result['OPERATOR_CRM'] = true;
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

			if(
				$manual &&
				$resultOperatorQueue['RESULT'] != true
			)
			{
				self::sendMessageSkipAlone($this->session['CHAT_ID']);
			}
			else
			{
				$updateSessionCheck = [
					'REASON_RETURN' => ImOpenLines\Queue::REASON_DEFAULT
				];

				$reasonReturn = SessionCheckTable::getById($this->session['ID'])->fetch()['REASON_RETURN'];

				//Event
				$resultOperatorQueue = self::sendEventOnBeforeSessionTransfer(
					[
						'session' => $this->session,
						'config' => $this->config,
						'chat' => $this->chat,
						'reasonReturn' => $reasonReturn,
						'newOperatorQueue' => $resultOperatorQueue
					]
				);
				// END Event

				$leaveTransfer = (string)$this->config['WELCOME_BOT_LEFT'] === Config::BOT_LEFT_CLOSE && Im\User::getInstance($this->session['OPERATOR_ID'])->isBot()? 'N':'Y';

				if((bool)$resultOperatorQueue['RESULT'] === true)
				{
					if(!empty($resultOperatorQueue['OPERATOR_ID']))
					{
						if((int)$this->session['OPERATOR_ID'] !== (int)$resultOperatorQueue['OPERATOR_ID'])
						{
							$this->chat->transfer([
								'FROM' => $this->session['OPERATOR_ID'],
								'TO' => $resultOperatorQueue['OPERATOR_ID'],
								'MODE' => Chat::TRANSFER_MODE_AUTO,
								'LEAVE' => $leaveTransfer
							]);
						}
					}
					elseif(!empty($resultOperatorQueue['OPERATOR_LIST']))
					{
						$this->chat->setOperators($resultOperatorQueue['OPERATOR_LIST'], $this->session['ID']);
						$this->chat->update(['AUTHOR_ID' => 0]);
					}

					$updateSessionCheck['UNDISTRIBUTED'] = 'N';

					$result = true;
				}
				else
				{
					if((int)$this->session['OPERATOR_ID'] !== 0)
					{
						$this->chat->transfer([
							'FROM' => $this->session['OPERATOR_ID'],
							'TO' => 0,
							'MODE' => Chat::TRANSFER_MODE_AUTO,
							'LEAVE' => $leaveTransfer
						]);
					}

					$updateSessionCheck['UNDISTRIBUTED'] = 'Y';
				}

				$updateSessionCheck['DATE_QUEUE'] = $resultOperatorQueue['DATE_QUEUE'];

				SessionCheckTable::update($this->session['ID'], $updateSessionCheck);

				if((int)$this->session['OPERATOR_ID'] !== (int)$resultOperatorQueue['OPERATOR_ID'])
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
	 * The automatic action for an incoming message from an external source
	 *
	 * @param bool $finish
	 * @param bool $vote
	 * @return bool
	 */
	public function automaticActionAddMessage($finish = false, $vote = false): bool
	{
		$removeSession = $this->isRemoveSession($finish, $vote);

		if($removeSession !== false)
		{
			$this->transferOperatorNotAvailable($removeSession);
		}

		return true;
	}

	/**
	 * Do I need to remove the session from the operator?
	 *
	 * @param bool $finish
	 * @param bool $vote
	 * @param bool $noCache
	 * @return bool|string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isRemoveSession($finish = false, $vote = false, $noCache = false)
	{
		$result = false;

		if(
			!$noCache &&
			isset($this->cacheRemoveSession[$this->session['ID']])
		)
		{
			$result = $this->cacheRemoveSession[$this->session['ID']];
		}
		else
		{
			if(
				$finish !== true &&
				$vote !== true &&
				!$this->sessionManager->isNowCreated() &&

				!empty($this->session['OPERATOR_ID']) &&
				(string)$this->session['PAUSE'] !== 'Y' &&
				$this->session['STATUS'] >= Session::STATUS_ANSWER &&

				!ImOpenLines\Queue::isOperatorSingleInLine($this->session['CONFIG_ID'], $this->session['OPERATOR_ID'])
			)
			{
				$operatorActive = $this->isOperatorActive($this->session['OPERATOR_ID'], true);
				if($operatorActive !== true)
				{
					$result = $operatorActive;
				}
			}

			$this->cacheRemoveSession[$this->session['ID']] = $result;
		}

		return $result;
	}

	/**
	 * Check the operator responsible for CRM on the possibility of transfer of chat.
	 *
	 * @param $userId
	 * @return bool|string
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
	 * @param string $reasonReturn
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function transferOperatorNotAvailable($reasonReturn = ImOpenLines\Queue::REASON_OPERATOR_NOT_AVAILABLE): void
	{
		ImOpenLines\Queue::returnSessionToQueue($this->session['ID'], $reasonReturn);

		ImOpenLines\Queue::transferToNextSession(false, ImOpenLines\Queue\Event::COUNT_SESSIONS_REALTIME, $this->config['ID']);
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
			'TO_CHAT_ID' => $this->session['CHAT_ID'],
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
			'TO_CHAT_ID' => $chatId,
			'MESSAGE' => Loc::getMessage('IMOL_QUEUE_SESSION_SKIP_ALONE'),
			'SYSTEM' => 'Y',
			'SKIP_COMMAND' => 'Y'
		]);
	}
}