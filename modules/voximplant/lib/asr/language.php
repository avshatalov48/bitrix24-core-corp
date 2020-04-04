<?php

namespace Bitrix\Voximplant\Asr;

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Language
{
	const AFRIKAANS_ZA = "AFRIKAANS_ZA";
	const ARABIC_AE = "ARABIC_AE";
	const ARABIC_BH = "ARABIC_BH";
	const ARABIC_DZ = "ARABIC_DZ";
	const ARABIC_EG = "ARABIC_EG";
	const ARABIC_IL = "ARABIC_IL";
	const ARABIC_IQ = "ARABIC_IQ";
	const ARABIC_JO = "ARABIC_JO";
	const ARABIC_KW = "ARABIC_KW";
	const ARABIC_LB = "ARABIC_LB";
	const ARABIC_MA = "ARABIC_MA";
	const ARABIC_OM = "ARABIC_OM";
	const ARABIC_PS = "ARABIC_PS";
	const ARABIC_QA = "ARABIC_QA";
	const ARABIC_SA = "ARABIC_SA";
	const ARABIC_TN = "ARABIC_TN";
	const BASQUE_ES = "BASQUE_ES";
	const BULGARIAN_BG = "BULGARIAN_BG";
	const CANTONESE_HK = "CANTONESE_HK";
	const CATALAN_ES = "CATALAN_ES";
	const CHINESE_CN = "CHINESE_CN";
	const CHINESE_HK = "CHINESE_HK";
	const CHINESE_TW = "CHINESE_TW";
	const CROATIAN_HR = "CROATIAN_HR";
	const CZECH_CZ = "CZECH_CZ";
	const DANISH_DK = "DANISH_DK";
	const DUTCH_NL = "DUTCH_NL";
	const ENGLISH_AU = "ENGLISH_AU";
	const ENGLISH_CA = "ENGLISH_CA";
	const ENGLISH_IE = "ENGLISH_IE";
	const ENGLISH_IN = "ENGLISH_IN";
	const ENGLISH_NZ = "ENGLISH_NZ";
	const ENGLISH_PH = "ENGLISH_PH";
	const ENGLISH_UK = "ENGLISH_UK";
	const ENGLISH_US = "ENGLISH_US";
	const ENGLISH_ZA = "ENGLISH_ZA";
	const FARSI_IR = "FARSI_IR";
	const FILIPINO_PH = "FILIPINO_PH";
	const FINNISH_FI = "FINNISH_FI";
	const FRENCH_CA = "FRENCH_CA";
	const FRENCH_FR = "FRENCH_FR";
	const GALICIAN_ES = "GALICIAN_ES";
	const GERMAN_DE = "GERMAN_DE";
	const GREEK_GR = "GREEK_GR";
	const HEBREW_IL = "HEBREW_IL";
	const HINDI_IN = "HINDI_IN";
	const HUNGARIAN_HU = "HUNGARIAN_HU";
	const ICELANDIC_IS = "ICELANDIC_IS";
	const INDONESIAN_ID = "INDONESIAN_ID";
	const ITALIAN_IT = "ITALIAN_IT";
	const JAPANESE_JP = "JAPANESE_JP";
	const KOREAN_KR = "KOREAN_KR";
	const LITHUANIAN_LT = "LITHUANIAN_LT";
	const MALAYSIA_MY = "MALAYSIA_MY";
	const NORWEGIAN_NO = "NORWEGIAN_NO";
	const POLISH_PL = "POLISH_PL";
	const PORTUGUESE_BR = "PORTUGUESE_BR";
	const PORTUGUES_PT = "PORTUGUES_PT";
	const ROMANIAN_RO = "ROMANIAN_RO";
	const RUSSIAN_RU = "RUSSIAN_RU";
	const SERBIAN_RS = "SERBIAN_RS";
	const SLOVAK_SK = "SLOVAK_SK";
	const SLOVENIAN_SL = "SLOVENIAN_SL";
	const SPANISH_AR = "SPANISH_AR";
	const SPANISH_BO = "SPANISH_BO";
	const SPANISH_CL = "SPANISH_CL";
	const SPANISH_CO = "SPANISH_CO";
	const SPANISH_CR = "SPANISH_CR";
	const SPANISH_DO = "SPANISH_DO";
	const SPANISH_EC = "SPANISH_EC";
	const SPANISH_ES = "SPANISH_ES";
	const SPANISH_GT = "SPANISH_GT";
	const SPANISH_HN = "SPANISH_HN";
	const SPANISH_MX = "SPANISH_MX";
	const SPANISH_NI = "SPANISH_NI";
	const SPANISH_PA = "SPANISH_PA";
	const SPANISH_PE = "SPANISH_PE";
	const SPANISH_PR = "SPANISH_PR";
	const SPANISH_PY = "SPANISH_PY";
	const SPANISH_SV = "SPANISH_SV";
	const SPANISH_US = "SPANISH_US";
	const SPANISH_UY = "SPANISH_UY";
	const SPANISH_VE = "SPANISH_VE";
	const SWEDISH_SE = "SWEDISH_SE";
	const THAI_TH = "THAI_TH";
	const TURKISH_TR = "TURKISH_TR";
	const UKRAINIAN_UA = "UKRAINIAN_UA";
	const VIETNAMESE_VN = "VIETNAMESE_VN";
	const ZULU_ZA = "ZULU_ZA";

	/**
	 * Returns array of available ASR languages
	 * @return array ('ID' => Language label)
	 */
	public static function getList()
	{
		static $result = null;
		if(!is_array($result))
		{
			$result = array();
			$reflection = new \ReflectionClass(__CLASS__);
			$constants = $reflection->getConstants();
			foreach ($constants as $constantName)
			{
				$result[$constantName] = Loc::getMessage("VOX_ASR_LANGUAGE_" . $constantName);
			}
		}
		return $result;
	}

	/**
	 * Returns default ASR Language for the language id.
	 * @param string $lang ID of the language.
	 * @return string
	 */
	public static function getDefault($lang = null)
	{
		if(is_null($lang))
			$lang = Application::getInstance()->getContext()->getLanguage();
		
		if($lang == 'ru')
			return self::RUSSIAN_RU;
		else
			return self::ENGLISH_US;
	}
}