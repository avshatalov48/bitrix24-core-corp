<?php

namespace Bitrix\Voximplant\Tts;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Volume
{
	const XSOFT = 'x-soft';
	const SOFT = 'soft';
	const MEDIUM = 'medium';
	const LOUD = 'loud';
	const XLOUD = 'x-loud';

	/**
	 * Returns array of available TTS volumes.
	 * @return array
	 */
	public static function getList()
	{
		return array(
			self::XSOFT => GetMessage('VI_TTS_VOLUME_X_SOFT'),
			self::SOFT => GetMessage('VI_TTS_VOLUME_SOFT'),
			self::MEDIUM => GetMessage('VI_TTS_VOLUME_MEDIUM'),
			self::LOUD => GetMessage('VI_TTS_VOLUME_LOUD'),
			self::XLOUD => GetMessage('VI_TTS_VOLUME_X_LOUD'),
		);
	}

	/**
	 * Returns default TTS volume.
	 * @return string
	 */
	public static function getDefault()
	{
		return self::MEDIUM;
	}
}
