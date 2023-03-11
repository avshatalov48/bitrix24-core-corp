<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Action;

use Bitrix\Crm\Activity\TodoCreateNotification;
use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Settings\Crm;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Entity\FactoryProvider;
use Bitrix\CrmMobile\Kanban\Entity;
use Bitrix\CrmMobile\Kanban\GridId;
use Bitrix\Main\UI\Filter\Options;

class GetTabsAction extends Action
{
	public function run()
	{
		$this->checkModules();
		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		$userPermissions = Container::getInstance()->getUserPermissions();
		$crmPermissions = $userPermissions->getCrmPermissions();

		return [
			'tabs' => $this->getTabs($userPermissions),
			'permissions' => [
				'exclude' => !$crmPermissions->HavePerm('EXCLUSION', BX_CRM_PERM_NONE, 'WRITE'),
			],
		];
	}

	/**
	 * @param UserPermissions $userPermissions
	 * @return array
	 */
	private function getTabs(UserPermissions $userPermissions): array
	{
		$result = [];
		$searchRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getSearchLimitRestriction();
		$infoHelperId = $searchRestriction->getMobileInfoHelperId();

		foreach (FactoryProvider::getAvailableFactories() as $factory)
		{
			$entityTypeName = $factory->getEntityName();
			$entityTypeId = $factory->getEntityTypeId();
			$counter = EntityCounterFactory::create($factory->getEntityTypeId(), EntityCounterType::ALL)->getValue();

			$isDealEntity = ($entityTypeName === \CCrmOwnerType::DealName);

			$categoryId = ($factory->isCategoriesSupported() ? $this->getCurrentCategoryId($factory) : null);
			$categories = ($factory->isCategoriesSupported() ? $factory->getCategories() : []);
			$permissions = $this->getPermissions($userPermissions, $entityTypeId, $categoryId);

			$filterOptions = $this->getFilterOptions($factory, $categoryId);

			$result[] = [
				'id' => $entityTypeId,
				'typeName' => $entityTypeName,
				'selectable' => $entityTypeName !== \CCrmOwnerType::LeadName,
				'active' => false,
				'title' => $factory->getEntityDescription(),
				'titleInPlural' => $factory->getEntityDescriptionInPlural(),
				'link' => $this->getLinkToDesktop($entityTypeName, $categoryId),
				'pageUrl' => ($entityTypeName === \CCrmOwnerType::LeadName ? '/mobile/crm/lead/' : null),
				'label' => $counter > 0 ? (string)$counter : '',
				'isStagesEnabled' => $factory->isStagesEnabled(),
				'canUseCategoryId' => $isDealEntity,

				// @todo temporary support only deals,
				// in the feature will be use factory when smart processes will be support this too
				'needSaveCurrentCategoryId' => $isDealEntity,

				'data' => [
					'currentCategoryId' => $categoryId,
					'categoriesCount' => count($categories),
					'counters' => $this->getCounters($factory, $categoryId),
					'presetId' => $this->getCurrentFilterPresetId($filterOptions),
					'defaultFilterId' => $this->getDefaultFilterId($filterOptions),
					'sortType' => $this->getSortType($entityTypeName, $categoryId),
					'smartActivitySettings' => $this->getSmartActivitySettings($factory, $permissions),
				],
				'permissions' => $permissions,
				'restrictions' => [
					'search' => [
						'isExceeded' => $searchRestriction->isExceeded($entityTypeId),
						'infoHelperId' => $infoHelperId,
					],
				],
			];
		}

		$this->prepareActiveTab($result);

		return $result;
	}

	/**
	 * @param Factory $factory
	 * @return int
	 */
	private function getCurrentCategoryId(Factory $factory): int
	{
		if (!$factory->isCategoriesSupported())
		{
			return 0;
		}

		$category = $factory->getDefaultCategory();
		$currentCategoryId = ($category ? $category->getId() : 0);

		if (!$factory->isCategoriesEnabled())
		{
			return $currentCategoryId;
		}

		// @todo temporary support only deals,
		// in the feature will be use factory when smart processes will be support this too
		if ($factory->getEntityName() === \CCrmOwnerType::DealName)
		{
			$currentCategoryId = (int) \CUserOptions::GetOption(
				'crm',
				'current_deal_category',
				$currentCategoryId
			);
		}

		return ($currentCategoryId ?? $factory->getCategories()[0]->getId());
	}

	private function getSortType(string $entityTypeName, ?int $categoryId): ?string
	{
		// only deals support now
		if ($entityTypeName !== \CCrmOwnerType::DealName)
		{
			return null;
		}

		$instance = \Bitrix\Crm\Kanban\Entity::getInstance($entityTypeName);
		if ($instance)
		{
			if ($categoryId !== null)
			{
				$instance->setCategoryId($categoryId);
			}

			return $instance->getSortSettings()->getCurrentType();
		}

		return null;
	}

	private function getSmartActivitySettings(Factory $factory, array $permissions = []): ?array
	{
		if (empty($permissions['update']) || $factory->getEntityTypeId() !== \CCrmOwnerType::Deal)
		{
			return null;
		}

		$todoNotificationAvailable = Crm::isUniversalActivityScenarioEnabled() && $factory->isStagesEnabled();
		if (!$todoNotificationAvailable)
		{
			return null;
		}

		return [
			'skipped' => (new TodoCreateNotification($factory->getEntityTypeId()))->isSkipped(),
		];
	}

	/**
	 * @param string $entityType
	 * @param int|null $categoryId
	 * @return string
	 */
	private function getLinkToDesktop(string $entityType, ?int $categoryId): string
	{
		return Entity::getInstance($entityType)->getDesktopLink($categoryId);
	}

	/**
	 * @param Factory $factory
	 * @param int|null $categoryId
	 * @return array
	 */
	private function getCounters(Factory $factory, ?int $categoryId = null): array
	{
		$userId = (int) $this->getCurrentUser()->getId();
		return Entity::getInstance($factory->getEntityName())->getCounters($userId, $categoryId);
	}

	private function getCurrentFilterPresetId(Options $options): string
	{
		return $options->getCurrentFilterPresetId() ?? $options->getCurrentFilterId();
	}

	/**
	 * @param Options $options
	 * @return mixed
	 */
	private function getDefaultFilterId(Options $options)
	{
		return $options->getDefaultFilterId();
	}

	private function getFilterOptions(Factory $factory, ?int $categoryId = null): Options
	{
		if ($factory->isStagesSupported())
		{
			$entity = \Bitrix\Crm\Kanban\Entity::getInstance($factory->getEntityName());
			if ($factory->isCategoriesSupported())
			{
				$entity->setCategoryId($categoryId);
			}

			$options = $entity->getFilterOptions();
		}
		else
		{
			$gridId = (new GridId($factory->getEntityTypeId()))->getValue();
			$filter = \Bitrix\Crm\Filter\Factory::createEntityFilter(
				\Bitrix\Crm\Filter\Factory::createEntitySettings($factory->getEntityTypeId(), $gridId)
			);

			$defaultPresets = (new \Bitrix\Crm\Filter\Preset\Contact())
				->setDefaultValues($filter->getDefaultFieldIDs())
				->getDefaultPresets();

			$options = (new Options($gridId, $defaultPresets));
		}

		return $options;
	}

	private function getPermissions(UserPermissions $userPermissions, int $entityTypeId, ?int $categoryId): array
	{
		return [
			'add' => $userPermissions->checkAddPermissions($entityTypeId, $categoryId),
			'update' => $userPermissions->checkUpdatePermissions($entityTypeId, 0, $categoryId),
			'read' => $userPermissions->checkReadPermissions($entityTypeId, 0, $categoryId),
			'delete' => $userPermissions->checkDeletePermissions($entityTypeId, 0, $categoryId),
		];
	}

	private function prepareActiveTab(array &$tabs): void
	{
		foreach ($tabs as &$tab)
		{
			if (!$tab['selectable'])
			{
				continue;
			}
			if ($tab['typeName'] === \CCrmOwnerType::DealName)
			{
				if ($tab['permissions']['read'])
				{
					$tab['active'] = true;
					break;
				}
			}
			else
			{
				$tab['active'] = true;
				break;
			}
		}
		unset($tab);
	}
}
