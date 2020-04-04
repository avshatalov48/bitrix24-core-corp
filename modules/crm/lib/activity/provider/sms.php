<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Activity\CommunicationStatistics;

Loc::loadMessages(__FILE__);

class Sms extends Base
{
	public static function getId()
	{
		return 'CRM_SMS';
	}

	public static function getTypeId(array $activity)
	{
		return 'SMS';
	}

	public static function getTypes()
	{
		return array(
			array(
				'NAME' => 'SMS',
				'PROVIDER_ID' => static::getId(),
				'PROVIDER_TYPE_ID' => 'SMS',
				'DIRECTIONS' => array(
					\CCrmActivityDirection::Incoming => Loc::getMessage('CRM_ACTIVITY_PROVIDER_SMS_INCOMING'),
					\CCrmActivityDirection::Outgoing => Loc::getMessage('CRM_ACTIVITY_PROVIDER_SMS_OUTGOING'),
				)
			)
		);
	}

	/**
	 * @return array
	 */
	public static function getTypesFilterPresets()
	{
		return array(
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_SMS_NAME'),
				'PROVIDER_TYPE_ID' => 'SMS'
			),
		);
	}

	public static function getName()
	{
		return 'SMS';
	}

	public static function getCommunicationType($providerTypeId = null)
	{
		return static::COMMUNICATION_TYPE_PHONE;
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @return bool
	 */
	public static function isTypeEditable($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return false;
	}

	/**
	 * Check if activity can be completed interactively by user.
	 * @return bool
	 */
	public static function isCompletable()
	{
		return false;
	}

	public static function getSupportedCommunicationStatistics()
	{
		return array(
			CommunicationStatistics::STATISTICS_QUANTITY
		);
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @param array|null $replace Message replace templates.
	 * @return string
	 */
	public static function generateSubject($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined, array $replace = null)
	{
		if($direction === \CCrmActivityDirection::Incoming)
		{
			return Loc::getMessage('CRM_ACTIVITY_PROVIDER_SMS_INCOMING', $replace);
		}
		elseif($direction === \CCrmActivityDirection::Outgoing)
		{
			return Loc::getMessage('CRM_ACTIVITY_PROVIDER_SMS_OUTGOING', $replace);
		}

		return parent::generateSubject($providerTypeId, $direction, $replace);
	}

	public static function renderView(array $activity)
	{
		global $APPLICATION;

		ob_start();

		$APPLICATION->IncludeComponent(
			'bitrix:crm.activity.sms', '',
			array(
				'ACTIVITY' => $activity
			)
		);

		return ob_get_clean();
	}

	public static function addActivity($fields, $checkPerms = true)
	{
		$fields['PROVIDER_ID'] = static::getId();

		if (!isset($fields['PROVIDER_TYPE_ID']))
		{
			$fields['PROVIDER_TYPE_ID'] = static::getTypeId(array());
		}
		if (!isset($fields['DIRECTION']))
		{
			$fields['DIRECTION'] = \CCrmActivityDirection::Outgoing;
		}
		if (empty($fields['SUBJECT']))
		{
			$fields['SUBJECT'] = static::generateSubject($fields['PROVIDER_TYPE_ID'], $fields['DIRECTION']);
		}
		if (!isset($fields['START_TIME']))
		{
			$fields['START_TIME'] = \ConvertTimeStamp(time() + \CTimeZone::GetOffset(), 'FULL');
		}
		if (!isset($fields['DESCRIPTION_TYPE']))
		{
			$fields['DESCRIPTION_TYPE'] = \CCrmContentType::PlainText;
		}
		if (!isset($fields['COMPLETED']))
		{
			$fields['COMPLETED'] = 'Y';
		}
		if (!isset($fields['RESPONSIBLE_ID']))
		{
			$fields['RESPONSIBLE_ID'] = $fields['AUTHOR_ID'];
		}

		return \CCrmActivity::Add($fields, $checkPerms, true, array('REGISTER_SONET_EVENT' => true));
	}
}
