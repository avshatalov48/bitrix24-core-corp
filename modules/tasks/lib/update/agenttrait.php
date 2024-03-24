<?php

namespace Bitrix\Tasks\Update;

use CAgent;

trait AgentTrait
{
	public static string $method = 'execute()';
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
}