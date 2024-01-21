<?php

namespace Bitrix\Tasks\Internals\Task\Search\Conversion\Converters;

use Bitrix\Tasks\Internals\Task\Search\Conversion\AbstractConverter;

class DescriptionConverter extends AbstractConverter
{
	public static function getFieldName(): string
	{
		return 'DESCRIPTION';
	}
}