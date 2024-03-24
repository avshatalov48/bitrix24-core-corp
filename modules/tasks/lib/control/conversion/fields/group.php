<?php

namespace Bitrix\Tasks\Control\Conversion\Fields;

class Group extends NumericField
{
	public const SUB_ENTITY_KEY = 'SE_PROJECT';

	public function getNormalizedKey(): string
	{
		return 'GROUP_ID';
	}

	public function getConvertKey(): string
	{
		return 'ID';
	}
}