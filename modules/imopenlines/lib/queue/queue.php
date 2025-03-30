<?php
namespace Bitrix\ImOpenLines\Queue;

use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\EventResult;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

use Bitrix\Im;

use Bitrix\ImOpenLines;
use Bitrix\ImOpenLines\Chat;
use Bitrix\ImOpenLines\Config;
use Bitrix\ImOpenLines\Common;
use Bitrix\ImOpenLines\Session;
use Bitrix\ImOpenLines\Tools\Lock;
use Bitrix\ImOpenLines\Model\ConfigTable;
use Bitrix\ImOpenLines\Model\SessionCheckTable;

Loc::loadMessages(__FILE__);

/**
 * Class Queue
 * @package Bitrix\ImOpenLines\Queue
 */
abstract class Queue
{
	const PREFIX_KEY_LOCK = 'imol_transfer_chat_id_';

	/** @var Session */
	protected $sessionManager = null;
	protected $session = [];
	protected $config = [];
	/** @var Chat */
	protected $chat = null;

	protected $cacheRemoveSession = [];

	protected bool $isEnableGroupByChat = false;

	/** @var ImOpenLines\Crm */
	protected $crmManager = null;

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
					if (!isset($newValues['newOperatorQueue']['RESULT']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = 'The event handler must pass the value [newOperatorQueue][RESULT]';
					}
					elseif (!is_bool($newValues['newOperatorQueue']['RESULT']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = '[newOperatorQueue][RESULT] only the bool type is allowed';
					}
					if (!isset($newValues['newOperatorQueue']['OPERATOR_ID']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = 'The event handler must pass the value [newOperatorQueue][OPERATOR_ID]';
					}
					elseif (
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
					if (!isset($newValues['newOperatorQueue']['OPERATOR_LIST']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = 'The event handler must pass the value [newOperatorQueue][OPERATOR_LIST]';
					}
					elseif (!is_array($newValues['newOperatorQueue']['OPERATOR_LIST']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = '[newOperatorQueue][OPERATOR_LIST] only an array is allowed, including an empty array []';
					}
					else
					{
						foreach ($newValues['newOperatorQueue']['OPERATOR_LIST'] as $operator)
						{
							if (empty($operator))
							{
								$errorEvent = true;
								$errorsMessageEvent[] = '[newOperatorQueue][OPERATOR_LIST] each value must not be empty';
							}
							elseif (!is_numeric($operator))
							{
								$errorEvent = true;
								$errorsMessageEvent[] = '[newOperatorQueue][OPERATOR_LIST] each value must be a digit';
							}
						}
					}
					if (!isset($newValues['newOperatorQueue']['DATE_QUEUE']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = 'The event handler must pass the value [newOperatorQueue][DATE_QUEUE]';
					}
					elseif (
						!($newValues['newOperatorQueue']['DATE_QUEUE'] instanceof DateTime) &&
						!empty($newValues['newOperatorQueue']['DATE_QUEUE'])
					)
					{
						$errorEvent = true;
						$errorsMessageEvent[] = '[newOperatorQueue][DATE_QUEUE] a valid value is only an object of the "\Bitrix\Main\Type\DateTime" class or "false"';
					}
					elseif (
						empty($newValues['newOperatorQueue']['DATE_QUEUE'])
						&& empty($newValues['newOperatorQueue']['OPERATOR_ID'])
						&& empty($newValues['newOperatorQueue']['OPERATOR_LIST'])
					)
					{
						$errorEvent = true;
						$errorsMessageEvent[] = '[newOperatorQueue][DATE_QUEUE] if an "empty" value is passed, including "false", then [newOperatorQueue][OPERATOR_ID] or [newOperatorQueue][OPERATOR_LIST] must have non-empty values';
					}
					if (!isset($newValues['newOperatorQueue']['QUEUE_HISTORY']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = 'The event handler must pass the value [newOperatorQueue][QUEUE_HISTORY]';
					}
					elseif (!is_array($newValues['newOperatorQueue']['QUEUE_HISTORY']))
					{
						$errorEvent = true;
						$errorsMessageEvent[] = '[newOperatorQueue][QUEUE_HISTORY] only an array is allowed, including an empty array []';
					}

					if ($errorEvent === false)
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

			if ($errorEvent === true)
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
	public function __construct($session)
	{
		if ($session instanceof Session)
		{
			$this->sessionManager = $session;
			$this->session = $session->getData();
			$this->config = $session->getConfig();
			$this->chat = $session->getChat();
		}
	}

	public function setCrmManager(ImOpenLines\Crm $crmManager): self
	{
		$this->crmManager = $crmManager;
		return $this;
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
	 */
	public function startLock()
	{
		return Lock::getInstance()->set($this->getKeyLock());
	}

	/**
	 * @return bool
	 */
	public function stopLock()
	{
		return Lock::getInstance()->delete($this->getKeyLock());
	}

	/**
	 * @return DateTime
	 */
	protected function getNewDateNoAnswer()
	{
		$dateNoAnswer = SessionCheckTable::getById($this->session['ID'])->fetch()['DATE_NO_ANSWER'];

		if ($this->session['SEND_NO_ANSWER_TEXT'] != 'Y' && empty($dateNoAnswer))
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
	 */
	public function isUndistributedSession(): bool
	{
		$result = false;

		$resultRequest = SessionCheckTable::getList([
			'select' => ['SESSION_ID'],
			'filter' =>
			[
				'=UNDISTRIBUTED' => 'Y',
				'=SESSION.CONFIG_ID' => $this->config['ID'],
				'!=DATE_QUEUE' => NULL
			],
			'limit' => 1
		])->fetch();

		if (!empty($resultRequest))
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
	 */
	public function isOperatorAvailable($userId, $currentOperator = 0)
	{
		$result = false;

		if ($this->isOperatorActive($userId) === true)
		{
			if ((int)$userId !== (int)$currentOperator)
			{
				$freeCountChatOperator = ImOpenLines\Queue::getCountFreeSlotOperator($userId, $this->config['ID'], $this->config["MAX_CHAT"], $this->config["TYPE_MAX_CHAT"]);

				if ($freeCountChatOperator > 0)
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

	public function getAvailableOperators(array $userIds, $currentOperatorId = 0): array
	{
		$result = [];

		$freeCountChatOperators = ImOpenLines\Queue::getCountFreeSlotsOperators($userIds, $this->config['ID'], $this->config["MAX_CHAT"], $this->config["TYPE_MAX_CHAT"]);

		foreach($userIds as $userId)
		{
			if ($this->isOperatorActive($userId) === true)
			{
				if ((int)$userId !== (int)$currentOperatorId)
				{
					if (
						in_array($userId, array_keys($freeCountChatOperators))
						&& $freeCountChatOperators[$userId] > 0
					)
					{
						$result[] = (int)$userId;
					}
				}
				else
				{
					$result[] = (int)$userId;
				}
			}
		}


		return $result;
	}

	abstract public function getOperatorsQueue($currentOperator = 0);

	/**
	 * @param int $configId
	 * @param $fullCountOperators
	 */
	protected function processingEmptyQueue(int $configId, $fullCountOperators): void
	{
		if (!empty($configId))
		{
			if ($fullCountOperators > 0)
			{
				if ($this->config['SEND_NOTIFICATION_EMPTY_QUEUE'] === 'Y')
				{
					$this->config['SEND_NOTIFICATION_EMPTY_QUEUE'] = 'N';
					ConfigTable::update($configId, ['SEND_NOTIFICATION_EMPTY_QUEUE' => 'N']);
				}
			}
			elseif ($fullCountOperators === 0)
			{
				if ($this->config['SEND_NOTIFICATION_EMPTY_QUEUE'] === 'N')
				{
					ConfigTable::update($configId, ['SEND_NOTIFICATION_EMPTY_QUEUE' => 'Y']);
					$this->sendNotificationEmptyQueue($configId);
				}
			}
		}
	}

	/**
	 * @param int $configId
	 */
	protected function sendNotificationEmptyQueue(int $configId): void
	{
		if (Loader::includeModule('im'))
		{
			$message = Loc::getMessage('IMOL_QUEUE_NOTIFICATION_EMPTY_QUEUE', ['#URL#' => Common::getContactCenterPublicFolder() . 'lines_edit/?ID=' . $configId . '&SIDE=Y']);

			$notificationUserList = Common::getAdministrators();

			foreach($notificationUserList as $userId)
			{
				$notifyFields = [
					'TO_USER_ID' => $userId,
					'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
					'NOTIFY_MODULE' => 'imopenlines',
					'NOTIFY_EVENT' => 'default',
					'NOTIFY_MESSAGE' => $message,
					'RECENT_ADD' => 'Y'
				];

				\CIMNotify::Add($notifyFields);
			}
		}
	}

	/**
	 * Returns the default queue time
	 *
	 * @return int
	 */
	public function getQueueTime()
	{
		$queueTime = ImOpenLines\Queue::UNDISTRIBUTED_QUEUE_TIME;

		if ($this->config['QUEUE_TIME'] > 0)
		{
			$queueTime = $this->config['QUEUE_TIME'];
		}

		return $queueTime;
	}

	public function enableGroupChat(bool $flag): self
	{
		$this->isEnableGroupByChat = $flag;
		return $this;
	}

	/**
	 * @param int $operatorId
	 * @return array
	 */
	public function createSession($operatorId = 0): array
	{
		$defaultQueueTime = $this->getQueueTime();

		$result = [
			'OPERATOR_ID' => 0,
			'QUEUE_HISTORY' => [],
			'OPERATOR_LIST' => [],
			'OPERATOR_FULL_LIST' => [],
			'DATE_OPERATOR' => null,
			'DATE_QUEUE' => null,
			'DATE_NO_ANSWER' => null,
			'JOIN_BOT' => false,
			'UNDISTRIBUTED' => false,
			'OPERATOR_CRM' => false,
		];

		if (empty($operatorId))
		{
			$result['DATE_QUEUE'] = new DateTime();

			// Bot
			if (
				$this->config['ACTIVE'] != 'N' &&
				$this->config['WELCOME_BOT_ENABLE'] == 'Y' &&
				$this->config['WELCOME_BOT_ID'] > 0 &&
				(
					$this->config['WELCOME_BOT_JOIN'] == Config::BOT_JOIN_ALWAYS ||
					$this->chat->isNowCreated()
				)
			)
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
				if (
					$this->config['CRM'] == 'Y'
					&& $this->config['CRM_FORWARD'] == 'Y'
					&& !$this->isEnableGroupByChat
					&& ($this->crmManager instanceof ImOpenLines\Crm)
					&& $this->crmManager->isLoaded()
				)
				{
					$this->crmManager->search();

					$crmOperatorId = $this->crmManager->getOperatorId();

					if (
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

				$undistributedSession = $this->isUndistributedSession();

				// Queue
				if (empty($operatorId) && !$undistributedSession)
				{
					$resultOperatorQueue = $this->getOperatorsQueue();

					if (count($resultOperatorQueue['OPERATOR_LIST']) === 1)
					{
						$result['OPERATOR_FULL_LIST'] = $this->getAllOperatorsQueue()['OPERATOR_LIST'];
					}

					if ($resultOperatorQueue['RESULT'])
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

				if (empty($operatorId) && $undistributedSession)
				{
					$result['UNDISTRIBUTED'] = true;
					$result['DATE_QUEUE'] = new DateTime();
				}

				if (!empty($operatorId))
				{
					$result['DATE_OPERATOR'] = new DateTime();
				}
			}
		}
		else
		{
			$result['DATE_OPERATOR'] = new DateTime();
		}

		if (!empty($operatorId))
		{
			$result['OPERATOR_ID'] = $operatorId;
			if (empty($result['OPERATOR_LIST']))
			{
				$result['OPERATOR_LIST'] = [$operatorId];
			}
			if (empty($result['QUEUE_HISTORY']))
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
	 */
	public function transferToNext($manual = true)
	{
		$result = false;

		ImOpenLines\Debug::addQueue($this->config['ID'], $this->session['ID'], 'start' . __METHOD__, ['manual' => $manual]);

		if ($this->startLock())
		{
			$resultOperatorQueue = $this->getOperatorsQueue($this->session['OPERATOR_ID']);

			if (
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

				if ($this->session['STATUS'] > Session::STATUS_SKIP)
				{
					$this->prepareToQueue();
				}

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

				if ((bool)$resultOperatorQueue['RESULT'] === true)
				{
					if (!empty($resultOperatorQueue['OPERATOR_ID']))
					{
						if ((int)$this->session['OPERATOR_ID'] !== (int)$resultOperatorQueue['OPERATOR_ID'])
						{
							$this->chat->transfer(
								[
									'FROM' => $this->session['OPERATOR_ID'],
									'TO' => $resultOperatorQueue['OPERATOR_ID'],
									'MODE' => Chat::TRANSFER_MODE_AUTO,
									'LEAVE' => $leaveTransfer
								],
								$this->sessionManager
							);
						}
					}
					elseif (!empty($resultOperatorQueue['OPERATOR_LIST']))
					{
						$this->chat->setOperators($resultOperatorQueue['OPERATOR_LIST'], $this->session['ID']);
						$this->chat->update(['AUTHOR_ID' => 0]);
					}

					$updateSessionCheck['UNDISTRIBUTED'] = 'N';

					$result = true;
				}
				else
				{
					if ((int)$this->session['OPERATOR_ID'] !== 0)
					{
						$this->chat->transfer(
							[
								'FROM' => $this->session['OPERATOR_ID'],
								'TO' => 0,
								'MODE' => Chat::TRANSFER_MODE_AUTO,
								'LEAVE' => $leaveTransfer
							],
							$this->sessionManager
						);
					}

					$updateSessionCheck['UNDISTRIBUTED'] = 'Y';
				}

				if (
					Im\User::getInstance($this->session['OPERATOR_ID'])->isBot()
					&& $this->config['NO_ANSWER_RULE'] == Session::RULE_TEXT
					&& $this->session['SEND_NO_ANSWER_TEXT'] !== 'Y'
					&& $this->session['STATUS'] <= Session::STATUS_CLIENT
				)
				{
					$updateSessionCheck['DATE_NO_ANSWER'] = (new DateTime())->add($this->config['NO_ANSWER_TIME'] . ' SECONDS');
				}

				$updateSessionCheck['DATE_QUEUE'] = $resultOperatorQueue['DATE_QUEUE'];

				SessionCheckTable::update($this->session['ID'], $updateSessionCheck);

				if ((int)$this->session['OPERATOR_ID'] !== (int)$resultOperatorQueue['OPERATOR_ID'])
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

	protected function prepareToQueue(): void
	{
		$sessionData = [
			'STATUS' => Session::STATUS_SKIP
		];

		if (in_array((int)$this->session['STATUS'], [Session::STATUS_CLIENT, Session::STATUS_CLIENT_AFTER_OPERATOR], true))
		{
			$sessionData['WAIT_ANSWER'] = 'Y';
		}

		$this->sessionManager->update($sessionData);

		$removeOperator = true;
		if (
			(int)$this->session['OPERATOR_ID'] > 0
			&& Im\User::getInstance($this->session['OPERATOR_ID'])->isBot()
			&& (int)$this->session['OPERATOR_ID'] === (int)$this->config['WELCOME_BOT_ID']
			&& (string)$this->config['WELCOME_BOT_LEFT'] === Config::BOT_LEFT_CLOSE
		)
		{
			$removeOperator = false;
		}

		if ($removeOperator)
		{
			$chat = \Bitrix\Im\V2\Chat::getInstance((int)$this->session['CHAT_ID']);
			if ($chat->getChatId())
			{
				$config = new Im\V2\Relation\DeleteUserConfig(false, false, false);
				$chat->deleteUser((int)$this->session['OPERATOR_ID'], $config);
			}
		}
		else
		{
			$fakeRelations = new \Bitrix\ImOpenLines\Relation((int)$this->session['CHAT_ID']);
			$fakeRelations->addRelation((int)$this->session['OPERATOR_ID']);
		}
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

		if ($removeSession !== false)
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
	 */
	public function isRemoveSession($finish = false, $vote = false, $noCache = false)
	{
		$result = false;

		if (
			!$noCache &&
			isset($this->cacheRemoveSession[$this->session['ID']])
		)
		{
			$result = $this->cacheRemoveSession[$this->session['ID']];
		}
		else
		{
			if (
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
				if ($operatorActive !== true)
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
	 */
	public function isActiveCrmUser($userId)
	{
		return $this->isOperatorActive($userId);
	}

	/**
	 * Directing a conversation to a queue when an operator is not available.
	 *
	 * @param string $reasonReturn
	 */
	public function transferOperatorNotAvailable($reasonReturn = ImOpenLines\Queue::REASON_OPERATOR_NOT_AVAILABLE): void
	{
		ImOpenLines\Queue::returnSessionToQueue($this->session['ID'], $reasonReturn);

		ImOpenLines\Queue::transferToNextSession(false, ImOpenLines\Queue\Event::COUNT_SESSIONS_REALTIME, $this->config['ID']);
	}

	/**
	 * @return bool
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

	public function getAllOperatorsQueue($currentOperator = 0): array
	{
		$queueTime = $this->getQueueTime();

		$result = [
			'RESULT' => false,
			'OPERATOR_ID' => 0,
			'OPERATOR_LIST' => [],
			'DATE_QUEUE' => (new DateTime())->add($queueTime . ' SECONDS'),
			'QUEUE_HISTORY' => [],
		];

		$queueHistory = [];

		$res = ImOpenLines\Queue::getList([
			'select' => [
				'ID',
				'USER_ID'
			],
			'filter' => [
				'=CONFIG_ID' => $this->config['ID']
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC'
			],
		]);

		$queueUsers = $res->fetchAll();
		$userIds = array_map(function ($user) {
			return (int)$user['USER_ID'];
		}, $queueUsers);

		$operatorList = $this->getAvailableOperators($userIds, $currentOperator);
		foreach ($operatorList as $userId)
		{
			$queueHistory[$userId] = true;
		}

		$this->processingEmptyQueue($this->config['ID'], count($userIds));

		if(!empty($operatorList))
		{
			$result = [
				'RESULT' => true,
				'OPERATOR_ID' => 0,
				'OPERATOR_LIST' => $operatorList,
				'DATE_QUEUE' => (new DateTime())->add($queueTime . ' SECONDS'),
				'QUEUE_HISTORY' => $queueHistory,
			];
		}

		return $result;
	}
}
