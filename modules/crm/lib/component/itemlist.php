<?php

namespace Bitrix\Crm\Component;

use Bitrix\Crm\Automation;
use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Filter\Filter;
use Bitrix\Crm\Filter\ItemDataProvider;
use Bitrix\Crm\Filter\ItemUfDataProvider;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Kanban;
use Bitrix\Crm\Relation\EntityRelationTable;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Crm\Settings\InvoiceSettings;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Buttons;
use Bitrix\UI\Buttons\Icon;
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
	protected $users;
	/** @var Category */
	protected $category;
	/** @var Service\Factory */
	protected $factory;
	/** @var Kanban\Entity */
	protected $kanbanEntity;
	/** @var string */
	protected $intranetBindingMenuViewHtml;

	protected function init(): void
	{
		parent::init();

		if($this->getErrors())
		{
			return;
		}

		$entityTypeId = (int)$this->arParams['entityTypeId'];
		$type = Service\Container::getInstance()->getTypeByEntityTypeId($entityTypeId);
		if (!$type && \CCrmOwnerType::isDynamicTypeBasedStaticEntity($entityTypeId))
		{
			// force creating type
			if ($entityTypeId === \CCrmOwnerType::SmartInvoice)
			{
				Service\Factory\SmartInvoice::createTypeIfNotExists();
			}
			elseif ($entityTypeId === \CCrmOwnerType::SmartDocument)
			{
				Service\Factory\SmartDocument::createTypeIfNotExists();
			}
			$type = Service\Container::getInstance()->getTypeByEntityTypeId($entityTypeId);
		}
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

		if ($this->category)
		{
			$canReadThisCategory = $this->userPermissions->canReadTypeInCategory(
				$this->entityTypeId,
				$this->category->getId()
			);
		}
		else
		{
			$canReadThisCategory = $this->userPermissions->canReadType($this->entityTypeId);
		}
		if (!$canReadThisCategory)
		{
			if($this->factory->isCategoriesEnabled())
			{
				// if user can not read current category, but can read some another - make the redirect there.
				foreach ($this->factory->getCategories() as $category)
				{
					if ($this->userPermissions->canReadTypeInCategory($this->entityTypeId, $category->getId()))
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
			$this->errorCollection[] = new Error(Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED'));
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

		EntityRelationTable::initiateClearingDuplicateSourceElementsWithInterval($this->factory->getEntityTypeId());
	}

	protected function getGridId(): string
	{
		return $this->kanbanEntity->getGridId();
	}

	/**
	 * @return Category|null - returns null if no category is selected
	 */
	protected function initCategory(): ?Category
	{
		$categoryId = (int)$this->arParams['categoryId'];

		if ($categoryId <= 0)
		{
			if (!$this->factory->isCategoriesEnabled())
			{
				return $this->factory->createDefaultCategoryIfNotExist();
			}

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
		$spotlight = null;

		$container = Service\Container::getInstance();
		$dynamicTypesLimit = RestrictionManager::getDynamicTypesLimitRestriction();
		$isTypeSettingsRestricted = $dynamicTypesLimit->isTypeSettingsRestricted($this->entityTypeId);
		$category = $this->category;
		if (!$category && $this->factory->isCategoriesSupported())
		{
			$category = $this->factory->getDefaultCategory();
		}

		$isEnabled = $container->getUserPermissions()->checkAddPermissions(
			$this->entityTypeId,
			$category ? $category->getId() : null
		);
		$addButtonParameters = $this->getAddButtonParameters(!$isEnabled);
		if ($isTypeSettingsRestricted)
		{
			$addButtonParameters['onclick'] = $isEnabled ? $dynamicTypesLimit->getShowFeatureJsHandler() : null;
			unset($addButtonParameters['link']);
		}
		$buttons[Toolbar\ButtonLocation::AFTER_TITLE][] = new Buttons\Button($addButtonParameters);

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
				$buttons[Toolbar\ButtonLocation::AFTER_TITLE][] = new Buttons\Button([
					'icon' => defined('Bitrix\UI\Buttons\Icon::FUNNEL') ? Icon::FUNNEL : '',
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
						'toolbar-collapsed-icon' => defined('Bitrix\UI\Buttons\Icon::FUNNEL') ? Icon::FUNNEL : '',
					],
				]);
			}
		}

		$settingsItems = $this->getToolbarSettingsItems();
		// a bit of hardcode to avoid components copying
		if (
			$this->entityTypeId === \CCrmOwnerType::SmartInvoice
			&& !InvoiceSettings::getCurrent()->isOldInvoicesEnabled()
			&& Container::getInstance()->getUserPermissions()->canReadType(\CCrmOwnerType::Invoice)
		)
		{
			$settingsItems[] = [
				'text' => \CCrmOwnerType::GetCategoryCaption(\CCrmOwnerType::Invoice),
				'href' => Container::getInstance()->getRouter()->getItemListUrlInCurrentView(\CCrmOwnerType::Invoice),
				'onclick' => new Buttons\JsHandler('BX.Crm.Router.Instance.closeSettingsMenu'),
			];

			if (InvoiceSettings::getCurrent()->isShowInvoiceTransitionNotice())
			{
				$spotlight = [
					'ID' => 'crm-old-invoices-transition',
					'JS_OPTIONS' => [
						'targetElement' => static::TOOLBAR_SETTINGS_BUTTON_ID,
						'content' => Loc::getMessage('CRM_COMPONENT_ITEM_LIST_OLD_INVOICES_TRANSITION_SPOTLIGHT'),
						'targetVertex' => "middle-center",
						'autoSave' => true,
					],
				];
			}
		}

		if (count($settingsItems) > 0)
		{
			$settingsButton = new Buttons\SettingsButton([
				'menu' => [
					'id' => 'crm-toolbar-settings-menu',
					'items' => $settingsItems,
					'offsetLeft' => 20,
					'closeByEsc' => true,
					'angle' => true
				],
			]);
			$settingsButton->addAttribute('id', static::TOOLBAR_SETTINGS_BUTTON_ID);
			$buttons[Toolbar\ButtonLocation::RIGHT][] = $settingsButton;
		}

		$parameters = [
			'buttons' => $buttons,
			'filter' => $this->prepareFilter(),
			'views' => $this->getToolbarViews(),
			'isWithFavoriteStar' => true,
			'spotlight' => $spotlight,
		];

		return array_merge(parent::getToolbarParameters(), $parameters);
	}

	protected function getToolbarSettingsItems(): array
	{
		$settingsItems = [];
		$router = Service\Container::getInstance()->getRouter();
		$userPermissions = Container::getInstance()->getUserPermissions();
		if ($userPermissions->canWriteConfig())
		{
			if ($userPermissions->canUpdateType($this->entityTypeId))
			{
				$settingsItems[] = [
					'text' => Loc::getMessage('CRM_TYPE_TYPE_SETTINGS'),
					'href' => $router->getTypeDetailUrl($this->entityTypeId),
					'onclick' => new Buttons\JsHandler('BX.Crm.Router.Instance.closeSettingsMenu'),
				];
			}
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
		$searchRestriction = RestrictionManager::getSearchLimitRestriction();
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
			'ENABLE_ADDITIONAL_FILTERS' => true,
			'ENABLE_FIELDS_SEARCH' => 'Y',
			'HEADERS_SECTIONS' => $this->getHeaderSections(),
			'CONFIG' => [
				'popupColumnsCount' => 4,
				'popupWidth' => 800,
				'showPopupInCenter' => true,
			],
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

	protected function getHeaderSections(): array
	{
		return [
			[
				'id' => $this->factory->getEntityName(),
				'name' => $this->factory->getEntityDescription(),
				'default' => true,
				'selected' => true,
			],
		];
	}

	protected function getToolbarViews(): array
	{
		$views = [];

		if ($this->isIntranetBindingMenuViewAvailable())
		{
			$views[] = $this->getIntranetBindingMenuView();
		}

		if ($this->factory->isStagesEnabled())
		{
			$views[Service\Router::LIST_VIEW_KANBAN] = [
				'title' =>
					Crm::isUniversalActivityScenarioEnabled()
					? Loc::getMessage('CRM_COMMON_PIPELINE')
					: Loc::getMessage('CRM_COMMON_KANBAN')
				,
				'url' => Container::getInstance()->getRouter()->getKanbanUrl($this->entityTypeId, $this->getCategoryId()),
				'isActive' => false,
			];
			$views[Service\Router::LIST_VIEW_LIST] = [
				'title' => Loc::getMessage('CRM_COMMON_LIST'),
				'url' => Container::getInstance()->getRouter()->getItemListUrl($this->entityTypeId, $this->getCategoryId()),
				'isActive' => true,
			];

			if ($this->isDeadlinesModeSupported())
			{
				$views[Service\Router::LIST_VIEW_DEADLINES] = [
					'title' => Loc::getMessage('CRM_COMMON_DEADLINES'),
					'url' => Container::getInstance()->getRouter()->getDeadlinesUrl($this->entityTypeId, $this->getCategoryId()),
					'isActive' => false,
				];
			}

		}

		if (Automation\Factory::isSupported($this->entityTypeId))
		{
			$categoryId = $this->getCategoryId();
			if (is_null($categoryId) && $this->factory->isCategoriesSupported())
			{
				$categoryId = $this->factory->createDefaultCategoryIfNotExist()->getId();
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

		return $views;
	}

	protected function isIntranetBindingMenuViewAvailable(): bool
	{
		return !empty($this->getIntranetBindingMenuViewHtml());
	}

	protected function getIntranetBindingMenuView(): array
	{
		return [
			'html' => $this->getIntranetBindingMenuViewHtml(),
			'position' => Toolbar\ButtonLocation::RIGHT,
			'isActive' => false,
		];
	}

	protected function getIntranetBindingMenuViewHtml(): ?string
	{
		if (!is_string($this->intranetBindingMenuViewHtml))
		{
			ob_start();

			\Bitrix\Main\UI\Extension::load('bizproc.script');
			$this->getApplication()->IncludeComponent(
				'bitrix:intranet.binding.menu',
				'',
				[
					'SECTION_CODE' => Integration\Intranet\BindingMenu\SectionCode::SWITCHER,
					'MENU_CODE' => Integration\Intranet\BindingMenu\CodeBuilder::getMenuCode(
						$this->factory->getEntityTypeId(),
					),
				]
			);

			$this->intranetBindingMenuViewHtml = ob_get_clean();
		}

		return $this->intranetBindingMenuViewHtml;
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

	protected function getAddButtonParameters(bool $isDisabled = false): array
	{
		$link = Service\Container::getInstance()->getRouter()
			->getItemDetailUrl(
				$this->entityTypeId,
				0,
				$this->getCategoryId()
			)
			->getUri()
		;

		// disabled button configuration
		$disabledButtonDataset = [];
		$disabledButtonClass = '';
		if($isDisabled)
		{
			$link = null;
			$hintMsg = $this->entityTypeId === \CCrmOwnerType::SmartInvoice
				? 'CRM_SMART_INVOICE_ADD_HINT'
				: 'CRM_TYPE_ITEM_ADD_HINT';
			$disabledButtonDataset = [
				'hint' => Loc::getMessage($hintMsg),
				'hint-no-icon' => '',
			];
			$disabledButtonClass = 'ui-btn-disabled-ex'; // to correct display hint
		}

		$buttonTitle = Loc::getMessage('CRM_COMMON_ACTION_CREATE_' . $this->entityTypeId);
		if (!$buttonTitle)
		{
			$buttonTitle = Loc::getMessage('CRM_COMMON_ACTION_CREATE');
		}

		return [
			'color' => Buttons\Color::SUCCESS,
			'text' => $buttonTitle,
			'link' => $link,
			'dataset' => $disabledButtonDataset,
			'className' => $disabledButtonClass,
		];
	}

	protected function isDeadlinesModeSupported(): bool
	{
		$supported = [\CCrmOwnerType::SmartInvoice];
		return in_array($this->entityTypeId, $supported);
	}
}
