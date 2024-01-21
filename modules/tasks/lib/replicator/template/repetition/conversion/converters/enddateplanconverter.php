<?php

namespace Bitrix\Tasks\Replicator\Template\Repetition\Conversion\Converters;

final class EndDatePlanConverter extends AbstractDateTimeConverter
{
	public function getTemplateFieldName(): string
	{
		return 'END_DATE_PLAN';
	}
}