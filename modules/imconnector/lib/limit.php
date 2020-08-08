<?php
namespace Bitrix\ImConnector;

use \Bitrix\Bitrix24\Feature;

class Limit
{
	protected const OPTION_CONNECTOR_IMESSAGE = 'imconnector_can_use_apple_business_chat';

	public const INFO_HELPER_LIMIT_CONNECTOR_IMESSAGE = 'limit_contact_center_apple_business_chat';

	/**
	 * @return bool
	 */
	public static function isDemoLicense()
	{
		$result = false;
		if (\CModule::IncludeModule('bitrix24'))
		{
			$result = \CBitrix24::getLicenseType() === 'demo';
		}

		return $result;
	}

	/**
	 * @return bool|mixed
	 */
	public static function canUseIMessage()
	{
		$return = true;

		if (\CModule::IncludeModule('bitrix24'))
		{
			$return = Feature::isFeatureEnabled(self::OPTION_CONNECTOR_IMESSAGE);
		}

		return $return;
	}

}