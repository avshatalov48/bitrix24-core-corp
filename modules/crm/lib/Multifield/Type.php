<?php

namespace Bitrix\Crm\Multifield;

/**
 * @internal Do not extend this class, it's still in active development phase.
 * This class is not covered by backwards compatibility
 */
abstract class Type
{
	public const ID = 'UNDEFINED';

	final public static function getCaption(): string
	{
		$caption = (string)\CCrmFieldMulti::GetEntityTypeCaption(static::ID);
		if ($caption === static::ID)
		{
			// caption not found
			return '';
		}

		return $caption;
	}
}
