<?php

namespace Bitrix\Voximplant\Tts;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Speed
{
	const XSLOW = 'x-slow';
	const SLOW = 'slow';
	const MEDIUM = 'medium';
	const FAST = 'fast';
	const XFAST = 'x-fast';

	/**
	 * Returns array of available TTS speeds
	 * @return array
	 */
	public static function getList()
	{
		return array(
			self::XSLOW => GetMessage('VI_TTS_SPEED_X_SLOW'),
			self::SLOW => GetMessage('VI_TTS_SPEED_SLOW'),
			self::MEDIUM => GetMessage('VI_TTS_SPEED_MEDIUM'),
			self::FAST => GetMessage('VI_TTS_SPEED_FAST'),
			self::XFAST => GetMessage('VI_TTS_SPEED_X_FAST'),
		);
	}

	/**
	 * Returns default TTS speed
	 * @return string
	 */
	public static function getDefault()
	{
		return self::MEDIUM;
	}
}
