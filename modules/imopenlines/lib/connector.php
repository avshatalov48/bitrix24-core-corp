<?php
namespace Bitrix\ImOpenLines;

use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Main\Type\DateTime,
	Bitrix\Main\Localization\Loc;
use Bitrix\Pull;
use Bitrix\ImConnector,
	Bitrix\ImConnector\Output,
	Bitrix\ImConnector\Library,
	Bitrix\ImConnector\InteractiveMessage;
use Bitrix\Im\Bot\Keyboard,
	Bitrix\Im\Text as ImText,
	Bitrix\Im\User as ImUser,
	Bitrix\Im\Model\ChatTable;

Loc::loadMessages(__FILE__);

class Connector
{
	const TYPE_LIVECHAT = 'livechat';
	const TYPE_NETWORK = 'network';
	const TYPE_CONNECTOR = 'connector';

	const EVENT_IMOPENLINE_MESSAGE_SEND = 'OnImopenlineMessageSend';
	const EVENT_IMOPENLINE_MESSAGE_RECEIVE = 'OnImopenlineMessageReceive';

	public const LOCK_MAX_ITERATIONS = 3;

	static $noVote = [
		self::TYPE_LIVECHAT,
		//self::TYPE_NETWORK
	];

	private $error = null;
	private $moduleLoad = false;

	/** @var Result */
	protected $result;

	/**
	 * Connector constructor.
	 * @throws Main\LoaderException
	 */
	public function __construct()
	{
		$imLoad = Loader::includeModule('im');
		$pullLoad = Loader::includeModule('pull');
		$connectorLoad = Loader::includeModule('imconnector');
		if (
			$imLoad &&
			$pullLoad &&
			$connectorLoad
		)
		{
			$this->result = new Result();
			//old version
			$this->error = new BasicError(null, '', '');
			$this->moduleLoad = true;
		}
		else
		{
			if (!$imLoad)
			{
				$this->result->addError(new Error(Loc::getMessage('IMOL_CHAT_ERROR_IM_LOAD'), 'IM_LOAD_ERROR', __METHOD__));
				//old version
				$this->error = new BasicError(__METHOD__, 'IM_LOAD_ERROR', Loc::getMessage('IMOL_CHAT_ERROR_IM_LOAD'));
			}
			elseif (!$pullLoad)
			{
				$this->result->addError(new Error(Loc::getMessage('IMOL_CHAT_ERROR_PULL_LOAD'), 'PULL_LOAD_ERROR', __METHOD__));
				//old version
				$this->error = new BasicError(__METHOD__, 'PULL_LOAD_ERROR', Loc::getMessage('IMOL_CHAT_ERROR_PULL_LOAD'));
			}
			elseif (!$connectorLoad)
			{
				$this->result->addError(new Error(Loc::getMessage('IMOL_CHAT_ERROR_CONNECTOR_LOAD'), 'CONNECTOR_LOAD_ERROR', __METHOD__));
				//old version
				$this->error = new BasicError(__METHOD__, 'CONNECTOR_LOAD_ERROR', Loc::getMessage('IMOL_CHAT_ERROR_CONNECTOR_LOAD'));
			}
		}
	}

	/**
	 * @return bool
	 */
	private function isModuleLoad(): bool
	{
		return $this->moduleLoad;
	}

	/**
	 * Adding an incoming message from an external channel.
	 *
	 * @param $params
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function addMessage($params)
	{
		$result = false;
		$finishSession = false;
		$voteSession = false;

		if (!empty($params))
		{
			if (
				!in_array($params['connector']['connector_id'], self::$noVote) &&
				(
					mb_strpos($params['message']['text'], '1') === 0
					|| mb_strpos($params['message']['text'], '0') === 0
				)
				&&
				(
					mb_strlen(trim($params['message']['text'])) == 1
					|| mb_substr($params['message']['text'], 1, 1) == " "
				)
			)
			{
				$voteSession = true;
			}

			$addMessage = [
				"FROM_USER_ID" => $params['message']['user_id'],
				"PARAMS" => isset($params['message']['params']) ? $params['message']['params'] : []
				//"SKIP_COMMAND" => "Y"
			];
			//if (is_object($params['message']['date']))
			//{
			//	$addMessage["MESSAGE_DATE"] = $params['message']['date']->toString();
			//}
			if (!empty($params['message']['text']) || $params['message']['text'] === '0')
			{
				$addMessage["MESSAGE"] = $params['message']['text'];
			}

			if (!empty($params['message']['attach']))
			{
				if ($params['connector']['connector_id'] == self::TYPE_LIVECHAT)
				{
					$addMessage["ATTACH"] = $params['message']['attach'];
				} else
				{
					$addMessage["ATTACH"] = \CIMMessageParamAttach::GetAttachByJson($params['message']['attach']);
				}
			}
			if (!empty($params['message']['keyboard']))
			{
				if ($params['connector']['connector_id'] == self::TYPE_LIVECHAT)
				{
					$addMessage["KEYBOARD"] = $params['message']['keyboard'];
				} else
				{
					$keyboard = [];
					if (!isset($params['message']['keyboard']['BUTTONS']))
					{
						$keyboard['BUTTONS'] = $params['message']['keyboard'];
					}
					else
					{
						$keyboard = $params['message']['keyboard'];
					}
					$addMessage["KEYBOARD"] = Keyboard::getKeyboardByJson($keyboard);
				}
			}
			if (!empty($params['message']['fileLinks']))
			{
				if (!$addMessage["ATTACH"])
				{
					$addMessage["ATTACH"] = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
				}

				foreach ($params['message']['fileLinks'] as $key => $value)
				{
					if ($value['type'] === 'image')
					{
						$addMessage["ATTACH"]->AddImages([[
							"NAME" => $value['name'],
							"LINK" => $value['link'],
							"WIDTH" => (int)$value['width'],
							"HEIGHT" => (int)$value['height'],
						]]);
					} else
					{
						$addMessage["ATTACH"]->AddFiles([[
							"NAME" => $value['name'],
							"LINK" => $value['link'],
							"SIZE" => $value['size'],
						]]);
					}
				}
			}

			$userCode = self::getUserCode($params['connector']);
			$keyLock = Chat::PREFIX_KEY_LOCK_NEW_SESSION . $userCode;

			$iteration = 0;
			$isAddMessage = false;
			do
			{
				$iteration++;
				if (
					$iteration > self::LOCK_MAX_ITERATIONS ||
					Tools\Lock::getInstance()->set($keyLock)
				)
				{
					if (
						!empty($params['message']['files']) ||
						!empty($addMessage['ATTACH']) ||
						$addMessage['MESSAGE'] <> ''
					)
					{
						$userViewChat = false;
						$addVoteResult = false;

						$session = new Session();
						$resultLoadSession = $session->load([
							'USER_CODE' => self::getUserCode($params['connector']),
							'CONNECTOR' => $params,
							'DEFERRED_JOIN' => 'Y',
							'VOTE_SESSION' => $voteSession? 'Y': 'N'
						]);
						if (
							$resultLoadSession ||
							$session->isCloseVote()
						)
						{
							$addMessage["TO_CHAT_ID"] = $session->getData('CHAT_ID');
							if (!empty($params['message']['files']))
							{
								$params['message']['files'] = \CIMDisk::UploadFileFromMain(
									$session->getData('CHAT_ID'),
									$params['message']['files']
								);

								if ($params['message']['files'])
								{
									$addMessage["PARAMS"]['FILE_ID'] = $params['message']['files'];
								}
								else if ($addMessage["MESSAGE"] == '' && empty($addMessage["ATTACH"]))
								{
									$addMessage["MESSAGE"] = '[FILE]';
								}
							}
						}

						if ($resultLoadSession)
						{
							if ($session->isNowCreated())
							{
								$customDataMessage = '';
								$customDataAttach = null;

								$customData = \Bitrix\ImOpenLines\Widget\Cache::get($params['connector']['user_id'], 'CUSTOM_DATA');
								if ($customData)
								{
									$customDataAttach = \CIMMessageParamAttach::GetAttachByJson($customData);
									if ($customDataAttach && $customDataAttach->IsEmpty())
									{
										$customDataAttach = null;
									}
									else
									{
										$customDataMessage = '[B]'.Loc::getMessage('IMOL_CONNECTOR_RECEIVED_DATA').'[/B]';
									}
								}
								else // TODO remove this after delete old livechat - see \Bitrix\ImOpenLines\Connector::saveCustomData
								{
									if ($params['connector']['connector_id'] == self::TYPE_LIVECHAT)
									{
										$orm = ChatTable::getById($params['chat']['id']);
										$guestChatData = $orm->fetch();
									}
									else
									{
										$guestChatData = $session->getChat()->getData();
									}

									if ($guestChatData && $guestChatData['DESCRIPTION'] <> '')
									{
										$customDataMessage = '[B]'.Loc::getMessage('IMOL_CONNECTOR_RECEIVED_DATA').'[/B][BR] '.$guestChatData['DESCRIPTION'];
									}
								}

								if ($params['connector']['connector_id'] == self::TYPE_LIVECHAT)
								{
									$chat = new Chat($params['chat']['id']);
									$chat->updateFieldData([Chat::FIELD_LIVECHAT => [
										'SESSION_ID' => $session->getData('ID'),
										'SHOW_FORM' => 'Y'
									]]);
								}

								if ($customDataMessage)
								{
									Im::addMessage([
										'TO_CHAT_ID' => $session->getData('CHAT_ID'),
										'MESSAGE' => $customDataMessage,
										'ATTACH' => $customDataAttach,
										'SYSTEM' => 'Y',
										'SKIP_COMMAND' => 'Y',
										'PARAMS' => [
											'CLASS' => 'bx-messenger-content-item-system'
										],
									]);
								}
							}

							$session->joinUser();

							if($session->getData('OPERATOR_ID') > 0)
							{
								$userViewChat = \CIMContactList::InRecent($session->getData('OPERATOR_ID'), IM_MESSAGE_OPEN_LINE, $session->getData('CHAT_ID'));
							}
							else
							{
								$userViewChat  = false;
							}

							if (
								$voteSession &&
								$session->getData('WAIT_VOTE') == 'Y'
							)
							{
								$voteValue = 0;
								if (mb_strlen($addMessage["MESSAGE"]) > 1)
								{
									$userViewChat = true;
								}
								if (mb_strpos($addMessage["MESSAGE"], '1') === 0)
								{
									$voteValue = 5;
									$addMessage["MESSAGE"] = '[like]'.mb_substr($addMessage["MESSAGE"], 1);
									$addVoteResult = $session->getConfig('VOTE_MESSAGE_2_LIKE');
								}
								else if (mb_strpos($addMessage["MESSAGE"], '0') === 0)
								{
									$voteValue = 1;
									$addMessage["MESSAGE"] = '[dislike]'.mb_substr($addMessage["MESSAGE"], 1);
									$addVoteResult = $session->getConfig('VOTE_MESSAGE_2_DISLIKE');
								}
								if ($addVoteResult)
								{
									$voteEventParams = [
										'SESSION_DATA' => $session->getData(),
										'VOTE' => $voteValue,
									];
									$event = new Main\Event('imopenlines', 'OnSessionVote', $voteEventParams);
									$event->send();

									$addMessage['RECENT_ADD'] = $userViewChat? 'Y': 'N';
									$session->update(['VOTE' => $voteValue, 'WAIT_VOTE' => 'N']);

									if($session->getConfig('VOTE_CLOSING_DELAY') == 'Y')
									{
										$finishSession = true;
									}

									Chat::sendRatingNotify(Chat::RATING_TYPE_CLIENT, $session->getData('ID'), $voteValue, $session->getData('OPERATOR_ID'), $session->getData('USER_ID'));
								}
							}
							else
							{
								$voteSession = false;
							}
						}

						if (
							$resultLoadSession ||
							$session->isCloseVote()
						)
						{
							$event = new Main\Event('imopenlines', self::EVENT_IMOPENLINE_MESSAGE_RECEIVE, $addMessage);
							$event->send();

							$eventMessageFields = $event->getParameters();
							if (!empty($eventMessageFields['MESSAGE']))
							{
								$addMessage['MESSAGE'] = $eventMessageFields['MESSAGE'];
							}
							if (!empty($eventMessageFields['PARAMS']))
							{
								$addMessage['PARAMS'] = $eventMessageFields['PARAMS'];
							}

							$messageId = Im::addMessage($addMessage);
						}

						if (
							$resultLoadSession &&
							!empty($messageId)
						)
						{
							$isGroupChatAllowed = \Bitrix\ImConnector\Connector::isChatGroup($params['connector']['connector_id']) != true;

							if ($addMessage["MESSAGE"] && $params['extra']['disable_tracker'] !== 'Y' && $isGroupChatAllowed)
							{
								$tracker = new \Bitrix\ImOpenLines\Tracker();
								$tracker->setSession($session);
								$tracker->message([
									'ID' => $messageId,
									'TEXT' => $addMessage["MESSAGE"]
								]);
							}
							if ($params['message']['id'])
							{
								if ($params['connector']['connector_id'] == self::TYPE_LIVECHAT)
								{
									if ($session->isNowCreated())
									{
										$updateParams = [
											'CONNECTOR_MID' => $messageId,
											'IMOL_SID' => $session->getData('ID'),
											'IMOL_FORM' => 'welcome',
											'TYPE' => 'lines',
											'COMPONENT_ID' => 'bx-imopenlines-message',
										];
									}
									else
									{
										$updateParams = [
											'CONNECTOR_MID' => $messageId,
										];
									}
									\CIMMessageParam::Set($params['message']['id'], $updateParams);
									\CIMMessageParam::SendPull($params['message']['id'], array_keys($updateParams));

									\Bitrix\ImOpenLines\Mail::removeSessionFromMailQueue($session->getData('ID'), false);
								}

								\CIMMessageParam::Set($messageId, ['CONNECTOR_MID' => $params['message']['id']]);
								if (!in_array($params['connector']['connector_id'], [self::TYPE_LIVECHAT, self::TYPE_NETWORK]))
								{
									\CIMMessageParam::SendPull($messageId, ['CONNECTOR_MID']);
								}
							}

							if ($addVoteResult)
							{
								Im::addMessage([
									"TO_CHAT_ID" => $session->getData('CHAT_ID'),
									"MESSAGE" => $addVoteResult,
									"SYSTEM" => 'Y',
									"IMPORTANT_CONNECTOR" => 'Y',
									"PARAMS" => [
										"CLASS" => "bx-messenger-content-item-ol-output"
									],
									"RECENT_ADD" => $userViewChat? 'Y': 'N'
								]);
							}

							//Automatic messages
							(new AutomaticAction($session))->automaticAddMessage($messageId, $finishSession, $voteSession);

							$limit = ImConnector\Connector::getReplyLimit($params['connector']['connector_id']);
							$chat = $session->getChat();
							if (
								!empty($limit['BLOCK_DATE']) &&
								!empty($limit['BLOCK_REASON']) &&
								$chat
							)
							{
								$limit['BLOCK_DATE'] = (new DateTime())->add($limit['BLOCK_DATE'].' SECONDS');
								ReplyBlock::add($session->getData('ID'), $chat, $limit);
							}
							elseif (ImConnector\Connector::isNeedToAutoDeleteBlock($params['connector']['connector_id']))
							{
								ReplyBlock::delete($session->getData('ID'), $chat);
							}

							$updateSession['MESSAGE_COUNT'] = true;
							$updateSession['DATE_LAST_MESSAGE'] = new DateTime();

							if (isset($params['extra']))
							{
								foreach($params['extra'] as $field => $value)
								{
									$oldValue = $session->getData($field);
									if ($oldValue != $value)
									{
										$updateSession[$field] = $value;
									}
								}
							}

							$session->update($updateSession);

							if (
								$session->getConfig('AGREEMENT_MESSAGE') == 'Y' &&
								!$session->chat->isNowCreated() &&
								$session->getUser() && $session->getUser('USER_ID') == $params['message']['user_id'] &&
								$session->getUser('USER_CODE') && $session->getUser('AGREES') == 'N'
							)
							{
								\Bitrix\ImOpenLines\Common::setUserAgrees([
									'AGREEMENT_ID' => $session->getConfig('AGREEMENT_ID'),
									'CRM_ACTIVITY_ID' => $session->getData('CRM_ACTIVITY_ID'),
									'SESSION_ID' => $session->getData('SESSION_ID'),
									'CONFIG_ID' => $session->getData('CONFIG_ID'),
									'USER_CODE' => $session->getUser('USER_CODE'),
								]);
							}

							$queueManager = Queue::initialization($session);
							if($queueManager)
							{
								$queueManager->automaticActionAddMessage($finishSession, $voteSession);
							}

							if (!$session->isNowCreated() && $finishSession === true)
							{
								$session->finish(true);
							}

							$this->callMessageTrigger($session, $messageId, $addMessage);

							//In case it's not a vote message or system message we make a new record
							if (!$voteSession && $params['message']['user_id'] != 0 && !ImUser::getInstance($params['message']['user_id'])->isBot())
							{
								$kpi = new KpiManager($session->getData('ID'));
								$kpi->addMessage(
									[
										'MESSAGE_ID' => $messageId,
										'LINE_ID' => $session->getData('CONFIG_ID'),
										'OPERATOR_ID' => $session->getData('OPERATOR_ID')
									]
								);
							}

							$result = [
								'SESSION_ID' => $session->isNowCreated()? $session->getData('ID'): 0,
								'MESSAGE_ID' => $messageId
							];
						}

						if (
							!$resultLoadSession &&
							$session->isCloseVote()
						)
						{
							Im::addCloseVoteMessage($session->getData('CHAT_ID'), $session->getConfig('VOTE_TIME_LIMIT'));
						}
					}

					$isAddMessage = true;
					Tools\Lock::getInstance()->delete($keyLock);
				}
				else
				{
					sleep($iteration);
				}
			}
			while ($isAddMessage === false);
		}

		return $result;
	}

	/**
	 * @param Session $session
	 * @param $messageId
	 * @param $messageData
	 *
	 * @return Result
	 * @throws Main\LoaderException
	 */
	protected function callMessageTrigger(Session $session, $messageId, $messageData)
	{
		$crm = new Crm($session);
		$result = new Result();

		if (
			$crm->isLoaded() &&
			$session->getData('CRM') == 'Y' &&
			$session->getData('CRM_ACTIVITY_ID') > 0
		)
		{
			$activities = \Bitrix\ImOpenLines\Crm\Common::getActivityBindingsFormatted($session->getData('CRM_ACTIVITY_ID'));
			$message = [
				'ID' => $messageId,
				'TEXT' => $messageData['MESSAGE'],
			];
			if(Loader::includeModule('im'))
			{
				$message['PLAIN_TEXT'] = ImText::removeBbCodes($messageData['MESSAGE']);
			}

			$result = $crm->executeAutomationMessageTrigger(
				$activities,
				[
					'CONFIG_ID' => $session->getData('CONFIG_ID'),
					'MESSAGE' => $message
				]
			);
		}

		return $result;
	}

	/**
	 * @param $params
	 * @return bool
	 */
	public function updateMessage($params)
	{
		if (empty($params))
			return false;

		$chat = new Chat();
		$result = $chat->load(Array(
			'USER_CODE' => self::getUserCode($params['connector']),
			'ONLY_LOAD' => 'Y',
		));
		if (!$result)
			return false;

		$messageIds = \CIMMessageParam::GetMessageIdByParam('CONNECTOR_MID', $params['message']['id'], $chat->getData('ID'));
		if (empty($messageIds))
			return false;

		\CIMMessenger::DisableMessageCheck();
		foreach($messageIds as $messageId)
		{
			\CIMMessenger::Update($messageId, $params['message']['text'], true, true, null, true);
		}
		\CIMMessenger::EnableMessageCheck();

		return true;
	}

	/**
	 * @param $params
	 * @return bool
	 */
	public function deleteMessage($params)
	{
		if (empty($params))
			return false;

		$chat = new Chat();
		$result = $chat->load(Array(
			'USER_CODE' => self::getUserCode($params['connector']),
			'ONLY_LOAD' => 'Y',
		));
		if (!$result)
			return false;

		$messageIds = \CIMMessageParam::GetMessageIdByParam('CONNECTOR_MID', $params['message']['id'], $chat->getData('ID'));
		if (empty($messageIds))
			return false;

		\CIMMessenger::DisableMessageCheck();
		foreach($messageIds as $messageId)
		{
			\CIMMessenger::Delete($messageId, null, false, true);
		}
		\CIMMessenger::EnableMessageCheck();

		return true;
	}

	/**
	 * Sending messages to external channels.
	 *
	 * @param $params
	 * @return Result
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function sendMessage($params): Result
	{
		$result = $this->result;

		if($result->isSuccess())
		{
			Log::write($params, 'SEND MESSAGE');

			$fields = [
				'im' => [
					'chat_id' => $params['message']['chat_id'],
					'message_id' => $params['message']['id']
				],
				'message' => [
					'user_id' => $params['message']['user_id'],
					'text' => $params['message']['text'],
					'files' => $params['message']['files'],
					'attachments' => $params['message']['attachments'],
					'params' => $params['message']['params'],
				],
				'chat' => [
					'id' => $params['connector']['chat_id']
				],
			];

			$actualLineId = $params['connector']['line_id'];

			$session = new Session();
			if($params['no_session'] !== 'Y')
			{
				$resultLoadSession = $session->load([
					'USER_CODE' => self::getUserCode($params['connector']),
					'MODE' => Session::MODE_OUTPUT,
					'OPERATOR_ID' => $params['message']['user_id']
				]);
				if (!$resultLoadSession)
				{
					$result->addError(new Error('Failed to load session', 'IMOPENLINES_ERROR_LOAD_SESSION', __METHOD__));
				}

				if (
					$result->isSuccess() &&
					ReplyBlock::isBlocked($session)
				)
				{
					$result->addError(new Error('This chat is blocked for sending outgoing messages', 'IMOPENLINES_ERROR_SESSION_BLOCKED', __METHOD__));
				}

				if(
					$result->isSuccess() &&
					$session->getConfig('ACTIVE') !== 'Y'
				)
				{
					$result->addError(new Error('The open line is deactivated', 'IMOPENLINES_ERROR_LINE_DEACTIVATED', __METHOD__));
				}

				if(
					$result->isSuccess() &&
					$params['message']['system'] !== 'Y'
				)
				{
					$updateSession = [
						'MESSAGE_COUNT' => true,
						'DATE_LAST_MESSAGE' => new DateTime()
					];

					if (
						!$session->getData('DATE_FIRST_ANSWER') &&
						!empty($session->getData('OPERATOR_ID')) && Queue::isRealOperator($session->getData('OPERATOR_ID'))
					)
					{
						$currentTime = new DateTime();
						$updateSession['DATE_FIRST_ANSWER'] = $currentTime;
						$updateSession['TIME_FIRST_ANSWER'] = $currentTime->getTimestamp()-$session->getData('DATE_CREATE')->getTimestamp();
					}

					$eventData = [
						'STATUS_BEFORE' => $session->getData('STATUS'),
						'CHAT_ENTITY_ID' => self::getUserCode($params['connector']),
						'AUTHOR_ID' => $params['message']['user_id']
					];
					$session->update($updateSession);
					$eventData['STATUS_AFTER'] = $session->getData('STATUS');
					Queue\Event::checkFreeSlotBySendMessage($eventData);
				}

				if($result->isSuccess())
				{
					$actualLineId = Queue::getActualLineId([
						'LINE_ID' =>  $params['connector']['line_id'],
						'USER_CODE' => $session->getData('USER_CODE')
					]);
				}
			}

			if(
				$params['no_session'] !== 'Y' &&
				$result->isSuccess()
			)
			{
				//Automatic messages
				(new AutomaticAction($session))->automaticSendMessage($params['message']['id']);
			}

			if (
				$params['no_session'] !== 'Y' &&
				$params['message']['system'] != 'Y' &&
				$result->isSuccess() &&
				!ImUser::getInstance($session->getData('OPERATOR_ID'))->isBot()
			)
			{
				KpiManager::setSessionLastKpiMessageAnswered($session->getData('ID'));
			}

			if ($result->isSuccess())
			{
				$fields['user'] = Queue::getUserData($actualLineId, $fields['message']['user_id']);

				$connector = new Output($params['connector']['connector_id'], $params['connector']['line_id']);
				$resultSendMessage = $connector->sendMessage([$fields]);

				if(!$resultSendMessage->isSuccess())
				{
					$result->addErrors($resultSendMessage->getErrors());
				}
			}
		}

		return $result;
	}

	/**
	 * @param $fields
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function sendStatusWriting($fields): bool
	{
		Log::write([$fields], 'STATUS WRITING');
		if(self::isNeedConnectorWritingStatus($fields['connector']['connector_id']))
		{
			$connector = new Output($fields['connector']['connector_id'], $fields['connector']['line_id']);
			$result = $connector->sendStatusWriting([$fields]);

			if (!$result->isSuccess())
			{
				$this->error = new Error(__METHOD__, 'CONNECTOR_SEND_ERROR', $result->getErrorMessages());
			}
		}

		return false;
	}

	/**
	 * @param $connector
	 * @param $messages
	 * @param $event
	 * @return false
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function sendStatusRead($connector, $messages, $event)
	{
		if (empty($messages))
			return false;

		if ($connector['connector_id'] == self::TYPE_NETWORK)
		{

		}
		else if ($connector['connector_id'] == 'lines')
		{
			Log::write(array($connector, $messages, $event), 'STATUS READ');

			$maxId = 0;
			foreach ($messages as $messageId)
			{
				$maxId = $maxId < $messageId? $messageId: $maxId;
			}

			$chat = new \CIMChat();
			$chat->SetReadMessage($connector['chat_id'], $maxId, true);
		}
		else if (\Bitrix\ImOpenLines\Connector::isLiveChat($connector['connector_id']))
		{
			Log::write(array($connector, $messages, $event), 'STATUS READ');

			$maxId = 0;
			foreach ($messages as $messageId)
			{
				$maxId = $maxId < $messageId? $messageId: $maxId;
			}

			$chat = new \Bitrix\ImOpenLines\Chat($connector['chat_id']);
			$chat->updateFieldData([\Bitrix\ImOpenLines\Chat::FIELD_LIVECHAT => [
				'READED' => 'Y',
				'READED_ID' => $maxId,
				'READED_TIME' => new DateTime()
			]]);
		}
		else
		{
			$sendMessages = Array();
			foreach ($messages as $messageId)
			{
				$sendMessages[] = Array(
					'chat' => Array(
						'id' => $connector['chat_id']
					),
					'message' => Array(
						'id' => $messageId
					)
				);
			}

			$connector = new Output($connector['connector_id'], $connector['line_id']);
			$connector->setStatusReading($sendMessages);
		}

		return false;
	}

	/**
	 * @param $params
	 * @return string
	 */
	public static function getUserCode($params)
	{
		return $params['connector_id'].'|'.$params['line_id'].'|'.$params['chat_id'].'|'.$params['user_id'];
	}

	/**
	 * @param $fields
	 * @param $chat
	 * @return array|bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onBeforeMessageSend($fields, $chat)
	{
		if ($chat['CHAT_ENTITY_TYPE'] != 'LINES')
			return true;

		if ($fields['FROM_USER_ID'] <= 0)
			return true;

		if (ImUser::getInstance($fields['FROM_USER_ID'])->isConnector())
			return true;

		if (!Loader::includeModule('imconnector'))
			return false;

		$result = true;
		//TODO: Replace with the method \Bitrix\ImOpenLines\Chat::parseLiveChatEntityId
		list($connectorId, $lineId) = explode('|', $chat['CHAT_ENTITY_ID']);

		if ($connectorId == self::TYPE_NETWORK)
		{}
		else if ($connectorId == self::TYPE_NETWORK)
		{}
		else if (Loader::includeModule('imconnector'))
		{
			$status = \Bitrix\ImConnector\Status::getInstance($connectorId, $lineId);
			if (!$status->isStatus() || !Config::isConfigActive($lineId))
			{
				$result = Array(
					'result' => false,
					'reason' => Loc::getMessage('IMOL_CONNECTOR_STATUS_ERROR_NEW')
				);
			}
		}

		return $result;
	}

	/**
	 * @param $messageId
	 * @param $messageFields
	 * @param $flags
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onMessageUpdate($messageId, $messageFields, $flags)
	{
		if (
			$flags['BY_EVENT'] ||
			!isset($messageFields['PARAMS']['CONNECTOR_MID'])
		)
		{
			return false;
		}

		//TODO: Replace with the method \Bitrix\ImOpenLines\Chat::parseLinesChatEntityId or \Bitrix\ImOpenLines\Chat::parseLiveChatEntityId
		list($connectorId, $lineId, $connectorChatId) = explode('|', $messageFields['CHAT_ENTITY_ID']);

		if ($messageFields['CHAT_ENTITY_TYPE'] == 'LINES')
		{
		}
		else if (self::isLiveChat($messageFields['CHAT_ENTITY_TYPE']))
		{
			$connectorId = self::TYPE_LIVECHAT;
		}
		else
		{
			return false;
		}

		if (
			$messageFields['SYSTEM'] != 'Y' &&
			self::isEnableSendMessageWithSignature($connectorId, $lineId) &&
			$messageFields['AUTHOR_ID'] > 0
		)
		{
			$flags['TEXT'] = '[b]' . htmlspecialchars_decode(self::getOperatorName($lineId, $messageFields['AUTHOR_ID'], $messageFields['CHAT_ENTITY_ID'])) . ':[/b]'.($flags['TEXT'] <> ''? '[br] '.$flags['TEXT']: '');
		}

		if ($connectorId == self::TYPE_LIVECHAT)
		{
			\CIMMessenger::DisableMessageCheck();
			foreach($messageFields['PARAMS']['CONNECTOR_MID'] as $mid)
			{
				\CIMMessenger::Update($mid, $flags['TEXT'], $flags['URL_PREVIEW'], $flags['EDIT_FLAG'], $flags['USER_ID'], true);
			}
			\CIMMessenger::EnableMessageCheck();
		}
		else if (
			isset($lineId) && isset($connectorChatId) &&
			!empty($messageFields['PARAMS']['CONNECTOR_MID']) &&
			is_array($messageFields['PARAMS']['CONNECTOR_MID']) &&
			Loader::includeModule('imconnector')
		)
		{
			$fields = [
				'im' => [
					'chat_id' => $messageFields['CHAT_ID'],
					'message_id' => $messageFields['ID']
				],
				'message' => [
					'id' => $messageFields['PARAMS']['CONNECTOR_MID'],
					'text' => $flags['TEXT'],
				],
				'chat' => [
					'id' => $connectorChatId
				],
			];

			$connector = new Output($connectorId, $lineId);
			$connector->updateMessage([$fields]);
		}

		return true;
	}

	/**
	 * @param $messageId
	 * @param $messageFields
	 * @param $flags
	 * @return bool
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 */
	public static function onMessageDelete($messageId, $messageFields, $flags)
	{
		if (
			$flags['BY_EVENT'] ||
			!isset($messageFields['PARAMS']['CONNECTOR_MID'])
		)
		{
			return false;
		}

		if ($messageFields['CHAT_ENTITY_TYPE'] == 'LINES')
		{
			list($connectorType, $lineId, $connectorChatId) = explode("|", $messageFields['CHAT_ENTITY_ID']);
		}
		else if (Connector::isLiveChat($messageFields['CHAT_ENTITY_TYPE']))
		{
			$connectorType = self::TYPE_LIVECHAT;
		}
		else
		{
			return false;
		}

		if ($connectorType == self::TYPE_LIVECHAT)
		{
			\CIMMessenger::DisableMessageCheck();
			foreach($messageFields['PARAMS']['CONNECTOR_MID'] as $mid)
			{
				\CIMMessenger::Delete($mid, $flags['USER_ID'], $flags['COMPLETE_DELETE'], true);
			}
			\CIMMessenger::EnableMessageCheck();
		}
		else if(
			isset($lineId) &&
			isset($connectorChatId) &&
			Loader::includeModule('imconnector')
		)
		{
			$fields = [];
			foreach($messageFields['PARAMS']['CONNECTOR_MID'] as $mid)
			{
				$fields[] = [
					'im' => [
						'chat_id' => $messageFields['CHAT_ID'],
						'message_id' => $messageFields['ID']
					],
					'message' => [
						'id' => $mid
					],
					'chat' => [
						'id' => $connectorChatId
					],
				];
			}
			if (!empty($fields))
			{
				$connector = new Output($connectorType, $lineId);
				$connector->deleteMessage($fields);
			}
		}

		return true;
	}

	/**
	 * @param $messageId
	 * @param $messageFields
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 * @throws Main\NotImplementedException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onMessageSend($messageId, $messageFields)
	{
		if ($messageFields['CHAT_ENTITY_TYPE'] != 'LINES')
			return false;

		$messageFields['MESSAGE_ID'] = $messageId;
		Log::write($messageFields, 'CONNECTOR MESSAGE SEND');

		if ($messageFields['AUTHOR_ID'] > 0)
		{
			$user = ImUser::getInstance($messageFields['AUTHOR_ID']);
			if ($user->isConnector())
				return false;
		}

		if (
			(
				$messageFields['SILENT_CONNECTOR'] == 'Y' ||
				$messageFields['CHAT_'.Chat::getFieldName(Chat::FIELD_SILENT_MODE)] == 'Y'
			)
			&& $messageFields['IMPORTANT_CONNECTOR'] != 'Y'
		)
		{
			\CIMMessageParam::Set($messageId, ['CLASS' => 'bx-messenger-content-item-system']);
			\CIMMessageParam::SendPull($messageId, ['CLASS']);
			return false;
		}

		if ($messageFields['SKIP_CONNECTOR'] == 'Y')
			return false;

		if ($messageFields['IMPORTANT_CONNECTOR'] != 'Y' && $messageFields['SYSTEM'] == 'Y')
			return false;

		//TODO: Replace with the method \Bitrix\ImOpenLines\Chat::parseLinesChatEntityId or \Bitrix\ImOpenLines\Chat::parseLiveChatEntityId
		list($connectorId, $lineId, $connectorChatId, $connectorUserId) = explode('|', $messageFields['CHAT_ENTITY_ID']);

		$event = new Main\Event('imopenlines', self::EVENT_IMOPENLINE_MESSAGE_SEND, $messageFields);
		$event->send();

		$eventMessageFields = $event->getParameters();
		if (!empty($eventMessageFields['MESSAGE']))
		{
			$messageFields['MESSAGE'] = $eventMessageFields['MESSAGE'];
		}
		if (!empty($eventMessageFields['PARAMS']))
		{
			$messageFields['PARAMS'] = $eventMessageFields['PARAMS'];
		}

		if ($connectorId == self::TYPE_LIVECHAT)
		{
			$resultLoadSession = false;

			if (isset($messageFields['PARAMS']['CLASS']))
			{
				$messageFields['PARAMS']['CLASS'] = str_replace('bx-messenger-content-item-ol-output', "", $messageFields['PARAMS']['CLASS']);
			}

			$params = [];
			$allowedFields = ['CLASS', 'TYPE', 'COMPONENT_ID', 'CRM_FORM_ID', 'url', 'fromSalescenterApplication', 'richUrlPreview'];

			foreach ($messageFields['PARAMS'] as $key => $value)
			{
				if (in_array($key, $allowedFields))
				{
					$params[$key] = $value;
				}
				else if (mb_strpos($key, 'IMOL_') === 0)
				{
					$params[$key] = $value;
				}
				else if (mb_strpos($key, 'IS_') === 0)
				{
					$params[$key] = $value;
				}
			}

			$message = [
				'TO_CHAT_ID' => $connectorChatId,
				'FROM_USER_ID' => $messageFields['AUTHOR_ID'],
				'SYSTEM' => $messageFields['SYSTEM'],
				'URL_PREVIEW' => $messageFields['URL_PREVIEW'],
				'ATTACH' => $messageFields['ATTACH'],
				'PARAMS' => $params,
				'SKIP_USER_CHECK' => 'Y',
				'SKIP_COMMAND' => 'Y',
				'SKIP_CONNECTOR' => 'Y',
				'EXTRA_PARAMS' => [
					'CONTEXT' => 'LIVECHAT',
					'LINE_ID' => $lineId
				],
			];
			if (array_key_exists('MESSAGE', $messageFields))
			{
				$message['MESSAGE'] = $messageFields['MESSAGE'];
			}

			if($messageFields['NO_SESSION_OL'] !== 'Y')
			{
				$session = new Session();
				$resultLoadSession = $session->load([
					'MODE' => Session::MODE_OUTPUT,
					'USER_CODE' => $messageFields['CHAT_ENTITY_ID'],
					'OPERATOR_ID' => $messageFields['AUTHOR_ID']
				]);

				if($resultLoadSession)
				{
					$updateSession = [
						'MESSAGE_COUNT' => true,
						'DATE_LAST_MESSAGE' => new DateTime(),
						'DATE_MODIFY' => new DateTime(),
						'USER_ID' => $messageFields['AUTHOR_ID'],
					];
					if (
						!$session->getData('DATE_FIRST_ANSWER') &&
						!empty($session->getData('OPERATOR_ID')) &&
						Queue::isRealOperator($session->getData('OPERATOR_ID'))
					)
					{
						$currentTime = new DateTime();
						$updateSession['DATE_FIRST_ANSWER'] = $currentTime;
						$updateSession['TIME_FIRST_ANSWER'] = $currentTime->getTimestamp()-$session->getData('DATE_CREATE')->getTimestamp();
					}

					$eventData = [
						'STATUS_BEFORE' => $session->getData('STATUS'),
						'CHAT_ENTITY_ID' => $messageFields['CHAT_ENTITY_ID'],
						'AUTHOR_ID' => $messageFields['AUTHOR_ID']
					];
					$session->update($updateSession);
					$eventData['STATUS_AFTER'] = $session->getData('STATUS');
					Queue\Event::checkFreeSlotBySendMessage($eventData);

					//for livechat only condition
					if (
						!ImUser::getInstance($session->getData('OPERATOR_ID'))->isBot() &&
						$messageFields['SYSTEM'] != 'Y'
					)
					{
						KpiManager::setSessionLastKpiMessageAnswered($session->getData('ID'));
					}
				}
			}

			$isActiveKeyboard = false;
			if (
				!empty($connectorChatId) &&
				$connectorChatId > 0 &&
				Loader::includeModule('imconnector'))
			{
				//Processing for native messages
				$interactiveMessage = InteractiveMessage\Output::getInstance($messageFields['TO_CHAT_ID'], ['connectorId' => 'livechat']);
				$message = $interactiveMessage->nativeMessageProcessing($message);

				$isActiveKeyboard = $interactiveMessage->isLoadedKeyboard();
			}

			$mid = Im::addMessage($message);
			if (
				$messageId &&
				$mid &&
				($messageFields['NO_SESSION_OL'] === 'Y' || $resultLoadSession)
			)
			{
				$paramsMessageLiveChat = ['CONNECTOR_MID' => $messageId];

				if(!empty($session) && $resultLoadSession)
				{
					$userData = Queue::getUserData($session->getData('CONFIG_ID'), $messageFields['AUTHOR_ID'], true);

					if(!empty($userData))
					{
						$paramsMessageLiveChat['NAME'] = $userData['NAME'];
					}

					//TODO: remove code duplication.
					//Automatic messages
					(new AutomaticAction($session))->automaticSendMessage($messageId);
				}

				\CIMMessageParam::Set($messageId, ['CONNECTOR_MID' => $mid]);
				\CIMMessageParam::SendPull($messageId, $paramsMessageLiveChat);
				\CIMMessageParam::Set($mid, $paramsMessageLiveChat);
				\CIMMessageParam::SendPull($mid, $paramsMessageLiveChat);
			}
			if(
				$messageFields['NO_SESSION_OL'] !== 'Y' &&
				!empty($session) &&
				$resultLoadSession
			)
			{
				\Bitrix\ImOpenLines\Mail::addSessionToMailQueue($session->getData('ID'), false);
			}
		}
		else
		{
			if (!self::isEnableSendSystemMessage($connectorId) && $messageFields['SYSTEM'] == 'Y')
			{
				return false;
			}

			$params = [];
			$allowedFields = ['CLASS', 'url', 'fromSalescenterApplication', 'richUrlPreview'];

			foreach ($messageFields['PARAMS'] as $key => $value)
			{
				if (in_array($key, $allowedFields))
				{
					$params[$key] = $value;
				}
				else if (mb_strpos($key, 'IMOL_') === 0)
				{
					$params[$key] = $value;
				}
				else if (mb_strpos($key, 'IS_') === 0)
				{
					$params[$key] = $value;
				}
			}

			$attaches = [];
			if (isset($messageFields['PARAMS']['ATTACH']))
			{
				foreach ($messageFields['PARAMS']['ATTACH'] as $attach)
				{
					if ($attach instanceof \CIMMessageParamAttach)
					{
						$attaches[] = $attach->GetJSON();
					}
				}
			}

			$files = [];
			if (isset($messageFields['FILES']) && Loader::includeModule('disk'))
			{
				$config = Model\ConfigTable::getById($lineId)->fetch();
				$langId = '';
				if ($config && $config['LANGUAGE_ID'])
				{
					$langId = mb_strtolower($config['LANGUAGE_ID']);
				}

				foreach ($messageFields['FILES'] as $file)
				{
					$fileModel = \Bitrix\Disk\File::loadById($file['id']);
					if (!$fileModel)
						continue;

					$file['link'] = \CIMDisk::GetFileLink($fileModel);

					if (!$file['link'])
						continue;

					$merged = false;
					if (\Bitrix\Disk\TypeFile::isImage($fileModel))
					{
						$source = $fileModel->getFile();
						if ($source)
						{
							$files[] = [
								'name' => $file['name'],
								'type' => $file['type'],
								'link' => $file['link'],
								'width' => $source["WIDTH"],
								'height' => $source["HEIGHT"],
								'size' => $file['size']
							];
							$merged = true;
						}
					}

					if (!$merged)
					{
						$files[] = [
							'name' => $file['name'],
							'type' => $file['type'],
							'link' => $file['link'],
							'size' => $file['size']
						];
					}
				}
			}
			if (empty($attaches) && empty($files) && empty($messageFields['MESSAGE']) && $messageFields['MESSAGE'] !== "0" && empty($params['url']))
				return false;

			if ($messageFields['SYSTEM'] != 'Y' &&
				self::isEnableSendMessageWithSignature($connectorId, $lineId) &&
				$messageFields['AUTHOR_ID'] > 0 &&
				!self::isNeedRichLinkData($connectorId, $messageFields['MESSAGE'])
			)
			{
				$messageFields['MESSAGE'] = '[b]' . htmlspecialchars_decode(self::getOperatorName($lineId, $messageFields['AUTHOR_ID'], $messageFields['CHAT_ENTITY_ID'])) . ':[/b]'.($messageFields['MESSAGE'] <> ''? '[br] '.$messageFields['MESSAGE']: '');
			}

			$fields = [
				'connector' => [
					'connector_id' => $connectorId,
					'line_id' => $lineId,
					'user_id' => $connectorUserId,
					'chat_id' => $connectorChatId,
				],
				'message' => [
					'id' => $messageId,
					'chat_id' => $messageFields['TO_CHAT_ID'],
					'user_id' => $messageFields['FROM_USER_ID'],
					'text' => $messageFields['MESSAGE'],
					'files' => $files,
					'attachments' => $attaches,
					'params' => $params,
					'system' => $messageFields['SYSTEM']
				],
				'no_session' => $messageFields['NO_SESSION_OL']
			];

			if (in_array($connectorId, self::getListShowDeliveryStatus()))
			{
				\CIMMessageParam::Set(
					$messageId,
					[
						'SENDING' => 'Y',
						'SENDING_TS' => time()
					]
				);
				\CIMMessageParam::SendPull(
					$messageId,
					[
						'SENDING',
						'SENDING_TS'
					]
				);
				//TODO: To turn on the new messenger
				//Pull\Event::send();
			}

			$manager = new self();
			$resultSendMessage = $manager->sendMessage($fields);
			if (!$resultSendMessage->isSuccess())
			{
				$isErrorLineDeactivated = $resultSendMessage->getErrorCollection()->get('IMOPENLINES_ERROR_LINE_DEACTIVATED');
				if(!$isErrorLineDeactivated)
				{
					Im::addMessage([
						'TO_CHAT_ID' => $messageFields['TO_CHAT_ID'],
						'MESSAGE' => Loc::getMessage('IMOL_CHAT_ERROR_CONNECTOR_SEND'),
						'SYSTEM' => 'Y',
					]);
				}

				return false;
			}
		}

		return true;
	}

	/**
	 * Typing notification.
	 *
	 * @param $params
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onStartWriting($params)
	{
		if (
			empty($params['CHAT']) ||
			!in_array($params['CHAT']['ENTITY_TYPE'], ['LINES', 'LIVECHAT']) ||
			$params['BY_EVENT']
		)
		{
			$result = true;
		}
		else
		{
			if ($params['CHAT']['ENTITY_TYPE'] == 'LINES')
			{
				$chatData = Chat::parseLinesChatEntityId($params['CHAT']['ENTITY_ID']);
				$userCode = $params['CHAT']['ENTITY_ID'];
			}
			else // LIVECHAT
			{
				$chatData = Chat::parseLinesChatEntityId($params['CHAT']['ENTITY_ID']);
				$chatData['connectorChatId'] = 0;
				$chatData['connectorId'] = self::TYPE_LIVECHAT;

				$userCode = $chatData['connectorId'] . '|' . $chatData['lineId'] . '|' . $params['CHAT']['ID'] . '|' . $params['USER_ID'];

				$orm = Model\SessionTable::getList([
					'select' => ['CHAT_ID'],
					'filter' => [
						'=USER_CODE' => $userCode,
						'=CLOSED' => 'N'
					]
				]);
				if ($session = $orm->fetch())
				{
					$chatData['connectorChatId'] = $session['CHAT_ID'];
				}
			}

			if (
				$chatData['connectorChatId'] <= 0 &&
				!self::isNeedConnectorWritingStatus($chatData['connectorId'])
			)
			{
				$result = true;
			}
			else
			{
				$chat = new Chat($params['CHAT']['ID']);
				if (
					$chat->isSilentModeEnabled() ||
					$params['LINES_SILENT_MODE']
				)
				{
					$result = true;
				}
				else
				{
					$actualLineId = Queue::getActualLineId([
						'LINE_ID' =>  $chatData['lineId'],
						'USER_CODE' => $userCode
					]);

					$fields = [
						'connector' => [
							'connector_id' => $chatData['connectorId'],
							'line_id' => $chatData['lineId'],
							'user_id' => $chatData['connectorUserId'],
							'chat_id' => $chatData['connectorChatId'],
						],
						'chat' => ['id' => $chatData['connectorChatId']],
						'user' => Queue::getUserData($actualLineId, $params['USER_ID'])
					];

					$manager = new self();
					$result = $manager->sendStatusWriting($fields);
				}
			}
		}

		return $result;
	}

	/**
	 * @param Main\Event $event
	 * @return array|array[]
	 */
	protected static function preparationDataOnSession(Main\Event $event): array
	{
		$result = [];

		$parameters = $event->getParameters();

		$session = $parameters['RUNTIME_SESSION'];
		if ($session instanceof Session)
		{
			$chatEntityId = Chat::parseLinesChatEntityId($session->getData('USER_CODE'));

			$result = [
				'connector' => [
					'connector_id' => $chatEntityId['connectorId'],
					'line_id' => $chatEntityId['lineId'],
					'chat_id' => $chatEntityId['connectorChatId'],
					'user_id' => $chatEntityId['connectorUserId'],
				],
				'session' => [
					'id' => $session->getData('ID'),
					'closed' => $session->getData('CLOSED'),
					'parent_id' => $session->getData('PARENT_ID'),
				],
				'chat' => ['id' => $chatEntityId['connectorChatId']],
				'user' => ['id' => $chatEntityId['connectorUserId']],
			];
		}

		return $result;
	}

	/**
	 * Event handler for `imopenlines::OnSessionStart`
	 * @see \Bitrix\ImOpenLines\Session::createSession
	 * @param Main\Event $event
	 * @return void
	 */
	public static function onSessionStart(Main\Event $event)
	{
		$fields = self::preparationDataOnSession($event);

		if (
			!empty($fields)
			&& Loader::includeModule('imconnector')
		)
		{
			Log::write($fields, 'SESSION STARTED');

			$connector = new Output($fields['connector']['connector_id'], $fields['connector']['line_id']);
			$connector->sessionStart([$fields]);
		}
	}

	/**
	 * Event handler for `imopenlines::OnSessionFinish`
	 * @see \Bitrix\ImOpenLines\Session::finish
	 * @param Main\Event $event
	 * @return void
	 */
	public static function onSessionFinish(Main\Event $event)
	{
		$fields = self::preparationDataOnSession($event);

		if (
			!empty($fields)
			&& Loader::includeModule('imconnector')
		)
		{
			Log::write($fields, 'SESSION FINISHED');

			$connector = new Output($fields['connector']['connector_id'], $fields['connector']['line_id']);
			$connector->sessionFinish([$fields]);
		}
	}

	/**
	 * @param $params
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onChatRead($params)
	{
		if (!in_array($params['CHAT_ENTITY_TYPE'], Array('LINES', 'LIVECHAT')) || $params['BY_EVENT'])
			return true;

		if ($params['CHAT_ENTITY_TYPE'] == 'LINES')
		{
			//TODO: Replace with the method \Bitrix\ImOpenLines\Chat::parseLinesChatEntityId
			list($connectorId, $lineId, $connectorChatId, $connectorUserId) = explode('|', $params['CHAT_ENTITY_ID']);
		}
		else // LIVECHAT
		{
			$chatId = $params['CHAT_ID'];
			$connectorChatId = 0;
			$connectorId = self::TYPE_LIVECHAT;
			//TODO: Replace with the method \Bitrix\ImOpenLines\Chat::parseLiveChatEntityId
			list($lineId, $connectorUserId) = explode('|', $params['CHAT_ENTITY_ID']);

			$orm = Model\SessionTable::getList(array(
				'select' => Array('ID', 'CHAT_ID'),
				'filter' => array(
					'=USER_CODE' => $connectorId.'|'.$lineId.'|'.$chatId.'|'.$connectorUserId,
					'=CLOSED' => 'N'
				)
			));
			if ($session = $orm->fetch())
			{
				$connectorChatId = $session['CHAT_ID'];
				\Bitrix\ImOpenLines\Mail::removeSessionFromMailQueue($session['ID'], false);
			}
			$connectorId = 'lines';
		}

		$event = $params;

		$connector = Array(
			'connector_id' => $connectorId,
			'line_id' => $lineId,
			'chat_id' => $connectorChatId,
		);

		$application = \Bitrix\Main\Application::getInstance();
		$connection = $application->getConnection();

		$params['END_ID'] = intval($params['END_ID']);

		$messages = Array();
		$query = $connection->query("
			SELECT M.ID, MP.PARAM_VALUE
			FROM b_im_message M
			LEFT JOIN b_im_message_param MP ON MP.MESSAGE_ID = M.ID AND MP.PARAM_NAME = 'CONNECTOR_MID'
			WHERE
			M.CHAT_ID = ".intval($params['CHAT_ID'])." AND
			M.ID > ".intval($params['START_ID']).($params['END_ID']? " AND M.ID < ".(intval($params['END_ID'])+1): "")."
		");
		while($row = $query->fetch())
		{
			$messages[] = $row['PARAM_VALUE'];
		}

		$manager = new self();
		return $manager->sendStatusRead($connector, $messages, $event);
	}

	/**
	 * @param $params
	 * @return array|false
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onReceivedEntity($params)
	{
		$userId = intval($params['user']);

		global $USER;
		if($userId > 0 && !$USER->IsAuthorized() && $USER->Authorize($userId, false, false))
		{
			setSessionExpired(true);
		}

		if (!isset($params['message']['user_id']))
		{
			$params['message']['user_id'] = $params['user'];
		}

		$fields = array(
			'connector' => Array(
				'connector_id' => $params['connector'],
				'line_id' => $params['line'],
				'chat_id' => $params['chat']['id'],
				'user_id' => $params['user'],
			),
			'chat' => $params['chat'],
			'message' => $params['message'],
			'extra' => isset($params['extra'])? $params['extra']: Array()
		);

		Log::write($fields, 'CONNECTOR - ENTITY ADD');

		$manager = new self();
		return $manager->addMessage($fields);
	}

	/**
	 * @param Main\Event $event
	 * @return array|false
	 */
	public static function onReceivedMessage(\Bitrix\Main\Event $event)
	{
		$params = $event->getParameters();
		if (empty($params))
			return false;

		return static::onReceivedEntity($params);
	}

	/**
	 * @param Main\Event $event
	 * @return array|false
	 */
	public static function onReceivedPost(\Bitrix\Main\Event $event)
	{
		$params = $event->getParameters();
		if (empty($params))
			return false;

		$params['message']['id'] = '';

		return static::onReceivedEntity($params);
	}

	/**
	 * @param Main\Event $event
	 * @return bool
	 */
	public static function onReceivedMessageUpdate(\Bitrix\Main\Event $event)
	{
		$params = $event->getParameters();
		if (empty($params))
			return false;

		$userId = intval($params['user']);

		global $USER;
		if($userId > 0 && !$USER->IsAuthorized() && $USER->Authorize($userId, false, false))
		{
			setSessionExpired(true);
		}

		if (!isset($params['message']['user_id']))
		{
			$params['message']['user_id'] = $params['user'];
		}

		$fields = array(
			'connector' => Array(
				'connector_id' => $params['connector'],
				'line_id' => $params['line'],
				'chat_id' => $params['chat']['id'],
				'user_id' => $params['user'],
			),
			'chat' => $params['chat'],
			'message' => $params['message']
		);

		Log::write($fields, 'CONNECTOR - ENTITY UPDATE');
		$manager = new self();
		return $manager->updateMessage($fields);
	}

	/**
	 * @param Main\Event $event
	 * @return bool
	 */
	public static function onReceivedPostUpdate(\Bitrix\Main\Event $event)
	{
		return self::onReceivedMessageUpdate($event);
	}

	/**
	 * @param Main\Event $event
	 * @return bool
	 */
	public static function onReceivedMessageDelete(\Bitrix\Main\Event $event)
	{
		$params = $event->getParameters();
		if (empty($params))
			return false;

		$userId = intval($params['user']);

		global $USER;
		if($userId > 0 && !$USER->IsAuthorized() && $USER->Authorize($userId, false, false))
		{
			setSessionExpired(true);
		}

		if (!isset($params['message']['user_id']))
		{
			$params['message']['user_id'] = $params['user'];
		}

		$fields = array(
			'connector' => Array(
				'connector_id' => $params['connector'],
				'line_id' => $params['line'],
				'chat_id' => $params['chat']['id'],
				'user_id' => $params['user'],
			),
			'chat' => $params['chat'],
			'message' => $params['message']
		);

		Log::write($fields, 'CONNECTOR - ENTITY DELETE');
		$manager = new self();
		return $manager->deleteMessage($fields);
	}

	/**
	 * @param Main\Event $event
	 * @return bool
	 */
	public static function onReceivedStatusError(\Bitrix\Main\Event $event)
	{
		return true;
	}

	/**
	 * @param Main\Event $event
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function onReceivedStatusDelivery(\Bitrix\Main\Event $event)
	{
		$params = $event->getParameters();
		if (empty($params))
		{
			return false;
		}

		Log::write($params, 'CONNECTOR - STATUS DELIVERY');

		if (!Loader::includeModule('im'))
		{
			return false;
		}

		\CIMMessageParam::Set($params['im']['message_id'], ['CONNECTOR_MID' => $params['message']['id'], 'SENDING' => 'N', 'SENDING_TS' => 0]);
		\CIMMessageParam::SendPull($params['im']['message_id'], ['CONNECTOR_MID', 'SENDING', 'SENDING_TS']);

		return true;
	}

	/**
	 * @param Main\Event $event
	 * @return bool
	 */
	public static function onReceivedStatusReading(\Bitrix\Main\Event $event)
	{
		return true;
	}

	/**
	 * @param Main\Event $event
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onReceivedStatusWrites(\Bitrix\Main\Event $event)
	{
		$params = $event->getParameters();
		if (empty($params))
		{
			return false;
		}

		if (!isset($params['message']['user_id']))
		{
			$params['message']['user_id'] = $params['user'];
		}

		$fields = [
			'connector' => [
				'connector_id' => $params['connector'],
				'line_id' => $params['line'],
				'chat_id' => $params['chat']['id'],
				'user_id' => $params['user'],
			],
			'chat' => $params['chat'],
			'message' => $params['message']
		];

		$skipCreate = true;

		$session = new Session();
		$resultLoadSession = $session->load([
			'USER_CODE' => self::getUserCode($fields['connector']),
			'CONNECTOR' => $fields,
			'SKIP_CREATE' => $skipCreate? 'Y': 'N'
		]);
		if (!$resultLoadSession)
		{
			return false;
		}

		$chat = $session->getChat();
		$chatId = $chat->getData('ID');

		if (\CModule::IncludeModule('im'))
		{
			\CIMMessenger::StartWriting('chat'.$chatId, $params['user'], "", true);
		}

		return true;
	}

	/**
	 * @param Main\Event $event
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function OnReceivedError(\Bitrix\Main\Event $event): bool
	{
		$result = false;

		$params = $event->getParameters();

		if (
			!empty($params) &&
			!empty($params['connector']) &&
			Loader::includeModule('imconnector')
		)
		{
			$result = ImConnector\Connector::initConnectorHandler($params['connector'])->receivedError($params);
		}

		return $result;
	}

	/**
	 * @param Main\Event $event
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function OnReceivedStatusBlock(\Bitrix\Main\Event $event): bool
	{
		$params = $event->getParameters();

		if (empty($params))
		{
			return false;
		}

		$fields = [
			'CONNECTOR_ID' => $params['connector'],
			'CONFIG_ID' => $params['line'],
			'EXTERNAL_CHAT_ID' => $params['chat']['id'],
			'CONNECTOR_USER_ID' => $params['user'],
		];

		$sessionParams['USER_CODE'] = Session\Common::combineUserCode($fields);

		$session = new Session();
		$resultLoadSession = $session->getLast($sessionParams);

		if ($resultLoadSession->isSuccess())
		{
			$sessionId = $session->getData('ID');
			$chat = $session->getChat();

			$limit = [
				'BLOCK_DATE' => new DateTime(),
				'BLOCK_REASON' => Library::BLOCK_REASON_USER
			];

			if ($params['message']['type'] === 'message_deny')
			{
				ReplyBlock::add($sessionId, $chat, $limit);
			}
			elseif ($params['message']['type'] === 'message_allow')
			{
				ReplyBlock::delete($sessionId, $chat);
			}

			return true;
		}

		return false;
	}

	/**
	 * @return array
	 * @throws Main\LoaderException
	 */
	public static function getListCanDeleteMessage()
	{
		$connectorList = [];
		if (Loader::includeModule('imconnector'))
		{
			$connectorList = ImConnector\Connector::getListConnectorDelExternalMessages();
		}

		return $connectorList;
	}

	/**
	 * @return array
	 * @throws Main\LoaderException
	 */
	public static function getListShowDeliveryStatus()
	{
		$connectorList = [];
		if (Loader::includeModule('imconnector'))
		{
			//$connectorList = ImConnector\Connector::getListConnectorShowDeliveryStatus();
			$connectorList = array_keys(ImConnector\Connector::getListConnector()); // TODO change method to getListConnectorShowDeliveryStatus()
			foreach ($connectorList as $key => $connectorId)
			{
				if (self::isLiveChat($connectorId))
				{
					unset($connectorList[$key]);
				}
			}
		}

		return $connectorList;
	}

	/**
	 * @return array
	 * @throws Main\LoaderException
	 */
	public static function getListCanUpdateOwnMessage()
	{
		$connectorList = [];
		if (Loader::includeModule('imconnector'))
		{
			$connectorList = ImConnector\Connector::getListConnectorEditInternalMessages();
		}

		$connectorList[] = self::TYPE_LIVECHAT;

		$connectorList[] = self::TYPE_NETWORK;

		return $connectorList;
	}

	/**
	 * @return array
	 * @throws Main\LoaderException
	 */
	public static function getListCanDeleteOwnMessage()
	{
		$connectorList = [];
		if (Loader::includeModule('imconnector'))
		{
			$connectorList = ImConnector\Connector::getListConnectorDelInternalMessages();
		}
		$connectorList[] = self::TYPE_LIVECHAT;

		$connectorList[] = self::TYPE_NETWORK;

		return $connectorList;
	}

	/**
	 * Returns the array of the operator description.
	 *
	 * @param $lineId
	 * @param $userId
	 * @param string $userCodeSession
	 * @return array|string
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getOperatorInfo($lineId, $userId, $userCodeSession = '')
	{
		$result = [];

		$actualLineId = Queue::getActualLineId([
			'LINE_ID' =>  $lineId,
			'USER_CODE' => $userCodeSession
		]);

		$userArray = Queue::getUserData($actualLineId, $userId);
		if ($userArray)
		{
			$result =  array_merge(
				ImUser::getInstance($userId)->getFields(),
				array_change_key_case($userArray, CASE_LOWER)
			);
		}

		return $result;
	}

	/**
	 * Returns the name of the operator description.
	 *
	 * @param $lineId
	 * @param $userId
	 * @param string $userCodeSession
	 * @return array|string
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getOperatorName($lineId, $userId, $userCodeSession = '')
	{
		$result = '';

		$actualLineId = Queue::getActualLineId([
			'LINE_ID' =>  $lineId,
			'USER_CODE' => $userCodeSession
		]);

		$userArray = Queue::getUserData($actualLineId, $userId);

		if($userArray)
		{
			$result = $userArray['NAME'];
		}

		return $result;
	}

	/**
	 * Returns the operator's avatar.
	 *
	 * @param $lineId
	 * @param $userId
	 * @param $userCodeSession
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getOperatorAvatar($lineId, $userId, $userCodeSession = '')
	{
		$actualLineId = Queue::getActualLineId([
			'LINE_ID' =>  $lineId,
			'USER_CODE' => $userCodeSession
		]);
		$userArray = Queue::getUserData($actualLineId, $userId);
		if (!empty($userArray['AVATAR']))
		{
			$result = $userArray['AVATAR'];
		}
		else
		{
			$result = ImUser::getInstance($userId)->getAvatar();
		}

		return $result;
	}

	/**
	 * @param $connectorId
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function isEnableSendSystemMessage($connectorId)
	{
		if (in_array($connectorId, array(self::TYPE_LIVECHAT, self::TYPE_NETWORK)))
		{
			$result = true;
		}
		else if (Loader::includeModule('imconnector'))
		{
			$result = ImConnector\Connector::isNeedSystemMessages($connectorId);
		}
		else
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param $connectorId
	 * @param int $lineId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function isEnableSendMessageWithSignature($connectorId, $lineId = 0)
	{
		$lineId = (int)$lineId;
		if ($lineId > 0)
		{
			$isShowOperatorData = Config::isShowOperatorData($lineId);

			if ($isShowOperatorData)
			{
				$result = self::isConnectorSendMessageWithSignature($connectorId);
			}
			else
			{
				$result = false;
			}
		}
		else
		{
			$result = self::isConnectorSendMessageWithSignature($connectorId);
		}

		return $result;
	}

	/**
	 * @param $connectorId
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function isConnectorSendMessageWithSignature($connectorId): bool
	{
		$result = false;

		if (Loader::includeModule('imconnector'))
		{
			$result = ImConnector\Connector::isNeedSignature($connectorId);
		}

		return $result;
	}

	/**
	 * @param string $connector
	 * @param string $message
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function isNeedRichLinkData(string $connector, $message): bool
	{
		if (empty($message))
		{
			return false;
		}

		if (self::isLinkOnly($message) && Loader::includeModule('imconnector'))
		{
			if (in_array($connector, Library::$listConnectorWithRichLinks, true))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns true if the message is a link.
	 *
	 * @param string $message
	 * @return bool
	 */
	private static function isLinkOnly(string $message): bool
	{
		$url = filter_var($message, FILTER_SANITIZE_URL);

		if (filter_var($url, FILTER_VALIDATE_URL) !== false)
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $connectorId
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function isEnableGroupByChat($connectorId)
	{
		$result = false;

		if (Loader::includeModule('imconnector'))
		{
			$result = \Bitrix\ImConnector\Connector::isChatGroup($connectorId);
		}

		return $result;
	}

	/**
	 * @param $idConnector
	 * @return bool
	 */
	public static function isLiveChat($idConnector)
	{
		$result = false;

		$idConnector = mb_strtolower($idConnector);

		if($idConnector == self::TYPE_LIVECHAT)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param $idConnector
	 * @return bool
	 * @throws Main\LoaderException
	 */
	private static function isNeedConnectorWritingStatus(string $idConnector): bool
	{
		$result = false;

		if (Loader::includeModule('imconnector'))
		{
			if (in_array($idConnector, Library::$listConnectorWritingStatus, true))
			{
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @param $idConnector
	 * @return bool
	 * @throws Main\LoaderException
	 */
	protected static function isImessage(string $idConnector): bool
	{
		$result = false;

		if (Loader::includeModule('imconnector'))
		{
			if ($idConnector === Library::ID_IMESSAGE_CONNECTOR)
			{
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @param $idConnector
	 * @return bool
	 */
	public static function isNeedCRMTracker(string $idConnector): bool
	{
		$result = false;

		if (self::isLiveChat($idConnector))
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param $chatId
	 * @param $customData
	 * @throws Main\LoaderException
	 */
	public static function saveCustomData($chatId, $customData)
	{
		$customDataMessage = '';
		$customDataAttach = null;

		if (!empty($customData))
		{
			$customDataAttach = \CIMMessageParamAttach::GetAttachByJson($customData);
			if (empty($customDataAttach) || !is_object($customDataAttach) || $customDataAttach->IsEmpty())
			{
				$customDataAttach = null;
			}
			else
			{
				$customDataMessage = '[B]'.Loc::getMessage('IMOL_CONNECTOR_RECEIVED_DATA').'[/B]';
			}
		}
		if ($customDataMessage)
		{
			Im::addMessage([
				"TO_CHAT_ID" => $chatId,
				'MESSAGE' => $customDataMessage,
				'ATTACH' => $customDataAttach,
				'SYSTEM' => 'Y',
				'SKIP_COMMAND' => 'Y',
				"PARAMS" => [
					"CLASS" => "bx-messenger-content-item-system"
				],
			]);
		}
	}

	public function getError()
	{
		return $this->error;
	}
}