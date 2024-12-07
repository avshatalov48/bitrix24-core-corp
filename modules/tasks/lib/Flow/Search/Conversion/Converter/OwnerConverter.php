<?php

namespace Bitrix\Tasks\Flow\Search\Conversion\Converter;

use Bitrix\Tasks\Flow\Search\Conversion\AbstractConverter;

class OwnerConverter extends AbstractConverter
{
	public function convert(): string
	{
		return $this->getUserNames();
	}

	public static function getFieldName(): string
	{
		return 'ownerId';
	}
}