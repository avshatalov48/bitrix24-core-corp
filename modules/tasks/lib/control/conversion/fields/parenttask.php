<?php

namespace Bitrix\Tasks\Control\Conversion\Fields;

class ParentTask extends NumericField
{
	public const SUB_ENTITY_KEY = 'SE_PARENTTASK';

	public function getNormalizedKey(): string
	{
		return 'PARENT_ID';
	}

	public function getConvertKey(): string
	{
		return 'ID';
	}
}