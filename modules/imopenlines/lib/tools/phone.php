<?php
namespace Bitrix\ImOpenLines\Tools;

use \Bitrix\Main\Loader,
	\Bitrix\Main\PhoneNumber,
	\Bitrix\Main\Application;

use \Bitrix\Crm\Communication\Validator as CrmValidator,
	\Bitrix\Crm\Communication\Normalizer as CrmNormalizer;

/**
 * Class Phone
 * @package Bitrix\ImOpenLines
 */
class Phone
{
	/**
	 * Validate phone number.
	 *
	 * @param string $phone Phone number.
	 * @return bool
	 */
	public static function validate($phone)
	{
//		static $region;
//		if ($region === null)
//		{
//			$region = strtoupper(Application::getInstance()->getLicense()->getRegion() ?: PhoneNumber\Parser::getDefaultCountry());
//		}
		// temporary fix for http://jabber.bx/view.php?id=159612
		// todo: review later
		$region = PhoneNumber\Parser::getDefaultCountry();

		$phoneParsed = PhoneNumber\Parser::getInstance()->parse($phone, $region);
		$result = $phoneParsed->isValid();

		// Moldova's number (issue #158084)
		if ($result && $phoneParsed->getCountryCode() == '373')
		{
			$phoneMd = preg_replace("/[^0-9]+/", '', $phone);
			return (
				substr($phoneMd, 0, 1) === '0' // local starts with '0 231 xxxxx'
				|| substr($phoneMd, 0, 3) === '373' // international starts with '371 231 xxxxx'
			);
		}

		if (!$result && !preg_match("/^\+/", $phone))
		{
			// prefix phone by "+"
			$phoneParsed = PhoneNumber\Parser::getInstance()->parse('+'. $phone, $region);
			$result = $phoneParsed->isValid();
		}

		// Brazil's number (issue #129267)
		if (!$result && $phoneParsed->getCountryCode() == '55')
		{
			$phoneBr = preg_replace("/[^0-9]+/", '', $phone);
			if (strlen($phoneBr) == 12)
			{
				// insert "9" at position 5
				$phoneBr = substr($phoneBr, 0, 4) . '9' . substr($phoneBr, 4);
				$phoneParsed = PhoneNumber\Parser::getInstance()->parse('+' . $phoneBr, $region);
				$result = $phoneParsed->isValid();
			}
		}

		return $result;
	}

	/**
	 * Normalize phone number.
	 *
	 * @param string $phone Phone number.
	 * @return string|null
	 */
	public static function normalize($phone)
	{
		if(Loader::includeModule('crm'))
		{
			$result = CrmNormalizer::normalizePhone($phone);
		}
		else
		{
			$result = PhoneNumber\Parser::getInstance()
				->parse($phone)
				->format(PhoneNumber\Format::E164);
		}

		return $result;
	}

	/**
	 * @param $phone1
	 * @param $phone2
	 * @return bool
	 */
	public static function isSame($phone1, $phone2)
	{
		$result = false;

		if(self::normalize($phone1) == self::normalize($phone2))
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param $text
	 * @return array
	 */
	public static function parseText($text)
	{
		$result = [];
		$matchesPhones = self::extractNumbers($text);;

		if (!empty($matchesPhones[0]))
		{
			foreach ($matchesPhones[0] as $phone)
			{
				if (self::validate($phone))
				{
					$result[] = self::normalize($phone);
				}
			}

			if (!empty($result))
			{
				$result = array_unique($result);
			}
		}

		return $result;
	}

	/**
	 * @param $text
	 * @return array
	 */
	public static function extractNumbers($text)
	{
		$matchesPhones = [];
		$phoneParserManager = PhoneNumber\Parser::getInstance();
		preg_match_all('/' . $phoneParserManager->getValidNumberPattern() . '/i', $text, $matchesPhones);

		if (!empty($matchesPhones))
		{
			$matchesPhones = array_unique($matchesPhones);
		}

		return $matchesPhones;
	}

	/**
	 * @param $phones
	 * @param $searchPhone
	 * @return bool
	 */
	public static function isInArray($phones, $searchPhone)
	{
		$result = false;

		if(!empty($phones) && is_array($phones))
		{
			foreach ($phones as $phone)
			{
				if(self::isSame($phone, $searchPhone))
				{
					$result = true;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * @param $phones
	 * @return array
	 */
	public static function getArrayUniqueValidate($phones)
	{
		$resultPhones = [];

		if(!empty($phones) && is_array($phones))
		{
			foreach ($phones as $phone)
			{
				if(self::validate($phone) && !self::isInArray($resultPhones, $phone))
				{
					$resultPhones[] = $phone;
				}
			}
		}

		return $resultPhones;
	}
}