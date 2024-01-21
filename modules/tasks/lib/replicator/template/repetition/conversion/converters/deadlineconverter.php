<?php

namespace Bitrix\Tasks\Replicator\Template\Repetition\Conversion\Converters;

final class DeadlineConverter extends AbstractDateTimeConverter
{
	public function getTemplateFieldName(): string
	{
		return 'DEADLINE_AFTER';
	}
}