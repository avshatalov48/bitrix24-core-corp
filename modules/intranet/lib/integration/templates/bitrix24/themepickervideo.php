<?php

namespace Bitrix\Intranet\Integration\Templates\Bitrix24;

use Bitrix\Main\Application;

class ThemePickerVideo
{
	private const DOMAINS = [
		'cis' => 'video.1c-bitrix.ru',
		'en' => 'd2gs98gj961qge.cloudfront.net',
	];

	public function getDomain(): string
	{
		$region = Application::getInstance()->getLicense()->getRegion();
		$isCIS = in_array($region, ['ru', 'by', 'kz']);

		return self::DOMAINS[$isCIS ? 'cis' : 'en'];
	}
}