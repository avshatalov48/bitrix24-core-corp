<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Event;


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

		$filter = [
			'>=PROCESSED' => DateTime::createFromTimestamp(0),
			'<PROCESSED' => DateTime::createFromTimestamp(time() - self::TTL)
		];
		EventTable::deleteList($filter);


		return static::getAgentName();
	}
}