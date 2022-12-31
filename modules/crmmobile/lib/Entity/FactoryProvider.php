<?php

namespace Bitrix\CrmMobile\Entity;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Settings\LeadSettings;

final class FactoryProvider
{
	/**
	 * @return Factory[]
	 */
	public static function getAvailableFactories(): array
	{
		$factories = Container::getInstance()->getTypesMap()->getFactories();

		$factories = static::filterSupportedFactories($factories);
		$factories = static::filterPermittedFactories($factories);

		return $factories;
	}

	/**
	 * @return Factory[]
	 */
	public static function getFactoriesMetaData(): array
	{
		$result = [];

		$factories = Container::getInstance()->getTypesMap()->getFactories();
		$userPermissions = Container::getInstance()->getUserPermissions();
		$supportedEntityNames = static::getSupportedEntityNames();

		foreach ($factories as $factory)
		{
			$entityTypeId = $factory->getEntityTypeId();
			if (!$userPermissions->checkReadPermissions($entityTypeId))
			{
				continue;
			}

			$entityTypeName = $factory->getEntityName();
			$result[] = [
				'entityTypeId' => $entityTypeId,
				'entityTypeName' => $entityTypeName,
				'title' => $factory->getEntityDescription(),
				'supported' => in_array($entityTypeName, $supportedEntityNames, true),
			];
		}

		return $result;
	}

	public static function getSupportedEntityNames(): array
	{
		$entities = [];

		if (LeadSettings::isEnabled())
		{
			$entities[] = \CCrmOwnerType::LeadName;
		}

		return array_merge(
			$entities,
			[
				\CCrmOwnerType::DealName,
				\CCrmOwnerType::ContactName,
				\CCrmOwnerType::CompanyName,
			]
		);
	}

	private static function filterSupportedFactories(array $factories): array
	{
		$factoryMap = [];
		foreach ($factories as $factory)
		{
			$factoryMap[$factory->getEntityName()] = $factory;
		}

		$result = [];

		foreach (static::getSupportedEntityNames() as $entityName)
		{
			$factory = $factoryMap[$entityName] ?? null;
			if ($factory)
			{
				$result[] = $factory;
			}
		}

		return $result;
	}

	private static function filterPermittedFactories(array $factories): array
	{
		$result = [];
		$userPermissions = Container::getInstance()->getUserPermissions();

		foreach ($factories as $factory)
		{
			if ($userPermissions->checkReadPermissions($factory->getEntityTypeId()))
			{
				$result[] = $factory;
			}
		}

		return $result;
	}
}
