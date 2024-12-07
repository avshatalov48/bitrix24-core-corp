<?php

namespace Bitrix\Tasks\Flow\Search\Conversion\Converter;

use Bitrix\Tasks\Flow\Search\Conversion\AbstractConverter;

class CreatorConverter extends AbstractConverter
{
	public function convert()
	{
		return $this->getUserNames();
	}

	public static function getFieldName(): string
	{
		return 'creatorId';
	}
}