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

	public static function getChatMessageUrl(int $chatId, int $messageId): string
	{
		return "/online/?IM_DIALOG=chat{$chatId}&IM_MESSAGE={$messageId}";
	}

	public static function getCallSliderUrl(int $callId): string
	{
		return "/call/?callId={$callId}";
	}

	public static function getCallAiFeedbackUrl(int $callId): string
	{
		return \Bitrix\Call\Integration\AI\CallAISettings::getFeedBackLink() ?? '';
	}
}


