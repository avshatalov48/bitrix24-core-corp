<?php

namespace Bitrix\Voximplant\Tts;

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Language
{
	const AU_ENGLISH_FEMALE = 'auenglishfemale';  		// VoiceList.Amazon.en_AU_Nicole,
	const BR_PORTUGUESE_FEMALE = 'brportuguesefemale';	// VoiceList.Amazon.pt_BR_Vitoria,
	const CA_ENGLISH_FEMALE = 'caenglishfemale';		// VoiceList.Legacy.Ispeech.en_CA_FEMALE
	const CA_FRENCH_FEMALE = 'cafrenchfemale';			// VoiceList.Amazon.fr_CA_Chantal,
	const CA_FRENCH_MALE = 'cafrenchmale';				// VoiceList.Amazon.fr_CA_Chantal
	const CH_CHINESE_FEMALE = 'chchinesefemale';		// VoiceList.Default.cmn_CN_Female
	const CH_CHINESE_MALE = 'chchinesemale';			// VoiceList.Legacy.Ispeech.cmn_Hans_CN_MALE
	const EUR_CATALAN_FEMALE = 'eurcatalanfemale';		// VoiceList.Legacy.Ispeech.ca_CA_FEMALE
	const EUR_CZECH_FEMALE = 'eurczechfemale';			// VoiceList.Legacy.Ispeech.cs_CZ_FEMALE
	const EUR_DANISH_FEMALE = 'eurdanishfemale';		// VoiceList.Default.da_DK_Female
	const EUR_DUTCH_FEMALE = 'eurdutchfemale';			// VoiceList.Default.nl_NL_Female
	const EUR_FINNISH_FEMALE = 'eurfinnishfemale';		// VoiceList.Legacy.Ispeech.fi_FI_FEMALE
	const EUR_FRENCH_FEMALE = 'eurfrenchfemale';		// VoiceList.Amazon.fr_FR_Celine
	const EUR_FRENCH_MALE = 'eurfrenchmale';			// VoiceList.Amazon.fr_FR_Mathieu
	const EUR_GERMAN_FEMALE = 'eurgermanfemale';		// VoiceList.Default.de_DE_Female
	const EUR_GERMAN_MALE = 'eurgermanmale';			// VoiceList.Default.de_DE_Male
	const EUR_ITALIAN_FEMALE = 'euritalianfemale';		// VoiceList.Default.it_IT_Female
	const EUR_ITALIAN_MALE = 'euritalianmale';			// VoiceList.Default.it_IT_Male
	const EUR_NORWEGIAN_FEMALE = 'eurnorwegianfemale';	// VoiceList.Default.nb_NO_Female
	const EUR_POLISH_FEMALE = 'eurpolishfemale';		// VoiceList.Default.pl_PL_Female
	const EUR_PORTUGUESE_FEMALE = 'eurportuguesefemale';// VoiceList.Default.pt_PT_Female
	const EUR_PORTUGUESE_MALE = 'eurportuguesemale';	// VoiceList.Default.pt_PT_Male
	const EUR_SPANISH_FEMALE = 'eurspanishfemale';		// VoiceList.Default.es_ES_Female
	const EUR_SPANISH_MALE = 'eurspanishmale';			// VoiceList.Default.es_ES_Male
	const EUR_TURKISH_FEMALE = 'eurturkishfemale';		// VoiceList.Default.tr_TR_Female
	const EUR_TURKISH_MALE = 'eurturkishmale';			// VoiceList.Default.tr_TR_Female
	const HK_CHINESE_FEMALE = 'hkchinesefemale';		// VoiceList.Legacy.Ispeech.cmn_Hans_HK_FEMALE
	const HU_HUNGARIAN_FEMALE = 'huhungarianfemale';	// VoiceList.Legacy.Ispeech.hu_HU_FEMALE
	const JP_JAPANESE_FEMALE = 'jpjapanesefemale';		// VoiceList.Default.ja_JP_Female
	const JP_JAPANESE_MALE = 'jpjapanesemale';			// VoiceList.Default.ja_JP_Male
	const KR_KOREAN_FEMALE = 'krkoreanfemale';			// VoiceList.Default.ko_KR_Female
	const KR_KOREAN_MALE = 'krkoreanmale';				// VoiceList.Legacy.Ispeech.ko_KR_MALE
	const RU_RUSSIAN_FEMALE = 'ruinternalfemale';		// VoiceList.Default.ru_RU_Female
	const RU_RUSSIAN_MALE = 'ruinternalmale';			// VoiceList.Default.ru_RU_Male
	const SW_SWEDISH_FEMALE = 'swswedishfemale'; 		// VoiceList.Default.sv_SE_Female
	const TW_CHINESE_FEMALE = 'twchinesefemale';		// VoiceList.Legacy.Ispeech.cmn_Hant_TW_FEMALE
	const UK_ENGLISH_FEMALE = 'ukenglishfemale';		// VoiceList.Amazon.en_GB_Amy
	const UK_ENGLISH_MALE = 'ukenglishmale';			// VoiceList.Amazon.en_GB_Brian
	const US_ENGLISH_FEMALE = 'usenglishfemale';		// VoiceList.Default.en_US_Female
	const US_ENGLISH_MALE = 'usenglishmale';			// VoiceList.Default.en_US_Male
	const US_SPANISH_FEMALE = 'usspanishfemale';		// VoiceList.Amazon.es_US_Penelope
	const US_SPANISH_MALE = 'usspanishmale';			// VoiceList.Amazon.es_US_Miguel

	const AMAZON_VOICES = [
		self::AU_ENGLISH_FEMALE => true,
		self::BR_PORTUGUESE_FEMALE=> true,
		self::CA_FRENCH_FEMALE=> true,
		self::CA_FRENCH_MALE=> true,
		self::EUR_FRENCH_FEMALE=> true,
		self::EUR_FRENCH_MALE=> true,
		self::UK_ENGLISH_FEMALE=> true,
		self::UK_ENGLISH_MALE=> true,
		self::US_SPANISH_FEMALE=> true,
		self::US_SPANISH_MALE=> true,
	];

	const PROVIDER_AMAZON = 'Amazon';
	const PROVIDER_DEFAULT = 'Default';

	/**
	 * Returns array of available TTS voices
	 * @return array
	 */
	public static function getList()
	{
		return array(
			self::AU_ENGLISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_AU_ENGLISH_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_AMAZON
			]),
			self::BR_PORTUGUESE_FEMALE => Loc::getMessage('VI_TTS_VOICE_BR_PORTUGUESE_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_AMAZON
			]),
			self::CA_ENGLISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_CA_ENGLISH_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::CA_FRENCH_FEMALE => Loc::getMessage('VI_TTS_VOICE_CA_FRENCH_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_AMAZON
			]),
			self::CA_FRENCH_MALE => Loc::getMessage('VI_TTS_VOICE_CA_FRENCH_MALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_AMAZON
			]),
			self::CH_CHINESE_FEMALE => Loc::getMessage('VI_TTS_VOICE_CH_CHINESE_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::CH_CHINESE_MALE => Loc::getMessage('VI_TTS_VOICE_CH_CHINESE_MALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_CATALAN_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_CATALAN_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_CZECH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_CZECH_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_DANISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_DANISH_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_DUTCH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_DUTCH_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_FINNISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_FINNISH_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_FRENCH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_FRENCH_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_AMAZON
			]),
			self::EUR_FRENCH_MALE => Loc::getMessage('VI_TTS_VOICE_EUR_FRENCH_MALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_AMAZON
			]),
			self::EUR_GERMAN_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_GERMAN_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_GERMAN_MALE => Loc::getMessage('VI_TTS_VOICE_EUR_GERMAN_MALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_ITALIAN_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_ITALIAN_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_ITALIAN_MALE => Loc::getMessage('VI_TTS_VOICE_EUR_ITALIAN_MALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_NORWEGIAN_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_NORWEGIAN_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_POLISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_POLISH_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_PORTUGUESE_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_PORTUGUESE_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_PORTUGUESE_MALE => Loc::getMessage('VI_TTS_VOICE_EUR_PORTUGUESE_MALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_SPANISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_SPANISH_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_SPANISH_MALE => Loc::getMessage('VI_TTS_VOICE_EUR_SPANISH_MALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_TURKISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_EUR_TURKISH_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::EUR_TURKISH_MALE => Loc::getMessage('VI_TTS_VOICE_EUR_TURKISH_MALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::HK_CHINESE_FEMALE => Loc::getMessage('VI_TTS_VOICE_HK_CHINESE_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::HU_HUNGARIAN_FEMALE => Loc::getMessage('VI_TTS_VOICE_HU_HUNGARIAN_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::JP_JAPANESE_FEMALE => Loc::getMessage('VI_TTS_VOICE_JP_JAPANESE_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::JP_JAPANESE_MALE => Loc::getMessage('VI_TTS_VOICE_JP_JAPANESE_MALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::KR_KOREAN_FEMALE => Loc::getMessage('VI_TTS_VOICE_KR_KOREAN_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::KR_KOREAN_MALE => Loc::getMessage('VI_TTS_VOICE_KR_KOREAN_MALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::RU_RUSSIAN_FEMALE => Loc::getMessage('VI_TTS_VOICE_RU_RUSSIAN_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::RU_RUSSIAN_MALE => Loc::getMessage('VI_TTS_VOICE_RU_RUSSIAN_MALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::SW_SWEDISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_SW_SWEDISH_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::TW_CHINESE_FEMALE => Loc::getMessage('VI_TTS_VOICE_TW_CHINESE_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::UK_ENGLISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_UK_ENGLISH_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_AMAZON
			]),
			self::UK_ENGLISH_MALE => Loc::getMessage('VI_TTS_VOICE_UK_ENGLISH_MALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_AMAZON
			]),
			self::US_ENGLISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_US_ENGLISH_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::US_ENGLISH_MALE => Loc::getMessage('VI_TTS_VOICE_US_ENGLISH_MALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_DEFAULT
			]),
			self::US_SPANISH_FEMALE => Loc::getMessage('VI_TTS_VOICE_US_SPANISH_FEMALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_AMAZON
			]),
			self::US_SPANISH_MALE => Loc::getMessage('VI_TTS_VOICE_US_SPANISH_MALE_WITH_PROVIDER', [
				'#PROVIDER#' => self::PROVIDER_AMAZON
			]),
		);
	}

	/**
	 * Returns default TTS voice for language id
	 * @param string $lang ID of the language.
	 * @return string
	 */
	public static function getDefaultVoice($lang = null)
	{
		static $defaultVoices = [
			'ru' => self::RU_RUSSIAN_FEMALE, //	Russian
			'en' => self::US_ENGLISH_MALE, // English
			'de' => self::EUR_GERMAN_FEMALE, //	German
			'ua' => self::RU_RUSSIAN_FEMALE, //	Ukrainian
			'la' => self::EUR_SPANISH_FEMALE, // Spanish
			'br' => self::BR_PORTUGUESE_FEMALE, // Portuguese
			'fr' => self::EUR_FRENCH_FEMALE, //	French
			'sc' => self::CH_CHINESE_FEMALE, //	Chinese Simplified
			'tc' => self::CH_CHINESE_FEMALE, //	Chinese Traditional
			'pl' => self::EUR_POLISH_FEMALE, //	Polish
			'it' => self::EUR_ITALIAN_FEMALE, // Italian
			'tr' => self::EUR_TURKISH_FEMALE, // Turkish
			'ja' => self::JP_JAPANESE_FEMALE, // Japanese
		];

		if ($lang === null)
		{
			$lang = Context::getCurrent()->getLanguage();
		}

		return $defaultVoices[$lang] ?? self::US_ENGLISH_MALE;
	}

	public static function getProvider(string $voice): ?string
	{
		return (isset(self::AMAZON_VOICES[$voice])) ?  self::PROVIDER_AMAZON : self::PROVIDER_DEFAULT;
	}
}
