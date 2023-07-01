<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Settings\ActivitySettings;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class Zoom extends Base
{
	public const PROVIDER_ID = 'ZOOM';
	public CONST TYPE_ZOOM_CONF_START = 'ZOOM_CONF_START';
	public CONST TYPE_ZOOM_CONF_JOINED = 'ZOOM_CONF_JOINED';

	public static function getId(): string
	{
		return self::PROVIDER_ID;
	}

	public static function getName(): string
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_ZOOM_TITLE');
	}

	/**
	 * Returns supported provider's types
	 * @return array
	 */
	public static function getTypes(): array
	{
		return array(
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_ZOOM_CONF_START_TITLE'),
				'PROVIDER_ID' => static::PROVIDER_ID,
				'PROVIDER_TYPE_ID' => static::TYPE_ZOOM_CONF_START,
				'DIRECTIONS' => array(
					\CCrmActivityDirection::Outgoing => Loc::getMessage('CRM_ACTIVITY_PROVIDER_ZOOM_CONF_START_TITLE'),
				),
			)
		);
	}

	/**
	 * Returns true if Zoom is available on current portal
	 *
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function isAvailable(): bool
	{
		if (!Loader::includeModule('socialservices'))
		{
			return false;
		}

		return \Bitrix\SocialServices\Integration\Zoom\Conference::isAvailable();
	}

	public static function isConnected(): bool
	{
		if (!Loader::includeModule('socialservices'))
		{
			return false;
		}

		return \CZoomInterface::isConnected(\CCrmSecurityHelper::GetCurrentUserID());
	}

	public static function prepareHistoryItemData($historyFields): ?array
	{
		return isset($historyFields['SETTINGS']) && is_array($historyFields['SETTINGS']) ? $historyFields['SETTINGS'] : [];
	}

	public static function getCommunicationType($providerTypeId = null): string
	{
		return static::COMMUNICATION_TYPE_UNDEFINED;
	}

	public static function generateSubject($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined, array $replace = null): string
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_ZOOM_CONF_START_TITLE');
	}

	/**
	 * Updates Zoom conference, if we change activity dates.
	 *
	 * @param int $entityId Internal zoom conference id.
	 * @param array $activity Activity data.
	 * @param array $options Options.
	 * @return Result|\Bitrix\Main\Result
	 */
	public static function updateAssociatedEntity($entityId, array $activity, array $options = array())
	{
		$result = new Result();

		if (!isset($activity['ASSOCIATED_ENTITY_ID']))
		{
			return $result->addError(new Error('Could not get entity id to update.'));
		}

		if (!Loader::includeModule('socialservices'))
		{
			return $result->addError(new Error('Socialservices module is not installed'));
		}

		if ($activity['COMPLETED'] !== 'Y')
		{
			$activityData['meeting_id'] = $entityId;
			$activityData['start_time'] = $activity['START_TIME'] ?:null;
			$activityData['end_time'] = $activity['END_TIME'] ?:null;

			$updateResult = \Bitrix\SocialServices\Integration\Zoom\Conference::update(\CCrmSecurityHelper::GetCurrentUserID(), $activityData);
			if (!$updateResult->isSuccess())
			{
				$result->addErrors($updateResult->getErrors());
			}
		}

		return $result;
	}

	public static function postForm(array &$activity, array $formData)
	{
		$result = new Result();

		if (isset($activity['START_TIME']) && $activity['COMPLETED'] !== 'Y')
		{
			$currentDateTime = new DateTime();
			$activityStartDateTime = DateTime::createFromUserTime($activity['START_TIME']);

			if ($activityStartDateTime->getTimestamp() < $currentDateTime->getTimestamp())
			{
				$result->addError(new Error(Loc::getMessage("CRM_ACTIVITY_PROVIDER_ZOOM_ERROR_INCORRECT_DATETIME")));
				return $result;
			}
		}

		return $result;
	}

	public static function canUseCalendarEvents($providerTypeId = null): bool
	{
		return $providerTypeId === static::TYPE_ZOOM_CONF_START;
	}

	public static function canKeepCompletedInCalendar($providerTypeId = null)
	{
		return ActivitySettings::getValue(ActivitySettings::KEEP_COMPLETED_CALLS);
	}

	public static function deleteAssociatedEntity($entityId, array $activity, array $options = array())
	{
		$result = new Result();
		if (!Loader::includeModule('socialservices'))
		{
			return $result->addError(new Error('Socialservices module is not installed'));
		}

		$deleteResult = \Bitrix\SocialServices\Integration\Zoom\Conference::delete($entityId);
		if (!$deleteResult->isSuccess())
		{
			return $result->addErrors($deleteResult->getErrors());
		}

		return $result;
	}

	public static function checkFields($action, &$fields, $id, $params = null)
	{
		if ($action === self::ACTION_UPDATE)
		{
			if (isset($fields['START_TIME']) && $fields['START_TIME'] !== '')
			{
				$fields['DEADLINE'] = $fields['START_TIME'];
			}
		}

		return new \Bitrix\Main\Result();
	}
}
