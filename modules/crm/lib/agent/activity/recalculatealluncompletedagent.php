<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Agent\Activity\Uncompleted\UncompletedRecalculation;
use Bitrix\Crm\Agent\AgentBase;

/**
 * Full recalculate entity_uncompleted_activity table.
 * @see UncompletedRecalculation
 */
class RecalculateAllUncompletedAgent extends AgentBase
{
	private const AGENT_DONE_STOP_IT = false;

	private const AGENT_CONTINUE = true;

	public static function doRun()
	{
		$agent = new UncompletedRecalculation();
		$isContinue = $agent->execute();

		if ($isContinue)
		{
			return self::AGENT_CONTINUE;
		}

		UncompletedRecalculation::cleanOptions();

		return self::AGENT_DONE_STOP_IT;
	}
}