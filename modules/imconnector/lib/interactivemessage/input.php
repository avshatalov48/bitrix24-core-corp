<?php
namespace Bitrix\ImConnector\InteractiveMessage;

use \Bitrix\Main\Loader;

use \Bitrix\ImOpenLines\Im,
	\Bitrix\ImOpenLines\Chat,
	\Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Session,
	\Bitrix\ImOpenLines\Model\SessionTable;

use \Bitrix\ImConnector\Error,
	\Bitrix\ImConnector\Result,
	\Bitrix\ImConnector\Connector;

/**
 * Class Base
 * @package Bitrix\ImConnector\InteractiveMessage
 */
class Input
{
	protected $message;
	protected $isProcessing = false;
	protected $idConnector = '';
	public const URL_ACTIVITY = '/crm/activity/?open_view=#activity_id#';

	/**
	 * @param string $idConnector
	 * @return Input
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function initialization($idConnector = ''): Input
	{
		$class = __CLASS__;

		if(
			!empty($idConnector) &&
			Connector::isConnector($idConnector)
		)
		{
			$idConnector = Connector::getConnectorRealId($idConnector);
			$className = "Bitrix\\ImConnector\\InteractiveMessage\\Connectors\\" . $idConnector . "\\Input";
			if(class_exists($className))
			{
				$class = $className;
			}
		}

		return new $class($idConnector);
	}

	/**
	 * Input constructor.
	 * @param $idConnector
	 */
	protected function __construct($idConnector)
	{
		$this->idConnector = $idConnector;
	}

	/**
	 * @param $message
	 * @return Input
	 */
	public function setMessage($message): Input
	{
		$this->message = $message;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getMessage(): array
	{
		return $this->message;
	}

	/**
	 * @return array
	 */
	public function processing(): array
	{
		return $this->message;
	}

	/**
	 * @return bool
	 */
	public function isSendMessage(): bool
	{
		$result = true;

		if($this->isProcessing === true)
		{
			$result = false;
		}

		return $result;
	}

	/**
	 * @param $command
	 * @param $data
	 * @return Result
	 */
	public function processingCommandKeyboard($command, $data): Result
	{
		return new Result();
	}

	/**
	 * @param $chatId
	 * @param $userId
	 * @param string $message
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function sessionClose($chatId, $userId, $message = ''): Result
	{
		$result = new Result();

		if(Loader::includeModule('imopenlines'))
		{
			if(!empty($message))
			{
				Im::addMessage([
					'TO_CHAT_ID' => $chatId,
					'MESSAGE' => $message,
					'SYSTEM' => 'Y',
					'IMPORTANT_CONNECTOR' => 'Y',
					'NO_SESSION_OL' => 'Y',
					'PARAMS' => [
						'CLASS' => 'bx-messenger-content-item-ol-output',
						'IMOL_FORM' => 'offline',
						'TYPE' => 'lines',
						'COMPONENT_ID' => 'bx-imopenlines-message',
					],
				]);
			}

			$chat = new Chat($chatId);
			$resultFinishChat = $chat->finish($userId, true);

			if($resultFinishChat->isSuccess())
			{
				$result->setResult(true);
			}
			else
			{
				$result->addErrors($resultFinishChat->getErrors());
			}
		}
		else
		{
			$result->addError(new Error('Failed to load the open lines module', 'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_MODULES_IMOPENLINES', __METHOD__));
		}

		return $result;
	}

	/**
	 * @param $chatId
	 * @param $userId
	 * @param string $message
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function sessionContinue($chatId, $userId, $message = ''): Result
	{
		$result = new Result();

		if(Loader::includeModule('imopenlines'))
		{
			if(!empty($message))
			{
				Im::addMessage([
					'TO_CHAT_ID' => $chatId,
					'MESSAGE' => $message,
					'SYSTEM' => 'Y',
					'IMPORTANT_CONNECTOR' => 'Y',
					'NO_SESSION_OL' => 'Y',
					'PARAMS' => [
						'CLASS' => 'bx-messenger-content-item-ol-output',
						'IMOL_FORM' => 'offline',
						'TYPE' => 'lines',
						'COMPONENT_ID' => 'bx-imopenlines-message',
					],
				]);

				$result->setResult(true);
			}
		}
		else
		{
			$result->addError(new Error('Failed to load the open lines module', 'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_MODULES_IMOPENLINES', __METHOD__));
		}

		return $result;
	}

	/**
	 * @param $chatId
	 * @param $userId
	 * @param string $message
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function sessionNew($chatId, $userId, $message = ''): Result
	{
		$result = new Result();

		if(Loader::includeModule('imopenlines'))
		{
			$chat = new Chat($chatId);
			$resultSessionNew = $chat->startSessionAndCloseOldSession($userId, $message);

			if($resultSessionNew->isSuccess())
			{
				$result->setResult(true);
			}
			else
			{
				$result->addErrors($resultSessionNew->getErrors());
			}
		}
		else
		{
			$result->addError(new Error('Failed to load the open lines module', 'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_MODULES_IMOPENLINES', __METHOD__));
		}

		return $result;
	}

	/**
	 * @param $params
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function runCommand($params): Result
	{
		$result = new Result();

		if(Loader::includeModule('imopenlines'))
		{
			$configTask = [];

			if(
				!empty($params['COMMAND']) &&
				!empty($params['CHAT_ID']) &&
				!empty($params['SESSION_ID']) &&
				!empty($params['TASK_ID']) &&
				!empty($params['CONFIG_TASK_ID'])
			)
			{
				$rawSession = SessionTable::getList([
					'select' => [
						'STATUS',
						'SOURCE',
						'CONFIG_ID',
						'OPERATOR_ID',
						'USER_ID',
						'CHAT_ID',
						'CLOSED'
					],
					'filter' => [
						'=ID' => $params['SESSION_ID']
					]
				]);

				if($sessionData = $rawSession->fetch())
				{
					if(
						$sessionData['CLOSED'] === 'Y' ||
						$sessionData['STATUS'] >= Session::STATUS_CLOSE
					)
					{
						$result->addError(new Error('You can\'t perform actions in a closed session', 'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_SESSION_CLOSED', __METHOD__, ['command' => $command, 'data' => $data]));
					}
					elseif($sessionData['SOURCE'] !== $this->idConnector)
					{
						$result->addError(new Error('The connector ID in the session does not match the connector ID in the request', 'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_CONNECTORS_DIVERGE', __METHOD__, ['command' => $command, 'data' => $data]));
					}
				}
				else
				{
					$result->addError(new Error('Session failed to load', 'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_CORRECT_DATA', __METHOD__, ['command' => $command, 'data' => $data]));
				}

				if($result->isSuccess())
				{
					$configManager = new Config();

					$automaticMessagesThisConfig = $configManager->getAutomaticMessage($sessionData['CONFIG_ID']);

					foreach ($automaticMessagesThisConfig as $value)
					{
						if($value['ID'] === $params['CONFIG_TASK_ID'])
						{
							$configTask = $value;
						}
					}

					if(empty($configTask))
					{
						$result->addError(new Error('Failed to load automatic message task configuration', 'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_DATA_AUTOMATIC_MESSAGE_TASK', __METHOD__, ['command' => $command, 'data' => $data]));
					}
				}

				if($result->isSuccess())
				{
					switch ($params['COMMAND'])
					{
						case 'sessionClose':
							$resultCommand = $this->sessionClose($params['CHAT_ID'], $sessionData['USER_ID'], $configTask['AUTOMATIC_TEXT_CLOSE']);
							break;

						case 'sessionContinue':
							$resultCommand = $this->sessionContinue($params['CHAT_ID'], $sessionData['USER_ID'], $configTask['AUTOMATIC_TEXT_CONTINUE']);
							break;

						case 'sessionNew':
							$resultCommand = $this->sessionNew($params['CHAT_ID'], $sessionData['USER_ID'], $configTask['AUTOMATIC_TEXT_NEW']);
							break;

						default:
							$resultCommand = new Result();
							$resultCommand->addError(new Error('An unsupported command was passed', 'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_COMMAND_NOT_SUPPORTED', __METHOD__, ['command' => $command, 'data' => $data]));
							break;
					}

					if($resultCommand->isSuccess())
					{
						$result->setResult($resultCommand->getResult());
					}
					else
					{
						$result->addErrors($resultCommand->getErrors());
					}
				}
			}
		}
		else
		{
			$result->addError(new Error('Failed to load the open lines module', 'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_MODULES_IMOPENLINES', __METHOD__));
		}

		return $result;
	}

	/**
	 * @param $activity_id
	 * @return string
	 */
	protected static function getActivityUrl($activity_id): string
	{
		return str_replace('#activity_id#', $activity_id, self::URL_ACTIVITY);
	}
}