<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @global CMain $APPLICATION
 */

use Bitrix\Main\Context;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Crm;
use Bitrix\Crm\Settings\OrderSettings;

class CCrmAdminPageController extends \CBitrixComponent implements Controllerable
{
	private $pageList = array();
	private $listMenuItems = array();

	/** @var \Bitrix\Crm\Product\Url\ShopBuilder */
	private $urlBuilder = null;

	/** @var string */
	private $currentPage = null;

	private $currentPageParams = null;

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

		return $params;
	}

	public function executeComponent()
	{
		try
		{
			$this->checkRequiredParams();

			$this->initUrlBuilder();

			$this->initCurrentPage();

			$this->prepareMenuToRender();

			$this->formatResult();

			$this->includeComponentTemplate();
		}
		catch(SystemException $e)
		{
			ShowError($e->getMessage());
		}
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
	public function getShopUrls()
	{
		$this->prepareMenuToRender();

		$finalMenu = $this->getFinalMenu();

		return $this->getUrlsFromMenu($finalMenu);
	}

	public function getCatalogSectionsAction()
	{
		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		if (!$this->checkRequiredCatalogParams($post))
		{
			return [];
		}

		$items = [];

		if ($this->checkRequiredModules())
		{
			$this->initUrlBuilder();;

			$iblockId = (int) $post["iblock_id"];
			$this->urlBuilder->setIblockId($iblockId);
			$this->urlBuilder->setUrlParams([]);
			$queryObject = CIBlockSection::getList(
				["LEFT_MARGIN" => "ASC"],
				[
					"IBLOCK_ID" => $post["iblock_id"],
					"SECTION_ID" => $post["section_id"],
				],
				false,
				["ID", "NAME", "LEFT_MARGIN", "RIGHT_MARGIN"]
			);
			while ($section = $queryObject->fetch())
			{
				$item = [
					"text" => htmlspecialcharsEx($section["NAME"]),
					"title" => htmlspecialcharsEx($section["NAME"]),
					"href" => $this->getSectionUrl($iblockId, $section["ID"], $post["selfFolder"]),
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

	private function checkRequiredCatalogParams(array $post)
	{
		if (!intval($post["iblock_id"]))
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
			$this->urlBuilder = new Crm\Product\Url\ShopBuilder();
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

	private function getSectionUrl($iblockId, $sectionId, $selfFolder)
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
			if (!preg_match('/^menu_catalog_[0-9]+$/', $menuItemId))
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
		foreach ($this->listMenuItems as $menuItemId => $menuItem)
		{
			if (!preg_match('/^menu_catalog_goods_[0-9]+$/', $menuItemId))
			{
				continue;
			}

			$catalogListId = $menuItemId;
			break;
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
			}

			/**
			 * Catalog store
			 */
			if ($menuItemId === 'menu_catalog_store')
			{
				$this->listMenuItems[$menuItemId]['IS_DISABLED'] = true;
			}

			/**
			 * Catalog
			 */
			if ($menuItemId === $catalogParentId)
			{
				$this->listMenuItems[$menuItemId] = array_merge(
					$menuItem,
					[
						'SORT' => 600,
						'IS_DISABLED' => true,
					]
				);

				$this->listMenuItems[$menuItemId]['IS_DISABLED'] = true;
			}

			/**
			 * Catalog goods list
			 */
			if ($menuItemId === $catalogListId)
			{
				$this->listMenuItems[$menuItemId] = array_merge(
					$menuItem,
					[
						'PARENT_ID' => null,
						'TEXT' => Loc::getMessage('CRM_SHOP_MENU_ITEM_GOODS'),
					]
				);
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
						'TEXT' => Loc::getMessage('CRM_SHOP_MENU_ITEM_EXPERT_SETTINGS'),
					]
				);
			}

			/**
			 * Payment systems
			 */
			if ($menuItemId === 'sale_pay_system')
			{
				$this->listMenuItems[$menuItemId] = array_merge(
					$menuItem,
					[
						'PARENT_ID' => 'menu_sale_quick_start_settings',
						'SORT' => 290,
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
						'PARENT_ID' => 'menu_sale_quick_start_settings',
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
						'PARENT_ID' => 'menu_sale_quick_start_settings',
						'SORT' => 310,
					]
				);
			}
		}
	}

	private function getUrlsFromMenu(array $menu)
	{
		$shopUrls = [];

		foreach ($menu as $itemId => $item)
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
	 * @throws SystemException
	 */
	protected function checkRequiredParams()
	{
		if (empty($this->arParams["MENU_ID"]))
		{
			throw new SystemException("Error: MENU_ID parameter missing.");
		}
	}

	/**
	 * @return array
	 */
	protected function getMenu()
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
				if (!empty($item["items"]))
				{
					$this->setPageList($item["items"]);
				}
			}
		}
	}

	protected function getAdditionalList()
	{
		$marketingUrl = SITE_DIR . "marketing/?marketing_title=Y";

		$result = array(
			array(
				"parent_menu" => "global_menu_store",
				"sort" => 250,
				"text" => GetMessage("SHOP_MENU_PRODUCT_MARKETING_TITLE"),
				"title" => GetMessage("SHOP_MENU_PRODUCT_MARKETING_TITLE"),
				"additional" => "Y",
				"url" => SITE_DIR . "marketing/",
				"url_constant" => true,
				"on_click" => 'BX.SidePanel.Instance.open("' . CUtil::JSescape($marketingUrl) . '");',
				"items_id" => "menu_sale_discounts",
				"items" => array(
					array(
						"parent_menu" => "menu_sale_discounts",
						"sort" => 250.1,
						"text" => GetMessage("SHOP_MENU_DISCOUNTS_AND_SALES"),
						"title" => GetMessage("SHOP_MENU_DISCOUNTS_AND_SALES"),
						"url" => "sale_discount_preset_list.php",
						"items_id" => "sale_discount_preset_list",
					),
					array(
						"parent_menu" => "menu_sale_discounts",
						"sort" => 250.2,
						"text" => GetMessage("SHOP_MENU_PRODUCT_MARKETING_COUPONS"),
						"title" => GetMessage("SHOP_MENU_PRODUCT_MARKETING_COUPONS"),
						"url" => "sale_discount_coupons.php",
						"items_id" => "sale_discount_coupons",
					)
				)
			),
			array(
				"parent_menu" => "global_menu_store",
				"sort" => 275,
				"text" => GetMessage("SHOP_MENU_QUICK_START_SETTINGS"),
				"title" => GetMessage("SHOP_MENU_QUICK_START_SETTINGS"),
				"additional" => "Y",
				"url" => null,
				"items_id" => "menu_sale_quick_start_settings",
				"items" => array(
				)
			),
			array(
				"parent_menu" => "menu_sale_buyers",
				"sort" => 401,
				"text" => GetMessage("SHOP_MENU_BUYER_GROUP_TITLE"),
				"title" => GetMessage("SHOP_MENU_BUYER_GROUP_TITLE"),
				"additional" => "Y",
				"url" => "/shop/buyer_group/",
				"url_constant" => true,
				"items_id" => "buyer_group_settings",
			),
			array(
				"parent_menu" => "menu_sale_settings",
				"sort" => 710.05,
				"text" => GetMessage("SHOP_MENU_SETTINGS_SALE_SETTINGS"),
				"title" => GetMessage("SHOP_MENU_SETTINGS_SALE_SETTINGS"),
				"additional" => "Y",
				"url" => "/crm/configs/sale/?type=common",
				"url_constant" => true,
				"items_id" => "csc_sale_settings",
			),
			array(
				"parent_menu" => "menu_sale_settings",
				"sort" => 710.1,
				"text" => GetMessage("SHOP_MENU_ORDER_FORM_SETTINGS_TITLE"),
				"title" => GetMessage("SHOP_MENU_ORDER_FORM_SETTINGS_TITLE"),
				"additional" => "Y",
				"url" => "/shop/orderform/",
				"url_constant" => true,
				"items_id" => "form_order_settings",
			),
			array(
				"parent_menu" => "menu_sale_settings",
				"sort" => 709.1,
				"text" => GetMessage("SHOP_MENU_SETTINGS_STATUS"),
				"title" => GetMessage("SHOP_MENU_SETTINGS_STATUS"),
				"additional" => "Y",
				"url_constant" => true,
				"items_id" => "crm_sale_status",
				"items" => array(
					array(
						"parent_menu" => "crm_sale_status",
						"sort" => 709.2,
						"text" => GetMessage("SHOP_MENU_SETTINGS_STATUS_ORDER"),
						"title" => GetMessage("SHOP_MENU_SETTINGS_STATUS_ORDER"),
						"url" => "/crm/configs/sale/?type=order",
						"url_constant" => true,
						"items_id" => "crm_sale_status_orders",
					),
					array(
						"parent_menu" => "crm_sale_status",
						"sort" => 709.3,
						"text" => GetMessage("SHOP_MENU_SETTINGS_STATUS_ORDER_SHIPMENT"),
						"title" => GetMessage("SHOP_MENU_SETTINGS_STATUS_ORDER_SHIPMENT"),
						"url" => "/crm/configs/sale/?type=shipment",
						"url_constant" => true,
						"items_id" => "crm_sale_status_shipment",
					),
				)
			),
			array(
				"parent_menu" => "menu_sale_settings",
				"sort" => 711,
				"text" => GetMessage("SHOP_MENU_SETTINGS_USER_FIELDS"),
				"title" => GetMessage("SHOP_MENU_SETTINGS_USER_FIELDS"),
				"additional" => "Y",
				"url" => "/crm/configs/sale/?type=fields",
				"url_constant" => true,
				"items_id" => "userfield_edit",
			),
			array(
				"parent_menu" => "menu_sale_settings",
				"sort" => 730,
				"text" => GetMessage("SHOP_MENU_PRODUCT_MARKETING_DISCOUNT"),
				"title" => GetMessage("SHOP_MENU_PRODUCT_MARKETING_DISCOUNT"),
				"additional" => "Y",
				"url" => "sale_discount.php",
				"items_id" => "sale_discount",
			),
		);

		if (\CCrmSaleHelper::isWithOrdersMode())
		{
			$result[] = array(
				"parent_menu" => "global_menu_store",
				"sort" => 100,
				"text" => GetMessage("SHOP_MENU_ORDER_TITLE"),
				"title" => GetMessage("SHOP_MENU_ORDER_TITLE"),
				"additional" => "Y",
				"url" => static::getOrderMenuUrl(),
				"url_constant" => true,
				"items_id" => "orders",
			);
		}

		if (CCrmSaleHelper::isShopAccess("admin"))
		{
			$result[] = array(
				"parent_menu" => "global_menu_store",
				"sort" => 150,
				"text" => GetMessage("SHOP_MENU_SHOP_TITLE"),
				"title" => GetMessage("SHOP_MENU_SHOP_TITLE"),
				"additional" => "Y",
				"url" => "/shop/stores/",
				"url_constant" => true,
				"items_id" => "stores",
			);
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

		foreach ($items as $itemId => $item)
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
					"IS_LOCKED" => false,
					"IS_DISABLED" => false,
					"ON_CLICK" => $item["on_click"] ?? null,
					"SORT" => $item["sort"] ? $item["sort"] : 0
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

	protected function getPagePath()
	{
		$pageId = $this->getPageId();

		if ($this->pageList[$pageId])
		{
			if (!empty($this->arParams["INTERNAL_PAGE_LIST"][$pageId]))
			{
				return $this->getInternalPagePath($pageId);
			}
			else
			{
				return array($pageId, $this->pageList[$pageId]["url"], $this->pageList[$pageId]["params"]);
			}
		}
		else
		{
			return $this->getInternalPagePath($pageId);
		}
	}

	protected function getInternalPagePath($pageId)
	{
		if (!empty($this->arParams["INTERNAL_PAGE_LIST"][$pageId]))
		{
			$pageParams = "";
			$requestUrl = Context::getCurrent()->getRequest()->getRequestUri();
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
				$request = Context::getCurrent()->getRequest();
				$explode = explode("?", $request->getRequestUri());
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
		$requestUrl = Bitrix\Main\Context::getCurrent()->getRequest()->getRequestedPage();

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
		list($this->arResult["PAGE_ID"], $this->arResult["PAGE_PATH"],
			$this->arResult["PAGE_PARAMS"]) = $this->getPagePath();
		$this->arResult["SEF_FOLDER"] = $this->arParams["SEF_FOLDER"];
		$this->arResult["CONNECT_PAGE"] = $this->arParams["CONNECT_PAGE"] === "Y";
		$this->arResult["INTERNAL_PAGE"] = $this->arParams["INTERNAL_PAGE"];
	}

	private function getFinalMenu()
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
		if (array_key_exists($menu["ID"], $this->arParams["ADDITIONAL_PARAMS"]))
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
		];

		if (!CCrmSaleHelper::isShopAccess('admin'))
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

	private function getMenuItems(array &$listMenuItems, $parentPageId = "")
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

						$requestValue = isset($this->currentPageParams[$name]) ? $this->currentPageParams[$name] : '';
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

	private function isAssociativeArray($array)
	{
		if (!is_array($array) || empty($array))
			return false;
		return array_keys($array) !== range(0, count($array) - 1);
	}

	private function getAjaxOptions(array $inputOptions)
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
				$ajaxOptions["data"]["selfFolder"] = $this->getSignedParameters() ?
					$this->arParams["SEF_FOLDER"] : $inputOptions["params"]["selfFolder"];
			}
		}

		return $ajaxOptions;
	}

	private function getPagePathForAjaxPages($pageId)
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
