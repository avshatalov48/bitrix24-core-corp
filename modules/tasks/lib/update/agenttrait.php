<?php

namespace Bitrix\Tasks\Update;

use CAgent;

trait AgentTrait
{
	public static string $method = 'execute()';
	public static int $interval = 60;

	public static function getAgentName(bool $withSlash = true): string
	{
		$prefix = $withSlash ? '\\' : '';
		return $prefix . static::class . '::' . static::$method . ';';
	}

	public static function removeAgent(string $moduleId = 'tasks'): void
	{
		CAgent::RemoveAgent(static::getAgentName(), $moduleId);
		// backward compatibility
		CAgent::RemoveAgent(static::getAgentName(false), $moduleId);
	}

	public static function addAgent(): void
	{
		$name = static::getAgentName();

		$agent = CAgent::GetList(arFilter: ['MODULE_ID' => 'tasks', 'NAME' => $name])->fetch();
		if(isset($agent['ID']))
		{
			return;
		}

		CAgent::AddAgent(
			$name,
			'tasks',
			'N', // don't care about how many times agent rises
			self::$interval
		);
	}
}