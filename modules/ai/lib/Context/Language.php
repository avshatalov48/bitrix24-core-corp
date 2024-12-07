<?php

namespace Bitrix\AI\Context;

use Bitrix\AI\Facade;
use Bitrix\Main\Localization\Loc;

/**
 * Context may have special language.
 */
class Language
{
	private const LANG_PHRASE_PREFIX = 'AI_CONTEXT_LANGUAGE_';
	private const AVAILABLE_LANG_CODES = [
		'af',
		'sq',
		'ar',
		'hy',
		'az',
		'eu',
		'be',
		'bn',
		'bs',
		'bg',
		'tc',
		'sc',
		'ca',
		'hr',
		'cs',
		'da',
		'nl',
		'en',
		'et',
		'fil',
		'fi',
		'fr',
		'gl',
		'de',
		'el',
		'gu',
		'he',
		'hi',
		'hu',
		'is',
		'id',
		'it',
		'ja',
		'kn',
		'kk',
		'ko',
		'lv',
		'lt',
		'mk',
		'ms',
		'mi',
		'mr',
		'ne',
		'no',
		'nno',
		'fa',
		'pl',
		'br',
		'pan',
		'ro',
		'ru',
		'sr',
		'sk',
		'sl',
		'la',
		'sw',
		'sv',
		'ta',
		'te',
		'th',
		'tr',
		'ua',
		'ur',
		'vn',
		'cy',
	];

	private string $code;

	/**
	 * @param string|null $langCode
	 *
	 * Get all available context languages with names (in current B24 localization)
	 * * @return array - code => name
	 */
	public static function getAvailable(?string $langCode = null): array
	{
		static $availableLangs = [];
		if (!empty($availableLangs[$langCode]))
		{
			return $availableLangs[$langCode];
		}

		foreach (self::AVAILABLE_LANG_CODES as $code)
		{
			$langPhraseCode = self::LANG_PHRASE_PREFIX . mb_strtoupper($code);
			$name = Loc::getMessage($langPhraseCode, null, $langCode);
			if ($name)
			{
				$availableLangs[$langCode][$code] = $name;
			}
		}

		// sort
		$first = [];
		$currentUserLang = self::getDefaultCode();
		if ($availableLangs[$langCode][$currentUserLang])
		{
			$first[$currentUserLang] = $availableLangs[$langCode][$currentUserLang];
			unset($availableLangs[$langCode][$currentUserLang]);
		}
		asort($availableLangs[$langCode]);
		$availableLangs[$langCode] = array_merge($first, $availableLangs[$langCode]);

		return $availableLangs[$langCode];
	}

	/**
	 * @param string $code - one of available codes. If incorrect code - will be used current B24 localization
	 */
	public function __construct(string $code)
	{
		$this->code =
			in_array($code, self::AVAILABLE_LANG_CODES)
				? $code
				: self::getDefaultCode()
		;
	}

	/**
	 * Get default code from current B24 localization
	 * @return string
	 */
	private static function getDefaultCode(): string
	{
		return Facade\User::getUserLanguage();
	}

	/**
	 * Return code of language.
	 * Most codes from ISO 639-1 standart, some from ISO 639-2, and some - Bitrix localization standart
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->code;
	}

	/**
	 * @param string|null $langCode
	 *
	 * Return language name in current B24 localization
	 * * @return string
	 */
	public function getName(?string $langCode = null): string
	{
		return self::getAvailable($langCode)[$this->code];
	}
}