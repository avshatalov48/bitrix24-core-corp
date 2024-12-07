<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder\Conditions;

use Bitrix\Crm\Observer\ObserverRepository;
use Bitrix\Crm\Security\Controller;

class ObserverConditionsBuilder
{
	private ObserverRepository $observerRepository;

	public function __construct(
		private Controller\Base $controller
	)
	{
		$this->observerRepository = ObserverRepository::getInstance();
	}

	/**
	 * @param int $userId
	 * @param string[] $permissionEntityTypes entity types code with category like LEAD, DEAL_C12
	 * @return ObserversCondition[]
	 */
	public function build(int $userId, array $permissionEntityTypes): array
	{
		if (empty($permissionEntityTypes))
		{
			return [];
		}

		$categoryIdMap = [];
		$hasCategories = $this->controller->hasCategories();
		foreach ($permissionEntityTypes as $permissionEntityType)
		{
			if (!$this->controller->isObservable())
			{
				continue;
			}

			$entityTypeID = $this->controller->getEntityTypeId();
			if (!isset($categoryIdMap[$entityTypeID]))
			{
				$categoryIdMap[$entityTypeID] = [];
			}

			$categoryIdMap[$entityTypeID][] = $hasCategories
				? $this->controller->extractCategoryId($permissionEntityType)
				: null;
		}

		$observerConditions = [];
		foreach ($categoryIdMap as $entityTypeID => $categoryIds)
		{
			if (!$this->observerRepository->isUsersPresentAsObservers($userId, $entityTypeID))
			{
				continue;
			}

			$categoryIds = array_filter($categoryIds, fn($cat) => $cat !== null);
			$categoryIds = array_unique($categoryIds);

			$observerConditions[] = new ObserversCondition($entityTypeID, $userId, $categoryIds);
		}

		return $observerConditions;

	}
}