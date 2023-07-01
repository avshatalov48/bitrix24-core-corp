<?php

class CCrmSecurityHelper
{
	/**
	 * @deprecated No longer used by internal code and not recommended. It is recommended to use the new API
	 * @see Bitrix\Crm\Service\Container::getInstance()->getContext()->getUserId()
	 *
	 * @return int
	 */
	public static function GetCurrentUserID()
	{
		//CUser::GetID may return null
		return intval(self::GetCurrentUser()->GetID());
	}

	/** @return CUser */
	public static function GetCurrentUser()
	{
		return isset($USER) && ((get_class($USER) === 'CUser') || ($USER instanceof CUser))
			? $USER : new CUser();
	}

	public static function IsAuthorized()
	{
		return self::GetCurrentUser()->IsAuthorized();
	}
}
