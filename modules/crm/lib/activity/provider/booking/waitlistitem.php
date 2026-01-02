<?php

declare(strict_types=1);

namespace Bitrix\Crm\Activity\Provider\Booking;

use Bitrix\Crm\Activity;
use Bitrix\Crm\Dto\Booking\WaitListItem\WaitListItemFields;
use Bitrix\Crm\Timeline;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class WaitListItem extends Activity\Provider\Base
{
	private const PROVIDER_TYPE_DEFAULT = 'WAIT_LIST_ITEM';

	public static function getId(): string
	{
		return 'CRM_WAIT_LIST_ITEM';
	}

	public static function getTypeId(array $activity): string
	{
		return self::PROVIDER_TYPE_DEFAULT;
	}

	public static function getTypes(): array
	{
		return [
			[
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_WAIT_LIST_ITEM_TYPE_DEFAULT_NAME'),
				'PROVIDER_ID' => self::getId(),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_DEFAULT,
			],
		];
	}

	public static function getName(): string
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_WAIT_LIST_ITEM_NAME') ?? '';
	}

	public static function getTypeName($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined): string
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_WAIT_LIST_ITEM_TYPE_DEFAULT_NAME') ?? '';
	}

	public static function getFieldsForEdit(array $activity): array
	{
		return [];
	}

	public static function onWaitListItemAdded(WaitListItemFields $waitListItem): int|null
	{
		$typeId = self::PROVIDER_TYPE_DEFAULT;

		$bindings = BookingCommon::makeBindings($waitListItem);

		if (empty($bindings))
		{
			return null;
		}

		$activityId = self::updateActivity(
			providerId: self::getId(),
			typeId: $typeId,
			waitListItem: $waitListItem,
			bindings: $bindings,
		);
		if ($activityId)
		{
			return $activityId;
		}

		$activityId = BookingCommon::createActivity(
			providerId: self::getId(),
			typeId: $typeId,
			entity: $waitListItem,
			bindings: $bindings,
			subject: BookingCommon::getActivitySubject(
				self::getTypeName($typeId),
				Loc::getMessage('CRM_ACTIVITY_PROVIDER_WAIT_LIST_ITEM_NAME') ?? '',
			),
			settings: [
				'FIELDS' => $waitListItem->toArray(),
			],
		);

		if (!$activityId)
		{
			return null;
		}

		(new TimeLine\Booking\Controller())->onWaitListItemCreated($bindings, $waitListItem);

		return $activityId;
	}

	public static function onWaitListItemUpdated(WaitListItemFields $waitListItem): int|null
	{
		return self::onWaitListItemAdded($waitListItem);
	}

	public static function onWaitListItemDeleted(WaitListItemFields $waitListItem, int $removedBy): void
	{
		$activitiesList = \CCrmActivity::getList(
			[],
			[
				'=PROVIDER_ID' => self::getId(),
				'=PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_DEFAULT,
				'=ASSOCIATED_ENTITY_ID' => $waitListItem->getId(),
				'CHECK_PERMISSIONS' => 'N',
				'COMPLETED' => 'N',
			]
		);
		while ($activity = $activitiesList->fetch())
		{
			$deleted = \CCrmActivity::Delete($activity['ID'], false);

			if ($deleted)
			{
				$bindings = BookingCommon::makeBindings($waitListItem);
				$activity['BINDINGS'] = $bindings;
				BookingCommon::sendPullEventOnDelete($activity);
			}
		}

		(new TimeLine\Booking\Controller())->onWaitListItemDeleted(
			BookingCommon::makeBindings($waitListItem),
			$waitListItem,
			$removedBy,
		);
	}

	private static function updateActivity(
		string $providerId,
		string $typeId,
		WaitListItemFields $waitListItem,
		array $bindings,
	): int|null
	{
		$existingActivity = \CCrmActivity::getList(
			[],
			[
				'=PROVIDER_ID' => $providerId,
				'=PROVIDER_TYPE_ID' => $typeId,
				'=ASSOCIATED_ENTITY_ID' => $waitListItem->getId(),
				'=COMPLETED' => 'N',
				'CHECK_PERMISSIONS' => 'N',
			]
		)?->fetch();

		if (!$existingActivity)
		{
			return null;
		}

		$existingActivity['BINDINGS'] = $bindings;
		$existingActivity['SETTINGS']['FIELDS'] = $waitListItem->toArray();

		$updated = \CCrmActivity::update($existingActivity['ID'], $existingActivity, false);

		if ($updated)
		{
			BookingCommon::sendPullEventOnUpdate($existingActivity);
		}

		return (int)$existingActivity['ID'];
	}
}
