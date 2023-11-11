<?php

namespace Bitrix\Tasks\Replicator\Template\Conversion\Converters;

final class EndDatePlanConverter extends DateTimeConverter
{
	public function getTemplateFieldName(): string
	{
		return 'END_DATE_PLAN';
	}
}