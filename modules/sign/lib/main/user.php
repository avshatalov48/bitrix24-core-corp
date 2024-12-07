<?php
namespace Bitrix\Sign\Main;

/**
 * @deprecated
 */
class User
{
	/**
	 * Returns main module's USER instance.
	 * @return \CUser
	 */
	public static function getInstance(): \CUser
	{
		return $GLOBALS['USER'];
	}

	/**
	 * Returns current user formatted name.
	 * @return string
	 */
	public static function getCurrentUserName(): string
	{
		return self::getInstance()->getFormattedName(true, false);
	}

	/**
	 * Returns true if current user is intranet.
	 * @return bool
	 */
	public static function isIntranet(): bool
	{
		static $hasAccess = null;

		if ($hasAccess !== null)
		{
			return $hasAccess;
		}

		$user = \CUser::getList(
			'ID', 'ASC',
			['ID_EQUAL_EXACT' => self::getInstance()->getId()],
			['FIELDS' => 'ID', 'SELECT' => ['UF_DEPARTMENT']]
		)->fetch();

		$hasAccess = ($user['UF_DEPARTMENT'][0] ?? 0) > 0;
		return $hasAccess;
	}
}
