<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Marketing;

use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Internals\Marketing\Exception\SaveEventException;
use Bitrix\Tasks\Internals\Marketing\Exception\UnknownEventException;
use Bitrix\Tasks\Util\Type\DateTime;

class EventManager
{
	private $userId;
	/* @var $event EventInterface */
	private $event;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @param string $eventClass
	 * @param null $params
	 * @throws UnknownEventException
	 */
	public function add(string $eventClass, $params = null)
	{
		$this->loadEventObject($eventClass, $params);

		if ($this->event->validate())
		{
			$this->save();
		}
	}

	/**
	 * @param string $eventClass
	 * @param null $params
	 * @throws UnknownEventException
	 */
	public function drop(string $eventClass, $params = null)
	{
		$this->loadEventObject($eventClass, $params);
		MarketingTable::deleteList([
			'=USER_ID' => $this->userId,
			'=EVENT' => $this->event->getClass(),
			'=DATE_EXECUTED' => 0,
		]);
	}

	/***
	 * @param string $eventClass
	 * @param null $params
	 * @return bool
	 * @throws UnknownEventException
	 */
	public function execute(string $eventClass, $params = null): bool
	{
		$this->loadEventObject($eventClass, $params);
		return $this->event->execute();
	}

	/**
	 * @throws \Exception
	 */
	protected function save(): void
	{
		$data = [
			'USER_ID' => $this->userId,
			'EVENT' => $this->event->getClass(),
			'DATE_CREATED' => DateTime::getCurrentTimestamp(),
			'DATE_SHEDULED' => $this->event->getDateSheduled(),
			'DATE_EXECUTED' => 0,
		];

		$params = $this->event->getParams();
		if ($params)
		{
			$data['PARAMS'] = Json::encode($params);
		}

		$res = MarketingTable::add($data);
		if (!$res->isSuccess())
		{
			throw new SaveEventException('Unable to save event '.$this->event->getClass());
		}
	}

	/**
	 * @param string $eventClass
	 * @param null $params
	 * @return string
	 * @throws UnknownEventException
	 */
	protected function loadEventObject(string $eventClass, $params = null): string
	{
		if (!$eventClass || !class_exists($eventClass))
		{
			throw new UnknownEventException('Unknown event '. $eventClass);
		}

		if (!is_a($eventClass, EventInterface::class, true))
		{
			throw new UnknownEventException('Event '. $eventClass.' should be an instance of EventInterface.');
		}

		$this->event = (new $eventClass($this->userId, $params));

		return $eventClass;
	}
}