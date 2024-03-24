<?php

namespace Bitrix\Tasks\Control\Conversion\Fields;

class Tag extends ArrayField
{
	public const SUB_ENTITY_KEY = 'SE_TAG';

	public function getNormalizedKey(): string
	{
		return 'TAGS';
	}

	public function getConvertKey(): string
	{
		return 'NAME';
	}
}