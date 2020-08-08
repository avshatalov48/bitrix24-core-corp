<?php

namespace Bitrix\DocumentGenerator\Value;

use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Nameable;
use Bitrix\DocumentGenerator\Value;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;

class DateTime extends Value implements Nameable
{
	/**
	 * DateTime constructor.
	 * @param $value
	 * @param array $options
	 */
	public function __construct($value, array $options = [])
	{
		$options = $this->getOptions($options);
		$format = $options['format'];
		if(!$value instanceof Type\Date)
		{
			$value = $this->getFromString($value, $format);
		}
		parent::__construct($value, $options);
	}

	/**
	 * @param $string
	 * @param null $format
	 * @return Type\DateTime|null
	 */
	protected function getFromString($string, $format = null)
	{
		if(is_array($string) || is_object($string))
		{
			return null;
		}
		$value = Type\DateTime::tryParse($string);
		if(!$value instanceof Type\Date)
		{
			$value = Type\DateTime::tryParse($string, $format);
		}
		if(!$value instanceof Type\Date && Loader::includeModule('rest'))
		{
			$convertedDate = \CRestUtil::unConvertDateTime($string);
			if($convertedDate)
			{
				$value = Type\DateTime::tryParse($convertedDate);
			}
		}
		if(!$value)
		{
			$value = null;
		}

		return $value;
	}

	/**
	 * @param null $modifier
	 * @return string
	 */
	public function toString($modifier = null)
	{
		$date = $this->value;
		$options = $this->getOptions($modifier);
		$format = $options['format'];
		if($date instanceof Type\Date)
		{
			$interfaceLanguage = LANGUAGE_ID;
			$templateLanguage = DataProviderManager::getInstance()->getRegionLanguageId();
			if($templateLanguage != $interfaceLanguage)
			{
				Loc::setCurrentLang($templateLanguage);
			}
			$result = FormatDate($format, $date->getTimestamp());
			if($templateLanguage != $interfaceLanguage)
			{
				Loc::setCurrentLang($interfaceLanguage);
			}

			return $result;
		}

		if(!$date)
		{
			$date = '';
		}
		return $date;
	}

	/**
	 * @return array
	 */
	protected static function getDefaultOptions()
	{
		return ['format' => Type\Date::getFormat(DataProviderManager::getInstance()->getCulture())];
	}

	/**
	 * @param string|array $modifier
	 * @return array
	 */
	public static function parseModifier($modifier)
	{
		$data = parent::parseModifier($modifier);
		if(is_array($modifier))
		{
			return $data;
		}
		if(empty($data))
		{
			$data = ['format' => $modifier];
		}
		elseif(!empty($modifier))
		{
			$format = $data['format'] ?? '';
			$parts = explode(',', $modifier);
			foreach($parts as $part)
			{
				if(empty(trim($part)))
				{
					continue;
				}
				if(mb_strpos($part, '=') === false)
				{
					if(!empty($format))
					{
						$format .= ',';
					}
					$format .= $part;
				}
				else
				{
					[$name, $value] = explode('=', $part);
					if(!$name || !$value)
					{
						if(!empty($format))
						{
							$format .= ',';
						}
						$format .= $part;
					}
				}
			}
			$data['format'] = $format;
		}

		return $data;
	}

	/**
	 * @return string
	 */
	public static function getLangName()
	{
		Loc::loadLanguageFile(__FILE__);
		return Loc::getMessage('DOCGEN_VALUE_DATETIME_TITLE');
	}
}