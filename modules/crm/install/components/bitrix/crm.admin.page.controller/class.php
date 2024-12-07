<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Catalog;
use Bitrix\Catalog\Restriction\ToolAvailabilityManager;
use Bitrix\Crm;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\OrderSettings;
use Bitrix\Iblock;
use Bitrix\Landing;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Catalog\Store\EnableWizard\Manager;

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @global CMain $APPLICATION
 */
class CCrmAdminPageController extends \CBitrixComponent implements Controllerable
{
	private $pageList = array();
	private $listMenuItems = array();

	/** @var Catalog\Url\ShopBuilder */
	private $urlBuilder;

	/** @var string */
	private $currentPage;

	private $currentPageParams;

	public function configureActions()
	{
		return [];
	}

	public function onIncludeComponentLang()
	{
		$this->includeComponentLang(basename(__FILE__));
		Loc::loadMessages(__FILE__);
	}

	public function onPrepareComponentParams($params)
	{
		$params["SEF_FOLDER"] = (!empty($params["SEF_FOLDER"]) ? $params["SEF_FOLDER"] : "/bitrix/admin/");
		$params["SIDE_PANEL_PAGE_LIST"] = (!empty($params["SIDE_PANEL_PAGE_LIST"]) ? $params["SIDE_PANEL_PAGE_LIST"] : array());
		$params["INTERNAL_PAGE_LIST"] = (!empty($params["INTERNAL_PAGE_LIST"]) ? $params["INTERNAL_PAGE_LIST"] : array());

		$params["REQUEST_URI"] = $this->request->getRequestUri();

		$params["ADDITIONAL_PARAMS"] = (!empty($params["ADDITIONAL_PARAMS"]) ? $params["ADDITIONAL_PARAMS"] : array());
		$params["CONNECT_PAGE"] = (!empty($params["CONNECT_PAGE"]) ? $params["CONNECT_PAGE"] : "Y");
		$params["INTERNAL_PAGE"] = (!empty($params["INTERNAL_PAGE"]) ? $params["INTERNAL_PAGE"] : "N");

		$params["MENU_ITEMS"] = array();
		$params['MENU_MODE'] = isset($params['MENU_MODE']) && $params['MENU_MODE'] === 'Y';
		$params["PAGE_ID"] = $params["PAGE_ID"] ?? null;
		$params["PAGE_PATH"] = $params["PAGE_PATH"] ?? '';
		$params["PAGE_PARAMS"] = $params["PAGE_PARAMS"] ?? null;

		return $params;
	}

	public function executeComponent()
	{
		if (!$this->checkRequiredParams())
		{
			return null;
		}

		$this->initUrlBuilder();

		$this->initCurrentPage();

		$this->prepareMenuToRender();

		$this->formatResult();

		if ($this->arParams['MENU_MODE'])
		{
			return [
				'ITEMS' => $this->createFileMenuItems($this->arResult["MENU_ITEMS"]),
			];
		}

		$this->includeComponentTemplate();

		return null;
	}

	/**
	 * The method sets the parameters, such as SELF_FOLDER or if you want to get an url of additional store pages.
	 *
	 * @param $params
	 */
	public function prepareComponentParams($params)
	{
		$this->arParams = $this->onPrepareComponentParams($params);
	}

	/**
	 * The method returns a list of all store pages.
	 *
	 * @return array List shop urls.
	 */
	public function getShopUrls(): array
	{
		$this->prepareMenuToRender();

		$finalMenu = $this->getFinalMenu();

		return $this->getUrlsFromMenu($finalMenu);
	}

	public function getCatalogSectionsAction(): array
	{
		$post = $this->request->getPostList()->toArray();

		if (!$this->checkRequiredCatalogParams($post))
		{
			return [];
		}

		$items = [];

		if ($this->checkRequiredModules())
		{
			if (!self::checkCatalogReadPermission())
			{
				return $items;
			}

			$iblockId = (int)$post["iblock_id"];
			if (!$this->checkIblockId($iblockId))
			{
				return $items;
			}

			$this->initUrlBuilder();
			$this->urlBuilder->setIblockId($iblockId);
			$this->urlBuilder->setUrlParams([]);
			$queryObject = CIBlockSection::getList(
				["LEFT_MARGIN" => "ASC"],
				[
					"IBLOCK_ID" => $iblockId,
					"SECTION_ID" => (int)($post["section_id"] ?? 0),
				],
				false,
				[
					"ID",
					"NAME",
				]
			);
			while ($section = $queryObject->fetch())
			{
				$item = [
					"text" => htmlspecialcharsEx($section["NAME"]),
					"title" => htmlspecialcharsEx($section["NAME"]),
					"href" => $this->getSectionUrl((int)$section["ID"]),
				];
				if ($this->hasSectionChild($iblockId, $section["ID"]))
				{
					$item["ajaxOptions"] = $this->getAjaxOptions([
						"module_id" => "catalog",
						"params" => [
							"iblock_id" => $iblockId,
							"section_id" => $section["ID"],
							"selfFolder" => $post["selfFolder"]
						]
					]);
				}
				$items[] = $item;
			}
		}

		return $items;
	}

	private function checkIblockId(int $iblockId): bool
	{
		if ($iblockId <= 0)
		{
			return false;
		}
		if ($iblockId === Crm\Product\Catalog::getDefaultId())
		{
			return true;
		}
		$row = Iblock\IblockTable::getList([
			'select' => ['ID', 'XML_ID'],
			'filter' => ['=ID' => $iblockId],
		])->fetch();
		if (empty($row))
		{
			return false;
		}
		if (strncmp($row['XML_ID'], 'crm_external_', 13) === 0)
		{
			return false;
		}
		$row = Catalog\CatalogIblockTable::getList([
			'select' => ['IBLOCK_ID'],
			'filter' => [
				'LOGIC' => 'OR',
				'=PRODUCT_IBLOCK_ID' => $iblockId,
				[
					'=IBLOCK_ID' => $iblockId,
					'=PRODUCT_IBLOCK_ID' => 0,
				],
			],
			'limit' => 1,
		])->fetch();
		if (!empty($row))
		{
			return true;
		}

		return false;
	}

	private function checkRequiredCatalogParams(array $post): bool
	{
		if (!(int)$post["iblock_id"])
		{
			return false;
		}
		if (!isset($post["section_id"]))
		{
			return false;
		}

		return true;
	}

	private function checkRequiredModules(): bool
	{
		return (
			Loader::includeModule('iblock')
			&& Loader::includeModule('catalog')
			&& Loader::includeModule('crm')
		);
	}

	private function initUrlBuilder(): void
	{
		if ($this->checkRequiredModules() && $this->urlBuilder === null)
		{
			$this->urlBuilder = new Catalog\Url\ShopBuilder();
		}
	}

	private function initCurrentPage(): void
	{
		$this->currentPage = $this->request->getRequestUri();
		$pos = mb_strpos($this->currentPage, '?');
		if ($pos !== false)
		{
			$this->currentPage = mb_substr($this->currentPage, 0, $pos);
		}
		$this->currentPageParams = $this->request->getQueryList()->getValues();
	}

	private function hasSectionChild($iblockId, $sectionId)
	{
		$queryObject = CIBlockSection::getList(
			[],
			[
				"IBLOCK_ID" => $iblockId,
				"SECTION_ID" => $sectionId,
			],
			false,
			["ID"],
			["nTopCount" => 1]
		);
		return ($queryObject->fetch());
	}

	private function getSectionUrl(?int $sectionId): string
	{
		return $this->urlBuilder->getSectionListUrl($sectionId);
	}

	private function prepareMenuToRender()
	{
		$menu = $this->getMenu();

		$this->setPageList($menu);
		$additionalList = $this->getAdditionalList();
		$this->setPageList($additionalList);

		$this->setListMenuItems(array_merge($additionalList, $menu["items"]));
		$this->modifyMenuItems();
		$this->sortListMenuItems();
	}

	private function modifyMenuItems()
	{
		/**
		 * Catalog menu parent item
		 */
		$catalogParentId = null;
		foreach ($this->listMenuItems as $menuItemId => $menuItem)
		{
			if (!preg_match('/^menu_catalog_[0-9+]+$/', $menuItemId))
			{
				continue;
			}

			$catalogParentId = $menuItemId;
			break;
		}

		/**
		 * Catalog goods list menu item
		 */
		$catalogListId = null;
		if ($this->checkRequiredModules())
		{
			$catalogListId = Crm\Product\Catalog::getDefaultId();
		}

		if ($catalogListId !== null)
		{
			$catalogListId = 'menu_catalog_goods_'.$catalogListId;
		}

		if (isset($this->listMenuItems['menu_catalog_store']) || isset($this->listMenuItems[$catalogListId]))
		{
			$this->listMenuItems['menu_sale_goods_and_documents'] = [
				'ID' => 'menu_sale_goods_and_documents',
				'PARENT_ID' => null,
				'SORT' => 200,
				'TEXT' => Loc::getMessage('SHOP_MENU_GOODS_DOCUMENTS'),
			];
		}

		/**
		 * Mutating
		 */
		foreach ($this->listMenuItems as $menuItemId => $menuItem)
		{
			/**
			 * Buyers
			 */
			if ($menuItemId === 'menu_sale_buyers')
			{
				$this->listMenuItems[$menuItemId]['IS_DISABLED'] = true;

				$this->listMenuItems[$menuItemId] = array_merge(
					$menuItem,
					[
						'SORT' => 150,
						'PARENT_ID' => 'menu_sale_settings_all'
					]
				);
			}

			if ($this->checkRequiredModules())
			{
				if (
					$menuItemId === 'menu_catalog_store'
					&& !ToolAvailabilityManager::getInstance()->checkInventoryManagementAvailability()
				)
				{
					unset($this->listMenuItems[$menuItemId]);
					continue;
				}

				if (
					Loader::includeModule('intranet') // TODO: erase this code row after remove public files from intranet wizard 'portal'
				)
				{
					if ($menuItemId === 'menu_catalog_store')
					{
						$this->listMenuItems[$menuItemId]['PARENT_ID'] = 'menu_sale_goods_and_documents';
						$this->listMenuItems[$menuItemId]['SORT'] = 100;
						$this->listMenuItems[$menuItemId]['URL'] = '/shop/documents/';
						if (Manager::isOnecMode())
						{
							\Bitrix\Main\UI\Extension::load('catalog.external-catalog-stub');
							$this->listMenuItems[$menuItemId]['ON_CLICK'] = 'event.preventDefault();BX.Catalog.ExternalCatalogStub.showDocsStub();';
						}
						else
						{
							$this->listMenuItems[$menuItemId]['ON_CLICK'] = 'event.preventDefault();BX.SidePanel.Instance.open("/shop/documents/?inventoryManagementSource=shop", {cacheable: false, customLeftBoundary: 0});';
						}
					}

					if ($this->listMenuItems[$menuItemId]['PARENT_ID'] === 'menu_catalog_store')
					{
						unset($this->listMenuItems[$menuItemId]);
						continue;
					}
				}
				else if ($menuItemId === 'menu_catalog_store')
				{
					$this->listMenuItems[$menuItemId]['PARENT_ID'] = 'menu_sale_goods_and_documents';
					$this->listMenuItems[$menuItemId]['SORT'] = 100;
				}
			}

			/**
			 * Catalog
			 */
			if ($menuItemId === $catalogParentId)
			{
				$this->listMenuItems[$menuItemId] = array_merge(
					$menuItem,
					[
						'IS_DISABLED' => true,
						'PARENT_ID' => 'menu_sale_settings_all',
						'SORT' => 250,
					]
				);
			}

			/**
			 * Catalog goods list
			 */
			if ($menuItemId === $catalogListId)
			{
				$this->initUrlBuilder();
				$this->urlBuilder->setIblockId(Crm\Product\Catalog::getDefaultId());
				$url = $this->urlBuilder->getElementListUrl(-1);

				$newCatalogItemFields = [
					'PARENT_ID' => 'menu_sale_goods_and_documents',
					'SORT' => 50,
					'TEXT' => Loc::getMessage('SHOP_MENU_CATALOG_GOODS'),
					'URL' => $url,
				];

				if (Catalog\Config\State::isExternalCatalog())
				{
					\Bitrix\Main\UI\Extension::load('catalog.external-catalog-stub');
					$newCatalogItemFields['ON_CLICK'] = 'event.preventDefault();BX.Catalog.ExternalCatalogStub.showCatalogStub();';
				}

				$this->listMenuItems[$menuItemId] = array_merge(
					$menuItem,
					$newCatalogItemFields,
				);
			}

			if (Manager::isOnecMode())
			{
				$catalogRights = null;
				if (Catalog\Config\Feature::isAccessControllerCheckingEnabled())
				{
					$catalogRightsUrl = '/shop/settings/permissions/';
					$catalogRights = [
						'ID' => 'menu_catalog_permissions',
						'PARENT_ID' => 'menu_sale_goods_and_documents',
						'SORT' => 150,
						'TEXT' => Loc::getMessage('SHOP_MENU_CATALOG_PERMISSIONS'),
						'URL' => $catalogRightsUrl,
						'ON_CLICK' => 'BX.SidePanel.Instance.open("' . CUtil::JSEscape($catalogRightsUrl) . '"); return false;'
					];
				}
				else
				{
					$helpLink = Catalog\Config\Feature::getAccessControllerHelpLink();
					if (!empty($helpLink))
					{
						$catalogRights = [
							'ID' => 'menu_catalog_permissions',
							'PARENT_ID' => 'menu_sale_goods_and_documents',
							'SORT' => 150,
							'TEXT' => Loc::getMessage('SHOP_MENU_CATALOG_PERMISSIONS'),
							'URL' => '',
							'ON_CLICK' => $helpLink['LINK'],
							'IS_LOCKED' => true,
						];
					}
				}

				if ($catalogRights)
				{
					$this->listMenuItems['menu_catalog_permissions'] = $catalogRights;
				}

				\Bitrix\Main\UI\Extension::load(['catalog.config.settings']);

				$this->listMenuItems['menu_catalog_settings'] = [
					'ID' => 'menu_catalog_settings',
					'PARENT_ID' => 'menu_sale_goods_and_documents',
					'SORT' => 250,
					'TEXT' => Loc::getMessage('SHOP_MENU_CATALOG_SETTINGS'),
					'ON_CLICK' => 'BX.Catalog.Config.Slider.open("crm");',
				];
			}

			/**
			 * Settings
			 */
			if ($menuItemId === 'menu_sale_settings')
			{
				$this->listMenuItems[$menuItemId] = array_merge(
					$menuItem,
					[
						'IS_DISABLED' => true,
						'PARENT_ID' => 'menu_sale_settings_all',
						'TEXT' => Loc::getMessage('CRM_SHOP_MENU_ITEM_EXPERT_SETTINGS'),
					]
				);
			}

			/**
			 * Payment systems
			 */
			if ($menuItemId === 'sale_pay_system')
			{
				$this->listMenuItems['sale_pay_system_root'] = array_merge(
					$menuItem,
					[
						'ID' => 'sale_pay_system_root',
						'PARENT_ID' => null,
						'SORT' => 350,
						'TEXT' => Loc::getMessage('SHOP_MENU_PAY_SYSTEMS_DELIVERY'),
					]
				);

				$this->listMenuItems[$menuItemId] = array_merge(
					$menuItem,
					[
						'PARENT_ID' => 'sale_pay_system_root',
						'SORT' => 50,

					]
				);
			}

			/**
			 * Cashboxes
			 */
			if ($menuItemId === 'menu_sale_cashbox')
			{
				$this->listMenuItems[$menuItemId] = array_merge(
					$menuItem,
					[
						'PARENT_ID' => 'sale_pay_system_root',
						'SORT' => 100,

					]
				);
			}

			/**
			 * Delivery systems
			 */
			if ($menuItemId === 'sale_delivery_service_list')
			{
				$this->listMenuItems[$menuItemId] = array_merge(
					$menuItem,
					[
						'PARENT_ID' => 'sale_pay_system_root',
						'SORT' => 150,
					]
				);
			}

			/**
			 * Terminal
			 */
			if ($menuItemId === 'terminal')
			{
				$this->listMenuItems[$menuItemId] = array_merge(
					$menuItem,
					[
						'PARENT_ID' => 'sale_pay_system_root',
						'SORT' => 200,
					]
				);
			}
		}
	}

	private function getUrlsFromMenu(array $menu): array
	{
		$shopUrls = [];

		foreach ($menu as $item)
		{
			if (!empty($item["URL"]))
			{
				$shopUrls[$item["ID"]] = $item["URL"];
			}
			if (!empty($item["ITEMS"]))
			{
				$shopUrls = $shopUrls + $this->getUrlsFromMenu($item["ITEMS"]);
			}
		}
		return $shopUrls;
	}

	/**
	 * Check required params.
	 *
	 * @return bool
	 */
	protected function checkRequiredParams(): bool
	{
		if (empty($this->arParams["MENU_ID"]))
		{
			ShowError("Error: MENU_ID parameter missing.");
			return false;
		}

		return true;
	}

	/**
	 * @return array
	 */
	protected function getMenu(): array
	{
		$_REQUEST["public_menu"] = "Y";
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");

		$adminMenu = new CAdminMenu();

		$baseMenu = array(
			"menu_id" => "store",
			"items_id" => "global_menu_store",
			"items" => array()
		);
		$listMenu = array();
		$listModules = array("sale");
		foreach ($listModules as $module)
		{
			$fname = getLocalPath("modules/".$module."/admin/menu.php");
			if ($fname !== false)
			{
				$menu = $adminMenu->_includeMenu($_SERVER["DOCUMENT_ROOT"].$fname);
				if (is_array($menu) && !empty($menu))
				{
					if(isset($menu["parent_menu"]) && $menu["parent_menu"] == "global_menu_store")
					{
						$listMenu[] = $menu;
					}
					else
					{
						foreach($menu as $submenu)
						{
							if(is_array($submenu) && !empty($submenu))
							{
								$listMenu[] = $submenu;
							}
						}
					}
				}
			}
		}

		$globalMenu = array();
		foreach(GetModuleEvents("main", "OnBuildGlobalMenu", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array(&$globalMenu, &$listMenu));
		}

		if ($listMenu)
		{
			foreach ($listMenu as $menu)
			{
				if (isset($menu["parent_menu"]) && $menu["parent_menu"] == "global_menu_store")
				{
					$baseMenu["items"][] = $menu;
				}
			}
		}

		return $baseMenu;
	}

	protected function setPageList(array $menu)
	{
		$items = array();
		if ($this->isAssociativeArray($menu))
		{
			$items[] = $menu;
		}
		else
		{
			$items = $menu;
		}

		foreach ($items as $item)
		{
			$pageId = (!empty($item["items_id"]) ? $item["items_id"] : "");
			if ($pageId)
			{
				if (!empty($item["items"]))
				{
					$this->setPageList($item["items"]);
				}

				if (empty($item["url"]))
				{
					continue;
				}

				if ($this->arParams["SEF_FOLDER"] != "/bitrix/admin/" && mb_strpos($item["url"], "/bitrix/admin/") !== false)
				{
					$item["url"] = str_replace("/bitrix/admin/", "", $item["url"]);
				}
				$explode = explode("?", $item["url"]);
				$this->pageList[$pageId]["url"] = $explode[0];
				if (count($explode) == 2)
				{
					$this->pageList[$pageId]["params"] = $explode[1];
				}
				$this->pageList[$pageId]["fullUrl"] = $item["url"];
			}
		}
	}

	protected function getAdditionalList(): array
	{
		$marketingUrl = SITE_DIR . "marketing/?marketing_title=Y";

		$result = [
			[
				"parent_menu" => "menu_sale_settings_all",
				"sort" => 50,
				"text" => Loc::getMessage("SHOP_MENU_SHOP_MARKETING"),
				"additional" => "Y",
				"url" => SITE_DIR . "marketing/",
				"url_constant" => true,
				"on_click" => 'BX.SidePanel.Instance.open("' . CUtil::JSescape($marketingUrl) . '");',
				"items_id" => "menu_sale_discounts",
				"items" => [
					[
						"parent_menu" => "menu_sale_discounts",
						"sort" => 250.1,
						"text" => GetMessage("SHOP_MENU_DISCOUNTS_AND_SALES"),
						"title" => GetMessage("SHOP_MENU_DISCOUNTS_AND_SALES"),
						"url" => "sale_discount_preset_list.php",
						"items_id" => "sale_discount_preset_list",
					],
					[
						"parent_menu" => "menu_sale_discounts",
						"sort" => 250.2,
						"text" => GetMessage("SHOP_MENU_PRODUCT_MARKETING_COUPONS"),
						"title" => GetMessage("SHOP_MENU_PRODUCT_MARKETING_COUPONS"),
						"url" => "sale_discount_coupons.php",
						"items_id" => "sale_discount_coupons",
					]
				]
			],
			[
				"parent_menu" => "global_menu_store",
				"sort" => 400,
				"text" => GetMessage("SHOP_MENU_QUICK_START_SETTINGS"),
				"title" => GetMessage("SHOP_MENU_QUICK_START_SETTINGS"),
				"additional" => "Y",
				"url" => null,
				"items_id" => "menu_sale_settings_all",
			],
			[
				"parent_menu" => "menu_sale_buyers",
				"sort" => 401,
				"text" => GetMessage("SHOP_MENU_BUYER_GROUP_TITLE"),
				"title" => GetMessage("SHOP_MENU_BUYER_GROUP_TITLE"),
				"additional" => "Y",
				"url" => "/shop/buyer_group/",
				"url_constant" => true,
				"items_id" => "buyer_group_settings",
			],
			[
				"parent_menu" => "menu_sale_settings",
				"sort" => 710.05,
				"text" => GetMessage("SHOP_MENU_SETTINGS_SALE_SETTINGS"),
				"title" => GetMessage("SHOP_MENU_SETTINGS_SALE_SETTINGS"),
				"additional" => "Y",
				"url" => "/crm/configs/sale/?type=common",
				"url_constant" => true,
				"items_id" => "csc_sale_settings",
			],
			[
				"parent_menu" => "menu_sale_settings",
				"sort" => 710.1,
				"text" => GetMessage("SHOP_MENU_ORDER_FORM_SETTINGS_TITLE"),
				"title" => GetMessage("SHOP_MENU_ORDER_FORM_SETTINGS_TITLE"),
				"additional" => "Y",
				"url" => "/shop/orderform/",
				"url_constant" => true,
				"items_id" => "form_order_settings",
			],
			[
				"parent_menu" => "menu_sale_settings",
				"sort" => 709.1,
				"text" => GetMessage("SHOP_MENU_SETTINGS_STATUS"),
				"title" => GetMessage("SHOP_MENU_SETTINGS_STATUS"),
				"additional" => "Y",
				"url_constant" => true,
				"items_id" => "crm_sale_status",
				"items" => [
					[
						"parent_menu" => "crm_sale_status",
						"sort" => 709.2,
						"text" => GetMessage("SHOP_MENU_SETTINGS_STATUS_ORDER"),
						"title" => GetMessage("SHOP_MENU_SETTINGS_STATUS_ORDER"),
						"url" => "/crm/configs/sale/?type=order",
						"url_constant" => true,
						"items_id" => "crm_sale_status_orders",
					],
					[
						"parent_menu" => "crm_sale_status",
						"sort" => 709.3,
						"text" => GetMessage("SHOP_MENU_SETTINGS_STATUS_ORDER_SHIPMENT"),
						"title" => GetMessage("SHOP_MENU_SETTINGS_STATUS_ORDER_SHIPMENT"),
						"url" => "/crm/configs/sale/?type=shipment",
						"url_constant" => true,
						"items_id" => "crm_sale_status_shipment",
					],
				]
			],
			[
				"parent_menu" => "menu_sale_settings",
				"sort" => 711,
				"text" => GetMessage("SHOP_MENU_SETTINGS_USER_FIELDS"),
				"title" => GetMessage("SHOP_MENU_SETTINGS_USER_FIELDS"),
				"additional" => "Y",
				"url" => "/crm/configs/sale/?type=fields",
				"url_constant" => true,
				"items_id" => "userfield_edit",
			],
			[
				"parent_menu" => "menu_sale_settings",
				"sort" => 730,
				"text" => GetMessage("SHOP_MENU_PRODUCT_MARKETING_DISCOUNT"),
				"title" => GetMessage("SHOP_MENU_PRODUCT_MARKETING_DISCOUNT"),
				"additional" => "Y",
				"url" => "sale_discount.php",
				"items_id" => "sale_discount",
			],
		];

		if ($this->checkRequiredModules())
		{
			$accessRightsButton = [
				"parent_menu" => "menu_sale_settings",
				"sort" => 740,
				"text" => GetMessage("SHOP_MENU_CATALOG_RIGHTS_SETTINGS"),
				"title" => GetMessage("SHOP_MENU_CATALOG_RIGHTS_SETTINGS"),
				"additional" => "Y",
				"url_constant" => true,
				"items_id" => "menu_catalog_rights_settings",
			];


			if (Catalog\Config\Feature::isAccessControllerCheckingEnabled())
			{
				$accessRightsButton['url'] = "/shop/settings/permissions/";
				$accessRightsButton['url_constant'] = true;
			}
			else
			{
				$helpLink = Catalog\Config\Feature::getAccessControllerHelpLink();
				if (!empty($helpLink))
				{
					$accessRightsButton['is_locked'] = true;
					$accessRightsButton['on_click'] = $helpLink['LINK'];
				}
				else
				{
					$accessRightsButton = null;
				}
			}
		}

		if ($accessRightsButton)
		{
			$result[] = $accessRightsButton;
		}

		if (
			Loader::includeModule('landing')
			&& Landing\Rights::hasAdditionalRight(Landing\Rights::ADDITIONAL_RIGHTS['menu24'])
		)
		{
			$result[] = [
				'parent_menu' => 'global_menu_store',
				'sort' => 50,
				'text' => Loc::getMessage('SHOP_MENU_SITES'),
				'title' => Loc::getMessage('SHOP_MENU_SITES'),
				'additional' => 'Y',
				'url' => '/sites/',
				'url_constant' => true,
				'items_id' => 'sites',
			];
		}

		if (self::checkCatalogReadPermission())
		{
			$result[] = array(
				"parent_menu" => "global_menu_store",
				"sort" => 100,
				"text" => GetMessage("SHOP_MENU_SHOP_TITLE"),
				"title" => GetMessage("SHOP_MENU_SHOP_TITLE"),
				"additional" => "Y",
				"url" => "/shop/stores/",
				"url_constant" => true,
				"items_id" => "stores",
			);
		}

		if (\CCrmSaleHelper::isWithOrdersMode())
		{
			$result[] = array(
				"parent_menu" => "global_menu_store",
				"sort" => 150,
				"text" => GetMessage("SHOP_MENU_ORDER_TITLE"),
				"title" => GetMessage("SHOP_MENU_ORDER_TITLE"),
				"additional" => "Y",
				"url" => static::getOrderMenuUrl(),
				"url_constant" => true,
				"items_id" => "orders",
			);
		}
		else
		{
			Extension::load(['catalog.config.settings']);

			$result[] = [
				"parent_menu" => "menu_sale_settings",
				"sort" => 710.07,
				"text" => GetMessage("SHOP_MENU_SETTINGS_CATALOG_SETTINGS"),
				"title" => GetMessage("SHOP_MENU_SETTINGS_CATALOG_SETTINGS"),
				"additional" => "Y",
				'on_click' => 'BX.Catalog.Config.Slider.open(\'shop\'); return false;',
				"items_id" => "csc_catalog_settings",
			];
		}

		$isAdmin = Container::getInstance()->getUserPermissions()->isAdmin();
		$userPermissions = CCrmPerms::GetCurrentUserPermissions();

		$clientSubItems = [];
		if ($isAdmin || CCrmContact::CheckReadPermission(0, $userPermissions))
		{
			$contactListUrl = SITE_DIR . 'crm/contact/list/';
			$clientSubItems[] = array(
				'parent_menu' => 'crm_clients',
				'sort' => 50,
				'text' => Loc::getMessage('SHOP_MENU_CRM_CONTACTS'),
				'additional' => 'Y',
				'on_click' => 'BX.SidePanel.Instance.open("' . CUtil::JSescape($contactListUrl) . '"); return false;',
				'url' => $contactListUrl,
				'url_constant' => true,
				'items_id' => 'crm_contacts',
			);
		}

		if ($isAdmin || CCrmCompany::CheckReadPermission(0, $userPermissions))
		{
			$companyListUrl = SITE_DIR . 'crm/company/list/';
			$clientSubItems[] = array(
				'parent_menu' => 'crm_clients',
				'sort' => 100,
				'text' => Loc::getMessage('SHOP_MENU_CRM_COMPANIES'),
				'additional' => 'Y',
				'on_click' => 'BX.SidePanel.Instance.open("' . CUtil::JSescape($companyListUrl) . '"); return false;',
				'url' => $companyListUrl,
				'url_constant' => true,
				'items_id' => 'crm_companies',
			);
		}

		$contactCenterUrl = Container::getInstance()->getRouter()->getContactCenterUrl();
		$clientSubItems[] = array(
			'parent_menu' => 'crm_clients',
			'sort' => 150,
			'text' => Loc::getMessage('SHOP_MENU_CONTACT_CENTER'),
			'additional' => 'Y',
			'on_click' => 'BX.SidePanel.Instance.open("' . CUtil::JSescape($contactCenterUrl) . '"); return false;',
			'url' => $contactCenterUrl,
			'url_constant' => true,
			'items_id' => 'contact_center',
		);

		if (!empty($clientSubItems))
		{
			$result[] = [
				'parent_menu' => 'global_menu_store',
				'sort' => 250,
				'text' => Loc::getMessage('SHOP_MENU_CRM_CLIENTS'),
				'additional' => 'Y',
				'url' => $clientSubItems[0]['url'] ?? '',
				'on_click' => $clientSubItems[0]['on_click'] ?? '',
				'url_constant' => true,
				'items_id' => 'crm_clients',
				'items' => $clientSubItems,
			];
		}

		if ($isAdmin || !$userPermissions->havePerm('WEBFORM', BX_CRM_PERM_NONE, 'READ'))
		{
			$webformsUrl = SITE_DIR . 'crm/webform/';

			$result[] = [
				'parent_menu' => 'global_menu_store',
				'sort' => 300,
				'text' => Loc::getMessage('SHOP_MENU_WEBFORMS'),
				'additional' => 'Y',
				'url' => $webformsUrl,
				'on_click' => 'BX.SidePanel.Instance.open("' . CUtil::JSescape($webformsUrl) . '");',
				'url_constant' => true,
				'items_id' => 'webforms',
			];
		}

		if (
			Crm\Terminal\AvailabilityManager::getInstance()->isAvailable()
			&& Container::getInstance()->getIntranetToolsManager()->checkTerminalAvailability()
		)
		{
			$result[] = [
				'parent_menu' => 'global_menu_store',
				'sort' => 350,
				'text' => Loc::getMessage('SHOP_MENU_TERMINAL'),
				'title' => Loc::getMessage('SHOP_MENU_TERMINAL'),
				'additional' => 'Y',
				'url' => '/shop/terminal/',
				'url_constant' => true,
				'items_id' => 'terminal',
			];
		}

		return $result;
	}

	protected function setListMenuItems(array $menu, $parentPageId = "")
	{
		$items = array();
		if ($this->isAssociativeArray($menu))
		{
			$items[] = $menu;
		}
		else
		{
			$items = $menu;
		}

		foreach ($items as $item)
		{
			$pageId = (!empty($item["items_id"]) ? $item["items_id"] : "");
			if ($pageId)
			{
				$menuItem = array(
					"ID" => $pageId,
					"PARENT_ID" => $parentPageId,
					"TEXT" => $item["text"],
					"CLASS" => "",
					"CLASS_SUBMENU_ITEM" => "",
					"SUB_LINK" => array(),
					"COUNTER" => 0,
					"COUNTER_ID" => "",
					"IS_ACTIVE" => false,
					"IS_LOCKED" => $item['is_locked'] ?? false,
					"IS_DISABLED" => false,
					"ON_CLICK" => $item["on_click"] ?? null,
					"SORT" => (int)($item["sort"] ?? 0)
				);

				if (!empty($item["ajax_options"]))
				{
					$menuItem["AJAX_OPTIONS"] = $this->getAjaxOptions($item["ajax_options"]);
				}

				if (isset($item["additional"]))
				{
					if (!$parentPageId && $item["parent_menu"] && $item["parent_menu"] != "global_menu_store")
					{
						$menuItem["PARENT_ID"] = $item["parent_menu"];
					}
				}
				if (!empty($item["url"]))
				{
					if (isset($item["url_constant"]))
					{
						$menuItem["URL"] = $item["url"];
						$menuItem["URL_CONSTANT"] = true;
					}
					else
					{
						$menuItem["URL"] = $this->arParams["SEF_FOLDER"].$pageId."/";
						$itemUrl = explode("?", $item["url"]);
						if (count($itemUrl) == 2)
						{
							$menuItem["URL"] .= "?".$itemUrl[1];
						}
					}
				}
				$this->setAdditionalParams($menu);
				$this->listMenuItems[$pageId] = $menuItem;

				if (!empty($item["items"]))
				{
					$this->setListMenuItems($item["items"], $pageId);
				}
			}
		}
	}

	protected function sortListMenuItems()
	{
		$sortData = array();
		foreach ($this->listMenuItems as $pageId => $menuItem)
		{
			$sortData[$pageId]  = $menuItem["SORT"];
		}
		array_multisort($sortData, SORT_ASC, $this->listMenuItems);
	}

	protected function getPagePath(): array
	{
		$pageId = $this->getPageId();

		if (!empty($this->pageList[$pageId]))
		{
			if (!empty($this->arParams["INTERNAL_PAGE_LIST"][$pageId]))
			{
				return $this->getInternalPagePath($pageId);
			}

			return array($pageId, $this->pageList[$pageId]["url"], $this->pageList[$pageId]["params"]);
		}

		return $this->getInternalPagePath($pageId);
	}

	protected function getInternalPagePath($pageId): array
	{
		if (!empty($this->arParams["INTERNAL_PAGE_LIST"][$pageId]))
		{
			$pageParams = "";
			$requestUrl = $this->request->getRequestUri();
			$explode = explode("?", $requestUrl);
			if (count($explode) == 2)
			{
				$pageParams = $explode[1];
			}
			$this->arParams["INTERNAL_PAGE"] = "Y";
			return array($pageId, $this->arParams["INTERNAL_PAGE_LIST"][$pageId], $pageParams);
		}
		else
		{
			if ($this->getPagePathForAjaxPages($pageId))
			{
				$explode = explode("?", $this->request->getRequestUri());
				$pageParams = "";
				if (count($explode) == 2)
				{
					$pageParams = $explode[1];
				}
				return array($pageId, $this->getPagePathForAjaxPages($pageId), $pageParams);
			}
			else
			{
				$this->arParams["CONNECT_PAGE"] = "N";
				return array();
			}
		}
	}

	protected function getPageId()
	{
		$requestUrl = $this->request->getRequestedPage();

		$folder404 = str_replace("\\", "/", $this->arParams["SEF_FOLDER"]);
		if ($folder404 != "/")
			$folder404 = "/".trim($folder404, "/ \t\n\r\0\x0B")."/";

		if (mb_strpos($requestUrl, $folder404) !== 0)
		{
			return false;
		}

		if (($i = mb_strpos($requestUrl, '/index.php')) !== false)
		{
			$requestUrl = mb_substr($requestUrl, 0, $i);
		}

		return mb_substr($requestUrl, mb_strlen($folder404));
	}

	protected function formatResult()
	{
		$this->arResult = array();
		$this->arResult["MENU_ID"] = $this->arParams["MENU_ID"];
		$this->arResult["MENU_ITEMS"] = $this->getFinalMenu();
		$pagePath = $this->getPagePath();
		if ($pagePath)
		{
			[$this->arResult["PAGE_ID"], $this->arResult["PAGE_PATH"], $this->arResult["PAGE_PARAMS"]] = $pagePath;
		}

		$this->arResult["SEF_FOLDER"] = $this->arParams["SEF_FOLDER"];
		$this->arResult["CONNECT_PAGE"] = $this->arParams["CONNECT_PAGE"] === "Y";
		$this->arResult["INTERNAL_PAGE"] = $this->arParams["INTERNAL_PAGE"];
	}

	protected function createFileMenuItems($items, $depthLevel = 1): array
	{
		$result = [];
		foreach ($items as $item)
		{
			$hasChildren = isset($item['ITEMS']) && is_array($item['ITEMS']) && !empty($item['ITEMS']);

			$result[] = [
				$item['NAME'] ?? $item['TEXT'],
				$item['URL'] ?? null,
				[],
				[
					'DEPTH_LEVEL' => $depthLevel,
					'FROM_IBLOCK' => true,
					'IS_PARENT' => $hasChildren,
					'onclick' => $item['ON_CLICK'] ?? null,
				]
			];

			if ($hasChildren)
			{
				$result = array_merge($result, $this->createFileMenuItems($item['ITEMS'], $depthLevel + 1));
			}
		}

		return $result;
	}

	private function getFinalMenu(): array
	{
		$listMenu = $this->getMenuItems($this->listMenuItems);
		foreach ($listMenu as &$menu)
		{
			$this->setAdditionalParams($menu);
		}
		return $listMenu;
	}

	private function setAdditionalParams(&$menu)
	{
		if (isset($menu["ID"]) && array_key_exists($menu["ID"], $this->arParams["ADDITIONAL_PARAMS"]))
		{
			$menu = array_merge($menu, $this->arParams["ADDITIONAL_PARAMS"][$menu["ID"]]);
		}
	}

	/**
	 * @return string[]
	 */
	private static function getIgnorePageList(): array
	{
		$result = [
			"menu_order",
			"sale_cashbox_zreport",
			"1c_admin",
			"sale_crm",
			"update_system_market",
			"menu_sale_stat",
			"menu_sale_affiliates",
			"sale_company",
			"menu_sale_properties",
			"sale_archive",
			"sale_report_edit",
			"menu_sale_trading_platforms",
			"sale_location_zone_list",
			"sale_location_default_list",
			"sale_location_external_service_list",
			"sale_recurring_admin",
			"mnu_catalog_exp",
			"mnu_catalog_imp",
			"menu_sale_bizval",
			"sale_status",
			"sale_ps_handler_refund",
			"cat_agent_scheme",
		];

		if (!self::checkCatalogReadPermission())
		{
			$result = array_merge(
				$result,
				[
					"menu_sale_discounts",
					"menu_sale_settings"
				]
			);
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	private static function checkCatalogReadPermission(): bool
	{
		return \CCrmSaleHelper::isShopAccess();
	}

	private function getMenuItems(array &$listMenuItems, $parentPageId = ""): array
	{
		$ignorePageList = static::getIgnorePageList();

		$menuItems = array();

		foreach ($listMenuItems as $menuItem)
		{
			if (in_array($menuItem["ID"], $ignorePageList))
			{
				unset($listMenuItems[$menuItem["ID"]]);
				continue;
			}

			if (in_array($menuItem["ID"], $this->arParams["SIDE_PANEL_PAGE_LIST"]) ||
				in_array(preg_replace("/_\d+/", "", $menuItem["ID"]), $this->arParams["SIDE_PANEL_PAGE_LIST"]))
			{
				if (!isset($menuItem["URL_CONSTANT"]))
				{
					$menuItem["URL"] = $this->pageList[$menuItem["ID"]]["fullUrl"];
					if (mb_strpos($this->pageList[$menuItem["ID"]]["url"], $this->arParams["SEF_FOLDER"]) !== 0)
					{
						$menuItem["URL"] = $this->arParams["SEF_FOLDER"].$menuItem["URL"];
					}
					if (preg_match("/\.php/i", $menuItem["URL"]))
					{
						$menuItem["URL"] = str_replace(".php", "/", $menuItem["URL"]);
					}
				}
			}

			if ($menuItem["PARENT_ID"] == $parentPageId)
			{
				$isActive = false;
				$childMenuItems = $this->getMenuItems($listMenuItems, $menuItem["ID"]);
				if ($childMenuItems)
				{
					$menuItem["ITEMS"] = $childMenuItems;
					foreach ($childMenuItems as $childMenuItem)
					{
						if (
							$childMenuItem["IS_ACTIVE"]
							|| (
								isset($childMenuItem["URL"])
								&& $this->checkMenuItemUrl($childMenuItem["URL"])
							)
						)
						{
							$isActive = true;
							break;
						}
/*						if ($childMenuItem["URL"] == $this->arParams["REQUEST_URI"] || $childMenuItem["IS_ACTIVE"])
						{
							$isActive = true;
						} */
					}
				}
				if (
					$isActive
					|| (
						isset($menuItem["URL"])
						&& $this->checkMenuItemUrl($menuItem["URL"])
					)
				)
				{
					$menuItem["IS_ACTIVE"] = true;
				}
/*				if (($this->arParams["REQUEST_URI"] == $menuItem["URL"]) || $isActive)
				{
					$menuItem["IS_ACTIVE"] = true;
				} */
				$menuItems[] = $menuItem;
			}
		}

		return $menuItems;
	}

	private function checkMenuItemUrl(string $url): bool
	{
		$result = false;

		if ($url !== '' && mb_strpos($this->currentPage, $url) === 0)
		{
			$result = true;
		}

		if (!$result)
		{
			$pos = mb_strpos($url, '?');
			if ($pos !== false)
			{
				if (mb_substr($url, 0, $pos) === $this->currentPage)
				{
					$right = mb_substr($url, $pos+1);
					$paramList = explode('&', $right);
					$found = true;

					foreach ($paramList as $block)
					{
						$eqpos = mb_strpos($block, '=');
						$value = '';
						if ($eqpos === false)
						{
							$name = $block;
						}
						elseif ($eqpos === 0)
						{
							continue;
						}
						else
						{
							$name = mb_substr($block, 0, $eqpos);
							$value = urldecode(mb_substr($block, $eqpos+1));
						}

						$requestValue = $this->currentPageParams[$name] ?? '';
						if ($requestValue != $value)
						{
							$found = false;
							break;
						}
					}

					if ($found)
					{
						$result = true;
					}
				}
			}
		}

		return $result;
	}

	private function isAssociativeArray($array): bool
	{
		if (!is_array($array) || empty($array))
			return false;
		return array_keys($array) !== range(0, count($array) - 1);
	}

	private function getAjaxOptions(array $inputOptions): array
	{
		$ajaxOptions = [];

		$actionsMap = [
			"catalog" => [
				"mode" => "component",
				"componentMode" => "class",
				"action" => "getCatalogSections",
				"component" => "bitrix:crm.admin.page.controller"
			]
		];

		if (array_key_exists($inputOptions["module_id"], $actionsMap))
		{
			$moduleOptions = $actionsMap[$inputOptions["module_id"]];
			$ajaxOptions = [
				"mode" => $moduleOptions["mode"],
				"action" => $moduleOptions["action"],
				"component" => $moduleOptions["component"],
			];
			if (!empty($moduleOptions["componentMode"]))
			{
				$ajaxOptions["componentMode"] = $moduleOptions["componentMode"];
			}
			if (!empty($inputOptions["params"]))
			{
				$ajaxOptions["data"] = $inputOptions["params"];
				if ($this->getSignedParameters())
				{
					$ajaxOptions["data"]["selfFolder"] = $this->arParams["SEF_FOLDER"] ?? '';
				}
				else
				{
					$ajaxOptions["data"]["selfFolder"] = $inputOptions["params"]["selfFolder"] ?? '';
				}
/*				$ajaxOptions["data"]["selfFolder"] = $this->getSignedParameters() ?
					$this->arParams["SEF_FOLDER"] : $inputOptions["params"]["selfFolder"]; */
			}
		}

		return $ajaxOptions;
	}

	private function getPagePathForAjaxPages($pageId): string
	{
		if (mb_strpos($pageId, "menu_catalog_category") !== false)
		{
			return $this->arParams["SEF_FOLDER"]."cat_section_admin.php";
		}
		return "";
	}

	/**
	 * @return string
	 */
	private static function getOrderMenuUrl(): string
	{
		$result = '/shop/orders/';
		if (OrderSettings::getCurrent()->getCurrentListViewID() == OrderSettings::VIEW_KANBAN)
		{
			$result = '/shop/orders/kanban/';
		}

		return $result;
	}
}
