<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Communication;

use Bitrix\Main\PhoneNumber;

/**
 * Class Normalizer
 * @package Bitrix\Crm\Communication
 */
class Normalizer
{
	/**
	 * Normalize.
	 *
	 * @param string $code Code.
	 * @param integer $typeId Type ID.
	 * @return string|null
	 */
	public static function normalize($code, $typeId)
	{
		if (!$code)
		{
			return null;

		}
		switch ($typeId)
		{
			case Type::OPENLINE:
				return self::normalizeOpenLine($code);

			case Type::PHONE:
				return self::normalizePhone($code);

			case Type::EMAIL:
			default:
				return self::normalizeEmail($code);
		}
	}

	/**
	 * Normalize email.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	public static function normalizeEmail($code)
	{
		return trim(mb_strtolower($code));
	}

	/**
	 * Normalize phone number.
	 *
	 * @param string $phone Phone number.
	 * @return string|null
	 */
	public static function normalizePhone($phone)
	{
		return PhoneNumber\Parser::getInstance()
			->parse($phone)
			->format(PhoneNumber\Format::E164);
	}

	/**
	 * Normalize openline
	 *
	 * @param string $code Code.
	 * @return string
	 */
	public static function normalizeOpenLine($code)
	{
		$code = trim($code);
		if (mb_strpos($code, 'imol|') === 0)
		{
			$code = mb_substr($code, 5);
		}

		return $code;
	}
}