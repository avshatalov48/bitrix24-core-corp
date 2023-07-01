<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Agent\Activity\LightCounter\FillLightCounterPart1;
use Bitrix\Crm\Agent\Activity\LightCounter\FillLightCounterPart2;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

/**
 * This agent fill the light counter date field in some db tables. It works in the two parts the part 2 have to run
 * after the part 1 is done.
 */
final class FillLightCounterTimeAgent extends AgentBase
{
	private const SCHEDULE_NEXT_AGENT_RUN = true;

	private const AGENT_WORK_IS_DONE_STOP_IT = false;

	public static function doRun(): bool
	{
		return self::processBatch();
	}

	private static function processBatch(): bool
	{
		$part1NotDone = (new FillLightCounterPart1())->execute();
		if ($part1NotDone)
		{
			return self::SCHEDULE_NEXT_AGENT_RUN;
		}

		$part2NotDone = (new FillLightCounterPart2())->execute();
		if ($part2NotDone)
		{
			return self::SCHEDULE_NEXT_AGENT_RUN;
		}

		FillLightCounterPart1::cleanOptions();
		FillLightCounterPart2::cleanOptions();

		self::removeFlagIndicatingThatCountersAreNotReady();

		self::resetCounters();

		return self::AGENT_WORK_IS_DONE_STOP_IT;
	}

	private static function removeFlagIndicatingThatCountersAreNotReady()
	{
		Option::delete('crm', ['name' => 'enable_act_counter_light']);
	}

	private static function resetCounters()
	{
		global $CACHE_MANAGER;
		Application::getConnection()
			->query("UPDATE b_user_counter set CNT=-1 WHERE CODE LIKE 'crm%' AND NOT CODE LIKE 'CRM\_**' and CNT > -1");
		$CACHE_MANAGER->CleanDir("user_counter");
	}
}
