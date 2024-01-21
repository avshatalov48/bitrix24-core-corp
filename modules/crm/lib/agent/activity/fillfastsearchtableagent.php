<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Agent\Activity\FastSearch\ActivityFastsearchFiller;
use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Main\Config\Option;

class FillFastSearchTableAgent extends AgentBase
{
	private const AGENT_DONE_STOP_IT = false;

	private const AGENT_CONTINUE = true;

	public static function doRun(): bool
	{
		$continue = (new ActivityFastsearchFiller())->execute();

		if ($continue)
		{
			return self::AGENT_CONTINUE;
		}

		ActivityFastsearchFiller::cleanOptions();

		self::clearActivityFastSearchFillerInProgress();

		return self::AGENT_DONE_STOP_IT;
	}

	private static function clearActivityFastSearchFillerInProgress(): void
	{
		Option::delete('crm', ['name' => 'enable_act_fastsearch_filter']);
	}
}