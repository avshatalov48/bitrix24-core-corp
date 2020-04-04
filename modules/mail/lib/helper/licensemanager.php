<?php

namespace Bitrix\Mail\Helper;

use Bitrix\Main;

/**
 * Class LicenseManager
 */
class LicenseManager
{

	/**
	 * Checks if mailboxes synchronization is available
	 *
	 * @return bool
	 */
	public static function isSyncAvailable()
	{
		if (!Main\Loader::includeModule('bitrix24'))
		{
			return true;
		}

		return \CBitrix24::isLicensePaid() || \CBitrix24::isNfrLicense() || \CBitrix24::isDemoLicense();
	}

	public static function getSharedMailboxesLimit()
	{
		if (!Main\Loader::includeModule('bitrix24'))
		{
			return -1;
		}

		return (int) Main\Config\Option::get('mail', 'shared_mailboxes_limit', -1);
	}

	/**
	 * How many mailboxes a user can connect
	 *
	 * @return int
	 */
	public static function getUserMailboxesLimit()
	{
		if (!Main\Loader::includeModule('bitrix24'))
		{
			return -1;
		}

		if (!static::isSyncAvailable())
		{
			return 0;
		}

		return (int) Main\Config\Option::get('mail', 'user_mailboxes_limit', -1);
	}

	/**
	 * Returns the number of days to store messages
	 *
	 * @return int
	 */
	public static function getSyncOldLimit()
	{
		return (int) Main\Config\Option::get('mail', 'sync_old_limit2', -1);
	}

	/**
	 * Checks if old messages should be deleted
	 *
	 * @return bool
	 */
	public static function isCleanupOldEnabled()
	{
		return static::getSyncOldLimit() > 0;
	}

}
