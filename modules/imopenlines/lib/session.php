<?php
namespace Bitrix\ImOpenLines;

use \Bitrix\Main,
	\Bitrix\Main\Loader,
	\Bitrix\Main\Type\DateTime,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\DB\SqlExpression;

use \Bitrix\Imopenlines\Im\Messages,
	\Bitrix\Imopenlines\Model\TrackerTable;

Loc::loadMessages(__FILE__);

class Session
{
	private $config = Array();
	private $session = Array();
	private $user = Array();
	private $connectorId = '';

	/* @var \Bitrix\ImOpenLines\Chat */
	public $chat = null;

	private $action = 'none';
	public $joinUserId = 0;
	public $joinUserList = Array();
	private $isCreated = false;

	const RULE_TEXT = 'text';
	const RULE_FORM = 'form';
	const RULE_QUEUE = 'queue';
	const RULE_NONE = 'none';

	const CRM_CREATE_LEAD = 'lead';
	const CRM_CREATE_NONE = 'none';

	const ACTION_WELCOME = 'welcome';
	const ACTION_WORKTIME = 'worktime';
	const ACTION_NO_ANSWER = 'no_answer';
	const ACTION_CLOSED = 'closed';
	const ACTION_NONE = 'none';

	const MODE_INPUT = 'input';
	const MODE_OUTPUT = 'output';

	const CACHE_QUEUE = 'queue';
	const CACHE_CLOSE = 'close';
	const CACHE_MAIL = 'mail';
	const CACHE_INIT = 'init';

	/** New dialog opens. */
	const STATUS_NEW = 0;
	/** The operator sent the dialog to the queue. */
	const STATUS_SKIP = 5;
	/** The operator took the dialogue to work. */
	const STATUS_ANSWER = 10;
	/** The client is waiting for the operator's response. */
	const STATUS_CLIENT = 20;
	/** Клиент ожидает ответа оператора (новый вопрос после ответа). */
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
	public function __construct($config = array())
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
			$params['USER_CODE'] = Session\Common::combineUserCode($parsedUserCode);
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
		$result = new Result();

		$result->setResult(false);

		//params
		$this->connectorId = $params['SOURCE'];

		$fields['PARENT_ID'] = intval($params['PARENT_ID']);
		$fields['USER_CODE'] = $params['USER_CODE'];
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
			if(Connector::isLiveChat($fields['SOURCE']))
			{
				$crmManager->setSkipCreate();
				$crmManager->setIgnoreSearchPerson();
			}

			$fields['CRM_TRACE_DATA'] = Crm\Tracker::getTraceData($params['CRM_TRACE_DATA']);
		}
		/* END CRM BLOCK */

		$fields['CHAT_ID'] = $this->chat->getData('ID');

		if ($this->chat->isNowCreated())
		{
			$fields['START_ID'] = 0;
			$fields['IS_FIRST'] = 'Y';

			//$this->chat->join($fields['USER_ID']); ??
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
				$this->session['JOIN_BOT'] = false;
				if ($this->config['WELCOME_BOT_ENABLE'] == 'Y' && $this->config['WELCOME_BOT_ID'] > 0)
				{
					if ($this->config['WELCOME_BOT_JOIN'] == Config::BOT_JOIN_ALWAYS)
					{
						//$this->chat->setUserIdForJoin($fields['USER_ID']);
						$this->session['JOIN_BOT'] = true;
					}
					else if ($this->chat->isNowCreated())
					{
						//$this->chat->setUserIdForJoin($fields['USER_ID']);
						$this->session['JOIN_BOT'] = true;
					}
				}
				else if ($this->chat->isNowCreated())
				{
					$this->action = self::ACTION_WELCOME;
				}

				$firstRoundQueue = true;

				/* QUEUE BLOCK */
				if ($this->config['QUEUE_TYPE'] == Config::QUEUE_TYPE_ALL)
				{
					$queue = $this->getQueue();
					$fields['QUEUE_HISTORY'] = Array();
					$params['USER_LIST'] = $queue['USER_LIST'];

					$this->session['OPERATOR_ID'] = $fields['OPERATOR_ID'] = 0;
				}
				else
				{
					/* CRM AND QUEUE BLOCK */
					if (!Connector::isEnableGroupByChat($fields['SOURCE']) && $this->config['CRM'] == 'Y' && $crmManager->isLoaded() && $this->config['CRM_FORWARD'] == 'Y')
					{
						$crmManager->search();

						$crmOperatorId = $crmManager->getOperatorId();

						if($crmOperatorId > 0)
						{
							$queueManager = new Queue($this->session, $this->config, $this->chat);
							if($queueManager->isActiveCrmUser($crmOperatorId))
							{
								$params['OPERATOR_ID'] = $crmOperatorId;
							}
						}

						$this->session['OPERATOR_ID'] = $fields['OPERATOR_ID'] = $params['OPERATOR_ID'];
					}
					/* END CRM AND QUEUE BLOCK */

					if(empty($params['OPERATOR_ID']))
					{
						$sessionCheckCount = Model\SessionCheckTable::getList(array(
							'select' => array('CNT'),
							'runtime' => array(new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')),
							'filter' => array(
								'!=DATE_QUEUE' => null,
								'SESSION.CONFIG_ID' => $this->config['ID'],
								array(
									'LOGIC' => 'OR',
									array('SESSION.OPERATOR_ID' => null),
									array('SESSION.OPERATOR_ID' => 0)
								)
							),
						))->fetch();

						if($sessionCheckCount['CNT'] > 0)
						{
							$fields['OPERATOR_ID'] = 0;
						}
						else
						{
							$queue = $this->getNextInQueue(false, $this->session['OPERATOR_ID']);
							$fields['OPERATOR_ID'] = $queue['RESULT'] ? $queue['USER_ID'] : 0;
							if($queue['SECOND'] == true)
							{
								$firstRoundQueue = false;
							}
						}
					}

					if (isset($this->session['QUEUE_HISTORY']))
					{
						if(!empty($fields['OPERATOR_ID']) && !empty($this->session['QUEUE_HISTORY'][$fields['OPERATOR_ID']]))
						{
							$fields['QUEUE_HISTORY'] = $this->session['QUEUE_HISTORY'] = array($fields['OPERATOR_ID'] => true);
						}
						else
						{
							$fields['QUEUE_HISTORY'] = $this->session['QUEUE_HISTORY'];
						}
					}
				}
				/* END QUEUE BLOCK */

				/* CLOSED LINE */
				if ($this->config['ACTIVE'] == 'N')
				{
					$this->session['JOIN_BOT'] = false;
					$this->action = self::ACTION_CLOSED;
					$fields['WORKTIME'] = 'N';
					$fields['WAIT_ACTION'] = 'Y';
				}
				/* WORKTIME BLOCK */
				else if ($this->checkWorkTime())
				{
					/* NO ANSWER BLOCK */
					if ((empty($fields['OPERATOR_ID']) || $firstRoundQueue === false) && !$this->session['JOIN_BOT'] && $this->config['QUEUE_TYPE'] != Config::QUEUE_TYPE_ALL)
					{
						if ($this->startNoAnswerRule())
						{
							$fields['WAIT_ACTION'] = 'Y';
						}
					}
				}
				else
				{
					$fields['WORKTIME'] = 'N';
					if ($this->session['JOIN_BOT'])
					{
						$this->action = self::ACTION_NONE;
					}
					else
					{
						$fields['WAIT_ACTION'] = 'Y';
					}
				}

				if ($this->session['JOIN_BOT'])
				{
					$fields['OPERATOR_ID'] = $this->config['WELCOME_BOT_ID'];
				}
				else if ($fields['OPERATOR_ID'])
				{
					$fields['DATE_OPERATOR'] = new DateTime();
					$fields['QUEUE_HISTORY'][$fields['OPERATOR_ID']] = true;
				}

				if (!empty($params['USER_LIST']) && !empty($fields['OPERATOR_ID']))
				{
					$this->joinUserList = array_merge(Array($fields['OPERATOR_ID']), $params['USER_LIST']);
				}
				else if (!empty($params['USER_LIST']) && empty($fields['OPERATOR_ID']))
				{
					$this->joinUserList = $params['USER_LIST'];
				}
				else if ($fields['OPERATOR_ID'])
				{
					$this->joinUserList = Array($fields['OPERATOR_ID']);
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
				$this->chat->sendJoinMessage($this->joinUserList);
				$this->chat->join($this->joinUserList);
				$this->joinUserList = Array();
			}

			$dateClose = new DateTime();
			$dateQueue = null;

			if ($fields['MODE'] == self::MODE_INPUT)
			{
				$dateClose->add('1 MONTH');

				$dateQueue = new DateTime();

				if ($this->config['QUEUE_TYPE'] == Config::QUEUE_TYPE_ALL)
				{
					$queueTime = $this->config['QUEUE_TIME'] > 0 ? $this->config['QUEUE_TIME'] : 60;
					$dateQueue->add($queueTime . ' SECONDS');
				}
				elseif(empty($fields['OPERATOR_ID']))
				{
					$dateQueue->add('60 SECONDS');
				}
				elseif ($this->session['JOIN_BOT'])
				{
					if ($this->config['WELCOME_BOT_TIME'] > 0)
					{
						$dateQueue->add($this->config['WELCOME_BOT_TIME'].' SECONDS');
					}
					else
					{
						$dateQueue = null;
					}
				}
				else if ($fields['WAIT_ACTION'] != 'Y')
				{
					$dateQueue->add($this->config['QUEUE_TIME'].' SECONDS');
				}
			}
			else
			{
				$dateClose->add($this->config['AUTO_CLOSE_TIME'].' SECONDS');
			}

			Model\SessionCheckTable::add(Array(
				'SESSION_ID' => $fields['SESSION_ID'],
				'DATE_CLOSE' => $dateClose,
				'DATE_QUEUE' => $dateQueue,
			));

			$sessionManager = Model\SessionTable::getByIdPerformance($fields['SESSION_ID']);
			$this->session = $sessionManager->fetch();
			$this->session['SESSION_ID'] = $this->session['ID'];

			$this->session['CHECK_DATE_CLOSE'] = $dateClose;
			$this->session['CHECK_DATE_QUEUE'] = $dateQueue;

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

			self::deleteQueueFlagCache();

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

				\Bitrix\Pull\Event::add($this->session['USER_ID'], Array(
					'module_id' => 'imopenlines',
					'command' => 'sessionStart',
					'params' => Array(
						'chatId' => (int)$parsedUserCode['EXTERNAL_CHAT_ID'],
						'sessionId' => (int)$this->session['ID'],
					)
				));
			}
		}
		else
		{
			$result->addErrors($resultAdd->getErrors());
		}

		return $result;
	}

	/**
	 * @param $fields
	 * @param $params
	 * @return Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function readingSession($fields, $params)
	{
		$result = new Result();

		$result->setResult(false);
		$configManager = new Config();

		$loadSession = false;

		$select = Model\SessionTable::getSelectFieldsPerformance();
		$select['CHECK_DATE_CLOSE'] = 'CHECK.DATE_CLOSE';
		$select['CHECK_DATE_QUEUE'] = 'CHECK.DATE_QUEUE';

		$orm = Model\SessionTable::getList([
			'select' => $select,
			'filter' => [
				'=USER_CODE' => $fields['USER_CODE'],
				'=CLOSED' => 'N'
			],
			'order' => ['ID' => 'DESC']
		]);
		while($row = $orm->fetch())
		{
			if (empty($loadSession))
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

			$orm = Model\SessionTable::getList(array(
				'select' => $select,
				'filter' => array(
					'=USER_CODE' => $fields['USER_CODE'],
					'=CLOSED' => 'Y',
				),
				'order' => array('ID' => 'DESC')
			));
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

					Model\SessionTable::update($loadSession['ID'], Array(
						'END_ID' => 0,
						'CLOSED' => 'N',
						'WAIT_ANSWER' => 'N',
						'WAIT_ACTION' => 'Y',
						'PAUSE' => 'N',
						//'STATUS' => self::STATUS_WAIT_CLIENT
					));
					Model\SessionCheckTable::add(Array(
						'SESSION_ID' => $this->session['SESSION_ID'],
						'DATE_CLOSE' => $dateClose
					));

					$this->chat->sendJoinMessage($this->joinUserList);
					$this->chat->join($this->session['OPERATOR_ID'], true, true);
					$this->chat->update(Array('AUTHOR_ID' => $this->session['OPERATOR_ID']));

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
			\Bitrix\Imopenlines\Model\UserRelationTable::add(Array(
				'USER_CODE' => $params['USER_CODE'],
				'USER_ID' => $params['USER_ID']
			));

			$this->chat = new Chat();
			$this->chat->load(Array(
				'USER_CODE' => $params['USER_CODE'],
				'USER_ID' => $params['USER_ID'],
				'LINE_NAME' => $this->config['LINE_NAME'],
				'CONNECTOR' => $params['CONNECTOR'],
			));
			if ($this->chat->isDataLoaded())
			{
				\Bitrix\Imopenlines\Model\UserRelationTable::update($params['USER_CODE'], Array('CHAT_ID' => $this->chat->getData('ID')));

				$resultUserRelation = Array(
					'USER_CODE' => $params['USER_CODE'],
					'USER_ID' => $params['USER_ID'],
					'CHAT_ID' => $this->chat->getData('ID'),
					'AGREES' => 'N',
				);
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

	public function pause($active = true)
	{
		$update = Array(
			'PAUSE' => $active? 'Y': 'N',
			//'DATE_MODIFY' => new DateTime() //TODO: fix 106972
		);
		if ($active == 'Y')
		{
			$update['WAIT_ACTION'] = 'N';
		}
		$this->update($update);

		return true;
	}

	public function markSpam()
	{
		$this->update(Array(
			'SPAM' => 'Y',
			'WAIT_ANSWER' => 'N',
			'DATE_MODIFY' => new DateTime(),
		));
		return true;
	}

	/**
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
		if (empty($this->session))
		{
			return false;
		}

		$update = Array();

		if ($force)
		{
			$this->session['CLOSED'] = 'Y';
			$update['FORCE_CLOSE'] = 'Y';
		}

		if (defined('IMOL_NETWORK_UPDATE') && $this->session['SOURCE'] == 'network')
		{
			$this->config['VOTE_MESSAGE'] = 'N';
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

			$params = Array(
				"CLASS" => "bx-messenger-content-item-ol-end"
			);
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
				\Bitrix\ImOpenLines\Log\Finish::add(
					[
						'SESSION_ID' => $this->session['ID'],
						'SOURCE' => $this->session['SOURCE'],
						'CONFIG_ID' => $this->session['CONFIG_ID'],
					],
					[
						'session' => $this->session,
						'params' => $params,
						'debug' => debug_backtrace()
					]
				);

				$addMessageId = Im::addMessage(Array(
					"TO_CHAT_ID" => $this->session['CHAT_ID'],
					"FROM_USER_ID" => $this->session['OPERATOR_ID'],
					"MESSAGE" => Loc::getMessage('IMOL_SESSION_CLOSE_FINAL'),
					"SYSTEM" => 'Y',
					"RECENT_ADD" => $userViewChat? 'Y': 'N',
					"PARAMS" => $params
				));

				if(!empty($addMessageId))
				{
					$lastMessageId = $addMessageId;
				}
			}

		}
		else
		{
			$enableSystemMessage = Connector::isEnableSendSystemMessage($this->connectorId);
			if ($this->config['ACTIVE'] == 'N')
			{
				$update['WAIT_ACTION'] = 'N';
				$update['WAIT_ANSWER'] = 'N';
				$update['CLOSED'] = 'Y';

				$params = Array(
					"CLASS" => "bx-messenger-content-item-ol-end"
				);
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
					\Bitrix\ImOpenLines\Log\Finish::add(
						[
							'SESSION_ID' => $this->session['ID'],
							'SOURCE' => $this->session['SOURCE'],
							'CONFIG_ID' => $this->session['CONFIG_ID'],
						],
						[
							'session' => $this->session,
							'params' => $params,
							'debug' => debug_backtrace()
						]
					);

					$addMessageId = Im::addMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"FROM_USER_ID" => $this->session['OPERATOR_ID'],
						"RECENT_ADD" => $userViewChat? 'Y': 'N',
						"MESSAGE" => Loc::getMessage('IMOL_SESSION_CLOSE_FINAL'),
						"SYSTEM" => 'Y',
						"PARAMS" => $params
					));

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
					$this->chat->update(Array(
						Chat::getFieldName(Chat::FIELD_SILENT_MODE) => 'N'
					));

					$addMessageId = Im::addMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"FROM_USER_ID" => $this->session['OPERATOR_ID'],
						"MESSAGE" => $this->config['AUTO_CLOSE_TEXT'],
						"SYSTEM" => 'Y',
						"RECENT_ADD" => $userViewChat? 'Y': 'N',
						"IMPORTANT_CONNECTOR" => 'Y',
						"PARAMS" => Array(
							"CLASS" => "bx-messenger-content-item-ol-output",
							"IMOL_FORM" => "history",
							"TYPE" => "lines",
							"COMPONENT_ID" => "bx-imopenlines-message",
						)
					));

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
					$addMessageId = Im::addMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"FROM_USER_ID" => $this->session['OPERATOR_ID'],
						"MESSAGE" => $this->config['VOTE_MESSAGE_2_TEXT'],
						"SYSTEM" => 'Y',
						"RECENT_ADD" => $userViewChat? 'Y': 'N',
						"IMPORTANT_CONNECTOR" => 'Y',
						"PARAMS" => Array(
							"IMOL_VOTE" => $this->session['ID'],
							"IMOL_VOTE_TEXT" => $this->config['VOTE_MESSAGE_1_TEXT'],
							"IMOL_VOTE_LIKE" => $this->config['VOTE_MESSAGE_1_LIKE'],
							"IMOL_VOTE_DISLIKE" => $this->config['VOTE_MESSAGE_1_DISLIKE'],
							"CLASS" => "bx-messenger-content-item-ol-output bx-messenger-content-item-vote",
							"IMOL_FORM" => "history-delay",
							"TYPE" => "lines",
							"COMPONENT_ID" => "bx-imopenlines-message",
						)
					));

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

					$params = Array(
						"CLASS" => "bx-messenger-content-item-ol-end"
					);
					if ($this->config['VOTE_MESSAGE'] == 'Y')
					{
						$params["TYPE"] = "lines";
						$params["COMPONENT_ID"] = "bx-imopenlines-message";
						$params["IMOL_VOTE_SID"] = $this->session['ID'];
						$params["IMOL_VOTE_USER"] = $this->session['VOTE'];
						$params["IMOL_VOTE_HEAD"] = $this->session['VOTE_HEAD'];
						$params["IMOL_COMMENT_HEAD"] = $this->session['COMMENT_HEAD'];
					}
					$addMessageId = Im::addMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"FROM_USER_ID" => $this->session['OPERATOR_ID'],
						"RECENT_ADD" => $userViewChat? 'Y': 'N',
						"MESSAGE" => Loc::getMessage('IMOL_SESSION_CLOSE_AUTO'),
						"SYSTEM" => 'Y',
						"PARAMS" => $params
					));

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
					$addMessageId = Im::addMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"FROM_USER_ID" => $this->session['OPERATOR_ID'],
						"MESSAGE" => $this->config['CLOSE_TEXT'],
						"RECENT_ADD" => $userViewChat? 'Y': 'N',
						"SYSTEM" => 'Y',
						"IMPORTANT_CONNECTOR" => 'Y',
						"PARAMS" => Array(
							"CLASS" => "bx-messenger-content-item-ol-output",
							"IMOL_FORM" => "history",
							"TYPE" => "lines",
							"COMPONENT_ID" => "bx-imopenlines-message",
						)
					));

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
					$addMessageId = Im::addMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"FROM_USER_ID" => $this->session['OPERATOR_ID'],
						"MESSAGE" => $this->config['VOTE_MESSAGE_2_TEXT'],
						"SYSTEM" => 'Y',
						"RECENT_ADD" => $userViewChat? 'Y': 'N',
						"IMPORTANT_CONNECTOR" => 'Y',
						"PARAMS" => Array(
							"IMOL_VOTE" => $this->session['ID'],
							"IMOL_VOTE_TEXT" => $this->config['VOTE_MESSAGE_1_TEXT'],
							"IMOL_VOTE_LIKE" => $this->config['VOTE_MESSAGE_1_LIKE'],
							"IMOL_VOTE_DISLIKE" => $this->config['VOTE_MESSAGE_1_DISLIKE'],
							"CLASS" => "bx-messenger-content-item-ol-output bx-messenger-content-item-vote",
							"IMOL_FORM" => "history-delay",
							"TYPE" => "lines",
							"COMPONENT_ID" => "bx-imopenlines-message",
						)
					));

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

					$params = Array(
						"CLASS" => "bx-messenger-content-item-ol-end"
					);
					if ($this->config['VOTE_MESSAGE'] == 'Y')
					{
						$params["IMOL_VOTE_SID"] = $this->session['ID'];
						$params["IMOL_VOTE_USER"] = $this->session['VOTE'];
						$params["IMOL_VOTE_HEAD"] = $this->session['VOTE_HEAD'];
						$params["IMOL_COMMENT_HEAD"] = $this->session['COMMENT_HEAD'];
					}
					$addMessageId = Im::addMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"FROM_USER_ID" => $this->session['OPERATOR_ID'],
						"RECENT_ADD" => $userViewChat? 'Y': 'N',
						"MESSAGE" => Loc::getMessage('IMOL_SESSION_CLOSE_'.$userSkip->getGender(), Array('#USER#' => '[USER='.$userSkip->getId().']'.$userSkip->getFullName(false).'[/USER]')),
						"SYSTEM" => 'Y',
						"PARAMS" => $params
					));

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
	public function dismissedOperatorFinish()
	{
		if (empty($this->session))
		{
			return false;
		}

		$update = Array();

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

		$this->update($update);

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
		$this->update(Array(
			'WAIT_ANSWER' => $waitAnswer? 'Y': 'N',
			'OPERATOR_ID' => $id,
			'DATE_MODIFY' => new DateTime(),
			'SKIP_DATE_CLOSE' => 'Y'
		));

		$crmManager = new Crm($this);
		if($crmManager->isLoaded())
		{
			$crmManager->setOperatorId($id, $autoMode);
		}

		return true;
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
	public function update($fields)
	{
		$updateCheckTable = Array();
		$updateChatSession = Array();
		$updateDateCrmClose = Array();
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

			$updateCheckTable = Array();

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

			if (Connector::isLiveChat($this->session['SOURCE']))
			{
				$parsedUserCode = Session\Common::parseUserCode($this->session['USER_CODE']);

				\Bitrix\Pull\Event::add($this->session['USER_ID'], Array(
					'module_id' => 'imopenlines',
					'command' => 'sessionFinish',
					'params' => Array(
						'chatId' => (int)$parsedUserCode['EXTERNAL_CHAT_ID'],
						'sessionId' => (int)$this->session['ID'],
						'spam' => $this->session['SPAM'] == 'Y',
					)
				));
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
					($this->session['STATUS'] >= self::STATUS_ANSWER && !in_array($this->session['STATUS'], Array(self::STATUS_CLIENT, self::STATUS_CLIENT_AFTER_OPERATOR)))
					|| ($fields['STATUS'] >= self::STATUS_ANSWER && !in_array($fields['STATUS'], Array(self::STATUS_CLIENT, self::STATUS_CLIENT_AFTER_OPERATOR)))
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

			if(empty($updateCheckTable['DATE_CLOSE']))
			{
				$dateClose = new DateTime();

				$updateCheckTable['DATE_CLOSE'] = $dateClose->add('1 MONTH');
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

		return true;
	}

	public function checkWorkTime()
	{
		$skipSession = false;
		if ($this->config['WORKTIME_ENABLE'] == 'N')
		{
			return true;
		}

		$timezone = !empty($this->config["WORKTIME_TIMEZONE"])? new \DateTimeZone($this->config["WORKTIME_TIMEZONE"]) : null;
		$numberDate = new DateTime(null, null, $timezone);

		if (!empty($this->config['WORKTIME_DAYOFF']))
		{
			$allWeekDays = array('MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 7);
			$currentWeekDay = $numberDate->format('N');
			foreach($this->config['WORKTIME_DAYOFF'] as $day)
			{
				if ($currentWeekDay == $allWeekDays[$day])
				{
					$skipSession = true;
					break;
				}
			}
		}

		if (!$skipSession && !empty($this->config['WORKTIME_HOLIDAYS']))
		{
			$currentDay = $numberDate->format('d.m');
			foreach($this->config['WORKTIME_HOLIDAYS'] as $holiday)
			{
				if ($currentDay == $holiday)
				{
					$skipSession = true;
					break;
				}
			}
		}

		if (!$skipSession)
		{
			$currentTime = $numberDate->format('G.i');

			if (!($currentTime >= $this->config['WORKTIME_FROM'] && $currentTime <= $this->config['WORKTIME_TO']))
			{
				$skipSession = true;
			}
		}

		if ($skipSession)
		{
			$this->action = self::ACTION_WORKTIME;
		}

		return $skipSession? false: true;
	}

	public function execAutoAction($params)
	{
		$update = Array();

		$enableSystemMessage = Connector::isEnableSendSystemMessage($this->connectorId);

		if ($this->action == self::ACTION_WELCOME)
		{
			if ($this->config['WELCOME_MESSAGE'] == 'Y' && $this->session['SOURCE'] != 'network' && $enableSystemMessage)
			{
				Im::addMessage(Array(
					"TO_CHAT_ID" => $this->session['CHAT_ID'],
					"MESSAGE" => $this->config['WELCOME_MESSAGE_TEXT'],
					"SYSTEM" => 'Y',
					"IMPORTANT_CONNECTOR" => 'Y',
					"PARAMS" => Array(
						"CLASS" => "bx-messenger-content-item-ol-output",
					)
				));
			}
		}

		if ($this->action == self::ACTION_CLOSED && $this->config['ACTIVE'] == 'N')
		{
			Im::addMessage(Array(
				"TO_CHAT_ID" => $this->session['CHAT_ID'],
				"MESSAGE" => Loc::getMessage('IMOL_SESSION_LINE_IS_CLOSED'),
				"SYSTEM" => 'Y',
			));
		}
		else if ($enableSystemMessage)
		{
			if ($this->action == self::ACTION_WORKTIME)
			{
				if ($this->config['WORKTIME_DAYOFF_RULE'] == self::RULE_TEXT)
				{
					\Bitrix\ImOpenLines\Log\NoAnswer::add($this->session);

					Im::addMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"MESSAGE" => $this->config['WORKTIME_DAYOFF_TEXT'],
						"SYSTEM" => 'Y',
						"IMPORTANT_CONNECTOR" => 'Y',
						"PARAMS" => Array(
							"CLASS" => "bx-messenger-content-item-ol-output",
							"IMOL_FORM" => "offline",
							"TYPE" => "lines",
							"COMPONENT_ID" => "bx-imopenlines-message",
						)
					));
				}
			}
			else if ($this->action == self::ACTION_NO_ANSWER && $this->session['SEND_NO_ANSWER_TEXT'] != 'Y')
			{
				if ($this->config['NO_ANSWER_RULE'] == self::RULE_TEXT)
				{
					\Bitrix\ImOpenLines\Log\NoAnswer::add($this->session);

					Im::addMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"MESSAGE" => $this->config['NO_ANSWER_TEXT'],
						"SYSTEM" => 'Y',
						"IMPORTANT_CONNECTOR" => 'Y',
						"PARAMS" => Array(
							"CLASS" => "bx-messenger-content-item-ol-output",
							"IMOL_FORM" => "offline",
							"TYPE" => "lines",
							"COMPONENT_ID" => "bx-imopenlines-message",
						)
					));
				}

				$update['SEND_NO_ANSWER_TEXT'] = $this->session['SEND_NO_ANSWER_TEXT'] = 'Y';
			}
		}

		if ($this->chat->isNowCreated() && $this->config['AGREEMENT_MESSAGE'] == 'Y' && $enableSystemMessage)
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
				Im::addMessage(Array(
					"TO_CHAT_ID" => $this->session['CHAT_ID'],
					"MESSAGE" => str_replace(
						Array('#LINK_START#', '#LINK_END#'),
						Array('[URL='.\Bitrix\ImOpenLines\Common::getAgreementLink($this->config['AGREEMENT_ID'], $this->config['LANGUAGE_ID'])."]", '[/URL]'),
						$mess['IMOL_SESSION_AGREEMENT_MESSAGE']
					),
					"SYSTEM" => 'Y',
					"IMPORTANT_CONNECTOR" => 'Y',
					"PARAMS" => Array(
						"CLASS" => "bx-messenger-content-item-ol-output",
					)
				));
				Im::addMessage(Array(
					"TO_CHAT_ID" => $this->session['CHAT_ID'],
					"MESSAGE" => Loc::getMessage('IMOL_SESSION_AGREEMENT_MESSAGE_OPERATOR'),
					"SYSTEM" => 'Y',
					"PARAMS" => Array(
						"CLASS" => "bx-messenger-content-item-ol-attention",
					)
				));
			}
		}

		$update['DATE_MODIFY'] = new DateTime();

		if (is_object($GLOBALS['USER']) && method_exists($GLOBALS['USER'], 'GetId'))
		{
			$update['USER_ID'] = $GLOBALS['USER']->GetId();
		}

		$this->update($update);
	}

	public function getQueue()
	{
		$queue = new Queue($this->session, $this->config, $this->chat);
		$result = $queue->getQueue();

		return $result;
	}

	public function getNextInQueue($manual = false, $currentOperator = 0)
	{
		$queue = new Queue($this->session, $this->config, $this->chat);
		$result = $queue->getNextUser($manual, $currentOperator);

		return $result;
	}

	public function transferToNextInQueue($manual = true)
	{
		$transferToQueue = false;
		if ($this->config['QUEUE_TYPE'] == Config::QUEUE_TYPE_ALL)
		{
			$queue['RESULT'] = false;
			$dateQueue = null;
		}
		else
		{
			$queue = $this->getNextInQueue($manual, $this->session['OPERATOR_ID']);
			$dateQueue = new DateTime();

			if(!empty($queue['USER_ID']))
			{
				$dateQueue->add($this->config['QUEUE_TIME'].' SECONDS');
			}
			else
			{
				$dateQueue->add('60 SECONDS');
			}
		}

		if ($queue['RESULT'])
		{
			if ($queue['USER_ID'] && $this->session['OPERATOR_ID'] != $queue['USER_ID'])
			{
				$this->chat->transfer(Array(
					'FROM' => $this->session['OPERATOR_ID'],
					'TO' => $queue['USER_ID'],
					'MODE' => Chat::TRANSFER_MODE_AUTO,
					'LEAVE' => $this->config['WELCOME_BOT_LEFT'] == Config::BOT_LEFT_CLOSE && \Bitrix\Im\User::getInstance($this->session['OPERATOR_ID'])->isBot()? 'N':'Y'
				));
			}

			if(!empty($this->session['QUEUE_HISTORY'][$queue['USER_ID']]))
			{
				$this->session['QUEUE_HISTORY'] = array($queue['USER_ID'] => true);
			}
			else
			{
				$this->session['QUEUE_HISTORY'][$queue['USER_ID']] = true;
			}

			$update['QUEUE_HISTORY'] = $this->session['QUEUE_HISTORY'];

			//TODO: fix 105666
			if($queue['SECOND'] == true)
			{
				if ($this->startNoAnswerRule())
				{
					if ($this->config['NO_ANSWER_RULE'] != self::RULE_QUEUE)
					{
						$update['WAIT_ACTION'] = 'Y';
					}
					if ($this->config['NO_ANSWER_RULE'] == self::RULE_TEXT && $this->session['SEND_NO_ANSWER_TEXT'] != 'Y' && Connector::isEnableSendSystemMessage($this->connectorId))
					{
						\Bitrix\ImOpenLines\Log\NoAnswer::add($this->session);

						Im::addMessage(Array(
							"TO_CHAT_ID" => $this->session['CHAT_ID'],
							"MESSAGE" => $this->config['NO_ANSWER_TEXT'],
							"SYSTEM" => 'Y',
							"IMPORTANT_CONNECTOR" => 'Y',
							"PARAMS" => Array(
								"CLASS" => "bx-messenger-content-item-ol-output",
								"IMOL_FORM" => "offline",
								"TYPE" => "lines",
								"COMPONENT_ID" => "bx-imopenlines-message",
							)
						));

						$update['SEND_NO_ANSWER_TEXT'] = $this->session['SEND_NO_ANSWER_TEXT'] = 'Y';
					}
				}
			}
		}
		else if ($this->session['WAIT_ACTION'] != 'Y' && $this->config['ACTIVE'] == 'Y')
		{
			if (
				$this->config['QUEUE_TYPE'] != Config::QUEUE_TYPE_ALL
				&& (\Bitrix\Im\User::getInstance($this->session['OPERATOR_ID'])->isBot())
			)
			{
				$this->chat->transfer(Array(
					'FROM' => $this->session['OPERATOR_ID'],
					'TO' => 0,
					'MODE' => Chat::TRANSFER_MODE_AUTO,
					'LEAVE' => $this->config['WELCOME_BOT_LEFT'] == Config::BOT_LEFT_CLOSE && \Bitrix\Im\User::getInstance($this->session['OPERATOR_ID'])->isBot()? 'N':'Y'
				));
			}

			if ($this->startNoAnswerRule())
			{
				if ($this->config['NO_ANSWER_RULE'] != self::RULE_QUEUE)
				{
					$update['WAIT_ACTION'] = 'Y';
				}
				if ($this->config['NO_ANSWER_RULE'] == self::RULE_TEXT && $this->session['SEND_NO_ANSWER_TEXT'] != 'Y' && Connector::isEnableSendSystemMessage($this->connectorId))
				{
					\Bitrix\ImOpenLines\Log\NoAnswer::add($this->session);

					Im::addMessage(Array(
						"TO_CHAT_ID" => $this->session['CHAT_ID'],
						"MESSAGE" => $this->config['NO_ANSWER_TEXT'],
						"SYSTEM" => 'Y',
						"IMPORTANT_CONNECTOR" => 'Y',
						"PARAMS" => Array(
							"CLASS" => "bx-messenger-content-item-ol-output",
							"IMOL_FORM" => "offline",
							"TYPE" => "lines",
							"COMPONENT_ID" => "bx-imopenlines-message",
						)
					));

					$update['SEND_NO_ANSWER_TEXT'] = $this->session['SEND_NO_ANSWER_TEXT'] = 'Y';
				}
			}
			else if ($this->config['NO_ANSWER_RULE'] == self::RULE_QUEUE && $manual)
			{
				Im::addMessage(Array(
					"TO_CHAT_ID" => $this->session['CHAT_ID'],
					'MESSAGE' => Loc::getMessage('IMOL_SESSION_SKIP_ALONE'),
					'SYSTEM' => 'Y',
					'SKIP_COMMAND' => 'Y'
				));
			}
		}
		else
		{
			if ($manual)
			{
				Im::addMessage(Array(
					"TO_CHAT_ID" => $this->session['CHAT_ID'],
					'MESSAGE' => Loc::getMessage('IMOL_SESSION_SKIP_ALONE'),
					'SYSTEM' => 'Y',
					'SKIP_COMMAND' => 'Y'
				));
			}
			else
			{
				if ($this->session['OPERATOR_ID'] != 0 && $this->config['QUEUE_TYPE'] != Config::QUEUE_TYPE_ALL)
				{
					$this->chat->transfer(Array(
						'FROM' => $this->session['OPERATOR_ID'],
						'TO' => 0,
						'MODE' => Chat::TRANSFER_MODE_AUTO,
						'LEAVE' => $this->config['WELCOME_BOT_LEFT'] == Config::BOT_LEFT_CLOSE && \Bitrix\Im\User::getInstance($this->session['OPERATOR_ID'])->isBot()? 'N':'Y'
					));
				}
			}

			$update['QUEUE_HISTORY'] = [];
		}

		Model\SessionCheckTable::update($this->session['ID'], Array(
			'DATE_QUEUE' => $dateQueue
		));

		$update['DATE_MODIFY'] = new DateTime();
		$update['SKIP_DATE_CLOSE'] = 'Y';

		$this->update($update);

		if ($transferToQueue)
		{
			self::transferToNextInQueue(true);
		}
	}

	public function startNoAnswerRule()
	{
		$finalize = false;
		if ($this->config['NO_ANSWER_RULE'] != self::RULE_QUEUE)
		{
			$this->action = self::ACTION_NO_ANSWER;
			$finalize = true;
		}
		return $finalize;
	}

	private static function prolongDueChatActivity($chatId)
	{
		$orm = Model\SessionTable::getList(array(
			'select' => Array(
				'ID',
				'CHECK_DATE_CLOSE' => 'CHECK.DATE_CLOSE'
			),
			'filter' => array(
				'=CHAT_ID' => $chatId,
				'=CLOSED' => 'N'
			)
		));

		if ($result = $orm->fetch())
		{
			$currentDate = new DateTime();
			if ($result['CHECK_DATE_CLOSE'] && $currentDate->getTimestamp()+600 > $result['CHECK_DATE_CLOSE']->getTimestamp())
			{
				$dateClose = $result['CHECK_DATE_CLOSE']->add('10 MINUTES');
				Model\SessionCheckTable::update($result['ID'], Array(
					'DATE_CLOSE' => $dateClose
				));
			}
		}
	}

	public static function setQueueFlagCache($type = "")
	{
		if (!$type)
			return false;

		$app = \Bitrix\Main\Application::getInstance();
		$managedCache = $app->getManagedCache();
		$managedCache->clean("imol_queue_flag_".$type);
		$managedCache->read(86400*30, "imol_queue_flag_".$type);
		$managedCache->set("imol_queue_flag_".$type, true);

		return true;
	}

	public static function deleteQueueFlagCache($type = "")
	{
		$app = \Bitrix\Main\Application::getInstance();
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
		}

		return true;
	}

	public static function getQueueFlagCache($type = "")
	{
		if (!$type)
			return false;

		$app = \Bitrix\Main\Application::getInstance();
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

	public function joinUser()
	{
		if (!empty($this->joinUserList))
		{
			Log::write($this->joinUserList, 'DEFFERED JOIN');
			$this->chat->sendJoinMessage($this->joinUserList);
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
		return \Bitrix\ImOpenLines\Session\Agent::transferToNextInQueue($nextExec, $offset);
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
		return \Bitrix\ImOpenLines\Session\Agent::closeByTime($nextExec);
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
		return \Bitrix\ImOpenLines\Session\Agent::mailByTime($nextExec);
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
	public static function dismissedOperatorAgent($nextExec)
	{
		return \Bitrix\ImOpenLines\Session\Agent::dismissedOperator($nextExec);
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

}