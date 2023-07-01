<?php
namespace Bitrix\ImOpenLines;

use Bitrix\Im\V2\Message\ReadService;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\UserConsent\Consent;

use Bitrix\Im\User;
use Bitrix\Im\Model\ChatTable;
use Bitrix\Im\Model\MessageTable;

use Bitrix\ImOpenLines\Log\Library;
use Bitrix\Imopenlines\Im\Messages;
use Bitrix\ImOpenLines\Log\EventLog;
use Bitrix\ImOpenLines\Session\Agent;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\ImOpenLines\Model\SessionCheckTable;
use Bitrix\Imopenlines\Model\SessionIndexTable;
use Bitrix\ImOpenLines\Model\SessionAutomaticTasksTable;
use Bitrix\Imopenlines\Model\ConfigAutomaticMessagesTable;

use Bitrix\ImConnector;
use Bitrix\ImConnector\InteractiveMessage;

Loc::loadMessages(__FILE__);

class Session
{
	private $config = [];
	private $session = [];
	private $user = [];
	private $connectorId = '';

	/* @var Chat */
	public $chat = null;

	private $action = 'none';
	public $joinUserId = 0;
	public $joinUserList = [];
	private $isCreated = false;
	protected $isCloseVote = false;
	protected $isDisabledSendSystemMessage = false;
	protected $isForcedSendVote = false;

	public const RULE_TEXT = 'text';
	public const RULE_FORM = 'form';
	public const RULE_NONE = 'none';

	public const ACTION_NO_ANSWER = 'no_answer';
	public const ACTION_CLOSED = 'closed';
	public const ACTION_NONE = 'none';

	public const MODE_INPUT = 'input';
	public const MODE_OUTPUT = 'output';

	public const CACHE_QUEUE = 'queue';
	public const CACHE_CLOSE = 'close';
	public const CACHE_MAIL = 'mail';
	public const CACHE_INIT = 'init';
	public const CACHE_NO_ANSWER = 'no_answer';

	/** New dialog opens. */
	public const STATUS_NEW = 0;
	/** The operator sent the dialog to the queue. */
	public const STATUS_SKIP = 5;
	/** The operator took the dialogue to work. */
	public const STATUS_ANSWER = 10;
	/** The client is waiting for the operator's response. */
	public const STATUS_CLIENT = 20;
	/** The client is waiting for the operator's answer (new question after answer). */
	public const STATUS_CLIENT_AFTER_OPERATOR = 25;
	/** The operator responded to the client. */
	public const STATUS_OPERATOR = 40;
	/** The dialogue in the mode of closing (pending the vote or after auto-answer). */
	public const STATUS_WAIT_CLIENT = 50;
	/** The conversation has ended. */
	public const STATUS_CLOSE = 60;
	/** Spam / forced termination. */
	public const STATUS_SPAM = 65;
	/** Duplicate session. The session is considered closed. */
	public const STATUS_DUPLICATE = 69;
	/** Closed without sending special messages and notifications. */
	public const STATUS_SILENTLY_CLOSE = 75;

	public const VOTE_LIKE = 5;
	public const VOTE_DISLIKE = 1;

	/**
	 * Session constructor.
	 * @param array $config An array describing the setting of an open line.
	 */
	public function __construct($config = null)
	{
		if (is_array($config))
		{
			$this->config = $config;
		}

		Loader::includeModule('im');
	}

	/**
	 * Initialization of the session by the given data.
	 *
	 * @param array $session Array describing the session.
	 * @param array $config An array describing the setting of an open line.
	 * @param Chat $chat The chat instance.
	 */
	public function loadByArray($session, $config, $chat)
	{
		$this->session = $session;
		$this->config = $config;
		$this->chat = $chat;
		$this->connectorId = $session['SOURCE'];

		Debug::addSession($this, __METHOD__);
	}

	/**
	 * Session load method.
	 *
	 * @param array $params
	 * @return bool
	 */
	public function load(array $params)
	{
		$result = false;

		$parsedUserCode = Session\Common::parseUserCode($params['USER_CODE']);
		$parsedUserCodeFail = $parsedUserCode;

		if (empty($params['CONFIG_ID']))
		{
			$params['CONFIG_ID'] = $parsedUserCode['CONFIG_ID'];
		}
		$params['USER_ID'] = $parsedUserCode['CONNECTOR_USER_ID'];
		$params['SOURCE'] = $parsedUserCode['CONNECTOR_ID'];
		$params['CHAT_ID'] = $parsedUserCode['EXTERNAL_CHAT_ID'];

		if (Connector::isEnableGroupByChat($parsedUserCode['CONNECTOR_ID']))
		{
			$parsedUserCode['CONNECTOR_USER_ID'] = 0;

			if (!empty($params['USER_ID']))
			{
				$params['USER_CODE_FAIL'] = $params['USER_CODE'];
			}

			$params['USER_CODE'] = Session\Common::combineUserCode($parsedUserCode);
		}
		else
		{
			//Handles the situation where the group chat feature or not has been changed.
			$parsedUserCodeFail['CONNECTOR_USER_ID'] = 0;
			$params['USER_CODE_FAIL'] = Session\Common::combineUserCode($parsedUserCodeFail);
		}

		$resultStart = $this->start($params);

		if (
			$resultStart->isSuccess() &&
			$resultStart->getResult() === true
		)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param $params
	 * @return Result
	 */
	public function start($params)
	{
		Debug::addSession($this, 'begin ' . __METHOD__, $params);
		$result = new Result();

		$result->setResult(false);

		$this->connectorId = $params['SOURCE'];

		$fields = [];
		$fields['PARENT_ID'] = (int)($params['PARENT_ID'] ?? 0);
		$fields['USER_CODE'] = $params['USER_CODE'];

		$fields['USER_CODE_FAIL'] = $params['USER_CODE_FAIL'];

		$fields['CONFIG_ID'] = (int)$params['CONFIG_ID'];
		$fields['USER_ID'] = (int)$params['USER_ID'];
		$fields['OPERATOR_ID'] = (int)($params['OPERATOR_ID'] ?? 0);
		$params['CRM_TRACE_DATA'] = (string)($params['CRM_TRACE_DATA'] ?? '');
		$params['CRM_SKIP_PHONE_VALIDATE'] = (string)($params['CRM_SKIP_PHONE_VALIDATE'] ?? '');
		$fields['SOURCE'] = (string)($params['SOURCE'] ?? '');
		$fields['MODE'] =  (isset($params['MODE']) && $params['MODE'] === self::MODE_OUTPUT ? self::MODE_OUTPUT : self::MODE_INPUT);
		$params['DEFERRED_JOIN'] = (isset($params['DEFERRED_JOIN']) && $params['DEFERRED_JOIN'] === 'Y' ? 'Y' : 'N');
		$params['SKIP_CREATE'] = (isset($params['SKIP_CREATE']) && $params['SKIP_CREATE'] === 'Y' ? 'Y' : 'N');
		$params['REOPEN'] = (isset($params['REOPEN']) && $params['REOPEN'] === 'Y' ? 'Y' : 'N');
		$params['SKIP_CRM'] = (isset($params['SKIP_CRM']) && $params['SKIP_CRM'] === 'Y' ? 'Y' : 'N');

		//Check open line configuration load
		if (empty($this->config) && !empty($fields['CONFIG_ID']))
		{
			$configManager = new Config();
			$this->config = $configManager->get($fields['CONFIG_ID']);
		}
		if (empty($this->config))
		{
			$result->addError(new Error(
				Loc::getMessage('IMOL_SESSION_ERROR_NO_IMOL_CONFIGURATION'),
				'NO IMOL CONFIGURATION',
				__METHOD__,
				$params
			));
		}

		if ($result->isSuccess())
		{
			if ($this->prepareUserChat($params) !== true || empty($this->chat))
			{
				$result->addError(new Error(Loc::getMessage('IMOL_SESSION_ERROR_NO_CHAT'), 'NO CHAT', __METHOD__, $params));
			}
		}

		if ($result->isSuccess())
		{
			//Load session
			$resultReading = $this->readingSession($fields, $params);

			if ($resultReading->isSuccess())
			{
				if ($resultReading->getResult() == true)
				{
					$result->setResult(true);
				}
				//If you do create a session
				elseif
				(
					$params['SKIP_CREATE'] !== 'Y' &&
					!$this->isCloseVote()
				)
				{
					//Creating a new session
					$resultCreate = $this->createSession($fields, $params);

					if ($resultCreate->isSuccess())
					{
						$result->setResult(true);
					}
					else
					{
						$result->addErrors($resultCreate->getErrors());
					}
				}
			}
			else
			{
				$result->addErrors($resultReading->getErrors());
			}
		}

		Debug::addSession($this, 'end ' . __METHOD__, ['SUCCESS' => $result->isSuccess(), 'ERRORS' => $result->getErrorMessages()]);

		return $result;
	}

	/**
	 * @param $fields
	 * @param $params
	 * @return Result
	 */
	protected function createSession($fields, $params)
	{
		Debug::addSession($this, 'begin ' . __METHOD__, ['fields' => $fields, '$params' => $params]);

		$result = new Result();

		/* CRM BLOCK */
		if ($params['SKIP_CRM'] != 'Y')
		{
			$crmManager = new Crm($this);
			if ($crmManager->isLoaded())
			{
				$crmManager
					->getFields()
					->setSkipPhoneValidate($params['CRM_SKIP_PHONE_VALIDATE'] === 'Y')
					->setDataFromUser($fields['USER_ID'])
				;
				$crmManager->setModeCreate($this->config['CRM_CREATE']);

				if (Connector::isNeedCRMTracker($fields['SOURCE']))
				{
					$crmManager
						->setSkipCreate()
						->setIgnoreSearchPerson();
				}

				$fields['CRM_TRACE_DATA'] = Crm\Tracker::getTraceData($fields['USER_ID'], $params['CRM_TRACE_DATA']);
			}
		}

		if ($fields['MODE'] === self::MODE_OUTPUT)
		{
			$previousSessionBlock = ReplyBlock::getBlockFromPreviousSession($fields);
		}

		$fields['CHAT_ID'] = $this->chat->getData('ID');

		if ($this->chat->isNowCreated())
		{
			$fields['START_ID'] = 0;
			$fields['IS_FIRST'] = 'Y';
			$fields['LAST_SEND_MAIL_ID'] = 0;
		}
		else
		{
			$fields['START_ID'] = $this->chat->getData('LAST_MESSAGE_ID')+1;
			$fields['IS_FIRST'] = 'N';
			$fields['LAST_SEND_MAIL_ID'] = $this->chat->getData('LAST_MESSAGE_ID');

			$this->chat->join($fields['USER_ID']);
		}

		if ($fields['MODE'] == self::MODE_OUTPUT)
		{
			$fields['STATUS'] = self::STATUS_ANSWER;
		}

		$resultAdd = Model\SessionTable::add($fields);
		if ($resultAdd->isSuccess())
		{
			$this->isCreated = true;
			$fields['SESSION_ID'] = $resultAdd->getId();

			$resultAddSessionCheck = Model\SessionCheckTable::add([
				'SESSION_ID' => $fields['SESSION_ID'],
				'DATE_CLOSE' => (new DateTime())->add('180 SECONDS'),
				'DATE_QUEUE' => (new DateTime())->add('180 SECONDS'),
			]);

			if ($resultAddSessionCheck->isSuccess())
			{
				$this->session = $fields;
				$this->session['ID'] = $this->session['SESSION_ID'] = $fields['SESSION_ID'];

				$dateNoAnswer = null;

				$queueManager = Queue::initialization($this);

				$resultQueue = $queueManager->createSession($fields['OPERATOR_ID'], $crmManager, Connector::isEnableGroupByChat($fields['SOURCE']));

				$this->session['JOIN_BOT'] = $resultQueue['JOIN_BOT'];
				$this->session['OPERATOR_ID'] = $fields['OPERATOR_ID'] = $params['OPERATOR_ID'] = $resultQueue['OPERATOR_ID'];
				$fields['DATE_OPERATOR'] = $resultQueue['DATE_OPERATOR'];
				$fields['QUEUE_HISTORY'] = $this->session['QUEUE_HISTORY'] = $resultQueue['QUEUE_HISTORY'];
				$dateNoAnswer = $resultQueue['DATE_NO_ANSWER'];
				$undistributed = $resultQueue['UNDISTRIBUTED'] == true? 'Y' : 'N';
				$dateQueue = $resultQueue['DATE_QUEUE'];
				$this->joinUserList = $resultQueue['OPERATOR_LIST'];
				$fields['OPERATOR_FROM_CRM'] = $resultQueue['OPERATOR_CRM'] == true? 'Y' : 'N';

				//Send message
				if ($fields['PARENT_ID'] > 0)
				{
					$messageId = Messages\Session::sendMessageStartSessionByMessage(
						$fields['CHAT_ID'],
						$fields['SESSION_ID'],
						$fields['PARENT_ID']
					);
				}
				else
				{
					$messageId = Messages\Session::sendMessageStartSession($fields['CHAT_ID'], $fields['SESSION_ID']);
				}
				//END Send message

				if ($this->chat->isNowCreated())
				{
					if (intval($messageId) < 0)
					{
						$messageId = 0;
					}

					$fields['START_ID'] = $messageId;
				}

				$this->chat->updateFieldData([Chat::FIELD_SESSION => [
					'ID' => $fields['SESSION_ID'],
					'DATE_CREATE' => new DateTime()
				]]);

				if ($fields['MODE'] == self::MODE_INPUT)
				{
					/* CLOSED LINE */
					if ($this->config['ACTIVE'] == 'N')
					{
						$this->action = self::ACTION_CLOSED;
						$fields['WORKTIME'] = 'N';
						$fields['WAIT_ACTION'] = 'Y';
					}
					elseif (!(new AutomaticAction\WorkTime($this))->isWorkTimeLine())
					{
						$fields['WORKTIME'] = 'N';
						if ($this->session['JOIN_BOT'])
						{
							$this->resetActionAll();
						}
						else
						{
							$fields['WAIT_ACTION'] = 'Y';
						}
					}
				}
				elseif ($fields['MODE'] == self::MODE_OUTPUT)
				{
					if ($this->config['ACTIVE'] == 'N')
					{
						$this->action = self::ACTION_CLOSED;
						$fields['WORKTIME'] = 'N';
						$fields['WAIT_ACTION'] = 'Y';
					}
					if ($fields['OPERATOR_ID'])
					{
						if ($this->chat->getData('AUTHOR_ID') == 0)
						{
							$this->chat->answer($fields['OPERATOR_ID'], true);
							$this->chat->join($fields['OPERATOR_ID']);
						}
					}
					$fields['WAIT_ANSWER'] = 'N';
				}

				$sessionId = $fields['SESSION_ID'];
				unset($fields['SESSION_ID']);
				Model\SessionTable::update($sessionId, $fields);
				$fields['SESSION_ID'] = $sessionId;

				if (
					$fields['MODE'] == self::MODE_INPUT
					&& !empty($this->joinUserList)
					&& $params['DEFERRED_JOIN'] == 'N'
				)
				{
					$this->chat->sendJoinMessage($this->joinUserList, $fields['OPERATOR_FROM_CRM']);
					$this->chat->join($this->joinUserList);
					$this->joinUserList = [];
				}

				$dateClose = new DateTime();

				if ($fields['MODE'] == self::MODE_INPUT)
				{
					$dateClose->add('1 MONTH');
				}
				else
				{
					$dateClose->add($this->config['AUTO_CLOSE_TIME'] . ' SECONDS');
				}

				Model\SessionCheckTable::update($fields['SESSION_ID'], [
					'DATE_CLOSE' => $dateClose,
					'DATE_QUEUE' => $dateQueue,
					'DATE_NO_ANSWER' => $dateNoAnswer,
					'UNDISTRIBUTED' => $undistributed,
				]);

				$sessionManager = Model\SessionTable::getByIdPerformance($fields['SESSION_ID']);
				$this->session = $sessionManager->fetch();
				$this->session['SESSION_ID'] = $this->session['ID'];

				$this->session['CHECK_DATE_CLOSE'] = $dateClose;
				$this->session['CHECK_DATE_QUEUE'] = $dateQueue;

				if (
					!empty($previousSessionBlock['BLOCK_DATE']) &&
					!empty($previousSessionBlock['BLOCK_REASON'])
				)
				{
					ReplyBlock::add($fields['SESSION_ID'], $this->chat, $previousSessionBlock);
				}
				elseif (Loader::includeModule('imconnector'))
				{
					$limit = \Bitrix\ImConnector\Connector::getReplyLimit($fields['SOURCE']);
					if ($limit)
					{
						if (!empty($limit['BLOCK_DATE']))
						{
							$limit['BLOCK_DATE'] = (new DateTime())->add($limit['BLOCK_DATE'] . ' SECONDS');

							ReplyBlock::add($fields['SESSION_ID'], $this->chat, $limit);
						}

						Messages\Session::sendMessageTimeLimit($fields['CHAT_ID'], $limit['BLOCK_REASON']);
					}
				}


				/* BLOCK STATISTIC*/
				ConfigStatistic::getInstance((int)$fields['CONFIG_ID'])->addInWork()->addSession();

				/* CRM BLOCK */
				if ($params['SKIP_CRM'] != 'Y')
				{
					if ($fields['MODE'] == self::MODE_INPUT)
					{
						if (
							!Connector::isEnableGroupByChat($fields['SOURCE'])
							&& $this->config['CRM'] == 'Y'
							&& $crmManager->isLoaded()
						)
						{
							$crmManager->getFields()->setTitle($this->chat->getData('TITLE'));

							$crmManager->setDefaultFlags();
							$crmManager->registrationChanges();
							$crmManager->sendCrmImMessages();
						}
						else
						{
							$crmManager->setDefaultFlags();
						}
					}
					elseif ($fields['MODE'] == self::MODE_OUTPUT)
					{
						if ($this->config['CRM'] == 'Y' && $crmManager->isLoaded())
						{
							$crmManager->getFields()->setTitle($this->chat->getData('TITLE'));

							if (
								$params['REOPEN'] === 'Y'
								|| $fields['IS_FIRST'] === 'N'
							)
							{
								$crmManager->setSkipAutomationTriggerFirstMessage();
							}

							$crmManager->registrationChanges();
							$crmManager->sendCrmImMessages();
						}
					}
				}

				/* Event */
				$eventData = [];
				$eventData['SESSION'] = $this->session;
				$eventData['RUNTIME_SESSION'] = $this;
				$eventData['CONFIG'] = $this->config;

				$event = new Main\Event('imopenlines', 'OnSessionStart', $eventData);
				$event->send();
				/* END Event */

				if (Connector::isLiveChat($this->session['SOURCE']))
				{
					$parsedUserCode = Session\Common::parseUserCode($this->session['USER_CODE']);

					\Bitrix\Pull\Event::add($this->session['USER_ID'], [
						'module_id' => 'imopenlines',
						'command' => 'sessionStart',
						'params' => [
							'chatId' => (int)$parsedUserCode['EXTERNAL_CHAT_ID'],
							'sessionId' => (int)$this->session['ID'],
						]
					]);
				}
			}
			else
			{
				$result->addErrors($resultAddSessionCheck->getErrors());
			}

		}
		else
		{
			$result->addErrors($resultAdd->getErrors());
		}

		Debug::addSession($this, 'end ' . __METHOD__, ['SUCCESS' => $result->isSuccess(), 'ERRORS' => $result->getErrorMessages()]);

		return $result;
	}


	/**
	 * @param array $params
	 * @return Result
	 */
	public function getLast(array $params): Result
	{
		$result = new Result();

		if (empty($params['USER_CODE']))
		{
			$result->addError(new Error(Loc::getMessage('IMOL_SESSION_ERROR_NO_USER_CODE'), 'NO IMOL CONFIGURATION', __METHOD__));
		}

		$select = Model\SessionTable::getSelectFieldsPerformance();

		$orm = Model\SessionTable::getList([
			'select' => $select,
			'filter' => [
				'=USER_CODE' => $params['USER_CODE'],
			],
			'order' => ['ID' => 'DESC'],
			'limit' => 1
		]);
		$loadSession = $orm->fetch();

		if (!empty($loadSession))
		{
			$loadSession['SESSION_ID'] = $loadSession['ID'];
			$this->session = $loadSession;
			$this->chat = new Chat($this->session['CHAT_ID']);

			$configManager = new Config();
			$this->config = $configManager->get($loadSession['CONFIG_ID']);
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_SESSION_ERROR_NO_LAST_SESSION'), 'NO LAST SESSION', __METHOD__));
		}

		return $result;
	}

	/**
	 * @param $fields
	 * @param $params
	 * @return Result
	 */
	protected function readingSession($fields, $params)
	{
		Debug::addSession($this, 'begin ' . __METHOD__, ['fields' => $fields, '$params' => $params]);

		$result = new Result();

		$result->setResult(false);
		$configManager = new Config();

		$loadSession = false;

		$select = Model\SessionTable::getSelectFieldsPerformance();
		$select['CHECK_DATE_CLOSE'] = 'CHECK.DATE_CLOSE';
		$select['CHECK_DATE_QUEUE'] = 'CHECK.DATE_QUEUE';

		$userCode = $fields['USER_CODE'];

		if (!empty($fields['USER_CODE_FAIL']))
		{
			$userCode = [$fields['USER_CODE'], $fields['USER_CODE_FAIL']];
		}

		$orm = Model\SessionTable::getList([
			'select' => $select,
			'filter' => [
				'=USER_CODE' => $userCode,
				'=CLOSED' => 'N'
			],
			'order' => ['ID' => 'DESC']
		]);
		while ($row = $orm->fetch())
		{
			if (
				$row['USER_CODE'] === $fields['USER_CODE']
				&& empty($loadSession)
			)
			{
				$loadSession = $row;
			}
			//Closes non-closed sessions
			else
			{
				try
				{
					self::closeDuplicate(
						[
							'ID' => $row['ID'],
							'CHAT_ID' => $row['CHAT_ID'],
							'OPERATOR_ID' => $row['OPERATOR_ID'],
						],
						[
							'CHAT_ID' => $loadSession['CHAT_ID'],
							'OPERATOR_ID' => $loadSession['OPERATOR_ID'],
						]
					);
				}
				catch (\Bitrix\Main\SystemException $e)
				{
				}
			}
		}

		//There is no open session, but the session voting
		if (
			empty($loadSession)
			&&
			(
				(
					isset($params['VOTE_SESSION'])
					&& $params['VOTE_SESSION'] === 'Y'
				)
				||
				(
					isset($params['REOPEN'])
					&& $params['REOPEN'] === 'Y'
				)
			)
		)
		{
			$select = Model\SessionTable::getSelectFieldsPerformance();
			$select['CHECK_DATE_CLOSE'] = 'CHECK.DATE_CLOSE';
			$select['CHECK_DATE_QUEUE'] = 'CHECK.DATE_QUEUE';

			$orm = Model\SessionTable::getList([
				'select' => $select,
				'filter' => [
					'=USER_CODE' => $fields['USER_CODE'],
					'=CLOSED' => 'Y',
				],
				'order' => ['ID' => 'DESC']
			]);
			$loadSession = $orm->fetch();

			if (
				empty($loadSession) ||
				$loadSession['WAIT_VOTE'] !== 'Y'
			)
			{
				$loadSession = false;
			}
		}

		if ($loadSession)
		{
			$loadSession['SESSION_ID'] = $loadSession['ID'];
			$this->session = $loadSession;

			if (isset($params['VOTE_SESSION']) && $params['VOTE_SESSION'] === 'Y')
			{
				$this->session['VOTE_SESSION'] = true;
			}

			if ($fields['CONFIG_ID'] !== $this->session['CONFIG_ID'])
			{
				$this->config = $configManager->get($this->session['CONFIG_ID']);
				if (!empty($this->config))
				{
					$fields['CONFIG_ID'] = $this->session['CONFIG_ID'];
				}
				else
				{
					$result->addError(new Error(
						Loc::getMessage('IMOL_SESSION_ERROR_NO_IMOL_CONFIGURATION'),
						'NO IMOL CONFIGURATION',
						__METHOD__,
						$params
					));
				}
			}

			if ($result->isSuccess())
			{
				if (
					!($this->chat instanceof Chat)
					|| ($this->chat->getData('ID') != $this->session['CHAT_ID'])
				)
				{
					$this->chat = new Chat($this->session['CHAT_ID']);
				}

				if (
					isset($params['VOTE_SESSION'])
					&& $params['VOTE_SESSION'] === 'Y'
					&& isset($loadSession['DATE_CLOSE_VOTE'])
					&& $loadSession['DATE_CLOSE_VOTE'] instanceof DateTime
					&& $loadSession['DATE_CLOSE_VOTE']->getTimestamp() < time()
				)
				{
					$this->isCloseVote = true;
				}

				if (!$this->isCloseVote())
				{
					//If the session is closed and voting
					if (
						isset($params['VOTE_SESSION'])
						&& $params['VOTE_SESSION'] === 'Y'
						&& $loadSession['CLOSED'] === 'Y'
					)
					{
						Messages\Session::sendMessageReopenSession($this->session['CHAT_ID'], $this->session['SESSION_ID']);

						//statistics
						ConfigStatistic::getInstance((int)$this->session['CONFIG_ID'])->deleteClosed()->addInWork();

						$dateClose = new DateTime();

						$fullCloseTime = $this->config['FULL_CLOSE_TIME'];
						if (!empty($fullCloseTime))
						{
							$dateClose->add($fullCloseTime . ' MINUTES');
						}

						$connectorChatId = (int)Chat::parseLinesChatEntityId($this->session['USER_CODE'])['connectorChatId'];
						$this->session['END_ID'] = 0;
						$this->session['CLOSED'] = 'N';
						$this->session['DATE_CLOSE'] = '';
						$this->session['WAIT_ANSWER'] = 'N';
						$this->session['WAIT_ACTION'] = 'Y';
						$this->session['PAUSE'] = 'N';
						$this->session['LAST_SEND_MAIL_ID'] = Session::getLastMessageId($connectorChatId);

						Model\SessionTable::update($loadSession['ID'], [
							'END_ID' => $this->session['END_ID'],
							'CLOSED' => $this->session['CLOSED'],
							'DATE_CLOSE' => $this->session['DATE_CLOSE'],
							'WAIT_ANSWER' => $this->session['WAIT_ANSWER'],
							'WAIT_ACTION' => $this->session['WAIT_ACTION'],
							'PAUSE' => $this->session['PAUSE'],
							'LAST_SEND_MAIL_ID' => $this->session['LAST_SEND_MAIL_ID'],
							//'STATUS' => self::STATUS_WAIT_CLIENT
						]);
						Model\SessionCheckTable::add([
							'SESSION_ID' => $this->session['SESSION_ID'],
							'DATE_CLOSE' => $dateClose
						]);

						$this->chat->sendJoinMessage($this->joinUserList);
						$this->chat->join($this->session['OPERATOR_ID'], true, true);
						$this->chat->update(['AUTHOR_ID' => $this->session['OPERATOR_ID']]);

						$this->chat->updateFieldData([
							Chat::FIELD_SESSION => [
								'ID' => $this->session['SESSION_ID'],
								'PAUSE' => $this->session['PAUSE'],
								'WAIT_ANSWER' => $this->session['WAIT_ANSWER'],
								'DATE_CREATE' => $this->session['DATE_CREATE']->getTimestamp()
							],
						]);
					}
					elseif (!$this->chat->isNowCreated())
					{
						$this->chat->join($fields['USER_ID']);
					}

					if (
						!empty($fields['OPERATOR_ID']) &&
						empty($this->session['OPERATOR_ID'])
					)
					{
						$resultChatAnswer = $this->chat->answer($fields['OPERATOR_ID'], true);
						$this->answer($fields['OPERATOR_ID']);

						/*if (!$resultChatAnswer->isSuccess())
						{
							$result->addErrors($resultChatAnswer->getErrors());
						}*/
					}

					$result->setResult(true);
				}
			}
		}

		Debug::addSession($this, 'end ' . __METHOD__, ['SUCCESS' => $result->isSuccess(), 'ERRORS' => $result->getErrorMessages()]);

		return $result;
	}

	/**
	 * @param $params
	 * @param int $count
	 * @return bool
	 */
	private function prepareUserChat($params, $count = 0)
	{
		$result = false;

		$resultUserRelation = \Bitrix\Imopenlines\Model\UserRelationTable::getByPrimary($params['USER_CODE'])->fetch();
		if ($resultUserRelation)
		{
			if ($resultUserRelation['CHAT_ID'])
			{
				if (
					!($this->chat instanceof Chat)
					|| ($this->chat->getData('ID') != $resultUserRelation['CHAT_ID'])
				)
				{
					$this->chat = new Chat($resultUserRelation['CHAT_ID'], $params);
				}
				if ($this->chat->isDataLoaded())
				{
					$this->user = $resultUserRelation;

					$result = true;
				}
			}
			elseif ($count <= 20)
			{
				usleep(500);
				$result = $this->prepareUserChat($params, ++$count);
			}
		}
		elseif ($params['SKIP_CREATE'] != 'Y')
		{
			$params['USER_ID'] = intval($params['USER_ID']);
			\Bitrix\Imopenlines\Model\UserRelationTable::add([
				'USER_CODE' => $params['USER_CODE'],
				'USER_ID' => $params['USER_ID']
			]);

			$this->chat = new Chat();
			$this->chat->load([
				'USER_CODE' => $params['USER_CODE'],
				'USER_ID' => $params['USER_ID'],
				'LINE_NAME' => $this->config['LINE_NAME'],
				'CONNECTOR' => $params['CONNECTOR'],
			]);
			if ($this->chat->isDataLoaded())
			{
				\Bitrix\Imopenlines\Model\UserRelationTable::update($params['USER_CODE'], ['CHAT_ID' => $this->chat->getData('ID')]);

				$resultUserRelation = [
					'USER_CODE' => $params['USER_CODE'],
					'USER_ID' => $params['USER_ID'],
					'CHAT_ID' => $this->chat->getData('ID'),
					'AGREES' => 'N',
				];
				$this->user = $resultUserRelation;

				$result = true;
			}
			else
			{
				\Bitrix\Imopenlines\Model\UserRelationTable::delete($params['USER_CODE']);
			}
		}

		return $result;
	}

	/**
	 * @param $userId
	 */
	public function answer($userId): void
	{
		$this->setOperatorId($userId, false, false);

		if ($this->getData('CRM_ACTIVITY_ID') > 0)
		{
			$closeDate = new Main\Type\DateTime();
			$closeDate->add((int)$this->getConfig('AUTO_CLOSE_TIME').' SECONDS');
			$closeDate->add('1 DAY');

			$crmManager = new Crm($this);
			if ($crmManager->isLoaded())
			{
				$crmManager->setSessionAnswered(['DATE_CLOSE' => $closeDate]);
				$crmManager->executeAutomationAnswerControlTrigger($this);
				$crmManager->executeAutomationAnswerTrigger($this);

				if (
					$this->getData('STATUS') <= self::STATUS_ANSWER
					&& $this->getConfig('IGNORE_WELCOME_FORM_RESPONSIBLE') === 'Y'
					&& $this->getData('CRM') === 'Y'
				)
				{
					$crmEntitiesManager = \Bitrix\ImOpenLines\Crm\Common::getActivityBindings($this->getData('CRM_ACTIVITY_ID'));
					if($crmEntitiesManager->isSuccess())
					{
						$entities = $crmEntitiesManager->getData();
						if (isset($entities[Crm::ENTITY_CONTACT]) && $entities[Crm::ENTITY_CONTACT] > 0)
						{
							$entity = new \CCrmContact(false);
							$data = ['ASSIGNED_BY_ID' => $crmManager->getResponsibleCrmId()];
							$entity->Update($entities[Crm::ENTITY_CONTACT], $data);
						}
						if (isset($entities[Crm::ENTITY_LEAD]) && $entities[Crm::ENTITY_LEAD] > 0)
						{
							$entity = new \CCrmLead(false);
							$data = ['ASSIGNED_BY_ID' => $crmManager->getResponsibleCrmId()];
							$entity->Update($entities[Crm::ENTITY_LEAD], $data);
						}
					}
				}
			}
		}

		$sessionUpdate = [
			'OPERATOR_ID' => $userId,
			'WAIT_ACTION' => 'N',
			'WAIT_ANSWER' => 'N',
			'SEND_NO_ANSWER_TEXT' => 'Y'
		];
		if (
			$this->getData('DATE_OPERATOR_ANSWER') <= 0 &&
			!User::getInstance($userId)->isBot()
		)
		{
			$currentDate = new DateTime();
			$sessionUpdate['DATE_OPERATOR_ANSWER'] = $currentDate;

			$dateCreate = $this->getData('DATE_CREATE');
			if (
				!empty($dateCreate) &&
				$dateCreate instanceof DateTime
			)
			{
				$sessionUpdate['TIME_ANSWER'] = $currentDate->getTimestamp()-$dateCreate->getTimestamp();
			}
		}

		$this->update($sessionUpdate);
	}

	/**
	 * @param bool $active
	 * @return bool
	 */
	public function pause(bool $active = true): bool
	{
		$update = [
			'PAUSE' => $active? 'Y': 'N',
		];
		if ($active === true)
		{
			$update['WAIT_ACTION'] = 'N';
		}
		$this->update($update);

		Debug::addSession($this,  __METHOD__);
		$this->addEventToLog(Library::EVENT_SESSION_PAUSE);

		return true;
	}

	/**
	 * @return bool
	 */
	public function markSpam()
	{
		$this->update([
			'SPAM' => 'Y',
			'WAIT_ANSWER' => 'N',
			'DATE_MODIFY' => new DateTime(),
		]);

		Debug::addSession($this,  __METHOD__);
		$this->addEventToLog(Library::EVENT_SESSION_SPAM);

		return true;
	}

	/**
	 * End of dialog.
	 *
	 * @param bool $auto
	 * @param bool $force
	 * @param bool $hideChat
	 * @return bool
	 */
	public function finish(bool $auto = false, bool $force = false, bool $hideChat = true): bool
	{
		$result = false;

		Debug::addSession($this, __METHOD__, ['auto' => $auto, 'force' => $force, 'hideChat' => $hideChat]);
		if (!empty($this->session))
		{
			KpiManager::setSessionLastKpiMessageAnswered($this->session['ID']);

			$update = [];
			$messages = [];

			if ($force)
			{
				$this->session['CLOSED'] = 'Y';
				$update['FORCE_CLOSE'] = 'Y';
			}

			$currentDate = new DateTime();

			if ($this->session['CHAT_ID'])
			{
				$chatData = ChatTable::getById($this->session['CHAT_ID'])->fetch();
				$lastMessageId = $chatData['LAST_MESSAGE_ID'];
			}
			else
			{
				$lastMessageId = 0;
			}

			/*
			todo: Make new session config option to select that to set as date close - the last massage date either current date.
			if (
				$auto
				&& $lastMessageId > 0
			)
			{
				$messageData = MessageTable::getById($lastMessageId)->fetch();
				if ($messageData)
				{
					$currentDate = clone $messageData['DATE_CREATE'];
				}
			}
			*/

			$userViewChat = \CIMContactList::InRecent($this->session['OPERATOR_ID'], IM_MESSAGE_OPEN_LINE, $this->session['CHAT_ID']);

			if (
				$this->session['CLOSED'] === 'Y'
				|| $this->session['SPAM'] === 'Y'
				|| ($this->session['WAIT_ACTION'] === 'Y' && $this->session['WAIT_ANSWER'] === 'N')
			)
			{
				$update['WAIT_ACTION'] = 'N';
				$update['WAIT_ANSWER'] = 'N';
				$update['CLOSED'] = 'Y';

				$messages[] = [
					'TO_CHAT_ID' => $this->session['CHAT_ID'],
					'FROM_USER_ID' => $this->session['OPERATOR_ID'],
					'MESSAGE' => Loc::getMessage('IMOL_SESSION_CLOSE_FINAL'),
					'SYSTEM' => 'Y',
					'RECENT_ADD'=> $userViewChat? 'Y': 'N',
					'PARAMS' => [
						'CLASS' => 'bx-messenger-content-item-ol-end',
						'TYPE' => 'lines',
						'COMPONENT_ID' => 'bx-imopenlines-message',
						'IMOL_VOTE_SID' => $this->session['ID'],
						'IMOL_VOTE_USER' => $this->session['VOTE'],
						'IMOL_VOTE_HEAD' => $this->session['VOTE_HEAD'],
						'IMOL_COMMENT_HEAD' => htmlspecialcharsbx($this->session['COMMENT_HEAD']),
					]
				];
			}
			else
			{
				if ($this->config['ACTIVE'] === 'N')
				{
					$update['WAIT_ACTION'] = 'N';
					$update['WAIT_ANSWER'] = 'N';
					$update['CLOSED'] = 'Y';

					$messages[] = [
						'TO_CHAT_ID' => $this->session['CHAT_ID'],
						'FROM_USER_ID' => $this->session['OPERATOR_ID'],
						'RECENT_ADD' => $userViewChat? 'Y': 'N',
						'MESSAGE'=> Loc::getMessage('IMOL_SESSION_CLOSE_FINAL'),
						'SYSTEM'=> 'Y',
						'PARAMS' => [
							'CLASS' => 'bx-messenger-content-item-ol-end',
							'TYPE' => 'lines',
							'COMPONENT_ID' => 'bx-imopenlines-message',
							'IMOL_VOTE_SID' => $this->session['ID'],
							'IMOL_VOTE_USER' => $this->session['VOTE'],
							'IMOL_VOTE_HEAD' => $this->session['VOTE_HEAD'],
							'IMOL_COMMENT_HEAD' => htmlspecialcharsbx($this->session['COMMENT_HEAD']),
						]
					];
				}
				else
				{
					$enableSystemMessage = $this->isEnableSendSystemMessage();
					$waitAction = false;

					if (
						$enableSystemMessage
						&& (
							(
								$auto
								&& $this->config['AUTO_CLOSE_RULE'] === self::RULE_TEXT
							)
							|| (
								!$auto
								&& $this->config['CLOSE_RULE'] === self::RULE_TEXT
							)
						)
					)
					{
						$messageCloseText = $this->config['CLOSE_TEXT'];

						if ($auto)
						{
							$this->chat->update([
								Chat::getFieldName(Chat::FIELD_SILENT_MODE) => 'N'
							]);

							$messageCloseText = $this->config['AUTO_CLOSE_TEXT'];
						}

						$messages[] = [
							'TO_CHAT_ID' => $this->session['CHAT_ID'],
							'FROM_USER_ID' => $this->session['OPERATOR_ID'],
							'MESSAGE' => $messageCloseText,
							'RECENT_ADD' => $userViewChat? 'Y': 'N',
							'SYSTEM' => 'Y',
							'IMPORTANT_CONNECTOR' => 'Y',
							'NO_SESSION_OL' => 'Y',
							'PARAMS' => [
								'CLASS' => 'bx-messenger-content-item-ol-output',
								'IMOL_FORM' => 'history',
								'TYPE' => 'lines',
								'COMPONENT_ID' => 'bx-imopenlines-message',
							]
						];

						$update['WAIT_ACTION'] = 'Y';
						$update['WAIT_ANSWER'] = 'N';
						$waitAction = true;
					}

					if (
						(
							$enableSystemMessage === true
							|| $this->isForcedSendVote === true
						)
						&& $this->config['VOTE_MESSAGE'] === 'Y'
						&& $this->session['CHAT_ID']
						&& empty($this->session['VOTE'])
					)
					{
						$paramsDateCloseVote = '';

						if (
							!empty((int)$this->config['VOTE_TIME_LIMIT'])
							&& (int)$this->config['VOTE_TIME_LIMIT'] > 0
						)
						{
							$dateCloseVote = new DateTime();
							$dateCloseVote->add(((int)$this->config['VOTE_TIME_LIMIT']) . ' SECONDS');
							//$dateCloseVote->add('15 SECONDS');
							$update['DATE_CLOSE_VOTE'] = $dateCloseVote;

							$paramsDateCloseVote = date('c', $dateCloseVote->getTimestamp());
						}

						$messages[] = [
							'TO_CHAT_ID' => $this->session['CHAT_ID'],
							'FROM_USER_ID' => $this->session['OPERATOR_ID'],
							'MESSAGE' => $this->config['VOTE_MESSAGE_2_TEXT'],
							'SYSTEM'=> 'Y',
							'RECENT_ADD' => $userViewChat? 'Y': 'N',
							'IMPORTANT_CONNECTOR' => 'Y',
							'NO_SESSION_OL' => 'Y',
							'PARAMS' => [
								'IMOL_VOTE' => $this->session['ID'],
								'IMOL_VOTE_TEXT' => $this->config['VOTE_MESSAGE_1_TEXT'],
								'IMOL_VOTE_LIKE' => $this->config['VOTE_MESSAGE_1_LIKE'],
								'IMOL_VOTE_DISLIKE' => $this->config['VOTE_MESSAGE_1_DISLIKE'],
								'IMOL_DATE_CLOSE_VOTE' => (string)$paramsDateCloseVote,
								'IMOL_TIME_LIMIT_VOTE' => (string)$this->config['VOTE_TIME_LIMIT'],
								'CLASS' => 'bx-messenger-content-item-ol-output bx-messenger-content-item-vote',
								'IMOL_FORM' => 'like',
								'TYPE' => 'lines',
								'COMPONENT_ID' => 'bx-imopenlines-message',
							]
						];

						$update['WAIT_ACTION'] = 'Y';
						$update['WAIT_ANSWER'] = 'N';
						$update['WAIT_VOTE'] = 'Y';
						$waitAction = true;
					}

					if ($waitAction !== true)
					{
						if ($auto)
						{
							$params = [
								'CLASS' => 'bx-messenger-content-item-ol-end'
							];
							if ($this->config['VOTE_MESSAGE'] === 'Y')
							{
								$params['TYPE'] = 'lines';
								$params['COMPONENT_ID'] = 'bx-imopenlines-message';
								$params['IMOL_VOTE_SID'] = $this->session['ID'];
								$params['IMOL_VOTE_USER'] = $this->session['VOTE'];
								$params['IMOL_VOTE_HEAD'] = $this->session['VOTE_HEAD'];
								$params['IMOL_COMMENT_HEAD'] = htmlspecialcharsbx($this->session['COMMENT_HEAD']);
							}
							$messages[] = [
								'TO_CHAT_ID' => $this->session['CHAT_ID'],
								'FROM_USER_ID' => $this->session['OPERATOR_ID'],
								'RECENT_ADD' => $userViewChat? 'Y': 'N',
								'MESSAGE' => Loc::getMessage('IMOL_SESSION_CLOSE_AUTO'),
								'SYSTEM' => 'Y',
								'PARAMS' => $params
							];
						}
						else
						{
							$userSkip = User::getInstance($this->chat->getData('OPERATOR_ID'));

							$params = [
								'CLASS' => 'bx-messenger-content-item-ol-end'
							];
							if ($this->config['VOTE_MESSAGE'] === 'Y')
							{
								$params['IMOL_VOTE_SID'] = $this->session['ID'];
								$params['IMOL_VOTE_USER'] = $this->session['VOTE'];
								$params['IMOL_VOTE_HEAD'] = $this->session['VOTE_HEAD'];
								$params['IMOL_COMMENT_HEAD'] = htmlspecialcharsbx($this->session['COMMENT_HEAD']);
							}
							$messages[] = [
								'TO_CHAT_ID' => $this->session['CHAT_ID'],
								'FROM_USER_ID' => $this->session['OPERATOR_ID'],
								'RECENT_ADD' => $userViewChat? 'Y': 'N',
								'MESSAGE' => Loc::getMessage(
									'IMOL_SESSION_CLOSE_' . $userSkip->getGender(),
									['#USER#' => '[USER=' . $userSkip->getId().'][/USER]']
								),
								'SYSTEM' => 'Y',
								'PARAMS' => $params
							];
						}

						$update['WAIT_ACTION'] = 'N';
						$update['WAIT_ANSWER'] = 'N';
						$update['CLOSED'] = 'Y';
					}

					if (!$auto)
					{
						if (!User::getInstance($this->session['OPERATOR_ID'])->isBot())
						{
							$update['DATE_OPERATOR_CLOSE'] = $currentDate;
						}
						if ($this->session['DATE_CREATE'] instanceof DateTime)
						{
							$update['TIME_CLOSE'] = $currentDate->getTimestamp() - $this->session['DATE_CREATE']->getTimestamp();
						}
					}
				}

				$update['DATE_MODIFY'] = $currentDate;
			}

			if (
				isset($update['CLOSED'])
				&& $update['CLOSED'] === 'Y'
			)
			{
				if ($this->session['CRM_ACTIVITY_ID'] > 0)
				{
					$crmManager = new Crm($this);
					if ($crmManager->isLoaded())
					{
						$crmManager->setSessionClosed(['DATE_CLOSE' => $currentDate]);
					}
				}

				$update['DATE_CLOSE'] = $currentDate;
				if (
					$this->session['TIME_CLOSE'] <= 0
					&& $this->session['DATE_CREATE'] instanceof DateTime
				)
				{
					$update['TIME_CLOSE'] = $update['DATE_CLOSE']->getTimestamp() - $this->session['DATE_CREATE']->getTimestamp();
				}
				if (
					$this->session['DATE_CREATE'] instanceof DateTime
					&& $this->session['TIME_BOT'] <= 0
					&& User::getInstance($this->session['OPERATOR_ID'])->isBot()
				)
				{
					$update['TIME_BOT'] = $update['DATE_CLOSE']->getTimestamp() - $this->session['DATE_CREATE']->getTimestamp();
				}
			}

			$this->update($update);

			// send messages
			foreach ($messages as $message)
			{
				$addMessageId = Im::addMessage($message);

				if (!empty($addMessageId))
				{
					$lastMessageId = $addMessageId;
				}
			}

			if (
				isset($update['CLOSED'])
				&& $update['CLOSED'] === 'Y'
				&& $this->session['CHAT_ID']
			)
			{
				$this->update([
					'END_ID' => $lastMessageId
				]);
			}

			if ($hideChat)
			{
				Im::chatHide($this->session['CHAT_ID']);
			}

			if (!empty($this->user['USER_CODE']))
			{
				$chatEntityId =	\Bitrix\ImOpenLines\Chat::parseLinesChatEntityId($this->user['USER_CODE']);
				if (!empty($chatEntityId['connectorId']) && !empty($chatEntityId['connectorChatId']))
				{
					ImConnector\Chat::deleteLastMessage($chatEntityId['connectorChatId'], $chatEntityId['connectorId']);
				}
			}

			if (
				isset($update['CLOSED'])
				&& $update['CLOSED'] === 'Y'
			)
			{
				$eventData = [];
				$eventData['RUNTIME_SESSION'] = $this;
				$eventData['SESSION'] = $this->session;
				$eventData['CONFIG'] = $this->config;
				$event = new Main\Event('imopenlines', 'OnSessionFinish', $eventData);
				$event->send();
			}

			$result = true;
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function dismissedOperatorFinish()
	{
		if (empty($this->session))
		{
			return false;
		}

		$update = [];

		$this->session['CLOSED'] = 'Y';
		$update['FORCE_CLOSE'] = 'Y';

		if (!empty($this->session['DATE_LAST_MESSAGE']))
		{
			$closeDate = $this->session['DATE_LAST_MESSAGE']->add('60 MINUTES');
		}
		else
		{
			$closeDate = new DateTime();
		}

		if ($this->session['CHAT_ID'])
		{
			$chatData = ChatTable::getById($this->session['CHAT_ID'])->fetch();
			$lastMessageId = $chatData['LAST_MESSAGE_ID'];
		}
		else
		{
			$lastMessageId = 0;
		}

		$update['WAIT_ACTION'] = 'N';
		$update['WAIT_ANSWER'] = 'N';
		$update['WAIT_VOTE'] = 'N';
		$update['CLOSED'] = 'Y';

		if (!(
			$this->session['CLOSED'] == 'Y'
			|| $this->session['SPAM'] == 'Y'
			|| $this->session['WAIT_ACTION'] == 'Y' && $this->session['WAIT_ANSWER'] == 'N'
		))
		{
			if ($this->config['ACTIVE'] != 'N')
			{
				if (!User::getInstance($this->session['OPERATOR_ID'])->isBot())
				{
					$update['DATE_OPERATOR_CLOSE'] = $closeDate;
				}
				if ($this->session['DATE_CREATE'])
				{
					$update['TIME_CLOSE'] = $closeDate->getTimestamp()-$this->session['DATE_CREATE']->getTimestamp();
				}
			}
			$update['DATE_MODIFY'] = $closeDate;
		}

		if ($this->session['CRM_ACTIVITY_ID'] > 0)
		{
			$crmManager = new Crm($this);
			if ($crmManager->isLoaded())
			{
				$crmManager->setSessionClosed(['DATE_CLOSE' => $closeDate]);
			}
		}

		$update['DATE_CLOSE'] = $closeDate;

		if ($this->session['TIME_CLOSE'] <= 0 && $this->session['DATE_CREATE'])
		{
			$update['TIME_CLOSE'] = $update['DATE_CLOSE']->getTimestamp()-$this->session['DATE_CREATE']->getTimestamp();
		}
		if (User::getInstance($this->session['OPERATOR_ID'])->isBot() && $this->session['TIME_BOT'] <= 0 && $this->session['DATE_CREATE'])
		{
			$update['TIME_BOT'] = $update['DATE_CLOSE']->getTimestamp()-$this->session['DATE_CREATE']->getTimestamp();
		}

		if ($this->session['CHAT_ID'])
		{
			$update['END_ID'] = $lastMessageId;
		}

		Im::chatHide($this->session['CHAT_ID']);

		//The data in the chat is not specially updated in order to avoid any notifications.
		$this->update($update);

		Debug::addSession($this,  __METHOD__, ['update' => $update]);
		$this->addEventToLog(Library::EVENT_SESSION_DISMISSED_OPERATOR_FINISH, $update);

		return true;
	}

	public function getData($field = '')
	{
		if ($field)
		{
			return isset($this->session[$field])? $this->session[$field]: null;
		}
		else
		{
			return $this->session;
		}
	}

	public function getConfig($field = '')
	{
		if ($field)
		{
			return isset($this->config[$field])? $this->config[$field]: null;
		}
		else
		{
			return $this->config;
		}
	}

	public function getUser($field = '')
	{
		if ($field)
		{
			return isset($this->user[$field])? $this->user[$field]: null;
		}
		else
		{
			return $this->user;
		}
	}

	/**
	 * @param $id
	 * @param bool $waitAnswer
	 * @param bool $autoMode
	 * @return bool
	 */
	public function setOperatorId($id, $waitAnswer = false, $autoMode = false)
	{
		$this->update([
			'WAIT_ANSWER' => $waitAnswer? 'Y': 'N',
			'OPERATOR_ID' => $id
		]);

		Debug::addSession($this,  __METHOD__, ['id' => $id, 'waitAnswer' => $waitAnswer, 'autoMode' => $autoMode]);

		return true;
	}

	/**
	 * Session update.
	 *
	 * TODO: the DATE_MODIFY field serves as a trigger for automatic actions!
	 * TODO: Required express refactor the method and change the approach.
	 * TODO: Hack. Manually changing the session status @see \Bitrix\ImOpenLines\Queue\All::transferToNext imopenlines/lib/queue/all.php:211
	 *
	 * @param $fields
	 * @return bool
	 */
	public function update($fields)
	{
		$updateCheckTable = [];
		$updateChatSession = [];
		$updateDateCrmClose = [];
		if (isset($fields['CONFIG_ID']))
		{
			$configManager = new Config();
			$config = $configManager->get($fields['CONFIG_ID']);
			if ($config)
			{
				$this->config = $config;
			}
			else
			{
				unset($fields['CONFIG_ID']);
			}
		}

		if (
			array_key_exists('SEND_NO_ANSWER_TEXT', $fields)
			&& $fields['SEND_NO_ANSWER_TEXT'] === 'Y'
		)
		{
			$updateCheckTable['DATE_NO_ANSWER'] = null;
		}

		if (array_key_exists('CHECK_DATE_CLOSE', $fields))
		{
			$updateCheckTable['DATE_CLOSE'] = $fields['CHECK_DATE_CLOSE'];
			unset($fields['CHECK_DATE_CLOSE']);
		}
		elseif (
			isset($fields['DATE_MODIFY'])
			&& $fields['DATE_MODIFY'] instanceof DateTime
			&& (!isset($fields['CLOSED']) || $fields['CLOSED'] !== 'Y')
		)
		{
			$dateCrmClose = new DateTime();
			$dateCrmClose->add('1 DAY');
			$dateCrmClose->add($this->getConfig('AUTO_CLOSE_TIME').' SECONDS');

			$fullCloseTime = $this->getConfig('FULL_CLOSE_TIME');

			/** var DateTime */
			$dateClose = clone $fields['DATE_MODIFY'];

			//do not pause
			if (
				$this->session['PAUSE'] === 'N'
				|| $fields['PAUSE'] === 'N'
			)
			{
				if (
					isset($fields['USER_ID'])
					&& User::getInstance($fields['USER_ID'])->isConnector()
				)
				{
					if (
						isset($this->session['VOTE_SESSION'])
						&& $this->session['VOTE_SESSION']
						&&
						(
							(
								$this->session['WAIT_ACTION'] === 'Y'
								&& $fields['WAIT_ACTION'] !== 'N'
							)
							||
							(
								isset($fields['WAIT_ACTION'])
								&& $fields['WAIT_ACTION'] === 'Y'
							)
						)
					)
					{
						$fields['STATUS'] = self::STATUS_WAIT_CLIENT;
						if (!empty($fullCloseTime))
						{
							$dateClose->add($fullCloseTime . ' MINUTES');
						}

						$updateDateCrmClose = $dateCrmClose;
					}
					else
					{
						$dateClose->add('1 MONTH');
						$updateCheckTable['DATE_CLOSE'] = $dateClose;
						$updateChatSession['WAIT_ACTION'] = "N";
						$this->session['WAIT_ACTION'] = "N";
						$fields['WAIT_ACTION'] = "N";

						if (
							$this->session['STATUS'] >= self::STATUS_OPERATOR ||
							$this->session['STATUS'] == self::STATUS_ANSWER
						)
						{
							$updateDateCrmClose = $dateCrmClose;
						}

						if ($this->session['WAIT_ANSWER'] == 'N')
						{
							$fields['STATUS'] =
								$this->session['STATUS'] >= self::STATUS_OPERATOR
									? self::STATUS_CLIENT_AFTER_OPERATOR
									: self::STATUS_CLIENT;
						}
					}
				}
				else
				{
					if (isset($fields['SKIP_DATE_CLOSE']))
					{
						$dateClose->add('1 MONTH');
					}
					elseif (
						(
							$this->session['WAIT_ANSWER'] == 'Y'
							&& $fields['WAIT_ANSWER'] != 'N'
						)
						||
						(
							isset($fields['WAIT_ANSWER'])
							&& $fields['WAIT_ANSWER'] == 'Y'
						)
					)
					{
						$fields['STATUS'] =
							$this->session['STATUS'] >= self::STATUS_CLIENT_AFTER_OPERATOR
								? self::STATUS_CLIENT_AFTER_OPERATOR
								: self::STATUS_CLIENT;

						$dateClose->add('1 MONTH');

						if ($this->session['STATUS'] >=  self::STATUS_OPERATOR)
						{
							$updateDateCrmClose = $dateCrmClose;
						}
					}
					elseif (
						(
							$this->session['WAIT_ACTION'] === 'Y'
							&& $fields['WAIT_ACTION'] !== 'N'
						)
						||
						(
							isset($fields['WAIT_ACTION'])
							&& $fields['WAIT_ACTION'] === 'Y'
						)
					)
					{
						$fields['STATUS'] = self::STATUS_WAIT_CLIENT;
						if (!empty($fullCloseTime))
						{
							$dateClose->add($fullCloseTime . ' MINUTES');
						}

						$updateDateCrmClose = $dateCrmClose;
					}
					else
					{
						$fields['STATUS'] = self::STATUS_OPERATOR;
						$dateClose->add($this->config['AUTO_CLOSE_TIME'].' SECONDS');

						$updateDateCrmClose = $dateCrmClose;
					}
				}
			}

			//On pause
			else
			{
				$dateCrmClose->add('6 DAY'); // 6+1 = 7
				if (
					$this->session['WAIT_ACTION'] === 'N' &&
					isset($fields['USER_ID']) &&
					User::getInstance($fields['USER_ID'])->isConnector()
				)
				{
					$dateClose->add('1 WEEK');

					if (
						$this->session['STATUS'] >= self::STATUS_OPERATOR ||
						$this->session['STATUS'] == self::STATUS_ANSWER
					)
					{
						$updateDateCrmClose = $dateCrmClose;
					}

					if ($this->session['WAIT_ANSWER'] == 'N')
					{
						$fields['STATUS'] =
							$this->session['STATUS'] >= self::STATUS_OPERATOR
								? self::STATUS_CLIENT_AFTER_OPERATOR
								: self::STATUS_CLIENT;
					}
				}
				else
				{
					if (isset($fields['SKIP_DATE_CLOSE']))
					{
						$dateClose->add('1 WEEK');
					}
					elseif (
						$this->session['WAIT_ANSWER'] === 'Y' &&
						$fields['WAIT_ANSWER'] !== 'N' ||
						$fields['WAIT_ANSWER'] === 'Y'
					)
					{
						$dateClose->add('1 WEEK');

						$fields['STATUS'] =
							$this->session['STATUS'] >= self::STATUS_CLIENT_AFTER_OPERATOR
								? self::STATUS_CLIENT_AFTER_OPERATOR
								: self::STATUS_CLIENT;

						if ($this->session['STATUS'] >=  self::STATUS_OPERATOR)
						{
							$updateDateCrmClose = $dateCrmClose;
						}
					}
					elseif (
						$this->session['WAIT_ACTION'] === 'Y' &&
						$fields['WAIT_ACTION'] !== 'N' ||
						$fields['WAIT_ACTION'] === 'Y'
					)
					{
						$fields['STATUS'] = self::STATUS_WAIT_CLIENT;
						if (!empty($fullCloseTime))
						{
							$dateClose->add($fullCloseTime . ' MINUTES');
						}

						$updateDateCrmClose = $dateCrmClose;
					}
					else
					{
						$dateClose->add('1 WEEK');

						$fields['STATUS'] = self::STATUS_OPERATOR;
						$updateDateCrmClose = $dateCrmClose;
					}
				}
			}

			if ($dateClose)
			{
				$updateCheckTable['DATE_CLOSE'] = $dateClose;
			}
		}

		if (
			isset($fields['DATE_LAST_MESSAGE'])
			&& $fields['DATE_LAST_MESSAGE'] instanceof DateTime
			&& $this->session['DATE_CREATE'] instanceof DateTime
		)
		{
			$fields['TIME_DIALOG'] = $fields['DATE_LAST_MESSAGE']->getTimestamp() - $this->session['DATE_CREATE']->getTimestamp();
		}

		if (
			!isset($fields['DATE_FIRST_LAST_USER_ACTION'])
			&& isset($fields['INPUT_MESSAGE'])
			&& $fields['INPUT_MESSAGE'] === true
			&& (
				empty($this->session['DATE_FIRST_LAST_USER_ACTION'])
				|| (
					!empty($fields['STATUS'])
					&& (
						(int)$fields['STATUS'] === self::STATUS_CLIENT
						|| (int)$fields['STATUS'] === self::STATUS_CLIENT_AFTER_OPERATOR
					)
					&& (int)$this->session['STATUS'] !== self::STATUS_CLIENT
					&& (int)$this->session['STATUS'] !== self::STATUS_CLIENT_AFTER_OPERATOR
				)
			)
		)
		{
			$fields['DATE_FIRST_LAST_USER_ACTION'] = new DateTime();
		}

		//The actual closing
		if
		(
			isset($fields['CLOSED']) &&
			$fields['CLOSED'] === 'Y'
		)
		{
			if ($this->session['SPAM'] == 'Y')
			{
				$fields['STATUS'] = self::STATUS_SPAM;
				$updateChatSession['ID'] = 0;
			}
			else
			{
				$fields['STATUS'] = self::STATUS_CLOSE;
			}

			$fields['PAUSE'] = 'N';
			$updateChatSession['PAUSE'] = 'N';

			$updateCheckTable = [];

			ConfigStatistic::getInstance((int)$this->session['CONFIG_ID'])->addClosed()->deleteInWork();

			if (!isset($fields['FORCE_CLOSE']) || $fields['FORCE_CLOSE'] != 'Y')
			{
				$this->chat->close();
			}

			if (
				Connector::isLiveChat($this->session['SOURCE']) &&
				$this->session['SPAM'] !== 'Y'
			)
			{
				if (
					Loader::includeModule('im') &&
					User::getInstance($this->session['USER_ID'])->isOnline())

				{
					\CAgent::AddAgent(
						'\Bitrix\ImOpenLines\Mail::sendOperatorAnswerAgent('.$this->session['ID'].');',
						"imopenlines",
						"N",
						60,
						"",
						"Y",
						\ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL")
					);
				}
				else
				{
					\Bitrix\ImOpenLines\Mail::sendOperatorAnswer($this->session['ID']);
				}
			}

			Model\SessionCheckTable::delete($this->session['ID']);
		}

		elseif (isset($fields['PAUSE']))
		{
			if ($fields['PAUSE'] == 'Y')
			{
				$datePause = new DateTime();
				$datePause->add('1 WEEK');

				$updateCheckTable['DATE_CLOSE'] = $datePause;
				$updateCheckTable['DATE_QUEUE'] = null;
			}
		}
		elseif (isset($fields['WAIT_ANSWER']))
		{
			if ($fields['WAIT_ANSWER'] == 'Y')
			{
				$fields['STATUS'] = self::STATUS_SKIP;
				$fields['PAUSE'] = 'N';
				$updateChatSession['PAUSE'] = 'N';

				$dateQueue = new DateTime();
				//TODO: A bad place! Potential problem. Can change the distribution time logic by ignoring rules from the queue.
				$dateQueue->add($this->config['QUEUE_TIME'].' SECONDS');
				$updateCheckTable['DATE_QUEUE'] = $dateQueue;
			}
			else
			{
				if (
					$this->session['STATUS'] < self::STATUS_ANSWER &&
					$fields['STATUS'] < self::STATUS_ANSWER
				)
				{
					$fields['STATUS'] = self::STATUS_ANSWER;
				}
				$fields['WAIT_ACTION'] = isset($fields['WAIT_ACTION'])? $fields['WAIT_ACTION']: 'N';
				$fields['PAUSE'] = 'N';
				$updateChatSession['WAIT_ACTION'] = $fields['WAIT_ACTION'];
				$updateChatSession['PAUSE'] = 'N';

				$updateCheckTable['DATE_QUEUE'] = null;
			}
		}

		//If the status is lower than STATUS_WAIT_CLIENT, the dialog cannot be in the evaluation waiting state
		if (
			isset($fields['STATUS']) &&
			$fields['STATUS'] < self::STATUS_WAIT_CLIENT &&
			!array_key_exists('WAIT_VOTE', $fields) &&
			$this->session['WAIT_VOTE'] === 'Y'
		)
		{
			$fields['WAIT_VOTE'] = 'N';
		}

		//If no more evaluation is expected, DATE_CLOSE_VOTE should be reset
		if (
			array_key_exists('WAIT_VOTE', $fields) &&
			$fields['WAIT_VOTE'] !== 'Y' &&
			!array_key_exists('DATE_CLOSE_VOTE', $fields) &&
			$this->session['DATE_CLOSE_VOTE'] !== null
		)
		{
			$fields['DATE_CLOSE_VOTE'] = null;
		}

		if (
			Connector::isLiveChat($this->session['SOURCE']) &&
			array_key_exists('DATE_CLOSE_VOTE', $fields)
		)
		{
			\Bitrix\Pull\Event::add($this->session['USER_ID'], [
				'module_id' => 'imopenlines',
				'command' => 'sessionDateCloseVote',
				'params' => [
					'sessionId' => (int)$this->session['ID'],
					'dateCloseVote' => (!empty($fields['DATE_CLOSE_VOTE']) && $fields['DATE_CLOSE_VOTE'] instanceof DateTime) ? date('c', $fields['DATE_CLOSE_VOTE']->getTimestamp()): '',
				]
			]);
		}

		if (!empty($updateChatSession))
		{
			$this->chat->updateFieldData([Chat::FIELD_SESSION => $updateChatSession]);
		}

		if (isset($fields['MESSAGE_COUNT']))
		{
			$fields["MESSAGE_COUNT"] = new SqlExpression("?# + 1", "MESSAGE_COUNT");
			ConfigStatistic::getInstance((int)$this->session['CONFIG_ID'])->addMessage();
		}

		if (!empty($updateCheckTable))
		{
			if (
				isset($updateCheckTable['DATE_CLOSE'])
				&& $this->session['CRM_ACTIVITY_ID'] > 0
				&& (
					!isset($fields['CLOSED']) ||
					$fields['CLOSED'] === 'N'
				)
			)
			{
				if (
					(
						$this->session['STATUS'] >= self::STATUS_ANSWER
						&& !in_array($this->session['STATUS'], [self::STATUS_CLIENT, self::STATUS_CLIENT_AFTER_OPERATOR])
					)
					||
					(
						$fields['STATUS'] >= self::STATUS_ANSWER
						&& !in_array($fields['STATUS'], [self::STATUS_CLIENT, self::STATUS_CLIENT_AFTER_OPERATOR])
					)
				)
				{
					if ($updateCheckTable['DATE_CLOSE'])
					{
						$dateCheckClose = clone $updateCheckTable['DATE_CLOSE'];
					}
					else
					{
						$dateCheckClose = new Main\Type\DateTime();
					}
					$dateCheckClose->add($this->getConfig('AUTO_CLOSE_TIME').' SECONDS');
					$dateCheckClose->add('1 DAY');

					$crmManager = new Crm($this);
					if ($crmManager->isLoaded())
					{
						$crmManager->setSessionDataClose($dateCheckClose);
					}
				}
			}

			Model\SessionCheckTable::update($this->session['ID'], $updateCheckTable);
		}

		if (
			!empty($updateDateCrmClose) &&
			$this->session['CRM_ACTIVITY_ID']
		)
		{
			$crmManager = new Crm($this);
			if ($crmManager->isLoaded())
			{
				$crmManager->setSessionDataClose($updateDateCrmClose);
			}
		}

		if (
			isset($fields['SKIP_CHANGE_STATUS'])
			&& $fields['SKIP_CHANGE_STATUS'] === true
			&& isset($fields['STATUS'])
		)
		{
			unset($fields['STATUS']);
		}

		unset(
			$fields['USER_ID'],
			$fields['SKIP_DATE_CLOSE'],
			$fields['SKIP_CHANGE_STATUS'],
			$fields['FORCE_CLOSE'],
			$fields['INPUT_MESSAGE']
		);

		$isResponseOperator = null;

		if (
			!empty($fields['STATUS'])
			&& $this->session['STATUS'] != $fields['STATUS']
		)
		{
			$this->chat->updateSessionStatus($fields['STATUS']);

			if (
				(int)$this->session['STATUS'] !== self::STATUS_OPERATOR
				&& (int)$fields['STATUS'] === self::STATUS_OPERATOR
			)
			{
				$isResponseOperator = true;
			}
			elseif (
				(int)$this->session['STATUS'] === self::STATUS_OPERATOR
				&& (int)$fields['STATUS'] !== self::STATUS_OPERATOR
			)
			{
				$isResponseOperator = false;
			}

			$sessionClose = false;
			if (
				isset($fields['CLOSED'])
				&& $fields['CLOSED'] === 'Y'
			)
			{
				$sessionClose = true;
			}

			if (
				!empty($this->session['SOURCE'])
				&& Connector::isLiveChat($this->session['SOURCE'])
				&& !empty($this->session['USER_CODE'])
			)
			{
				$chatEntityId = \Bitrix\ImOpenLines\Chat::parseLinesChatEntityId($this->session['USER_CODE']);
				if (!empty($chatEntityId['connectorChatId']))
				{
					\Bitrix\Pull\Event::add($this->session['USER_ID'], [
						'module_id' => 'imopenlines',
						'command' => 'sessionStatus',
						'params' => [
							'chatId' => (int)$chatEntityId['connectorChatId'],
							'sessionId' => (int)$this->session['ID'],
							'sessionStatus' => (int)$fields['STATUS'],
							'spam' => $this->session['SPAM'] == 'Y',
							'sessionClose' => $sessionClose
						]
					]);
				}
			}
		}

		foreach ($fields as $key => $value)
		{
			$this->session[$key] = $value;
		}
		foreach ($updateCheckTable as $key => $value)
		{
			$this->session['CHECK_'.$key] = $value;
		}

		if (
			$this->session['STATUS'] < self::STATUS_CLOSE
			&& $this->session['CLOSED'] === 'Y'
		)
		{
			$this->session['STATUS'] = self::STATUS_CLOSE;
			$fields['STATUS'] = self::STATUS_CLOSE;
		}

		if (
			$this->session['ID']
			&& !empty($fields)
		)
		{
			Model\SessionTable::update($this->session['ID'], $fields);
		}

		if ($isResponseOperator !== null)
		{
			$automaticActionMessages = new AutomaticAction\Messages($this);

			if ($isResponseOperator === true)
			{
				$automaticActionMessages->setStatusResponseOperator();
			}
			elseif($isResponseOperator === false)
			{
				$automaticActionMessages->setStatusNotResponseOperator();
			}
		}

		Debug::addSession($this,  __METHOD__, [
			'fields' => $fields,
			'updateCheckTable' => $updateCheckTable,
			'updateChatSession' => $updateChatSession,
			'updateDateCrmClose' => $updateDateCrmClose
		]);

		return true;
	}

	/**
	 * @param $params
	 */
	public function execAutoAction($params): void
	{
		if (
			$this->action === self::ACTION_CLOSED
			&& $this->config['ACTIVE'] === 'N'
		)
		{
			Im::addMessage([
				'TO_CHAT_ID' => $this->session['CHAT_ID'],
				'MESSAGE' => Loc::getMessage('IMOL_SESSION_LINE_IS_CLOSED'),
				'SYSTEM' => 'Y',
			]);
		}

		if (
			$this->config['AGREEMENT_MESSAGE'] === 'Y'
			&& $this->chat->isNowCreated()
			&& $this->isEnableSendSystemMessage()
		)
		{
			$addAgreementMessage = true;
			if (Connector::isLiveChat($this->session['SOURCE']))
			{
				$parsedUserCode = Session\Common::parseUserCode($this->session['USER_CODE']);
				$addAgreementMessage = !Consent::getByContext(
					(int)$this->config['AGREEMENT_ID'],
					'imopenlines/livechat',
					$parsedUserCode['EXTERNAL_CHAT_ID']
				);
			}

			if ($addAgreementMessage)
			{
				$mess = Loc::loadLanguageFile(__FILE__, $this->config['LANGUAGE_ID']);
				Im::addMessage([
					'TO_CHAT_ID' => $this->session['CHAT_ID'],
					'MESSAGE' => str_replace(
						['#LINK_START#', '#LINK_END#'],
						['[URL=' . Common::getAgreementLink($this->config['AGREEMENT_ID'], $this->config['LANGUAGE_ID']) . ']', '[/URL]'],
						$mess['IMOL_SESSION_AGREEMENT_MESSAGE']
					),
					'SYSTEM' => 'Y',
					'IMPORTANT_CONNECTOR' => 'Y',
					'NO_SESSION_OL' => 'Y',
					'PARAMS' => [
						'CLASS'=> 'bx-messenger-content-item-ol-output',
					]
				]);
				Im::addMessage([
					'TO_CHAT_ID' => $this->session['CHAT_ID'],
					'MESSAGE' => Loc::getMessage('IMOL_SESSION_AGREEMENT_MESSAGE_OPERATOR'),
					'SYSTEM' => 'Y',
					'PARAMS' => [
						'CLASS' => 'bx-messenger-content-item-ol-attention',
					]
				]);
			}
		}
	}

	/**
	 * Transfer session to the next operator in the queue.
	 *
	 * @param bool $manual
	 * @return bool
	 */
	public function transferToNextInQueue($manual = true)
	{
		$result = false;

		Debug::addSession($this,  __METHOD__, ['manual' => $manual]);

		$queueManager = Queue::initialization($this);

		if ($queueManager)
		{
			$result = $queueManager->transferToNext($manual);
		}

		return $result;
	}

	/**
	 * Send notification about unavailability of the operator.
	 *
	 * @return bool
	 */
	public function sendMessageNoAnswer()
	{
		$result = false;

		if ($this->config['NO_ANSWER_RULE'] == Session::RULE_TEXT && $this->session['SEND_NO_ANSWER_TEXT'] != 'Y' && $this->isEnableSendSystemMessage())
		{
			Im::addMessage([
				"TO_CHAT_ID" => $this->session['CHAT_ID'],
				"MESSAGE" => $this->config['NO_ANSWER_TEXT'],
				"SYSTEM" => 'Y',
				"IMPORTANT_CONNECTOR" => 'Y',
				"NO_SESSION_OL" => 'Y',
				"PARAMS" => [
					"CLASS" => "bx-messenger-content-item-ol-output",
					"IMOL_FORM" => "offline",
					"TYPE" => "lines",
					"COMPONENT_ID" => "bx-imopenlines-message",
				]
			]);
		}

		if ($this->session['SEND_NO_ANSWER_TEXT'] != 'Y')
		{
			$this->update(['SEND_NO_ANSWER_TEXT' => 'Y', 'WAIT_ACTION' => 'Y']);
		}
		else
		{
			$this->update(['SEND_NO_ANSWER_TEXT' => 'Y']);
		}

		Debug::addSession($this,  __METHOD__, ['result' => $result]);

		return $result;
	}

	/**
	 * @param int $idTask
	 * @param int $idConfigTask
	 * @param array|bool $configTask
	 * @return Result
	 */
	public function sendAutomaticMessage($idTask, $idConfigTask, $configTask = []): Result
	{
		$result = new Result();

		if (empty($configTask))
		{
			$configTask = ConfigAutomaticMessagesTable::getByPrimary($idConfigTask)->fetch();
		}

		if (
			$this->session['CLOSED'] !== 'Y'
			&& $this->session['PAUSE'] !== 'Y'
			&& $this->session['STATUS'] < self::STATUS_WAIT_CLIENT
			&& !empty($configTask)
			&& $configTask['ACTIVE'] === 'Y'
			&& Loader::includeModule('imconnector')
			&& $this->isEnableSendSystemMessage()
		)
		{
			$operatorKeyboard = new \Bitrix\Im\Bot\Keyboard();

			$connectorKeyboard = [];

			if (!empty($configTask['TEXT_BUTTON_CLOSE']))
			{
				$bottomColor = '#86AE1E';
				$textColor = '#fff';

				$operatorKeyboard->addButton([
					'TEXT' => $configTask['TEXT_BUTTON_CLOSE'],
					'ACTION' => 'SEND',
					'ACTION_VALUE' => $configTask['LONG_TEXT_BUTTON_CLOSE'],
					'BG_COLOR' => $bottomColor,
					'TEXT_COLOR' => $textColor,
					'DISPLAY' => 'LINE',
					'DISABLED' => 'Y'
				]);

				$connectorKeyboard[] = [
					'TEXT_BUTTON' => $configTask['TEXT_BUTTON_CLOSE'],
					'LONG_TEXT' => $configTask['LONG_TEXT_BUTTON_CLOSE'],
					'BOTTOM_COLOR' => $bottomColor,
					'TEXT_COLOR' => $textColor,
					'DISPLAY' => 'LINE',
					'COMMAND' => \Bitrix\ImConnector\InteractiveMessage\Input::COMMAND_SESSION,
					'COMMAND_PARAMS' => [
						'COMMAND' => \Bitrix\ImConnector\InteractiveMessage\Input::COMMAND_SESSION_CLOSE,
						'SESSION_ID' => $this->session['ID'],
						'CHAT_ID' => $this->session['CHAT_ID'],
						'TASK_ID' => $idTask,
						'CONFIG_TASK_ID' => $idConfigTask,
					],
				];
			}

			if (!empty($configTask['TEXT_BUTTON_CONTINUE']))
			{
				$bottomColor = '#EE322D';
				$textColor = '#fff';

				$operatorKeyboard->addButton([
					'TEXT' => $configTask['TEXT_BUTTON_CONTINUE'],
					'ACTION' => 'SEND',
					'ACTION_VALUE' => $configTask['LONG_TEXT_BUTTON_CONTINUE'],
					'BG_COLOR' => $bottomColor,
					'TEXT_COLOR' => $textColor,
					'DISPLAY' => 'LINE',
					'DISABLED' => 'Y'
				]);

				$connectorKeyboard[] = [
					'TEXT_BUTTON' => $configTask['TEXT_BUTTON_CONTINUE'],
					'LONG_TEXT' => $configTask['LONG_TEXT_BUTTON_CONTINUE'],
					'BOTTOM_COLOR' => $bottomColor,
					'TEXT_COLOR' => $textColor,
					'DISPLAY' => 'LINE',
					'COMMAND' => \Bitrix\ImConnector\InteractiveMessage\Input::COMMAND_SESSION,
					'COMMAND_PARAMS' => [
						'COMMAND' => \Bitrix\ImConnector\InteractiveMessage\Input::COMMAND_SESSION_CONTINUE,
						'SESSION_ID' => $this->session['ID'],
						'CHAT_ID' => $this->session['CHAT_ID'],
						'TASK_ID' => $idTask,
						'CONFIG_TASK_ID' => $idConfigTask,
					],
				];
			}

			if (!empty($configTask['TEXT_BUTTON_NEW']))
			{
				$bottomColor = '#0CA7D9';
				$textColor = '#fff';

				$operatorKeyboard->addButton([
					'TEXT' => $configTask['TEXT_BUTTON_NEW'],
					'ACTION' => 'SEND',
					'ACTION_VALUE' => $configTask['LONG_TEXT_BUTTON_NEW'],
					'BG_COLOR' => $bottomColor,
					'TEXT_COLOR' => $textColor,
					'DISPLAY' => 'LINE',
					'DISABLED' => 'Y'
				]);

				$connectorKeyboard[] = [
					'TEXT_BUTTON' => $configTask['TEXT_BUTTON_NEW'],
					'LONG_TEXT' => $configTask['LONG_TEXT_BUTTON_NEW'],
					'BOTTOM_COLOR' => $bottomColor,
					'TEXT_COLOR' => $textColor,
					'DISPLAY' => 'LINE',
					'COMMAND' => \Bitrix\ImConnector\InteractiveMessage\Input::COMMAND_SESSION,
					'COMMAND_PARAMS' => [
						'COMMAND' => \Bitrix\ImConnector\InteractiveMessage\Input::COMMAND_SESSION_NEW,
						'SESSION_ID' => $this->session['ID'],
						'CHAT_ID' => $this->session['CHAT_ID'],
						'TASK_ID' => $idTask,
						'CONFIG_TASK_ID' => $idConfigTask,
					],
				];
			}
			$chatEntityId = Chat::parseLinesChatEntityId($this->chat->getData('ENTITY_ID'));
			$connectorId = $chatEntityId['connectorId'] ?? '';
			InteractiveMessage\Output::getInstance($this->session['CHAT_ID'], ['connectorId' => $connectorId])->setKeyboardData($connectorKeyboard);

			Im::addMessage([
				'TO_CHAT_ID' => $this->session['CHAT_ID'],
				'MESSAGE' => $configTask['MESSAGE'],
				'SYSTEM' => 'Y',
				'IMPORTANT_CONNECTOR' => 'Y',
				'NO_SESSION_OL' => 'Y',
				'PARAMS' => [
					'CLASS' => 'bx-messenger-content-item-ol-output',
					'IMOL_FORM' => 'offline',
					'TYPE' => 'lines',
					'COMPONENT_ID' => 'bx-imopenlines-form-offline',
				],
				'KEYBOARD' => $operatorKeyboard,
			]);
		}

		$resultDelete = SessionAutomaticTasksTable::delete($idTask);
		if (!$resultDelete->isSuccess())
		{
			$result->addErrors($resultDelete->getErrors());
		}

		Debug::addSession($this,  __METHOD__, ['result' => $result]);

		return $result;
	}

	private static function prolongDueChatActivity($chatId)
	{
		$orm = Model\SessionTable::getList([
			'select' => [
				'ID',
				'CHECK_DATE_CLOSE' => 'CHECK.DATE_CLOSE'
			],
			'filter' => [
				'=CHAT_ID' => $chatId,
				'=CLOSED' => 'N'
			]
		]);

		if ($result = $orm->fetch())
		{
			$currentDate = new DateTime();
			if ($result['CHECK_DATE_CLOSE'] && $currentDate->getTimestamp()+600 > $result['CHECK_DATE_CLOSE']->getTimestamp())
			{
				$dateClose = $result['CHECK_DATE_CLOSE']->add('10 MINUTES');
				Model\SessionCheckTable::update($result['ID'], [
					'DATE_CLOSE' => $dateClose
				]);
			}
		}
	}

	public static function setQueueFlagCache($type = "")
	{
		if (!$type)
		{
			return false;
		}

		$app = Application::getInstance();
		$managedCache = $app->getManagedCache();
		$managedCache->clean("imol_queue_flag_".$type);
		$managedCache->read(86400*30, "imol_queue_flag_".$type);
		$managedCache->set("imol_queue_flag_".$type, true);

		return true;
	}

	public static function deleteQueueFlagCache($type = "")
	{
		$app = Application::getInstance();
		$managedCache = $app->getManagedCache();
		if ($type)
		{
			$managedCache->clean("imol_queue_flag_".$type);
		}
		else
		{
			$managedCache->clean("imol_queue_flag_".self::CACHE_CLOSE);
			$managedCache->clean("imol_queue_flag_".self::CACHE_QUEUE);
			$managedCache->clean("imol_queue_flag_".self::CACHE_INIT);
			$managedCache->clean("imol_queue_flag_".self::CACHE_MAIL);
			$managedCache->clean("imol_queue_flag_".self::CACHE_NO_ANSWER);
		}

		return true;
	}

	/**
	 * @param string $type
	 * @return bool
	 */
	public static function getQueueFlagCache($type = "")
	{
		if (!$type)
		{
			return false;
		}

		$app = Application::getInstance();
		$managedCache = $app->getManagedCache();
		if ($result = $managedCache->read(86400*30, "imol_queue_flag_".$type))
		{
			$result = $managedCache->get("imol_queue_flag_".$type) === false? false: true;
		}
		return $result;
	}

	/**
	 * @return Chat
	 */
	public function getChat()
	{
		return $this->chat;
	}

	/**
	 * @param Chat $chat
	 */
	public function setChat(Chat $chat): self
	{
		$this->chat = $chat;

		return $this;
	}

	public function getAction()
	{
		return $this->action;
	}

	/**
	 * Remove all signs of automatic actions.
	 *
	 * @return bool
	 */
	public function resetActionAll(): bool
	{
		$this->action = self::ACTION_NONE;

		return true;
	}

	/**
	 * To add users to the chat.
	 *
	 * @return bool
	 */
	public function joinUser()
	{
		Debug::addSession($this,  __METHOD__, ['joinUserList' => $this->joinUserList]);

		if (!empty($this->joinUserList))
		{
			$operatorFromCrm = false;
			if ($this->isNowCreated())
			{
				$operatorFromCrm = $this->session['OPERATOR_FROM_CRM'] == 'Y'? true : false;
			}
			$this->chat->sendJoinMessage($this->joinUserList, $operatorFromCrm);
			$this->chat->join($this->joinUserList);
		}

		return true;
	}

	public function isNowCreated()
	{
		return $this->isCreated;
	}

	/**
	 * @param $fields
	 * @return bool
	 */
	public function updateCrmFlags($fields)
	{
		$result = false;
		$updateFields = [];

		if (
			(isset($fields['CRM_CREATE_LEAD']) && $fields['CRM_CREATE_LEAD'] == 'Y') ||
			(isset($fields['CRM_CREATE_COMPANY']) && $fields['CRM_CREATE_COMPANY'] == 'Y') ||
			(isset($fields['CRM_CREATE_CONTACT']) && $fields['CRM_CREATE_CONTACT'] == 'Y') ||
			(isset($fields['CRM_CREATE_DEAL']) && $fields['CRM_CREATE_DEAL'] == 'Y') ||
			(isset($fields['CRM_ACTIVITY_ID']) && !empty($fields['CRM_ACTIVITY_ID']))
		)
		{
			$updateFields['CRM'] = 'Y';
			$updateFields['CRM_CREATE'] = 'Y';
		}
		elseif((isset($fields['CRM_ACTIVITY_ID']) && !empty($fields['CRM_ACTIVITY_ID'])))
		{
			$updateFields['CRM'] = 'Y';
		}
		else
		{
			if (isset($fields['CRM_CREATE']))
			{
				if ($fields['CRM_CREATE'] == 'Y')
				{
					$updateFields['CRM_CREATE'] = 'Y';
					$updateFields['CRM'] = 'Y';
				}
				else
				{
					$updateFields['CRM_CREATE'] = 'N';

					if (isset($fields['CRM']))
					{
						if ($fields['CRM'] == 'Y')
						{
							$updateFields['CRM'] = 'Y';
						}
						else
						{
							$updateFields['CRM'] = 'N';
						}
					}
				}
			}
		}

		if (isset($fields['CRM_CREATE_LEAD']))
		{
			if ($fields['CRM_CREATE_LEAD'] == 'Y')
			{
				$updateFields['CRM_CREATE_LEAD'] = 'Y';
			}
			else
			{
				$updateFields['CRM_CREATE_LEAD'] = 'N';
			}
		}

		if (isset($fields['CRM_CREATE_COMPANY']))
		{
			if ($fields['CRM_CREATE_COMPANY'] == 'Y')
			{
				$updateFields['CRM_CREATE_COMPANY'] = 'Y';
			}
			else
			{
				$updateFields['CRM_CREATE_COMPANY'] = 'N';
			}
		}

		if (isset($fields['CRM_CREATE_CONTACT']))
		{
			if ($fields['CRM_CREATE_CONTACT'] == 'Y')
			{
				$updateFields['CRM_CREATE_CONTACT'] = 'Y';
			}
			else
			{
				$updateFields['CRM_CREATE_CONTACT'] = 'N';
			}
		}

		if (isset($fields['CRM_CREATE_DEAL']))
		{
			if ($fields['CRM_CREATE_DEAL'] == 'Y')
			{
				$updateFields['CRM_CREATE_DEAL'] = 'Y';
			}
			else
			{
				$updateFields['CRM_CREATE_DEAL'] = 'N';
			}
		}

		if (isset($fields['CRM_ACTIVITY_ID']))
		{
			$updateFields['CRM_ACTIVITY_ID'] = $fields['CRM_ACTIVITY_ID'];
		}

		if (!empty($updateFields))
		{
			foreach ($updateFields as $cell=>$field)
			{
				if ($this->getData($cell) == $field)
				{
					unset($updateFields[$cell]);
				}
			}
		}

		if (!empty($updateFields))
		{
			$updateFields['SKIP_CHANGE_STATUS'] = true;
			$result = $this->update($updateFields);
		}

		Debug::addSession($this,  __METHOD__, ['updateFields' => $updateFields, 'fields' => $fields]);

		return $result;
	}

	/**
	 * The vote of the user via a special form.
	 *
	 * @param $sessionId
	 * @param $action
	 * @param $userId
	 * @return bool
	 */
	public static function voteAsUser($sessionId, $action, $userId = null): bool
	{
		$result = false;

		$finishSession = false;

		$sessionData = Model\SessionTable::getByIdPerformance($sessionId)->fetch();
		if ($sessionData)
		{
			$userId = (int)$userId;
			if (
				$userId <= 0
				|| $sessionData['USER_ID'] == $userId
			)
			{
				$voteValue = $action === 'dislike'? 1: 5;

				$session = new Session();

				$resultLoadSession = $session->load([
					'USER_CODE' => $sessionData['USER_CODE'],
					'SKIP_CREATE' => 'Y',
					'DEFERRED_JOIN' => 'Y',
					'VOTE_SESSION' => 'Y'
				]);

				if (!$resultLoadSession)
				{
					if ($session->isCloseVote())
					{
						Im::addCloseVoteMessage(
							$session->getData('CHAT_ID'),
							$session->getConfig('VOTE_TIME_LIMIT')
						);
					}
				}
				else
				{

					Debug::addSession($session,  __METHOD__, ['sessionId' => $sessionId, 'action' => $action, 'userId' => $userId]);

					if ($session->getData('ID') == $sessionId)
					{
						if (
							$session->getData('CLOSED') !== 'Y'
							|| $session->getData('VOTE') !== ''
						)
						{
							$updateSession['VOTE'] = $voteValue;
							$updateSession['WAIT_VOTE'] = 'N';

							if (
								$session->getData('WAIT_VOTE') === 'Y'
								&& $session->getConfig('VOTE_CLOSING_DELAY') === 'Y'
							)
							{
								$finishSession = true;
							}
							else
							{
								//TODO: hack!
								$updateSession['STATUS'] = self::STATUS_WAIT_CLIENT;
								$updateSession['DATE_MODIFY'] = new DateTime;
								$updateSession['USER_ID'] = $userId;
							}

							$session->update($updateSession);

							$voteEventParams = [
								'SESSION_DATA' => $sessionData,
								'VOTE' => $voteValue,
							];
							$event = new Main\Event('imopenlines', 'OnSessionVote', $voteEventParams);
							$event->send();

							if ($sessionData['END_ID'] > 0)
							{
								\CIMMessageParam::Set($sessionData['END_ID'], ['IMOL_VOTE_SID' => $sessionId, 'IMOL_VOTE_USER' => $voteValue]);
								\CIMMessageParam::SendPull($sessionData['END_ID'], ['IMOL_VOTE_SID', 'IMOL_VOTE_USER']);
							}

							Chat::sendRatingNotify(
								Chat::RATING_TYPE_CLIENT,
								$sessionData['ID'],
								$voteValue,
								$sessionData['OPERATOR_ID'],
								$sessionData['USER_ID']
							);

							if ($finishSession === true)
							{
								$session->finish(true);
							}

							$result =  true;
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param $sessionId
	 * @param null $voteValue
	 * @param null $commentValue
	 * @param null $userId
	 * @return bool
	 */
	public static function voteAsHead($sessionId, $voteValue = null, $commentValue = null, $userId = null)
	{
		$result = false;

		$sessionData = Model\SessionTable::getByIdPerformance($sessionId)->fetch();

		if ($sessionData)
		{
			$userId = (int)$userId;
			if ($userId > 0)
			{
				$configManager = new \Bitrix\ImOpenLines\Config();
				$resultPermissions = $configManager->canVoteAsHead($sessionData['CONFIG_ID']);
			}
			else
			{
				$resultPermissions = true;
				$userId = $GLOBALS['USER']->GetId();
			}

			if ($resultPermissions)
			{
				$fieldsUpdate = [];

				$pullMessage = [
					'module_id' => 'imopenlines',
					'command' => 'voteHead',
					'expiry' => 60,
					'params' => [
						'sessionId' => $sessionId,
					],
				];

				if (!empty($commentValue))
				{
					if (mb_strlen($commentValue) > 10000)
					{
						$commentValue = mb_substr($commentValue, 0, 10000).'...';
					}

					$commentValueSafely = htmlspecialcharsbx($commentValue);
				}

				if ($voteValue !== null && $commentValue !== null)
				{
					$voteValue = $voteValue == 1 || $voteValue <= 5? $voteValue: 0;

					$fieldsUpdate['VOTE_HEAD'] = $voteValue;

					$fieldsUpdate['COMMENT_HEAD'] = $commentValue;

					if ($voteValue > 0)
					{
						\Bitrix\ImOpenLines\Chat::sendRatingNotify(
							\Bitrix\ImOpenLines\Chat::RATING_TYPE_HEAD_AND_COMMENT,
							$sessionData['ID'],
							['vote' => $voteValue, 'comment' => $commentValue],
							$sessionData['OPERATOR_ID'],
							$userId
						);
					}
					else
					{
						\Bitrix\ImOpenLines\Chat::sendRatingNotify(
							\Bitrix\ImOpenLines\Chat::RATING_TYPE_COMMENT,
							$sessionData['ID'],
							$commentValue,
							$sessionData['OPERATOR_ID'],
							$userId
						);
					}

					$result = true;
				}
				elseif($voteValue !== null)
				{
					$voteValue = $voteValue == 1 || $voteValue <= 5? $voteValue: 0;

					$fieldsUpdate['VOTE_HEAD'] = $voteValue;

					if ($voteValue > 0)
					{
						\Bitrix\ImOpenLines\Chat::sendRatingNotify(
							\Bitrix\ImOpenLines\Chat::RATING_TYPE_HEAD,
							$sessionData['ID'],
							$voteValue,
							$sessionData['OPERATOR_ID'],
							$userId
						);
					}

					$result = true;
				}
				elseif($commentValue !== null)
				{
					$fieldsUpdate['COMMENT_HEAD'] = $commentValue;

					\Bitrix\ImOpenLines\Chat::sendRatingNotify(
						\Bitrix\ImOpenLines\Chat::RATING_TYPE_COMMENT,
						$sessionData['ID'],
						$commentValue,
						$sessionData['OPERATOR_ID'],
						$userId
					);

					$result = true;
				}

				if (!empty($fieldsUpdate))
				{
					Model\SessionTable::update($sessionId, $fieldsUpdate);
				}

				if (Loader::includeModule("pull") && ($voteValue !==null || $commentValue !==null))
				{
					$paramsPull = [
						'IMOL_VOTE_SID' => $sessionData['ID']
					];

					if ($voteValue !==null)
					{
						$pullMessage['params']['voteValue'] = $voteValue;
						$paramsPull['IMOL_VOTE_HEAD'] = $voteValue;
					}

					if ($commentValue !==null)
					{
						$pullMessage['params']['commentValue'] = $commentValueSafely;
						$paramsPull['IMOL_COMMENT_HEAD']['text'] = $commentValueSafely;
					}

					$pullUsers = \CIMChat::GetRelationById($sessionData['CHAT_ID'], false, true, false);
					$pullUsers[] = $userId;

					\Bitrix\Pull\Event::add($pullUsers, $pullMessage);

					if ($voteValue !==null)
					{
						$pullMessage['skip_users'] = $pullUsers;
						unset($pullMessage['params']['commentValue']);
						\CPullWatch::AddToStack('IMOL_STATISTICS', $pullMessage);
					}

					if ($sessionData['END_ID'] > 0)
					{
						\CIMMessageParam::Set($sessionData['END_ID'], $paramsPull);
						\CIMMessageParam::SendPull($sessionData['END_ID'], array_keys($paramsPull));
					}
				}
			}
		}

		return $result;
	}

	public static function setLastSendMailId(array $session, ?int $lastSendMailId = null): void
	{
		$chatId = (int)Chat::parseLinesChatEntityId($session['USER_CODE'])['connectorChatId'];
		$sessionId = (int)$session['ID'];
		$lastSendMailId ??= self::getLastMessageId($chatId);
		$sql = "UPDATE b_imopenlines_session 
				SET LAST_SEND_MAIL_ID = (case when LAST_SEND_MAIL_ID > {$lastSendMailId} then LAST_SEND_MAIL_ID else {$lastSendMailId} end)
				WHERE ID = {$sessionId}";
		Application::getConnection()->query($sql);
	}

	private static function getLastMessageId(int $chatId): int
	{
		Loader::includeModule('im');
		return (new ReadService())->getLastMessageIdInChat($chatId);
	}

	/**
	 * Closed sessions, which for some reason remained open in the presence of an actual and active session.
	 *
	 * @param $duplicateSession
	 * @param $actualSession
	 * @return Result
	 */
	protected static function closeDuplicate($duplicateSession, $actualSession)
	{
		$result = new Result();

		$resultSessionUpdate = Model\SessionTable::update($duplicateSession['ID'], [
			'STATUS' => self::STATUS_DUPLICATE,
			'WAIT_ANSWER' => 'N',
			'CLOSED' => 'Y'
		]);

		if (!$resultSessionUpdate->isSuccess())
		{
			$result->addErrors($resultSessionUpdate->getErrors());
		}

		$resultSessionCheckDelete = Model\SessionCheckTable::delete($duplicateSession['ID']);

		if (!$resultSessionCheckDelete->isSuccess())
		{
			$result->addErrors($resultSessionCheckDelete->getErrors());
		}

		if (
			$actualSession['CHAT_ID'] != $duplicateSession['CHAT_ID']
			|| $actualSession['OPERATOR_ID']
			&& $duplicateSession['OPERATOR_ID']
			&& $actualSession['OPERATOR_ID'] != $duplicateSession['OPERATOR_ID']
		)
		{
			$chatManager = new Chat($duplicateSession['CHAT_ID']);
			$chatManager->leave($duplicateSession['OPERATOR_ID']);
		}

		//statistics
		ConfigStatistic::getInstance((int)$duplicateSession['CONFIG_ID'])->addClosed()->deleteInWork();

		return $result;
	}

	/**
	 * Checks that message is first operator message in current session
	 *
	 * @param $messageId
	 * @return bool
	 */
	public function isFirstOperatorMessage($messageId)
	{
		$message = MessageTable::getList(
			[
				'select' => ['ID'],
				'order' => ['ID' => 'ASC'],
				'filter' => [
					'CHAT.ENTITY_ID' => $this->getData('USER_CODE'),
					'CHAT.ENTITY_TYPE' => 'LINES',
					'AUTHOR_ID' => $this->getData('OPERATOR_ID'),
					'>ID' => $this->getData('START_ID')
				]
			]
		)->fetch();

		return $message['ID'] == $messageId;
	}

	/**
	 * Add new event item to event log table.
	 *
	 * @param string $eventType
	 * @param Main\Result|array|null $result
	 *
	 * @return Main\ORM\Data\AddResult|Main\Result|mixed
	 */
	private function addEventToLog($eventType, $result = null)
	{
		if (is_null($result))
		{
			$result = new Result();
		}
		elseif (!($result instanceof Result))
		{
			$resultData = $result;
			$result = new Result();
			$result->setData($resultData);
		}

		$resultData = [];
		$fieldData = $result->getData();
		if (is_array($fieldData) && !empty($fieldData))
		{
			$resultData = array_merge($resultData, $fieldData);
		}
		$result->setData($resultData);
		$eventLog = EventLog::addEvent($eventType, $result, $this->config['ID'], $this->session['ID']);

		return $eventLog;
	}

	/**
	 * Check whether system messages can be sent to the session.
	 *
	 * @return bool
	 */
	public function isEnableSendSystemMessage(): bool
	{
		$result = false;

		if (
			$this->isDisabledSendSystemMessage === false
			&& !empty($this->connectorId)
			&& !ReplyBlock::isBlocked($this)
		)
		{
			$result = Connector::isEnableSendSystemMessage($this->connectorId);

			if (
				$result === true
				&& $this->action === self::ACTION_CLOSED
				&& $this->config['ACTIVE'] === 'N'
			)
			{
				$result = false;
			}
			elseif (Loader::includeModule('imconnector'))
			{
				$connectorHandler = ImConnector\Connector::initConnectorHandler($this->getData('SOURCE'));

				if (!empty($connectorHandler))
				{
					$result = $connectorHandler->isEnableSendSystemMessage($this);
				}
			}
		}

		return $result;
	}

	public function isCloseVote()
	{
		return $this->isCloseVote;
	}

	/**
	 * Forcibly disabling system messages.
	 *
	 * @param bool $value
	 */
	public function setDisabledSendSystemMessage(bool $value): void
	{
		$this->isDisabledSendSystemMessage = $value;
	}

	/**
	 * Forcibly disabling system messages.
	 *
	 * @param bool $value
	 */
	public function setForcedSendVote(bool $value): void
	{
		$this->isForcedSendVote = $value;
	}

	//Event
	public static function onSessionProlongLastMessage($chatId, $dialogId, $entityType = '', $entityId = '', $userId = '')
	{
		if ($entityType != 'LINES')
		{
			return true;
		}

		self::prolongDueChatActivity($chatId);

		return true;
	}

	public static function onSessionProlongWriting($params)
	{
		$entityType = $params['CHAT']['ENTITY_TYPE'] ?? null;
		if ($entityType !== 'LINES')
		{
			return true;
		}

		self::prolongDueChatActivity($params['CHAT']['ID']);

		return true;
	}

	public static function onSessionProlongChatRename($chatId, $title, $entityType = '', $entityId = '', $userId = '')
	{
		if ($entityType != 'LINES')
		{
			return true;
		}

		self::prolongDueChatActivity($chatId);

		return true;
	}

	//Agent
	/**
	 * @deprecated
	 *
	 * @param $nextExec
	 * @param $offset
	 * @return string
	 */
	public static function transferToNextInQueueAgent($nextExec, $offset = 0)
	{
		return Agent::transferToNextInQueue($nextExec, $offset);
	}

	/**
	 * @deprecated
	 *
	 * @param $nextExec
	 * @return string
	 */
	public static function closeByTimeAgent($nextExec)
	{
		return Agent::closeByTime($nextExec);
	}

	/**
	 * @deprecated
	 *
	 * @param $nextExec
	 * @return string
	 */
	public static function mailByTimeAgent($nextExec)
	{
		return Agent::mailByTime($nextExec);
	}

	/**
	 * @deprecated
	 *
	 * @return array
	 */
	public static function getAgreementFields()
	{
		return Session\Common::getAgreementFields();
	}

	/**
	 * Delete all work data about session with sessionId
	 *
	 * @param $sessionId
	 *
	 * @return bool
	 */
	public static function deleteSession($sessionId)
	{
		//TODO - add removing of bounded im_chat
		SessionTable::delete($sessionId);
		SessionCheckTable::delete($sessionId);
		SessionIndexTable::delete($sessionId);
		$kpi = new KpiManager($sessionId);
		$kpi->deleteSessionMessages();
		unset($kpi);

		return true;
	}

	/**
	 * @deprecated
	 *
	 * @param $nextExec
	 * @return string
	 */
	public static function dismissedOperatorAgent($nextExec)
	{
		return '';
	}
}
