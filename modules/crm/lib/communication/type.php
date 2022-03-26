<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Communication;

use Bitrix\Main\Localization\Loc;

/**
 * Class Type
 *
 * @package Bitrix\Crm\Communication
 */
class Type
{
	const UNDEFINED = 0;
	const PHONE = 1;
	const EMAIL = 2;
	const FACEBOOK = 3;
	const TELEGRAM = 4;
	const VK = 5;
	const SKYPE = 6;
	const BITRIX24 = 7;
	const OPENLINE = 8;
	const VIBER = 9;
	const IMOL = 10;
	const SLUSER = 10;

	const PHONE_NAME = 'PHONE';
	const EMAIL_NAME = 'EMAIL';
	const FACEBOOK_NAME = 'FACEBOOK';
	const TELEGRAM_NAME = 'TELEGRAM';
	const VK_NAME = 'VK';
	const SKYPE_NAME = 'SKYPE';
	const BITRIX24_NAME = 'BITRIX24';
	const OPENLINE_NAME = 'OPENLINE';
	const VIBER_NAME = 'VIBER';
	const IMOL_NAME = 'IMOL';
	const SLUSER_NAME = 'SLUSER';

	/**
	 * Get all names.
	 * @return array
	 */
	public static function getAllNames()
	{
		return array(
			self::PHONE_NAME,
			self::EMAIL_NAME,
			self::FACEBOOK_NAME,
			self::TELEGRAM_NAME,
			self::SKYPE_NAME,
			self::VK_NAME,
			self::BITRIX24_NAME,
			self::OPENLINE_NAME,
			self::VIBER_NAME,
			self::IMOL_NAME,
			self::SLUSER_NAME,
		);
	}
	/**
	 * Check if specified type ID is defined.
	 * @param int $ID Type ID.
	 * @return bool
	 */
	public static function isDefined($ID)
	{
		if(!is_numeric($ID))
		{
			return false;
		}

		$ID = (int)$ID;
		return $ID >= self::PHONE && $ID <= self::SLUSER;
	}
	/**
	 * Try to resolve type ID by name.
	 * @param string $name Type name.
	 * @return int
	 */
	public static function resolveID($name)
	{
		if(!is_string($name))
		{
			return self::UNDEFINED;
		}

		$name = mb_strtoupper($name);
		if($name === self::PHONE_NAME)
		{
			return self::PHONE;
		}
		elseif($name === self::EMAIL_NAME)
		{
			return self::EMAIL;
		}
		elseif($name === self::FACEBOOK_NAME)
		{
			return self::FACEBOOK;
		}
		elseif($name === self::TELEGRAM_NAME)
		{
			return self::TELEGRAM;
		}
		elseif($name === self::VK_NAME)
		{
			return self::VK;
		}
		elseif($name === self::SKYPE_NAME)
		{
			return self::SKYPE;
		}
		elseif($name === self::BITRIX24_NAME)
		{
			return self::BITRIX24;
		}
		elseif($name === self::OPENLINE_NAME)
		{
			return self::OPENLINE;
		}
		elseif($name === self::VIBER_NAME)
		{
			return self::VIBER;
		}
		elseif($name === self::SLUSER_NAME)
		{
			return self::SLUSER;
		}
		return self::UNDEFINED;
	}
	/**
	 *  Try to resolve type name by ID.
	 * @param int $ID Type ID.
	 * @return string
	 */
	public static function resolveName($ID)
	{
		if(!is_numeric($ID))
		{
			return '';
		}

		$ID = (int)$ID;
		if($ID <= 0)
		{
			return '';
		}

		if($ID === self::PHONE)
		{
			return self::PHONE_NAME;
		}
		elseif($ID === self::EMAIL)
		{
			return self::EMAIL_NAME;
		}
		elseif($ID === self::FACEBOOK)
		{
			return self::FACEBOOK_NAME;
		}
		elseif($ID === self::TELEGRAM)
		{
			return self::TELEGRAM_NAME;
		}
		elseif($ID === self::VK)
		{
			return self::VK_NAME;
		}
		elseif($ID === self::SKYPE)
		{
			return self::SKYPE_NAME;
		}
		elseif($ID === self::BITRIX24)
		{
			return self::BITRIX24_NAME;
		}
		elseif($ID === self::OPENLINE)
		{
			return self::OPENLINE_NAME;
		}
		elseif($ID === self::VIBER)
		{
			return self::VIBER_NAME;
		}
		elseif($ID === self::SLUSER)
		{
			return self::SLUSER_NAME;
		}
		return '';
	}
	/**
	 * Get related Multifield type IDs
	 * @return array
	 */
	public static function getMultiFieldTypeIDs()
	{
		return array(\CCrmFieldMulti::PHONE, \CCrmFieldMulti::EMAIL, \CCrmFieldMulti::IM, \CCrmFieldMulti::LINK);
	}
	/**
	 * Detect type by communication code.
	 * @param string $code Communication code.
	 * @param bool $isNormalized Is code normalized.
	 * @return integer|null
	 */
	public static function detect($code, $isNormalized = false)
	{
		$list = [self::EMAIL, self::PHONE];
		foreach ($list as $id)
		{
			if ($isNormalized)
			{
				$normalizedCode = $code;
			}
			else
			{
				$normalizedCode = Normalizer::normalize($code, $id);
			}

			if (!Validator::validate($normalizedCode, $id))
			{
				continue;
			}

			return $id;
		}

		return null;
	}
	/**
	 * Get caption.
	 * @param int $id ID.
	 * @return string|null
	 */
	public static function getCaption($id)
	{
		$name = self::resolveName($id);
		return $name ? Loc::getMessage("CRM_COMMUNICATION_TYPE_$name") : null;
	}
}