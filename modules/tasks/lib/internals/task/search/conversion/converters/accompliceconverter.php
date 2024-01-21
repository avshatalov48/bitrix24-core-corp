<?php

namespace Bitrix\Tasks\Internals\Task\Search\Conversion\Converters;

use Bitrix\Tasks\Internals\Task\Search\Conversion\AbstractConverter;

class AccompliceConverter extends AbstractConverter
{
	public function convert(): string
	{
		return $this->getUserNames();
	}

	public static function getFieldName(): string
	{
		return 'ACCOMPLICES';
	}
}