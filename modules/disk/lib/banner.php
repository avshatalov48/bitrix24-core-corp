<?php


namespace Bitrix\Disk;


final class Banner
{
	/**
	 * Checks banner for current user by name.
	 * @param string $name Banner name.
	 * @return bool
	 */
	public static function isActive($name)
	{
		$userSettings = \CUserOptions::getOption(Driver::INTERNAL_MODULE_ID, '~banner-offer', array($name => false));

		return empty($userSettings[$name]);
	}

	/**
	 * Deactivates banner for current user by name.
	 * @param string $name Banner name.
	 * @return bool
	 */
	public static function deactivate($name)
	{
		global $USER;
		if(!$USER instanceof \CUser || $USER->getId() <= 0)
		{
			return false;
		}
		return \CUserOptions::setOption(Driver::INTERNAL_MODULE_ID, '~banner-offer', array($name => true), false, $USER->getId());
	}
}