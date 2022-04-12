<?php

namespace Bitrix\Voximplant\Tts;

use Bitrix\Main\Localization\Loc;

class Disclaimer
{
	/**
	 * @return string[]
	 */
	public static function getRaw(): array
	{
		return [
			Loc::getMessage('VI_TTS_DISCLAIMER_P1'),
			Loc::getMessage('VI_TTS_DISCLAIMER_P2'),
		];
	}

	public static function getHtml(): string
	{
		$link = static::getPriceListLink();
		$p1 = Loc::getMessage('VI_TTS_DISCLAIMER_P1', [
			'#LINK#' => "<a href=\"{$link}\" target=\"_blank\">{$link}</a>"
		]);
		$p2 = Loc::getMessage('VI_TTS_DISCLAIMER_P2');

		return "{$p1}<br>{$p2}";
	}

	public static function getPriceListLink(): string
	{
		return 'https://voximplant.com/pricing';
	}
}
