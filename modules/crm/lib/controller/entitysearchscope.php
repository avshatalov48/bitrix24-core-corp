<?php
namespace Bitrix\Crm\Controller;

class EntitySearchScope
{
	const UNDEFINED = 0;
	const DENOMINATION = 1;
	const INDEX = 2;

	const DENOMINATION_NAME = 'DENOMINATION';
	const INDEX_NAME = 'INDEX';

	public static function isDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = intval($typeID);
		return $typeID >= self::DENOMINATION && $typeID <= self::INDEX;
	}

	public static function resolveName($typeID)
	{
		if(!is_numeric($typeID))
		{
			return '';
		}

		$typeID = intval($typeID);
		if($typeID <= 0)
		{
			return '';
		}

		switch($typeID)
		{
			case self::DENOMINATION:
				return self::DENOMINATION_NAME;
			case self::INDEX:
				return self::INDEX_NAME;
			case self::UNDEFINED:
			default:
				return '';
		}
	}

	public static function resolveID($name)
	{
		$name = mb_strtoupper(trim($name));
		if($name == '')
		{
			return self::UNDEFINED;
		}

		switch($name)
		{
			case self::DENOMINATION_NAME:
				return self::DENOMINATION;
			case self::INDEX_NAME:
				return self::INDEX;
			default:
				return self::UNDEFINED;
		}
	}
}