<?php
namespace Bitrix\Crm\Synchronization;

class UserFieldCustomSynchronizer
{
	/** @var \CCrmPerms|null  */
	protected static $userPermissions = null;
	/** @var array|null */
	protected static $languageIDs = null;

	protected static function getCurrentUserPermissions()
	{
		if(self::$userPermissions === null)
		{
			self::$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}
		return self::$userPermissions;
	}
	protected static function getLanguageIDs()
	{
		if(self::$languageIDs === null)
		{
			$dbResult = \CLanguage::GetList();
			while($arLang = $dbResult->Fetch())
			{
				self::$languageIDs[] = $arLang['LID'];
			}
		}
		return self::$languageIDs;
	}
}