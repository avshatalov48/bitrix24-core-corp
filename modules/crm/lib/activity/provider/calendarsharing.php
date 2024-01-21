<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\Badge\SourceIdentifier;
use Bitrix\Crm\Badge\Type\CalendarSharingStatus;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class CalendarSharing extends Base
{
	public const PROVIDER_ID = 'CRM_CALENDAR_SHARING';
	public const PROVIDER_TYPE_ID = 'CALENDAR_SHARING';

	public static function getId()
	{
		return self::PROVIDER_ID;
	}

	public static function getTypeId(array $activity)
	{
		return self::PROVIDER_TYPE_ID;
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALENDAR_SHARING_NAME_V2') ?? Loc::getMessage('CRM_ACTIVITY_PROVIDER_CALENDAR_SHARING_NAME');
	}

	public static function getTypes()
	{
		return [
			[
				'NAME' => self::getName(),
				'PROVIDER_ID' => self::getId(),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID,
			],
		];
	}

	public static function getDefaultPingOffsets(array $params = []): array
	{
		return TodoPingSettingsProvider::DEFAULT_OFFSETS;
	}

	public static function syncBadges(int $activityId, array $activityFields, array $bindings): void
	{
		if (!isset($activityFields['SETTINGS']['LINK_ID']) || !(int)$activityFields['SETTINGS']['LINK_ID'])
		{
			return;
		}

		$isCompleted = isset($activityFields['COMPLETED']) && $activityFields['COMPLETED'] === 'Y';
		if (!$isCompleted)
		{
			return;
		}

		$badge = Container::getInstance()->getBadge(
			CalendarSharingStatus::CALENDAR_SHARING_STATUS_TYPE,
			CalendarSharingStatus::CANCELED_BY_CLIENT
		);

		$itemIdentifier = new ItemIdentifier(
			(int)$activityFields['OWNER_TYPE_ID'],
			(int)$activityFields['OWNER_ID']
		);

		$sourceIdentifier = new SourceIdentifier(
			SourceIdentifier::CALENDAR_SHARING_TYPE_PROVIDER,
			0,
			$activityFields['SETTINGS']['LINK_ID']
		);

		if (isset($activityFields['SETTINGS']['CANCELED_BY_CLIENT']))
		{
			$badge->upsert($itemIdentifier, $sourceIdentifier);
		}
		else
		{
			$badge->unbindWithAnyValue($itemIdentifier, $sourceIdentifier);
		}
	}

	public static function checkFields($action, &$fields, $id, $params = null): Result
	{
		if ($action === self::ACTION_UPDATE)
		{
			if (isset($fields['END_TIME']) && (string)($fields['END_TIME']) !== '')
			{
				$fields['START_TIME'] = $fields['END_TIME'];
				$fields['DEADLINE'] = $fields['END_TIME'];
			}
		}

		return new Result();
	}
}
