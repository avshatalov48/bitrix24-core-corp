<?php

namespace Bitrix\Tasks\Integration\CRM\Timeline\Event;

use Bitrix\Tasks\Integration\CRM\Timeline\BackGroundJob;

class EventsController
{
	private static ?EventsController $instance = null;
	/** @var TimeLineEvent[] */
	private static array $events = [];

	/**
	 * @return EventsController
	 */
	public static function getInstance(): EventsController
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param TimeLineEvent $event
	 * @return void
	 */
	public function addEvent(TimeLineEvent $event): void
	{
		$hash = md5($event->getEndpoint() . ':' . json_encode($event->getPayload()));
		self::$events[$hash] = $event;
	}

	/**
	 * @param BackGroundJob $repository
	 * @return void
	 */
	public function pushEvents(BackGroundJob $repository): void
	{
		foreach (self::$events as $key => $event)
		{
			$repository->addToBackgroundJobs(
				$event->getPayload(),
				$event->getEndpoint(),
				$event->getPriority(),
			);
			unset(self::$events[$key]);
		}
	}

	private function __construct() {}

	private function __clone() {}

	public function __wakeup()
	{
		throw new \Exception('Cannot unserialize singleton');
	}
}