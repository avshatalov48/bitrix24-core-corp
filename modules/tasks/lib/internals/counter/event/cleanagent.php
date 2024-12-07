<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Event;


use Bitrix\Main\Config\Option;
use Bitrix\Tasks\Update\AgentInterface;
use Bitrix\Tasks\Update\AgentTrait;
use Bitrix\Tasks\Util\Type\DateTime;

class CleanAgent implements AgentInterface
{
	use AgentTrait;

	private const TTL = 21*24*3600;

	private static $processing = false;

	public static function execute(): string
	{
		if (self::$processing)
		{
			return static::getAgentName();
		}

		$deleteIds = self::getOutdatedEvents();
		if (!empty($deleteIds))
		{
			EventTable::deleteList(['ID' => $deleteIds]);
		}

		return static::getAgentName();
	}

	private static function getOutdatedEvents(): array
	{
		$deleteIds = [];
		$query = EventTable::query()
			->addSelect('ID')
			->where('PROCESSED', '>', DateTime::createFromTimestamp(0))
			->where('PROCESSED', '<', DateTime::createFromTimestamp(time() - self::TTL))
			->setLimit(self::getLimit())
			->exec()
		;

		while ($row = $query->fetch())
		{
			$deleteIds[] = $row['ID'];
		}

		return $deleteIds;
	}

	private static function getLimit(): int
	{
		return (int)Option::get('tasks', 'CleanAgentLimit', 1000);
	}
}