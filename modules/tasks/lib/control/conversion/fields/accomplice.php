<?php

namespace Bitrix\Tasks\Control\Conversion\Fields;

class Accomplice extends ArrayField
{
	public const SUB_ENTITY_KEY = 'SE_ACCOMPLICE';

	public static function getSubEntityKey(): string
	{
		return 'SE_ACCOMPLICE';
	}

	public function getNormalizedKey(): string
	{
		return 'ACCOMPLICES';
	}

	public function getConvertKey(): string
	{
		return 'ID';
	}
}