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
use Bitrix\Main\Web\Uri;

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

		if (isset($arParams['CACHE_TIME']) && intval($arParams['CACHE_TIME']) < 0)
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

	/**
	 * Return formatted form item url with params
	 *
	 * @param string $code
	 * @return array|string|string[]
	 */
	private function getFormsListLink(string $code, array $additionalOptions = [])
	{
		$uri = new Uri(\Bitrix\Crm\WebForm\Manager::getUrl());
		$options = ['apply_filter' => 'Y',];
		$options = array_merge($additionalOptions, $options);

		switch ($code)
		{
			case 'call':
				$options['IS_CALLBACK_FORM'] = 'Y';
				$options['PRESET'] = 'callback';
				break;
			case 'vkontakteads':
				$options['INTEGRATIONS'] = 'VKONTAKTE';
				$options['PRESET'] = 'vk';
				break;
			case 'facebookads':
				$options['INTEGRATIONS'] = 'FACEBOOK';
				$options['PRESET'] = 'facebook';
				break;
			case 'form':
			default:
				$options['clear_filter'] = 'Y';
				break;
		}
		$uri->addParams($options);

		return \CUtil::JSEscape($uri->getUri());
	}

	/**
	 * Return formatted form item url with params
	 *
	 * @param string $code
	 * @return array|string|string[]
	 */
	private function getFormCreateUrl(string $code)
	{
		global $USER;
		$CrmPerms = new CCrmPerms($USER->GetID());
		if ($CrmPerms->HavePerm('WEBFORM', BX_CRM_PERM_NONE, 'WRITE'))
		{
			return $this->getFormsListLink($code, ['show_permission_error' => 'Y']);
		}

		$uri = new Uri(
			\Bitrix\Crm\WebForm\Manager::getEditUrl(0)
		);
		$options = ['ACTIVE' => 'Y', 'ncc' => 1];

		switch ($code)
		{
			case 'call':
				$options['IS_CALLBACK_FORM'] = 'Y';
				$options['PRESET'] = 'callback';
				break;
			case 'vkontakteads':
				$options['PRESET'] = 'vk';
				break;
			case 'facebookads':
				$options['PRESET'] = 'facebook';
				break;
			case 'form':
			default:
				break;
		}
		$uri->addParams($options);

		return \CUtil::JSEscape($uri->getUri());
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
				$crmItem["ITEM_CODE"] = $itemCode;
				$itemParams = [
					'moduleId' => 'crm',
					'itemCode' => $itemCode
				];

				$formKeys = [
					'form',
					'call',
					'vkontakteads',
					'facebookads',
				];

				if (in_array($itemCode, $formKeys, true))
				{
					$crmItem['LIST'] = [
						[
							'NAME' => Loc::getMessage("CONTACT_CENTER_CRM_FORMS_CREATE"),
							'FIXED' => false,
							'ONCLICK' => "top.window.location='{$this->getFormCreateUrl($itemCode)}'",
						],
						[
							'NAME' => Loc::getMessage('CONTACT_CENTER_CRM_FORMS_VIEW_ALL'),
							'FIXED' => true,
							'ONCLICK' => $this->getOnclickScript(
								$this->getFormsListLink($itemCode),
								$itemParams
							),
						],
						[
							'NAME' => Loc::getMessage('CONTACT_CENTER_CRM_FORMS_HELP'),
							'FIXED' => false,
							'ONCLICK' => "top.BX.Helper.show('redirect=detail&code=6875449');",
						]
					];

					$this->jsParams["menu"][] = [
						'element' => "menu$itemCode",
						'bindElement' => "feed-add-post-form-link-text-$itemCode",
						'items' => $crmItem["LIST"]
					];
				}
				elseif (!empty($crmItem["LIST"]))
				{
					if ($itemCode !== 'crm_shop')
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

					if (!empty($crmItem['SIDEPANEL_PARAMS']) && is_array($crmItem['SIDEPANEL_PARAMS']))
					{
						$itemParams = array_merge($itemParams, $crmItem['SIDEPANEL_PARAMS']);
					}
					$crmItem["ONCLICK"] = $this->getOnclickScript($crmItem["LINK"], $itemParams);

					if (isset($crmItem['LINK_TYPE']) && $crmItem['LINK_TYPE'] === 'newWindow')
					{
						$crmItem["ONCLICK"] = "top.window.location='{$crmItem["LINK"]}'";
					}
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
				$sliderWidth = 700;

				$itemParams = [
					'width' => $sliderWidth,
					'moduleId' => 'imopenlines',
					'itemCode' => $itemCode,
					'allowChangeHistory' => true
				];

				$this->jsParams['menu'][] = [
					'element' => 'menu' . $itemCode,
					'bindElement' => 'feed-add-post-form-link-text-' . $itemCode,
					'items' => !empty($item['LIST']) ? $item['LIST'] : '',
				];

				if(!$item['CONNECTION_INFO_HELPER_LIMIT'])
				{
					$item['ONCLICK'] = $this->getOnclickScript($item['LINK'] ?? null, $itemParams);
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
		if (isset($itemParams["width"]) && intval($itemParams["width"]) > 0)
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

	private function getRuleLink(): string
	{
		$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();

		switch ($region)
		{
			case 'ru':
				return 'https://www.bitrix24.ru/abuse/contact-center.php';
			case 'en':
				return 'https://www.bitrix24.com/terms/contact_center-rules.php';
			case 'la':
				return 'https://www.bitrix24.es/terms/contact_center-rules.php';
			case 'br':
				return 'https://www.bitrix24.com.br/terms/contact_center-rules.php';
			case 'eu':
				return 'https://www.bitrix24.eu/terms/contact_center-rules.php';
			case 'de':
				return 'https://www.bitrix24.de/terms/contact_center-rules.php';
			case 'fr':
				return 'https://www.bitrix24.fr/terms/contact_center-rules.php';
			case 'pl':
				return 'https://www.bitrix24.pl/terms/contact_center-rules.php';
			case 'it':
				return 'https://www.bitrix24.it/terms/contact_center-rules.php';
			case 'in':
				return 'https://www.bitrix24.in/terms/contact_center-rules.php';
			case 'tr':
				return 'https://www.bitrix24.com.tr/terms/contact_center-rules.php';
			case 'cn':
				return 'https://www.bitrix24.cn/terms/contact_center-rules.php';
			case 'id':
				return 'https://www.bitrix24.id/terms/contact_center-rules.php';
			case 'ms':
				return 'https://www.bitrix24.com/my/terms/contact_center-rules.php';
			case 'th':
				return 'https://www.bitrix24.com/th/terms/contact_center-rules.php';
			case 'vn':
				return 'https://www.bitrix24.vn/terms/contact_center-rules.php';
			case 'jp':
				return 'https://www.bitrix24.jp/terms/contact_center-rules.php';
			case 'co':
				return 'https://www.bitrix24.co/terms/contact_center-rules.php';
			case 'mx':
				return 'https://www.bitrix24.mx/terms/contact_center-rules.php';
			case 'uk':
				return 'https://www.bitrix24.uk/terms/contact_center-rules.php';
			case 'by':
				return 'https://www.bitrix24.by/abuse/contact-center.php';
			case 'kz':
				return 'https://www.bitrix24.kz/abuse/contact-center.php';
			case 'ua':
				return '';
			default:
				return 'https://www.bitrix24.com/terms/contact_center-rules.php';
		}
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
			$this->arResult["RULE_LINK"] = $this->getRuleLink();

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