<?php

namespace Bitrix\Tasks\Replicator\Template\Repetition\Conversion\Converters;

use Bitrix\Tasks\Replicator\Template\Repetition\Conversion\ConverterInterface;
use Bitrix\Tasks\Replicator\Template\RepositoryInterface;

final class DescriptionBBCodeConverter implements ConverterInterface
{
	public function convert(RepositoryInterface $repository): array
	{
		$taskFields['DESCRIPTION_IN_BBCODE'] = 'Y';

		return $taskFields;
	}

	public function getTemplateFieldName(): string
	{
		return 'DESCRIPTION_IN_BBCODE';
	}
}