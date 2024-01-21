<?php

namespace Bitrix\Tasks\Internals\Task\Search\Conversion\Converters;

use Bitrix\Tasks\Internals\Task\Search\Conversion\AbstractConverter;

class TagConverter extends AbstractConverter
{
	public function convert(): string
	{
		$tags = $this->getFieldValue();

		if (!is_array($tags))
		{
			$tags = [$tags];
		}

		return implode(' ', $tags);
	}

	public static function getFieldName(): string
	{
		return 'TAGS';
	}
}