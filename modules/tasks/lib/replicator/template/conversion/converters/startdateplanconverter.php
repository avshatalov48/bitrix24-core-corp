<?php

namespace Bitrix\Tasks\Replicator\Template\Conversion\Converters;

final class StartDatePlanConverter extends DateTimeConverter
{
	public function getTemplateFieldName(): string
	{
		return 'START_DATE_PLAN_AFTER';
	}
}