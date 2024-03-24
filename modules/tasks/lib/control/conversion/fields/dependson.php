<?php

namespace Bitrix\Tasks\Control\Conversion\Fields;

class DependsOn extends ArrayField
{
	public const SUB_ENTITY_KEY = 'SE_RELATEDTASK';

	public function getNormalizedKey(): string
	{
		return 'DEPENDS_ON';
	}

	public function getConvertKey(): string
	{
		return 'ID';
	}
}