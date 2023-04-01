<?php
namespace Bitrix\ImOpenLines\AutomaticAction;

use Bitrix\Main\Loader,
	Bitrix\Main\Type\DateTime;
use Bitrix\Pull;
use	Bitrix\ImOpenLines\Chat,
	Bitrix\ImOpenLines\Error,
	Bitrix\ImOpenLines\Tools,
	Bitrix\ImOpenLines\Result,
	Bitrix\ImOpenLines\Config,
	Bitrix\ImOpenLines\Session,
	Bitrix\ImOpenLines\Model\SessionTable,
	Bitrix\ImOpenLines\Model\SessionAutomaticTasksTable;

/**
 * Class Messages
 * @package Bitrix\ImOpenLines\AutomaticAction
 */
class Messages
{
	protected const ENABLE_CONNECTOR = [
		'livechat',
		'network'
	];

	/** @var Session */
	protected $sessionManager = null;
	protected $session = [];
	//protected $config = [];
	/**Chat*/
	//protected $chat = null;

	/**
	 * Messages constructor.
	 * @param Session $session
	 */
	public function __construct($session)
	{
		$this->sessionManager = $session;
		$this->session = $session->getData();
		//$this->config = $session->getConfig();
		//$this->chat = $session->getChat();
	}

	/**
	 * @return bool
	 */
	public static function isActualMessagesForSend(): bool
	{
		$result = false;

		$count = SessionAutomaticTasksTable::getCount(['<=DATE_TASK' => new DateTime()]);

		if(
			!empty($count) &&
			$count > 0
		)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param int $limitTime
	 * @param int $limit
	 */
	public static function sendMessages($limitTime = 60, $limit = 0): void
	{
		$time = new Tools\Time;

		$configs = [];
		$configsAutomaticMessage = [];
		$chats = [];
		$configManager = new Config();

		$count = 0;
		$countIterationPull = 0;
		while (
			($time->getElapsedTime() <= $limitTime)
			&& (
				empty($limit)
				|| ($count < $limit)
			)
		)
		{
			if ($countIterationPull > 10 && Loader::includeModule('pull'))
			{
				$countIterationPull = 0;

				Pull\Event::send();
			}

			$select = SessionTable::getSelectFieldsPerformance('SESSION');

			$select['ID'] = 'ID';
			$select['CONFIG_AUTOMATIC_MESSAGE_ID'] = 'CONFIG_AUTOMATIC_MESSAGE_ID';

			$tasks = SessionAutomaticTasksTable::getList([
				'select' => $select,
				'filter' => [
					'<=DATE_TASK' => new DateTime()
				],
				'order' => [
					'DATE_TASK'
				],
				'limit' => 1
			]);

			if ($task = $tasks->fetch())
			{
				SessionAutomaticTasksTable::update($task['ID'], ['DATE_TASK' => (new DateTime())->add('120 SECONDS')]);

				$sessionFields = [];
				foreach ($task as $key => $value)
				{
					$newKey = str_replace('IMOPENLINES_MODEL_SESSION_AUTOMATIC_TASKS_SESSION_', '', $key);

					if ($newKey !== $key)
					{
						$sessionFields[$newKey] = $value;
					}
				}

				if (!isset($configs[$sessionFields['CONFIG_ID']]))
				{
					$configs[$sessionFields['CONFIG_ID']] = $configManager->get($sessionFields['CONFIG_ID']);

					if (empty($configsAutomaticMessage[$task['CONFIG_AUTOMATIC_MESSAGE_ID']]))
					{
						$automaticMessagesThisConfig = $configManager->getAutomaticMessage($sessionFields['CONFIG_ID']);

						foreach ($automaticMessagesThisConfig as $automaticMessage)
						{
							$configsAutomaticMessage[$automaticMessage['ID']] = $automaticMessage;
						}
					}
				}

				if (!isset($chats[$sessionFields['CHAT_ID']]))
				{
					$chats[$sessionFields['CHAT_ID']] = new Chat($sessionFields['CHAT_ID']);
				}

				$session = new Session();
				$session->loadByArray($sessionFields, $configs[$sessionFields['CONFIG_ID']], $chats[$sessionFields['CHAT_ID']]);
				$resultSendAutomaticMessage = $session->sendAutomaticMessage($task['ID'], $task['CONFIG_AUTOMATIC_MESSAGE_ID'], $configsAutomaticMessage[$task['CONFIG_AUTOMATIC_MESSAGE_ID']]);

				if ($resultSendAutomaticMessage->isSuccess())
				{
					$countIterationPull++;
				}
				$count++;
			}
			else
			{
				break;
			}
		}

		if (
			$countIterationPull > 0 &&
			Loader::includeModule('pull')
		)
		{
			Pull\Event::send();
		}
	}

	/**
	 * @param $sessionId
	 * @return Result
	 */
	public static function deleteAllTaskThisSession($sessionId): Result
	{
		$result = new Result();

		$tasks = SessionAutomaticTasksTable::getList([
			'select' => ['ID'],
			'filter' => ['=SESSION_ID' => $sessionId],
			'order' => ['ID']
		]);

		if($task = $tasks->fetch())
		{
			$resultDelete = SessionAutomaticTasksTable::delete($task['ID']);

			if(!$resultDelete->isSuccess())
			{
				$errors = $resultDelete->getErrors();
				foreach ($errors as $error)
				{
					$result->addError(new Error($error->getMessage(), $error->getCode(), __METHOD__, ['field' => $error->getField()]));
				}
			}
		}

		return $result;
	}

	/**
	 * @param $sessionId
	 * @param $configId
	 * @return Result
	 */
	public static function addTaskThisSession($sessionId, $configId): Result
	{
		$result = new Result();

		$configManager = new Config();
		$automaticMessagesThisConfig = $configManager->getAutomaticMessage($configId);

		foreach ($automaticMessagesThisConfig as $automaticMessage)
		{
			$resultAdd = SessionAutomaticTasksTable::add(
				[
					'CONFIG_AUTOMATIC_MESSAGE_ID' => $automaticMessage['ID'],
					'SESSION_ID' => $sessionId,
					'DATE_TASK' => (new DateTime())->add($automaticMessage['TIME_TASK'] . ' SECONDS')
				]
			);

			if(!$resultAdd->isSuccess())
			{
				$errors = $resultAdd->getErrors();
				foreach ($errors as $error)
				{
					$result->addError(new Error($error->getMessage(), $error->getCode(), __METHOD__, ['field' => $error->getField()]));
				}
			}
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	public function setStatusResponseOperator(): Result
	{
		$result = new Result();
		if($this->isEnableAutomaticMessagesThisSession())
		{
			$resultDelete = self::deleteAllTaskThisSession($this->session['ID']);
			if(!$resultDelete->isSuccess())
			{
				$result->addErrors($resultDelete->getErrors());
			}

			$resultAdd = self::addTaskThisSession($this->session['ID'], $this->session['CONFIG_ID']);

			if(!$resultAdd->isSuccess())
			{
				$errors = $resultAdd->getErrors();
				foreach ($errors as $error)
				{
					$result->addError(new Error($error->getMessage(), $error->getCode(), __METHOD__));
				}
			}
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	public function setStatusNotResponseOperator(): Result
	{
		$result = new Result();

		if($this->isEnableAutomaticMessagesThisSession())
		{
			$result = self::deleteAllTaskThisSession($this->session['ID']);
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	protected function isEnableAutomaticMessagesThisSession(): bool
	{
		$result = false;

		if(
			$this->sessionManager->isEnableSendSystemMessage() &&
			in_array($this->session['SOURCE'],self::ENABLE_CONNECTOR, false)
		)
		{
			$result = true;
		}

		return $result;
	}
}