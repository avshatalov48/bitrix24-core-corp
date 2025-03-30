<?php
namespace Bitrix\ImOpenLines;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk;
use Bitrix\ImOpenLines;
use Bitrix\ImConnector;
use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\InteractiveMessage;
use Bitrix\Im;
use Bitrix\Im\Bot\Keyboard;
use Bitrix\Im\Text;
use Bitrix\Im\User;
use Bitrix\Im\Model\ChatTable;

Loc::loadMessages(__FILE__);

class Connector
{
	public const TYPE_LIVECHAT = 'livechat';
	public const TYPE_NETWORK = 'network';

	public const EVENT_IMOPENLINE_MESSAGE_SEND = 'OnImopenlineMessageSend';
	public const EVENT_IMOPENLINE_MESSAGE_RECEIVE = 'OnImopenlineMessageReceive';

	public const LOCK_MAX_ITERATIONS = 3;

	public static $noVote = [
		self::TYPE_LIVECHAT,
	];

	/** @var BasicError|Error|null */
	private $error;

	/** @var Result */
	protected $result;

	/**
	 * Connector constructor.
	 */
	public function __construct()
	{
		$imLoad = Loader::includeModule('im');
		$pullLoad = Loader::includeModule('pull');
		$connectorLoad = Loader::includeModule('imconnector');
		if (
			$imLoad
			&& $pullLoad
			&& $connectorLoad
		)
		{
			$this->result = new Result();
			//old version
			$this->error = new BasicError(null, '', '');
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

	public function setChatName($params)
	{
		if (empty($params['connector']) || empty($params['name']))
		{
			return false;
		}

		$session = new Session();
		$resultLoadSession = $session->load([
			'USER_CODE' => self::getUserCode($params['connector']),
			'CONNECTOR' => $params,
			'SKIP_CREATE' => 'Y',
		]);

		if (!$resultLoadSession)
		{
			return false;
		}

		$chat = new \CIMChat(0);
		return $chat->Rename($session->getData('CHAT_ID'), $params['name'], false);
	}

	/**
	 * Adding an incoming message from an external channel.
	 *
	 * @param $params
	 * @return array
	 */
	public function addMessage($params)
	{
		$result = false;
		$finishSession = false;
		$voteSession = false;

		if (!empty($params))
		{
			if (
				!in_array($params['connector']['connector_id'], self::$noVote)
				&&
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
				"PARAMS" => $params['message']['params'] ?? []
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
				}
				else
				{
					$addMessage["ATTACH"] = \CIMMessageParamAttach::GetAttachByJson($params['message']['attach']);
				}
			}
			if (!empty($params['message']['keyboard']))
			{
				if ($params['connector']['connector_id'] == self::TYPE_LIVECHAT)
				{
					$addMessage["KEYBOARD"] = $params['message']['keyboard'];
				}
				else
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
					}
					else
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
					$iteration > self::LOCK_MAX_ITERATIONS
					|| Tools\Lock::getInstance()->set($keyLock)
				)
				{
					if (
						!empty($params['message']['files'])
						|| !empty($addMessage['ATTACH'])
						|| $addMessage['MESSAGE'] <> ''
					)
					{
						$userViewChat = false;
						$addVoteResult = false;

						if (isset($params['message']['extraData']['SOURCE_SESSION_ID']))
						{
							$sourceSession = ImOpenLines\Model\SessionTable::getRow([
								'select' => ['USER_LANG'],
								'filter' => [
									'=ID' => (int)$params['message']['extraData']['SOURCE_SESSION_ID']
								],
							]);

							$sourceLang = $sourceSession['USER_LANG'];
						}

						$session = new Session();
						$resultLoadSession = $session->load([
							'USER_CODE' => self::getUserCode($params['connector']),
							'CRM_SKIP_PHONE_VALIDATE' => ($params['extra']['skip_phone_validate'] ?? ''),
							'CONNECTOR' => $params,
							'DEFERRED_JOIN' => 'Y',
							'VOTE_SESSION' => $voteSession? 'Y': 'N',
							'OPERATOR_ID' => $params['message']['params']['IMOL_FORCE_OPERATOR'] ?? 0,
							'USER_LANG' => $sourceLang ?? '',
							'CRM_TRACKER_REF' => $params['ref']['source'] ?? '',
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

								$customData = ImOpenLines\Widget\Cache::get($params['connector']['user_id'], 'CUSTOM_DATA');
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
								else // TODO remove this after delete old livechat - see ImOpenLines\Connector::saveCustomData
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
									ImOpenLines\Im::addMessage([
										'TO_CHAT_ID' => $session->getData('CHAT_ID'),
										'MESSAGE' => $customDataMessage,
										'ATTACH' => $customDataAttach,
										'SYSTEM' => 'Y',
										'SKIP_COMMAND' => 'Y',
										'PARAMS' => [
											'CLASS' => 'bx-messenger-content-item-system',
											'COMPONENT_ID' => 'HiddenMessage',
										],
									]);
								}
							}

							if (!empty($params['message']['description']))
							{
								ImOpenLines\Im::addMessage([
									'TO_CHAT_ID' => $session->getData('CHAT_ID'),
									'MESSAGE' => $params['message']['description'],
									'SYSTEM' => 'Y',
									'SKIP_COMMAND' => 'Y',
									'URL_PREVIEW' => 'N',
									'PARAMS' => [
										'CLASS' => 'bx-messenger-content-item-system',
										'COMPONENT_ID' => 'HiddenMessage',
									],
								]);
								unset($params['message']['description']);
							}

							if ($session->isNowCreated())
							{
								$allBots = \Bitrix\Im\Bot::getListCache();
								$botsInLine = [];
								if ($session->getConfig('QUEUE_TYPE') === Config::QUEUE_TYPE_ALL)
								{
									$messageId = 0;
									foreach ($session->getJoinUserList() as $fakeRelation)
									{
										$messageId = $session->joinUser(
											!isset($allBots[$fakeRelation]),
											(int)$fakeRelation,
											$messageId)
										;

										if (isset($allBots[$fakeRelation]))
										{
											$botsInLine[] = (int)$fakeRelation;
										}
									}
								}
								else
								{
									$firstOperatorId = (int)current($session->getJoinUserList());
									$session->joinUser(!isset($allBots[$firstOperatorId]), $firstOperatorId);

									if (isset($allBots[$firstOperatorId]))
									{
										$botsInLine[] = $firstOperatorId;
									}
								}

								$chat = \Bitrix\Im\V2\Chat::getInstance((int)$session->getData('CHAT_ID'));
								$relations = $chat->getRelations();
								foreach ($botsInLine as $botUserId)
								{
									$relation = $relations->getByUserId($botUserId, (int)$session->getData('CHAT_ID'));
									$relation?->setManager(true)->save();
								}
							}
							else
							{
								$session->joinUser();
							}

							if (isset($params['message']['extraData']['OPERATOR_ID']))
							{
								$messageParams = [
									'TO_CHAT_ID' => $session->getData('CHAT_ID'),
									'MESSAGE' => $params['message']['text'],
									'SYSTEM' => 'Y',
									'SKIP_COMMAND' => 'Y',
									'URL_PREVIEW' => 'N',
									'SKIP_CONNECTOR' => 'Y',
								];

								if (isset($params['message']['extraData']['SOURCE_MESSAGE_ID']))
								{
									$message = new \Bitrix\Im\V2\Message($params['message']['extraData']['SOURCE_MESSAGE_ID']);
									$messageParams['MESSAGE'] = $message->getMessage();
									if ($message->getAuthorId() === (int)$params['message']['user_id'])
									{
										$messageParams['FROM_USER_ID'] = $message->getAuthorId();
										unset($messageParams['SYSTEM']);
									}
								}

								\Bitrix\ImOpenLines\Im::addMessage($messageParams);
							}

							if ($session->getData('OPERATOR_ID') > 0)
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
									$event = new Event('imopenlines', 'OnSessionVote', $voteEventParams);
									$event->send();

									$addMessage['RECENT_ADD'] = $userViewChat? 'Y': 'N';
									$session->update(['VOTE' => $voteValue, 'WAIT_VOTE' => 'N']);

									if ($session->getConfig('VOTE_CLOSING_DELAY') == 'Y')
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
							$event = new Event('imopenlines', self::EVENT_IMOPENLINE_MESSAGE_RECEIVE, $addMessage);
							$event->send();

							$eventMessageFields = $event->getParameters();
							if (
								isset($params['message']['extraData']['SOURCE_SESSION_ID'])
								&& $params['message']['extraData']['SOURCE_SESSION_ID']
							)
							{
								$sourceSession = ImOpenLines\Model\SessionTable::getRowById((int)$params['message']['extraData']['SOURCE_SESSION_ID']);

								ImOpenLines\Im\Messages\Session::sendMessageStartSessionByMultiDialogToParentChat(
									(int)$sourceSession['CHAT_ID'],
									(int)$session->getData('ID'),
									(int)$session->getData('CHAT_ID')
								);

								if (!empty($sourceSession['EXTRA_TARIFF']))
								{
									$session->update([
										'EXTRA_REGISTER' => $sourceSession['EXTRA_REGISTER'],
										'EXTRA_TARIFF' => $sourceSession['EXTRA_TARIFF'],
										'EXTRA_USER_LEVEL' => $sourceSession['EXTRA_USER_LEVEL'],
										'EXTRA_PORTAL_TYPE' => $sourceSession['EXTRA_PORTAL_TYPE'],
										'EXTRA_URL' => $sourceSession['EXTRA_URL'],
									]);
								}

								$result = [
									'SESSION_ID' => $session->getData('ID'),
									'MESSAGE_ID' => -1
								];

								$addMessage = false;
							}
							elseif (!empty($eventMessageFields['MESSAGE']))
							{
								$addMessage['MESSAGE'] = $eventMessageFields['MESSAGE'];
							}

							if (!empty($eventMessageFields['PARAMS']))
							{
								$addMessage['PARAMS'] = $eventMessageFields['PARAMS'];
							}

							if ($addMessage)
							{
								$messageId = ImOpenLines\Im::addMessage($addMessage);
							}

							if (is_array($params['message']['extraData']))
							{
								$chat = new Chat($session->getData('CHAT_ID'));
								if (
									isset($sourceSession)
									&& (int)$sourceSession['CONFIG_ID'] !== (int)$session->getData('CONFIG_ID')
								)
								{
									$chat->transfer([
										'FROM' => $session->getData('OPERATOR_ID'),
										'TO' => 'queue' . $sourceSession['CONFIG_ID'],
										'SKIP_CHECK' => 'Y',
										'CHANGE_LINE_ONLY' => true,
									]);
								}

								if (isset($params['message']['extraData']['OPERATOR_ID']) && $params['message']['extraData']['OPERATOR_ID'])
								{
									$chat->answer((int)$params['message']['extraData']['OPERATOR_ID']);
								}
							}
						}

						if (
							$resultLoadSession &&
							!empty($messageId)
						)
						{
							$isGroupChatAllowed = \Bitrix\ImConnector\Connector::isChatGroup($params['connector']['connector_id']) != true;

							if (
								$addMessage["MESSAGE"]
								&& (empty($params['extra']['disable_tracker']) || $params['extra']['disable_tracker'] !== 'Y')
								&& $isGroupChatAllowed
							)
							{
								$tracker = new ImOpenLines\Tracker();
								$tracker
									->setSession($session)
									->trackMessage([
										'ID' => $messageId,
										'TEXT' => $addMessage["MESSAGE"]
									])
								;
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

									ImOpenLines\Mail::removeSessionFromMailQueue($session->getData('ID'), false);
									Session::setLastSendMailId($session->getData(), $params['message']['id']);
								}

								\CIMMessageParam::Set($messageId, ['CONNECTOR_MID' => $params['message']['id']]);
								if (!in_array($params['connector']['connector_id'], [self::TYPE_LIVECHAT, self::TYPE_NETWORK]))
								{
									\CIMMessageParam::SendPull($messageId, ['CONNECTOR_MID']);
								}
							}

							if ($addVoteResult)
							{
								ImOpenLines\Im::addMessage([
									"TO_CHAT_ID" => $session->getData('CHAT_ID'),
									"MESSAGE" => $addVoteResult,
									"SYSTEM" => 'Y',
									"IMPORTANT_CONNECTOR" => 'Y',
									'NO_SESSION_OL' => 'Y',
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

							$updateSession = [
								'MESSAGE_COUNT' => true,
								'DATE_LAST_MESSAGE' => new DateTime(),
							];

							if (!$finishSession && !$voteSession)
							{
								//$updateSession['STATUS'] = Session::STATUS_CLIENT;
								$updateSession['INPUT_MESSAGE'] = true;
								$updateSession['DATE_MODIFY'] = new DateTime;
								$updateSession['USER_ID'] = $session->getData('USER_ID');
							}

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
								$session->getConfig('AGREEMENT_MESSAGE') == 'Y'
								&& !$session->chat->isNowCreated()
								&& $session->getUser()
								&& $session->getUser('USER_ID') == $params['message']['user_id']
								&& $session->getUser('USER_CODE')
								&& $session->getUser('AGREES') == 'N'
							)
							{
								ImOpenLines\Common::setUserAgrees([
									'AGREEMENT_ID' => $session->getConfig('AGREEMENT_ID'),
									'CRM_ACTIVITY_ID' => $session->getData('CRM_ACTIVITY_ID'),
									'SESSION_ID' => $session->getData('SESSION_ID'),
									'CONFIG_ID' => $session->getData('CONFIG_ID'),
									'USER_CODE' => $session->getUser('USER_CODE'),
								]);
							}

							$queueManager = Queue::initialization($session);
							if ($queueManager)
							{
								$queueManager->automaticActionAddMessage($finishSession, $voteSession);
							}

							if (!$session->isNowCreated() && $finishSession === true)
							{
								$session->finish(true);
							}

							$this->callMessageTrigger($session, $messageId, $addMessage);

							//In case it's not a vote message or system message we make a new record
							if (!$voteSession && $params['message']['user_id'] != 0 && !User::getInstance($params['message']['user_id'])->isBot())
							{
								$kpi = new KpiManager($session->getData('ID'));
								$kpi->addMessage([
									'MESSAGE_ID' => $messageId,
									'LINE_ID' => $session->getData('CONFIG_ID'),
									'OPERATOR_ID' => $session->getData('OPERATOR_ID')
								]);
							}

							$result = [
								'SESSION_ID' => $session->isNowCreated()? $session->getData('ID'): 0,
								'MESSAGE_ID' => $messageId
							];

							if ($session->getData('ID'))
							{
								$result['SESSION'] = [
									'ID' => $session->getData('ID'),
									'CHAT_ID' => $session->getData('CHAT_ID')
								];
							}
						}

						if (
							!$resultLoadSession
							&& $session->isCloseVote()
						)
						{
							ImOpenLines\Im::addCloseVoteMessage(
								$session->getData('CHAT_ID'),
								$session->getConfig('VOTE_TIME_LIMIT'),
								$session->getData('USER_LANG')
							);
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
	 */
	protected function callMessageTrigger(Session $session, $messageId, $messageData)
	{
		$crm = $session->getCrmManager();
		$result = new Result();

		if (
			$crm->isLoaded()
			&& $session->getData('CRM') == 'Y'
			&& $session->getData('CRM_ACTIVITY_ID') > 0
		)
		{
			$activities = ImOpenLines\Crm\Common::getActivityBindingsFormatted($session->getData('CRM_ACTIVITY_ID'));
			$message = [
				'ID' => $messageId,
				'TEXT' => $messageData['MESSAGE'],
			];
			if (
				!empty($messageData['MESSAGE'])
				&& Loader::includeModule('im')
			)
			{
				$message['PLAIN_TEXT'] = Text::removeBbCodes($messageData['MESSAGE']);
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

	//region Output

	/**
	 * @param $params
	 * @return bool
	 */
	public function updateMessage($params)
	{
		if (empty($params))
		{
			return false;
		}

		$connector = $params['connector'];
		if (self::isEnableGroupByChat($params['connector']['connector_id']))
		{
			$userCodeFail = self::getUserCode($connector);

			$connector['user_id'] = '0';
			$userCode = self::getUserCode($connector);
		}
		else
		{
			$userCode = self::getUserCode($connector);

			$connector['user_id'] = '0';
			$userCodeFail = self::getUserCode($connector);
		}

		$chat = new Chat();
		if (!$chat->load(['USER_CODE' => $userCode, 'ONLY_LOAD' => 'Y']))
		{
			if (!$chat->load(['USER_CODE' => $userCodeFail, 'ONLY_LOAD' => 'Y']))
			{
				return false;
			}
		}

		$messageIds = \CIMMessageParam::GetMessageIdByParam('CONNECTOR_MID', $params['message']['id'], $chat->getData('ID'));
		if (empty($messageIds))
		{
			return false;
		}

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
		{
			return false;
		}

		$connector = $params['connector'];
		if (self::isEnableGroupByChat($params['connector']['connector_id']))
		{
			$userCodeFail = self::getUserCode($connector);

			$connector['user_id'] = '0';
			$userCode = self::getUserCode($connector);
		}
		else
		{
			$userCode = self::getUserCode($connector);

			$connector['user_id'] = '0';
			$userCodeFail = self::getUserCode($connector);
		}

		$chat = new Chat();
		if (!$chat->load(['USER_CODE' => $userCode, 'ONLY_LOAD' => 'Y']))
		{
			if (!$chat->load(['USER_CODE' => $userCodeFail, 'ONLY_LOAD' => 'Y']))
			{
				return false;
			}
		}

		$messageIds = \CIMMessageParam::GetMessageIdByParam('CONNECTOR_MID', $params['message']['id'], $chat->getData('ID'));
		if (empty($messageIds))
		{
			return false;
		}

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
	 * Event `im:OnAfterMessagesAdd` fired in @see \CIMMessenger::Add
	 * when calls @see \Bitrix\ImOpenLines\Connector::onMessageSend
	 *
	 * @param $params
	 * @return Result
	 */
	public function sendMessage($params): Result
	{
		$result = clone $this->result;

		if ($result->isSuccess())
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

			$allowTouchSession = empty($params['no_session']) || $params['no_session'] !== 'Y';

			if ($allowTouchSession)
			{
				$session = new Session();

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
					$result->isSuccess()
					&& ReplyBlock::isBlocked($session)
				)
				{
					$result->addError(new Error('This chat is blocked for sending outgoing messages', 'IMOPENLINES_ERROR_SESSION_BLOCKED', __METHOD__));
				}

				if (
					$result->isSuccess()
					&& $session->getConfig('ACTIVE') !== 'Y'
				)
				{
					$result->addError(new Error('The open line is deactivated', 'IMOPENLINES_ERROR_LINE_DEACTIVATED', __METHOD__));
				}

				if ($result->isSuccess())
				{
					if ($params['message']['system'] !== 'Y')
					{
						$updateSession = [
							'DATE_MODIFY' => new DateTime,
							'MESSAGE_COUNT' => true,
							'DATE_LAST_MESSAGE' => new DateTime
						];

						if (
							!$session->getData('DATE_FIRST_ANSWER')
							&& !empty($session->getData('OPERATOR_ID'))
							&& Queue::isRealOperator($session->getData('OPERATOR_ID'))
						)
						{
							$currentTime = new DateTime();
							$updateSession['DATE_FIRST_ANSWER'] = $currentTime;
							$updateSession['TIME_FIRST_ANSWER'] = $currentTime->getTimestamp()
								- $session->getData('DATE_CREATE')->getTimestamp();
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

					$actualLineId = Queue::getActualLineId([
						'LINE_ID' => $params['connector']['line_id'],
						'USER_CODE' => $session->getData('USER_CODE')
					]);

					//Automatic messages
					(new AutomaticAction($session))->automaticSendMessage($params['message']['id']);

					if (
						$params['message']['system'] !== 'Y'
						&& !User::getInstance($session->getData('OPERATOR_ID'))->isBot()
					)
					{
						KpiManager::setSessionLastKpiMessageAnswered($session->getData('ID'));
					}
				}
			}

			if ($result->isSuccess())
			{
				if (!empty($fields['message']['user_id']))
				{
					$fields['user'] = Queue::getUserData($actualLineId, $fields['message']['user_id']);
				}

				$connector = new Output($params['connector']['connector_id'], $params['connector']['line_id']);
				$resultSendMessage = $connector->sendMessage([$fields]);

				if (!$resultSendMessage->isSuccess())
				{
					$result->addErrors($resultSendMessage->getErrors());
				}

				$restEvent = new \Bitrix\Main\Event(
					'imopenlines',
					'OnOpenLineMessageAdd',
					[
						'CONNECTOR' => $params['connector']['connector_id'],
						'LINE' => $params['connector']['line_id'],
						'DATA' => $params
					]
				);
				$restEvent->send();
			}
		}

		return $result;
	}

	/**
	 * @param array $fields
	 * @return bool
	 */
	public function sendStatusWriting($fields): bool
	{
		Log::write([$fields], 'STATUS WRITING');
		if (self::isNeedConnectorWritingStatus($fields['connector']['connector_id']))
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
	 * @see \Bitrix\ImOpenLines\Connector::onChatRead
	 * @param array $connector
	 * @param array $messages
	 * @param $event
	 * @return false
	 */
	public function sendStatusRead($connector, $messages, $event)
	{
		if (empty($messages))
		{
			return false;
		}

		if ($connector['connector_id'] == self::TYPE_NETWORK)
		{

		}
		elseif ($connector['connector_id'] == 'lines')
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
		elseif (ImOpenLines\Connector::isLiveChat($connector['connector_id']))
		{
			Log::write(array($connector, $messages, $event), 'STATUS READ');

			$maxId = 0;
			foreach ($messages as $messageId)
			{
				$maxId = $maxId < $messageId? $messageId: $maxId;
			}

			$chat = new ImOpenLines\Chat($connector['chat_id']);
			$chat->updateFieldData([ImOpenLines\Chat::FIELD_LIVECHAT => [
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

	//endregion

	/**
	 * @param $params
	 * @return string
	 */
	public static function getUserCode($params)
	{
		return $params['connector_id'].'|'.$params['line_id'].'|'.$params['chat_id'].'|'.$params['user_id'];
	}

	//region IM event handlers

	/**
	 * Handler for event `im:OnBeforeChatMessageAdd` fired in \CIMMessenger::Add.
	 * @see \CIMMessenger::Add
	 * @param array $fields
	 * @param array $chat
	 * @return array|bool
	 */
	public static function onBeforeMessageSend($fields, $chat)
	{
		if ($chat['CHAT_ENTITY_TYPE'] != 'LINES')
		{
			return true;
		}

		if ($fields['SKIP_CONNECTOR'] === 'Y')
		{
			return true;
		}

		if ($fields['FROM_USER_ID'] <= 0)
		{
			return true;
		}

		if (User::getInstance($fields['FROM_USER_ID'])->isConnector())
		{
			return true;
		}

		if (!Loader::includeModule('imconnector'))
		{
			return false;
		}

		$result = true;
		//TODO: Replace with the method ImOpenLines\Chat::parseLiveChatEntityId
		[$connectorId, $lineId] = explode('|', $chat['CHAT_ENTITY_ID']);

		if ($connectorId == self::TYPE_NETWORK)
		{}
		else
		{
			$status = \Bitrix\ImConnector\Status::getInstance($connectorId, (int)$lineId);
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
	 * Handler for event `im:OnAfterMessagesUpdate` fired in \CIMMessenger::Add.
	 * @see \CIMMessenger::Add
	 * @param int $messageId
	 * @param array $messageFields
	 * @param array $flags
	 * @return bool
	 */
	public static function onMessageUpdate($messageId, $messageFields, $flags)
	{
		if (
			$flags['BY_EVENT']
			|| !isset($messageFields['PARAMS']['CONNECTOR_MID'])
		)
		{
			return false;
		}

		//TODO: Replace with the method ImOpenLines\Chat::parseLinesChatEntityId or ImOpenLines\Chat::parseLiveChatEntityId
		[$connectorId, $lineId, $connectorChatId] = explode('|', $messageFields['CHAT_ENTITY_ID']);

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
			$messageFields['SYSTEM'] != 'Y'
			&& self::isEnableSendMessageWithSignature($connectorId, $lineId)
			&& $messageFields['AUTHOR_ID'] > 0
		)
		{
			$flags['TEXT'] =
				'[b]' . htmlspecialchars_decode(self::getOperatorName($lineId, $messageFields['AUTHOR_ID'], $messageFields['CHAT_ENTITY_ID'])) . ':[/b]'.
				($flags['TEXT'] <> ''? '[br] '.$flags['TEXT']: '');
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
			isset($lineId) && isset($connectorChatId)
			&& !empty($messageFields['PARAMS']['CONNECTOR_MID'])
			&& is_array($messageFields['PARAMS']['CONNECTOR_MID'])
			&& Loader::includeModule('imconnector')
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

			$event = new \Bitrix\Main\Event(
				'imopenlines',
				'OnOpenLineMessageUpdate',
				[
					'CONNECTOR' => $connectorId,
					'LINE' => $lineId,
					'DATA' => $fields
				]
			);
			$event->send();
		}

		return true;
	}

	/**
	 * Handler for event `im:OnAfterMessagesDelete` fired in \CIMMessenger::Add.
	 * @see \CIMMessenger::Add
	 * @param $messageId
	 * @param $messageFields
	 * @param $flags
	 * @return bool
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
			[$connectorType, $lineId, $connectorChatId] = explode("|", $messageFields['CHAT_ENTITY_ID']);
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
		else if (
			isset($lineId)
			&& isset($connectorChatId)
			&& Loader::includeModule('imconnector')
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

				$event = new \Bitrix\Main\Event(
					'imopenlines',
					'OnOpenLineMessageDelete',
					[
						'CONNECTOR' => $connectorType,
						'LINE' => $lineId,
						'DATA' => $fields
					]
				);
				$event->send();
			}
		}

		return true;
	}

	/**
	 * Handler for event `im:OnAfterMessagesAdd` fired in @see \CIMMessenger::Add
	 * @param int $messageId
	 * @param array $messageFields
	 * @return bool
	 */
	public static function onMessageSend($messageId, $messageFields)
	{
		$chatEntityType = $messageFields['CHAT_ENTITY_TYPE'] ?? null;
		if ($chatEntityType !== 'LINES')
		{
			return false;
		}

		$messageFields['MESSAGE_ID'] = $messageId;
		Log::write($messageFields, 'CONNECTOR MESSAGE SEND');

		if ($messageFields['AUTHOR_ID'] > 0)
		{
			$user = User::getInstance($messageFields['AUTHOR_ID']);
			if ($user->isConnector())
			{
				return false;
			}
		}

		if (
			$messageFields['IMPORTANT_CONNECTOR'] !== 'Y'
			&&
			(
				$messageFields['SILENT_CONNECTOR'] === 'Y'
				|| $messageFields['CHAT_'.Chat::getFieldName(Chat::FIELD_SILENT_MODE)] === 'Y'
			)
		)
		{
			\CIMMessageParam::Set($messageId, ['CLASS' => 'bx-messenger-content-item-system', 'COMPONENT_ID' => 'HiddenMessage']);
			\CIMMessageParam::SendPull($messageId, ['CLASS', 'COMPONENT_ID']);
			return false;
		}

		if (
			isset($messageFields['SKIP_CONNECTOR'])
			&& $messageFields['SKIP_CONNECTOR'] === 'Y'
		)
		{
			return false;
		}

		if (
			$messageFields['IMPORTANT_CONNECTOR'] !== 'Y'
			&& isset($messageFields['SYSTEM'])
			&& $messageFields['SYSTEM'] === 'Y'
		)
		{
			return false;
		}

		//TODO: Replace with the method ImOpenLines\Chat::parseLinesChatEntityId or ImOpenLines\Chat::parseLiveChatEntityId
		[$connectorId, $lineId, $connectorChatId, $connectorUserId] = explode('|', $messageFields['CHAT_ENTITY_ID']);

		$event = new Event('imopenlines', self::EVENT_IMOPENLINE_MESSAGE_SEND, $messageFields);
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

		if ($connectorId === self::TYPE_LIVECHAT)
		{
			$resultLoadSession = false;

			if (isset($messageFields['PARAMS']['CLASS']))
			{
				$messageFields['PARAMS']['CLASS'] = str_replace('bx-messenger-content-item-ol-output', "", $messageFields['PARAMS']['CLASS']);
			}

			$params = [];
			$allowedFields = ['CLASS', 'TYPE', 'COMPONENT_ID', 'CRM_FORM_ID', 'CRM_FORM_SEC', 'CRM_FORM_FILLED', 'url', 'fromSalescenterApplication', 'richUrlPreview'];

			foreach ($messageFields['PARAMS'] as $key => $value)
			{
				if (in_array($key, $allowedFields))
				{
					$params[$key] = $value;
				}
				elseif (mb_strpos($key, 'IMOL_') === 0)
				{
					$params[$key] = $value;
				}
				elseif (mb_strpos($key, 'IS_') === 0)
				{
					$params[$key] = $value;
				}
				elseif ($key === 'FILE_ID')
				{
					foreach ($value as $fileId)
					{
						$messageFields['MESSAGE'] .= ' [DISK='.$fileId.']';
					}
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

			if (empty($messageFields['NO_SESSION_OL']) || $messageFields['NO_SESSION_OL'] !== 'Y')
			{
				$session = new Session();
				$resultLoadSession = $session->load([
					'MODE' => Session::MODE_OUTPUT,
					'USER_CODE' => $messageFields['CHAT_ENTITY_ID'],
					'OPERATOR_ID' => $messageFields['AUTHOR_ID']
				]);

				if ($resultLoadSession)
				{
					$updateSession = [
						'MESSAGE_COUNT' => true,
						'DATE_LAST_MESSAGE' => new DateTime(),
						'DATE_MODIFY' => new DateTime(),
						'USER_ID' => $messageFields['AUTHOR_ID'],
					];
					if ($messageFields['SYSTEM'] === 'Y')
					{
						$updateSession['SKIP_CHANGE_STATUS'] = true;
					}
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

					if (
						$session->getSessionField('STATUS') < Session::STATUS_ANSWER
						&& $session->getConfig('WELCOME_BOT_ENABLE') === 'Y'
						&& (int)$session->getConfig('WELCOME_BOT_ID') === (int)$messageFields['AUTHOR_ID']
					)
					{
						$updateSession['SKIP_CHANGE_STATUS'] = true;
					}

					$session->update($updateSession);
					$eventData['STATUS_AFTER'] = $session->getData('STATUS');
					Queue\Event::checkFreeSlotBySendMessage($eventData);

					//for livechat only condition
					if (
						$messageFields['SYSTEM'] !== 'Y'
						&& !User::getInstance($session->getData('OPERATOR_ID'))->isBot()

					)
					{
						KpiManager::setSessionLastKpiMessageAnswered($session->getData('ID'));
					}
				}
			}

			if (
				!empty($connectorChatId)
				&& $connectorChatId > 0
				&& Loader::includeModule('imconnector')
			)
			{
				//Processing for native messages
				$interactiveMessage = InteractiveMessage\Output::getInstance($messageFields['TO_CHAT_ID'], ['connectorId' => self::TYPE_LIVECHAT]);
				$message = $interactiveMessage->nativeMessageProcessing($message);
			}

			$mid = ImOpenLines\Im::addMessage($message);
			if (
				$messageId
				&& $mid
				&& (
					$messageFields['NO_SESSION_OL'] === 'Y'
					|| $resultLoadSession
				)
			)
			{
				$paramsMessageLiveChat = ['CONNECTOR_MID' => $messageId];

				if (
					isset($session)
					&& $session instanceof Session
					&& $resultLoadSession
				)
				{
					$userData = Queue::getUserData($session->getData('CONFIG_ID'), $messageFields['AUTHOR_ID'], true);

					if (!empty($userData))
					{
						$paramsMessageLiveChat['NAME'] = $userData['NAME'];
					}

					//TODO: remove code duplication.
					//Automatic messages
					(new AutomaticAction($session))->automaticSendMessage($messageId);
				}

				\CIMMessageParam::set($messageId, ['CONNECTOR_MID' => $mid]);
				\CIMMessageParam::sendPull($messageId, ['CONNECTOR_MID']);
				\CIMMessageParam::set($mid, $paramsMessageLiveChat);
				\CIMMessageParam::sendPull($mid, array_keys($paramsMessageLiveChat));
			}
			if (
				$messageFields['NO_SESSION_OL'] !== 'Y'
				&& isset($session)
				&& $session instanceof Session
				&& $resultLoadSession
			)
			{
				ImOpenLines\Mail::addSessionToMailQueue($session->getData('ID'), false);
			}
		}
		else
		{
			if (
				isset($messageFields['SYSTEM'])
				&& $messageFields['SYSTEM'] === 'Y'
				&& !self::isEnableSendSystemMessage($connectorId)
			)
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
				elseif (mb_strpos($key, 'IMOL_') === 0)
				{
					$params[$key] = $value;
				}
				elseif (mb_strpos($key, 'IS_') === 0)
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
						$attaches[] = $attach->getJson();
					}
				}
			}

			$files = [];
			if (
				!empty($messageFields['FILES'])
				&& is_array($messageFields['FILES'])
				&& Loader::includeModule('disk')
			)
			{
				foreach ($messageFields['FILES'] as $file)
				{
					$fileModel = Disk\File::loadById($file['id']);
					if (!$fileModel)
					{
						continue;
					}

					$file['link'] = \CIMDisk::GetFileLink($fileModel);
					if (!$file['link'])
					{
						continue;
					}

					$source = $fileModel->getFile();

					$file['mime'] = $source['CONTENT_TYPE'] ?? Disk\TypeFile::getMimeTypeByFilename($file['name']);
					$file['sizef'] = \CFile::FormatSize($file['size']);

					if (Disk\TypeFile::isImage($fileModel))
					{
						$files[] = [
							'name' => $file['name'],
							'type' => $file['type'],
							'mime' => $file['mime'],
							'link' => $file['link'],
							'width' => $source["WIDTH"],
							'height' => $source["HEIGHT"],
							'size' => $file['size'],
							'sizef' => $file['sizef'],
						];
					}
					else
					{
						$files[] = [
							'name' => $file['name'],
							'type' => $file['type'],
							'mime' => $file['mime'],
							'link' => $file['link'],
							'size' => $file['size'],
							'sizef' => $file['sizef'],
						];
					}
				}
			}

			if (
				empty($attaches)
				&& empty($files)
				&& empty($messageFields['MESSAGE'])
				&& $messageFields['MESSAGE'] !== "0"
				&& empty($params['url'])
			)
			{
				return false;
			}

			if (
				(empty($messageFields['SYSTEM']) || $messageFields['SYSTEM'] !== 'Y')
				&& isset($messageFields['AUTHOR_ID'], $messageFields['MESSAGE'])
				&& (int)$messageFields['AUTHOR_ID'] > 0
				&& self::isEnableSendMessageWithSignature($connectorId, $lineId)
				&& !self::isNeedRichLinkData($connectorId, $messageFields['MESSAGE'])
			)
			{
				$messageFields['MESSAGE'] =
					'[b]' . htmlspecialchars_decode(self::getOperatorName($lineId, $messageFields['AUTHOR_ID'], $messageFields['CHAT_ENTITY_ID'])) . ':[/b] '.
					($messageFields['MESSAGE'] !== '' ? '[br]'. $messageFields['MESSAGE'] : '');
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
					'text' => $messageFields['MESSAGE'] ?? '',
					'files' => $files,
					'attachments' => $attaches,
					'params' => $params,
					'system' => $messageFields['SYSTEM'] ?? 'N',
				],
				'no_session' => $messageFields['NO_SESSION_OL'] ?? 'N',
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
			}

			$resultSendMessage = (new self())->sendMessage($fields);
			if (!$resultSendMessage->isSuccess())
			{
				$isErrorLineDeactivated = $resultSendMessage->getErrorCollection()->get('IMOPENLINES_ERROR_LINE_DEACTIVATED');
				if (!$isErrorLineDeactivated)
				{
					ImOpenLines\Im::addMessage([
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
	 * Handler for event `im:OnStartWriting`
	 *
	 * @param $params
	 * @return bool
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

					$result = (new self())->sendStatusWriting($fields);
				}
			}
		}

		return $result;
	}

	/**
	 * @param Event $event
	 * @return array|array[]
	 */
	protected static function preparationDataOnSession(Event $event): array
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
					'parent_id' => $session->getData('PARENT_ID'), // previous session
					'close_term' => $session->getConfig('FULL_CLOSE_TIME'), // minutes to close session
				],
				'chat' => ['id' => $chatEntityId['connectorChatId']],
				'user' => ['id' => $chatEntityId['connectorUserId']],
			];
		}

		return $result;
	}

	/**
	 * Event handler for `imopenlines::OnSessionStart`
	 * @see ImOpenLines\Session::createSession
	 * @param Event $event
	 * @return void
	 */
	public static function onSessionStart(Event $event)
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
	 * @see ImOpenLines\Session::finish
	 * @param Event $event
	 * @return void
	 */
	public static function onSessionFinish(Event $event)
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
	 * Event handler for `im:OnAfterChatRead`
	 * @param $params
	 * @return bool
	 */
	public static function onChatRead($params)
	{
		if (!in_array($params['CHAT_ENTITY_TYPE'], Array('LINES', 'LIVECHAT')) || $params['BY_EVENT'])
		{
			return true;
		}

		if ($params['CHAT_ENTITY_TYPE'] == 'LINES')
		{
			//TODO: Replace with the method ImOpenLines\Chat::parseLinesChatEntityId
			[$connectorId, $lineId, $connectorChatId, $connectorUserId] = explode('|', $params['CHAT_ENTITY_ID']);
		}
		else // LIVECHAT
		{
			$chatId = $params['CHAT_ID'];
			$connectorChatId = 0;
			$connectorId = self::TYPE_LIVECHAT;
			//TODO: Replace with the method ImOpenLines\Chat::parseLiveChatEntityId
			[$lineId, $connectorUserId] = explode('|', $params['CHAT_ENTITY_ID']);
			$userCode = $connectorId.'|'.$lineId.'|'.$chatId.'|'.$connectorUserId;

			$orm = Model\SessionTable::getList(array(
				'select' => Array('ID', 'CHAT_ID'),
				'filter' => array(
					'=USER_CODE' => $userCode,
					'=CLOSED' => 'N'
				)
			));
			if ($session = $orm->fetch())
			{
				$connectorChatId = $session['CHAT_ID'];
				ImOpenLines\Mail::removeSessionFromMailQueue($session['ID'], false);
				Session::setLastSendMailId(['ID' => $session['ID'], 'USER_CODE' => $userCode], (int)$params['END_ID']);
			}
			$connectorId = 'lines';
		}

		$event = $params;

		$connector = [
			'connector_id' => $connectorId,
			'line_id' => $lineId,
			'chat_id' => $connectorChatId,
		];

		$connection = \Bitrix\Main\Application::getInstance()->getConnection();

		$params['END_ID'] = (int)$params['END_ID'];

		$messages = [];
		$query = $connection->query("
			SELECT M.ID, MP.PARAM_VALUE
			FROM 
				b_im_message M
				LEFT JOIN b_im_message_param MP 
					ON MP.MESSAGE_ID = M.ID 
					AND MP.PARAM_NAME = 'CONNECTOR_MID'
			WHERE
				M.CHAT_ID = ". (int)$params['CHAT_ID'] ." 
				AND M.ID > ". (int)$params['START_ID']
				.($params['END_ID'] ? " AND M.ID < ".((int)$params['END_ID'] + 1) : "")."
		");
		while($row = $query->fetch())
		{
			$messages[] = $row['PARAM_VALUE'];
		}

		return (new self())->sendStatusRead($connector, $messages, $event);
	}

	/**
	 * @param array $params
	 * @return array
	 */
	protected static function processReceivedEntity(array $params): array
	{
		$userId = (int)$params['user'];

		global $USER;
		if (
			$userId > 0
			&& !$USER->IsAuthorized()
			&&
			(
				!Loader::includeModule('im')
				|| User::getInstance($userId)->isConnector()
			)
		)
		{
			if ($USER->Authorize($userId, false, false))
			{
				setSessionExpired(true);
			}
		}

		if (!isset($params['message']['user_id']))
		{
			$params['message']['user_id'] = $params['user'];
		}

		return [
			'connector' => [
				'connector_id' => $params['connector'],
				'line_id' => $params['line'],
				'chat_id' => $params['chat']['id'],
				'user_id' => $params['user'],
			],
			'chat' => $params['chat'],
			'message' => $params['message'],
			'ref' => $params['ref'] ?? [],
			'extra' => $params['extra'] ?? [],
		];
	}

	public static function onNewChatName(Event $event)
	{
		$params = $event->getParameters();
		if (empty($params))
		{
			return false;
		}

		if (
			empty($params['name'])
			|| empty($params['connector'])
			|| empty($params['line'])
			|| empty($params['chat_id'])
		)
		{
			return false;
		}

		$userId = 0;
		if (!self::isEnableGroupByChat($params['connector']))
		{
			if (empty($params['user_id']))
			{
				return false;
			}

			$user = \Bitrix\ImConnector\Connector::initConnectorHandler($params['connector'])->getUserByUserCode(['id' => $params['user_id']]);
			if (!$user->isSuccess())
			{
				return false;
			}

			$userId = $user->getResult()['ID'];
		}

		$fields = [
			'name' => $params['name'],
			'connector' => [
				'connector_id' => $params['connector'],
				'line_id' => $params['line'],
				'chat_id' => $params['chat_id'],
				'user_id' => $userId,
			]
		];

		Log::write($fields, 'CONNECTOR - NEW CHAT NAME');

		$renameResult = (new self())->setChatName($fields);
		$result = new \Bitrix\Main\Result();
		if (!$renameResult)
		{
			$result->addError(new \Bitrix\ImConnector\Error(
				'Chat renaming failed',
				'CHAT_RENAMING_FAILED'
			));
		}

		return $result;
	}

	/**
	 * @param $params
	 * @return array|false
	 */
	public static function onReceivedEntity($params)
	{
		$fields = self::processReceivedEntity($params);

		Log::write($fields, 'CONNECTOR - ENTITY ADD');

		return (new self())->addMessage($fields);
	}

	/**
	 * @param Event $event
	 * @return array|false
	 */
	public static function onReceivedMessage(Event $event)
	{
		$params = $event->getParameters();
		if (empty($params))
		{
			return false;
		}

		$result = static::onReceivedEntity($params);
		if ($result !== false)
		{
			$restEvent = new \Bitrix\Main\Event(
				'imopenlines',
				'OnOpenLineMessageAdd',
				[
					'DATA' => self::processReceivedEntity($params)
				]
			);
			$restEvent->send();
		}

		return $result;
	}

	/**
	 * @param Event $event
	 * @return array|false
	 */
	public static function onReceivedPost(Event $event)
	{
		$params = $event->getParameters();
		if (empty($params))
		{
			return false;
		}

		$params['message']['id'] = '';

		return static::onReceivedEntity($params);
	}

	/**
	 * @param Event $event
	 * @return bool
	 */
	public static function onReceivedMessageUpdate(Event $event)
	{
		$params = $event->getParameters();
		if (empty($params))
		{
			return false;
		}

		$fields = self::processReceivedEntity($params);

		Log::write($fields, 'CONNECTOR - ENTITY UPDATE');

		$result = (new self())->updateMessage($fields);

		if ($result)
		{
			$restEvent = new \Bitrix\Main\Event(
				'imopenlines',
				'OnOpenLineMessageUpdate',
				[
					'DATA' => $fields
				]
			);
			$restEvent->send();
		}

		return $result;
	}

	/**
	 * @param Event $event
	 * @return bool
	 */
	public static function onReceivedPostUpdate(Event $event)
	{
		return self::onReceivedMessageUpdate($event);
	}

	/**
	 * @param Event $event
	 * @return bool
	 */
	public static function onReceivedMessageDelete(Event $event)
	{
		$params = $event->getParameters();
		if (empty($params))
		{
			return false;
		}

		$fields = self::processReceivedEntity($params);

		Log::write($fields, 'CONNECTOR - ENTITY DELETE');

		$result = (new self())->deleteMessage($fields);
		if ($result)
		{
			$restEvent = new \Bitrix\Main\Event(
				'imopenlines',
				'OnOpenLineMessageDelete',
				[
					'DATA' => $fields
				]
			);
			$restEvent->send();
		}

		return $result;
	}

	/**
	 * Event handler for command /start.
	 * @param Event $event
	 * @event 'imconnector:OnReceivedCommandStart'
	 * @see \Bitrix\ImConnector\Provider\Base\Input::sendEventCommand
	 * @return bool
	 */
	public static function onReceivedCommandStart(Event $event)
	{
		$params = $event->getParameters();
		if (empty($params))
		{
			return false;
		}

		$fields = self::processReceivedEntity($params);

		Log::write($fields, 'CONNECTOR - COMMAND START');

		$chat = new ImOpenLines\Chat();
		$chat->load([
			'USER_CODE' => self::getUserCode($fields['connector']),
			'USER_ID' => $fields['connector']['user_id'],
			'CONNECTOR' => $fields['connector'],
		]);

		$session = new ImOpenLines\Session();
		$session->setChat($chat);

		/** @var ImOpenLines\Tracker $tracker */
		$tracker = ServiceLocator::getInstance()->get('ImOpenLines.Services.Tracker');

		$hasTrackerRef = false;
		if (!empty($fields['ref']['source']))
		{
			$hasTrackerRef = !empty($tracker->findExpectationByTrackId($fields['ref']['source'])); //todo: Replace it with session method
		}

		// start parameter
		$session->load([
			'USER_CODE' => self::getUserCode($fields['connector']),
			'CONFIG_ID' => $fields['connector']['line_id'],
			'USER_ID' => $fields['connector']['user_id'],
			'SOURCE' => $fields['connector']['connector_id'],
			'MODE' => ImOpenLines\Session::MODE_INPUT,
			'CRM_TRACKER_REF' => $hasTrackerRef ? $fields['ref']['source'] : '',
		]);

		return true;
	}

	/**
	 * @param Event $event
	 * @return bool
	 */
	public static function onReceivedStatusError(Event $event)
	{
		return true;
	}

	/**
	 * @param Event $event
	 * @return bool
	 */
	public static function onReceivedStatusDelivery(Event $event): bool
	{
		$params = $event->getParameters();
		if (
			empty($params)
			|| empty($params['im']['message_id'])
		)
		{
			return false;
		}

		Log::write($params, 'CONNECTOR - STATUS DELIVERY');

		if (!Loader::includeModule('im'))
		{
			return false;
		}

		$message = new Im\V2\Message($params['im']['message_id']);
		if (
			empty($message->getMessageId())
			|| $message->getMessageId() != $params['im']['message_id']
		)
		{
			return false;
		}

		$chat = $message->getChat();
		if (!($chat instanceof Im\V2\Chat\OpenLineChat))
		{
			return false;
		}

		// set read message
		$messages = new Im\V2\MessageCollection();
		$messages->add($message);

		$entityData = $chat->getEntityData();
		$connectorUserId = $entityData['entityId']['connectorUserId'] ?? 0;
		$chat
			->withContextUser((int)$connectorUserId)
			->readMessages($messages, true)
		;

		$messageParams = $message->getParams();
		if (!$messageParams->get(Im\V2\Message\Params::SENDING)->getValue())
		{
			return false;
		}

		$pullParams = [
			Im\V2\Message\Params::SENDING,
			Im\V2\Message\Params::SENDING_TS,
		];
		$messageParams->fill([
			Im\V2\Message\Params::SENDING => false,
			Im\V2\Message\Params::SENDING_TS => 0
		]);
		if (!empty($params['message']['id']))
		{
			$pullParams[] = Imopenlines\MessageParameter::CONNECTOR_MID;
			$messageParams->get(Imopenlines\MessageParameter::CONNECTOR_MID)->setValue($params['message']['id']);
		}
		$messageParams->save();

		\CIMMessageParam::sendPull($message->getMessageId(), $pullParams);

		return true;
	}

	/**
	 * @param Event $event
	 * @return bool
	 */
	public static function onReceivedStatusReading(Event $event)
	{
		return true;
	}

	/**
	 * @param Event $event
	 * @return bool
	 */
	public static function onReceivedStatusWrites(Event $event)
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

		$chatId = $session->getChat()->getData('ID');

		if (\Bitrix\Main\Loader::includeModule('im'))
		{
			\CIMMessenger::StartWriting('chat'.$chatId, $params['user'], "", true);
		}

		return true;
	}

	/**
	 * @param Event $event
	 * @return bool
	 */
	public static function OnReceivedError(Event $event): bool
	{
		$result = false;

		$params = $event->getParameters();

		if (
			!empty($params)
			&& !empty($params['connector'])
			&& Loader::includeModule('imconnector')
		)
		{
			$result = ImConnector\Connector::initConnectorHandler($params['connector'])->receivedError($params);
		}

		return $result;
	}

	/**
	 * @param Event $event
	 * @return bool
	 */
	public static function OnReceivedStatusBlock(Event $event): bool
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

	//endregion

	//region Supported interfaces

	/**
	 * @return array
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
	 * @param $connectorId
	 * @return bool
	 */
	public static function isEnableSendSystemMessage($connectorId): bool
	{
		$result = true;

		if (Loader::includeModule('imconnector'))
		{
			$result = ImConnector\Connector::isNeedSystemMessages($connectorId);
		}

		return $result;
	}

	/**
	 * @param $connectorId
	 * @param int $lineId
	 * @return bool
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
	 * @param string $connectorId
	 * @return bool
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
	 * @param string $idConnector
	 * @return bool
	 */
	public static function isLiveChat($idConnector)
	{
		$result = false;

		$idConnector = mb_strtolower($idConnector);

		if ($idConnector == self::TYPE_LIVECHAT)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param string $idConnector
	 * @return bool
	 */
	public static function isNetwork($idConnector)
	{
		$result = false;

		$idConnector = mb_strtolower($idConnector);

		if ($idConnector == self::TYPE_NETWORK)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param string $idConnector
	 * @return bool
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
	 * @param string $idConnector
	 * @return bool
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
	 * @param string $idConnector
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

	//endregion

	//region Operator

	/**
	 * Returns the array of the operator description.
	 *
	 * @param $lineId
	 * @param $userId
	 * @param string $userCodeSession
	 * @return array|string
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
				User::getInstance($userId)->getFields(),
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
	 */
	public static function getOperatorName($lineId, $userId, $userCodeSession = '')
	{
		$result = '';

		$actualLineId = Queue::getActualLineId([
			'LINE_ID' =>  $lineId,
			'USER_CODE' => $userCodeSession
		]);

		$userArray = Queue::getUserData($actualLineId, $userId);

		if ($userArray)
		{
			$result = $userArray['NAME'];
		}

		return $result;
	}

	/**
	 * Returns the operator's avatar.
	 *
	 * @param int $lineId OpenLine Id.
	 * @param int $userId User Id.
	 * @param string $userCodeSession Combined session code, ex. 'livechat|1|33|14'.
	 * @return string|null
	 */
	public static function getOperatorAvatar(int $lineId, int $userId, string $userCodeSession = ''): ?string
	{
		$actualLineId = Queue::getActualLineId([
			'LINE_ID' =>  $lineId,
			'USER_CODE' => $userCodeSession
		]);
		$userArray = Queue::getUserData($actualLineId, $userId);

		return $userArray['AVATAR'];
	}

	//endregion

	/**
	 * @param $chatId
	 * @param $customData
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
			ImOpenLines\Im::addMessage([
				"TO_CHAT_ID" => $chatId,
				'MESSAGE' => $customDataMessage,
				'ATTACH' => $customDataAttach,
				'SYSTEM' => 'Y',
				'SKIP_COMMAND' => 'Y',
				"PARAMS" => [
					"CLASS" => "bx-messenger-content-item-system",
				],
			]);
		}
	}

	/**
	 * @return BasicError|Error|null
	 */
	public function getError()
	{
		return $this->error;
	}
}
