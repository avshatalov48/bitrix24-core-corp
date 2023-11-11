<?php

namespace Bitrix\Tasks\Replicator\Template\Conversion\Converters;

final class DeadlineConverter extends DateTimeConverter
{
	public function getTemplateFieldName(): string
	{
		return 'DEADLINE_AFTER';
	}
}