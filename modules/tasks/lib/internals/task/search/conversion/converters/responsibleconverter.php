<?php

namespace Bitrix\Tasks\Internals\Task\Search\Conversion\Converters;

use Bitrix\Tasks\Internals\Task\Search\Conversion\AbstractConverter;

class ResponsibleConverter extends AbstractConverter
{
	public function convert(): string
	{
		return $this->getUserNames();
	}

	public static function getFieldName(): string
	{
		return 'RESPONSIBLE_ID';
	}
}