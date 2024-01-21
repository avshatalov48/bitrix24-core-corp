<?php
namespace Bitrix\ImConnector\Provider\Network;

use Bitrix\ImConnector\DeliveryMark;
use Bitrix\Main\Loader;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\ImBot;
use Bitrix\Imopenlines\Model\SessionTable;
use Bitrix\Imopenlines\MessageParameter;
use Bitrix\ImConnector;
use Bitrix\ImConnector\Error;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Connector;
use Bitrix\ImConnector\Provider\Base;

/**
 * Class Input provider for Network connector.
 *
 * @package Bitrix\ImConnector\Provider\Network
 */
class Input extends Base\Input
{
	/**
	 * Input constructor.
	 * @param array $params
	 */
	public function __construct(array $params)
	{
		parent::__construct($params);

		$this->command = $params['BX_COMMAND'];
		unset($params['BX_COMMAND']);
		$this->params = $params;
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

		if ($result->isSuccess())
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
					//todo: Using parent:: to fire error, against current method.
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
		$result = clone $this->result;

		if (!Loader::includeModule('im'))
		{
			$result->addError(new Error(
				'Failed to load the im module',
				'ERROR_IMCONNECTOR_FAILED_LOAD_IM',
				__METHOD__
			));
		}

		if ($result->isSuccess())
		{
			$resultData = [];
			foreach ($this->data as $cell => $params)
			{
				$resultStatus = new Result();
				$messageData = [];

				$params['MESSAGE_ID'] = (int)($params['MESSAGE_ID'] ?? -1);
				if ($params['MESSAGE_ID'] <= 0)
				{
					$resultStatus->addError(new Error(
						'Got wrong or empty parameter MESSAGE_ID',
						'ERROR_IMCONNECTOR_WRONG_PARAMETER',
						__METHOD__,
						$params
					));
				}

				if ($resultStatus->isSuccess())
				{
					$queryMessage = ImConnector\Data\Message::getInstance()->query();
					$messageData = $queryMessage
						->setSelect([
							'CHAT_ENTITY_TYPE' => 'CHAT.ENTITY_TYPE',
							'CHAT_ENTITY_ID' => 'CHAT.ENTITY_ID',
							'CHAT_ID'
						])
						->setFilter([
							'=ID' => $params['MESSAGE_ID']
						])
						->exec()
						->fetch()
					;
					if (
						!$messageData
						|| $messageData['CHAT_ENTITY_TYPE'] != 'LINES'
						|| mb_strpos($messageData['CHAT_ENTITY_ID'], 'network|'. $params['LINE_ID']. '|'. $params['GUID']) !== 0
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

				if ($resultStatus->isSuccess())
				{
					$queryMessageParam = ImConnector\Data\MessageParam::getInstance()->query();
					$messageParamData = $queryMessageParam
						->addSelect('PARAM_VALUE')
						->setFilter([
							'=MESSAGE_ID' => $params['MESSAGE_ID'],
							'=PARAM_NAME' => 'SENDING',
						])
						->exec()
						->fetch()
					;
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

				if ($resultStatus->isSuccess())
				{
					$status = [
						'chat' => [
							'id' => $params['GUID']
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
						$resultStatus->addErrors($event->getErrors());
					}
				}

				if ($resultStatus->isSuccess())
				{
					$resultData[$cell]['SUCCESS'] = true;
					DeliveryMark::unsetDeliveryMark($params['MESSAGE_ID'], $messageData['CHAT_ID']);
				}
				else
				{
					$resultData[$cell]['SUCCESS'] = false;
					$resultData[$cell]['ERRORS'] = $resultStatus->getErrorMessages();
				}
			}

			$result->setResult($resultData);
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	protected function receivingSessionVote(): Result
	{
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			$resultData = [];
			foreach ($this->data as $cell => $params)
			{
				$resultProcessingSessionVote = $this->processingSessionVote($params);

				$resultData[$cell] = $resultProcessingSessionVote->getResult();
				if ($resultProcessingSessionVote->isSuccess())
				{
					$resultData[$cell]['SUCCESS'] = true;
				}
				else
				{
					$resultData[$cell]['SUCCESS'] = false;
					$resultData[$cell]['ERRORS'] = $resultProcessingSessionVote->getErrorMessages();
				}
			}
			$result->setResult($resultData);
		}

		return $result;
	}

	/**
	 * @param array $params
	 * @return Result
	 */
	protected function processingSessionVote(array $params): Result
	{
		$result = clone $this->result;
		$messageParams = [];

		if (!Loader::includeModule('imopenlines'))
		{
			$result->addError(new Error(
				'Failed to load the imopenlines module',
				'ERROR_IMCONNECTOR_FAILED_LOAD_IMOPENLINES',
				__METHOD__
			));
		}

		if ($result->isSuccess())
		{
			$resultProcessing = Connector::initConnectorHandler($this->connector)->processingInputSessionVote($params, $this->line);

			if ($resultProcessing->isSuccess())
			{
				$params = $resultProcessing->getResult()['PARAMS'];
				$messageParams = $resultProcessing->getResult()['MESSAGE_PARAMS'];
			}
			else
			{
				$result->addErrors($resultProcessing->getErrors());
			}
		}

		if ($result->isSuccess())
		{
			$params['ACTION'] = $params['ACTION'] === 'dislike' ? 'dislike': 'like';

			$resultVote = false;
			/** @var \Bitrix\ImOpenLines\Services\SessionManager $sessionManager */
			$sessionManager = ServiceLocator::getInstance()->get('ImOpenLines.Services.SessionManager');
			if ($sessionManager instanceof \Bitrix\ImOpenLines\Services\SessionManager)
			{
				$resultVote = $sessionManager->voteAsUser((int)$messageParams[MessageParameter::IMOL_VOTE_SID], $params['ACTION']);
			}

			if ($resultVote)
			{
				$messageParamService = ServiceLocator::getInstance()->get('Im.Services.MessageParam');
				if ($messageParamService instanceof \Bitrix\Im\Services\MessageParam)
				{
					$messageParamService->setParam((int)$params['MESSAGE_ID'], MessageParameter::IMOL_VOTE, $params['ACTION'], true);

					global $USER_FIELD_MANAGER;
					if (
						defined('IMOL_FDC')
						&& isset($params['VOTE_IP'])
						&& is_string($params['VOTE_IP'])
						&& $USER_FIELD_MANAGER instanceof \CUserTypeManager
						&& array_key_exists('UF_IMOPENLINES_SESSION_VOTE_IP', $USER_FIELD_MANAGER->GetUserFields(SessionTable::getUfId()))
					)
					{
						$USER_FIELD_MANAGER->Update(SessionTable::getUfId(), $params['SESSION_ID'], ['UF_IMOPENLINES_SESSION_VOTE_IP' => $params['VOTE_IP']]);
					}
				}
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

		return $result;
	}

	/**
	 * @param $message
	 * @return Result
	 */
	protected function finishSession($message): Result
	{
		$result = clone $this->result;

		if (!Loader::includeModule('im'))
		{
			$result->addError(new Error(
				'Failed to load the im module',
				'ERROR_IMCONNECTOR_FAILED_LOAD_IM',
				__METHOD__
			));
		}

		if (!Loader::includeModule('imopenlines'))
		{
			$result->addError(new Error(
				'Failed to load the imopenlines module',
				'ERROR_IMCONNECTOR_FAILED_LOAD_IMOPENLINES',
				__METHOD__
			));
		}

		if ($result->isSuccess())
		{
			$resultData = [];
			foreach ($this->data as $cell => $params)
			{
				$resultProcessingFinishSession = $this->processingFinishSession($params, $message);

				$resultData[$cell] = $resultProcessingFinishSession->getResult();
				if ($resultProcessingFinishSession->isSuccess())
				{
					$resultData[$cell]['SUCCESS'] = true;
				}
				else
				{
					$resultData[$cell]['SUCCESS'] = false;
					$resultData[$cell]['ERRORS'] = $resultProcessingFinishSession->getErrorMessages();
				}
			}
			$result->setResult($resultData);
		}

		return $result;
	}

	/**
	 * @param array $params
	 * @param $message
	 * @return Result
	 */
	protected function processingFinishSession(array $params, $message): Result
	{
		$result = clone $this->result;

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

		if ($result->isSuccess())
		{
			$querySession = ImConnector\Data\Session::getInstance()->query();
			$orm = $querySession
				->setSelect(['ID', 'CONFIG_ID', 'USER_ID', 'SOURCE', 'CHAT_ID', 'USER_CODE'])
				->setFilter([
					'=ID' => $sessions,
					'=CLOSED' => 'N',
				])
				->exec()
			;
			while (
				($row = $orm->fetch())
				&& $result->isSuccess()
			)
			{
				/** @var \Bitrix\ImOpenLines\Services\Message $messenger */
				$messenger = ServiceLocator::getInstance()->get('ImOpenLines.Services.Message');
				$messenger->addMessage([
					'MESSAGE_TYPE' => \IM_MESSAGE_OPEN_LINE,
					'TO_CHAT_ID' => $row['CHAT_ID'],
					'MESSAGE' => $params['MESSAGE'] ? : $message,
					'SYSTEM' => 'Y',
					'SKIP_COMMAND' => 'Y',
					'RECENT_ADD' => 'N',
					'PARAMS' => [
						'CLASS' => 'bx-messenger-content-item-system'
					],
				]);

				/** @var \Bitrix\ImOpenLines\Services\SessionManager $sessionManager */
				$sessionManager = ServiceLocator::getInstance()->get('ImOpenLines.Services.SessionManager');
				$session = $sessionManager->create();

				$resultSessionStart = $session->start(array_merge($row, [
					'SKIP_CREATE' => 'Y',
				]));

				if (
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
					$session->finish();
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
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			$resultData = [];
			foreach ($this->data as $cell => $params)
			{
				$resultProcessingCommandKeyboard = $this->processingCommandKeyboard($params);

				$resultData[$cell] = $resultProcessingCommandKeyboard->getResult();
				if ($resultProcessingCommandKeyboard->isSuccess())
				{
					$resultData[$cell]['SUCCESS'] = true;
				}
				else
				{
					$resultData[$cell]['SUCCESS'] = false;
					$resultData[$cell]['ERRORS'] = $resultProcessingCommandKeyboard->getErrorMessages();
				}
			}
			$result->setResult($resultData);
		}

		return $result;
	}

	/**
	 * @param array $params
	 * @return Result
	 */
	protected function processingCommandKeyboard(array $params): Result
	{
		$result = clone $this->result;
		$userId = 0;

		if (!Loader::includeModule('imbot'))
		{
			$result->addError(new Error(
				'Failed to load the ImBot module',
				'ERROR_IMCONNECTOR_FAILED_LOAD_IM_BOT',
				__METHOD__
			));
		}

		if (!Loader::includeModule('im'))
		{
			$result->addError(new Error(
				'Failed to load the im module',
				'ERROR_IMCONNECTOR_FAILED_LOAD_IM',
				__METHOD__
			));
		}

		if ($result->isSuccess())
		{
			$resultProcessing = Connector::initConnectorHandler($this->connector)->processingInputCommandKeyboard($params, $this->line);

			if ($resultProcessing->isSuccess())
			{
				$params = $resultProcessing->getResult()['PARAMS'];
				$userId = $resultProcessing->getResult()['USER_ID'];
			}
			else
			{
				$result->addErrors($resultProcessing->getErrors());
			}
		}

		if (
			$result->isSuccess()
			&& isset($params['MESSAGE_ID'])
			&& (int)$params['MESSAGE_ID'] > 0
		)
		{
			/** @var \Bitrix\Im\Services\Message $messager */
			$messager = ServiceLocator::getInstance()->get('Im.Services.Message');
			$message = $messager->getMessage((int)$params['MESSAGE_ID']);

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

			$resultEvent = $this->sendEvent([
				'user' => $userId,
				'chat' => [
					'id' => $params['GUID']
				],
				'command' => $params['COMMAND'],
				'command_params' => $params['COMMAND_PARAMS'],
			], Library::EVENT_RECEIVED_MESSAGE);

			if (!$resultEvent->isSuccess())
			{
				$result->addErrors($resultEvent->getErrors());
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

		if (
			$result->isSuccess()
			&& !empty($result->getResult())
		)
		{
			foreach ($result->getResult() as $evenResult)
			{
				if ($evenResult instanceof EventResult)
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
