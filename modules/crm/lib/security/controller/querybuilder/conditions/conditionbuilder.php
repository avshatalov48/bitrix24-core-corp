<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder\Conditions;

use Bitrix\Crm\Security\AccessAttribute\Collection;
use Bitrix\Crm\Security\Controller;
use Bitrix\Crm\Security\Controller\QueryBuilder\RestrictionByAttributes\RestrictionAttributesFactory;
use Bitrix\Crm\Security\Controller\QueryBuilder\RestrictionByAttributes\RestrictionsByAttributes;
use Bitrix\Crm\Security\QueryBuilder\QueryBuilderOptions;
use Bitrix\Main;

class ConditionBuilder
{
	private RestrictionAttributesFactory $restrictionAttributesFactory;

	private RestrictionsByAttributes $restrictedService;

	private ObserverConditionsBuilder $observerConditionsBuilder;

	public function __construct(
		private Controller\Base $controller,
	)
	{
		$this->restrictionAttributesFactory = RestrictionAttributesFactory::getInstance();
		$this->restrictedService = $this->restrictionAttributesFactory->make($this->controller);

		$this->observerConditionsBuilder = new ObserverConditionsBuilder($this->controller);
	}

	public function build(
		QueryBuilderOptions $options,
		Collection $attributes
	): RestrictedConditionsList
	{
		$restrictionMap = $this->restrictedService->getRestrictions($attributes, $options);

		$conditions = new RestrictedConditionsList();
		$isEntityWithCategories = $this->controller->hasCategories();
		$unRestrictedEntityTypes = [];
		$alwaysReadableConditionApplied = [];

		foreach ($restrictionMap as $restriction)
		{

			$entityTypes = $restriction['ENTITY_TYPES'] ?? [];
			$categoryIds = $restriction['CATEGORY_ID'] ?? [];
			$isOpened = isset($restriction['OPENED']) && $restriction['OPENED'];
			$categoriesCount = $isEntityWithCategories ? count($categoryIds) : 0;

			$resultCategoryIds = [];
			$resultUserIds = [];
			$resultProgressSteps = [];
			$resultIsOpened = $isOpened;

			if ($categoriesCount > 1)
			{
				$resultCategoryIds = $categoryIds;
			}
			elseif ($categoriesCount === 1)
			{
				$resultCategoryIds = [$categoryIds[0]];
				$resultProgressSteps = $restriction['PROGRESS_STEPS'] ?? [];
			}
			elseif (!$isEntityWithCategories)
			{
				$resultProgressSteps = $restriction['PROGRESS_STEPS'] ?? [];
			}

			$userIDs = $restriction['USER_IDS'] ?? [];

			$hasOnlyCategoryCondition = false;
			if (!$isOpened && empty($userIDs) && empty($resultProgressSteps))
			{
				if (count($resultCategoryIds) > 0)
				{
					$hasOnlyCategoryCondition = true;
				}
				$unRestrictedEntityTypes = array_merge($unRestrictedEntityTypes, $entityTypes);
			}
			else
			{
				if ($isOpened)
				{
					$resultIsOpened = true;
				}

				if (!empty($userIDs))
				{
					$resultUserIds = $userIDs;
				}
			}

			if ($options->isReadAllAllowed() && !$hasOnlyCategoryCondition && count($resultCategoryIds) > 0)
			{
				// Always readable condition must be added only one per categories combination.
				$catHash = implode('_', $resultCategoryIds);
				if (!in_array($catHash, $alwaysReadableConditionApplied, true))
				{
					$conditions->add(
						new AlwaysReadableCondition($resultCategoryIds)
					);
					$alwaysReadableConditionApplied[] = $catHash;
				}
			}

			if (
				empty($resultCategoryIds)
				&& empty($resultUserIds)
				&& empty($resultProgressSteps)
				&& empty($resultIsOpened)
			)
			{
				continue;
			}

			$condition = new UserAttributesCondition(
				$resultCategoryIds,
				$resultUserIds,
				$resultProgressSteps,
				$resultIsOpened
			);

			$conditions->add($condition);

		}

		if ($this->isUseObserverConditions())
		{
			$resultObserverConditions = $this->observerConditionsBuilder->build(
				$attributes->getUserId(),
				array_diff($attributes->getAllowedEntityTypes(), $unRestrictedEntityTypes)
			);

			foreach ($resultObserverConditions as $observerCondition)
			{
				$conditions->add($observerCondition);
			}
		}

		return $conditions;
	}

	private function isUseObserverConditions(): bool
	{
		return Main\Config\Option::get('crm', 'CRM_MOVE_OBSERVERS_TO_ACCESS_ATTR_IN_WORK', 'Y') === 'Y';
	}
}
