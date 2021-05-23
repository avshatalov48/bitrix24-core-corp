<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Intranet\ContactCenter;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CIntranetContactCenterListComponent extends \CBitrixComponent implements Controllerable, Errorable
{
	/** @var  ErrorCollection */
	private $errorCollection;

	private $moduleList = array(
		'mail',
		'voximplant',
		'crm',
		'imopenlines',
		'sale',
//		'rest'
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
		$this->errorCollection = new ErrorCollection();

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
	 * Return list of blocks for Sale module
	 *
	 * @return array
	 */
	private function saleGetItems()
	{
		return $this->getContactCenterHandler()->saleGetItems()->getData();
	}

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
					$newEditorEnabled = class_exists('Bitrix\Crm\Settings\WebFormSettings') && Bitrix\Crm\Settings\WebFormSettings::getCurrent()->isNewEditorEnabled();
					if (in_array($itemCode, ['form', 'call']) && $newEditorEnabled)
					{
						$crmItem["LIST"] = array_map(
							function ($item)
							{
								$link = CUtil::JSEscape($item['LINK']);
								$item['ONCLICK'] = "window.location='{$link}'";
								return $item;
							},
							$crmItem["LIST"]
						);
					}
					else
					{
						$crmItem["LIST"] = $this->setMenuItemsClickAction($crmItem["LIST"], $itemParams);
					}

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
					'itemCode' => $itemCode,
					'allowChangeHistory' => false
				);

				$this->jsParams["menu"][] = array(
					"element" => "menu" . $itemCode,
					"bindElement" => "feed-add-post-form-link-text-" . $itemCode,
					"items" => ($item["LIST"] ?: "")
				);

				if(!$item['CONNECTION_INFO_HELPER_LIMIT'])
				{
					$item['ONCLICK'] = $this->getOnclickScript($item['LINK'], $itemParams);
				}
				else
				{
					$item['ONCLICK'] = 'BX.UI.InfoHelper.show(\'' . $item['CONNECTION_INFO_HELPER_LIMIT'] . '\');';
				}
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
		$systemItems = ["ccplacement", "chatbot", "telephonybot"];
		//$canInstallApplication = \CRestUtil::canInstallApplication();
		$canInstallApplication = true; //because we have to show this blocks for all users - whatever they can't install it without admin permission

		if ($canInstallApplication)
		{
			$itemsList["ccplacement"]["ONCLICK"] = "BX.rest.Marketplace.open({PLACEMENT: '".\CIntranetRestService::CONTACT_CENTER_PLACEMENT."'});";
			$itemsList["chatbot"]["ONCLICK"] = "BX.rest.Marketplace.open({}, 'chat_bots');";
			$itemsList["telephonybot"]["ONCLICK"] = "BX.rest.Marketplace.open({PLACEMENT: 'IVR_BOT'});";
		}

		foreach ($itemsList as $itemCode => &$item)
		{
			if (in_array($itemCode, $systemItems) || isset($item['ONCLICK']))
			{
				if (!$canInstallApplication)
				{
					unset($itemsList[$itemCode]);
				}
			}
			else
			{
				$variableItemCode = "closeHandler".str_replace('.', '_', $itemCode);
				$item["ONCLICK"] =  "var ".$variableItemCode." = function(){var curSlider = BX.SidePanel.Instance.getTopSlider();".
									"BX.SidePanel.Instance.postMessage(curSlider, 'ContactCenter:reloadItem', {moduleId:'rest',itemCode:'".$itemCode."'})}; ".
									"BX.rest.AppLayout.openApplication(".$item["APP_ID"].",".
									"{ID:".$item["PLACEMENT_ID"]."},".
									"{PLACEMENT:'".\CIntranetRestService::CONTACT_CENTER_PLACEMENT."', PLACEMENT_ID:".$item["PLACEMENT_ID"]."},".
									$variableItemCode.",".
									");";
			}
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
		$this->jsParams["parentSelector"] = 'intranet-contact-list';
		$this->jsParams['parentSelectorPartnersBlock'] = 'intranet-contact-rest-list';

		return $this->jsParams;
	}

	/**
	 * Return JS-params for initialization contact-center view
	 *
	 * @return mixed
	 */
	private function getJsRestParams()
	{
		$this->jsParams["signedParameters"] = $this->getSignedParameters();
		$this->jsParams["componentName"] = $this->getName();
		$this->jsParams["parentSelector"] = 'intranet-contact-rest-list';

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
		if (isset($itemParams["allowChangeHistory"]) && $itemParams["allowChangeHistory"] === false)
		{
			$params[] = "allowChangeHistory:false";
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

	private function getRestItems()
	{
		$itemsList = array();

		if (Loader::includeModule('rest'))
		{
			$itemsList[] = $this->restGetItems();
		}

		return $itemsList;
	}

	public function getRestAppAction($code)
	{
		$row = \Bitrix\Rest\AppTable::getRow([
			'select' => [
				'ID', 'APP_NAME', 'CLIENT_ID', 'CLIENT_SECRET',
				'URL_INSTALL', 'STATUS',
				'MENU_NAME' => 'LANG.MENU_NAME',
				'MENU_NAME_DEFAULT' => 'LANG_DEFAULT.MENU_NAME',
				'MENU_NAME_LICENSE' => 'LANG_LICENSE.MENU_NAME',
			],
			'filter' => [
				'=CODE' => $code
			],
		]);
		if(!$row)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('ICCL_MARKETPLACE_APPLICATION_NOT_FOUND_ERROR'));
			return null;
		}

		$isLocal = $row['STATUS'] === \Bitrix\Rest\AppTable::STATUS_LOCAL;
		if($isLocal)
		{
			$onlyApi = empty($row['MENU_NAME']) && empty($row['MENU_NAME_DEFAULT']) && empty($row['MENU_NAME_LICENSE']);
			return [
				'TYPE' => $onlyApi ? 'A' : 'N'
			];
		}

		$result = \Bitrix\Rest\Marketplace\Client::getApp($code);
		if(isset($result['ITEMS']))
		{
			return $result['ITEMS'];
		}

		$this->errorCollection[] = new Error(Loc::getMessage('ICCL_MARKETPLACE_APPLICATION_NOT_FOUND_ERROR'));
		return null;
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
			$this->arResult['REST_ITEMS'] = $this->getRestItems();
			$this->arResult["JS_PARAMS"] = $this->getJsParams();
			$this->arResult["ADDITIONAL_STYLES"] = $this->additionalStyles;

			$this->includeComponentTemplate();
		}
	}

	/**
	 * @return array|\Bitrix\Main\Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @param string $code
	 * @return \Bitrix\Main\Error|null
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}