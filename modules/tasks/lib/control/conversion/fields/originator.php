<?php

namespace Bitrix\Tasks\Control\Conversion\Fields;

class Originator extends NumericField
{
	public const SUB_ENTITY_KEY = 'SE_ORIGINATOR';

	public function getNormalizedKey(): string
	{
		return 'CREATED_BY';
	}

	public function getConvertKey(): string
	{
		return 'ID';
	}
}