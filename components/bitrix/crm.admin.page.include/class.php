<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @global CMain $APPLICATION
 */

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Main\AccessDeniedException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;

class CCrmAdminPageInclude extends \CBitrixComponent
{
	/**
	 * Load language file.
	 */
	public function onIncludeComponentLang()
	{
		$this->includeComponentLang(basename(__FILE__));
		Loc::loadMessages(__FILE__);
	}

	public function onPrepareComponentParams($params)
	{
		$params["SEF_FOLDER"] = (!empty($params["SEF_FOLDER"]) ? $params["SEF_FOLDER"] : "/bitrix/admin/");
		$params["PAGE_ID"] = (!empty($params["PAGE_ID"]) ? $params["PAGE_ID"] : "");
		$params["PAGE_PATH"] = (!empty($params["PAGE_PATH"]) ? $params["PAGE_PATH"] : "");
		if (mb_strpos($params["PAGE_PATH"], $params["SEF_FOLDER"]) === false)
		{
			$params["PAGE_PATH"] = $params["SEF_FOLDER"].$params["PAGE_PATH"];
		}
		$params["PAGE_PARAMS"] = (!empty($params["PAGE_PARAMS"]) ? $params["PAGE_PARAMS"] : "");
		$params["PAGE_CONSTANTS"] =
			!empty($params["PAGE_CONSTANTS"]) && is_array($params["PAGE_CONSTANTS"])
				? $params["PAGE_CONSTANTS"]
				: []
		;
		$params["INTERNAL_PAGE"] = (!empty($params["INTERNAL_PAGE"]) ? $params["INTERNAL_PAGE"] : "N");

		if (isset($params['IS_SIDE_PANEL']))
		{
			$params['IS_SIDE_PANEL'] = !($params['IS_SIDE_PANEL'] === 'N');
		}
		else
		{
			$params['IS_SIDE_PANEL'] = (
				$this->request->get('IFRAME') === 'Y'
				&& $this->request->get('IFRAME_TYPE') === 'SIDE_SLIDER'
				&& $this->request->get('disableRedirect') !== 'Y'
			);
		}

		return $params;
	}

	/**
	 * Check required params.
	 *
	 * @throws SystemException
	 */
	protected function checkRequiredParams()
	{
		if (empty($this->arParams["PAGE_ID"]))
		{
			throw new SystemException("Error: PAGE_ID parameter missing.");
		}
	}

	/**
	 * Check required params.
	 *
	 * @throws SystemException
	 */
	protected function checkAccessRights(): bool
	{
		if (!Loader::includeModule('catalog'))
		{
			return true;
		}

		$isCatalogPage =
			mb_stripos($this->arParams['PAGE_ID'], 'menu_catalog_goods_') === 0
			|| preg_match('/^menu_catalog_\d+$/', $this->arParams['PAGE_ID']) === 1
		;
		if (\CCrmSaleHelper::isShopAccess())
		{
			return true;
		}
		elseif ($isCatalogPage)
		{
			throw new AccessDeniedException();
		}

		$action2pages = [
			ActionDictionary::ACTION_MEASURE_EDIT => [
				'cat_measure_list',
				'cat_measure_edit',
			],
			ActionDictionary::ACTION_VAT_EDIT => [
				'cat_vat_admin',
				'cat_vat_edit',
			],
			ActionDictionary::ACTION_PRICE_GROUP_EDIT => [
				'cat_group_admin',
				'cat_group_edit',
				'cat_round_list',
			],
			ActionDictionary::ACTION_PRODUCT_DISCOUNT_SET => [
				'sale_discount_preset_list',
				'sale_discount_coupons',
				'sale_discount',
			],
			ActionDictionary::ACTION_PRODUCT_PRICE_EXTRA_EDIT => [
				'cat_extra',
			],
		];
		foreach ($action2pages as $action => $pages)
		{
			if (in_array($this->arParams['PAGE_ID'], $pages, true))
			{
				$can = AccessController::getCurrent()->check($action);
				if (!$can)
				{
					throw new AccessDeniedException();
				}
				break;
			}
		}

		return true;
	}

	protected function getAddressMap()
	{
		//TODO We need to get rid of the connection through scripts
		return array(
			"cat_product_list" => array(
				"url" => "/bitrix/modules/iblock/admin/iblock_list_admin.php",
				"constants" => array("CATALOG_PRODUCT" => "Y")
			),
			"cat_product_admin" => array(
				"url" => "/bitrix/modules/iblock/admin/iblock_element_admin.php",
				"constants" => array("CATALOG_PRODUCT" => "Y")
			),
		);
	}

	/**
	 * Check that pages exists.
	 *
	 * @throws SystemException
	 */
	protected function checkPage()
	{
		$page = new \Bitrix\Main\IO\File($_SERVER['DOCUMENT_ROOT'].$this->arParams["PAGE_PATH"]);
		if (!$page->isExists())
		{
			throw new SystemException("Page not found");
		}
	}

	protected function getRealPagePath()
	{
		$addressMap = $this->getAddressMap();

		$constantList = $this->arParams['PAGE_CONSTANTS'];
		if (isset($addressMap[$this->arParams["PAGE_ID"]]))
		{
			$pageMap = $addressMap[$this->arParams["PAGE_ID"]];
			$this->arParams["PAGE_PATH"] = $pageMap["url"];
			if (!empty($pageMap["constants"]) && is_array($pageMap["constants"]))
			{
				$constantList = array_merge($pageMap["constants"], $constantList);
			}
		}
		if (!empty($constantList))
		{
			foreach ($constantList as $constant => $constantValue)
			{
				if (is_numeric($constant))
				{
					continue;
				}
				if (!defined($constant))
				{
					define($constant, $constantValue);
				}
			}
		}
	}

	protected function setSettings()
	{
		$_REQUEST["public"] = "Y";

		if ($this->arParams["PAGE_PARAMS"])
		{
			foreach(explode("&", $this->arParams["PAGE_PARAMS"]) as $param)
			{
				$explode = explode("=", $param);
				if (!isset($_REQUEST[$explode[0]]))
				{
					$_REQUEST[$explode[0]] = $explode[1];
				}
				if (!isset($_GET[$explode[0]]))
				{
					$_GET[$explode[0]] = $explode[1];
				}
			}
		}

		$_REQUEST["lang"] = LANGUAGE_ID;

		if (!defined('SELF_FOLDER_URL'))
		{
			define('SELF_FOLDER_URL', $this->arParams['SEF_FOLDER']);
		}
	}

	protected function formatResult()
	{
		$this->arResult = array();
		$this->arResult["PAGE_PATH"] = $this->arParams["PAGE_PATH"];
		$this->arResult["PAGE_PARAMS"] = $this->arParams["PAGE_PARAMS"];
		$this->arResult["IS_SIDE_PANEL"] = $this->arParams["IS_SIDE_PANEL"];
		$this->arResult["INTERNAL_PAGE"] = $this->arParams["INTERNAL_PAGE"] == "Y";
		$pagePath = $this->arResult["PAGE_PATH"]."?".$this->arResult["PAGE_PARAMS"];

		if ($this->arResult["IS_SIDE_PANEL"])
		{
			$this->arResult["REDIRECT_URL"] = \CHTTP::urlAddParams($pagePath,
				array("IFRAME" => "Y", "IFRAME_TYPE" => "SIDE_SLIDER"));
		}
		elseif($this->arResult["INTERNAL_PAGE"])
		{
			$this->arResult["FRAME_URL"] = \CHTTP::urlAddParams($pagePath,
				array("IFRAME" => "Y", "IFRAME_TYPE" => "PUBLIC_FRAME"));
		}
	}

	/**
	 * @return mixed|void
	 * @throws Exception
	 */
	public function executeComponent()
	{
		try
		{
			$this->checkRequiredParams();
			$this->checkAccessRights();
			$this->getRealPagePath();
			$this->checkPage();
			$this->setSettings();
			$this->formatResult();

			$this->includeComponentTemplate();
		}
		catch(AccessDeniedException $e)
		{
			$this->includeComponentTemplate('error');
		}
		catch(SystemException $e)
		{
			ShowError($e->getMessage());
		}
	}
}
