<?php
namespace Bitrix\ImConnector;

use \Bitrix\Bitrix24\Feature;

class Limit
{
	protected const OPTION_CONNECTOR_IMESSAGE = 'imconnector_can_use_apple_business_chat';
	protected const OPTION_CONNECTOR_VIBER = 'imconnector_can_use_viber';
	protected const OPTION_CONNECTOR_WHATSAPPBYTWILIO = 'imconnector_can_use_whatsappbytwilio';
	protected const OPTION_CONNECTOR_NOTIFICATIONS = 'notifications';

	protected const LIST_LIMIT_CONNECTOR = [
		'imessage' => [
			'option' => self::OPTION_CONNECTOR_IMESSAGE,
			'helper' => self::INFO_HELPER_LIMIT_CONNECTOR_IMESSAGE,
		],
		'viber' => [
			'option' => self::OPTION_CONNECTOR_VIBER,
			'helper' => self::INFO_HELPER_LIMIT_CONNECTOR_VIBER,
		],
		'whatsappbytwilio' => [
			'option' => self::OPTION_CONNECTOR_WHATSAPPBYTWILIO,
			'helper' => self::INFO_HELPER_LIMIT_CONNECTOR_WHATSAPPBYTWILIO,
		],
		'notifications' => [
			'option' => self::OPTION_CONNECTOR_NOTIFICATIONS,
			'helper' => self::INFO_HELPER_LIMIT_CONNECTOR_NOTIFICATIONS,
		],
	];

	public const INFO_HELPER_LIMIT_CONNECTOR_IMESSAGE = 'limit_contact_center_apple_business_chat';
	public const INFO_HELPER_LIMIT_CONNECTOR_VIBER = 'limit_contact_center_viber';
	public const INFO_HELPER_LIMIT_CONNECTOR_WHATSAPPBYTWILIO = 'limit_contact_center_whatsappbytwilio';
	public const INFO_HELPER_LIMIT_CONNECTOR_NOTIFICATIONS = 'limit_crm_sales_sms_whatsapp';

	/**
	 * @return bool
	 */
	public static function isDemoLicense(): bool
	{
		$result = false;
		if (\CModule::IncludeModule('bitrix24'))
		{
			$result = \CBitrix24::getLicenseType() === 'demo';
		}

		return $result;
	}

	/**
	 * @param $connectorId
	 * @return bool
	 */
	public static function canUseConnector($connectorId): bool
	{
		$return = true;

		if (
			!empty(self::LIST_LIMIT_CONNECTOR[$connectorId]['option'])
			&& \CModule::IncludeModule('bitrix24')
		)
		{
			$return = Feature::isFeatureEnabled(self::LIST_LIMIT_CONNECTOR[$connectorId]['option']);
		}

		return $return;
	}

	/**
	 * @param $connectorId
	 * @return string
	 */
	public static function getIdInfoHelperConnector($connectorId): string
	{
		$return = '';

		if (!empty(self::LIST_LIMIT_CONNECTOR[$connectorId]['helper']))
		{
			$return = self::LIST_LIMIT_CONNECTOR[$connectorId]['helper'];
		}

		return $return;
	}

}