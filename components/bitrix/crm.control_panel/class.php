<?php

use Bitrix\Catalog;
use Bitrix\Crm\Component\ControlPanel\ControlPanelMenuMapper;
use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Integration\Catalog\Contractor\CategoryRepository;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory\SmartDocument;
use Bitrix\Intranet;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CrmControlPanel extends CBitrixComponent
{
	/**
	 * @deprecated consts deprecated,
	 * @see ControlPanelMenuMapper
	 */
	public const MENU_ID_CRM_CLIENT = ControlPanelMenuMapper::MENU_ID_CRM_CLIENT;
	public const MENU_ID_CRM_CONTACT = ControlPanelMenuMapper::MENU_ID_CRM_CONTACT;
	public const MENU_ID_CRM_COMPANY = ControlPanelMenuMapper::MENU_ID_CRM_COMPANY;
	public const MENU_ID_CRM_STORE_CONTRACTORS = ControlPanelMenuMapper::MENU_ID_CRM_STORE_CONTRACTORS;
	public const MENU_ID_CRM_SIGN_COUNTERPARTY = ControlPanelMenuMapper::MENU_ID_CRM_SIGN_COUNTERPARTY;
	public const MENU_ID_CRM_SIGN_COUNTERPARTY_CONTACTS = ControlPanelMenuMapper::MENU_ID_CRM_SIGN_COUNTERPARTY_CONTACTS;
	public const MENU_ID_CRM_STORE_CONTRACTORS_COMPANIES = ControlPanelMenuMapper::MENU_ID_CRM_STORE_CONTRACTORS_COMPANIES;
	public const MENU_ID_CRM_STORE_CONTRACTORS_CONTACTS = ControlPanelMenuMapper::MENU_ID_CRM_STORE_CONTRACTORS_CONTACTS;
	public const MENU_ID_CRM_CONTACT_CENTER = ControlPanelMenuMapper::MENU_ID_CRM_CONTACT_CENTER;

	protected $oldMenuStructure;

	protected array $contractorCategories = [];
	protected array $counterPartyCategories = [];

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->contractorCategories = [
			CCrmOwnerType::Company => CategoryRepository::getIdByEntityTypeId(
				CCrmOwnerType::Company
			),
			CCrmOwnerType::Contact => CategoryRepository::getIdByEntityTypeId(
				CCrmOwnerType::Contact
			),
		];

		$counterPartyCategory = Container::getInstance()
			->getFactory(CCrmOwnerType::Contact)
			->getCategoryByCode(SmartDocument::CONTACT_CATEGORY_CODE)
		;
		if ($counterPartyCategory)
		{
			$this->counterPartyCategories = [
				CCrmOwnerType::Contact => $counterPartyCategory->getId(),
			];
		}
	}

	public function onPrepareComponentParams($arParams)
	{
		$arParams['ACTIVE_ITEM_ID'] = $arParams['ACTIVE_ITEM_ID'] ?? null;

		return $arParams;
	}

	public function createMenuTree($standardItems)
	{
		return $this->createMenuItems($this->getMap(), $standardItems);
	}

	private function getMap(): array
	{
		return [
			['ID' => 'LEAD'],
			['ID' => 'DEAL'],
			[
				'ID' => ControlPanelMenuMapper::MENU_ID_CRM_CATALOGUE,
				'TEXT' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_CATALOGUE_STORE_DOCS'),
				'IS_DISABLED' => $this->isCatalogSectionDisabled(),
				'SUB_ITEMS' => [
					['ID' => 'CATALOGUE'],
					...(
						Catalog\Restriction\ToolAvailabilityManager::getInstance()->checkInventoryManagementAvailability()
						? [
							['ID' => 'STORE_DOCUMENTS']
						]
						: []
					),
					['ID' => 'STORE_MENU_CATALOG_PERMISSIONS'],
					['ID' => 'CATALOG_SETTINGS'],
				],
			],
			[
				'ID' => ControlPanelMenuMapper:: MENU_ID_CRM_CLIENT,
				'TEXT' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_CLIENTS'),
				'SUB_ITEMS' => [
					['ID' => 'CONTACT'],
					['ID' => 'COMPANY'],
					...$this->getCounterpartyMenuSubItems(),
					...$this->getContractorsMenuSubItems(),
					['ID' => 'CONTACT_CENTER', 'SLIDER_MODE' => true],
				],
			],
			[
				'ID' => ControlPanelMenuMapper::MENU_ID_CRM_SALES,
				'TEXT' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_SALES'),
				'SUB_ITEMS' => [
					['ID' => 'SMART_INVOICE'],
					['ID' => 'INVOICE'],
					['ID' => 'QUOTE'],
					['ID' => 'SALES_CENTER', 'SLIDER_MODE' => true],
					['ID' => 'TERMINAL'],
					['ID' => 'CALL_ASSESSMENT'],
				],
			],
			['ID' => 'BIC_DASHBOARDS'],
			[
				'ID' => ControlPanelMenuMapper::MENU_ID_CRM_ANALYTICS,
				'TEXT' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_ANALYTICS'),
				'URL' => '',
				'SUB_ITEMS' => [
					['ID' => 'ANALYTICS_SALES_FUNNEL'],
					['ID' => 'ANALYTICS_MANAGERS'],
					['ID' => 'ANALYTICS_CALLS'],
					['ID' => 'ANALYTICS_DIALOGS'],
					['ID' => 'CRM_TRACKING', 'SLIDER_MODE' => true],
					['ID' => 'REPORT'],
					['ID' => 'ANALYTICS_BI'],
				],
			],
			[
				'ID' => ControlPanelMenuMapper::MENU_ID_CRM_INTEGRATIONS,
				'TEXT' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_INTEGRATIONS'),
				'IS_DISABLED' => $this->isOldMenuStructure(),
				'SUB_ITEMS' => [
					['ID' => 'TELEPHONY', 'SLIDER_MODE' => true],
					['ID' => 'MAIL', 'SLIDER_MODE' => true],
					['ID' => 'MESSENGERS', 'SLIDER_MODE' => true],
					['ID' => 'SITEBUTTON'],
					['ID' => 'WEBFORM'],
					['ID' => 'CONTACT_CENTER', 'SLIDER_MODE' => true],
					['ID' => 'MARKETPLACE', 'SLIDER_MODE' => true],
					['ID' => 'MARKETPLACE_CRM_MIGRATION', 'SLIDER_MODE' => true],
					['ID' => 'ONEC', 'SLIDER_MODE' => true],
					['ID' => 'MARKETPLACE_CRM_SOLUTIONS', 'SLIDER_MODE' => true],
					['ID' => 'DEVOPS', 'SLIDER_MODE' => true],
				],
			],
			['ID' => 'DYNAMIC_ITEMS'],
			[
				'ID' => ControlPanelMenuMapper::MENU_ID_CRM_SETTINGS,
				'TEXT' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_SETTINGS'),
				'IS_DISABLED' => $this->isOldMenuStructure(),
				'SUB_ITEMS' => [
					['ID' => 'SETTINGS'],
					['ID' => 'MY_COMPANY'],
					[
						'ID' => 'PERMISSIONS',
						'SUB_ITEMS' => [
							['ID' => 'CRM_PERMISSIONS'],
							['ID' => 'CATALOG_PERMISSIONS'],
						],
					],
					['ID' => 'SALES_CENTER_PAYMENT', 'SLIDER_MODE' => true],
					['ID' => 'SALES_CENTER_DELIVERY', 'SLIDER_MODE' => true],
					['ID' => 'FEATURES_LIST', 'SLIDER_MODE' => false],
				],
			],
			['ID' => 'RECYCLE_BIN'],
			['ID' => 'EVENT'],
			['ID' => 'MY_ACTIVITY'],
			['ID' => 'START'],
			['ID' => 'STREAM'],
		];
	}

	protected function createFileMenuItems($items, $depthLevel = 1): array
	{
		$result = [];
		foreach ($items as $item)
		{
			$hasChildren = isset($item['ITEMS']) && is_array($item['ITEMS']) && !empty($item['ITEMS']);

			$result[] = [
				$item['NAME'] ?? $item['TEXT'],
				$item['URL'] ?? '',
				[],
				[
					'DEPTH_LEVEL' => $depthLevel,
					'FROM_IBLOCK' => true,
					'IS_PARENT' => $hasChildren,
					'onclick' => $item['ON_CLICK'] ?? null,
				],
			];

			if ($hasChildren)
			{
				$result = array_merge($result, $this->createFileMenuItems($item['ITEMS'], $depthLevel + 1));
			}
		}

		return $result;
	}

	protected function getAvailableCategoriesMenuItems(int $entityTypeId): array
	{
		$result = [];
		$routerService = Container::getInstance()->getRouter();
		$userPermissionsService = Container::getInstance()->getUserPermissions();
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory || !$factory->isCategoriesSupported())
		{
			return $result;
		}

		$categories = $factory->getCategories();
		foreach ($categories as $category)
		{
			if (
				$category->getIsSystem()
				|| $category->getIsDefault()
				|| !$userPermissionsService->canViewItemsInCategory($category)
			)
			{
				continue;
			}

			$categoryId = $category->getId();
			$counter = EntityCounterFactory::create(
				$entityTypeId,
				EntityCounterType::ALL,
				$userPermissionsService->getUserId(),
				[
					'CATEGORY_ID' => $categoryId,
				]
			);
			$menuId = CCrmOwnerType::ResolveName($entityTypeId) . '_C' . $categoryId;
			$actions = [];
			if ($userPermissionsService->checkAddPermissions($entityTypeId, $categoryId))
			{
				$actions[] = [
					'ID' => 'CREATE',
					'URL' => $routerService->getItemDetailUrl($entityTypeId, 0, $categoryId)->getLocator(),
				];
			}

			$result[] = [
				'ID' => $menuId,
				'MENU_ID' => 'menu_crm_' . strtolower($menuId),
				'NAME' => $category->getName(),
				'TITLE' => $category->getName(),
				'URL' => $routerService->getItemListUrl($entityTypeId, $categoryId)->getLocator(),
				'COUNTER' => $counter->getValue(),
				'COUNTER_ID' => $counter->getCode(),
				'ACTIONS' => $actions,
			];
		}

		return $result;
	}

	/**
	 * @param int $entityTypeId
	 * @param string $url
	 * @param string $menuId
	 * @param array $counterExtras
	 * @return array|null
	 */
	protected function getContractorsMenuItem(
		int $entityTypeId,
		string $url,
		string $menuId,
		array $counterExtras
	): ?array
	{
		if (!isset($this->contractorCategories[$entityTypeId]))
		{
			return null;
		}
		$categoryId = $this->contractorCategories[$entityTypeId];

		$counter = EntityCounterFactory::create(
			$entityTypeId,
			EntityCounterType::ALL,
			Container::getInstance()->getUserPermissions()->getUserId(),
			array_merge(
				$counterExtras,
				[
					'CATEGORY_ID' => $categoryId,
				]
			)
		);

		return [
			'ID' => CCrmComponentHelper::getMenuActiveItemId(
				CCrmOwnerType::ResolveName($entityTypeId),
				$categoryId
			),
			'MENU_ID' => $menuId,
			'NAME' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_' . CCrmOwnerType::ResolveName($entityTypeId)),
			'TITLE' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_' . CCrmOwnerType::ResolveName($entityTypeId) . '_TITLE'),
			'URL' => $url,
			'ON_CLICK' => 'event.preventDefault();BX.SidePanel.Instance.open("' . CUtil::JSescape($url) . '", {cacheable: false, customLeftBoundary: 0,});',
			'COUNTER' => $counter->getValue(),
			'COUNTER_ID' => $counter->getCode(),
		];
	}

	protected function resolveCounterpartyMenuId($entityTypeId)
	{
		$categoryId = $this->counterPartyCategories[$entityTypeId];

		return CCrmComponentHelper::getMenuActiveItemId(
			CCrmOwnerType::ResolveName($entityTypeId),
			$categoryId
		);
	}

	protected function prepareItems($items)
	{
		foreach ($items as $key => $item)
		{
			$items[$key] = $this->prepareItem($item);
		}

		return $items;
	}

	protected function prepareItem(array $item)
	{
		$itemActions = isset($item['ACTIONS']) ? [
			'CLASS' => ($item['ACTIONS'][0]['ID'] ?? null) === 'CREATE' ? 'crm-menu-plus-btn' : '',
			'URL' => $item['ACTIONS'][0]['URL'] ?? '',
		] : false;

		unset($item['ACTIONS']);

		$item['TEXT'] = $item['TEXT'] ?? ($item['NAME'] ?? '');
		$item['IS_ACTIVE'] = $this->arResult['ACTIVE_ITEM_ID'] === ($item['ID'] ?? null);
		$item['ID'] = $item['MENU_ID'] ?? ($item['ID'] ?? null);
		$item['SUB_LINK'] = $itemActions;
		$item['COUNTER'] = (isset($item['COUNTER']) && (int)$item['COUNTER'] > 0) ? $item['COUNTER'] : false;
		$item['COUNTER_ID'] = $item['COUNTER_ID'] ?? '';
		$item['IS_LOCKED'] = $item['IS_LOCKED'] ?? false;

		if (isset($item['IS_DISABLED']) && $item['IS_DISABLED'] === true)
		{
			$item['IS_DISABLED'] = true;
		}

		$itemClass = 'crm-menu-' . ($item['ICON'] ?? '') . ' crm-menu-item-wrap';
		if (isset($item['CLASS']))
		{
			$itemClass .= ' ' . $item['CLASS'];
		}

		$submenuClass = 'crm-menu-more-' . ($item['ICON'] ?? '');
		if (isset($item['CLASS_SUBMENU_ITEM']))
		{
			$submenuClass .= ' ' . $item['CLASS_SUBMENU_ITEM'];
		}

		$item['CLASS'] = $itemClass;
		$item['CLASS_SUBMENU_ITEM'] = $submenuClass;

		if (isset($item['ITEMS']) && is_array($item['ITEMS']) && count($item['ITEMS']) > 0)
		{
			$this->prepareSubItems($item, $item['ITEMS']);
		}

		return $item;
	}

	protected function prepareSubItems(&$item, array &$subItems)
	{
		for ($i = 0, $count = count($subItems); $i < $count; $i++)
		{
			$subItems[$i] = $this->prepareItem($subItems[$i]);
		}
	}

	protected function isCatalogSectionDisabled(): bool
	{
		if ($this->isOldMenuStructure())
		{
			$menuSettings = $this->getMenuSettings();
			$storeDocs = ControlPanelMenuMapper::getCrmTabMenuIdById('STORE_DOCUMENTS', true); // 'crm_control_panel_menu_menu_crm_store_docs';
			$catalog = ControlPanelMenuMapper::getCrmTabMenuIdById('CATALOG', true); //'crm_control_panel_menu_menu_crm_catalog';
			$products = 'crm_control_panel_menu_menu_crm_product';

			$storeDocsHidden =
				isset($menuSettings[$storeDocs]['isDisabled']) && $menuSettings[$storeDocs]['isDisabled'] === true;

			$catalogHidden =
				isset($menuSettings[$catalog]['isDisabled']) && $menuSettings[$catalog]['isDisabled'] === true;

			$productsHidden =
				isset($menuSettings[$products]['isDisabled']) && $menuSettings[$products]['isDisabled'] === true;

			return $storeDocsHidden && ($catalogHidden || $productsHidden);
		}

		return false;
	}

	protected function isOldMenuStructure(): bool
	{
		if ($this->oldMenuStructure !== null)
		{
			return $this->oldMenuStructure;
		}

		$this->oldMenuStructure = false;
		if ($this->isOldPortal())
		{
			$option = CUserOptions::getOption('intranet', 'crm_old_menu_structure', null);
			if ($option === null)
			{
				// First Hit
				$menuSettings = $this->getMenuSettings();
				$this->oldMenuStructure = !empty($menuSettings);
				CUserOptions::setOption('intranet', 'crm_old_menu_structure', $this->oldMenuStructure ? 'Y' : 'N');
			}
			else if ($option === 'Y')
			{
				$menuSettings = $this->getMenuSettings();
				$this->oldMenuStructure = true;
				if (empty($menuSettings))
				{
					// Old structure was reset
					CUserOptions::setOption('intranet', 'crm_old_menu_structure', 'N');
					$this->oldMenuStructure = false;
				}
			}
		}

		return $this->oldMenuStructure;
	}

	protected function getMenuSettings(): array
	{
		$menuSettings = [];
		$userOptions = CUserOptions::getOption('ui', ControlPanelMenuMapper::CONTROL_PANEL_CODE_NAME);
		if (is_array($userOptions) && isset($userOptions['settings']) && !empty($userOptions['settings']))
		{
			$menuSettings = json_decode($userOptions['settings'], true);
		}

		return $menuSettings;
	}

	protected function isOldPortal(): bool
	{
		if (COption::getOptionString('intranet', 'new_portal_structure', 'N') === 'Y')
		{
			return false;
		}

		if (Loader::includeModule('bitrix24'))
		{
			$targetTime = strtotime('2022-06-24');
			$createTime = CBitrix24::getCreateTime();
			if ($createTime && $createTime > $targetTime)
			{
				return false;
			}
		}

		return true;
	}

	private function createMenuItems($mapItems, $standardItems): array
	{
		$result = [];
		foreach ($mapItems as $mapItem)
		{
			if (!is_array($mapItem))
			{
				continue;
			}

			if (
				class_exists('Bitrix\Intranet\Settings\Tools\ToolsManager')
				&& isset($mapItem['ID'])
				&& !Intranet\Settings\Tools\ToolsManager::getInstance()->checkAvailabilityByMenuId($mapItem['ID'])
			)
			{
				continue;
			}

			$item = $standardItems[$mapItem['ID'] ?? null] ?? $mapItem;
			if (!isset($item['NAME']) && !isset($item['TEXT']) && !isset($item['IS_DELIMITER']))
			{
				continue;
			}

			if (empty($mapItem['SUB_ITEMS']) && empty($item['SUB_ITEMS']))
			{
				$item['IS_ACTIVE'] = $this->arParams["ACTIVE_ITEM_ID"] === ($item['ID'] ?? null);
				$item['TEXT'] = $item['TEXT'] ?? ($item['NAME'] ?? '');

				if (isset($mapItem['SLIDER_MODE']) && $mapItem['SLIDER_MODE'] === true)
				{
					$item['ON_CLICK'] = 'BX.SidePanel.Instance.open("' . CUtil::JSEscape($item['URL']) . '");';
					$item['ON_CLICK'] .= 'return false;';
				}

				if (isset($item['SLIDER_ONLY']) && $item['SLIDER_ONLY'] === true)
				{
					$item['URL'] = '';
				}

				$result[] = $item;
			}
			else
			{
				$subItems = $this->createMenuItems($mapItem['SUB_ITEMS'] ?? $item['SUB_ITEMS'], $standardItems);
				if (!empty($subItems))
				{
					$isNew = (bool) ($item['IS_NEW'] ?? false);
					if ($isNew === false)
					{
						foreach ($subItems as $subItem)
						{
							if ((bool) ($subItem['IS_NEW'] ?? false) === true)
							{
								$isNew = true;
								break;
							}
						}
					}

					//$firstSubItem = $subItems[0];
					$result[] = [
						'IS_ACTIVE' => $this->arParams["ACTIVE_ITEM_ID"] === $item['ID'],
						'ID' => $item['ID'],
						'TEXT' => $item['TEXT'] ?? $item['NAME'],
						'ITEMS' => $subItems,
						'IS_DISABLED' => $item['IS_DISABLED'] ?? false,
						'IS_NEW' => $isNew,
						// 'URL' => $item['URL'] ?? $firstSubItem['URL'],
						// 'ON_CLICK' => $item['ON_CLICK'] ?? $firstSubItem['ON_CLICK'],
					];
				}
			}
		}

		return $result;
	}

	/**
	 * @return array|array[]
	 */
	private function getContractorsMenuSubItems(): array
	{
		$result = [];

		if (
			isset($this->contractorCategories[CCrmOwnerType::Company])
			|| isset($this->contractorCategories[CCrmOwnerType::Contact])
		)
		{
			$subItems = [];

			if (isset($this->contractorCategories[CCrmOwnerType::Company]))
			{
				$subItems[] = [
					'ID' => CCrmComponentHelper::getMenuActiveItemId(
						CCrmOwnerType::ResolveName(CCrmOwnerType::Company),
						$this->contractorCategories[CCrmOwnerType::Company]
					),
				];
			}

			if (isset($this->contractorCategories[CCrmOwnerType::Contact]))
			{
				$subItems[] = [
					'ID' => CCrmComponentHelper::getMenuActiveItemId(
						CCrmOwnerType::ResolveName(CCrmOwnerType::Contact),
						$this->contractorCategories[CCrmOwnerType::Contact]
					),
				];
			}

			$result = [
				[
					'ID' => ControlPanelMenuMapper::MENU_ID_CRM_STORE_CONTRACTORS,
					'TEXT' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_CONTRACTORS'),
					'SUB_ITEMS' => $subItems,
				],
			];
		}

		return $result;
	}

	/**
	 * @return array|array[]
	 */
	private function getCounterpartyMenuSubItems(): array
	{
		$result = [];

		if (isset($this->counterPartyCategories[CCrmOwnerType::Contact]))
		{
			$result = [
				[
					'ID' => ControlPanelMenuMapper::MENU_ID_CRM_SIGN_COUNTERPARTY,
					'TEXT' => Loc::getMessage('CRM_CTRL_PANEL_ITEM_SIGN_COUNTERPARTY'),
					'SUB_ITEMS' => [['ID' => $this->resolveCounterpartyMenuId(CCrmOwnerType::Contact),]],
				],
			];
		}

		return $result;
	}
}
