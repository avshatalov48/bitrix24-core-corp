<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Communication;

/**
 * Class Validator
 * @package Bitrix\Crm\Communication
 */
class Validator
{
	/**
	 * Validate.
	 *
	 * @param string $code Code.
	 * @param integer $typeId Type ID.
	 * @return string
	 */
	public static function validate($code, $typeId)
	{
		switch ($typeId)
		{
			case Type::OPENLINE:
				return self::validateOpenLine($code);

			case Type::PHONE:
				return self::validatePhone($code);

			case Type::EMAIL:
			default:
				return self::validateEmail($code);
		}
	}

	/**
	 * Validate email.
	 *
	 * @param string $email Email.
	 * @return string
	 */
	public static function validateEmail($email)
	{
		return check_email($email);
	}

	/**
	 * Validate phone number.
	 *
	 * @param string $phone Phone number.
	 * @return bool
	 */
	public static function validatePhone($phone)
	{
		return (bool) preg_match('/^[\+]?[\d]{4,25}$/', $phone);
	}

	/**
	 * Validate OpenLine code.
	 *
	 * @param string $code Code.
	 * @return bool
	 */
	public static function validateOpenLine($code)
	{
		return (bool) preg_match('/^[\d]+\|[\d]+$/', $code);
	}
}