<?php

namespace Bitrix\Tasks\Flow\Responsible\Agent\RemoveUserFromFlowResponsible;

use Bitrix\Tasks\Flow\Migration\Exclusion\Agent\ExclusionFromFlowAgent;
use Bitrix\Tasks\Update\AgentInterface;

/**
 * @see ExclusionFromFlowAgent
 * @deprecated since tasks 25.100.0
 * todo removed in tasks 25.200.0
 */
final class RemoveUserFromFlowResponsible implements AgentInterface
{
	public static function execute(): string
	{
		$userId = (int)(func_get_args()[0] ?? null);

		if ($userId <= 0)
		{
			return '';
		}

		return self::convertToExclusionAgent($userId);
	}

	/**
	 * @see ExclusionFromFlowAgent
	 */
	private static function convertToExclusionAgent(int $deletedUserId): string
	{
		return ExclusionFromFlowAgent::getAgentName("U{$deletedUserId}");
	}
}