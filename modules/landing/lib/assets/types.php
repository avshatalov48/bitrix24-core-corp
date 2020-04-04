<?php

namespace Bitrix\Landing\Assets;

class Types
{
	const KEY_RELATIVE = 'rel';
	const TYPE_CSS = 'css';
	const TYPE_JS = 'js';
	const TYPE_LANG = 'lang';
	const TYPE_LANG_ADDITIONAL = 'lang_additional';
	const TYPE_FONT = 'font';
	
	/**
	 * Asset may use include.php, but we can overwriting them, or add unique extensions
	 *
	 * @return array
	 */
	public static function getAssetTypes()
	{
		return [
			self::KEY_RELATIVE,
			self::TYPE_LANG,
			self::TYPE_JS,
			self::TYPE_CSS,
			self::TYPE_FONT,
		];
	}
}