<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Counter\EntityCounterManager;
use Bitrix\Crm\Integration\Intranet\CustomSectionProvider;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory\Dynamic;

class Counter extends Base
{
	public function listAction(
		int $entityTypeId,
		?array $extras = null,
		bool $withExcludeUsers = false
	): ?array
	{
		if (!is_array($extras))
		{
			$extras = [];
		}

		if (!\CCrmOwnerType::IsDefined($entityTypeId))
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError($entityTypeId));

			return null;
		}
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError($entityTypeId));

			return null;
		}
		$categoryId = $extras['CATEGORY_ID'] ?? $extras['DEAL_CATEGORY_ID'] ?? null;
		$categoryId = is_null($categoryId) ? null : (int)$categoryId;
		if (!Container::getInstance()->getUserPermissions()->checkReadPermissions($entityTypeId, 0, $categoryId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		if (!$factory->getCountersSettings()->isCountersEnabled())
		{
			return [];
		}

		$sanitizedExtras = [];
		if (array_key_exists('CATEGORY_ID', $extras))
		{
			$sanitizedExtras['CATEGORY_ID'] = (int)$extras['CATEGORY_ID'];
		}
		if (array_key_exists('DEAL_CATEGORY_ID', $extras))
		{
			$sanitizedExtras['DEAL_CATEGORY_ID'] = (int)$extras['DEAL_CATEGORY_ID'];
		}
		$currentUserId = $this->getCurrentUser()->getId();
		$enabledCounterTypes = $factory->getCountersSettings()->getEnabledCountersTypes();

		if ($factory instanceof Dynamic && CustomSectionProvider::hasCustomSection($factory))
		{
			$settingsName = IntranetManager::preparePageSettingsForItemsList($factory->getEntityTypeId());
			CustomSectionProvider::getAllCustomSectionIdsByEntityTypeId($factory->getEntityTypeId());
			EntityCounterManager::prepareValue(CustomSectionProvider::COUNTER_PREFIX . $settingsName);

			$sectionIds = CustomSectionProvider::getAllCustomSectionIdsByEntityTypeId($factory->getEntityTypeId());
			foreach ($sectionIds as $sectionId)
			{
				EntityCounterManager::prepareValue(
					CustomSectionProvider::buildCustomSectionCounterId($sectionId)
				);
			}
		}

		$result = [];
		foreach($enabledCounterTypes as $typeId)
		{
			$counter = EntityCounterFactory::create($entityTypeId, $typeId, $currentUserId, $sanitizedExtras);
			$result[$counter->getCode()] = $counter->getValue();
		}
		// if $sanitizedExtras defines counter category, also refresh counter for all categories:
		if (in_array(\Bitrix\Crm\Counter\EntityCounterType::ALL, $enabledCounterTypes, true))
		{
			$counter = EntityCounterFactory::create($entityTypeId, \Bitrix\Crm\Counter\EntityCounterType::ALL, $currentUserId);
			$result[$counter->getCode()] = $counter->getValue();
		}

		if ($withExcludeUsers)
		{
			$excludeUsersExtras = $sanitizedExtras;
			$excludeUsersExtras['EXCLUDE_USERS'] = true;
			foreach($enabledCounterTypes as $typeId)
			{
				$counter = EntityCounterFactory::create($entityTypeId, $typeId, $currentUserId, $excludeUsersExtras);
				$result[$counter->getCode()] = $counter->getValue();
			}
		}

		$counter = EntityCounterFactory::createNamed(\CCrmSaleHelper::isWithOrdersMode()
			? \Bitrix\Crm\Counter\EntityCounterFactory::TOTAL_COUNTER
			: \Bitrix\Crm\Counter\EntityCounterFactory::NO_ORDERS_COUNTER
		);
		if($counter !== null)
		{
			$result[$counter->getCode()] = $counter->getValue();
		}

		return $result;
	}

}
