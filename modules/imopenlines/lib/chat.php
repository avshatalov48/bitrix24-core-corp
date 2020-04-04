<?php
namespace Bitrix\ImOpenLines;

use \Bitrix\Main,
	\Bitrix\Main\Event,
	\Bitrix\Main\Type\DateTime,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\Im\User as ImUser,
	\Bitrix\Im\Model\ChatTable;

use \Bitrix\Pull;

use \Bitrix\Im\Model\RelationTable,
	\Bitrix\ImOpenLines\Tools\DbNameLock,
	\Bitrix\ImOpenlines\Model\ConfigTable,
	\Bitrix\ImOpenLines\Model\OperatorTransferTable;

Loc::loadMessages(__FILE__);

class Chat
{
	const FIELD_SESSION = 'LINES_SESSION';
	const FIELD_CRM = 'LINES_CRM';
	const FIELD_LIVECHAT = 'LIVECHAT_SESSION';

	const FIELD_SILENT_MODE = 'LINES_SILENT_MODE';

	const RATING_TYPE_CLIENT = 'CLIENT';
	const RATING_TYPE_HEAD = 'HEAD';
	const RATING_TYPE_COMMENT = 'COMMENT';
	const RATING_TYPE_HEAD_AND_COMMENT = 'HEAD_AND_COMMENT';
	const RATING_VALUE_LIKE = '5';
	const RATING_VALUE_DISLIKE = '1';

	public static $fieldAssoc = Array(
		'LINES_SESSION' => 'ENTITY_DATA_1',
		'LINES_CRM' => 'ENTITY_DATA_2',
		'LIVECHAT_SESSION' => 'ENTITY_DATA_1',
	);

	const TRANSFER_MODE_AUTO = 'AUTO';
	const TRANSFER_MODE_MANUAL = 'MANUAL';
	const TRANSFER_MODE_BOT = 'BOT';

	const TEXT_WELCOME = 'WELCOME';
	const TEXT_DEFAULT = 'DEFAULT';

	const CHAT_TYPE_OPERATOR = 'LINES';
	const CHAT_TYPE_CLIENT = 'LIVECHAT';

	private $error = null;
	private $moduleLoad = false;
	private $isCreated = false;
	private $isDataLoaded = false;
	private $joinByUserId = 0;
	private $chat = [];

	const IMOL_CRM_ERROR_NOT_GIVEN_CORRECT_DATA = 'IMOL CRM ERROR NOT GIVEN THE CORRECT DATA';

	public function __construct($chatId = 0, $params = Array())
	{
		$imLoad = \Bitrix\Main\Loader::includeModule('im');
		$pullLoad = \Bitrix\Main\Loader::includeModule('pull');
		if ($imLoad && $pullLoad)
		{
			$this->error = new BasicError(null, '', '');
			$this->moduleLoad = true;
		}
		else
		{
			if (!$imLoad)
			{
				$this->error = new BasicError(__METHOD__, 'IM_LOAD_ERROR', Loc::getMessage('IMOL_CHAT_ERROR_IM_LOAD'));
			}
			elseif (!$pullLoad)
			{
				$this->error = new BasicError(__METHOD__, 'PULL_LOAD_ERROR', Loc::getMessage('IMOL_CHAT_ERROR_PULL_LOAD'));
			}
		}

		if ($imLoad)
		{
			$chatId = intval($chatId);
			if ($chatId > 0)
			{
				$chat = \Bitrix\Im\Model\ChatTable::getById($chatId)->fetch();
				if (!empty($chat) && in_array($chat['ENTITY_TYPE'], Array(self::CHAT_TYPE_OPERATOR, self::CHAT_TYPE_CLIENT)))
				{
					if (isset($params['CONNECTOR']['chat']['description']) && $chat['DESCRIPTION'] != $params['CONNECTOR']['chat']['description'])
					{
						$chatManager = new \CIMChat(0);
						$chatManager->SetDescription($chat['ID'], $params['CONNECTOR']['chat']['description']);
						$chat['DESCRIPTION'] = $params['CONNECTOR']['chat']['description'];
					}

					$this->chat = $chat;
					$this->isDataLoaded = true;
				}
			}
		}
	}

	private function isModuleLoad()
	{
		return $this->moduleLoad;
	}

	public function load($params)
	{
		if (!$this->isModuleLoad())
		{
			return false;
		}
		$orm = \Bitrix\Im\Model\ChatTable::getList(array(
			'filter' => array(
				'=ENTITY_TYPE' => 'LINES',
				'=ENTITY_ID' => $params['USER_CODE']
			),
			'limit' => 1
		));
		if($chat = $orm->fetch())
		{
			if (isset($params['CONNECTOR']['chat']['description']) && $chat['DESCRIPTION'] != $params['CONNECTOR']['chat']['description'])
			{
				$chatManager = new \CIMChat(0);
				$chatManager->SetDescription($chat['ID'], $params['CONNECTOR']['chat']['description']);
				$chat['DESCRIPTION'] = $params['CONNECTOR']['chat']['description'];
			}
			$this->chat = $chat;

			$this->isDataLoaded = true;
			return true;
		}
		else if ($params['ONLY_LOAD'] == 'Y')
		{
			return false;
		}

		$parsedUserCode = Session\Common::parseUserCode($params['USER_CODE']);
		$connectorId = $parsedUserCode['CONNECTOR_ID'];

		$avatarId = 0;
		$userName = '';
		$chatColorCode = '';
		$addChat['USERS'] = false;
		if ($params['USER_ID'])
		{
			$orm = \Bitrix\Main\UserTable::getById($params['USER_ID']);
			if ($user = $orm->fetch())
			{
				if ($user['PERSONAL_PHOTO'] > 0)
				{
					$avatarId = $user['PERSONAL_PHOTO'];
				}
				$addChat['USERS'] = Array($params['USER_ID']);

				if ($connectorId != 'livechat' || !empty($user['NAME']))
				{
					$userName = ImUser::getInstance($params['USER_ID'])->getFullName(false);
				}
				$chatColorCode = \Bitrix\Im\Color::getCodeByNumber($params['USER_ID']);
				if (ImUser::getInstance($params['USER_ID'])->getGender() == 'M')
				{
					$replaceColor = \Bitrix\Im\Color::getReplaceColors();
					if (isset($replaceColor[$chatColorCode]))
					{
						$chatColorCode = $replaceColor[$chatColorCode];
					}
				}
			}
		}

		$description = '';
		if (isset($params['CONNECTOR']['chat']['description']))
		{
			$description = trim($params['CONNECTOR']['chat']['description']);
		}

		$titleParams = $this->getTitle($params['LINE_NAME'], $userName, $chatColorCode);

		$addChat['TYPE'] = IM_MESSAGE_OPEN_LINE;
		$addChat['AVATAR_ID'] = $avatarId;
		$addChat['TITLE'] = $titleParams['TITLE'];
		$addChat['COLOR'] = $titleParams['COLOR'];
		$addChat['DESCRIPTION'] = $description;
		$addChat['ENTITY_TYPE'] = 'LINES';
		$addChat['ENTITY_ID'] = $params['USER_CODE'];
		$addChat['SKIP_ADD_MESSAGE'] = 'Y';

		$chat = new \CIMChat(0);
		$id = $chat->Add($addChat);
		if (!$id)
		{
			return false;
		}

		$orm = \Bitrix\Im\Model\ChatTable::getById($id);
		$this->chat = $orm->fetch();
		$this->isCreated = true;
		$this->isDataLoaded = true;

		return true;
	}

	/**
	 * @param $userId
	 * @param bool $skipSession
	 * @param bool $skipMessage
	 * @return Result
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function answer($userId, $skipSession = false, $skipMessage = false)
	{
		$result = new Result();
		$result->setResult(true);
		$answerTag = 'answer_'.$this->chat['ID'];
		$session = null;

		if ($this->chat['AUTHOR_ID'] == $userId)
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CHAT_ERROR_ANSWER_ALREADY_RESPONSIBLE'), 'IMOL_CHAT_ERROR_ANSWER_ALREADY_RESPONSIBLE', __METHOD__, ['$userId' => $userId]));
		}

		if(!$this->isDataLoaded())
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CHAT_ERROR_NOT_LOAD_DATA'), 'IMOL_CHAT_ERROR_NOT_LOAD_DATA', __METHOD__, ['chat' => $this->chat]));
		}

		if($result->isSuccess())
		{
			if(DbNameLock::set($answerTag))
			{
				$chat = new \CIMChat(0);
				$relations = \CIMChat::GetRelationById($this->chat['ID']);

				if (!isset($relations[$userId]))
				{
					$chat->AddUser($this->chat['ID'], $userId, false, true);
				}

				if ($skipSession)
				{
					DbNameLock::delete($answerTag);
				}
				else
				{
					$session = new Session();
					$resultLoad = $session->load([
						'USER_CODE' => $this->chat['ENTITY_ID'],
						'MODE' => Session::MODE_OUTPUT,
						'OPERATOR_ID' => $userId
					]);
					if (!$resultLoad)
					{
						DbNameLock::delete($answerTag);
						$result->setResult(false);
						$result->addError(new Error(Loc::getMessage('IMOL_CHAT_ERROR_ANSWER_NOT_LOAD_SESSION'), 'IMOL_CHAT_ERROR_ANSWER_NOT_LOAD_SESSION', __METHOD__, ['USER_CODE' => $this->chat['ENTITY_ID'], 'MODE' => Session::MODE_OUTPUT, 'OPERATOR_ID' => $userId]));
					}
					else
					{
						if($this->validationAction($session->getData('CHAT_ID')))
						{
							if($session->isNowCreated())
							{
								DbNameLock::delete($answerTag);
							}
						}
						else
						{
							$result->addError(new Error(Loc::getMessage('IMOL_CHAT_ERROR_NOT_LOAD_DATA'), 'IMOL_CHAT_ERROR_NOT_LOAD_DATA', __METHOD__, ['chat' => $this->chat]));
						}
					}
				}

				if($result->isSuccess())
				{
					$config = [];

					if ($skipSession)
					{
						list(, $lineId) = explode('|', $this->chat['ENTITY_ID']);
						$configManager = new Config();
						$config = $configManager->get($lineId);
					}
					else if ($session && !$session->isNowCreated())
					{
						$session->setOperatorId($userId, false, false);

						if ($session->getData('CRM_ACTIVITY_ID') > 0)
						{
							$closeDate = new Main\Type\DateTime();
							$closeDate->add(intval($session->getConfig('AUTO_CLOSE_TIME')).' SECONDS');
							$closeDate->add('1 DAY');

							$crmManager = new Crm($session);
							if($crmManager->isLoaded())
							{
								$crmManager->setSessionAnswered(['DATE_CLOSE' => $closeDate]);
							}
						}

						$sessionUpdate = Array(
							'OPERATOR_ID' => $userId,
							'WAIT_ACTION' => 'N',
							'WAIT_ANSWER' => 'N',
							'SEND_NO_ANSWER_TEXT' => 'Y'
						);
						if (!ImUser::getInstance($userId)->isBot() && $session->getData('DATE_OPERATOR_ANSWER') <= 0)
						{
							$currentDate = new DateTime();
							$sessionUpdate['DATE_OPERATOR_ANSWER'] = $currentDate;
							$sessionUpdate['TIME_ANSWER'] = $currentDate->getTimestamp()-$session->getData('DATE_CREATE')->getTimestamp();
						}

						$session->update($sessionUpdate);
						$config = $session->getConfig();
					}
					else if ($session)
					{
						$config = $session->getConfig();
					}

					$relations = \CIMChat::GetRelationById($this->chat['ID']);
					foreach ($relations as $relation)
					{
						if(
							!ImUser::getInstance($relation['USER_ID'])->isConnector() &&
							!ImUser::getInstance($relation['USER_ID'])->isBot() &&
							$userId != $relation['USER_ID']
						)
						{
							$chat->DeleteUser($this->chat['ID'], $relation['USER_ID'], false, true);
						}
					}

					$this->update(Array(
						'AUTHOR_ID' => $userId
					));

					Pull\Event::add($userId, Array(
						'module_id' => 'imopenlines',
						'command' => 'linesAnswer',
						'params' => Array(
							'chatId' => $this->chat['ID']
						)
					));

					if (!$skipMessage)
					{
						$userAnswer = ImUser::getInstance($userId);

						Im::addMessage(Array(
							"FROM_USER_ID" => $userId,
							"TO_CHAT_ID" => $this->chat['ID'],
							"MESSAGE" => Loc::getMessage('IMOL_CHAT_ANSWER_'.$userAnswer->getGender(), Array('#USER#' => '[USER='.$userAnswer->getId().']'.$userAnswer->getFullName(false).'[/USER]')),
							"SYSTEM" => 'Y',
						));
					}

					DbNameLock::delete($answerTag);

					if ($session)
					{
						$eventData = array(
							'RUNTIME_SESSION' => $session,
							'USER_ID' => $userId,
						);
						$event = new Event("imopenlines", "OnChatAnswer", $eventData);
						$event->send();
					}
				}
			}
			else
			{
				$result->addError(new Error(Loc::getMessage('IMOL_CHAT_ERROR_ANSWER_COMPETITIVE_REQUEST'), 'IMOL_CHAT_ERROR_ANSWER_COMPETITIVE_REQUEST', __METHOD__, ['$userId' => $userId]));
			}
		}

		return $result;
	}

	/**
	 * @param $userId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function intercept($userId)
	{
		$result = false;

		if(
			$this->isDataLoaded() &&
			$this->chat['AUTHOR_ID'] > 0 &&
			$this->chat['AUTHOR_ID'] != $userId
		)
		{
			$previousOwnerId = $this->chat['AUTHOR_ID'];

			$resultAnswer = $this->answer($userId, false, true);

			if($resultAnswer->isSuccess())
			{
				$previousOwner = ImUser::getInstance($previousOwnerId);
				$newOwner = ImUser::getInstance($userId);

				\CIMChat::AddMessage(Array(
					"FROM_USER_ID" => $userId,
					"TO_CHAT_ID" => $this->chat['ID'],
					"MESSAGE" => Loc::getMessage('IMOL_CHAT_INTERCEPT_'.$newOwner->getGender(), Array(
						'#USER_1#' => '[USER='.$newOwner->getId().']'.$newOwner->getFullName(false).'[/USER]',
						'#USER_2#' => '[USER='.$previousOwner->getId().']'.$previousOwner->getFullName(false).'[/USER]'
					)),
					"SYSTEM" => 'Y',
				));

				$result = true;
			}
		}

		return $result;
	}

	/**
	 * Skip the dialogue.
	 *
	 * @param int $userId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function skip($userId = 0)
	{
		$result = false;

		if($this->isDataLoaded())
		{
			$session = new Session();
			$resultLoad = $session->load(Array(
				'USER_CODE' => $this->chat['ENTITY_ID'],
				'SKIP_CREATE' => 'Y'
			));
			if ($resultLoad)
			{
				if($this->validationAction($session->getData('CHAT_ID')))
				{
					$eventData = array(
						'RUNTIME_SESSION' => $session,
						'USER_ID' => $userId,
					);
					$event = new Event("imopenlines", "OnChatSkip", $eventData);
					$event->send();

					if ($userId)
					{
						$userSkip = ImUser::getInstance($userId);

						Im::addMessage(Array(
							"FROM_USER_ID" => $userId,
							"TO_CHAT_ID" => $this->chat['ID'],
							"MESSAGE" => Loc::getMessage('IMOL_CHAT_SKIP_'.$userSkip->getGender(), Array('#USER#' => '[USER='.$userSkip->getId().']'.$userSkip->getFullName(false).'[/USER]')),
							"SYSTEM" => 'Y',
						));
					}

					$result = $session->transferToNextInQueue();
				}
			}
			else
			{
				if ($userId > 0)
				{
					$chat = new \CIMChat();
					$chat->DeleteUser($this->chat['ID'], $userId, false);
				}
			}
		}

		return $result;
	}

	/**
	 * The end of the session of the bot.
	 *
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function endBotSession()
	{
		$result = false;

		if($this->isDataLoaded())
		{
			$session = new Session();
			$resultLoadSession = $session->load(Array(
				'USER_CODE' => $this->chat['ENTITY_ID']
			));
			if ($resultLoadSession && ImUser::getInstance($session->getData('OPERATOR_ID'))->isBot())
			{
				if($this->validationAction($session->getData('CHAT_ID')))
				{
					$chat = new \CIMChat(0);
					if ($session->getConfig('WELCOME_BOT_LEFT') != Config::BOT_LEFT_CLOSE)
					{
						$chat->DeleteUser($this->chat['ID'], $session->getData('OPERATOR_ID'), false, true);
					}
					else
					{
						$chat->SetOwner($this->chat['ID'], 0);
					}

					$session->transferToNextInQueue();

					Im::addMessage(Array(
						"TO_CHAT_ID" => $this->chat['ID'],
						"MESSAGE" => Loc::getMessage('IMOL_CHAT_TO_QUEUE_NEW'),
						"SYSTEM" => 'Y',
					));

					$result =  true;
				}
			}
		}

		return $result;
	}

	/**
	 * @param $userId
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function waitAnswer($userId)
	{
		$this->update([
			'AUTHOR_ID' => $userId,
			self::getFieldName(self::FIELD_SILENT_MODE) => 'N'
		]);
	}

	/**
	 * @param $params
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function transfer($params)
	{
		$result = false;

		if($this->isDataLoaded())
		{
			$keyLock = Queue\Queue::PREFIX_KEY_LOCK . $this->chat['ID'];

			if(Tools\Lock::getInstance()->set($keyLock))
			{
				$mode = in_array($params['MODE'], [self::TRANSFER_MODE_AUTO, self::TRANSFER_MODE_BOT])? $params['MODE']: self::TRANSFER_MODE_MANUAL;
				$selfExit = isset($params['LEAVE']) && $params['LEAVE'] == 'N'? false: true;
				$skipCheck = isset($params['SKIP_CHECK']) && $params['SKIP_CHECK'] == 'Y';

				$session = new Session();
				$resultLoadSession = $session->load([
					'USER_CODE' => $this->chat['ENTITY_ID']
				]);
				if ($resultLoadSession)
				{
					if($this->validationAction($session->getData('CHAT_ID')))
					{
						//Event
						$event = new Event("imopenlines", "OnOperatorTransfer", [
							'CHAT' => $this->chat,
							'SESSION' => $session,
							'TRANSFER' => [
								'MODE' => $mode,
								'FROM' => $params['FROM'],
								'TO' => $params['TO'],
							]
						]);
						$event->send();

						foreach($event->getResults() as $eventResult)
						{
							if ($eventResult->getType() != \Bitrix\Main\EventResult::SUCCESS)
								continue;

							$newValues = $eventResult->getParameters();
							if (!empty($newValues['TRANSFER_ID']))
							{
								$params['TO'] = $newValues['TRANSFER_ID'];
							}
						}
						// END Event

						//Transfer to queue
						if (substr($params['TO'], 0, 5) == 'queue')
						{
							$result = $this->transferToQueue($params, $session, $mode);
						}
						//Transfer to the operator
						else
						{
							$result = $this->transferToOperator($params, $session, $mode, $selfExit, $skipCheck);
						}
					}
				}

				Tools\Lock::getInstance()->delete($keyLock);
			}
		}

		return $result;
	}

	/**
	 * Transfer to queue.
	 *
	 * @param $params
	 * @param Session $session
	 * @param $mode
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function transferToQueue($params, $session, $mode)
	{
		$result = false;

		if($this->isDataLoaded())
		{
			$userFrom = ImUser::getInstance($params['FROM']);

			$lineFromId = $session->getConfig('ID');
			$lineFrom = $session->getConfig('LINE_NAME');
			$userCode = $session->getData('USER_CODE');

			//Transfer the same queue
			if($params['TO']  == 'queue')
			{
				$queueId = $lineFromId;
			}
			else
			{
				$queueId = intval(substr($params['TO'], 5));
			}

			$config = ConfigTable::getById($queueId)->fetch();
			if ($config)
			{
				$session->setOperatorId(0, true, $mode ==  self::TRANSFER_MODE_MANUAL ? false : true);
				$this->update(['AUTHOR_ID' => 0]);
				$this->updateFieldData([Chat::FIELD_SESSION => ['LINE_ID' => $queueId]]);

				$chat = new \CIMChat(0);
				$relations = \CIMChat::GetRelationById($this->chat['ID']);
				foreach ($relations as $relation)
				{
					if (ImUser::getInstance($relation['USER_ID'])->isConnector())
						continue;

					if (ImUser::getInstance($relation['USER_ID'])->isBot())
						continue;

					$chat->DeleteUser($this->chat['ID'], $relation['USER_ID'], false, true);
				}

				if($queueId == 0)
				{
					$queueId = $lineFromId;
				}

				Log::write([$params['FROM'], $queueId], 'TRANSFER TO LINE');

				OperatorTransferTable::Add([
					'CONFIG_ID' => $session->getData('CONFIG_ID'),
					'SESSION_ID' => $session->getData('ID'),
					'USER_ID' => $params['FROM'],
					'TRANSFER_MODE' => $mode,
					'TRANSFER_TYPE' => 'QUEUE',
					'TRANSFER_LINE_ID' => $queueId
				]);

				$updateData = [
					'CONFIG_ID' => $queueId,
					'QUEUE_HISTORY' => [],
					'SKIP_DATE_CLOSE' => true,
					'DATE_MODIFY' => new DateTime(),
					'OPERATOR_FROM_CRM' => 'N'
				];

				if ($userFrom->isBot() && !$session->getData('DATE_OPERATOR'))
				{
					$currentDate = new DateTime();
					$updateData['DATE_OPERATOR'] = $currentDate;
					$updateData['TIME_BOT'] = $currentDate->getTimestamp() - $session->getData('DATE_CREATE')->getTimestamp();
				}

				$session->update($updateData);

				$lineTo = $session->getConfig('LINE_NAME');

				if($params['TO']  == 'queue')
				{
					$message = Loc::getMessage('IMOL_CHAT_RETURNED_TO_QUEUE_NEW');
				}
				else if ($lineFromId == $queueId)
				{
					$message = Loc::getMessage('IMOL_CHAT_SKIP_'.$userFrom->getGender(), [
						'#USER#' => '[USER='.$userFrom->getId().']'.$userFrom->getFullName(false).'[/USER]',
					]);
				}
				else
				{
					$message = Loc::getMessage('IMOL_CHAT_TRANSFER_LINE_'.$userFrom->getGender(), [
						'#USER_FROM#' => '[USER='.$userFrom->getId().']'.$userFrom->getFullName(false).'[/USER]',
						'#LINE_FROM#' => '[b]'.$lineFrom.'[/b]',
						'#LINE_TO#' => '[b]'.$lineTo.'[/b]',
					]);
				}

				$session->transferToNextInQueue(false);

				Im::addMessage([
					"TO_CHAT_ID" => $this->chat['ID'],
					"MESSAGE" => $message,
					"SYSTEM" => 'Y',
				]);

				$result = true;
			}
		}

		return $result;
	}

	/**
	 * Transfer to the operator.
	 *
	 * @param $params
	 * @param Session $session
	 * @param $mode
	 * @param $selfExit
	 * @param $skipCheck
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function transferToOperator($params, $session, $mode, $selfExit, $skipCheck)
	{
		$result = false;

		if($this->isDataLoaded())
		{
			$transferUserId = intval($params['TO']);

			if($skipCheck ||
				(
					!ImUser::getInstance($transferUserId)->isBot() &&
					!ImUser::getInstance($transferUserId)->isExtranet() &&
					!ImUser::getInstance($transferUserId)->isConnector()
				))
			{
				$chat = new \CIMChat(0);
				$relations = \CIMChat::GetRelationById($this->chat['ID']);
				foreach ($relations as $relation)
				{
					if(
						!ImUser::getInstance($relation['USER_ID'])->isConnector() &&
						!ImUser::getInstance($relation['USER_ID'])->isBot() &&
						$relation['USER_ID'] != $params['FROM']
					)
					{
						$chat->DeleteUser($this->chat['ID'], $relation['USER_ID'], false, true);
					}
				}

				if($params['FROM'] > 0 && $selfExit)
				{
					$chat->DeleteUser($this->chat['ID'], $params['FROM'], false, true);
				}

				if ($session->getConfig('ACTIVE') == 'Y')
				{
					$this->update(Array('AUTHOR_ID' => 0));
				}
				else
				{
					$this->update(Array('AUTHOR_ID' => $transferUserId));
				}
				if($transferUserId > 0)
				{
					$chat->AddUser($this->chat['ID'], $transferUserId, false, true);
				}

				$userFrom = ImUser::getInstance($params['FROM']);
				if($transferUserId > 0)
				{
					$userTo = ImUser::getInstance($transferUserId);
				}

				Log::write(Array($params['FROM'], $transferUserId), 'TRANSFER TO USER');

				if ($transferUserId > 0 && $params['FROM'] > 0 && ($mode == self::TRANSFER_MODE_MANUAL || $mode == self::TRANSFER_MODE_BOT))
				{
					$message = Loc::getMessage('IMOL_CHAT_TRANSFER_'.$userFrom->getGender(), [
							'#USER_FROM#' => '[USER='.$userFrom->getId().']'.$userFrom->getFullName(false).'[/USER]',
							'#USER_TO#' => '[USER='.$userTo->getId().']'.$userTo->getFullName(false).'[/USER]']
					);
				}
				else if(empty($transferUserId))
				{
					$message = Loc::getMessage('IMOL_CHAT_NO_OPERATOR_AVAILABLE_IN_QUEUE_NEW');
				}
				else
				{
					$message = Loc::getMessage('IMOL_CHAT_NEXT_IN_QUEUE_NEW', ['#USER_TO#' => '[USER='.$userTo->getId().']'.$userTo->getFullName(false).'[/USER]']);
				}

				OperatorTransferTable::Add([
					'CONFIG_ID' => $session->getData('CONFIG_ID'),
					'SESSION_ID' => $session->getData('ID'),
					'USER_ID' => intval($params['FROM']),
					'TRANSFER_MODE' => $mode,
					'TRANSFER_TYPE' => 'USER',
					'TRANSFER_USER_ID' => $transferUserId
				]);

				Im::addMessage([
					"TO_CHAT_ID" => $this->chat['ID'],
					"MESSAGE" => $message,
					"SYSTEM" => 'Y',
				]);

				$updateDataSession = [
					'OPERATOR_FROM_CRM' => 'N'
				];

				if ($userFrom->isBot() && !$session->getData('DATE_OPERATOR'))
				{
					$currentDate = new DateTime();
					$updateDataSession['DATE_OPERATOR'] = $currentDate;
					$updateDataSession['TIME_BOT'] = $currentDate->getTimestamp()-$session->getData('DATE_CREATE')->getTimestamp();
				}

				if ($mode == self::TRANSFER_MODE_MANUAL)
				{
					$this->answer($transferUserId, false, true);
					$updateDataSession['DATE_MODIFY'] = new DateTime();
					$updateDataSession['SKIP_DATE_CLOSE'] = true;
				}
				else
				{
					$session->setOperatorId($transferUserId, true, true);
				}

				$session->update($updateDataSession);

				$result = true;
			}
		}

		return $result;
	}

	/**
	 * Set the chat operators who see the chat.
	 *
	 * @param array $users
	 * @param bool $skipMessage
	 * @param bool $skipRecent
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function setOperators($users = [], $skipMessage = true, $skipRecent = false): bool
	{
		$result = false;

		if($this->isDataLoaded() && is_array($users))
		{
			$users = array_unique($users);

			$delete = [];
			$relationList = RelationTable::getList([
				"select" => ["ID", "USER_ID"],
				"filter" => [
					"=CHAT_ID" => $this->chat['ID']
				],
			]);

			while ($relation = $relationList->fetch())
			{
				if(Queue::isRealOperator($relation['USER_ID']))
				{
					if(!in_array($relation['USER_ID'], $users))
					{
						$delete[] = $relation['USER_ID'];
					}
				}
			}

			if(!empty($users) || !empty($delete))
			{
				$chat = new \CIMChat($this->joinByUserId);

				if(!empty($users))
				{
					$result = $chat->AddUser($this->chat['ID'], $users, false, $skipMessage, $skipRecent);
					self::chatAutoAddRecent($this->chat['ID'], $users);
				}
				if(!empty($delete))
				{
					foreach ($delete as $userId)
					{
						$result = $chat->DeleteUser($this->chat['ID'], $userId, false, $skipMessage);
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Adding a user to the chat.
	 *
	 * @param $userId
	 * @param bool $skipMessage
	 * @param bool $skipRecent
	 * @return bool
	 */
	public function join($userId, $skipMessage = true, $skipRecent = false)
	{
		$result = false;

		if($this->isDataLoaded() && !empty($userId))
		{
			$chat = new \CIMChat($this->joinByUserId);
			$result = $chat->AddUser($this->chat['ID'], $userId, false, $skipMessage, $skipRecent);
		}

		return $result;
	}

	/**
	 * To exclude a user from chat.
	 *
	 * @param $userId
	 * @return bool
	 */
	public function leave($userId)
	{
		$result = false;

		if ($this->isDataLoaded())
		{
			$chat = new \CIMChat(0);
			$result = $chat->DeleteUser($this->chat['ID'], $userId, false, true);
		}

		return $result;
	}

	/**
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function close()
	{
		$result = false;

		if($this->isDataLoaded())
		{
			$relationList = RelationTable::getList(array(
				"select" => array("ID", "USER_ID", "EXTERNAL_AUTH_ID" => "USER.EXTERNAL_AUTH_ID"),
				"filter" => array(
					"=CHAT_ID" => $this->chat['ID']
				),
			));
			while ($relation = $relationList->fetch())
			{
				if ($relation['EXTERNAL_AUTH_ID'] == "imconnector")
					continue;

				$this->leave($relation['USER_ID']);
			}

			$this->updateFieldData([Chat::FIELD_SESSION => [
				'ID' => '0',
				'PAUSE' => 'N',
				'WAIT_ACTION' => 'N'
			]]);

			$this->update(Array(
				'AUTHOR_ID' => 0,
				self::getFieldName(self::FIELD_SILENT_MODE) => 'N'
			));

			$result = true;
		}

		return $result;
	}

	/**
	 * @param $userId
	 * @return bool
	 */
	public function setUserIdForJoin($userId)
	{
		$this->joinByUserId = intval($userId);
		return true;
	}

	/**
	 * @param $params array
	 * 			(
	 * 				ACTIVE
	 * 				CRM
	 *				CRM_ENTITY_TYPE
	 *				CRM_ENTITY_ID
	 *				LEAD
	 *				COMPANY
	 *				CONTACT
	 *				DEAL
	 * 			)
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function setCrmFlag($params)
	{
		$result = false;

		$updateDate = [];

		$sessionField = $this->getFieldData(self::FIELD_SESSION);
		$sessionCrmField = $this->getFieldData(self::FIELD_CRM);

		if(isset($params['ACTIVE']))
		{
			$params['ACTIVE'] = $params['ACTIVE'] == 'Y'? 'Y': 'N';
			if($params['ACTIVE'] != $sessionField['CRM'])
			{
				$updateDate[self::FIELD_SESSION]['CRM'] = $params['ACTIVE'];
			}
		}
		if(isset($params['CRM']))
		{
			$params['CRM'] = $params['CRM'] == 'Y'? 'Y': 'N';
			if($params['CRM'] != $sessionField['CRM'])
			{
				$updateDate[self::FIELD_SESSION]['CRM'] = $params['CRM'];
			}
		}
		if(isset($params['ENTITY_TYPE']))
		{
			if($params['ENTITY_TYPE'] != $sessionField['CRM_ENTITY_TYPE'])
			{
				$updateDate[self::FIELD_SESSION]['CRM_ENTITY_TYPE'] = $params['ENTITY_TYPE'];
			}
		}
		if(isset($params['ENTITY_ID']))
		{
			$params['ENTITY_ID'] = intval($params['ENTITY_ID']);
			if($params['ENTITY_ID'] != $sessionField['CRM_ENTITY_ID'])
			{
				$updateDate[self::FIELD_SESSION]['CRM_ENTITY_ID'] = $params['ENTITY_ID'];
			}
		}

		if(isset($params['LEAD']))
		{
			$params['LEAD'] = intval($params['LEAD']);
			if($params['LEAD'] != $sessionCrmField['LEAD'])
			{
				$updateDate[self::FIELD_CRM]['LEAD'] = $params['LEAD'];
			}
		}

		if(isset($params['COMPANY']))
		{
			$params['COMPANY'] = intval($params['COMPANY']);
			if($params['COMPANY'] != $sessionCrmField['COMPANY'])
			{
				$updateDate[self::FIELD_CRM]['COMPANY'] = $params['COMPANY'];
			}
		}

		if(isset($params['CONTACT']))
		{
			$params['CONTACT'] = intval($params['CONTACT']);
			if($params['CONTACT'] != $sessionCrmField['CONTACT'])
			{
				$updateDate[self::FIELD_CRM]['CONTACT'] = $params['CONTACT'];
			}
		}

		if(isset($params['DEAL']))
		{
			$params['DEAL'] = intval($params['DEAL']);
			if($params['DEAL'] != $sessionCrmField['DEAL'])
			{
				$updateDate[self::FIELD_CRM]['DEAL'] = $params['DEAL'];
			}
		}

		if(!empty($updateDate))
		{
			$raw = $this->updateFieldData($updateDate);

			if($raw->isSuccess())
			{
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @param $status
	 * @return bool
	 */
	public function updateSessionStatus($status)
	{
		$result = false;

		if($this->isDataLoaded())
		{
			$users = [];
			$relations = \Bitrix\Im\Chat::getRelation($this->chat['ID'], [
				'SELECT' => Array('USER_ID'),
				'USER_DATA' => 'Y',
			]);
			foreach ($relations as $relation)
			{
				if ($relation['USER_DATA']["EXTERNAL_AUTH_ID"] == 'imconnector')
				{
					continue;
				}
				$users[] = $relation['USER_ID'];
			}

			Pull\Event::add($users, [
				'module_id' => 'imopenlines',
				'command' => 'updateSessionStatus',
				'params' => [
					'chatId' => $this->chat['ID'],
					'status' => (int)$status
				],
			]);

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
	public function finish()
	{
		$result = false;

		if($this->isDataLoaded())
		{
			$session = new Session();
			$resultLoadSession = $session->load([
				'USER_CODE' => $this->chat['ENTITY_ID']
			]);
			if ($resultLoadSession)
			{
				if($this->validationAction($session->getData('CHAT_ID')))
				{
					$eventData = [
						'RUNTIME_SESSION' => $session
					];

					$event = new Event("imopenlines", "OnChatFinish", $eventData);
					$event->send();

					$session->finish();

					$result = true;
				}
			}
			else
			{
				$this->validationAction();
			}
		}

		return $result;
	}

	/**
	 * @param $userId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function markSpamAndFinish($userId)
	{
		$result = false;

		$session = new Session();

		if (
			$this->isDataLoaded() &&
			$session->load([
				'USER_CODE' => $this->chat['ENTITY_ID'],
				'SKIP_CREATE' => 'Y'
			])
		)
		{
			if($this->validationAction($session->getData('CHAT_ID')))
			{
				$queueManager = Queue::initialization($session);

				if($queueManager && $queueManager->startLock())
				{
					$user = ImUser::getInstance($userId);
					$message = Loc::getMessage('IMOL_CHAT_MARK_SPAM_'.$user->getGender(), Array(
						'#USER#' => '[USER='.$user->getId().']'.$user->getFullName(false).'[/USER]',
					));

					Im::addMessage(Array(
						"TO_CHAT_ID" => $this->chat['ID'],
						"MESSAGE" => $message,
						"SYSTEM" => 'Y',
					));

					if (\Bitrix\ImOpenLines\Connector::isLiveChat($session->getData('SOURCE')))
					{
						$parsedUserCode = Session\Common::parseUserCode($session->getData('USER_CODE'));
						$chatId = $parsedUserCode['EXTERNAL_CHAT_ID'];
						$liveChat = new Chat($chatId);
						$liveChat->updateFieldData([Chat::FIELD_LIVECHAT => [
							'SESSION_ID' => 0,
							'SHOW_FORM' => 'N'
						]]);
					}

					$eventData = array(
						'RUNTIME_SESSION' => $session,
						'USER_ID' => $userId,
					);
					$event = new Event("imopenlines", "OnChatMarkSpam", $eventData);
					$event->send();

					$session->markSpam();
					$session->finish();

					$queueManager->stopLock();
					$result = true;
				}
			}
		}
		else
		{
			$this->validationAction();
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
		$result = false;

		if($this->isDataLoaded())
		{
			$session = new Session();

			$params = [
				'SKIP_CREATE' => 'Y',
				'USER_CODE' => $this->chat['ENTITY_ID']
			];

			$parsedUserCode = Session\Common::parseUserCode($this->chat['ENTITY_ID']);

			$params['USER_ID'] = $parsedUserCode['CONNECTOR_USER_ID'];
			$params['SOURCE'] = $parsedUserCode['CONNECTOR_ID'];
			$params['CHAT_ID'] = $parsedUserCode['EXTERNAL_CHAT_ID'];
			$params['CONFIG_ID'] = $parsedUserCode['CONFIG_ID'];

			$resultStart = $session->start($params);

			if ($resultStart->isSuccess() && $resultStart->getResult() == true)
			{
				$session->dismissedOperatorFinish();

				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @param $userId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function startSession($userId)
	{
		$result = false;

		if($this->isDataLoaded())
		{
			$session = new Session();
			$resultLoad = $session->load(Array(
				'USER_CODE' => $this->chat['ENTITY_ID'],
				'MODE' => Session::MODE_OUTPUT,
				'OPERATOR_ID' => $userId,
			));

			if($resultLoad)
			{
				if($this->validationAction($session->getData('CHAT_ID')))
				{
					if ($session->isNowCreated())
					{
						$dateClose = new DateTime();
						$dateClose->add('1 MONTH');

						$sessionUpdate = Array(
							'CHECK_DATE_CLOSE' => $dateClose
						);
						$session->update($sessionUpdate);
					}
					else
					{
						$this->join($userId, false);
					}

					$result = true;
				}
			}
		}

		return $result;
	}

	/**
	 * Create a new dialog based on an old message.
	 *
	 * @param $userId
	 * @param $messageId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function startSessionByMessage($userId, $messageId)
	{
		$result = false;

		if($this->isDataLoaded())
		{
			$session = new Session();
			$resultLoad = $session->load([
				'USER_CODE' => $this->chat['ENTITY_ID']
			]);
			if ($resultLoad)
			{
				if($this->validationAction($session->getData('CHAT_ID')))
				{
					$message = \CIMMessenger::GetById($messageId);
					if ($message['CHAT_ID'] == $this->chat['ID'])
					{
						$user = ImUser::getInstance($userId);
						Im::addMessage(Array(
							"TO_CHAT_ID" => $this->chat['ID'],
							"SYSTEM" => 'Y',
							"MESSAGE" => Loc::getMessage('IMOL_CHAT_CLOSE_FOR_OPEN_'.$user->getGender(), Array(
								'#USER#' => '[USER='.$user->getId().']'.$user->getFullName(false).'[/USER]',
							)),
						));

						$configId = $session->getData('CONFIG_ID');
						$sessionId = $session->getData('SESSION_ID');

						$session->finish(false, true, false);

						$session = new Session();
						$session->load(Array(
							'USER_CODE' => $this->chat['ENTITY_ID'],
							'MODE' => Session::MODE_OUTPUT,
							'OPERATOR_ID' => $userId,
							'CONFIG_ID' => $configId,
							'PARENT_ID' => $sessionId
						));

						if ($this->chat['AUTHOR_ID'] == $userId)
						{
							$resultAnswer = true;
						}
						else
						{
							$resultAnswer = $this->answer($userId, false, true)->isSuccess();
						}

						if($resultAnswer)
						{
							Im::addMessage(Array(
								"TO_CHAT_ID" => $this->chat['ID'],
								"FROM_USER_ID" => $message['AUTHOR_ID'],
								"MESSAGE" => $message['MESSAGE'],
								"PARAMS" => $message['PARAMS'],
								"SKIP_CONNECTOR" => 'Y',
							));

							$dateClose = new DateTime();
							$dateClose->add('1 MONTH');

							$sessionUpdate = Array(
								'CHECK_DATE_CLOSE' => $dateClose
							);
							$session->update($sessionUpdate);

							$result = true;
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param bool $active
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public function setSilentMode($active = true)
	{
		$result = false;

		if($this->isDataLoaded())
		{
			$active = $active? 'Y': '';
			if ($this->chat[self::getFieldName(self::FIELD_SILENT_MODE)] == $active)
			{
				$result = true;
			}
			else
			{
				\Bitrix\Im\Model\ChatTable::update($this->chat['ID'], Array(
					self::getFieldName(self::FIELD_SILENT_MODE) => $active
				));

				Im::addMessage(Array(
					"TO_CHAT_ID" => $this->chat['ID'],
					"MESSAGE" => Loc::getMessage($active? 'IMOL_CHAT_STEALTH_ON': 'IMOL_CHAT_STEALTH_OFF'),
					"SYSTEM" => 'Y',
				));

				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function isSilentModeEnabled()
	{
		$result = false;

		if($this->isDataLoaded())
		{
			$result = $this->chat[self::getFieldName(self::FIELD_SILENT_MODE)] == 'Y';;
		}

		return $result;
	}

	/**
	 * @param $params
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function setPauseFlag($params)
	{
		$result = false;

		$pause = $params['ACTIVE'] == 'Y'? 'Y': 'N';
		$sessionField = $this->getFieldData(self::FIELD_SESSION);
		if ($sessionField['PAUSE'] == $pause)
		{
			$result = true;
		}
		elseif($this->isDataLoaded())
		{
			$session = new Session();
			$resultLoad = $session->load([
				'USER_CODE' => $this->chat['ENTITY_ID']
			]);
			if ($resultLoad)
			{
				if($this->validationAction($session->getData('CHAT_ID')))
				{
					if(empty($params['USER_ID']) || $params['USER_ID'] == $session->getData('OPERATOR_ID'))
					{
						$queueManager = Queue::initialization($session);

						if($queueManager && $queueManager->startLock())
						{
							$session->pause($pause == 'Y');

							$this->updateFieldData([self::FIELD_SESSION => [
								'PAUSE' => $pause
							]]);

							if ($pause == 'Y')
							{
								$datePause = new DateTime();
								$datePause->add('1 WEEK');

								$formattedDate = \FormatDate('d F', $datePause->getTimestamp());
								Im::addMessage([
									"TO_CHAT_ID" => $this->chat['ID'],
									"MESSAGE" => Loc::getMessage('IMOL_CHAT_ASSIGN_ON', ['#DATE#' => '[b]'.$formattedDate.'[/b]']),
									"SYSTEM" => 'Y',
								]);
							}
							else
							{
								Im::addMessage([
									"TO_CHAT_ID" => $this->chat['ID'],
									"MESSAGE" => Loc::getMessage('IMOL_CHAT_ASSIGN_OFF'),
									"SYSTEM" => 'Y',
								]);
							}

							$queueManager->stopLock();
							$result = true;
						}
					}
				}
			}
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
	public function createLead()
	{
		$result = false;

		$sessionField = $this->getFieldData(self::FIELD_SESSION);
		if ($sessionField['CRM'] == 'Y')
		{
			$result = true;
		}
		elseif($this->isDataLoaded())
		{
			$session = new Session();
			if($session->load([
				'USER_CODE' => $this->chat['ENTITY_ID']
			]))
			{
				if($this->validationAction($session->getData('CHAT_ID')))
				{
					$crmManager = new Crm($session);
					if($crmManager->isLoaded())
					{
						$crmFieldsManager = $crmManager->getFields();
						$crmFieldsManager->setTitle($session->getChat()->getData('TITLE'));
						$crmFieldsManager->setDataFromUser();
						$crmManager->setSkipSearch();
						$crmManager->setSkipAutomationTrigger();

						$rawResult = $crmManager->registrationChanges();
						$crmManager->sendCrmImMessages();

						if($rawResult->isSuccess())
						{
							$result = true;
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param $field
	 * @return array
	 */
	public function getFieldData($field)
	{
		$data = [];

		if ($this->isDataLoaded() &&
			in_array($field, [self::FIELD_CRM, self::FIELD_SESSION, self::FIELD_LIVECHAT])
		)
		{
			if ($field == self::FIELD_SESSION)
			{
				$data = [
					'ID' => time(),
					'CRM' => 'N',
					'CRM_ENTITY_TYPE' => 'NONE',
					'CRM_ENTITY_ID' => '0',
					'PAUSE' => 'N',
					'WAIT_ACTION' => 'N',
					'DATE_CREATE' => '0',
					'LINE_ID' => 0
				];

				$fieldData = explode("|", $this->chat[self::getFieldName($field)]);
				if (isset($fieldData[0]) && $fieldData[0] == 'Y')
				{
					$data['CRM'] = $fieldData[0];
				}
				if (isset($fieldData[1]))
				{
					$data['CRM_ENTITY_TYPE'] = $fieldData[1];
				}
				if (isset($fieldData[2]))
				{
					$data['CRM_ENTITY_ID'] = $fieldData[2];
				}
				if (isset($fieldData[3]) && $fieldData[3] == 'Y')
				{
					$data['PAUSE'] = $fieldData[3];
				}
				if (isset($fieldData[4]) && $fieldData[4] == 'Y')
				{
					$data['WAIT_ACTION'] = $fieldData[4];
				}
				if (isset($fieldData[5]))
				{
					$data['ID'] = intval($fieldData[5]);
				}
				if (isset($fieldData[6]))
				{
					$data['DATE_CREATE'] = intval($fieldData[6]);
				}
				if (isset($fieldData[7]) && $fieldData[7] > 0)
				{
					$data['LINE_ID'] = intval($fieldData[7]);
				}
				if (isset($fieldData[8]))
				{
					$data['BLOCK_DATE'] = (int)$fieldData[8];
				}
				if (isset($fieldData[9]))
				{
					$data['BLOCK_REASON'] = $fieldData[9];
				}
			}
			else if ($field == self::FIELD_CRM)
			{
				$data = [
					'LEAD' => 0,
					'COMPANY' => 0,
					'CONTACT' => 0,
					'DEAL' => 0,
				];

				$fieldData = explode("|", $this->chat[self::getFieldName($field)]);

				$countFields = count($fieldData);
				for($i = 0; $countFields>$i; $i=$i+2)
				{
					if(isset($data[$fieldData[$i]]) && isset($fieldData[$i+1]))
					{
						$data[$fieldData[$i]] = $fieldData[$i+1];
					}
				}
			}
			else if ($field == self::FIELD_LIVECHAT)
			{
				$data = [
					'READED' => 'N',
					'READED_ID' => '0',
					'READED_TIME' => false,
					'SESSION_ID' => '0',
					'SHOW_FORM' => 'Y',
				];
				$fieldData = explode("|", $this->chat[self::getFieldName($field)]);
				if (isset($fieldData[0]) && $fieldData[0] == 'Y')
				{
					$data['READED'] = $fieldData[0];
				}
				if (isset($fieldData[1]))
				{
					$data['READED_ID'] = intval($fieldData[1]);
				}
				if (isset($fieldData[2]))
				{
					$data['READED_TIME'] = $fieldData[2];
				}
				if (isset($fieldData[3]))
				{
					$data['SESSION_ID'] = intval($fieldData[3]);
				}
				if (isset($fieldData[4]))
				{
					$data['SHOW_FORM'] = $fieldData[4] == 'N'? 'N': 'Y';
				}
			}
		}

		return $data;
	}

	/**
	 * @param $lineName
	 * @param string $userName
	 * @param string $userColor
	 * @return array
	 */
	public function getTitle($lineName, $userName = '', $userColor = '')
	{
		if (!$userName)
		{
			$result = self::getGuestName($userColor);
			$userName = $result['USER_NAME'];
			$userColor = $result['USER_COLOR'];
		}

		if (!$userColor)
		{
			$userColor = \Bitrix\Im\Color::getRandomCode();
		}

		return Array(
			'TITLE' => Loc::getMessage('IMOL_CHAT_CHAT_NAME', Array("#USER_NAME#" => $userName, "#LINE_NAME#" => $lineName)),
			'COLOR' => $userColor
		);
	}

	/**
	 * @param $fields
	 * @return Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function updateFieldData($fields)
	{
		$result = new Result;
		$updateDate = [];

		if(!$this->isDataLoaded())
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CHAT_ERROR_NOT_LOAD_DATA'), 'IMOL_CHAT_ERROR_NOT_LOAD_DATA', __METHOD__, ['chat' => $this->chat]));
		}

		if($result->isSuccess() && is_array($fields))
		{
			foreach ($fields as $fieldType => $fieldData)
			{
				if (in_array($fieldType, [self::FIELD_CRM, self::FIELD_SESSION, self::FIELD_LIVECHAT]) && !empty($fieldData))
				{
					if ($fieldType == self::FIELD_SESSION)
					{
						$data = self::getFieldData($fieldType);
						if (isset($fieldData['CRM']))
						{
							$data['CRM'] = $fieldData['CRM'];
						}
						if (isset($fieldData['CRM_ENTITY_TYPE']))
						{
							$data['CRM_ENTITY_TYPE'] = $fieldData['CRM_ENTITY_TYPE'];
						}
						if (isset($fieldData['CRM_ENTITY_ID']))
						{
							$data['CRM_ENTITY_ID'] = $fieldData['CRM_ENTITY_ID'];
						}
						if (isset($fieldData['PAUSE']))
						{
							$data['PAUSE'] = $fieldData['PAUSE'];
						}
						if (isset($fieldData['WAIT_ACTION']))
						{
							$data['WAIT_ACTION'] = $fieldData['WAIT_ACTION'];
						}
						if (isset($fieldData['ID']))
						{
							$data['ID'] = $fieldData['ID'];
						}
						if (isset($fieldData['DATE_CREATE']))
						{
							$data['DATE_CREATE'] = $fieldData['DATE_CREATE'] instanceof DateTime? $fieldData['DATE_CREATE']->getTimestamp(): intval($fieldData['DATE_CREATE']);
						}
						if (isset($fieldData['LINE_ID']) && intval($fieldData['LINE_ID']) > 0)
						{
							$data['LINE_ID'] = intval($fieldData['LINE_ID']);
						}
						if (isset($fieldData['BLOCK_DATE']))
						{
							$data['BLOCK_DATE'] = $fieldData['BLOCK_DATE'] instanceof DateTime? $fieldData['BLOCK_DATE']->getTimestamp(): (int)$fieldData['BLOCK_DATE'];
						}
						if (isset($fieldData['BLOCK_REASON']))
						{
							$data['BLOCK_REASON'] = $fieldData['BLOCK_REASON'];
						}

						$this->chat[self::getFieldName($fieldType)] = $data['CRM'].'|'
																	.$data['CRM_ENTITY_TYPE'].'|'
																	.$data['CRM_ENTITY_ID'].'|'
																	.$data['PAUSE'].'|'
																	.$data['WAIT_ACTION'].'|'
																	.$data['ID'].'|'
																	.$data['DATE_CREATE'].'|'
																	.$data['LINE_ID'].'|'
																	.$data['BLOCK_DATE'].'|'
																	.$data['BLOCK_REASON'];

						$updateDate[self::getFieldName($fieldType)] = $this->chat[self::getFieldName($fieldType)];
					}
					else if ($fieldType == self::FIELD_CRM)
					{
						$strungData = '';
						$data = self::getFieldData($fieldType);
						if (isset($fieldData['LEAD']))
						{
							$data['LEAD'] = $fieldData['LEAD'];
						}
						if (isset($fieldData['COMPANY']))
						{
							$data['COMPANY'] = $fieldData['COMPANY'];
						}
						if (isset($fieldData['CONTACT']))
						{
							$data['CONTACT'] = $fieldData['CONTACT'];
						}
						if (isset($fieldData['DEAL']))
						{
							$data['DEAL'] = $fieldData['DEAL'];
						}

						foreach ($data as $type => $value)
						{
							if(!empty($strungData))
								$strungData .= '|';

							$strungData .= $type . '|' . $value;
						}

						if(!empty($strungData))
						{
							$updateDate[self::getFieldName($fieldType)] = $this->chat[self::getFieldName($fieldType)] = $strungData;
						}
					}
					elseif ($fieldType == self::FIELD_LIVECHAT)
					{
						$data = self::getFieldData($fieldType);
						if (isset($fieldData['READED']))
						{
							$data['READED'] = $fieldData['READED'];
						}
						if (isset($fieldData['READED_ID']))
						{
							$data['READED_ID'] = intval($fieldData['READED_ID']);
						}
						if (isset($fieldData['READED_TIME']))
						{
							$data['READED_TIME'] = $fieldData['READED_TIME'] instanceof DateTime? date('c', $fieldData['READED_TIME']->getTimestamp()): false;
						}
						if (isset($fieldData['SESSION_ID']))
						{
							$data['SESSION_ID'] = intval($fieldData['SESSION_ID']);
						}
						if (isset($fieldData['SHOW_FORM']))
						{
							$data['SHOW_FORM'] = $fieldData['SHOW_FORM'] == 'N'? 'N': 'Y';
						}
						$updateDate[self::getFieldName($fieldType)] = $this->chat[self::getFieldName($fieldType)] = $data['READED'].'|'.$data['READED_ID'].'|'.$data['READED_TIME'].'|'.$data['SESSION_ID'].'|'.$data['SHOW_FORM'];
					}
				}
			}
		}

		if($result->isSuccess() && empty($updateDate))
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NOT_GIVEN_CORRECT_DATA'), self::IMOL_CRM_ERROR_NOT_GIVEN_CORRECT_DATA, __METHOD__));
		}

		if($result->isSuccess())
		{
			$rawUpdate = ChatTable::update($this->chat['ID'], $updateDate);

			if(!$rawUpdate->isSuccess())
			{
				$result->addErrors($rawUpdate->getErrors());
			}
		}

		if($result->isSuccess())
		{
			$users = [];
			$relationList = RelationTable::getList([
				"select" => array("ID", "USER_ID", "EXTERNAL_AUTH_ID" => "USER.EXTERNAL_AUTH_ID"),
				"filter" => array(
					"=CHAT_ID" => $this->chat['ID']
				)
			]);
			while ($relation = $relationList->fetch())
			{
				if (
					!ImUser::getInstance($relation['USER_ID'])->isBot() &&
					!ImUser::getInstance($relation['USER_ID'])->isNetwork() &&
					(isset($fields[self::FIELD_LIVECHAT]) || !ImUser::getInstance($relation['USER_ID'])->isConnector())
				)
				{
					\CIMContactList::CleanChatCache($relation['USER_ID']);
					$users[] = $relation['USER_ID'];
				}
			}

			if (!empty($users))
			{
				foreach ($updateDate as $name => $value)
				{
					Pull\Event::add($users, Array(
						'module_id' => 'im',
						'command' => 'chatUpdateParam',
						'params' => Array(
							'chatId' => (int)$this->chat['ID'],
							'name' => strtolower($name),
							'value' => $value
						),
						'extra' =>  \Bitrix\Im\Common::getPullExtra()
					));
				}
			}
		}

		return $result;
	}

	/**
	 * @param $fields
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function update($fields)
	{
		$result = false;

		if($this->isDataLoaded())
		{
			foreach($fields as $field => $value)
			{
				if ($this->chat[$field] === $value)
				{
					unset($fields[$field]);
				}
				else
				{
					$this->chat[$field] = $value;
				}
			}

			if (!empty($fields))
			{
				\Bitrix\Im\Model\ChatTable::update($this->chat['ID'], $fields);

				$relations = \CIMChat::GetRelationById($this->chat['ID']);
				foreach ($relations as $rel)
				{
					\CIMContactList::CleanChatCache($rel['USER_ID']);

					if (isset($fields['AUTHOR_ID']))
					{
						if ($rel['USER_ID'] == $this->chat['AUTHOR_ID'])
						{
							RelationTable::update($rel['ID'], Array('MANAGER' => 'N'));
						}
						if ($rel['USER_ID'] == $fields['AUTHOR_ID'])
						{
							RelationTable::update($rel['ID'], Array('MANAGER' => 'Y'));
						}
					}
				}

				if (array_key_exists('AUTHOR_ID', $fields))
				{
					//CRM
					if(!empty($fields['AUTHOR_ID']) && !ImUser::getInstance($fields['AUTHOR_ID'])->isBot())
					{
						$session = new Session();
						$loadSession = $session->load(Array(
							'USER_CODE' => $this->chat['ENTITY_ID']
						));
						if($loadSession)
						{
							$crmManager = new Crm($session);

							$crmManager->setOperatorId($fields['AUTHOR_ID'], false);
						}
					}
					//END CRM

					$parsedUserCode = Session\Common::parseUserCode($this->chat['ENTITY_ID']);
					if (\Bitrix\ImOpenLines\Connector::isLiveChat($parsedUserCode['CONNECTOR_ID']))
					{
						Pull\Event::add($parsedUserCode['CONNECTOR_USER_ID'], Array(
							'module_id' => 'imopenlines',
							'command' => 'sessionOperatorChange',
							'params' => Array(
								'chatId' => (int)$parsedUserCode['EXTERNAL_CHAT_ID'],
								'operator' => Rest::objectEncode(
									Queue::getUserData($parsedUserCode['CONFIG_ID'], $fields['AUTHOR_ID'])
								)
							)
						));
					}
				}
			}

			$result = true;
		}

		return $result;
	}

	/**
	 * @param string $field
	 * @return array|bool|false|mixed|null
	 */
	public function getData($field = '')
	{
		$result = false;

		if($this->isDataLoaded())
		{
			if ($field)
			{
				$result = isset($this->chat[$field])? $this->chat[$field]: null;
			}
			else
			{
				$result = $this->chat;
			}
		}

		return $result;
	}

	public static function getGuestName($chatColorCode = '')
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		if (\Bitrix\Im\Color::isEnabled())
		{
			if (!$chatColorCode)
			{
				\CGlobalCounter::Increment('im_chat_color_id', \CGlobalCounter::ALL_SITES, false);
				$chatColorId = \CGlobalCounter::GetValue('im_chat_color_id', \CGlobalCounter::ALL_SITES);
				$chatColorCode = \Bitrix\Im\Color::getCodeByNumber($chatColorId);
			}
			\CGlobalCounter::Increment('im_chat_color_'.$chatColorCode, \CGlobalCounter::ALL_SITES, false);

			$chatColorCodeCount = \CGlobalCounter::GetValue('im_chat_color_'.$chatColorCode, \CGlobalCounter::ALL_SITES);
			if ($chatColorCodeCount == 99)
			{
				\CGlobalCounter::Set('im_chat_color_'.$chatColorCode, 1, \CGlobalCounter::ALL_SITES, '', false);
			}
			$userName = Loc::getMessage('IMOL_CHAT_CHAT_NAME_COLOR_GUEST', Array("#COLOR#" => \Bitrix\Im\Color::getName($chatColorCode), "#NUMBER#" => $chatColorCodeCount+1));
		}
		else
		{
			$guestId = \CGlobalCounter::GetValue('imol_guest_id', \CGlobalCounter::ALL_SITES);
			\CGlobalCounter::Increment('imol_guest_id', \CGlobalCounter::ALL_SITES, false);
			if ($guestId == 99)
			{
				\CGlobalCounter::Set('imol_guest_id', 1, \CGlobalCounter::ALL_SITES, '', false);
			}
			$userName = Loc::getMessage('IMOL_CHAT_CHAT_NAME_GUEST', Array("#NUMBER#" => $guestId+1));
		}

		return Array(
			'USER_NAME' => $userName,
			'USER_COLOR' => $chatColorCode,
		);
	}

	public static function getFieldName($field)
	{
		return self::$fieldAssoc[$field];
	}

	/**
	 * @param $userList
	 * @param bool $userCrm
	 * @return bool|int
	 * @throws Main\LoaderException
	 */
	public function sendJoinMessage($userList, $userCrm = false)
	{
		$result = false;

		if (!empty($userList) && $this->isDataLoaded())
		{
			if (count($userList) == 1)
			{
				$toUserId = $userList[0];
				$userName = ImUser::getInstance($toUserId)->getFullName(false);

				if($userCrm === true)
				{
					$message = Loc::getMessage('IMOL_CHAT_ASSIGN_OPERATOR_CRM_NEW', ['#USER#' => '[USER='.$toUserId.']'.$userName.'[/USER]']);
				}
				else
				{
					$message = Loc::getMessage('IMOL_CHAT_ASSIGN_OPERATOR_NEW', ['#USER#' => '[USER='.$toUserId.']'.$userName.'[/USER]']);
				}
			}
			else
			{
				$message = Loc::getMessage('IMOL_CHAT_ASSIGN_OPERATOR_LIST_NEW');
			}

			$messageId = Im::addMessage([
				"TO_CHAT_ID" => $this->chat['ID'],
				"FROM_USER_ID" => 0,
				"MESSAGE" => $message,
				"SYSTEM" => 'Y',
				"IMPORTANT_CONNECTOR" => 'N'
			]);

			$result = $messageId;
		}

		return $result;
	}

	/**
	 * @param null $type
	 * @param string $message
	 * @return bool|int
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function sendAutoMessage($type = null, $message = '')
	{
		$result = false;

		if (!$type)
		{
			$result = true;
		}
		elseif($this->isDataLoaded())
		{
			$session = new Session();
			$resultLoad = $session->load([
				'USER_CODE' => $this->chat['ENTITY_ID']
			]);
			if ($resultLoad)
			{
				if($this->validationAction($session->getData('CHAT_ID')))
				{
					$messageId = false;
					if ($type == self::TEXT_WELCOME)
					{
						if ($session->getConfig('WELCOME_MESSAGE') == 'Y' && $session->getConfig('SOURCE') != 'network')
						{
							$messageId = Im::addMessage(Array(
								"TO_CHAT_ID" => $this->chat['ID'],
								"MESSAGE" => $session->getConfig('WELCOME_MESSAGE_TEXT'),
								"SYSTEM" => 'Y',
								"IMPORTANT_CONNECTOR" => 'Y',
								"PARAMS" => Array(
									"CLASS" => "bx-messenger-content-item-ol-output"
								)
							));
						}
					}
					elseif($type == self::TEXT_DEFAULT)
					{
						if (!empty($message))
						{
							$messageId = Im::addMessage(
								Array(
									"TO_CHAT_ID" => $this->chat['ID'],
									"MESSAGE" => $message,
									"SYSTEM" => 'Y',
									"IMPORTANT_CONNECTOR" => 'Y',
									"PARAMS" => Array(
										"CLASS" => "bx-messenger-content-item-ol-output"
									)
								)
							);
						}
					}

					$result = $messageId;
				}
			}
		}



		return $result;
	}

	/**
	 * @param $entityId
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function updateChatLineData($entityId)
	{
		$this->update(array('ENTITY_ID' => $entityId));

		$entity = explode('|', $entityId);

		if (strtolower($entity[0] == 'livechat'))
		{
			$relationChat = new self($entity[2]);
			$relationEntityId = $entity[1] . '|' . $entity[3];
			$relationChat->update(array('ENTITY_ID' => $relationEntityId));
		}
	}

	/**
	 * Check before action that the same dialog was loaded.
	 *
	 * @param $loadChatId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function validationAction($loadChatId = 0)
	{
		$result = false;

		if($this->isDataLoaded())
		{
			if($this->chat['ID'] == $loadChatId)
			{
				$result = true;
			}
			else
			{
				$this->close();
			}
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function isNowCreated()
	{
		return $this->isCreated;
	}

	/**
	 * @return bool
	 */
	public function isDataLoaded()
	{
		return $this->isDataLoaded;
	}

	/**
	 * @return BasicError|null
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @param $type
	 * @param $sessionId
	 * @param $rating
	 * @param $toUserId
	 * @param null $fromUserId
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function sendRatingNotify($type, $sessionId, $rating, $toUserId, $fromUserId = null)
	{
		$result = false;

		$toUserId = intval($toUserId);
		$fromUserId = intval($fromUserId);
		if ($fromUserId <= 0)
		{
			$fromUserId = $GLOBALS['USER']->GetId();
		}

		if (
			\Bitrix\Main\Loader::includeModule('im') &&
			in_array($type, Array(self::RATING_TYPE_CLIENT, self::RATING_TYPE_HEAD, self::RATING_TYPE_HEAD_AND_COMMENT, self::RATING_TYPE_COMMENT)) &&
			$toUserId > 0 &&
			$toUserId != $fromUserId
		)
		{
			$commentValue = null;
			$ratingValue = null;

			if($type == self::RATING_TYPE_CLIENT || $type == self::RATING_TYPE_HEAD)
			{
				$rating = intval($rating);
				if ($rating < 6 && $rating > 0)
				{
					$ratingValue = $rating;
				}
			}
			elseif($type == self::RATING_TYPE_COMMENT)
			{
				$commentValue = htmlspecialcharsbx($rating);
			}
			elseif($type == self::RATING_TYPE_HEAD_AND_COMMENT)
			{
				if ($rating['vote'] < 6 && $rating['vote'] > 0)
				{
					$ratingValue = $rating['vote'];
				}

				$commentValue = htmlspecialcharsbx($rating['comment']);
			}

			if($commentValue !== null || $ratingValue !== null)
			{
				$userName = '';
				if ($type == self::RATING_TYPE_CLIENT)
				{
					$notifyMessageName = $ratingValue == self::RATING_VALUE_DISLIKE? 'IMOL_CHAT_NOTIFY_RATING_CLIENT_DISLIKE_NEW': 'IMOL_CHAT_NOTIFY_RATING_CLIENT_LIKE_NEW';
					$ratingImage = $ratingValue == self::RATING_VALUE_DISLIKE? '[dislike]': '[like]';
					$ratingText = Loc::getMessage('IMOL_CHAT_NOTIFY_RATING_VALUE_'.($ratingValue == self::RATING_VALUE_DISLIKE? 'DISLIKE': 'LIKE'));
					$commentText = '';
				}
				else
				{
					$userName = ImUser::getInstance($fromUserId)->getFullName(false);
					$userGender = ImUser::getInstance($fromUserId)->getGender();

					if($type == self::RATING_TYPE_HEAD)
					{
						$notifyMessageName = 'IMOL_CHAT_NOTIFY_RATING_HEAD_' . $userGender . '_LIKE';
						$ratingImage = "[RATING=" . $ratingValue . "]";
						$ratingText = Loc::getMessage('IMOL_CHAT_NOTIFY_RATING_VALUE_' . $ratingValue);
						$commentText = '';
					}
					elseif($type == self::RATING_TYPE_COMMENT)
					{
						$notifyMessageName = 'IMOL_CHAT_NOTIFY_RATING_HEAD_' . $userGender . '_COMMENT';
						$ratingImage = "[RATING=" . $ratingValue . "]";
						$ratingText = '';
						$commentText = $commentValue;
					}
					else
					{
						$notifyMessageName = 'IMOL_CHAT_NOTIFY_RATING_HEAD_'.$userGender.'_LIKE_AND_COMMENT';
						$ratingImage = "[RATING=" . $ratingValue . "]";
						$ratingText = Loc::getMessage('IMOL_CHAT_NOTIFY_RATING_VALUE_' . $ratingValue);
						$commentText = $commentValue;
					}
				}

				$userViewChat = \CIMContactList::InRecent($toUserId, IM_MESSAGE_OPEN_LINE, $sessionId);

				\CIMNotify::DeleteBySubTag("IMOL|RATING|".$type.'|'.$sessionId);

				\CIMNotify::Add(array(
					"TO_USER_ID" => $toUserId,
					"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
					"NOTIFY_MODULE" => "imopenlines",
					"NOTIFY_EVENT" => $type == self::RATING_TYPE_CLIENT? 'rating_client': 'rating_head',
					"NOTIFY_SUB_TAG" => "IMOL|RATING|".$type.'|'.$sessionId,
					"NOTIFY_MESSAGE" => '[b]'.Loc::getMessage('IMOL_CHAT_NOTIFY_RATING_TITLE').'[/b][br]'.Loc::getMessage($notifyMessageName, Array(
							'#NUMBER#' => '[CHAT=imol|'.$sessionId.']'.$sessionId.'[/CHAT]',
							'#USER#' => '[USER='.$fromUserId.']'.$userName.'[/USER]',
							'#RATING#' => $ratingImage,
							'#COMMENT#' => $commentText,
						)),
					"NOTIFY_MESSAGE_OUT" => '[b]'.Loc::getMessage('IMOL_CHAT_NOTIFY_RATING_TITLE').'[/b][br]'.Loc::getMessage($notifyMessageName, Array(
							'#NUMBER#' => '[URL=/online/?IM_HISTORY=imol|'.$sessionId.']'.$sessionId.'[/URL]',
							'#USER#' => $userName,
							'#RATING#' => $ratingText,
							'#COMMENT#' => $commentText,
						)),
					"RECENT_ADD" => $userViewChat? 'Y': 'N'
				));

				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public static function onGetNotifySchema()
	{
		return [
			"imopenlines" => [
				"rating_client" => [
					"NAME" => Loc::getMessage('IMOL_CHAT_NOTIFY_SCHEMA_RATING_CLIENT_new'),
					"LIFETIME" => 86400*7
				],
				"rating_head" => [
					"NAME" => Loc::getMessage('IMOL_CHAT_NOTIFY_SCHEMA_RATING_HEAD'),
					"LIFETIME" => 86400*7
				],
			],
		];
	}

	/**
	 * @param $icon
	 * @param null $lang
	 * @return array|bool
	 */
	public static function onAppLang($icon, $lang = null)
	{
		$title = Loc::getMessage('IMOL_CHAT_APP_ICON_QUICK_TITLE', null, $lang);
		$description = Loc::getMessage('IMOL_CHAT_APP_ICON_QUICK_DESCRIPTION', null, $lang);

		$result = false;
		if (strlen($title) > 0)
		{
			$result = [
				'TITLE' => $title,
				'DESCRIPTION' => $description,
				'COPYRIGHT' => ''
			];
		}

		return $result;
	}

	/**
	 * @param $dialogId
	 * @param $user
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function chatAutoAddRecent($dialogId, $user)
	{
		if (Main\Loader::includeModule("im"))
		{
			if (!is_array($user))
			{
				$user = [$user];
			}

			foreach($user as $userId)
			{
				\Bitrix\Im\Recent::show($dialogId, $userId);
			}
		}
	}

	/**
	 * @deprecated Use \Bitrix\ImOpenLines\Crm\Common::getLastChatIdByCrmEntity instead.
	 *
	 * @param $crmEntityType
	 * @param $crmEntityId
	 * @return int
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getLastChatIdByCrmEntity($crmEntityType, $crmEntityId): int
	{
		return \Bitrix\ImOpenLines\Crm\Common::getLastChatIdByCrmEntity($crmEntityType, $crmEntityId);
	}
}