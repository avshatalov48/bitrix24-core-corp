<?php
namespace Bitrix\ImConnector\Provider\Network;

use Bitrix\Main\Loader;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImBot;

use Bitrix\Im\Model\MessageTable;
use Bitrix\Im\Model\MessageParamTable;

use Bitrix\ImOpenLines\Im;
use Bitrix\ImOpenlines\Session;
use Bitrix\Imopenlines\Model\SessionTable;

use Bitrix\ImConnector\Error;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Connectors;
use Bitrix\ImConnector\Provider\Base;
use Bitrix\ImConnector\InteractiveMessage;

class Input extends Base\Input
{
	/**
	 * Input constructor.
	 * @param string $command
	 * @param array $params
	 */
	public function __construct(string $command, array $params)
	{
		parent::__construct($params);

		$this->params = $params;

		$this->command = $command;
		$this->connector = 'network';
		$this->line = $this->params['LINE_ID'];
		$this->data = [$this->params];
	}

	/**
	 * @param $command
	 * @param $connector
	 * @param null $line
	 * @param array $data
	 * @return Result
	 */
	public function routing($command, $connector, $line = null, $data = []): Result
	{
		$result = parent::routing($command, $connector, $line, $data);

		if($result->isSuccess())
		{
			switch ($command)
			{
				case 'clientMessageAdd'://To receive the message
				case 'clientMessageUpdate':
				case 'clientMessageDelete':
				case 'clientStartWriting':
					$typeMessage = '';
					switch ($command)
					{
						case 'clientMessageAdd'://To receive the message
							$typeMessage = 'message';
							break;
						case 'clientMessageUpdate':
							$typeMessage = 'message_update';
							break;
						case 'clientMessageDelete':
							$typeMessage = 'message_del';
							break;
						case 'clientStartWriting':
							$typeMessage = 'typing_start';
							break;
					}
					foreach ($this->data as $cell=>$message)
					{
						$this->data[$cell]['type_message'] = $typeMessage;
					}
					$result = $this->receivingMessage();
					break;
				case 'clientMessageReceived'://To receive a delivery status
					$result = $this->receivingStatusDelivery();
					break;
				case 'clientSessionVote':
					$result = $this->receivingSessionVote();
					break;
				case 'clientChangeLicence':
					$result = $this->finishSession(Loc::getMessage('IMCONNECTOR_PROVIDER_NETWORK_TARIFF_DIALOG_CLOSE'));
					break;
				case 'clientRequestFinalizeSession':
					$result = $this->finishSession(Loc::getMessage('IMCONNECTOR_PROVIDER_NETWORK_UNREGISTER_DIALOG_CLOSE'));
					break;
				case 'clientCommandSend':
					$result = $this->receivingCommandKeyboard();
					break;
				default:
					$result = parent::receivingDefault();
			}
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	protected function receivingDefault(): Result
	{
		return $this->result;
	}

	/**
	 * @return Result
	 */
	protected function receivingStatusDelivery(): Result
	{
		$result = $this->result;

		if(!Loader::includeModule('im'))
		{
			$result->addError(new Error(
				'Failed to load the im module',
				'ERROR_IMCONNECTOR_FAILED_LOAD_IM',
				__METHOD__
			));
		}

		if($result->isSuccess())
		{
			foreach ($this->data as $cell => $params)
			{
				$resultStatus = new Result();
				$messageData = [];
				$status['MESSAGE_ID'] = (int)$params['MESSAGE_ID'];
				if ($params['MESSAGE_ID'] <= 0)
				{
					$resultStatus->addError(new Error(
						'Failed to load the im module',
						'ERROR_IMCONNECTOR_FAILED_LOAD_IM',
						__METHOD__,
						$params
					));
				}

				if($resultStatus->isSuccess())
				{
					$messageData = MessageTable::getList([
						'select' => [
							'CHAT_ENTITY_TYPE' => 'CHAT.ENTITY_TYPE',
							'CHAT_ENTITY_ID' => 'CHAT.ENTITY_ID',
							'CHAT_ID'
						],
						'filter' => [
							'=ID' => $params['MESSAGE_ID']
						]
					])->fetch();

					if (
						!$messageData
						|| $messageData['CHAT_ENTITY_TYPE'] != 'LINES'
						||
						mb_strpos(
							$messageData['CHAT_ENTITY_ID'], 'network|'
							. $params['LINE_ID']
							. '|'
							. $params['GUID']
						) !== 0
					)
					{
						$resultStatus->addError(new Error(
							'Failed to load message data',
							'ERROR_IMCONNECTOR_FAILED_LOAD_MESSAGE_DATA',
							__METHOD__,
							$params
						));
					}
				}

				if($resultStatus->isSuccess())
				{
					$messageParamData = MessageParamTable::getList([
						'select' => ['PARAM_VALUE'],
						'filter' => [
							'=MESSAGE_ID' => $params['MESSAGE_ID'],
							'=PARAM_NAME' => 'SENDING'
						]
					])->fetch();
					if (
						!$messageParamData
						|| $messageParamData['PARAM_VALUE'] != 'Y'
					)
					{
						$resultStatus->addError(new Error(
							'Failed to load message parameters',
							'ERROR_IMCONNECTOR_FAILED_LOAD_MESSAGE_PARAMETERS',
							__METHOD__,
							$params
						));
					}
				}

				if($resultStatus->isSuccess())
				{
					$status = [
						'chat' => [
							'id' => $params['DIALOG_ID']
						],
						'im' => [
							'message_id' => $params['MESSAGE_ID'],
							'chat_id' => $messageData['CHAT_ID']
						],
						'message' => [
							'id' => [$params['CONNECTOR_MID']]
						],
					];

					$event = $this->sendEventStatusDelivery($status);
					if (!$event->isSuccess())
					{
						$result->addErrors($event->getErrors());
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	protected function receivingSessionVote(): Result
	{
		$result = $this->result;

		if(!Loader::includeModule('im'))
		{
			$result->addError(new Error(
				'Failed to load the im module',
				'ERROR_IMCONNECTOR_FAILED_LOAD_IM',
				__METHOD__
			));
		}

		if(!Loader::includeModule('imopenlines'))
		{
			$result->addError(new Error(
				'Failed to load the imopenlines module',
				'ERROR_IMCONNECTOR_FAILED_LOAD_IMOPENLINES',
				__METHOD__
			));
		}

		if($result->isSuccess())
		{
			foreach ($this->data as $cell => $params)
			{
				if (
					!isset($params['USER'])
					&& $result->isSuccess()
				)
				{
					$result->addError(new Error(
						'User data not transmitted',
						'ERROR_IMCONNECTOR_NOT_TRANSMITTED_USER_DATA',
						__METHOD__,
						$params
					));
				}

				if($result->isSuccess())
				{
					$userId = Connectors\Network::getUserId($params['USER']);

					if (empty($userId))
					{
						$result->addError(new Error(
							'Failed to create or update user',
							'ERROR_IMCONNECTOR_FAILED_USER',
							__METHOD__,
							$params
						));
					}
				}

				$messageParams['IMOL_VOTE'] = 0;

				if($result->isSuccess())
				{
					$messageParams = \CIMMessageParam::Get($params['MESSAGE_ID']);

					if ($messageParams['IMOL_VOTE'] != $params['SESSION_ID'])
					{
						$result->addError(new Error(
							'Voting for the wrong session',
							'ERROR_IMCONNECTOR_VOTING_FOR_WRONG_SESSION',
							__METHOD__,
							$params
						));
					}
				}

				if($result->isSuccess())
				{
					$params['ACTION'] = $params['ACTION'] === 'dislike'? 'dislike': 'like';

					$resultVote = Session::voteAsUser($messageParams['IMOL_VOTE'], $params['ACTION']);
					if ($resultVote)
					{
						\CIMMessageParam::Set($params['MESSAGE_ID'], [
							'IMOL_VOTE' => $params['ACTION']
						]);
						\CIMMessageParam::SendPull($params['MESSAGE_ID'], ['IMOL_VOTE']);
					}
					else
					{
						$result->addError(new Error(
							'Voting error',
							'ERROR_IMCONNECTOR_VOTING_ERROR',
							__METHOD__,
							$params
						));
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param $message
	 * @return Result
	 */
	protected function finishSession($message): Result
	{
		$result = $this->result;

		if(!Loader::includeModule('im'))
		{
			$result->addError(new Error(
				'Failed to load the im module',
				'ERROR_IMCONNECTOR_FAILED_LOAD_IM',
				__METHOD__
			));
		}

		if(!Loader::includeModule('imopenlines'))
		{
			$result->addError(new Error(
				'Failed to load the imopenlines module',
				'ERROR_IMCONNECTOR_FAILED_LOAD_IMOPENLINES',
				__METHOD__
			));
		}

		if($result->isSuccess())
		{
			foreach ($this->data as $cell => $params)
			{
				$sessions = array_map(function($value){return (int)$value;}, $params['SESSIONS']);

				if (
					empty($sessions)
					&& $result->isSuccess()
				)
				{
					$result->addError(new Error(
						'No session',
						'ERROR_IMCONNECTOR_ERROR_NO_SESSION',
						__METHOD__,
						$params
					));
				}

				if($result->isSuccess())
				{
					$orm = SessionTable::getList([
						'select' => ['ID', 'CONFIG_ID', 'USER_ID', 'SOURCE', 'CHAT_ID', 'USER_CODE'],
						'filter' => [
							'=ID' => $sessions,
							'=CLOSED' => 'N',
						]
					]);
					while(
						($row = $orm->fetch())
						&& $result->isSuccess()
					)
					{
						Im::addMessage([
							'TO_CHAT_ID' => $row['CHAT_ID'],
							'MESSAGE' => $params['MESSAGE'] ? : $message,
							'SYSTEM' => 'Y',
							'SKIP_COMMAND' => 'Y',
							'RECENT_ADD' => 'N',
							'PARAMS' => [
								'CLASS' => 'bx-messenger-content-item-system'
							],
						]);

						$session = new Session();
						$resultSessionStart = $session->start(array_merge($row, [
							'SKIP_CREATE' => 'Y',
						]));
						if(
							!$resultSessionStart->isSuccess() ||
							$resultSessionStart->getResult() !== true
						)
						{
							$result->addError(new Error(
								'Failed to load session',
								'ERROR_IMCONNECTOR_FAILED_LOAD_SESSION',
								__METHOD__,
								$params
							));
						}
						else
						{
							$session->update([
								'WAIT_ACTION' => 'Y',
								'WAIT_ANSWER' => 'N',
							]);
							$session->finish();
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	protected function receivingCommandKeyboard(): Result
	{
		$result = $this->result;

		if(!Loader::includeModule('im'))
		{
			$result->addError(new Error(
				'Failed to load the im module',
				'ERROR_IMCONNECTOR_FAILED_LOAD_IM',
				__METHOD__
			));
		}

		if($result->isSuccess())
		{
			foreach ($this->data as $cell => $params)
			{
				$userId = 0;

				if (
					$result->isSuccess()
					&& !isset($params['USER'])
				)
				{
					$result->addError(new Error(
						'User data not transmitted',
						'ERROR_IMCONNECTOR_NOT_TRANSMITTED_USER_DATA',
						__METHOD__,
						$params
					));
				}

				if($result->isSuccess())
				{
					$userId = Connectors\Network::getUserId($params['USER']);

					if (empty($userId))
					{
						$result->addError(new Error(
							'Failed to create or update user',
							'ERROR_IMCONNECTOR_FAILED_USER',
							__METHOD__,
							$params
						));
					}
				}

				if($result->isSuccess())
				{
					$interactiveMessage = InteractiveMessage\Input::init('network');
					$resultProcessing = $interactiveMessage->processingCommandKeyboard($params['COMMAND'], $params['COMMAND_PARAMS']);

					if ($resultProcessing->isSuccess())
					{
						$message = \CIMMessenger::GetById($params['MESSAGE_ID']);
						if ($message['PARAMS']['CONNECTOR_MID'][0] == $params['CONNECTOR_MID'])
						{
							foreach ($message['PARAMS']['KEYBOARD'] as &$button)
							{
								$button['DISABLED'] = 'Y';
							}

							ImBot\Service\Openlines::operatorMessageUpdate([
								'LINE_ID' => $params['LINE_ID'],
								'GUID' => $params['GUID'],
								'MESSAGE_ID' => $params['MESSAGE_ID'],
								'CONNECTOR_MID' => $params['CONNECTOR_MID'],
								'MESSAGE_TEXT' => $message['MESSAGE'],
								'URL_PREVIEW' => 'N',
								'KEYBOARD' => $message['PARAMS']['KEYBOARD'],
							]);
						}
					}
					else
					{
						$result->addErrors($resultProcessing->getErrors());
					}
				}

				if($result->isSuccess())
				{
					$resultEvent = $this->sendEvent([
						'user' => $userId,
						'chat' => [
							'id' => $params['GUID']
						],
						'command' => $params['COMMAND'],
						'command_params' => $params['COMMAND_PARAMS'],
					], Library::EVENT_RECEIVED_MESSAGE);
					if(!$resultEvent->isSuccess())
					{
						$result->addErrors($resultEvent->getErrors());
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	protected function receivingStatusReading(): Result
	{
		return $this->receivingBase();
	}

	/**
	 * @return Result
	 */
	protected function receivingError(): Result
	{
		return $this->receivingBase();
	}

	/**
	 * @return Result
	 */
	protected function receivingStatusBlock(): Result
	{
		return $this->receivingBase();
	}

	/**
	 * @return Result
	 */
	protected function deactivateConnector(): Result
	{
		return $this->receivingBase();
	}

	//TODO: Event
	/**
	 * @param $data
	 * @return Result
	 */
	protected function sendEventAddMessage($data): Result
	{
		$result = $this->sendEvent($data, Library::EVENT_RECEIVED_MESSAGE);

		if(
			$result->isSuccess()
			&& !empty($result->getResult())
		)
		{
			foreach ($result->getResult() as $evenResult)
			{
				if($evenResult instanceof EventResult)
				{
					$connectorParameters = $evenResult->getParameters();

					if (
						is_array($connectorParameters)
						&& !empty($connectorParameters)
					)
					{
						ImBot\Service\Openlines::operatorMessageReceived([
							'LINE_ID' => $this->line,
							'GUID' => $data['chat']['id'],
							'MESSAGE_ID' => $data['message']['id'],
							'CONNECTOR_MID' => $connectorParameters['MESSAGE_ID'],
							'SESSION_ID' => $connectorParameters['SESSION_ID'],
						]);
					}
				}
			}
		}

		return $result;
	}
}
