<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Event;


use Bitrix\Tasks\Util\Type\DateTime;

class CleanAgent
{
	private const TTL = 21*24*3600;

	private static $processing = false;

	public static function execute()
	{
		if (self::$processing)
		{
			return self::getAgentName();
		}

		$filter = [
			'>PROCESSED' => DateTime::createFromTimestampGmt(0),
			'<PROCESSED' => DateTime::createFromTimestampGmt(time() - self::TTL)
		];
		EventTable::deleteList($filter);


		return self::getAgentName();
	}

	/**
	 * @return string
	 */
	private static function getAgentName(): string
	{
		return static::class . "::execute();";
	}
}