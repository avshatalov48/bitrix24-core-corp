<?php
namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Counter\Event\Event;
use Bitrix\Tasks\Internals\Counter\Event\EventCollection;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;
use Bitrix\Tasks\Internals\Counter\Event\EventResourceCollection;

/**
 * Class CounterService
 *
 * @package Bitrix\Tasks\Internals\Counter
 */
class CounterService
{
	private const LOCK_KEY = 'tasks.countlock';

	private static $instance;
	private static $jobOn = false;

	private static $hitId;

	/**
	 * CounterService constructor.
	 */
	private function __construct()
	{
		self::$hitId = $this->generateHid();
		$this->enableJob();
		$this->handleLostEvents();
	}

	/**
	 * @return CounterService
	 */
	public static function getInstance(): CounterService
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param string $type
	 * @param array $data
	 */
	public static function addEvent(string $type, array $data): void
	{
		self::getInstance()->storeEvent($type, $data);
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function proceedEvents(): void
	{
		$events = (EventCollection::getInstance())->list();
		if (empty($events))
		{
			Application::getConnection()->unlock(self::LOCK_KEY);
			return;
		}

		$service = self::getInstance();
		$service->collectModifiedData();

		(new Counter\Event\UserEventProcessor())->process();
		(new Counter\Event\ProjectEventProcessor())->process();
		//(new Counter\Event\GarbageCollector())->process();

		$service->done();
	}

	/**
	 * @param int $taskId
	 */
	public function collectData(int $taskId): void
	{
		$this->getResourceCollection()->collectOrigin($taskId);
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function handleLostEvents(): void
	{
		if (!Application::getConnection()->lock(self::LOCK_KEY))
		{
			return;
		}

		$events = Counter\Event\EventTable::getLostEvents();
		if (empty($events))
		{
			return;
		}

		foreach ($events as $row)
		{
			$event = new Event(
				$row['HID'],
				$row['TYPE']
			);
			$event
				->setId($row['ID'])
				->setData(Main\Web\Json::decode($row['DATA']));
			$this->getEventCollection()->push($event);

			$taskData = !empty($row['TASK_DATA']) ? Main\Web\Json::decode($row['TASK_DATA']) : null;
			if ($taskData && array_key_exists('ID', $taskData))
			{
				$this->getResourceCollection()->collectOrigin((int)$taskData['ID'], $taskData);
			}
		}
	}

	/**
	 * @param string $type
	 * @param array $data
	 */
	private function storeEvent(string $type, array $data): void
	{
		$event = new Event(self::$hitId, $type);
		$event->setData($data);

		$eventId = $this->saveToDb($event);
		$event->setId($eventId);

		$this->getEventCollection()->push($event);
	}

	/**
	 *
	 */
	private function enableJob(): void
	{
		if (self::$jobOn)
		{
			return;
		}

		$application = Application::getInstance();
		$application && $application->addBackgroundJob(
			['\Bitrix\Tasks\Internals\Counter\CounterService', 'proceedEvents'],
			[],
			Application::JOB_PRIORITY_LOW - 2
		);

		self::$jobOn = true;
	}

	/**
	 * @param string $type
	 * @param array $data
	 * @return int
	 */
	private function saveToDb(Event $event): int
	{
		try
		{
			$originData = $this->getResourceCollection()->getOrigin();

			$taskId = $event->getTaskId();
			$taskData = null;
			if ($taskId && array_key_exists($taskId, $originData))
			{
				$taskData = $originData[$taskId];
			}

			$res = Counter\Event\EventTable::add([
				'HID' => self::$hitId,
				'TYPE' => $event->getType(),
				'DATA' => Main\Web\Json::encode($event->getData()),
				'TASK_DATA' => $taskData ? Main\Web\Json::encode($taskData->toArray()) : null,
			]);
		}
		catch (\Exception $e)
		{
			return 0;
		}

		return (int)$res->getId();
	}

	/**
	 *
	 */
	private function done(): void
	{
		$ids = $this->getEventCollection()->getEventsId();
		if (empty($ids))
		{
			return;
		}

		Counter\Event\EventTable::markProcessed([
			'@ID' => $ids
		]);

		Application::getConnection()->unlock(self::LOCK_KEY);
	}

	/**
	 * @param int $taskId
	 */
	private function collectModifiedData(): void
	{
		$events = EventCollection::getInstance()->list();
		foreach ($events as $event)
		{
			$taskId = $event->getTaskId();
			$eventType = $event->getType();
			if (in_array($eventType, [
				EventDictionary::EVENT_AFTER_TASK_DELETE,
				EventDictionary::EVENT_AFTER_COMMENTS_READ_ALL,
				EventDictionary::EVENT_AFTER_PROJECT_READ_ALL,
				EventDictionary::EVENT_AFTER_SCRUM_READ_ALL,
				EventDictionary::EVENT_PROJECT_DELETE,
				EventDictionary::EVENT_PROJECT_PERM_UPDATE,
				EventDictionary::EVENT_PROJECT_USER_ADD,
				EventDictionary::EVENT_PROJECT_USER_DELETE,
				EventDictionary::EVENT_PROJECT_USER_UPDATE
			]))
			{
				continue;
			}

			$this->getResourceCollection()->collectModified($taskId);
		}
	}

	/**
	 * @return EventResourceCollection
	 */
	private function getResourceCollection(): EventResourceCollection
	{
		return EventResourceCollection::getInstance();
	}

	/**
	 * @return EventCollection
	 */
	private function getEventCollection(): EventCollection
	{
		return EventCollection::getInstance();
	}

	/**
	 * @return string
	 */
	private function generateHid(): string
	{
		return sha1(microtime(true) . mt_rand(10000, 99999));
	}
}