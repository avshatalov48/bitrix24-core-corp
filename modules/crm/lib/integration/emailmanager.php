<?php
namespace Bitrix\Crm\Integration;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Mail\MailboxTable;

class EmailManager
{
	/** @var bool|null  */
	private static $isEnabled = null;
	private static $isInUse = null;
	/**
	 * Check if current manager enabled.
	 * @return bool
	 */
	public static function isEnabled()
	{
		if(self::$isEnabled === null)
		{
			self::$isEnabled = ModuleManager::isModuleInstalled('mail')
				&& Loader::includeModule('mail');
		}
		return self::$isEnabled;
	}
	/**
	 * Check if telephony in use.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function isInUse()
	{
		if(self::$isInUse !== null)
		{
			return self::$isInUse;
		}

		if(!(self::isEnabled() && \CCrmSecurityHelper::IsAuthorized()))
		{
			return (self::$isInUse = false);
		}

		$mailBox = MailboxTable::getList(
			array(
				'filter' => array('LID' => SITE_ID, 'ACTIVE' => 'Y', 'USER_ID' => \CCrmSecurityHelper::GetCurrentUserID()),
				'select' => array('OPTIONS'),
				'limit' => 1
			)
		)->fetch();
		$options = is_array($mailBox) && isset($mailBox['OPTIONS']) && is_array($mailBox['OPTIONS'])
			? $mailBox['OPTIONS'] : array();
		$flags = isset($options['flags']) && is_array($options['flags'])
			? $options['flags'] : array();

		return (self::$isInUse = in_array('crm_connect', $flags, true));
	}
	/**
	 * Get service URL.
	 * @return string
	 * @throws Main\LoaderException
	 */
	public static function getUrl()
	{
		return self::isEnabled()
			? Option::get('socialnetwork', 'user_page', SITE_DIR.'company/personal/', SITE_ID).'mail/?page=home'
			: '';
	}
	/**
	 * Check if current user has permission to configure email.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function checkConfigurationPermission()
	{
		return self::isEnabled() && \CCrmSecurityHelper::IsAuthorized();
	}
}