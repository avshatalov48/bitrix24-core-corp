<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\ActivityController;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class Document extends Base
{
	public const PROVIDER_ID = 'CRM_DOCUMENT';
	public const PROVIDER_TYPE_ID_DOCUMENT = 'DOCUMENT';

	public static function getId(): string
	{
		return static::PROVIDER_ID;
	}

	public static function isActive(): bool
	{
		return DocumentGeneratorManager::getInstance()->isEnabled();
	}

	public static function getName(): ?string
	{
		Container::getInstance()->getLocalization()->loadMessages();
		return Loc::getMessage('CRM_COMMON_DOCUMENT');
	}

	public static function getTypes(): array
	{
		return [
			[
				'NAME' => self::getName(),
				'PROVIDER_ID' => static::PROVIDER_ID,
				'PROVIDER_TYPE_ID' => static::PROVIDER_TYPE_ID_DOCUMENT,
			],
		];
	}

	public static function isTypeEditable($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined): bool
	{
		return false;
	}

	public static function checkPostponePermission($entityId, array $activity, $userId): bool
	{
		return false;
	}

	public static function checkReadPermission(array $activityFields, $userId = null)
	{
		if (!parent::checkReadPermission($activityFields, $userId))
		{
			return false;
		}

		$userId = is_numeric($userId) ? (int)$userId : null;
		if ($userId <= 0)
		{
			$userId = null;
		}

		return (
			DocumentGeneratorManager::getInstance()->isEnabled()
			&& Driver::getInstance()->getUserPermissions($userId)->canViewDocuments()
		);
	}

	public static function checkUpdatePermission(array $activityFields, $userId = null)
	{
		//this activity type is not editable
		return false;
	}

	public static function deleteAssociatedEntity($entityId, array $activity, array $options = []): Result
	{
		$isErasingFromRecycleBin = (bool)($options['IS_ERASING_FROM_RECYCLE_BIN'] ?? false);
		if (
			$isErasingFromRecycleBin
			&& DocumentGeneratorManager::getInstance()->isEnabled()
		)
		{
			return \Bitrix\DocumentGenerator\Model\DocumentTable::delete($entityId);
		}

		return new Result();
	}

	public static function checkFields($action, &$fields, $id, $params = null): Result
	{
		$result = new Result();

		$fields['PROVIDER_TYPE_ID'] = self::PROVIDER_TYPE_ID_DOCUMENT;

		if ($action === self::ACTION_ADD)
		{
			$documentId = (int)($fields['ASSOCIATED_ENTITY_ID'] ?? 0);
			if ($documentId <= 0)
			{
				return $result->addError(new Error('ASSOCIATED_ENTITY_ID is required for ' . self::class));
			}

			$anotherActivityForSameDocument = self::getActivityByAssociatedEntity($documentId);
			if (!empty($anotherActivityForSameDocument))
			{
				return $result->addError(new Error('Every document can have only one ' . self::class . ' activity bound to it'));
			}
		}

		return $result;
	}

	private static function getActivityByAssociatedEntity(int $associatedEntityId): ?array
	{
		$activity = \CCrmActivity::GetList(
			[],
			[
				'TYPE_ID' => \CCrmActivityType::Provider,
				'PROVIDER_ID' => self::getId(),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_DOCUMENT,
				'ASSOCIATED_ENTITY_ID' => $associatedEntityId,
			],
			false,
			false,
		)->Fetch();

		return is_array($activity) ? $activity : null;
	}

	public static function onDocumentUpdate(int $associatedEntityId): void
	{
		if ($associatedEntityId <= 0)
		{
			throw new ArgumentOutOfRangeException("associatedEntityId", 1);
		}

		$activity = self::getActivityByAssociatedEntity($associatedEntityId);
		if (!$activity)
		{
			return;
		}

		ActivityController::getInstance()->notifyTimelinesAboutActivityUpdate($activity);
	}
}
