<?php

namespace Bitrix\Crm\Component;

use Bitrix\Crm\Automation;
use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Component\EntityList\Settings\PermissionItem;
use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Filter\Filter;
use Bitrix\Crm\Filter\HeaderSections;
use Bitrix\Crm\Filter\ItemDataProvider;
use Bitrix\Crm\Filter\ItemUfDataProvider;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Builder\Entity\AddOpenEvent;
use Bitrix\Crm\Kanban;
use Bitrix\Crm\Relation\EntityRelationTable;
use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\InvoiceSettings;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
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
	protected ?string $counterPanelViewHtml = null;

	protected function init(): void
	{
		parent::init();

		if($this->getErrors())
		{
			return;
		}

		//@codingStandardsIgnoreStart
		$entityTypeId = (int)$this->arParams['entityTypeId'];
		//@codingStandardsIgnoreEnd
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
			elseif ($entityTypeId === \CCrmOwnerType::SmartB2eDocument)
			{
				Service\Factory\SmartB2eDocument::createTypeIfNotExists();
			}
			$type = Service\Container::getInstance()->getTypeByEntityTypeId($entityTypeId);
		}
		if (!$type)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('CRM_TYPE_TYPE_NOT_FOUND'));
			return;
		}

		$this->entityTypeId = $type->getEntityTypeId();

		$this->router->setCurrentListView($this->entityTypeId, $this->getListViewType());

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
							$this->router->getItemListUrlInCurrentView(
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
		$additionalProviders = HeaderSections::getInstance()->additionalProviders($settings, $filterFactory);

		$this->filter = $filterFactory->createFilter($settings->getID(), $this->provider, $additionalProviders);

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
		//@codingStandardsIgnoreStart
		$categoryId = (int) ($this->arParams['categoryId'] ?? null);
		//@codingStandardsIgnoreEnd
		$optionName = 'current_' . mb_strtolower($this->factory->getEntityName()) . '_category';

		if ($categoryId <= 0)
		{
			if (!$this->factory->isCategoriesEnabled())
			{
				return $this->factory->createDefaultCategoryIfNotExist();
			}

			\CUserOptions::DeleteOption('crm', $optionName);

			return null;
		}

		$category = $this->factory->getCategory($categoryId);

		if (!$category)
		{
			\CUserOptions::DeleteOption('crm', $optionName);

			$this->errorCollection[] = new Error(Loc::getMessage('CRM_TYPE_CATEGORY_NOT_FOUND_ERROR'));

			return null;
		}

		\CUserOptions::SetOption('crm', $optionName, $categoryId);

		return $category;
	}

	//@codingStandardsIgnoreStart
	public function onPrepareComponentParams($arParams): array
	{
		$this->fillParameterFromRequest('categoryId', $arParams);
		$this->fillParameterFromRequest('entityTypeId', $arParams);

		return parent::onPrepareComponentParams($arParams);
	}
	//@codingStandardsIgnoreEnd

	protected function getCategoryId(): ?int
	{
		return $this->category?->getId();
	}

	protected function getToolbarParameters(): array
	{
		$buttons = [];
		$spotlight = null;

		$container = Service\Container::getInstance();
		$dynamicTypesLimit = RestrictionManager::getDynamicTypesLimitRestriction();
		$isTypeSettingsRestricted = $dynamicTypesLimit->isTypeSettingsRestricted($this->entityTypeId);

		$isDefaultCategory = false;
		$category = $this->category;
		if (!$category && $this->factory->isCategoriesSupported())
		{
			$category = $this->factory->getDefaultCategory();
			$isDefaultCategory = true;
		}

		$isEnabled = $container->getUserPermissions()->checkAddPermissions(
			$this->entityTypeId,
			$category?->getId()
		);

		$addButtonParameters = $this->getAddButtonParameters(!$isEnabled);
		if (!$isEnabled && $isDefaultCategory)
		{
			$availableCategory = $this->getFirstAvailableForAddCategory();
			if ($availableCategory !== null)
			{
				$addButtonParameters = $this->getAddButtonParameters(false, $availableCategory->getId());
			}
		}

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
				|| Container::getInstance()->getUserPermissions()->isAdminForEntity($this->entityTypeId)
			)
			{
				$buttonConfig = [
					'icon' => defined('Bitrix\UI\Buttons\Icon::FUNNEL') ? Icon::FUNNEL : '',
					'color' => Buttons\Color::LIGHT_BORDER,
					'className' => 'ui-btn ui-btn-themes ui-btn-light-border ui-btn-dropdown ui-toolbar-btn-dropdown',
					'text' => $this->category ? $this->category->getName() : Loc::getMessage('CRM_TYPE_TOOLBAR_ALL_ITEMS'),
					'menu' => [
						'items' => $this->getToolbarCategories($categories), //Tools\ToolBar::mapItems(),
					],
					'maxWidth' => '400px',
					'dataset' => [
						'role' => 'bx-crm-toolbar-categories-button',
						'entity-type-id' => $this->factory->getEntityTypeId(),
						'category-id' => $this->category?->getId(),
						'toolbar-collapsed-icon' => defined('Bitrix\UI\Buttons\Icon::FUNNEL') ? Icon::FUNNEL : '',
					],
				];

				if ($this->factory->isCountersEnabled())
				{
					$counterValue = $this->getCounterValue(null);
					if ($counterValue > 0)
					{
						$buttonConfig['counter'] = $counterValue;
					}
				}

				$buttons[Toolbar\ButtonLocation::AFTER_TITLE][] = new Buttons\Button($buttonConfig);
			}
		}

		$settingsItems = $this->getToolbarSettingsItems();
		// a bit of hardcode to avoid components copying
		if (
			$this->entityTypeId === \CCrmOwnerType::SmartInvoice
			&& Container::getInstance()->getUserPermissions()->canReadType(\CCrmOwnerType::Invoice)
		)
		{
			if (!InvoiceSettings::getCurrent()->isOldInvoicesEnabled())
			{
				$settingsItems[] = [
					'text' => \CCrmOwnerType::GetCategoryCaption(\CCrmOwnerType::Invoice),
					'href' => $this->router->getItemListUrlInCurrentView(\CCrmOwnerType::Invoice),
					'onclick' => new Buttons\JsHandler('BX.Crm.Router.Instance.closeSettingsMenu'),
				];

				if (InvoiceSettings::getCurrent()->isShowInvoiceTransitionNotice())
				{
					$spotlight = [
						'ID' => 'crm-old-invoices-transition',
						'JS_OPTIONS' => [
							'targetElement' => static::TOOLBAR_SETTINGS_BUTTON_ID,
							'content' => Loc::getMessage('CRM_COMPONENT_ITEM_LIST_OLD_INVOICES_TRANSITION_SPOTLIGHT'),
							'targetVertex' => 'middle-center',
							'autoSave' => true,
						],
					];
				}
			}

			$currentListView = Container::getInstance()->getRouter()->getCurrentListView(\CCrmOwnerType::SmartInvoice);
			$availableViews = [\Bitrix\Crm\Service\Router::LIST_VIEW_LIST, \Bitrix\Crm\Service\Router::LIST_VIEW_KANBAN];
			if (in_array($currentListView, $availableViews, true))
			{
				$einvoiceToolbarSettings = new Integration\Rest\EInvoiceApp\ToolbarSettings();
				$settingsItems = array_merge(
					$settingsItems,
					$einvoiceToolbarSettings->getItems(),
				);
			}
		}

		$permissionItem = PermissionItem::createByEntity($this->entityTypeId, $this->getCategoryId());
		$permissionItem->setAnalytics($this->getAnalytics());
		if ($permissionItem->canShow())
		{
			$settingsItems[] = $permissionItem->delimiter();
			$settingsItems[] = $permissionItem->toArray();
		}

		if (count($settingsItems) > 0)
		{
			$settingsButton = new Buttons\SettingsButton([
				'menu' => [
					'id' => 'crm-toolbar-settings-menu',
					'items' => $settingsItems,
					'offsetLeft' => 20,
					'closeByEsc' => true,
					'angle' => true,
				],
			]);
			$settingsButton->addAttribute('id', static::TOOLBAR_SETTINGS_BUTTON_ID);
			$buttons[Toolbar\ButtonLocation::RIGHT][] = $settingsButton;
		}

		//@codingStandardsIgnoreStart
		$parameters = [
			'buttons' => $buttons,
			'filter' => $this->prepareFilter(),
			'views' => $this->getToolbarViews(),
			'isWithFavoriteStar' => true,
			'spotlight' => $spotlight,
			'entityTypeName' => \CCrmOwnerType::ResolveName($this->entityTypeId),
			'categoryId' => $this->arResult['categoryId'],
			'pathToEntityList' => '/crm/type/' . $this->entityTypeId,
		];
		//@codingStandardsIgnoreEnd

		return array_merge(parent::getToolbarParameters(), $parameters);
	}

	private function getFirstAvailableForAddCategory(): ?Category
	{
		$availableCategories = $this->getAvailableForAddCategories();

		return $availableCategories[0] ?? null;
	}

	private function getAvailableForAddCategories(): array
	{
		$categories = $this->factory->getCategories();

		return array_values(array_filter($categories, [$this->userPermissions, 'canAddItemsInCategory']));
	}

	protected function getToolbarSettingsItems(): array
	{
		$settingsItems = [];

		$userPermissions = $this->userPermissions;
		if ($userPermissions->canUpdateType((int)$this->entityTypeId))
		{
			$settingsItems[] = [
				'text' => Loc::getMessage('CRM_TYPE_TYPE_SETTINGS'),
				'href' => $this->router->getTypeDetailUrl($this->entityTypeId)->addParams([
					'c_sub_section' => Integration\Analytics\Dictionary::ELEMENT_SETTINGS_BUTTON,
				]),
				'onclick' => new Buttons\JsHandler('BX.Crm.Router.Instance.closeSettingsMenu'),
			];
		}
		if ($userPermissions->isAdminForEntity((int)$this->entityTypeId))
		{
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
					'href' => $this->router->getUserFieldListUrl($this->entityTypeId),
					'onclick' => new Buttons\JsHandler('BX.Crm.Router.Instance.closeSettingsMenu'),
				];
			}
		}

		return $settingsItems;
	}

	//@codingStandardsIgnoreStart
	protected function prepareFilter(): array
	{
		$limits = null;
		$searchRestriction = RestrictionManager::getSearchLimitRestriction();
		if($searchRestriction->isExceeded($this->entityTypeId))
		{
			$limits = $searchRestriction->prepareStubInfo([
				'ENTITY_TYPE_ID' => $this->entityTypeId,
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
			'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => (
				$this->arParams['useCheckboxListForSettingsPopup'] ?? ModuleManager::isModuleInstalled('ui')
			),
			'RESTRICTED_FIELDS' => $this->arResult['restrictedFields'] ?? [],
		];
	}
	//@codingStandardsIgnoreEnd

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
		return HeaderSections::getInstance()->sections($this->factory);
	}

	protected function getToolbarViews(): array
	{
		$views = [];

		if ($this->factory->isCountersEnabled())
		{
			$views['COUNTER_PANEL'] = $this->getCounterPanelView();
		}

		if ($this->isIntranetBindingMenuViewAvailable())
		{
			$views[] = $this->getIntranetBindingMenuView();
		}

		if ($this->factory->isStagesEnabled())
		{
			$views[Service\Router::LIST_VIEW_KANBAN] = [
				'title' => Loc::getMessage('CRM_COMMON_KANBAN'),
				'url' => $this->router->getKanbanUrl($this->entityTypeId, $this->getCategoryId()),
				'isActive' => false,
			];
			$views[Service\Router::LIST_VIEW_LIST] = [
				'title' => Loc::getMessage('CRM_COMMON_LIST'),
				'url' => $this->router->getItemListUrl($this->entityTypeId, $this->getCategoryId()),
				'isActive' => true,
			];

			if ($this->isDeadlinesModeSupported())
			{
				$views[Service\Router::LIST_VIEW_DEADLINES] = [
					'title' => Loc::getMessage('CRM_COMMON_DEADLINES'),
					'url' => $this->router->getDeadlinesUrl($this->entityTypeId, $this->getCategoryId()),
					'isActive' => false,
				];
			}

		}

		if (Automation\Factory::isAutomationAvailable($this->entityTypeId))
		{
			$categoryId = $this->getCategoryId();
			if (is_null($categoryId) && $this->factory->isCategoriesSupported())
			{
				$categoryId = $this->factory->createDefaultCategoryIfNotExist()->getId();
			}
			$url = $this->router->getAutomationUrl(
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
			else
			{
				$toolsManager = Container::getInstance()->getIntranetToolsManager();
				if (!$toolsManager->checkRobotsAvailability())
				{
					$robotView['onclick'] = htmlspecialcharsbx(
						AvailabilityManager::getInstance()->getRobotsAvailabilityLock()
					);
					unset($robotView['url']);
				}
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

	protected function getCounterPanelView(): array
	{
		// remove this after UI release
		$position = defined('\Bitrix\UI\Toolbar\ButtonLocation::AFTER_NAVIGATION')
			? Toolbar\ButtonLocation::AFTER_NAVIGATION
			: 'after_navigation';

		return [
			'html' => $this->getCounterPanelViewHtml(),
			'position' => $position,
			'isActive' => false,
		];
	}

	protected function getCounterPanelViewHtml(): ?string
	{
		if ($this->counterPanelViewHtml === null)
		{
			ob_start();
			//@codingStandardsIgnoreStart
			$this->getApplication()->IncludeComponent(
				'bitrix:crm.entity.counter.panel',
				'',
				[
					'ENTITY_TYPE_NAME' => $this->arResult['entityTypeName'],
					'EXTRAS' => $this->arResult['categoryId'] > 0 ? ['CATEGORY_ID' => $this->arResult['categoryId']] : [],
					'PATH_TO_ENTITY_LIST' => '/crm/type/' . $this->entityTypeId,
					'RETURN_AS_HTML_MODE' => true,
				]
			);
			//@codingStandardsIgnoreEnd
			$this->counterPanelViewHtml = ob_get_clean();
		}
		return $this->counterPanelViewHtml;
	}

	protected function getToolbarCategories(array $categories): array
	{
		$menu = parent::getToolbarCategories($categories);

		if ($this->factory->isCountersEnabled())
		{
			foreach ($menu as &$item)
			{
				$counterValue = $this->getCounterValue($item['categoryId']);
				if ($counterValue <= 0)
				{
					continue;
				}
				$text = htmlspecialcharsbx($item['text']);
				$item['html'] = sprintf(
					'%s <span class="main-buttons-item-counter">%d</span>',
					$text,
					$counterValue
				);
			}
		}

		if ($this->userPermissions->isAdminForEntity($this->entityTypeId))
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
					'href' => $this->router->getCategoryListUrl($this->entityTypeId),
				];
			}
		}

		return $menu;
	}

	abstract protected function getListViewType(): string;

	protected function getAddButtonParameters(bool $isDisabled = false, int $forcedCategoryId = null): array
	{
		$rawLink = $this->router->getItemDetailUrl(
			$this->entityTypeId,
			0,
			$forcedCategoryId ?? $this->getCategoryId(),
		);

		$analyticsEventBuilder = AddOpenEvent::createDefault($this->entityTypeId);
		$this->configureAnalyticsEventBuilder($analyticsEventBuilder);
		$analyticsEventBuilder->setElement(\Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_CREATE_BUTTON);

		$link = $analyticsEventBuilder
			->buildUri($rawLink)
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

		$btnParameters = [
			'color' => Buttons\Color::SUCCESS,
			'text' => $buttonTitle,
			'link' => $link,
			'dataset' => $disabledButtonDataset,
			'className' => $disabledButtonClass,
		];
		$event = new \Bitrix\Main\Event(
			'crm',
			'onGetComponentItemListAddBtnParameters',
			[
				'btnParameters' => $btnParameters,
				'entityTypeId' => $this->entityTypeId,
			]
		);
		$event->send();
		$eventResults = $event->getResults();
		foreach ($eventResults as $eventResult)
		{
			$parameters = $eventResult->getParameters();
			if ($eventResult->getType() !== $eventResult::SUCCESS)
			{
				continue;
			}
			if (!is_array($parameters) || !is_string($parameters['link'] ?? null))
			{
				continue;
			}
			$btnParameters['link'] = $parameters['link'];

			return $btnParameters;
		}

		return $btnParameters;
	}

	//@codingStandardsIgnoreStart
	protected function configureAnalyticsEventBuilder(AbstractBuilder $builder): void
	{
		if ($this->isEmbedded())
		{
			if (!empty($this->arParams['ANALYTICS']['c_section']) && is_string($this->arParams['ANALYTICS']['c_section']))
			{
				$builder->setSection($this->arParams['ANALYTICS']['c_section']);
			}
			if (
				!empty($this->arParams['ANALYTICS']['c_sub_section'])
				&& is_string($this->arParams['ANALYTICS']['c_sub_section'])
			)
			{
				$builder->setSubSection($this->arParams['ANALYTICS']['c_sub_section']);
			}
		}
		elseif (Integration\IntranetManager::isEntityTypeInCustomSection($this->entityTypeId))
		{
			$builder->setSection(\Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_CUSTOM);
		}
		else
		{
			/**
			 * @see Integration\Analytics\Dictionary::SECTION_SMART_INVOICE
			 * @see Integration\Analytics\Dictionary::SECTION_DYNAMIC
			 */
			$builder->setSection(Integration\Analytics\Dictionary::getAnalyticsEntityType($this->entityTypeId) . '_section');
		}

		if ($this->category && $this->category->getCode())
		{
			$builder->setP2WithValueNormalization('category', $this->category->getCode());
		}
	}
	//@codingStandardsIgnoreEnd

	final protected function getAnalytics(): array
	{
		$dummyEvent = new AddOpenEvent();
		$this->configureAnalyticsEventBuilder($dummyEvent);

		return [
			'c_section' => $dummyEvent->getSection(),
			'c_sub_section' => $dummyEvent->getSubSection(),
		];
	}

	protected function isDeadlinesModeSupported(): bool
	{
		$supported = [\CCrmOwnerType::SmartInvoice];
		return in_array($this->entityTypeId, $supported);
	}

	private function getCounterValue(?int $categoryId = null): int
	{
		$extras = [];
		if ($categoryId !== null)
		{
			$extras['CATEGORY_ID'] = $categoryId;
		}

		$totalCounter = EntityCounterFactory::create(
			$this->factory->getEntityTypeId(),
			EntityCounterType::ALL,
			Container::getInstance()->getUserPermissions()->getUserId(),
			$extras
		);
		return (int)$totalCounter->getValue();
	}

	protected function getTopPanelParameters(): array
	{
		$params = parent::getTopPanelParameters();

		$params['ANALYTICS'] = $this->getAnalytics();

		return $params;
	}
}
