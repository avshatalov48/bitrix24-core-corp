<?php
namespace Bitrix\Tasks\Replica;

class Bind
{
	/** @var \Bitrix\Tasks\Replica\TaskHandler */
	protected static $taskHandler = null;

	/**
	 * Initializes replication process on tasks side.
	 *
	 * @return void
	 */
	public function start()
	{
		self::$taskHandler = new TaskHandler();
		\Bitrix\Replica\Client\HandlersManager::register(self::$taskHandler);
		\Bitrix\Replica\Client\HandlersManager::register(new TaskMemberHandler);
		\Bitrix\Replica\Client\HandlersManager::register(new TaskTagHandler);
		\Bitrix\Replica\Client\HandlersManager::register(new TaskLogHandler);
		\Bitrix\Replica\Client\HandlersManager::register(new TaskElapsedTimeHandler);
		\Bitrix\Replica\Client\HandlersManager::register(new TaskViewedHandler);
		\Bitrix\Replica\Client\HandlersManager::register(new TaskReminderHandler);
		\Bitrix\Replica\Client\HandlersManager::register(new TaskChecklistItemHandler);
		\Bitrix\Replica\Client\HandlersManager::register(new TaskRatingVoteHandler);

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->addEventHandler("tasks", "OnTaskAdd", array(self::$taskHandler, "onTaskAdd"));
		$eventManager->addEventHandler("tasks", "OnBeforeTaskUpdate", array(self::$taskHandler, "onBeforeTaskUpdate"));
		$eventManager->addEventHandler("tasks", "OnTaskUpdate", array(self::$taskHandler, "onTaskUpdate"));
		$eventManager->addEventHandler("tasks", "OnTaskDelete", array(self::$taskHandler, "onTaskDelete"));
		$eventManager->addEventHandler("tasks", "OnBeforeTaskZombieDelete", array(self::$taskHandler, "onBeforeTaskZombieDelete"));
		$eventManager->addEventHandler("tasks", "OnTaskZombieDelete", array(self::$taskHandler, "onTaskZombieDelete"));
	}
}
