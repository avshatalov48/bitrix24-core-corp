<?php

namespace Bitrix\Disk\Document;

class Language
{
	public static function getIso639Code(string $internalLang): ?string
	{
		$mapISO639 = [
			'ru' => 'ru',
			'en' => 'en',
			'de' => 'de',
			'ua' => 'uk',
			'la' => 'es',
			'br' => 'pt',
			'fr' => 'fr',
			'sc' => 'zh-CN',
			'tc' => 'tc',
			'pl' => 'pl',
			'it' => 'it',
			'tr' => 'tr',
			'ja' => 'ja',
			'vn' => 'vi',
			'id' => 'id',
			'ms' => 'ms',
			'th' => 'th',
			'hi' => 'hi',
		];

		return $mapISO639[$internalLang] ?? null;
	}
}