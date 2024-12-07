<?php

namespace Bitrix\Crm\Integration\BiConnector;


use Bitrix\Main\Localization\Loc;

class Localization
{
	public static function getMessage(string $code, string $languageId, ?array $replace = null): ?string
	{
		$messages = Loc::loadLanguageFile(__FILE__, $languageId);

		$message = $messages[$code] ?? null;
		if (is_string($message) && !empty($replace))
		{
			$message = strtr($message, $replace);
		}

		return $message;
	}
}
