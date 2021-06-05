<?php
namespace Bitrix\Crm\Component;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ComponentError
{
	public const NONE				= 0x0;
	public const ENTITY_NOT_FOUND 	= -0x1;
	public const PERMISSION_DENIED	= -0x2;

	public static function getMessage($error): string
	{
		if($error === self::ENTITY_NOT_FOUND)
		{
			return Loc::getMessage('CRM_COMPONENT_ERROR_ENTITY_NOT_FOUND');
		}
		if($error === self::PERMISSION_DENIED)
		{
			return Loc::getMessage('CRM_COMPONENT_ERROR_PERMISSION_DENIED');
		}

		return Loc::getMessage('CRM_COMPONENT_ERROR_GENERAL');
	}
}