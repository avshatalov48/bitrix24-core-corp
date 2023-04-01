<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @global CMain $APPLICATION
 */

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\AccessDeniedException;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;

class CCrmShopPageController extends CBitrixComponent
{
	public function onPrepareComponentParams($params)
	{
		$params["ADDITIONAL_PARAMS"] = (!empty($params["ADDITIONAL_PARAMS"]) ? $params["ADDITIONAL_PARAMS"] : array());
		$params["CONNECT_PAGE"] = (!empty($params["CONNECT_PAGE"]) ? $params["CONNECT_PAGE"] : "Y");

		return $params;
	}

	public function executeComponent()
	{
		try
		{
			$this->checkRequiredParams();
			$this->checkUsageStatus();
			$this->setMenuCount();
			$this->formatResult();
			$this->initCore();
			\CCrmInvoice::installExternalEntities();
			$this->includeComponentTemplate();
		}
		catch(AccessDeniedException $e)
		{
			if ($this->arParams["CONNECT_PAGE"] === "Y")
			{
				$GLOBALS['APPLICATION']->SetTitle(\Bitrix\Main\Localization\Loc::getMessage('CRM_CATALOG_TITLE'));
				Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
				$this->includeComponentTemplate('error');
			}
		}
		catch(SystemException $e)
		{
			ShowError($e->getMessage());
		}
	}

	/**
	 * @throws AccessDeniedException
	 */
	protected function checkRequiredParams()
	{
		if (
			!Loader::includeModule("crm")
			|| !Loader::includeModule("catalog")
			|| !CCrmSaleHelper::isShopAccess()
		)
		{
			throw new AccessDeniedException();
		}
	}

	protected function checkUsageStatus()
	{
		$requestUrl = Bitrix\Main\Context::getCurrent()->getRequest()->getRequestedPage();
		if (mb_strpos($requestUrl, "/shop/orders/menu/") === false)
		{
			return;
		}

		$storesUrl = '/shop/stores/';
		if (!CCrmSaleHelper::isWithOrdersMode())
		{
			LocalRedirect($storesUrl);
		}

		$isOrder = false;
		$isStore = false;

		if (Loader::includeModule("sale"))
		{
			if (Bitrix\Sale\Internals\OrderTable::getList(array("select" => array("ID"), "limit" => 1))->fetch())
			{
				$isOrder = true;
			}
		}

		if (Loader::includeModule("landing"))
		{
			if (Bitrix\Landing\Site::getList(
				array("select" => array("ID"),"filter" => array("=TYPE" => "STORE")))->fetch())
			{
				$isStore = true;
			}
		}

		if (!$isOrder && !$isStore)
		{
			LocalRedirect($storesUrl);
		}
	}

	protected function setMenuCount()
	{
		$additionalParams = $this->arParams["ADDITIONAL_PARAMS"];

		if (Loader::includeModule("crm"))
		{
			global $USER;
			$orderCounter = Bitrix\Crm\Counter\EntityCounterFactory::create(
				CCrmOwnerType::Order,
				Bitrix\Crm\Counter\EntityCounterType::ALL,
				CCrmSecurityHelper::GetCurrentUserID()
			);
			CUserCounter::set($USER->getId(), "shop_all", $orderCounter->getValue(), SITE_ID, "", false);
			$orderParams = array(
				"COUNTER" => $orderCounter->getValue(),
				"COUNTER_ID" => $orderCounter->getCode()
			);
			$this->arParams["GLOBAL_MENU_COUNTER"] = $orderCounter->getValue();
			if (!array_key_exists("orders", $additionalParams))
			{
				$additionalParams["orders"] = array();
			}
			$additionalParams["orders"] = array_merge($additionalParams["orders"], $orderParams);
		}

		$this->arParams["ADDITIONAL_PARAMS"] = $additionalParams;
	}

	protected function formatResult()
	{
		$this->arResult = array();

		$this->arResult["ADDITIONAL_PARAMS"] = $this->arParams["ADDITIONAL_PARAMS"];
		$this->arResult["GLOBAL_MENU_COUNTER"] = $this->arParams["GLOBAL_MENU_COUNTER"] ?: 0;
		$this->arResult["CONNECT_PAGE"] = $this->arParams["CONNECT_PAGE"];
	}

	protected function initCore()
	{
		Extension::load(['admin_interface', 'sidepanel']);
	}
}
