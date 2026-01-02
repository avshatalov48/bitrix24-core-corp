<?php

namespace Bitrix\Crm\Agent\RepeatSale;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\RepeatSale\Segment\FillPreliminarySegments;

final class PrefillAgent extends AgentBase
{
	public const AGENT_DONE_STOP_IT = false;

	public static function doRun(): bool
	{
		(new FillPreliminarySegments())->execute();

		return self::AGENT_DONE_STOP_IT;
	}
}
