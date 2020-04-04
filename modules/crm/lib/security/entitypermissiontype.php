<?php
namespace Bitrix\Crm\Security;

class EntityPermissionType
{
	const UNDEFINED	= 0x0;
	const CREATE	= 0x1;
	const READ		= 0x2;
	const UPDATE	= 0x3;
	const DELETE	= 0x4;

	const CREATE_NAME	= 'ADD';
	const READ_NAME		= 'READ';
	const UPDATE_NAME	= 'WRITE';
	const DELETE_NAME	= 'DELETE';

	public static function resolveName($typeID)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if($typeID === self::CREATE)
		{
			return self::CREATE_NAME;
		}
		elseif($typeID === self::READ)
		{
			return self::READ_NAME;
		}
		elseif($typeID === self::UPDATE)
		{
			return self::UPDATE_NAME;
		}
		elseif($typeID === self::DELETE)
		{
			return self::DELETE_NAME;
		}

		return '';
	}
}