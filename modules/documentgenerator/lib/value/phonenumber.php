<?php

namespace Bitrix\DocumentGenerator\Value;

use Bitrix\DocumentGenerator\Nameable;
use Bitrix\DocumentGenerator\Value;
use Bitrix\Main\Localization\Loc;

class PhoneNumber extends Value implements Nameable
{
	/**
	 * @param $value
	 * @param array $options
	 */
	public function __construct($value, array $options = [])
	{
		if($value == null)
		{
			$value = '';
		}
		if(!$value instanceof \Bitrix\Main\PhoneNumber\PhoneNumber)
		{
			$value = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($value);
		}
		parent::__construct($value, $options);
	}

	/**
	 * @param string $modifier
	 * @return string
	 */
	public function toString($modifier = '')
	{
		$format = $this->getOptions($modifier)['format'];
		/** @var \Bitrix\Main\PhoneNumber\PhoneNumber $number */
		$number = $this->value;
		return $number->format($format);
	}

	/**
	 * @return array
	 */
	protected static function getDefaultOptions()
	{
		return ['format' => \Bitrix\Main\PhoneNumber\Format::INTERNATIONAL];
	}

	/**
	 * @return string
	 */
	public static function getLangName()
	{
		Loc::loadLanguageFile(__FILE__);
		return Loc::getMessage('DOCGEN_VALUE_PHONENUMBER_TITLE');
	}
}