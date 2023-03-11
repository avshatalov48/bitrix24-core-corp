<?php
namespace Bitrix\ImConnector\InteractiveMessage;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

use Bitrix\Im\Model\ChatTable;

use Bitrix\ImOpenLines;
use Bitrix\ImOpenLines\Im;
use Bitrix\ImOpenLines\Chat;
use Bitrix\ImOpenLines\Tools;
use Bitrix\ImOpenLines\Queue;
use Bitrix\ImOpenLines\Config;
use Bitrix\ImOpenLines\Session;
use Bitrix\ImOpenLines\AutomaticAction;
use Bitrix\ImOpenLines\Model\SessionTable;

use Bitrix\ImConnector;
use Bitrix\ImConnector\Error;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Connector;

/**
 * Class Base
 * @package Bitrix\ImConnector\InteractiveMessage
 */
class Input
{
	public const
		COMMAND_SESSION = 'session',
		COMMAND_SESSION_CLOSE = 'sessionClose',
		COMMAND_SESSION_CONTINUE = 'sessionContinue',
		COMMAND_SESSION_NEW = 'sessionNew';

	protected $message;
	protected $isProcessing = false;
	protected $idConnector = '';

	public const URL_ACTIVITY = '/crm/activity/?open_view=#activity_id#';

	/**
	 * @param string $idConnector
	 * @return self
	 */
	public static function init($idConnector = ''): self
	{
		$class = __CLASS__;

		if (
			!empty($idConnector) &&
			Connector::isConnector($idConnector)
		)
		{
			$idConnector = Connector::getConnectorRealId($idConnector);
			$className = "Bitrix\\ImConnector\\InteractiveMessage\\Connectors\\" . $idConnector . "\\Input";
			if (class_exists($className))
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

		if ($this->isProcessing === true)
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
	 */
	protected function sessionClose($chatId, $userId, string $message = ''): Result
	{
		$result = new Result();

		if (Loader::includeModule('imopenlines'))
		{
			if (!empty($message))
			{
				/** @var \Bitrix\ImOpenLines\Services\Message $messenger */
				$messenger = ServiceLocator::getInstance()->get('ImOpenLines.Services.Message');
				$messenger->addMessage([
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

			/** @var \Bitrix\ImOpenLines\Services\ChatDispatcher $chatDispatcher */
			$chatDispatcher = ServiceLocator::getInstance()->get('ImOpenLines.Services.ChatDispatcher');
			$chat = $chatDispatcher->getChat((int)$chatId);

			$resultFinishChat = $chat->finish($userId, true, true, true);

			if ($resultFinishChat->isSuccess())
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
	 */
	protected function sessionContinue($chatId, $userId, $message = ''): Result
	{
		$result = new Result();

		if (Loader::includeModule('imopenlines'))
		{
			if (!empty($message))
			{
				/** @var \Bitrix\ImOpenLines\Services\ChatDispatcher $chatDispatcher */
				$chatDispatcher = ServiceLocator::getInstance()->get('ImOpenLines.Services.ChatDispatcher');
				$chat = $chatDispatcher->getChat((int)$chatId);

				$resultChatOperation = $chat->continueSession($userId, $message);

				if ($resultChatOperation->isSuccess())
				{
					$result->setResult(true);
				}
				else
				{
					$result->addErrors($resultChatOperation->getErrors());
				}

			}
		}
		else
		{
			$result->addError(new Error(
				'Failed to load the open lines module',
				'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_MODULES_IMOPENLINES',
				__METHOD__
			));
		}

		return $result;
	}

	/**
	 * @param $chatId
	 * @param $userId
	 * @param string $message
	 * @return Result
	 */
	protected function sessionNew($chatId, $userId, $message = ''): Result
	{
		$result = new Result();

		if (Loader::includeModule('imopenlines'))
		{
			/** @var \Bitrix\ImOpenLines\Services\ChatDispatcher $chatDispatcher */
			$chatDispatcher = ServiceLocator::getInstance()->get('ImOpenLines.Services.ChatDispatcher');
			$chat = $chatDispatcher->getChat((int)$chatId);

			$resultSessionNew = $chat->restartSession($userId, $message);

			if ($resultSessionNew->isSuccess())
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
			$result->addError(new Error(
				'Failed to load the open lines module',
				'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_MODULES_IMOPENLINES',
				__METHOD__
			));
		}

		return $result;
	}

	/**
	 * @param $params
	 * @return Result
	 */
	protected function runCommand($params): Result
	{
		$result = new Result();

		if (!Loader::includeModule('imopenlines'))
		{
			return $result->addError(new Error(
				'Failed to load the open lines module',
				'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_MODULES_IMOPENLINES',
				__METHOD__
			));
		}

		if (
			!empty($params['COMMAND'])
			&&
			(
				$params['COMMAND'] === self::COMMAND_SESSION_NEW
				|| $params['COMMAND'] === self::COMMAND_SESSION_CLOSE
				|| $params['COMMAND'] === self::COMMAND_SESSION_CONTINUE
			)
			&& !empty($params['CHAT_ID'])
			&& !empty($params['SESSION_ID'])
			&& !empty($params['TASK_ID'])
			&& !empty($params['CONFIG_TASK_ID'])
		)
		{
			$configTask = [];

			$querySession = ImConnector\Data\Session::getInstance()->query();
			$rawSession = $querySession
				->setSelect([
					'STATUS',
					'SOURCE',
					'CONFIG_ID',
					'OPERATOR_ID',
					'USER_ID',
					'CHAT_ID',
					'CLOSED'
				])
				->setFilter([
					'=ID' => $params['SESSION_ID']
				])
				->exec()
			;
			if ($sessionData = $rawSession->fetch())
			{
				if (
					$sessionData['CLOSED'] === 'Y' ||
					$sessionData['STATUS'] >= Session::STATUS_CLOSE
				)
				{
					$result->addError(new Error(
						'You can\'t perform actions in a closed session',
						'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_SESSION_CLOSED',
						__METHOD__,
						['command' => $params['COMMAND'], 'data' => $params]
					));
				}
				elseif ($sessionData['SOURCE'] !== $this->idConnector)
				{
					$result->addError(new Error(
						'The connector ID in the session does not match the connector ID in the request',
						'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_CONNECTORS_DIVERGE',
						__METHOD__,
						['command' => $params['COMMAND'], 'data' => $params]
					));
				}
			}
			else
			{
				$result->addError(new Error(
					'Session failed to load',
					'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_CORRECT_DATA',
					__METHOD__,
					['command' => $params['COMMAND'], 'data' => $params]
				));
			}

			if ($result->isSuccess())
			{
				/** @var \Bitrix\ImOpenLines\Config $configManager */
				$configManager = ServiceLocator::getInstance()->get('ImOpenLines.Config');

				$automaticMessagesThisConfig = $configManager->getAutomaticMessage($sessionData['CONFIG_ID']);

				foreach ($automaticMessagesThisConfig as $value)
				{
					if ($value['ID'] === $params['CONFIG_TASK_ID'])
					{
						$configTask = $value;
					}
				}

				if (empty($configTask))
				{
					$result->addError(new Error(
						'Failed to load automatic message task configuration',
						'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_DATA_AUTOMATIC_MESSAGE_TASK',
						__METHOD__,
						['command' => $params['COMMAND'], 'data' => $params]
					));
				}
			}

			if ($result->isSuccess())
			{
				switch ($params['COMMAND'])
				{
					case self::COMMAND_SESSION_CLOSE:
						$resultCommand = $this->sessionClose(
							$params['CHAT_ID'],
							$sessionData['USER_ID'],
							$configTask['AUTOMATIC_TEXT_CLOSE'] ?? ''
						);
						break;

					case self::COMMAND_SESSION_CONTINUE:
						$resultCommand = $this->sessionContinue(
							$params['CHAT_ID'],
							$sessionData['USER_ID'],
							$configTask['AUTOMATIC_TEXT_CONTINUE'] ?? ''
						);
						break;

					case self::COMMAND_SESSION_NEW:
						$resultCommand = $this->sessionNew(
							$params['CHAT_ID'],
							$sessionData['USER_ID'],
							$configTask['AUTOMATIC_TEXT_NEW'] ?? ''
						);
						break;

					default:
						$resultCommand = new Result();
						$resultCommand->addError(new Error(
							'An unsupported command was passed',
							'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_COMMAND_NOT_SUPPORTED',
							__METHOD__,
							['command' => $params['COMMAND'], 'data' => $params]
						));
						break;
				}

				if ($resultCommand->isSuccess())
				{
					$result->setResult($resultCommand->getResult());
				}
				else
				{
					$result->addErrors($resultCommand->getErrors());
				}
			}
			else
			{
				$result->addError(new Error(
					'Invalid data was transmitted',
					'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_CORRECT_DATA',
 					__METHOD__,
					['command' => $params['COMMAND'], 'data' => $params]
				));
			}
		}
		else
		{
			$result->addError(new Error(
				'Invalid data was transmitted',
				'IMCONNECTOR_INTERACTIVE_MESSAGE_ERROR_NOT_LOAD_CORRECT_DATA',
				__METHOD__,
				['command' => $params['COMMAND'], 'data' => $params]
			));
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