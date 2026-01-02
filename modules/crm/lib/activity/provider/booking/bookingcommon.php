<?php

declare(strict_types=1);

namespace Bitrix\Crm\Activity\Provider\Booking;

use Bitrix\Crm\Dto\Booking\EntityFieldsInterface;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline;

class BookingCommon
{
	public static function makeBindings(EntityFieldsInterface $entity): array
	{
		$bindings = [];

		foreach ($entity->getClients() as $client)
		{
			$clientTypeModule = $client->typeModule;
			$clientTypeCode = $client->typeCode;

			if ($clientTypeModule !== 'crm')
			{
				continue;
			}

			$ownerTypeId = \CCrmOwnerType::ResolveID($clientTypeCode);
			if (!in_array($ownerTypeId, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company], true))
			{
				continue;
			}

			$ownerId = $client->id;
			if (!$ownerId)
			{
				continue;
			}

			$bindings[] = [
				'OWNER_TYPE_ID' => (int)$ownerTypeId,
				'OWNER_ID' => (int)$ownerId,
			];
		}

		foreach ($entity->getExternalData() as $externalData)
		{
			$isCrm = $externalData->moduleId === 'crm';
			$ownerTypeId = \CCrmOwnerType::ResolveID($externalData->entityTypeId);
			$ownerId = $externalData->value;

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
					'OWNER_TYPE_ID' => (int)$ownerTypeId,
					'OWNER_ID' => (int)$ownerId,
				];
			}
		}

		return $bindings;
	}

	public static function getActivitySubject(string $typeName, string $name): string
	{
		return sprintf('%s: %s', $typeName, $name);
	}

	public static function sendPullEventOnAdd(array $activity): void
	{
		$activityController = Timeline\ActivityController::getInstance();

		foreach ($activity['BINDINGS'] as $binding)
		{
			$identifier = new ItemIdentifier((int)$binding['OWNER_TYPE_ID'], (int)$binding['OWNER_ID']);

			$activityController->sendPullEventOnAddScheduled($identifier, $activity);
		}
	}

	public static function sendPullEventOnUpdate(array $activity): void
	{
		$activityController = Timeline\ActivityController::getInstance();

		foreach ($activity['BINDINGS'] as $binding)
		{
			$identifier = new ItemIdentifier((int)$binding['OWNER_TYPE_ID'], (int)$binding['OWNER_ID']);

			$activityController->sendPullEventOnUpdateScheduled($identifier, $activity);
		}
	}

	public static function sendPullEventOnDelete(array $activity): void
	{
		$activityController = Timeline\ActivityController::getInstance();

		foreach ($activity['BINDINGS'] as $binding)
		{
			$identifier = new ItemIdentifier((int)$binding['OWNER_TYPE_ID'], (int)$binding['OWNER_ID']);

			$activityController->sendPullEventOnDelete($identifier, (int)$activity['ID']);
		}
	}

	public static function createActivity(
		string $providerId,
		string $typeId,
		EntityFieldsInterface $entity,
		array $bindings,
		string $subject,
		array $settings
	): int|null
	{
		$authorId = $entity->getCreatedBy();

		$fields = [
			'TYPE_ID' => \CCrmActivityType::Provider,
			'PROVIDER_ID' => $providerId,
			'PROVIDER_TYPE_ID' => $typeId,
			'ASSOCIATED_ENTITY_ID' => $entity->getId(),
			'SUBJECT' => $subject,
			'IS_HANDLEABLE' => 'Y',
			'IS_INCOMING_CHANNEL' => 'N',
			'COMPLETED' => 'N',
			'STATUS' => \CCrmActivityStatus::Waiting,
			'RESPONSIBLE_ID' => $authorId,
			'PRIORITY' => \CCrmActivityPriority::Medium,
			'AUTHOR_ID' => $authorId,
			'BINDINGS' => $bindings,
			'SETTINGS' => $settings,
		];

		$activityId = (int)\CCrmActivity::add($fields, false);

		if ($activityId)
		{
			self::sendPullEventOnAdd(['ID' => $activityId, ...$fields]);
		}

		return $activityId;
	}
}
