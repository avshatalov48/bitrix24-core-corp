<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Action;

use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Entity\FactoryProvider;
use Bitrix\CrmMobile\Kanban\Entity;
use Bitrix\CrmMobile\Kanban\GridId;
use Bitrix\ImOpenlines\Security\Permissions;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
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
		$hasRegisterTelegramConnector = $this->hasRegisterTelegramConnector();
		$isGoToChatAvailable = (bool)Option::get('crmmobile', 'release-spring-2023', true);

		return [
			'tabs' => $this->getTabs($userPermissions),
			'user' => \CCrmViewHelper::getUserInfo(),
			'restrictions' => [
				'crmMode' => !\Bitrix\CrmMobile\Entity\RestrictionManager::isEntityRestricted(\CCrmOwnerType::Deal)
					&& !\Bitrix\CrmMobile\Entity\RestrictionManager::isEntityRestricted(\CCrmOwnerType::Lead),
			],
			'permissions' => [
				'exclude' => !$crmPermissions->HavePerm('EXCLUSION', BX_CRM_PERM_NONE, 'WRITE'),
				'openLinesAccess' => $this->hasOpenLinesAccess(),
			],
			'connectors' => [
				'telegram' => $hasRegisterTelegramConnector || !$isGoToChatAvailable,
			],
		];
	}

	private function getTabs(UserPermissions $userPermissions): array
	{
		$result = [];
		$searchRestriction = RestrictionManager::getSearchLimitRestriction();
		$infoHelperId = $searchRestriction->getMobileInfoHelperId();

		foreach (FactoryProvider::getAvailableFactories() as $factory)
		{
			$entityTypeName = $factory->getEntityName();
			$entityTypeId = $factory->getEntityTypeId();

			if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
			{
				continue;
			}

			$hasRestrictions = \Bitrix\CrmMobile\Entity\RestrictionManager::isEntityRestricted($entityTypeId);
			$counter = EntityCounterFactory::create($factory->getEntityTypeId(), EntityCounterType::ALL)->getValue();

			$isCategoriesSupported = ($factory->isCategoriesSupported() && $entityTypeId !== \CCrmOwnerType::Contact);
			$categoryId = $isCategoriesSupported ? $this->getCurrentCategoryId($factory, $userPermissions) : 0;
			$permissions = $this->getPermissions($userPermissions, $entityTypeId, $categoryId);

			$isCategoriesEnabled = $factory->isCategoriesEnabled();
			$categories = $isCategoriesEnabled ? $factory->getCategories() : [];

			$filterOptions = $this->getFilterOptions($factory, $categoryId);

			$result[] = [
				'id' => $entityTypeId,
				'typeName' => $entityTypeName,
				'active' => false,
				'selectable' => !$hasRestrictions,
				'hasRestrictions' => $hasRestrictions,
				'link' => $this->getLinkToDesktop($entityTypeName, $categoryId),
				'title' => $factory->getEntityDescription(),
				'titleInPlural' => $factory->getEntityDescriptionInPlural(),
				'entityLink' => $this->getEntityLink($entityTypeName),
				'pageUrl' => null,
				'label' => $counter > 0 ? (string)$counter : '',
				'isStagesEnabled' => $factory->isStagesEnabled(),
				'isCategoriesSupported' => $isCategoriesSupported,
				'isCategoriesEnabled' => $isCategoriesEnabled,
				'isLastActivityEnabled' => $factory->isLastActivityEnabled(),
				'isAvailableCrmMode' => (bool)Option::get('crmmobile', 'release-spring-2023', true),

				// @todo temporary support only deals,
				// in the feature will be use factory when smart processes will be support this too
				'needSaveCurrentCategoryId' => ($entityTypeName === \CCrmOwnerType::DealName),

				'data' => [
					'currentCategoryId' => $categoryId,
					'categoriesCount' => $isCategoriesEnabled ? count($categories) : 1,
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
					'conversion' => RestrictionManager::isConversionPermitted(),
				],
			];
		}

		$this->prepareActiveTab($result);

		return $result;
	}

	private function hasRegisterTelegramConnector(): bool
	{
		if (!Loader::includeModule('imconnector'))
		{
			return false;
		}

		$statuses = \Bitrix\ImConnector\Status::getInstanceAllLine('telegrambot');

		foreach ($statuses as $status)
		{
			if (!$status->getError() && $status->getRegister())
			{
				return true;
			}
		}

		return false;
	}

	private function getCurrentCategoryId(Factory $factory, UserPermissions $userPermissions): int
	{
		$defaultCategory = $factory->getDefaultCategory();
		$defaultCategoryId = ($defaultCategory ? $defaultCategory->getId() : 0);

		if (!$factory->isCategoriesEnabled())
		{
			return $defaultCategoryId;
		}

		// @todo temporary support only deals,
		// in the feature will be use factory when smart processes will be support this too
		if ($factory->getEntityName() === \CCrmOwnerType::DealName)
		{
			$currentCategoryId = (int)\CUserOptions::GetOption(
				'crm',
				'current_deal_category',
				$defaultCategoryId
			);
		}
		else
		{
			$currentCategoryId = $defaultCategoryId;
		}

		if (
			$currentCategoryId !== null
			&& !$userPermissions->canReadTypeInCategory($factory->getEntityTypeId(), $currentCategoryId)
		)
		{
			$currentCategoryId = $this->getFirstAvailableCategory($factory, $userPermissions);
		}

		return ($currentCategoryId ?? $factory->getCategories()[0]->getId());
	}

	private function getFirstAvailableCategory(Factory $factory, UserPermissions $userPermissions): ?int
	{
		$entityTypeId = $factory->getEntityTypeId();
		$categories = $factory->getCategories();
		foreach ($categories as $category)
		{
			if ($userPermissions->canReadTypeInCategory($entityTypeId, $category->getId()))
			{
				return $category->getId();
			}
		}

		return null;
	}

	private function getSortType(string $entityTypeName, ?int $categoryId): ?string
	{
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
		if (empty($permissions['update']) || !$factory->isSmartActivityNotificationSupported())
		{
			return null;
		}

		return [
			'notificationSupported' => $factory->isSmartActivityNotificationSupported(),
			'notificationEnabled' => $factory->isSmartActivityNotificationEnabled(),
		];
	}

	/**
	 * @param string $entityTypeName
	 * @param int|null $categoryId
	 * @return string
	 */
	private function getLinkToDesktop(string $entityTypeName, ?int $categoryId): string
	{
		return Entity::getInstance($entityTypeName)->getDesktopLink($categoryId);
	}

	/**
	 * @param string $entityTypeName
	 * @return string
	 */
	private function getEntityLink(string $entityTypeName): string
	{
		return Entity::getInstance($entityTypeName)->getEntityLink();
	}

	/**
	 * @param Factory $factory
	 * @param int|null $categoryId
	 * @return array
	 */
	private function getCounters(Factory $factory, ?int $categoryId = null): array
	{
		$userId = (int)$this->getCurrentUser()->getId();
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
				->getDefaultPresets()
			;

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

	private function hasOpenLinesAccess(): bool
	{
		if (!Loader::includeModule('imopenlines'))
		{
			return false;
		}

		return Permissions::createWithCurrentUser()
			->canPerform(Permissions::ENTITY_LINES, Permissions::ACTION_MODIFY)
		;
	}
}
