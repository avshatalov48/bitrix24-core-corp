<?php

namespace Bitrix\Tasks\Replicator\Template\Conversion\Converters;

use Bitrix\Tasks\Replicator\Template\Conversion\Converter;
use Bitrix\Tasks\Replicator\Template\Repository;

final class DescriptionBBCodeConverter implements Converter
{
	public function convert(Repository $repository): array
	{
		$taskFields['DESCRIPTION_IN_BBCODE'] = 'Y';

		return $taskFields;
	}

	public function getTemplateFieldName(): string
	{
		return 'DESCRIPTION_IN_BBCODE';
	}
}