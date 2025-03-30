<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Booking extends Activity\Provider\Base
{
	private const PROVIDER_TYPE_DEFAULT = 'BOOKING';

	public static function getId(): string
	{
		return 'CRM_BOOKING';
	}

	public static function getTypeId(array $activity): string
	{
		return self::PROVIDER_TYPE_DEFAULT;
	}

	public static function getTypes(): array
	{
		return [
			[
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_BOOKING_TYPE_DEFAULT_NAME'),
				'PROVIDER_ID' => self::getId(),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_DEFAULT,
			],
		];
	}

	public static function getName(): string
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_BOOKING_NAME');
	}

	public static function getTypeName($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined): string
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_BOOKING_TYPE_DEFAULT_NAME');
	}

	public static function getFieldsForEdit(array $activity): array
	{
		return [];
	}

	public static function onBookingAdded(array $booking): int|null
	{
		if (empty($booking['id']))
		{
			return null;
		}

		$bindings = self::makeBindings($booking);

		if (empty($bindings))
		{
			return null;
		}

		$typeId = self::PROVIDER_TYPE_DEFAULT;
		$associatedEntityId = $booking['id'];

		$existingActivity = \CCrmActivity::getList(
			[],
			[
				'=PROVIDER_ID' => self::getId(),
				'=PROVIDER_TYPE_ID' => $typeId,
				'=ASSOCIATED_ENTITY_ID' => $associatedEntityId,
				'=COMPLETED' => 'N',
				'CHECK_PERMISSIONS' => 'N',
			]
		)->fetch();

		if ($existingActivity)
		{
			$existingActivity['BINDINGS'] = $bindings;
			$existingActivity['SETTINGS']['FIELDS'] = $booking;

			$updated = \CCrmActivity::update($existingActivity['ID'], $existingActivity, false);

            if ($updated)
            {
                self::sendPullEventOnUpdate($existingActivity);
            }

            return $existingActivity['ID'];
		}

		$authorId = $booking['createdBy'];

		$fields = [
			'TYPE_ID' => \CCrmActivityType::Provider,
			'PROVIDER_ID' => self::getId(),
			'PROVIDER_TYPE_ID' => $typeId,
			'ASSOCIATED_ENTITY_ID' => $associatedEntityId,
			'SUBJECT' => self::getActivitySubject($booking, $typeId),
			'IS_HANDLEABLE' => 'Y',
			'IS_INCOMING_CHANNEL' => 'N',
			'COMPLETED' => 'N',
			'STATUS' => \CCrmActivityStatus::Waiting,
			'RESPONSIBLE_ID' => $authorId,
			'PRIORITY' => \CCrmActivityPriority::Medium,
			'AUTHOR_ID' => $authorId,
			'BINDINGS' => $bindings,
			'SETTINGS' => [
				'FIELDS' => $booking,
			],
		];

		$activityId = (int)\CCrmActivity::add($fields, false);

		if ($activityId)
		{
            self::sendPullEventOnAdd(['ID' => $activityId, ...$fields]);
			(new TimeLine\Booking\Controller())->onBookingCreated($bindings, $booking);
		}

		return $activityId;
	}

	public static function onBookingUpdated(array $booking): int|null
	{
		return self::onBookingAdded($booking);
	}

	public static function onBookingDeleted(int $bookingId): void
	{
		$activitiesList = \CCrmActivity::getList(
			[],
			[
				'=PROVIDER_ID' => self::getId(),
				'=PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_DEFAULT,
				'=ASSOCIATED_ENTITY_ID' => $bookingId,
				'=COMPLETED' => 'N',
				'CHECK_PERMISSIONS' => 'N',
			]
		);
		while ($activity = $activitiesList->fetch())
		{
			$deleted = \CCrmActivity::Delete($activity['ID'], false);

            if ($deleted)
            {
                self::sendPullEventOnDelete($activity);
            }
		}
	}

	private static function getActivitySubject(array $booking, string $typeId): string
	{
		return sprintf('%s: %s', self::getTypeName($typeId), $booking['name']);
	}

    private static function sendPullEventOnAdd(array $activity): void
    {
        $activityController = Timeline\ActivityController::getInstance();

        foreach ($activity['BINDINGS'] as $binding)
        {
            $identifier = new ItemIdentifier($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']);

            $activityController->sendPullEventOnAddScheduled($identifier, $activity);
        }
    }

    private static function sendPullEventOnUpdate(array $activity): void
    {
        $activityController = Timeline\ActivityController::getInstance();

        foreach ($activity['BINDINGS'] as $binding)
        {
            $identifier = new ItemIdentifier($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']);

            $activityController->sendPullEventOnUpdateScheduled($identifier, $activity);
        }
    }

    private static function sendPullEventOnDelete(array $activity): void
    {
        $activityController = Timeline\ActivityController::getInstance();

        foreach ($activity['BINDINGS'] as $binding)
        {
            $identifier = new ItemIdentifier($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']);

            $activityController->sendPullEventOnDelete($identifier, $activity['ID']);
        }
    }

	private static function makeBindings(array $booking): array
	{
		$bindings = [];

		foreach ($booking['clients'] as $client)
		{
			$clientTypeModule = $client['type']['module'] ?? '';
			$clientTypeCode = $client['type']['code'] ?? '';

			if ($clientTypeModule !== 'crm')
			{
				continue;
			}

			$ownerTypeId = \CCrmOwnerType::ResolveID($clientTypeCode);
			if (!in_array($ownerTypeId, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company], true))
			{
				continue;
			}

			$ownerId = isset($client['id']) ? (int)$client['id'] : 0;
			if (!$ownerId)
			{
				continue;
			}

			$bindings[] = [
				'OWNER_TYPE_ID' => $ownerTypeId,
				'OWNER_ID' => $ownerId,
			];
		}

		foreach ($booking['externalData'] as $externalData)
		{
			$isCrm = isset($externalData['moduleId']) && $externalData['moduleId'] === 'crm';
			$ownerTypeId = \CCrmOwnerType::ResolveID($externalData['entityTypeId']);
			$ownerId = isset($externalData['value']) ? (int)$externalData['value'] : 0;

			if (
				$isCrm
				&& (
					$ownerTypeId === \CCrmOwnerType::Deal
					|| \CCrmOwnerType::isPossibleDynamicTypeId($ownerTypeId)
				)
				&& $ownerId
			)
			{
				$bindings[] = [
					'OWNER_TYPE_ID' => $ownerTypeId,
					'OWNER_ID' => $ownerId,
				];
			}
		}

		return $bindings;
	}
}
