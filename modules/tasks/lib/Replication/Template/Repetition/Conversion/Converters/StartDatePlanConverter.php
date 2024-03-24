<?php

namespace Bitrix\Tasks\Replication\Template\Repetition\Conversion\Converters;

final class StartDatePlanConverter extends AbstractDateTimeConverter
{
	public function getTemplateFieldName(): string
	{
		return 'START_DATE_PLAN_AFTER';
	}
}