<?php

namespace Bitrix\Tasks\Control\Conversion\Fields;

class Auditor extends ArrayField
{
	public const SUB_ENTITY_KEY = 'SE_AUDITOR';

	public function getNormalizedKey(): string
	{
		return 'AUDITORS';
	}

	public function getConvertKey(): string
	{
		return 'ID';
	}
}