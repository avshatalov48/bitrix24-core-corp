<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Marketing;

class MarketingAgent
{
	private static $processing = false;

	public static function execute()
	{
		if (self::$processing)
		{
			return self::getAgentName();
		}

		self::$processing = true;

		(new EventProcessor())->proceed();

		self::$processing = false;

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