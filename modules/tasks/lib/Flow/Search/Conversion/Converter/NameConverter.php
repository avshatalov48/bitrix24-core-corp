<?php

namespace Bitrix\Tasks\Flow\Search\Conversion\Converter;

use Bitrix\Tasks\Flow\Search\Conversion\AbstractConverter;

class NameConverter extends AbstractConverter
{
	public static function getFieldName(): string
	{
		return 'name';
	}
}