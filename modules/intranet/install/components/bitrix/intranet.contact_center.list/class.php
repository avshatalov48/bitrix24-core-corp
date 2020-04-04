<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Loader;
use \Bitrix\Main\Engine\Contract\Controllerable;

use \Bitrix\Intranet\ContactCenter;

class CIntranetContactCenterListComponent extends \CBitrixComponent implements Controllerable
{
	private $moduleList = array(
		'mail',
		'voximplant',
		'crm',
		'imopenlines',
		'rest'
	);
	private $jsCoreList = array(
		'sidepanel',
		'marketplace',
		'applayout'
	);

	private $jsParams;
	private $contactCenterHandler;
	private $additionalStyles = array();

	public function onPrepareComponentParams($arParams)
	{
		if (intval($arParams['CACHE_TIME']) < 0)
		{
			$arParams['CACHE_TIME'] = 86400;
		}

		return parent::onPrepareComponentParams($arParams);
	}

	protected function getContactCenterHandler()
	{
		if (empty($this->contactCenterHandler))
		{
			$this->contactCenterHandler = new ContactCenter();
		}

		return $this->contactCenterHandler;
	}

	/**
	 * @param bool $additionalCacheId
	 * @return string
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getCacheID($additionalCacheId = false)
	{
		$additionalCacheId = !empty($additionalCacheId) && is_array($additionalCacheId) ? $additionalCacheId : array();

		$additionalCacheId[] = CIntranetUtils::IsExternalMailAvailable();

		if (Loader::includeModule("voximplant"))
		{
			$additionalCacheId[] = \Bitrix\Voximplant\Security\Helper::isMainMenuEnabled();
		}
		if (Loader::includeModule("crm"))
		{
			$additionalCacheId[] = CCrmPerms::IsAccessEnabled();
		}
		if (Loader::includeModule("imopenlines") && Loader::includeModule("imconnector"))
		{
			$additionalCacheId[] = \Bitrix\ImOpenlines\Security\Helper::isMainMenuEnabled();
		}
		if (Loader::includeModule("rest"))
		{
			$additionalCacheId[] = \CRestUtil::canInstallApplication();
		}

		$additionalCacheId = array_merge($additionalCacheId, $this->arParams);

		return parent::getCacheID($additionalCacheId);
	}

	//methods called in getItems for getting connectors from current modules from $moduleList

	/**
	 * Return list of blocks for Mail module
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function mailGetItems()
	{
		$itemsList = array();

		if (CIntranetUtils::IsExternalMailAvailable())
		{
			$itemsList = $this->getContactCenterHandler()->mailGetItems()->getData();

			if (!empty($itemsList["mail"]))
			{
				$itemParams = array(
					'moduleId' => 'mail',
					'itemCode' => 'mail'
				);
				$itemsList["mail"]["ONCLICK"] = ($itemsList["mail"]["SELECTED"] ?
					"window.open('" . $itemsList["mail"]["LINK"] . "','_blank');" : $this->getOnclickScript($itemsList["mail"]["LINK"], $itemParams));
				$this->jsParams["handleMailLinks"] = true;
			}
		}

		return $itemsList;
	}

	/**
	 * Return list of blocks for Voximplant module
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function voximplantGetItems()
	{
		$itemsList = array();

		//The same conditions as in superleft_ext menu
		if (\Bitrix\Voximplant\Security\Helper::isMainMenuEnabled())
		{
			$itemsList = $this->getContactCenterHandler()->voximplantGetItems()->getData();

			if (!empty($itemsList["voximplant"]))
			{
				$itemParams = array(
					'moduleId' => 'voximplant',
					'itemCode' => 'voximplant'
				);
				$itemsList["voximplant"]["ONCLICK"] = $this->getOnclickScript($itemsList["voximplant"]["LINK"], $itemParams);
			}
		}

		return $itemsList;
	}

	//CRM-blocks ----------------------------------------------------------------
	/**
	 * Return list of blocks for CRM module
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function crmGetItems()
	{
		$itemsList = array();

		if (CCrmPerms::IsAccessEnabled())
		{
			//setting preset forms and widgets if not installed yet
			if (\Bitrix\Crm\SiteButton\Preset::checkVersion())
			{
				$preset = new \Bitrix\Crm\SiteButton\Preset();
				$preset->install();
			}

			$itemsList = $this->getContactCenterHandler()->crmGetItems()->getData();

			foreach ($itemsList as $itemCode => &$crmItem)
			{
				if (!empty($crmItem["LIST"]))
				{
					$itemParams = array(
						'moduleId' => 'crm',
						'itemCode' => $itemCode
					);
					$crmItem["LIST"] = $this->setMenuItemsClickAction($crmItem["LIST"], $itemParams);

					$this->jsParams["menu"][] = array(
						"element" => "menu" . $itemCode,
						"bindElement" => "feed-add-post-form-link-text-" . $itemCode,
						"items" => $crmItem["LIST"]
					);
				}
				else
				{
					$itemParams = array(
						'moduleId' => 'crm',
						'itemCode' => $itemCode
					);
					if (!empty($crmItem['SIDEPANEL_WIDTH']))
					{
						$itemParams['width'] = $crmItem['SIDEPANEL_WIDTH'];
					}

					$crmItem["ONCLICK"] = $this->getOnclickScript($crmItem["LINK"], $itemParams);
				}
			}
		}

		return $itemsList;
	}

	/**
	//End CRM blocks----------------------------------------------------------------

	 * Return list of blocks for Imopenlines module
	 * (imconnector connectors list)
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function imopenlinesGetItems()
	{
		$itemsList = array();

		if (\Bitrix\ImOpenlines\Security\Helper::isMainMenuEnabled())
		{
			$itemsList = $this->getContactCenterHandler()->imopenlinesGetItems()->getData();

			foreach ($itemsList as $itemCode => &$item)
			{
				$itemParams = array(
					'width' => 700,
					'moduleId' => 'imopenlines',
					'itemCode' => $itemCode
				);
				$item["ONCLICK"] = $this->getOnclickScript($item["LINK"], $itemParams);
			}
		}

		$this->additionalStyles[] = \Bitrix\ImConnector\CustomConnectors::getStyleCss();

		return $itemsList;
	}

	/**
	 * Return list of blocks for Rest module
	 * (rest apps with additional blocks)
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function restGetItems()
	{
		$itemsList = $this->getContactCenterHandler()->restGetItems()->getData();

		foreach ($itemsList as &$item)
		{
			$item["ONCLICK"] = "BX.rest.AppLayout.openApplication(".$item["APP_ID"].",{ID:".$item["PLACEMENT_ID"]."},{PLACEMENT:'".\CIntranetRestService::CONTACT_CENTER_PLACEMENT."', PLACEMENT_ID:".$item["PLACEMENT_ID"]."})";
		}

		return $itemsList;
	}

	//Service functions-------------------------------------------------------------

	/**
	 * Set onclick-action field for menu list items
	 *
	 * @param $itemsList
	 * @param array $itemParams
	 *
	 * @return mixed
	 */
	private function setMenuItemsClickAction($itemsList, $itemParams = array())
	{
		foreach ($itemsList as &$menuItem)
		{
			if (!empty($menuItem["LIST"]))
			{
				$menuItem["LIST"] = $this->setMenuItemsClickAction($menuItem["LIST"], $itemParams);
			}
			else
			{
				$menuItem["ONCLICK"] = $this->getOnclickScript($menuItem["LINK"], $itemParams);
			}
		}

		return $itemsList;
	}

	/**
	 * Get list of params for ajax component reload
	 *
	 * @return array
	 */
	protected function listKeysSignedParameters()
	{
		//We list the names of the parameters to be used in Ajax actions
		$result = array();

		if (!empty($arParams['SIGNED_PARAMETERS']))
		{
			$result = $arParams['SIGNED_PARAMETERS'];
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return array();
	}

	/**
	 * Reload blocks using ajax-request
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function reloadAction()
	{
		ob_start();
		$this->executeComponent();
		$html = ob_get_clean();
		return array(
			'html' => $html,
			'js_data' => $this->getJsParams()
		);
	}

	/**
	 * Reload single block using ajax-request
	 *
	 * @param $moduleId
	 * @param $itemCode
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function reloadItemAction($moduleId, $itemCode)
	{
		$result = array();
		$methodName = $moduleId . 'GetItems';
		if (method_exists($this, $methodName) && Loader::includeModule($moduleId))
		{
			$items = call_user_func(array($this, $methodName));
		}

		if (!empty($items[$itemCode]))
		{
			$result = $items[$itemCode];
		}

		return array(
			'data' => $result,
			'js_data' => $this->getJsParams()
		);
	}

	/**
	 * Return JS-params for initialization contact-center view
	 *
	 * @return mixed
	 */
	private function getJsParams()
	{
		$this->jsParams["signedParameters"] = $this->getSignedParameters();
		$this->jsParams["componentName"] = $this->getName();
		$this->jsParams["restPlacement"] = \CIntranetRestService::CONTACT_CENTER_PLACEMENT;

		return $this->jsParams;
	}

	/**
	 * Add url to list of contact-center slider urls for correct slider close event handling
	 *
	 * @param $url
	 */
	private function addSliderUrlMask($url)
	{
		$this->jsParams["sliderUrls"][] = htmlspecialcharsbx('^' . preg_quote($url) . '([0-9a-zA-Z_\\-/&\\?\\=]*)');
	}

	/**
	 * Return script for onclick action
	 *
	 * @param $link
	 * @param array $itemParams
	 *
	 * @return string
	 */
	public function getOnclickScript($link, $itemParams = array())
	{
		$params = array();
		$reloadParams = '';
		if (intval($itemParams["width"]) > 0)
		{
			$params[] = "width: " . intval($itemParams["width"]);
		}
		if (!empty($itemParams["moduleId"]) && !empty($itemParams["itemCode"]))
		{
			$reloadParams = "{moduleId:'".$itemParams["moduleId"]."',itemCode:'".$itemParams["itemCode"]."'}";
		}

		$params[] = "events: {onClose: function(e){BX.SidePanel.Instance.postMessage(e.getSlider(), 'ContactCenter:reloadItem', ".$reloadParams.")}}";

		$result = "BX.SidePanel.Instance.open('".$link."'";
		if (!empty($params))
		{
			$result .= ", {" . implode(", ", $params) . "}";
		}
		$result .= ");";

		return $result;
	}

	//End of service functions--------------------------------------------------------

	/**
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function getItems()
	{
		$itemsList = array();

		foreach($this->moduleList as $module)
		{
			$methodName = $module . 'GetItems';
			if (method_exists($this, $methodName) && Loader::includeModule($module))
			{
				$itemsList[$module] = call_user_func(array($this, $methodName));
			}
		}

		return $itemsList;
	}

	/**
	 * @return mixed|void
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function executeComponent()
	{
		\CJSCore::init($this->jsCoreList);

		if ($this->startResultCache())
		{
			$this->arResult["ITEMS"] = $this->getItems();
			$this->arResult["JS_PARAMS"] = $this->getJsParams();
			$this->arResult["ADDITIONAL_STYLES"] = $this->additionalStyles;
			$this->arResult["SHOW_APP_BANNER"] = false;
			/*if (Loader::includeModule("rest"))
			{
				$this->arResult["SHOW_APP_BANNER"] = \CRestUtil::canInstallApplication();
			}*/

			$this->includeComponentTemplate();
		}
	}
}