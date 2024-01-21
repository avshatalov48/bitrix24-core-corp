<?php

namespace Bitrix\Tasks\Internals\Task\Search\Conversion\Converters;

use Bitrix\Tasks\Internals\Task\Search\Conversion\AbstractConverter;

class CheckListConverter extends AbstractConverter
{
	public function convert(): string
	{
		$titles = array_map(static fn (array $item): string => $item['TITLE'], $this->getFieldValue());
		return implode(' ', $titles);
	}

	public static function getFieldName(): string
	{
		return 'CHECKLIST';
	}
}