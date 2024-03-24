<?php

namespace Bitrix\Tasks\Replication\Template\Repetition\Conversion\Converters;

final class EndDatePlanConverter extends AbstractDateTimeConverter
{
	public function getTemplateFieldName(): string
	{
		return 'END_DATE_PLAN';
	}
}