<?php
namespace Bitrix\Crm\Integration;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\ImOpenLines;

Loc::loadMessages(__FILE__);

class IMOpenLineManager
{
	/** @var bool|null  */
	private static $isEnabled = null;
	const UNDEFINED_FEATURE = 0;
	const CHAT_FEATURE = 1;
	/**
	 * Check if IM Open Lines enabled.
	 * @return bool
	 */
	public static function isEnabled()
	{
		if(self::$isEnabled === null)
		{
			self::$isEnabled = ModuleManager::isModuleInstalled('imopenlines')
				&& Loader::includeModule('imopenlines');
		}
		return self::$isEnabled;
	}
	/**
	 * Check if IM Open Lines in use.
	 * @return bool
	 */
	public static function isInUse()
	{
		if(!self::isEnabled())
		{
			return false;
		}

		return ImOpenLines\Helper::isAvailable();
	}

	/**
	 * Check if IM Open Lines feature in use
	 * @param int $featureID Featute ID.
	 * @return bool
	 */
	public static function isFeatureInUse($featureID)
	{
		if(!self::isEnabled())
		{
			return false;
		}

		if(!is_int($featureID))
		{
			$featureID = (int)$featureID;
		}

		return $featureID === self::CHAT_FEATURE
			? ImOpenLines\Helper::isLiveChatAvailable() : false;
	}

	/**
	 * Check if current user has permission to configure IM Open Lines.
	 * @return bool
	 */
	public static function checkConfigurationPermission()
	{
		if(!self::isEnabled())
		{
			return false;
		}

		return ImOpenLines\Security\Helper::canCurrentUserModifyLine();
	}
	/**
	 * Check if current user has permission to configure  IM Open Lines feature.
	 * @param int $featureID Featute ID.
	 * @return bool
	 */
	public static function checkFeatureConfigurationPermission($featureID)
	{
		if(!self::isEnabled())
		{
			return false;
		}

		if(!is_int($featureID))
		{
			$featureID = (int)$featureID;
		}

		return $featureID === self::CHAT_FEATURE
			? ImOpenLines\Security\Helper::canCurrentUserModifyConnector() : false;
	}
	/**
	 * Get IM Open Lines URL.
	 * @return string
	 */
	public static function getUrl()
	{
		return self::isEnabled() ? ImOpenLines\Helper::getAddUrl() : '';
	}
	/**
	 * Get IM Open Lines feature URL.
	 * @param int $featureID Featute ID.
	 * @return string
	 * @throws Main\LoaderException
	 */
	public static function getFeatureUrl($featureID)
	{
		if(!self::isEnabled())
		{
			return '';
		}

		if(!is_int($featureID))
		{
			$featureID = (int)$featureID;
		}

		return $featureID === self::CHAT_FEATURE
			? ImOpenLines\Helper::getListUrl() : '';
	}
}