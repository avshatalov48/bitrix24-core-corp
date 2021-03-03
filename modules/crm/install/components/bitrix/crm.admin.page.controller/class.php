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
		$params["ADDITIONAL_LIST"] = (!empty($params["ADDITIONAL_LIST"]) ? $params["ADDITIONAL_LIST"] : array());
		$params["IGNORE_PAGE_LIST"] = (!empty($params["IGNORE_PAGE_LIST"]) ? $params["IGNORE_PAGE_LIST"] : array());
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
		$this->sortListMenuItems();
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
		$listModules = array("sale", "catalog");
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
		$additionalList = array();
		foreach ($this->arParams["ADDITIONAL_LIST"] as $additionalListBase)
		{
			$additionalListBase["additional"] = "Y";
			$additionalList[] = $additionalListBase;
		}
		return $additionalList;
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

	private function getMenuItems(array &$listMenuItems, $parentPageId = "")
	{
		$menuItems = array();

		foreach ($listMenuItems as $menuItem)
		{
			if (in_array($menuItem["ID"], $this->arParams["IGNORE_PAGE_LIST"]))
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
}