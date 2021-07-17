<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Event;

class EventCollection
{
	private static $instance;

	private $registry = [];
	private $ids = [];

	/**
	 * CounterService constructor.
	 */
	private function __construct()
	{

	}

	/**
	 * @return self
	 */
	public static function getInstance(): self
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return array
	 */
	public function list(): array
	{
		return $this->registry;
	}

	/**
	 * @param Event $event
	 */
	public function push(Event $event): void
	{
		$this->registry[] = $event;
		$this->ids[] = $event->getId();
	}

	/**
	 * @param string $eventType
	 * @return array
	 */
	public function getTasksByEventType(string $eventType): array
	{
		$res = [];
		foreach ($this->registry as $event)
		{
			if ($event->getType() === $eventType)
			{
				$res[] = $event->getTaskId();
			}
		}
		return $res;
	}

	/**
	 * @return array
	 */
	public function getEventsId(): array
	{
		return $this->ids;
	}

}