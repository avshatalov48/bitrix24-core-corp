<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\ActivityController;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class SignB2eDocument extends Base
{
	public const PROVIDER_ID = 'CRM_SIGN_B2E_DOCUMENT';
	public const PROVIDER_TYPE_ID_SIGN = 'SIGN_B2E_DOCUMENT';

	public static function getId(): string
	{
		return self::PROVIDER_ID;
	}

	public static function isActive(): bool
	{
		return \Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled();
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
				'PROVIDER_ID' => self::PROVIDER_ID,
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_SIGN,
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

	public static function checkReadPermission(array $activityFields, $userId = null): bool
	{
		if (!parent::checkReadPermission($activityFields, $userId))
		{
			return false;
		}

		$documentId = (int)($activityFields['ASSOCIATED_ENTITY_ID'] ?? 0);
		$userId = is_numeric($userId) ? (int)$userId : null;
		if ($userId <= 0)
		{
			$userId = null;
		}

		$userPermissions = Container::getInstance()->getUserPermissions($userId);

		return $userPermissions->checkReadPermissions(\CCrmOwnerType::SmartB2eDocument, $documentId);
	}

	public static function checkUpdatePermission(array $activityFields, $userId = null): bool
	{
		return parent::checkUpdatePermission($activityFields, $userId);
	}

	public static function checkFields($action, &$fields, $id, $params = null): Result
	{
		$result = new Result();

		$fields['PROVIDER_TYPE_ID'] = self::PROVIDER_TYPE_ID_SIGN;

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

		if ($action === self::ACTION_UPDATE)
		{
			if (isset($fields['END_TIME']) && $fields['END_TIME'] != '')
			{
				$fields['DEADLINE'] = $fields['END_TIME'];
			}
			elseif (isset($fields['~END_TIME']) && $fields['~END_TIME'] !== '')
			{
				$fields['~DEADLINE'] = $fields['~END_TIME'];
			}
		}

		return $result;
	}

	public static function getActivityByAssociatedEntity(int $associatedEntityId, bool $checkPermissions = true): ?array
	{
		$activity = \CCrmActivity::GetList(
			[],
			[
				'TYPE_ID' => \CCrmActivityType::Provider,
				'PROVIDER_ID' => self::getId(),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_SIGN,
				'ASSOCIATED_ENTITY_ID' => $associatedEntityId,
				'CHECK_PERMISSIONS' => $checkPermissions ? 'Y' : 'N',
			],
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

	public static function isCompletable()
	{
		return false;
	}
}
