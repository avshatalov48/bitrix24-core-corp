<?php
namespace Bitrix\Timeman\Service\Worktime\Violation;

class FlexTimeScheduleViolationBuilder extends WorktimeViolationBuilder
{
	protected function skipViolationsCheck()
	{
		return true;
	}
}