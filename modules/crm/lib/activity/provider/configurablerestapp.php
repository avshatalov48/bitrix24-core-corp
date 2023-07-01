<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Badge;
use Bitrix\Crm\Badge\Model\BadgeTable;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class ConfigurableRestApp extends Base
{
	public const PROVIDER_ID = 'CONFIGURABLE_REST_APP';
	public const PROVIDER_TYPE_ID_DEFAULT = 'CONFIGURABLE';

	public static function getId()
	{
		return self::PROVIDER_ID;
	}

	public static function getTypeId(array $activity)
	{
		return $activity['PROVIDER_TYPE_ID'] ?? '';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_CONFIGURABLE_REST_APP_NAME');
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
	 * @return array
	 */
	public static function getTypesFilterPresets()
	{
		return [
			[
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_CONFIGURABLE_REST_APP_NAME'),
			],
		];
	}

	public static function getTypes()
	{
		return [
			[
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_CONFIGURABLE_REST_APP_NAME'),
				'PROVIDER_ID' => self::getId(),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_DEFAULT
			]
		];
	}

	public static function getTypeName($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_CONFIGURABLE_REST_APP_NAME');
	}

	public static function renderView(array $activity)
	{
		return '';
	}

	public static function checkFields($action, &$fields, $id, $params = null)
	{
		if (isset($fields['END_TIME']) && $fields['END_TIME'] != '')
		{
			$fields['DEADLINE'] = $fields['END_TIME'];
		}

		return new Result();
	}

	/**
	 * @inheritdoc
	 */
	public static function syncBadges(int $activityId, array $activityFields, array $rawBindings): void
	{
		$badgeType = (string)($activityFields['PROVIDER_PARAMS']['badgeCode'] ?? '');
		if ($badgeType !== '')
		{
			if (!\Bitrix\Crm\Badge\Model\CustomBadgeTable::getByCode($badgeType))
			{
				$badgeType = '';
			}
		}
		$bindings = [];
		foreach ($rawBindings as $rawBinding)
		{
			$ownerTypeId = (int)$rawBinding['OWNER_TYPE_ID'];
			$ownerId = (int)$rawBinding['OWNER_ID'];
			if (\CCrmOwnerType::IsDefined($ownerTypeId) && $ownerId > 0)
			{
				$bindings[] =  new ItemIdentifier($ownerTypeId, $ownerId);
			}
		}

		$sourceIdentifier = new Badge\SourceIdentifier(
			Badge\SourceIdentifier::CRM_OWNER_TYPE_PROVIDER,
			\CCrmOwnerType::Activity,
			$activityId,
		);
		$existedBadges = [];
		$existedBadgesIterator = BadgeTable::query()
			->where('TYPE', Badge\Type\RestAppStatus::REST_APP_TYPE)
			->where('SOURCE_PROVIDER_ID', $sourceIdentifier->getProviderId())
			->where('SOURCE_ENTITY_TYPE_ID', $sourceIdentifier->getEntityTypeId())
			->where('SOURCE_ENTITY_ID', $sourceIdentifier->getEntityId())
			->setSelect([
				'ID',
				'VALUE',
				'ENTITY_TYPE_ID',
				'ENTITY_ID',
			])
			->exec()
		;
		while($existedBadge = $existedBadgesIterator->fetch())
		{
			$existedBadges[$existedBadge['ENTITY_TYPE_ID']][$existedBadge['ENTITY_ID']] = $existedBadge['VALUE'];
		}

		$isCompleted = isset($activityFields['COMPLETED']) && $activityFields['COMPLETED'] === 'Y';
		if ($isCompleted || $badgeType === '') // remove existed badges
		{
			foreach ($existedBadges as $existedBadgeEntityTypeId => $byTypeId)
			{
				foreach ($byTypeId as $existedBadgeEntityId => $value)
				{
					$existedBadge = Container::getInstance()->getBadge(
						Badge\Type\RestAppStatus::REST_APP_TYPE,
						$value,
					);
					$itemIdentifier = new ItemIdentifier((int)$existedBadgeEntityTypeId, (int)$existedBadgeEntityId);
					$existedBadge->unbind($itemIdentifier, $sourceIdentifier);
				}
			}

			return;
		}
		$newBadge = Container::getInstance()->getBadge(
			Badge\Type\RestAppStatus::REST_APP_TYPE,
			$badgeType
		);

		foreach ($bindings as $singleBinding)
		{
			$existedBadgeValue = $existedBadges[$singleBinding->getEntityTypeId()][$singleBinding->getEntityId()] ?? null;
			if ($existedBadgeValue === $badgeType) // existed record was not changed
			{
				continue;
			}
			if ($existedBadgeValue)
			{
				$existedBadge = Container::getInstance()->getBadge(
					Badge\Type\RestAppStatus::REST_APP_TYPE,
					$existedBadgeValue,
				);
				$existedBadge->unbind($singleBinding, $sourceIdentifier);
			}

			$newBadge->bind($singleBinding, $sourceIdentifier);
		}
	}
}
