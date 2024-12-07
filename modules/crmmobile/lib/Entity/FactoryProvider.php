<?php

namespace Bitrix\CrmMobile\Entity;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Main\Loader;

final class FactoryProvider
{
	/**
	 * @return Factory[]
	 */
	public static function getAvailableFactories(): array
	{
		$factories = Container::getInstance()->getTypesMap()->getFactories();

		$factories = self::filterSupportedFactories($factories);
		$factories = self::filterPermittedFactories($factories);

		return $factories;
	}

	/**
	 * @return Factory[]
	 */
	public static function getFactoriesMetaData(): array
	{
		if (self::isExtranetUser())
		{
			return [];
		}

		$result = [];

		$factories = Container::getInstance()->getTypesMap()->getFactories();
		$userPermissions = Container::getInstance()->getUserPermissions();
		$supportedEntityTypeIds = self::getSupportedEntityTypeIds();

		foreach ($factories as $factory)
		{
			$entityTypeId = $factory->getEntityTypeId();
			$categoryId = self::getCategoryIdForCheckPermission($factory);

			if (!$userPermissions->checkReadPermissions($entityTypeId, 0, $categoryId))
			{
				continue;
			}

			$result[] = [
				'entityTypeId' => $entityTypeId,
				'entityTypeName' => $factory->getEntityName(),
				'title' => $factory->getEntityDescription(),
				'supported' => in_array($entityTypeId, $supportedEntityTypeIds, true),
				'restricted' => RestrictionManager::isEntityRestricted($entityTypeId),
			];
		}

		return $result;
	}

	public static function getSupportedEntityTypeIds(): array
	{
		$entities = [];
		$dynamicEntities = [];

		if (LeadSettings::isEnabled())
		{
			$entities[] = \CCrmOwnerType::Lead;
		}

		if (Crm::isMobileDynamicTypesEnabled())
		{
			$dynamicTypesMap = Container::getInstance()->getDynamicTypesMap()->load([
				'isLoadStages' => false,
				'isLoadCategories' => false,
			]);

			$dynamicEntities = array_map(
				static fn ($type) => $type->getEntityTypeId(),
				$dynamicTypesMap->getTypes()
			);
		}

		return array_merge(
			$entities,
			[
				\CCrmOwnerType::Deal,
				\CCrmOwnerType::Contact,
				\CCrmOwnerType::Company,
				\CCrmOwnerType::SmartInvoice,
				\CCrmOwnerType::Quote,
			],
			$dynamicEntities
		);
	}

	private static function filterSupportedFactories(array $factories): array
	{
		$factoryMap = [];
		foreach ($factories as $factory)
		{
			$factoryMap[$factory->getEntityTypeId()] = $factory;
		}

		$result = [];

		foreach (self::getSupportedEntityTypeIds() as $entityTypeId)
		{
			$factory = $factoryMap[$entityTypeId] ?? null;
			if ($factory)
			{
				$result[] = $factory;
			}
		}

		return $result;
	}

	private static function filterPermittedFactories(array $factories): array
	{
		if (self::isExtranetUser())
		{
			return [];
		}

		$result = [];

		$userPermissions = Container::getInstance()->getUserPermissions();

		foreach ($factories as $factory)
		{
			$categoryId = self::getCategoryIdForCheckPermission($factory);
			if ($userPermissions->checkReadPermissions($factory->getEntityTypeId(), 0, $categoryId))
			{
				$result[] = $factory;
			}
		}

		return $result;
	}

	private static function getCategoryIdForCheckPermission(Factory $factory): ?int
	{
		return (
			$factory->getEntityTypeId() === \CCrmOwnerType::Contact
			|| $factory->getEntityTypeId() === \CCrmOwnerType::Company
				? $factory->getDefaultCategory()->getId()
				: null
		);
	}

	private static function isExtranetUser()
	{
		return Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser();
	}
}
