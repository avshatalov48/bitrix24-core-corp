<?php

namespace Bitrix\Voximplant\Asr;

use Bitrix\Main\Localization\Loc;

class Provider {
	const GOOGLE = 'google';
	const YANDEX = 'yandex';
	const TINKOFF = 'tinkoff';


	/**
	 * Returns array of available ASR providers
	 * @return array ('ID' => Language label)
	 */
	public static function getList()
	{
		static $result = null;
		if(!is_array($result))
		{
			$result = array();
			$reflection = new \ReflectionClass(__CLASS__);
			$constants = $reflection->getConstants();
			foreach ($constants as $constantName)
			{
				$result[$constantName] = Loc::getMessage("VOX_ASR_PROVIDER_".mb_strtoupper($constantName));
			}
		}
		return $result;
	}

	public static function getDefault(string $transcribeLanguage)
	{
		return $transcribeLanguage == Language::RUSSIAN_RU ? static::YANDEX : static::GOOGLE;
	}
}