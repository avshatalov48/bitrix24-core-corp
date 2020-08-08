<?php

namespace Bitrix\Disk;

use Bitrix\Main\Loader;

final class Desktop
{
	const OPT_DESKTOP_DISK_INSTALL = 'DesktopDiskInstall';

	const DESKTOP_DISK_STATUS_ONLINE        = 'online';
	const DESKTOP_DISK_STATUS_NOT_INSTALLED = 'not_installed';
	const DESKTOP_DISK_STATUS_NOT_ENABLED   = 'not_enabled';

	/**
	 * Checks status of disk install.
	 * @return bool
	 * @deprecated
	 */
	public static function isDesktopDiskInstall()
	{
		return (bool)\CUserOptions::getOption(Driver::INTERNAL_MODULE_ID, self::OPT_DESKTOP_DISK_INSTALL);
	}

	/**
	 * Checks online status of disk.
	 * @return bool
	 * @deprecated
	 */
	public static function isDesktopDiskOnline()
	{
		return self::isDesktopImOnline() && self::isDesktopDiskInstall();
	}

	public static function enableInVersion($version)
	{
		if(!Loader::includeModule('im'))
		{
			return false;
		}

		return \CIMMessenger::enableInVersion($version);
	}

	/**
	 * Checks status of desktop install.
	 * @return bool
	 */
	public static function isDesktopInstall()
	{
		if(!Loader::includeModule('im'))
		{
			return false;
		}

		return \CIMMessenger::checkInstallDesktop();
	}

	/**
	 * Checks online status of IM.
	 * @return bool
	 */
	public static function isDesktopImOnline()
	{
		if(!Loader::includeModule('im'))
		{
			return false;
		}

		return \CIMMessenger::checkDesktopStatusOnline();
	}

	/**
	 * Sets option for current user for disk install.
	 * @deprecated
	 */
	public static function setDesktopDiskInstalled()
	{
		global $USER;
		if (!$USER instanceof \CUser)
		{
			return;
		}

		\CUserOptions::setOption(Driver::INTERNAL_MODULE_ID, self::OPT_DESKTOP_DISK_INSTALL, true, false, $USER->getId());
		UserConfiguration::resetDocumentServiceCode();
		Banner::deactivate('install_disk');
	}

	/**
	 * Sets option for current user for disk uninstall.
	 * @deprecated
	 */
	public static function setDesktopDiskUninstalled()
	{
		global $USER;
		if (!$USER instanceof \CUser)
		{
			return;
		}

		\CUserOptions::setOption(Driver::INTERNAL_MODULE_ID, self::OPT_DESKTOP_DISK_INSTALL, false, false, $USER->getId());
		UserConfiguration::resetDocumentServiceCode();
	}

	/**
	 * Gets disk version from http headers.
	 *
	 * @param bool $strictDisk Non ajax request from Desktop Chrome.
	 * @return int
	 */
	public static function getDiskVersion($strictDisk = false)
	{
		if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('%Bitrix24.Disk/([0-9\.]+)%i', $_SERVER['HTTP_USER_AGENT'], $m))
		{
			if($strictDisk && mb_strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false)
			{
				return 0;
			}
			return $m[1];
		}
		return 0;
	}

	/**
	 * Returns version of api disk from http headers.
	 *
	 * @return int
	 */
	public static function getApiDiskVersion()
	{
		$diskVersion = self::getDiskVersion();
		if(!$diskVersion)
		{
			return 0;
		}

		$parts = explode('.', $diskVersion);
		if(!isset($parts[3]))
		{
			return 0;
		}

		return (int)$parts[3];
	}
}