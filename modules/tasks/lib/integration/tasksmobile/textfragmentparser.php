<?php

namespace Bitrix\Tasks\Integration\TasksMobile;

use Bitrix\Tasks\Integration\TasksMobile;

class TextFragmentParser extends TasksMobile
{
	public static function getTextFragmentParserClass(): ?string
	{
		if (static::includeModule())
		{
			return \Bitrix\TasksMobile\TextFragmentParser::class;
		}

		return null;
	}
}