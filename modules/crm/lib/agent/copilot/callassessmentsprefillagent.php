<?php

namespace Bitrix\Crm\Agent\Copilot;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Copilot\CallAssessment\FillPreliminaryCallAssessments;

final class CallAssessmentsPrefillAgent extends AgentBase
{
	public const AGENT_DONE_STOP_IT = false;

	public static function doRun(): bool
	{
		(new FillPreliminaryCallAssessments())->execute();

		return self::AGENT_DONE_STOP_IT;
	}
}
