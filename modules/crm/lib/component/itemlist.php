<?php

namespace Bitrix\Crm\Component;

use Bitrix\Crm\Automation\Factory;
use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Filter\Filter;
use Bitrix\Crm\Filter\ItemDataProvider;
use Bitrix\Crm\Filter\ItemUfDataProvider;
use Bitrix\Crm\Kanban;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UserField\Display;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar;

abstract class ItemList extends Base
{
	protected $navParamName = 'page';
	/** @var ItemDataProvider */
	protected $provider;
	/** @var ItemUfDataProvider */
	protected $ufProvider;
	/** @var Filter */
	protected $filter;
	/** @var Display */
	protected $display;
	protected $users;
	/** @var Category */
	protected $category;
	/** @var Service\Factory */
	protected $factory;
	/** @var Kanban\Entity */
	protected $kanbanEntity;

	protected function init(): void
	{
		parent::init();

		if($this->getErrors())
		{
			return;
		}

		$type = Service\Container::getInstance()->getTypeByEntityTypeId((int)$this->arParams['entityTypeId']);
		if (!$type)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('CRM_TYPE_TYPE_NOT_FOUND'));
			return;
		}

		$this->entityTypeId = $type->getEntityTypeId();

		$router = Container::getInstance()->getRouter();
		$router->setCurrentListView($this->entityTypeId, $this->getListViewType());

		$this->factory = Service\Container::getInstance()->getFactory($this->entityTypeId);
		$this->kanbanEntity = Kanban\Entity::getInstance($this->factory->getEntityName());

		if($this->factory->isCategoriesSupported())
		{
			$category = $this->initCategory();
			if (!is_null($category))
			{
				$this->category = $category;
				$this->kanbanEntity->setCategoryId($this->category->getId());
			}
		}

		$canReadThisCategory = $this->userPermissions->canReadType(
			$this->entityTypeId,
			$this->category ? $this->category->getId() : 0
		);
		if (!$canReadThisCategory)
		{
			if($this->factory->isCategoriesEnabled())
			{
				// if user can not read current category, but can read some another - make the redirect there.
				foreach ($this->factory->getCategories() as $category)
				{
					if ($this->userPermissions->canReadType($this->entityTypeId, $category->getId()))
					{
						LocalRedirect(
							$router->getItemListUrlInCurrentView(
								$this->entityTypeId,
								$category->getId()
							)
						);
						return;
					}
				}
			}
			$this->errorCollection[] = new Error(Loc::getMessage('CRM_TYPE_TYPE_ACCESS_DENIED'));
			return;
		}

		$filterFactory = Container::getInstance()->getFilterFactory();
		$settings = $filterFactory->getSettings(
			$this->factory->getEntityTypeId(),
			$this->getGridId(),
			[
				'categoryId' => $this->getCategoryId(),
				'type' => $type,
			]
		);
		$this->provider = $filterFactory->getDataProvider($settings);
		$this->ufProvider = $filterFactory->getUserFieldDataProvider($settings);
		$this->filter = $filterFactory->createFilter($settings->getID(), $this->provider, [$this->ufProvider]);

		$this->display = new Display($this->factory);
	}

	protected function getGridId(): string
	{
		return $this->kanbanEntity->getGridId();
	}

	protected function initCategory(): ?Category
	{
		$categoryId = (int)$this->arParams['categoryId'];

		if ($categoryId <= 0)
		{
			return null;
		}

		$category = $this->factory->getCategory($categoryId);

		if (!$category)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('CRM_TYPE_CATEGORY_NOT_FOUND_ERROR'));
			return null;
		}

		return $category;
	}

	public function onPrepareComponentParams($arParams): array
	{
		$this->fillParameterFromRequest('categoryId', $arParams);
		$this->fillParameterFromRequest('entityTypeId', $arParams);

		return parent::onPrepareComponentParams($arParams);
	}

	protected function getCategoryId(): ?int
	{
		return ($this->category ? $this->category->getId() : null);
	}

	protected function getToolbarParameters(): array
	{
		$buttons = [];

		$container = Service\Container::getInstance();
		$dynamicTypesLimit = RestrictionManager::getDynamicTypesLimitRestriction();
		$isTypeSettingsRestricted = $dynamicTypesLimit->isTypeSettingsRestricted($this->entityTypeId);
		$category = $this->category;
		if (!$category && $this->factory->isCategoriesSupported())
		{
			$category = $this->factory->getDefaultCategory();
		}
		if (
			$container->getUserPermissions()->checkAddPermissions(
				$this->entityTypeId,
				$category ? $category->getId() : 0
			)
		)
		{
			$addButtonParameters = $this->getAddButtonParameters();
			if ($isTypeSettingsRestricted)
			{
				$addButtonParameters['onclick'] = $dynamicTypesLimit->getShowFeatureJsHandler();
				unset($addButtonParameters['link']);
			}
			$buttons[Toolbar\ButtonLocation::AFTER_TITLE][] = new Buttons\Button($addButtonParameters);
		}

		if ($this->factory->isCategoriesEnabled())
		{
			$categories = $this->userPermissions->filterAvailableForReadingCategories(
				$this->factory->getCategories()
			);
			if (
				count($categories) > 1
				|| Container::getInstance()->getUserPermissions()->canWriteConfig()
			)
			{
				$buttons[Toolbar\ButtonLocation::RIGHT][] = new Buttons\Button([
					'color' => Buttons\Color::LIGHT_BORDER,
					'className' => 'ui-btn ui-btn-themes ui-btn-light-border ui-btn-dropdown ui-toolbar-btn-dropdown',
					'text' => $this->category ? $this->category->getName() : Loc::getMessage('CRM_TYPE_TOOLBAR_ALL_ITEMS'),
					'menu' => [
						'items' => $this->getToolbarCategories(
							$categories
						),
					],
					'maxWidth' => '400px',
					'dataset' => [
						'role' => 'bx-crm-toolbar-categories-button',
						'entity-type-id' => $this->factory->getEntityTypeId(),
						'category-id' => $this->category ? $this->category->getId() : null,
					],
				]);
			}
		}

		$settingsItems = $this->getToolbarSettingsItems();
		if (count($settingsItems) > 0)
		{
			$buttons[Toolbar\ButtonLocation::RIGHT][] = new Buttons\SettingsButton([
				'menu' => [
					'id' => 'crm-toolbar-settings-menu',
					'items' => $settingsItems,
				],
			]);
		}

		$parameters = [
			'buttons' => $buttons,
			'filter' => $this->prepareFilter(),
			'views' => $this->getToolbarViews(),
			'isWithFavoriteStar' => true,
		];

		return array_merge(parent::getToolbarParameters(), $parameters);
	}

	protected function getToolbarSettingsItems(): array
	{
		$settingsItems = [];
		$router = Service\Container::getInstance()->getRouter();
		if (Container::getInstance()->getUserPermissions()->canWriteConfig())
		{
			$settingsItems[] = [
				'text' => Loc::getMessage('CRM_TYPE_TYPE_SETTINGS'),
				'href' => $router->getTypeDetailUrl($this->entityTypeId),
				'onclick' => new Buttons\JsHandler('BX.Crm.Router.Instance.closeSettingsMenu'),
			];
			$dynamicTypesLimit = RestrictionManager::getDynamicTypesLimitRestriction();
			$isTypeSettingsRestricted = $dynamicTypesLimit->isTypeSettingsRestricted($this->entityTypeId);
			if ($isTypeSettingsRestricted)
			{
				$settingsItems[] = [
					'text' => Loc::getMessage('CRM_TYPE_TYPE_FIELDS_SETTINGS'),
					'onclick' => $dynamicTypesLimit->getShowFeatureJsHandler(),
				];
			}
			else
			{
				$settingsItems[] = [
					'text' => Loc::getMessage('CRM_TYPE_TYPE_FIELDS_SETTINGS'),
					'href' => $router->getUserFieldListUrl($this->entityTypeId),
					'onclick' => new Buttons\JsHandler('BX.Crm.Router.Instance.closeSettingsMenu'),
				];
			}
		}

		return $settingsItems;
	}

	protected function prepareFilter(): array
	{
		$limits = null;
		$searchRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getSearchLimitRestriction();
		if($searchRestriction->isExceeded($this->entityTypeId))
		{
			$limits = $searchRestriction->prepareStubInfo([
				'ENTITY_TYPE_ID' => $this->entityTypeId
			]);
		}

		return [
			'FILTER_ID' => $this->getGridId(),
			'GRID_ID' => $this->getGridId(),
			'FILTER' => $this->getDefaultFilterFields(),
			'FILTER_PRESETS' => $this->getDefaultFilterPresets(),
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => true,
			'DISABLE_SEARCH' => false,
			'ENABLE_LIVE_SEARCH' => true,
			'LIMITS' => $limits,
		];
	}

	protected function getDefaultFilterFields(): array
	{
		return $this->filter->getFieldArrays();
	}

	protected function getDefaultFilterPresets(): array
	{
		return $this->kanbanEntity->getFilterPresets();
	}

	protected function getToolbarViews(): array
	{
		if (!$this->factory->isStagesEnabled())
		{
			return [];
		}

		$views = [];

		if (Factory::isSupported($this->entityTypeId))
		{
			$categoryId = $this->getCategoryId();
			if (!$categoryId)
			{
				$categoryId = $this->factory->getDefaultCategory()->getId();
			}
			$url = Container::getInstance()->getRouter()->getAutomationUrl(
				$this->entityTypeId,
				$categoryId
			);
			$robotView = [
				'className' => 'ui-btn ui-btn-light-border ui-btn-no-caps ui-btn-themes ui-btn-round crm-robot-btn',
				'title' => Loc::getMessage('CRM_COMMON_ROBOTS'),
				'url' => $url,
				'isActive' => false,
				'position' => Toolbar\ButtonLocation::RIGHT,
			];
			$dynamicTypesLimit = RestrictionManager::getDynamicTypesLimitRestriction();
			$isTypeSettingsRestricted = $dynamicTypesLimit->isTypeSettingsRestricted($this->entityTypeId);
			if ($isTypeSettingsRestricted)
			{
				$robotView['onclick'] = $dynamicTypesLimit->getShowFeatureJsFunctionString();
				unset($robotView['url']);
			}
			$views[] = $robotView;
		}

		$views[Service\Router::LIST_VIEW_KANBAN] = [
			'title' => Loc::getMessage('CRM_COMMON_KANBAN'),
			'url' => Container::getInstance()->getRouter()->getKanbanUrl($this->entityTypeId, $this->getCategoryId()),
			'isActive' => false,
		];
		$views[Service\Router::LIST_VIEW_LIST] = [
			'title' => Loc::getMessage('CRM_COMMON_LIST'),
			'url' => Container::getInstance()->getRouter()->getItemListUrl($this->entityTypeId, $this->getCategoryId()),
			'isActive' => true,
		];

		return $views;
	}

	protected function getToolbarCategories(array $categories): array
	{
		$menu = parent::getToolbarCategories($categories);

		if ($this->userPermissions->canWriteConfig())
		{
			$menu[] = [
				'delimiter' => true,
			];
			$dynamicTypesLimit = RestrictionManager::getDynamicTypesLimitRestriction();
			$isTypeSettingsRestricted = $dynamicTypesLimit->isTypeSettingsRestricted($this->entityTypeId);
			if ($isTypeSettingsRestricted)
			{
				$menu[] = [
					'text' => Loc::getMessage('CRM_TYPE_CATEGORY_SETTINGS'),
					'onclick' => $dynamicTypesLimit->getShowFeatureJsHandler(),
				];
			}
			else
			{
				$menu[] = [
					'text' => Loc::getMessage('CRM_TYPE_CATEGORY_SETTINGS'),
					'href' => Container::getInstance()->getRouter()->getCategoryListUrl($this->entityTypeId),
				];
			}
		}

		return $menu;
	}

	abstract protected function getListViewType(): string;

	protected function getAddButtonParameters(): array
	{
		return [
			'color' => Buttons\Color::PRIMARY,
			'text' => Loc::getMessage('CRM_COMMON_ACTION_ADD'),
			'icon' => Buttons\Icon::ADD,
			'link' => Service\Container::getInstance()->getRouter()
				->getItemDetailUrl(
					$this->entityTypeId,
					0,
					$this->getCategoryId()
				)
				->getUri(),
		];
	}
}
