<?php
namespace Bitrix\ImOpenLines;

use \Bitrix\Main,
	\Bitrix\Main\Loader,
	\Bitrix\Main\Application,
	\Bitrix\Main\Type\DateTime,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\DB\SqlExpression;

use \Bitrix\ImOpenLines\Log\Library,
	\Bitrix\Imopenlines\Im\Messages,
	\Bitrix\ImOpenLines\Log\EventLog,
	\Bitrix\ImOpenLines\Session\Agent,
	\Bitrix\ImOpenLines\Model\SessionTable,
	\Bitrix\ImOpenLines\Model\SessionCheckTable,
	\Bitrix\Imopenlines\Model\SessionIndexTable;

Loc::loadMessages(__FILE__);

class Session
{
	private $config = [];
	private $session = [];
	private $user = [];
	private $connectorId = '';

	/* @var \Bitrix\ImOpenLines\Chat */
	public $chat = null;

	private $action = 'none';
	public $joinUserId = 0;
	public $joinUserList = [];
	private $isCreated = false;

	const RULE_TEXT = 'text';
	const RULE_FORM = 'form';
	const RULE_NONE = 'none';

	const ACTION_NO_ANSWER = 'no_answer';
	const ACTION_CLOSED = 'closed';
	const ACTION_NONE = 'none';

	const MODE_INPUT = 'input';
	const MODE_OUTPUT = 'output';

	const CACHE_QUEUE = 'queue';
	const CACHE_CLOSE = 'close';
	const CACHE_MAIL = 'mail';
	const CACHE_INIT = 'init';
	const CACHE_NO_ANSWER = 'no_answer';

	/** New dialog opens. */
	const STATUS_NEW = 0;
	/** The operator sent the dialog to the queue. */
	const STATUS_SKIP = 5;
	/** The operator took the dialogue to work. */
	const STATUS_ANSWER = 10;
	/** The client is waiting for the operator's response. */
	const STATUS_CLIENT = 20;
	/** The client is waiting for the operator's answer (new question after answer). */
	const STATUS_CLIENT_AFTER_OPERATOR = 25;
	/** The operator responded to the client. */
	const STATUS_OPERATOR = 40;
	/** The dialogue in the mode of closing (pending the vote or after auto-answer). */
	const STATUS_WAIT_CLIENT = 50;
	/** The conversation has ended. */
	const STATUS_CLOSE = 60;
	/** Spam / forced termination. */
	const STATUS_SPAM = 65;
	/** Duplicate session. The session is considered closed. */
	const STATUS_DUPLICATE = 69;
	/** Closed without sending special messages and notifications. */
	const STATUS_SILENTLY_CLOSE = 75;

	const ORM_SAVE = 'save';
	const ORM_GET = 'get';

	const VOTE_LIKE = 5;
	const VOTE_DISLIKE = 1;

	/**
	 * Session constructor.
	 * @param array $config
	 * @throws Main\LoaderException
	 */
	public function __construct($config = [])
	{
		$this->config = $config;

		Loader::includeModule('im');
	}

	/**
	 * Initialization of the session by the given data.
	 *
	 * @param array $session Array describing the session.
	 * @param array $config An array describing the setting of an open line.
	 * @param array $chat An array describing the chat.
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
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
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

		if(Connector::isEnableGroupByChat($parsedUserCode['CONNECTOR_ID']))
		{
			$parsedUserCode['CONNECTOR_USER_ID'] = 0;

			if(!empty($params['USER_ID']))
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

		if($resultStart->isSuccess() && $resultStart->getResult() == true)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param $params
	 * @return Result
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function start($params)
	{
		Debug::addSession($this, 'begin ' . __METHOD__, $params);
		$result = new Result();

		$result->setResult(false);

		//params
		$this->connectorId = $params['SOURCE'];

		$fields['PARENT_ID'] = intval($params['PARENT_ID']);
		$fields['USER_CODE'] = $params['USER_CODE'];

		$fields['USER_CODE_FAIL'] = $params['USER_CODE_FAIL'];

		$fields['CONFIG_ID'] = intval($params['CONFIG_ID']);
		$fields['USER_ID'] = intval($params['USER_ID']);
		$fields['OPERATOR_ID'] = intval($params['OPERATOR_ID']);
		$params['CRM_TRACE_DATA'] = (string)$params['CRM_TRACE_DATA'];
		$fields['SOURCE'] = $params['SOURCE'];
		$fields['MODE'] =  $params['MODE'] == self::MODE_OUTPUT? self::MODE_OUTPUT: self::MODE_INPUT;
		$params['DEFERRED_JOIN'] =  $params['DEFERRED_JOIN'] == 'Y'? 'Y': 'N';
		$params['SKIP_CREATE'] =  $params['SKIP_CREATE'] == 'Y'? 'Y': 'N';
		$params['REOPEN'] =  $params['REOPEN'] == 'Y'? 'Y': 'N';

		$configManager = new Config();
		//Check open line configuration load
		if (empty($this->config) && !empty($fields['CONFIG_ID']))
		{
			$this->config = $configManager->get($fields['CONFIG_ID']);
		}
		if (empty($this->config))
		{
			$result->addError(new Error(Loc::getMessage('IMOL_SESSION_ERROR_NO_IMOL_CONFIGURATION'), 'NO IMOL CONFIGURATION', __METHOD__, $params));
		}

		if($result->isSuccess())
		{
			if($this->prepareUserChat($params) !== true || empty($this->chat))
			{
				$result->addError(new Error(Loc::getMessage('IMOL_SESSION_ERROR_NO_CHAT'), 'NO CHAT', __METHOD__, $params));
			}
		}
		//END params

		if($result->isSuccess())
		{
			//Load session
			$resultReading = $this->readingSession($fields, $params);

			if($resultReading->isSuccess())
			{
				if($resultReading->getResult() == true)
				{
					$result->setResult(true);
				}
				//If you do create a session
				else if ($params['SKIP_CREATE'] != 'Y')
				{
					//Creating a new session
					$resultCreate = $this->createSession($fields, $params);

					if($resultCreate->isSuccess())
					{
						$result->setResult(true);
					}
					else
					{
						$result->addErrors($resultCreate->getErrors());
					}
					//END Creating a new session
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
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function createSession($fields, $params)
	{
		Debug::addSession($this, 'begin ' . __METHOD__, ['fields' => $fields, '$params' => $params]);

		$result = new Result();

		/* CRM BLOCK */
		$crmManager = new Crm($this);
		if($crmManager->isLoaded())
		{
			$crmFieldsManager = $crmManager->getFields();
			$crmFieldsManager->setDataFromUser($fields['USER_ID']);
			if($this->config['CRM_CREATE'] != Config::CRM_CREATE_LEAD)
			{
				$crmManager->setSkipCreate();
			}

			if(Connector::isNeedCRMTracker($fields['SOURCE']))
			{
				$crmManager->setSkipCreate();
				$crmManager->setIgnoreSearchPerson();
			}

			$fields['CRM_TRACE_DATA'] = Crm\Tracker::getTraceData($fields['USER_ID'], $params['CRM_TRACE_DATA']);
		}
		/* END CRM BLOCK */

		if ($fields['MODE'] === self::MODE_OUTPUT)
		{
			$previousSessionBlock = ReplyBlock::getBlockFromPreviousSession($fields);
		}

		$fields['CHAT_ID'] = $this->chat->getData('ID');

		if ($this->chat->isNowCreated())
		{
			$fields['START_ID'] = 0;
			$fields['IS_FIRST'] = 'Y';
		}
		else
		{
			$fields['START_ID'] = $this->chat->getData('LAST_MESSAGE_ID')+1;
			$fields['IS_FIRST'] = 'N';

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

			if($resultAddSessionCheck->isSuccess())
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
					$messageId = Messages\Session::sendMessageStartSessionByMessage($fields['CHAT_ID'], $fields['SESSION_ID'], $fields['PARENT_ID']);
				}
				else
				{
					$messageId = Messages\Session::sendMessageStartSession($fields['CHAT_ID'], $fields['SESSION_ID']);
				}
				//END Send message

				if ($this->chat->isNowCreated())
				{
					if(intval($messageId) < 0)
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
					else if(!(new AutomaticAction\WorkTime($this))->isWorkTimeLine())
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
					$fields['MODE'] == self::MODE_INPUT &&
					!empty($this->joinUserList) &&
					$params['DEFERRED_JOIN'] == 'N'
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

				if (!empty($previousSessionBlock['BLOCK_DATE']) && !empty($previousSessionBlock['BLOCK_REASON']))
				{
					ReplyBlock::add($fields['SESSION_ID'], $this->chat, $previousSessionBlock);
				}
				elseif (Loader::includeModule('imconnector'))
				{
					$limit = \Bitrix\ImConnector\Connector::getReplyLimit($fields['SOURCE']);
					if ($limit)
					{
						$limit['BLOCK_DATE'] = (new DateTime())->add($limit['BLOCK_DATE'].' SECONDS');

						ReplyBlock::add($fields['SESSION_ID'], $this->chat, $limit);
						Messages\Session::sendMessageTimeLimit($fields['CHAT_ID'], $limit['BLOCK_REASON']);
					}
				}


				/* BLOCK STATISTIC*/
				ConfigStatistic::getInstance($fields['CONFIG_ID'])->addInWork()->addSession();
				/* END BLOCK STATISTIC*/

				/* CRM BLOCK */
				if ($fields['MODE'] == self::MODE_INPUT)
				{
					if (!Connector::isEnableGroupByChat($fields['SOURCE']) && $this->config['CRM'] == 'Y' && $crmManager->isLoaded())
					{
						$crmFieldsManager->setTitle($this->chat->getData('TITLE'));

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
					if($this->config['CRM'] == 'Y' && $crmManager->isLoaded())
					{
						$crmFieldsManager->setTitle($this->chat->getData('TITLE'));

						$crmManager->registrationChanges();
						$crmManager->sendCrmImMessages();
					}
				}
				/* END CRM BLOCK */

				/* Event */
				$eventData['SESSION'] = $this->session;
				$eventData['RUNTIME_SESSION'] = $this;
				$eventData['CONFIG'] = $this->config;

				$event = new \Bitrix\Main\Event("imopenlines", "OnSessionStart", $eventData);
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
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
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
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
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

		if(!empty($fields['USER_CODE_FAIL']))
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
		while($row = $orm->fetch())
		{
			if (
				$row['USER_CODE'] == $fields['USER_CODE'] &&
				empty($loadSession)
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
				catch (\Exception $e)
				{
				}
			}
		}

		//There is no open session, but the session voting
		if (
			empty($loadSession) &&
			(
				$params['VOTE_SESSION'] == 'Y' ||
				$params['REOPEN'] == 'Y'
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

			if (empty($loadSession) || $loadSession['WAIT_VOTE'] != 'Y')
			{
				$loadSession = false;
			}
		}

		if ($loadSession)
		{
			$loadSession['SESSION_ID'] = $loadSession['ID'];
			$this->session = $loadSession;

			if($params['VOTE_SESSION'] == 'Y')
			{
				$this->session['VOTE_SESSION'] = true;
			}

			if ($fields['CONFIG_ID'] != $this->session['CONFIG_ID'])
			{
				$this->config = $configManager->get($this->session['CONFIG_ID']);
				if (!empty($this->config))
				{
					$fields['CONFIG_ID'] = $this->session['CONFIG_ID'];
				}
				else
				{
					$result->addError(new Error(Loc::getMessage('IMOL_SESSION_ERROR_NO_IMOL_CONFIGURATION'), 'NO IMOL CONFIGURATION', __METHOD__, $params));
				}
			}

			if($result->isSuccess())
			{
				$this->chat = new Chat($this->session['CHAT_ID']);

				//If the session is closed and voting
				if ($params['VOTE_SESSION'] == 'Y' && $loadSession['CLOSED'] == 'Y')
				{
					Messages\Session::sendMessageReopenSession($this->session['CHAT_ID'], $this->session['SESSION_ID']);

					//statistics
					ConfigStatistic::getInstance($this->session['CONFIG_ID'])->deleteClosed()->addInWork();
					//statistics END

					$dateClose = new DateTime();

					$fullCloseTime = $this->config['FULL_CLOSE_TIME'];
					if(!empty($fullCloseTime))
					{
						$dateClose->add($fullCloseTime . ' MINUTES');
					}

					$this->session['END_ID'] = 0;
					$this->session['CLOSED'] = 'N';
					$this->session['WAIT_ANSWER'] = 'N';
					$this->session['WAIT_ACTION'] = 'Y';
					$this->session['PAUSE'] = 'N';

					Model\SessionTable::update($loadSession['ID'], [
						'END_ID' => 0,
						'CLOSED' => 'N',
						'WAIT_ANSWER' => 'N',
						'WAIT_ACTION' => 'Y',
						'PAUSE' => 'N',
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
							'PAUSE' => 'N',
							'WAIT_ANSWER' => 'N',
							'DATE_CREATE' => $this->session['DATE_CREATE']->getTimestamp()
						],
					]);
				}
				else if (!$this->chat->isNowCreated())
				{
					$this->chat->join($fields['USER_ID']);
				}

				$result->setResult(true);
			}
		}

		Debug::addSession($this, 'end ' . __METHOD__, ['SUCCESS' => $result->isSuccess(), 'ERRORS' => $result->getErrorMessages()]);

		return $result;
	}

	/**
	 * @param $params
	 * @param int $count
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function prepareUserChat($params, $count = 0)
	{
		$result = false;

		$resultUserRelation = \Bitrix\Imopenlines\Model\UserRelationTable::getByPrimary($params['USER_CODE'])->fetch();
		if ($resultUserRelation)
		{
			if ($resultUserRelation['CHAT_ID'])
			{
				$this->chat = new Chat($resultUserRelation['CHAT_ID'], $params);
				if ($this->chat->isDataLoaded())
				{
					$this->user = $resultUserRelation;

					$result = true;
				}
			}
			else if ($count <= 20)
			{
				usleep(500);
				$result = $this->prepareUserChat($params, ++$count);
			}
		}
		else if ($params['SKIP_CREATE'] != 'Y')
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
	 * @param bool $active
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function pause($active = true)
	{
		$update = [
			'PAUSE' => $active? 'Y': 'N',
		];
		if ($active == 'Y')
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
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
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
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function finish($auto = false, $force = false, $hideChat = true)
	{
		$result = false;

		Debug::addSession($this, __METHOD__, ['auto' => $auto, 'force' => $force, 'hideChat' => $hideChat]);
		if (!empty($this->session))
		{
			KpiManager::setSessionLastKpiMessageAnswered($this->session['ID']);

			$update = [];

			if ($force)
			{
				$this->session['CLOSED'] = 'Y';
				$update['FORCE_CLOSE'] = 'Y';
			}

			$currentDate = new DateTime();

			if ($this->session['CHAT_ID'])
			{
				$chatData = \Bitrix\Im\Model\ChatTable::getById($this->session['CHAT_ID'])->fetch();
				$lastMessageId = $chatData['LAST_MESSAGE_ID'];
			}
			else
			{
				$lastMessageId = 0;
			}

			if ($auto && $lastMessageId > 0)
			{
				$messageData = \Bitrix\Im\Model\MessageTable::getById($lastMessageId)->fetch();
				if ($messageData)
				{
					$currentDate = clone $messageData['DATE_CREATE'];
				}
			}

			$userViewChat = \CIMContactList::InRecent($this->session['OPERATOR_ID'], IM_MESSAGE_OPEN_LINE, $this->session['CHAT_ID']);

			if (
				$this->session['CLOSED'] == 'Y'
				|| $this->session['SPAM'] == 'Y'
				|| $this->session['WAIT_ACTION'] == 'Y' && $this->session['WAIT_ANSWER'] == 'N'
			)
			{
				$update['WAIT_ACTION'] = 'N';
				$update['WAIT_ANSWER'] = 'N';
				$update['CLOSED'] = 'Y';

				$params = [
					"CLASS" => "bx-messenger-content-item-ol-end"
				];
				if ($this->config['VOTE_MESSAGE'] == 'Y')
				{
					$params["TYPE"] = "lines";
					$params["COMPONENT_ID"] = "bx-imopenlines-message";
					$params["IMOL_VOTE_SID"] = $this->session['ID'];
					$params["IMOL_VOTE_USER"] = $this->session['VOTE'];
					$params["IMOL_VOTE_HEAD"] = $this->session['VOTE_HEAD'];
					$params["IMOL_COMMENT_HEAD"] = $this->session['COMMENT_HEAD'];
				}
				{

					$addMessageId = Im::addMessage([
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"FROM_USER_ID" => $this->session['OPERATOR_ID'],
						"MESSAGE" => Loc::getMessage('IMOL_SESSION_CLOSE_FINAL'),
						"SYSTEM" => 'Y',
						"RECENT_ADD" => $userViewChat? 'Y': 'N',
						"PARAMS" => $params
					]);

					if(!empty($addMessageId))
					{
						$lastMessageId = $addMessageId;
					}
				}

			}
			else
			{
				$enableSystemMessage = $this->isEnableSendSystemMessage();
				if ($this->config['ACTIVE'] == 'N')
				{
					$update['WAIT_ACTION'] = 'N';
					$update['WAIT_ANSWER'] = 'N';
					$update['CLOSED'] = 'Y';

					$params = [
						"CLASS" => "bx-messenger-content-item-ol-end"
					];
					if ($this->config['VOTE_MESSAGE'] == 'Y')
					{
						$params["TYPE"] = "lines";
						$params["COMPONENT_ID"] = "bx-imopenlines-message";
						$params["IMOL_VOTE_SID"] = $this->session['ID'];
						$params["IMOL_VOTE_USER"] = $this->session['VOTE'];
						$params["IMOL_VOTE_HEAD"] = $this->session['VOTE_HEAD'];
						$params["IMOL_COMMENT_HEAD"] = $this->session['COMMENT_HEAD'];
					}
					{

						$addMessageId = Im::addMessage([
							"TO_CHAT_ID" => $this->session['CHAT_ID'],
							"FROM_USER_ID" => $this->session['OPERATOR_ID'],
							"RECENT_ADD" => $userViewChat? 'Y': 'N',
							"MESSAGE" => Loc::getMessage('IMOL_SESSION_CLOSE_FINAL'),
							"SYSTEM" => 'Y',
							"PARAMS" => $params
						]);

						if(!empty($addMessageId))
						{
							$lastMessageId = $addMessageId;
						}
					}
				}
				else if ($auto)
				{
					$waitAction = false;
					if ($enableSystemMessage && $this->config['AUTO_CLOSE_RULE'] == self::RULE_TEXT)
					{
						$this->chat->update([
							Chat::getFieldName(Chat::FIELD_SILENT_MODE) => 'N'
						]);

						$addMessageId = Im::addMessage([
							"TO_CHAT_ID" => $this->session['CHAT_ID'],
							"FROM_USER_ID" => $this->session['OPERATOR_ID'],
							"MESSAGE" => $this->config['AUTO_CLOSE_TEXT'],
							"SYSTEM" => 'Y',
							"RECENT_ADD" => $userViewChat? 'Y': 'N',
							"IMPORTANT_CONNECTOR" => 'Y',
							"PARAMS" => [
								"CLASS" => "bx-messenger-content-item-ol-output",
								"IMOL_FORM" => "history",
								"TYPE" => "lines",
								"COMPONENT_ID" => "bx-imopenlines-message",
							]
						]);

						if(!empty($addMessageId))
						{
							$lastMessageId = $addMessageId;
						}

						$update['WAIT_ACTION'] = 'Y';
						$update['WAIT_ANSWER'] = 'N';
						$waitAction = true;
					}

					if ($enableSystemMessage && $this->config['VOTE_MESSAGE'] == 'Y' && $this->session['CHAT_ID'] && empty($this->session['VOTE']))
					{
						$addMessageId = Im::addMessage([
							"TO_CHAT_ID" => $this->session['CHAT_ID'],
							"FROM_USER_ID" => $this->session['OPERATOR_ID'],
							"MESSAGE" => $this->config['VOTE_MESSAGE_2_TEXT'],
							"SYSTEM" => 'Y',
							"RECENT_ADD" => $userViewChat? 'Y': 'N',
							"IMPORTANT_CONNECTOR" => 'Y',
							"PARAMS" => [
								"IMOL_VOTE" => $this->session['ID'],
								"IMOL_VOTE_TEXT" => $this->config['VOTE_MESSAGE_1_TEXT'],
								"IMOL_VOTE_LIKE" => $this->config['VOTE_MESSAGE_1_LIKE'],
								"IMOL_VOTE_DISLIKE" => $this->config['VOTE_MESSAGE_1_DISLIKE'],
								"CLASS" => "bx-messenger-content-item-ol-output bx-messenger-content-item-vote",
								"IMOL_FORM" => "history-delay",
								"TYPE" => "lines",
								"COMPONENT_ID" => "bx-imopenlines-message",
							]
						]);

						if(!empty($addMessageId))
						{
							$lastMessageId = $addMessageId;
						}

						$update['WAIT_ACTION'] = 'Y';
						$update['WAIT_ANSWER'] = 'N';
						$update['WAIT_VOTE'] = 'Y';
						$waitAction = true;
					}

					if (!$waitAction)
					{
						$update['WAIT_ACTION'] = 'N';
						$update['WAIT_ANSWER'] = 'N';
						$update['CLOSED'] = 'Y';

						$params = [
							"CLASS" => "bx-messenger-content-item-ol-end"
						];
						if ($this->config['VOTE_MESSAGE'] == 'Y')
						{
							$params["TYPE"] = "lines";
							$params["COMPONENT_ID"] = "bx-imopenlines-message";
							$params["IMOL_VOTE_SID"] = $this->session['ID'];
							$params["IMOL_VOTE_USER"] = $this->session['VOTE'];
							$params["IMOL_VOTE_HEAD"] = $this->session['VOTE_HEAD'];
							$params["IMOL_COMMENT_HEAD"] = $this->session['COMMENT_HEAD'];
						}
						$addMessageId = Im::addMessage([
							"TO_CHAT_ID" => $this->session['CHAT_ID'],
							"FROM_USER_ID" => $this->session['OPERATOR_ID'],
							"RECENT_ADD" => $userViewChat? 'Y': 'N',
							"MESSAGE" => Loc::getMessage('IMOL_SESSION_CLOSE_AUTO'),
							"SYSTEM" => 'Y',
							"PARAMS" => $params
						]);

						if(!empty($addMessageId))
						{
							$lastMessageId = $addMessageId;
						}
					}
				}
				else
				{
					$waitAction = false;
					if ($enableSystemMessage && $this->config['CLOSE_RULE'] == self::RULE_TEXT)
					{
						$addMessageId = Im::addMessage([
							"TO_CHAT_ID" => $this->session['CHAT_ID'],
							"FROM_USER_ID" => $this->session['OPERATOR_ID'],
							"MESSAGE" => $this->config['CLOSE_TEXT'],
							"RECENT_ADD" => $userViewChat? 'Y': 'N',
							"SYSTEM" => 'Y',
							"IMPORTANT_CONNECTOR" => 'Y',
							"PARAMS" => [
								"CLASS" => "bx-messenger-content-item-ol-output",
								"IMOL_FORM" => "history",
								"TYPE" => "lines",
								"COMPONENT_ID" => "bx-imopenlines-message",
							]
						]);

						if(!empty($addMessageId))
						{
							$lastMessageId = $addMessageId;
						}

						$update['WAIT_ACTION'] = 'Y';
						$update['WAIT_ANSWER'] = 'N';
						$waitAction = true;
					}

					if ($enableSystemMessage && $this->config['VOTE_MESSAGE'] == 'Y' && empty($this->session['VOTE']))
					{
						$addMessageId = Im::addMessage([
							"TO_CHAT_ID" => $this->session['CHAT_ID'],
							"FROM_USER_ID" => $this->session['OPERATOR_ID'],
							"MESSAGE" => $this->config['VOTE_MESSAGE_2_TEXT'],
							"SYSTEM" => 'Y',
							"RECENT_ADD" => $userViewChat? 'Y': 'N',
							"IMPORTANT_CONNECTOR" => 'Y',
							"PARAMS" => [
								"IMOL_VOTE" => $this->session['ID'],
								"IMOL_VOTE_TEXT" => $this->config['VOTE_MESSAGE_1_TEXT'],
								"IMOL_VOTE_LIKE" => $this->config['VOTE_MESSAGE_1_LIKE'],
								"IMOL_VOTE_DISLIKE" => $this->config['VOTE_MESSAGE_1_DISLIKE'],
								"CLASS" => "bx-messenger-content-item-ol-output bx-messenger-content-item-vote",
								"IMOL_FORM" => "history-delay",
								"TYPE" => "lines",
								"COMPONENT_ID" => "bx-imopenlines-message",
							]
						]);

						if(!empty($addMessageId))
						{
							$lastMessageId = $addMessageId;
						}

						$update['WAIT_ACTION'] = 'Y';
						$update['WAIT_ANSWER'] = 'N';
						$update['WAIT_VOTE'] = 'Y';
						$waitAction = true;
					}

					if (!$waitAction)
					{
						$userSkip = \Bitrix\Im\User::getInstance($this->chat->getData('OPERATOR_ID'));

						$params = [
							"CLASS" => "bx-messenger-content-item-ol-end"
						];
						if ($this->config['VOTE_MESSAGE'] == 'Y')
						{
							$params["IMOL_VOTE_SID"] = $this->session['ID'];
							$params["IMOL_VOTE_USER"] = $this->session['VOTE'];
							$params["IMOL_VOTE_HEAD"] = $this->session['VOTE_HEAD'];
							$params["IMOL_COMMENT_HEAD"] = $this->session['COMMENT_HEAD'];
						}
						$addMessageId = Im::addMessage([
							"TO_CHAT_ID" => $this->session['CHAT_ID'],
							"FROM_USER_ID" => $this->session['OPERATOR_ID'],
							"RECENT_ADD" => $userViewChat? 'Y': 'N',
							"MESSAGE" => Loc::getMessage('IMOL_SESSION_CLOSE_' . $userSkip->getGender(), ['#USER#' => '[USER=' . $userSkip->getId().'][/USER]']),
							"SYSTEM" => 'Y',
							"PARAMS" => $params
						]);

						if(!empty($addMessageId))
						{
							$lastMessageId = $addMessageId;
						}

						$update['WAIT_ACTION'] = 'N';
						$update['WAIT_ANSWER'] = 'N';
						$update['CLOSED'] = 'Y';
					}

					if (!\Bitrix\Im\User::getInstance($this->session['OPERATOR_ID'])->isBot())
					{
						$update['DATE_OPERATOR_CLOSE'] = $currentDate;
					}
					if ($this->session['DATE_CREATE'])
					{
						$update['TIME_CLOSE'] = $currentDate->getTimestamp()-$this->session['DATE_CREATE']->getTimestamp();
					}
				}
				$update['DATE_MODIFY'] = $currentDate;
			}

			if ($update['CLOSED'] == 'Y')
			{
				if ($this->session['CRM_ACTIVITY_ID'] > 0)
				{
					$crmManager = new Crm($this);
					if($crmManager->isLoaded())
					{
						$crmManager->setSessionClosed(['DATE_CLOSE' => $currentDate]);
					}
				}

				$update['DATE_CLOSE'] = $currentDate;
				if ($this->session['TIME_CLOSE'] <= 0 && $this->session['DATE_CREATE'])
				{
					$update['TIME_CLOSE'] = $update['DATE_CLOSE']->getTimestamp()-$this->session['DATE_CREATE']->getTimestamp();
				}
				if (\Bitrix\Im\User::getInstance($this->session['OPERATOR_ID'])->isBot() && $this->session['TIME_BOT'] <= 0 && $this->session['DATE_CREATE'])
				{
					$update['TIME_BOT'] = $update['DATE_CLOSE']->getTimestamp()-$this->session['DATE_CREATE']->getTimestamp();
				}

				if ($this->session['CHAT_ID'])
				{
					$update['END_ID'] = $lastMessageId;
				}
			}

			if ($hideChat)
			{
				Im::chatHide($this->session['CHAT_ID']);
			}

			$this->update($update);

			if (method_exists('\\Bitrix\\ImConnector\\Chat', 'deleteLastMessage'))
			{
				//TODO: Replace with the method \Bitrix\ImOpenLines\Chat::parseLinesChatEntityId or \Bitrix\ImOpenLines\Chat::parseLiveChatEntityId
				list($connectorId, $lineId, $connectorChatId, $connectorUserId) = explode('|', $this->user['USER_CODE']);
				\Bitrix\ImConnector\Chat::deleteLastMessage($connectorChatId, $connectorId);
			}

			if ($update['CLOSED'] == 'Y')
			{
				$eventData['RUNTIME_SESSION'] = $this;
				$eventData['SESSION'] = $this->session;
				$eventData['CONFIG'] = $this->config;
				$event = new \Bitrix\Main\Event("imopenlines", "OnSessionFinish", $eventData);
				$event->send();
			}

			$result = true;
		}

		return $result;
	}

	/**
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
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

		if(!empty($this->session['DATE_LAST_MESSAGE']))
		{
			$closeDate = $this->session['DATE_LAST_MESSAGE']->add('60 MINUTES');
		}
		else
		{
			$closeDate = new DateTime();
		}

		if ($this->session['CHAT_ID'])
		{
			$chatData = \Bitrix\Im\Model\ChatTable::getById($this->session['CHAT_ID'])->fetch();
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
				if (!\Bitrix\Im\User::getInstance($this->session['OPERATOR_ID'])->isBot())
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
			if($crmManager->isLoaded())
			{
				$crmManager->setSessionClosed(['DATE_CLOSE' => $closeDate]);
			}
		}

		$update['DATE_CLOSE'] = $closeDate;

		if ($this->session['TIME_CLOSE'] <= 0 && $this->session['DATE_CREATE'])
		{
			$update['TIME_CLOSE'] = $update['DATE_CLOSE']->getTimestamp()-$this->session['DATE_CREATE']->getTimestamp();
		}
		if (\Bitrix\Im\User::getInstance($this->session['OPERATOR_ID'])->isBot() && $this->session['TIME_BOT'] <= 0 && $this->session['DATE_CREATE'])
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
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
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
	 *
	 * @param $fields
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
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

		if (array_key_exists('SEND_NO_ANSWER_TEXT', $fields) && $fields['SEND_NO_ANSWER_TEXT'] == 'Y')
		{
			$updateCheckTable['DATE_NO_ANSWER'] = null;
		}

		if (array_key_exists('CHECK_DATE_CLOSE', $fields))
		{
			$updateCheckTable['DATE_CLOSE'] = $fields['CHECK_DATE_CLOSE'];
			unset($fields['CHECK_DATE_CLOSE']);
		}
		else if (isset($fields['DATE_MODIFY']) && $fields['CLOSED'] != 'Y')
		{
			$dateCrmClose = new DateTime();
			$dateCrmClose->add('1 DAY');
			$dateCrmClose->add($this->getConfig('AUTO_CLOSE_TIME').' SECONDS');

			$fullCloseTime = $this->getConfig('FULL_CLOSE_TIME');

			/** var DateTime */
			$dateClose = clone $fields['DATE_MODIFY'];

			//do not pause
			if ($this->session['PAUSE'] == 'N' || $fields['PAUSE'] == 'N')
			{
				if (isset($fields['USER_ID']) && \Bitrix\Im\User::getInstance($fields['USER_ID'])->isConnector())
				{
					if ($this->session['VOTE_SESSION'] && ($this->session['WAIT_ACTION'] == 'Y' && $fields['WAIT_ACTION'] != 'N' || $fields['WAIT_ACTION'] == 'Y'))
					{
						$fields['STATUS'] = self::STATUS_WAIT_CLIENT;
						if(!empty($fullCloseTime))
						{
							$dateClose->add($fullCloseTime . ' MINUTES');
						}

						$updateDateCrmClose = $dateCrmClose;
					}
					else
					{
						$dateClose->add('1 MONTH');
						$updateCheckTable['DATE_CLOSE'] = $dateClose;
						$updateChatSession['WAIT_ACTION'] = $this->session['WAIT_ACTION'] = $fields['WAIT_ACTION'] = "N";

						if ($this->session['STATUS'] >= self::STATUS_OPERATOR || $this->session['STATUS'] == self::STATUS_ANSWER)
						{
							$updateDateCrmClose = $dateCrmClose;
						}

						if ($this->session['WAIT_ANSWER'] == 'N')
						{
							$fields['STATUS'] = $this->session['STATUS'] >= self::STATUS_OPERATOR? self::STATUS_CLIENT_AFTER_OPERATOR: self::STATUS_CLIENT;
						}
					}
				}
				else
				{
					if (isset($fields['SKIP_DATE_CLOSE']))
					{
						$dateClose->add('1 MONTH');
					}
					else if ($this->session['WAIT_ANSWER'] == 'Y' && $fields['WAIT_ANSWER'] != 'N' || $fields['WAIT_ANSWER'] == 'Y')
					{
						$fields['STATUS'] = $this->session['STATUS'] >= self::STATUS_CLIENT_AFTER_OPERATOR? self::STATUS_CLIENT_AFTER_OPERATOR: self::STATUS_CLIENT;
						$dateClose->add('1 MONTH');

						if ($this->session['STATUS'] >=  self::STATUS_OPERATOR)
						{
							$updateDateCrmClose = $dateCrmClose;
						}
					}
					else if ($this->session['WAIT_ACTION'] == 'Y' && $fields['WAIT_ACTION'] != 'N' || $fields['WAIT_ACTION'] == 'Y')
					{
						$fields['STATUS'] = self::STATUS_WAIT_CLIENT;
						if(!empty($fullCloseTime))
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
				if ($this->session['WAIT_ACTION'] == 'N' && isset($fields['USER_ID']) && \Bitrix\Im\User::getInstance($fields['USER_ID'])->isConnector())
				{
					$dateClose->add('1 MONTH');

					if ($this->session['STATUS'] >= self::STATUS_OPERATOR || $this->session['STATUS'] == self::STATUS_ANSWER)
					{
						$updateDateCrmClose = $dateCrmClose;
					}

					if ($this->session['WAIT_ANSWER'] == 'N')
					{
						$fields['STATUS'] = $this->session['STATUS'] >= self::STATUS_OPERATOR? self::STATUS_CLIENT_AFTER_OPERATOR: self::STATUS_CLIENT;
					}
				}
				else
				{
					if (isset($fields['SKIP_DATE_CLOSE']))
					{
						$dateClose->add('1 MONTH');
					}
					else if ($this->session['WAIT_ANSWER'] == 'Y' && $fields['WAIT_ANSWER'] != 'N' || $fields['WAIT_ANSWER'] == 'Y')
					{
						$dateClose->add('1 MONTH');

						$fields['STATUS'] = $this->session['STATUS'] >= self::STATUS_CLIENT_AFTER_OPERATOR? self::STATUS_CLIENT_AFTER_OPERATOR: self::STATUS_CLIENT;

						if ($this->session['STATUS'] >=  self::STATUS_OPERATOR)
						{
							$updateDateCrmClose = $dateCrmClose;
						}
					}
					else if ($this->session['WAIT_ACTION'] == 'Y' && $fields['WAIT_ACTION'] != 'N' || $fields['WAIT_ACTION'] == 'Y')
					{
						$fields['STATUS'] = self::STATUS_WAIT_CLIENT;
						if(!empty($fullCloseTime))
						{
							$dateClose->add($fullCloseTime . ' MINUTES');
						}

						$updateDateCrmClose = $dateCrmClose;
					}
					else
					{
						$dateClose->add('1 MONTH');

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

		if (isset($fields['DATE_LAST_MESSAGE']) && $this->session['DATE_CREATE'])
		{
			$fields['TIME_DIALOG'] = $fields['DATE_LAST_MESSAGE']->getTimestamp()-$this->session['DATE_CREATE']->getTimestamp();
		}

		if (isset($fields['CLOSED']) && $fields['CLOSED'] == 'Y')
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

			ConfigStatistic::getInstance($this->session['CONFIG_ID'])->addClosed()->deleteInWork();

			if ($fields['FORCE_CLOSE'] != 'Y')
			{
				$this->chat->close();
			}

			if (Connector::isLiveChat($this->session['SOURCE']) && $this->session['SPAM'] != 'Y')
			{
				if (Loader::includeModule('im') && \Bitrix\Im\User::getInstance($this->session['USER_ID'])->isOnline())
				{
					\CAgent::AddAgent('\Bitrix\ImOpenLines\Mail::sendOperatorAnswerAgent('.$this->session['ID'].');', "imopenlines", "N", 60, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL"));
				}
				else
				{
					\Bitrix\ImOpenLines\Mail::sendOperatorAnswer($this->session['ID']);
				}
			}

			Model\SessionCheckTable::delete($this->session['ID']);
		}
		else if (isset($fields['PAUSE']))
		{
			if ($fields['PAUSE'] == 'Y')
			{
				$datePause = new DateTime();
				$datePause->add('1 WEEK');

				$updateCheckTable['DATE_CLOSE'] = $datePause;
				$updateCheckTable['DATE_QUEUE'] = null;
			}
		}
		else if (isset($fields['WAIT_ANSWER']))
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
				if ($this->session['STATUS'] < self::STATUS_ANSWER)
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

		if (!empty($updateChatSession))
		{
			$this->chat->updateFieldData([Chat::FIELD_SESSION => $updateChatSession]);
		}

		if (isset($fields['MESSAGE_COUNT']))
		{
			$fields["MESSAGE_COUNT"] = new SqlExpression("?# + 1", "MESSAGE_COUNT");
			ConfigStatistic::getInstance($this->session['CONFIG_ID'])->addMessage();
		}

		if (!empty($updateCheckTable))
		{
			if (
				isset($updateCheckTable['DATE_CLOSE'])
				&& $this->session['CRM_ACTIVITY_ID'] > 0
				&& (!isset($fields['CLOSED']) || $fields['CLOSED'] == 'N')
			)
			{
				if (
					($this->session['STATUS'] >= self::STATUS_ANSWER && !in_array($this->session['STATUS'], [self::STATUS_CLIENT, self::STATUS_CLIENT_AFTER_OPERATOR]))
					|| ($fields['STATUS'] >= self::STATUS_ANSWER && !in_array($fields['STATUS'], [self::STATUS_CLIENT, self::STATUS_CLIENT_AFTER_OPERATOR]))
				)
				{
					if ($updateCheckTable['DATE_CLOSE'])
					{
						$dateClose = clone $updateCheckTable['DATE_CLOSE'];
					}
					else
					{
						$dateClose = new Main\Type\DateTime();
					}
					$dateClose->add($this->getConfig('AUTO_CLOSE_TIME').' SECONDS');
					$dateClose->add('1 DAY');

					$crmManager = new Crm($this);
					if($crmManager->isLoaded())
					{
						$crmManager->setSessionDataClose($dateClose);
					}
				}
			}

			Model\SessionCheckTable::update($this->session['ID'], $updateCheckTable);
		}

		if (!empty($updateDateCrmClose) && $this->session['CRM_ACTIVITY_ID'])
		{
			$crmManager = new Crm($this);
			if($crmManager->isLoaded())
			{
				$crmManager->setSessionDataClose($updateDateCrmClose);
			}
		}
		unset($fields['USER_ID']);
		unset($fields['SKIP_DATE_CLOSE']);
		unset($fields['FORCE_CLOSE']);

		if (isset($fields['STATUS']) && $this->session['STATUS'] != $fields['STATUS'])
		{
			$this->chat->updateSessionStatus($fields['STATUS']);

			if (Connector::isLiveChat($this->session['SOURCE']))
			{
				$parsedUserCode = Session\Common::parseUserCode($this->session['USER_CODE']);
			}

			$sessionClose = false;
			if (isset($fields['CLOSED']) && $fields['CLOSED'] == 'Y')
			{
				$sessionClose = true;
			}

			\Bitrix\Pull\Event::add($this->session['USER_ID'], [
				'module_id' => 'imopenlines',
				'command' => 'sessionStatus',
				'params' => [
					'chatId' => (int)$parsedUserCode['EXTERNAL_CHAT_ID'],
					'sessionId' => (int)$this->session['ID'],
					'sessionStatus' => (int)$fields['STATUS'],
					'spam' => $this->session['SPAM'] == 'Y',
					'sessionClose' => $sessionClose
				]
			]);
		}

		foreach ($fields as $key => $value)
		{
			$this->session[$key] = $value;
		}
		foreach ($updateCheckTable as $key => $value)
		{
			$this->session['CHECK_'.$key] = $value;
		}
		if ($this->session['ID'] && !empty($fields))
		{
			Model\SessionTable::update($this->session['ID'], $fields);
		}

		Debug::addSession($this,  __METHOD__, ['fields' => $fields, 'updateCheckTable' => $updateCheckTable, 'updateChatSession' => $updateChatSession, 'updateDateCrmClose' => $updateDateCrmClose]);

		return true;
	}

	/**
	 * @param $params
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function execAutoAction($params)
	{
		$update = [];

		if ($this->action == self::ACTION_CLOSED && $this->config['ACTIVE'] == 'N')
		{
			Im::addMessage([
				"TO_CHAT_ID" => $this->session['CHAT_ID'],
				"MESSAGE" => Loc::getMessage('IMOL_SESSION_LINE_IS_CLOSED'),
				"SYSTEM" => 'Y',
			]);
		}

		if ($this->chat->isNowCreated() && $this->config['AGREEMENT_MESSAGE'] == 'Y' && $this->isEnableSendSystemMessage())
		{
			$addAgreementMessage = true;
			if (Connector::isLiveChat($this->session['SOURCE']))
			{
				$parsedUserCode = Session\Common::parseUserCode($this->session['USER_CODE']);
				$addAgreementMessage = !\Bitrix\Main\UserConsent\Consent::getByContext(intval($this->config['AGREEMENT_ID']), 'imopenlines/livechat', $parsedUserCode['EXTERNAL_CHAT_ID']);
			}

			if ($addAgreementMessage)
			{
				$mess = Loc::loadLanguageFile(__FILE__, $this->config['LANGUAGE_ID']);
				Im::addMessage([
					"TO_CHAT_ID" => $this->session['CHAT_ID'],
					"MESSAGE" => str_replace(
						['#LINK_START#', '#LINK_END#'],
						['[URL='.\Bitrix\ImOpenLines\Common::getAgreementLink($this->config['AGREEMENT_ID'], $this->config['LANGUAGE_ID'])."]", '[/URL]'],
						$mess['IMOL_SESSION_AGREEMENT_MESSAGE']
					),
					"SYSTEM" => 'Y',
					"IMPORTANT_CONNECTOR" => 'Y',
					"PARAMS" => [
						"CLASS" => "bx-messenger-content-item-ol-output",
					]
				]);
				Im::addMessage([
					"TO_CHAT_ID" => $this->session['CHAT_ID'],
					"MESSAGE" => Loc::getMessage('IMOL_SESSION_AGREEMENT_MESSAGE_OPERATOR'),
					"SYSTEM" => 'Y',
					"PARAMS" => [
						"CLASS" => "bx-messenger-content-item-ol-attention",
					]
				]);
			}
		}

		if (is_object($GLOBALS['USER']) && method_exists($GLOBALS['USER'], 'GetId'))
		{
			$update['USER_ID'] = $GLOBALS['USER']->GetId();
		}

		if($update)
		{
			$update['DATE_MODIFY'] = new DateTime();
			$this->update($update);
		}
	}

	/**
	 * Transfer to the next statement in the queue.
	 *
	 * @param bool $manual
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function transferToNextInQueue($manual = true)
	{
		$result = false;

		Debug::addSession($this,  __METHOD__, ['manual' => $manual]);

		$queueManager = Queue::initialization($this);

		if($queueManager)
		{
			$result = $queueManager->transferToNext($manual);
		}

		return $result;
	}

	/**
	 * Send notification about unavailability of the operator.
	 *
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
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

		if($this->session['SEND_NO_ANSWER_TEXT'] != 'Y')
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
			return false;

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
	 * @throws Main\SystemException
	 */
	public static function getQueueFlagCache($type = "")
	{
		if (!$type)
			return false;

		$app = Application::getInstance();
		$managedCache = $app->getManagedCache();
		if ($result = $managedCache->read(86400*30, "imol_queue_flag_".$type))
		{
			$result = $managedCache->get("imol_queue_flag_".$type) === false? false: true;
		}
		return $result;
	}

	public function getChat()
	{
		return $this->chat;
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
	 * @throws Main\LoaderException
	 */
	public function joinUser()
	{
		Debug::addSession($this,  __METHOD__, ['joinUserList' => $this->joinUserList]);

		if (!empty($this->joinUserList))
		{
			$operatorFromCrm = false;
			if($this->isNowCreated())
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
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function updateCrmFlags($fields)
	{
		$result = false;
		$updateFields = [];

		if(
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
			if(isset($fields['CRM_CREATE']))
			{
				if($fields['CRM_CREATE'] == 'Y')
				{
					$updateFields['CRM_CREATE'] = 'Y';
					$updateFields['CRM'] = 'Y';
				}
				else
				{
					$updateFields['CRM_CREATE'] = 'N';

					if(isset($fields['CRM']))
					{
						if($fields['CRM'] == 'Y')
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

		if(isset($fields['CRM_CREATE_LEAD']))
		{
			if($fields['CRM_CREATE_LEAD'] == 'Y')
			{
				$updateFields['CRM_CREATE_LEAD'] = 'Y';
			}
			else
			{
				$updateFields['CRM_CREATE_LEAD'] = 'N';
			}
		}

		if(isset($fields['CRM_CREATE_COMPANY']))
		{
			if($fields['CRM_CREATE_COMPANY'] == 'Y')
			{
				$updateFields['CRM_CREATE_COMPANY'] = 'Y';
			}
			else
			{
				$updateFields['CRM_CREATE_COMPANY'] = 'N';
			}
		}

		if(isset($fields['CRM_CREATE_CONTACT']))
		{
			if($fields['CRM_CREATE_CONTACT'] == 'Y')
			{
				$updateFields['CRM_CREATE_CONTACT'] = 'Y';
			}
			else
			{
				$updateFields['CRM_CREATE_CONTACT'] = 'N';
			}
		}

		if(isset($fields['CRM_CREATE_DEAL']))
		{
			if($fields['CRM_CREATE_DEAL'] == 'Y')
			{
				$updateFields['CRM_CREATE_DEAL'] = 'Y';
			}
			else
			{
				$updateFields['CRM_CREATE_DEAL'] = 'N';
			}
		}

		if(isset($fields['CRM_ACTIVITY_ID']))
		{
			$updateFields['CRM_ACTIVITY_ID'] = $fields['CRM_ACTIVITY_ID'];
		}

		if(!empty($updateFields))
		{
			foreach ($updateFields as $cell=>$field)
			{
				if($this->getData($cell) == $field)
				{
					unset($updateFields['$cell']);
				}
			}
		}

		if(!empty($updateFields))
		{
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
	 * @param null $userId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function voteAsUser($sessionId, $action, $userId = null)
	{
		$finishSession = false;

		$sessionData = Model\SessionTable::getByIdPerformance($sessionId)->fetch();
		if (!$sessionData)
			return false;

		$userId = intval($userId);
		if ($userId > 0 && $sessionData['USER_ID'] != $userId)
		{
			return false;
		}

		$voteValue = $action == 'dislike'? 1: 5;

		$session = new Session();

		$result = $session->load([
			'USER_CODE' => $sessionData['USER_CODE'],
			'SKIP_CREATE' => 'Y',
			'DEFERRED_JOIN' => 'Y',
			'VOTE_SESSION' => 'Y'
		]);

		if (!$result)
		{
			return false;
		};

		Debug::addSession($session,  __METHOD__, ['sessionId' => $sessionId, 'action' => $action, 'userId' => $userId]);

		if ($session->session['ID'] != $sessionId)
		{
			return false;
		};

		$updateSession['VOTE'] = $voteValue;
		$updateSession['WAIT_VOTE'] = 'N';

		if ($session->getData('CLOSED') == 'Y' && $session->getData('VOTE') === '')
		{
			return false;
		}
		else if ($session->getData('WAIT_VOTE') == 'Y')
		{
			if($session->getConfig('VOTE_CLOSING_DELAY') == 'Y')
			{
				$finishSession = true;
			}
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

		if (Connector::isLiveChat($session->getData('SOURCE')))
		{
			// TODO change to send message to chat
			\Bitrix\ImOpenLines\Chat::sendRatingNotify(
				\Bitrix\ImOpenLines\Chat::RATING_TYPE_CLIENT,
				$sessionData['ID'],
				$voteValue,
				$sessionData['OPERATOR_ID'],
				$sessionData['USER_ID']
			);
		}
		else
		{
			\Bitrix\ImOpenLines\Chat::sendRatingNotify(
				\Bitrix\ImOpenLines\Chat::RATING_TYPE_CLIENT,
				$sessionData['ID'],
				$voteValue,
				$sessionData['OPERATOR_ID'],
				$sessionData['USER_ID']
			);
		}

		if($finishSession === true)
		{
			$session->finish(true);
		}

		return true;
	}

	/**
	 * @param $sessionId
	 * @param null $voteValue
	 * @param null $commentValue
	 * @param null $userId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function voteAsHead($sessionId, $voteValue = null, $commentValue = null, $userId = null)
	{
		$result = false;

		$sessionData = Model\SessionTable::getByIdPerformance($sessionId)->fetch();

		if ($sessionData)
		{
			$userId = intval($userId);
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

			if($resultPermissions)
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

				if(!empty($commentValue))
				{
					if(strlen($commentValue) > 10000)
					{
						$commentValue = substr($commentValue, 0, 10000) . '...';
					}

					$commentValueSafely = htmlspecialcharsbx($commentValue);
				}

				if($voteValue !== null && $commentValue !== null)
				{
					$voteValue = $voteValue == 1 || $voteValue <= 5? $voteValue: 0;

					$fieldsUpdate['VOTE_HEAD'] = $voteValue;

					$fieldsUpdate['COMMENT_HEAD'] = $commentValue;

					if($voteValue > 0)
					{
						\Bitrix\ImOpenLines\Chat::sendRatingNotify(\Bitrix\ImOpenLines\Chat::RATING_TYPE_HEAD_AND_COMMENT, $sessionData['ID'], ['vote' => $voteValue, 'comment' => $commentValue], $sessionData['OPERATOR_ID'], $userId);
					}
					else
					{
						\Bitrix\ImOpenLines\Chat::sendRatingNotify(\Bitrix\ImOpenLines\Chat::RATING_TYPE_COMMENT, $sessionData['ID'], $commentValue, $sessionData['OPERATOR_ID'], $userId);
					}

					$result = true;
				}
				elseif($voteValue !== null)
				{
					$voteValue = $voteValue == 1 || $voteValue <= 5? $voteValue: 0;

					$fieldsUpdate['VOTE_HEAD'] = $voteValue;

					if ($voteValue > 0)
					{
						\Bitrix\ImOpenLines\Chat::sendRatingNotify(\Bitrix\ImOpenLines\Chat::RATING_TYPE_HEAD, $sessionData['ID'], $voteValue, $sessionData['OPERATOR_ID'], $userId);
					}

					$result = true;
				}
				elseif($commentValue !== null)
				{
					$fieldsUpdate['COMMENT_HEAD'] = $commentValue;

					\Bitrix\ImOpenLines\Chat::sendRatingNotify(\Bitrix\ImOpenLines\Chat::RATING_TYPE_COMMENT, $sessionData['ID'], $commentValue, $sessionData['OPERATOR_ID'], $userId);

					$result = true;
				}

				if(!empty($fieldsUpdate))
				{
					Model\SessionTable::update($sessionId, $fieldsUpdate);
				}

				if (Loader::includeModule("pull") && ($voteValue !==null || $commentValue !==null))
				{
					$paramsPull = [
						'IMOL_VOTE_SID' => $sessionData['ID']
					];

					if($voteValue !==null)
					{
						$pullMessage['params']['voteValue'] = $voteValue;
						$paramsPull['IMOL_VOTE_HEAD'] = $voteValue;
					}

					if($commentValue !==null)
					{
						$pullMessage['params']['commentValue'] = $commentValueSafely;
						$paramsPull['IMOL_COMMENT_HEAD']['text'] = $commentValueSafely;
					}

					$pullUsers = \CIMChat::GetRelationById($sessionData['CHAT_ID']);
					$pullUsers[] = $userId;

					\Bitrix\Pull\Event::add($pullUsers, $pullMessage);

					if($voteValue !==null)
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

	/**
	 * Closed sessions, which for some reason remained open in the presence of an actual and active session.
	 *
	 * @param $duplicateSession
	 * @param $actualSession
	 * @return Result
	 * @throws \Exception
	 */
	protected static function closeDuplicate($duplicateSession, $actualSession)
	{
		$result = new Result();

		$resultSessionUpdate = Model\SessionTable::update($duplicateSession['ID'], [
			'STATUS' => self::STATUS_DUPLICATE,
			'WAIT_ANSWER' => 'N',
			'CLOSED' => 'Y'
		]);

		if(!$resultSessionUpdate->isSuccess())
		{
			$result->addErrors($resultSessionUpdate->getErrors());
		}

		$resultSessionCheckDelete = Model\SessionCheckTable::delete($duplicateSession['ID']);

		if(!$resultSessionCheckDelete->isSuccess())
		{
			$result->addErrors($resultSessionCheckDelete->getErrors());
		}

		if (
			$actualSession['CHAT_ID'] != $duplicateSession['CHAT_ID'] ||
			$actualSession['OPERATOR_ID'] &&
			$duplicateSession['OPERATOR_ID'] &&
			$actualSession['OPERATOR_ID'] != $duplicateSession['OPERATOR_ID']
		)
		{
			$chatManager = new Chat($duplicateSession['CHAT_ID']);
			$chatManager->leave($duplicateSession['OPERATOR_ID']);
		}

		//statistics
		ConfigStatistic::getInstance($duplicateSession['CONFIG_ID'])->addClosed()->deleteInWork();
		//statistics END

		return $result;
	}

	/**
	 * Checks that message is first operator message in current session
	 *
	 * @param $messageId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function isFirstOperatorMessage($messageId)
	{
		$message = \Bitrix\Im\MessageTable::getList(
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
	 * @throws \Exception
	 */
	private function addEventToLog($eventType, $result = null)
	{
		if (is_null($result))
		{
			$result = new Result();
		}
		else if(!($result instanceof Result))
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

		if(!empty($this->connectorId))
		{
			$result = Connector::isEnableSendSystemMessage($this->connectorId);
		}

		if($result)
		{
			if($this->action == self::ACTION_CLOSED && $this->config['ACTIVE'] == 'N')
			{
				$result = false;
			}
		}

		if (ReplyBlock::isBlocked($this))
		{
			$result = false;
		}

		return $result;
	}

	//Event
	public static function onSessionProlongLastMessage($chatId, $dialogId, $entityType = '', $entityId = '', $userId = '')
	{
		if ($entityType != 'LINES')
			return true;

		self::prolongDueChatActivity($chatId);

		return true;
	}

	public static function onSessionProlongWriting($params)
	{
		if ($params['CHAT']['ENTITY_TYPE'] != 'LINES')
			return true;

		self::prolongDueChatActivity($params['CHAT']['ID']);

		return true;
	}

	public static function onSessionProlongChatRename($chatId, $title, $entityType = '', $entityId = '', $userId = '')
	{
		if ($entityType != 'LINES')
			return true;

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
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
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
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
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
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
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
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
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
	 * Adds a lock to the session and chat.
	 *
	 * @param int $sessionId
	 * @param Chat $chat
	 * @param array $limit
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function setReplyBlock(int $sessionId, Chat $chat , array $limit): void
	{
		if (!empty($limit['BLOCK_DATE']) && !empty($limit['BLOCK_REASON']))
		{
			Model\SessionTable::update($sessionId, Array(
				'BLOCK_DATE' => $limit['BLOCK_DATE'],
				'BLOCK_REASON' => $limit['BLOCK_REASON'],
			));

			$chat->updateFieldData([Chat::FIELD_SESSION => [
				'ID' => $sessionId,
				'BLOCK_DATE' => $limit['BLOCK_DATE'],
				'BLOCK_REASON' => $limit['BLOCK_REASON'],
			]]);
		}

	}

	/**
	 * Removes a block from the session and chat.
	 *
	 * @param int $sessionId
	 * @param Chat $chat
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function deleteReplyBlock(int $sessionId, Chat $chat): void
	{
		if ($sessionId > 0 && $chat)
		{
			Model\SessionTable::update($sessionId, Array(
				'BLOCK_DATE' => null,
				'BLOCK_REASON' => null,
			));

			$chat->updateFieldData([Chat::FIELD_SESSION => [
				'ID' => $sessionId,
				'BLOCK_DATE' => 0,
				'BLOCK_REASON' => '',
			]]);
		}
	}

	/**
	 * Checks if the ability to respond is blocked.
	 *
	 * @return bool
	 * @throws Main\ObjectException
	 */
	public function isReplyBlocked(): bool
	{
		$sessionData = $this->getData();

		if (!empty($sessionData['BLOCK_DATE']))
		{
			if ($sessionData['BLOCK_DATE'] < new Main\Type\DateTime())
			{
				return true;
			}
		}

		return false;
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
