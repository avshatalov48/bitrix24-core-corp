<?php

namespace Bitrix\Voximplant\Tts;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Language
{
	const AU_ENGLISH_FEMALE = 'auenglishfemale';
	const BR_PORTUGUESE_FEMALE = 'brportuguesefemale';
	const CA_ENGLISH_FEMALE = 'caenglishfemale';
	const CA_FRENCH_FEMALE = 'cafrenchfemale';
	const CA_FRENCH_MALE = 'cafrenchmale';
	const CH_CHINESE_FEMALE = 'chchinesefemale';
	const CH_CHINESE_MALE = 'chchinesemale';
	const EUR_CATALAN_FEMALE = 'eurcatalanfemale';
	const EUR_CZECH_FEMALE = 'eurczechfemale';
	const EUR_DANISH_FEMALE = 'eurdanishfemale';
	const EUR_DUTCH_FEMALE = 'eurdutchfemale';
	const EUR_FINNISH_FEMALE = 'eurfinnishfemale';
	const EUR_FRENCH_FEMALE = 'eurfrenchfemale';
	const EUR_FRENCH_MALE = 'eurfrenchmale';
	const EUR_GERMAN_FEMALE = 'eurgermanfemale';
	const EUR_GERMAN_MALE = 'eurgermanmale';
	const EUR_ITALIAN_FEMALE = 'euritalianfemale';
	const EUR_ITALIAN_MALE = 'euritalianmale';
	const EUR_NORWEGIAN_FEMALE = 'eurnorwegianfemale';
	const EUR_POLISH_FEMALE = 'eurpolishfemale';
	const EUR_PORTUGUESE_FEMALE = 'eurportuguesefemale';
	const EUR_PORTUGUESE_MALE = 'eurportuguesemale';
	const EUR_SPANISH_FEMALE = 'eurspanishfemale';
	const EUR_SPANISH_MALE = 'eurspanishmale';
	const EUR_TURKISH_FEMALE = 'eurturkishfemale';
	const EUR_TURKISH_MALE = 'eurturkishmale';
	const HK_CHINESE_FEMALE = 'hkchinesefemale';
	const HU_HUNGARIAN_FEMALE = 'huhungarianfemale';
	const JP_JAPANESE_FEMALE = 'jpjapanesefemale';
	const JP_JAPANESE_MALE = 'jpjapanesemale';
	const KR_KOREAN_FEMALE = 'krkoreanfemale';
	const KR_KOREAN_MALE = 'krkoreanmale';
	const RU_RUSSIAN_FEMALE = 'ruinternalfemale';
	const RU_RUSSIAN_MALE = 'ruinternalmale';
	const SW_SWEDISH_FEMALE = 'swswedishfemale';
	const TW_CHINESE_FEMALE = 'twchinesefemale';
	const UK_ENGLISH_FEMALE = 'ukenglishfemale';
	const UK_ENGLISH_MALE = 'ukenglishmale';
	const US_ENGLISH_FEMALE = 'usenglishfemale';
	const US_ENGLISH_MALE = 'usenglishmale';
	const US_SPANISH_FEMALE = 'usspanishfemale';
	const US_SPANISH_MALE = 'usspanishmale';

	/**
	 * Returns array of available TTS voices
	 * @return array
	 */
	public static function getList()
	{
		return array(
			self::AU_ENGLISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_AU_ENGLISH_FEMALE'),
			self::BR_PORTUGUESE_FEMALE => Loc::getMessage('VI_TTS_VOICE_BR_PORTUGUESE_FEMALE'),
			self::CA_ENGLISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_CA_ENGLISH_FEMALE'),
			self::CA_FRENCH_FEMALE => Loc::getMessage('VI_TTS_VOICE_CA_FRENCH_FEMALE'),
			self::CA_FRENCH_MALE => Loc::getMessage('VI_TTS_VOICE_CA_FRENCH_MALE'),
			self::CH_CHINESE_FEMALE => Loc::getMessage('VI_TTS_VOICE_CH_CHINESE_FEMALE'),
			self::CH_CHINESE_MALE => Loc::getMessage('VI_TTS_VOICE_CH_CHINESE_MALE'),
			self::EUR_CATALAN_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_CATALAN_FEMALE'),
			self::EUR_CZECH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_CZECH_FEMALE'),
			self::EUR_DANISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_DANISH_FEMALE'),
			self::EUR_DUTCH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_DUTCH_FEMALE'),
			self::EUR_FINNISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_FINNISH_FEMALE'),
			self::EUR_FRENCH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_FRENCH_FEMALE'),
			self::EUR_FRENCH_MALE => Loc::getMessage('VI_TTS_VOICE_EUR_FRENCH_MALE'),
			self::EUR_GERMAN_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_GERMAN_FEMALE'),
			self::EUR_GERMAN_MALE => Loc::getMessage('VI_TTS_VOICE_EUR_GERMAN_MALE'),
			self::EUR_ITALIAN_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_ITALIAN_FEMALE'),
			self::EUR_ITALIAN_MALE => Loc::getMessage('VI_TTS_VOICE_EUR_ITALIAN_MALE'),
			self::EUR_NORWEGIAN_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_NORWEGIAN_FEMALE'),
			self::EUR_POLISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_POLISH_FEMALE'),
			self::EUR_PORTUGUESE_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_PORTUGUESE_FEMALE'),
			self::EUR_PORTUGUESE_MALE => Loc::getMessage('VI_TTS_VOICE_EUR_PORTUGUESE_MALE'),
			self::EUR_SPANISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_SPANISH_FEMALE'),
			self::EUR_SPANISH_MALE => Loc::getMessage('VI_TTS_VOICE_EUR_SPANISH_MALE'),
			self::EUR_TURKISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_TURKISH_FEMALE'),
			self::EUR_TURKISH_MALE => Loc::getMessage('VI_TTS_VOICE_EUR_TURKISH_MALE'),
			self::HK_CHINESE_FEMALE => Loc::getMessage('VI_TTS_VOICE_HK_CHINESE_FEMALE'),
			self::HU_HUNGARIAN_FEMALE => Loc::getMessage('VI_TTS_VOICE_HU_HUNGARIAN_FEMALE'),
			self::JP_JAPANESE_FEMALE => Loc::getMessage('VI_TTS_VOICE_JP_JAPANESE_FEMALE'),
			self::JP_JAPANESE_MALE => Loc::getMessage('VI_TTS_VOICE_JP_JAPANESE_MALE'),
			self::KR_KOREAN_FEMALE => Loc::getMessage('VI_TTS_VOICE_KR_KOREAN_FEMALE'),
			self::KR_KOREAN_MALE => Loc::getMessage('VI_TTS_VOICE_KR_KOREAN_MALE'),
			self::RU_RUSSIAN_FEMALE => Loc::getMessage('VI_TTS_VOICE_RU_RUSSIAN_FEMALE'),
			self::RU_RUSSIAN_MALE => Loc::getMessage('VI_TTS_VOICE_RU_RUSSIAN_MALE'),
			self::SW_SWEDISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_SW_SWEDISH_FEMALE'),
			self::TW_CHINESE_FEMALE => Loc::getMessage('VI_TTS_VOICE_TW_CHINESE_FEMALE'),
			self::UK_ENGLISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_UK_ENGLISH_FEMALE'),
			self::UK_ENGLISH_MALE => Loc::getMessage('VI_TTS_VOICE_UK_ENGLISH_MALE'),
			self::US_ENGLISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_US_ENGLISH_FEMALE'),
			self::US_ENGLISH_MALE => Loc::getMessage('VI_TTS_VOICE_US_ENGLISH_MALE'),
			self::US_SPANISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_US_SPANISH_FEMALE'),
			self::US_SPANISH_MALE => Loc::getMessage('VI_TTS_VOICE_US_SPANISH_MALE'),
		);
	}

	/**
	 * Returns default TTS voice for language id
	 * @param string $lang ID of the language.
	 * @return string
	 */
	public static function getDefaultVoice($lang = 'ru')
	{
		if ($lang == 'ru')
		{
			$voice = self::RU_RUSSIAN_FEMALE;
		}
		else if ($lang == 'de')
		{
			$voice = self::EUR_GERMAN_FEMALE;
		}
		else
		{
			$voice = self::US_ENGLISH_MALE;
		}

		return $voice;
	}
}
