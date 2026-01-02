<?php

declare(strict_types=1);

namespace Bitrix\Crm\Activity\Provider\Booking;

use Bitrix\Crm\Activity;
use Bitrix\Crm\Dto\Booking\Booking\BookingFields;
use Bitrix\Crm\Dto\Booking\Booking\BookingStatusEnum;
use Bitrix\Crm\Dto\Booking\Message\Message;
use Bitrix\Crm\Integration\Booking\BookingProvider;
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

	public static function onBookingAdded(BookingFields $booking): int|null
	{
		if (empty($booking->id))
		{
			return null;
		}

		$typeId = self::PROVIDER_TYPE_DEFAULT;

		$bindings = BookingCommon::makeBindings($booking);

		if (empty($bindings))
		{
			return null;
		}

		$activityId = self::updateActivity(
			booking: $booking,
			bindings: $bindings,
		);
		if ($activityId)
		{
			return $activityId;
		}

		$settings =  [
			'FIELDS' => $booking->toArray(),
		];
		$status = self::calculateStatusOnUpdate($booking);
		if ($status)
		{
			$settings['STATUS'] = $status->value;
		}

		$activityId = BookingCommon::createActivity(
			providerId: self::getId(),
			typeId: $typeId,
			entity: $booking,
			bindings: $bindings,
			subject: BookingCommon::getActivitySubject(
				self::getTypeName($typeId),
				$booking->name ?: Loc::getMessage('CRM_ACTIVITY_PROVIDER_BOOKING_NAME'),
			),
			settings: $settings,
		);

		if (!$activityId)
		{
			return null;
		}

		(new TimeLine\Booking\Controller())->onBookingCreated($bindings, $booking);

		return $activityId;
	}

	public static function onBookingUpdated(BookingFields $booking): int|null
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
				BookingCommon::sendPullEventOnDelete($activity);
			}

			$statusData = $activity['SETTINGS']['STATUS'] ?? null;
			$status = $statusData ? BookingStatusEnum::tryFrom($statusData) : null;
			(new BookingBadge())->clearKanbanBadge($bookingId, $status);
		}
	}

	public static function updateActivityCompleteStatus(int $activityId, string $completeStatus): bool
	{
		$activitiesList = \CCrmActivity::getList(
			[],
			[
				'=ID' => $activityId,
				'=PROVIDER_ID' => self::getId(),
				'=PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_DEFAULT,
				'=COMPLETED' => 'Y',
			]
		);

		if (!$activity = $activitiesList->fetch())
		{
			return false;
		}

		return \CCrmActivity::Update($activityId, ['SETTINGS' => [
			...$activity['SETTINGS'],
			'COMPLETE_STATUS' => $completeStatus,
		]]);
	}

	public static function onBookingStatusUpdated(
		BookingFields $booking,
		BookingStatusEnum $status,
	): int|null
	{
		$activity = self::getNotCompletedBookingActivity($booking->getId());
		$statusUpdatedAt = time();

		if (!$activity)
		{
			(new BookingBadge())->updateOrClearBadge(
				booking: $booking,
				status: $status,
				statusUpdatedAt: $statusUpdatedAt,
			);

			return null;
		}

		$activity['SETTINGS']['STATUS'] = $status->value;
		$activity['SETTINGS']['STATUS_UPDATED'] = $statusUpdatedAt;

		$updated = \CCrmActivity::update($activity['ID'], $activity, false);

		if ($updated)
		{
			BookingCommon::sendPullEventOnUpdate($activity);
		}

		(new BookingBadge())->updateKanbanBadgeByActivityData($activity['SETTINGS'] ?? null);

		return (int)$activity['ID'];
	}

	public static function onBookingMessageUpdated(
		BookingFields $booking,
		Message $message,
	): int|null
	{
		$activity = self::getNotCompletedBookingActivity($booking->getId());

		if (!$activity)
		{
			(new BookingBadge())->clearKanbanBadge($booking->getId());

			return null;
		}

		$activity['SETTINGS']['MESSAGE'] = $message->toArray();

		$updated = \CCrmActivity::update($activity['ID'], $activity, false);

		if ($updated)
		{
			BookingCommon::sendPullEventOnUpdate($activity);
		}

		(new BookingBadge())->updateKanbanBadgeByActivityData($activity['SETTINGS'] ?? null);

		return (int)$activity['ID'];
	}

	private static function calculateStatusOnUpdate(
		BookingFields $booking,
		string|null $storedStatus = null,
	): BookingStatusEnum|null
	{
		$bookingStatus = BookingStatusEnum::tryFrom($storedStatus ?? '');
		switch ($bookingStatus)
		{
			case BookingStatusEnum::DelayedCounterActivated:
				$isDelayed = (new BookingProvider())
					->isBookingDelayed($booking->getId())
				;
				// if for some reason booking not defined, skip check
				if ($isDelayed === null)
				{
					return $bookingStatus;
				}

				return $isDelayed ? $bookingStatus : null;
			case BookingStatusEnum::ConfirmedByManager:
			case BookingStatusEnum::ConfirmedByClient:
				if (!$booking->isConfirmed)
				{
					return null;
				}
				break;
			case null:
				if ($booking->isConfirmed)
				{
					return BookingStatusEnum::ConfirmedByManager;
				}
				break;
		}

		return $bookingStatus;
	}

	private static function updateActivity(
		BookingFields $booking,
		array $bindings,
	): int|null
	{
		$existingActivity = self::getNotCompletedBookingActivity($booking->getId());

		if (!$existingActivity)
		{
			return null;
		}

		$existingActivity['BINDINGS'] = $bindings;
		$existingActivity['SETTINGS']['FIELDS'] = $booking->toArray();
		$status = self::calculateStatusOnUpdate(
			$booking,
			$existingActivity['SETTINGS']['STATUS'] ?? null,
		);
		if ($status)
		{
			$existingActivity['SETTINGS']['STATUS'] = $status->value;
			(new BookingBadge())->updateKanbanBadgeByActivityData($existingActivity['SETTINGS'] ?? null);
		}
		else
		{
			unset($existingActivity['SETTINGS']['STATUS']);
			(new BookingBadge())->clearKanbanBadge($booking->getId());
		}

		$updated = \CCrmActivity::update($existingActivity['ID'], $existingActivity, false);

		if ($updated)
		{
			BookingCommon::sendPullEventOnUpdate($existingActivity);
		}

		return (int)$existingActivity['ID'];
	}

	public static function onBeforeComplete(int $id, array $activityFields, array $params = null): void
	{
		$existingActivity = \CCrmActivity::getList(
			[],
			[
				'ID'=> $id,
				'=PROVIDER_ID' => self::getId(),
				'=PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_DEFAULT,
				'=COMPLETED' => 'N',
				'CHECK_PERMISSIONS' => 'N',
			]
		)?->fetch();

		if (!$existingActivity)
		{
			return;
		}

		$bookingId = $existingActivity['SETTINGS']['FIELDS']['id'] ?? null;
		if ($bookingId)
		{
			(new BookingBadge())->clearKanbanBadge($bookingId);
		}

		parent::onBeforeComplete($id, $activityFields, $params);
	}

	private static function getNotCompletedBookingActivity(int $associatedEntityId): array|null
	{
		return \CCrmActivity::getList(
			[],
			[
				'=PROVIDER_ID' => self::getId(),
				'=PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_DEFAULT,
				'=ASSOCIATED_ENTITY_ID' => $associatedEntityId,
				'=COMPLETED' => 'N',
				'CHECK_PERMISSIONS' => 'N',
			]
		)?->fetch() ?: null;
	}
}
