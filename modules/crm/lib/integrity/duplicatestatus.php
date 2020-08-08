<?php

namespace Bitrix\Crm\Integrity;
/**
 * Class DuplicateStatus
 *
 * @package Bitrix\Crm\Integrity
 */
class DuplicateStatus
{
	const UNDEFINED = 0;
	const PENDING   = 1;
	const CONFLICT  = 2;
	const POSTPONED = 3;
	const ERROR     = 4;

	const PENDING_NAME      = 'PENDING';
	const CONFLICT_NAME     = 'CONFLICT';
	const POSTPONED_NAME    = 'POSTPONED';
	const ERROR_NAME        = 'ERROR';

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
		return $ID >= self::PENDING && $ID <= self::ERROR;
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
		if($name === self::PENDING_NAME)
		{
			return self::PENDING;
		}
		elseif($name === self::CONFLICT_NAME)
		{
			return self::CONFLICT;
		}
		elseif($name === self::POSTPONED_NAME)
		{
			return self::POSTPONED;
		}
		elseif($name === self::ERROR_NAME)
		{
			return self::ERROR;
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

		if($ID === self::PENDING)
		{
			return self::PENDING_NAME;
		}
		elseif($ID === self::CONFLICT)
		{
			return self::CONFLICT_NAME;
		}
		elseif($ID === self::POSTPONED)
		{
			return self::POSTPONED_NAME;
		}
		elseif($ID === self::ERROR)
		{
			return self::ERROR_NAME;
		}
		return '';
	}
}