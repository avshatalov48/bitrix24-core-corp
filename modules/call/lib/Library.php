<?php

namespace Bitrix\Call;


class Library
{
	protected const SELF_TEST_UTL = [
		'ru' => 'https://calltest.bitrix24.ru/',
		'en' => 'https://calltest.bitrix24.com/',
	];

	public static function getClientSelfTestUrl(string $region = 'en'): string
	{
		$url = match (\Bitrix\Main\Application::getInstance()->getLicense()->getRegion() ?: $region)
		{
			'ru','by','kz' => self::SELF_TEST_UTL['ru'],
			default => self::SELF_TEST_UTL['en'],
		};
		$url .= '?hl='. \Bitrix\Main\Localization\Loc::getCurrentLang();

		return $url;
	}
}


