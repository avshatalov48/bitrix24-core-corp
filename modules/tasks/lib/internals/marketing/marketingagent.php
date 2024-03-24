<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Marketing;

use Bitrix\Tasks\Update\AgentInterface;
use Bitrix\Tasks\Update\AgentTrait;

class MarketingAgent implements AgentInterface
{
	use AgentTrait;

	private static $processing = false;

	public static function execute(): string
	{
		if (self::$processing)
		{
			return static::getAgentName();
		}

		self::$processing = true;

		(new EventProcessor())->proceed();

		self::$processing = false;

		return static::getAgentName();
	}
}