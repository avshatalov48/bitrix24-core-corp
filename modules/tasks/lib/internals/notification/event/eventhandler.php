<?php
namespace Bitrix\Tasks\Internals\Notification\Event;

use Bitrix\Main\Application;

class EventHandler
{
	private static $instance;
	private static $isJobIsOn = false;

	private $registry;

	private function __construct()
	{

	}

	public static function getInstance(): EventHandler
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function addEvent(string $type, array $data): void
	{
		$event = new Event($type, $data);

		self::getInstance()->registerEvent($event);
		self::getInstance()->addBackgroundJob();
	}

	private function registerEvent(Event $event): void
	{
		$this->registry[] = $event;
	}

	private function addBackgroundJob(): void
	{
		if (self::$isJobIsOn)
		{
			return;
		}

		$application = Application::getInstance();
		$application && $application->addBackgroundJob([__CLASS__, 'process'], [], (Application::JOB_PRIORITY_LOW - 3));

		self::$isJobIsOn = true;
	}

	public static function process(): void
	{
		self::getInstance()->handleEvents();
	}

	private function handleEvents(): void
	{
		/** @var Event $event */
		foreach ($this->registry as $event)
		{
			if ($event->getType() === 'message')
			{
				$eventData = $event->getData();
				$eventData['PARAMETERS']['IS_ON_BACKGROUND_JOB'] = 'N';

				\CTaskNotifications::sendMessageEx(
					$eventData['TASK_ID'],
					$eventData['FROM_USER'],
					$eventData['TO_USERS'],
					$eventData['MESSAGES'],
					$eventData['PARAMETERS']
				);
			}
		}
	}
}