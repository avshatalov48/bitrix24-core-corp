<?php
namespace Bitrix\Intranet;


use Bitrix\Main\Application;
use \Bitrix\Main\Error;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Result;
use Bitrix\Main\UI\Extension;
use \Bitrix\Main\Web\Uri;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;
use Bitrix\Voximplant\Limits;

Loc::loadMessages(__FILE__);

class ContactCenter
{
	const CC_MODULE_NOT_LOADED = 1;

	private $cisCheck;
	private $modules = array(
		"mail",
		"voximplant",
		"crm",
		"imopenlines",
		"rest"
	);


	/**
	 *
	 */
	public function _construct()
	{

	}

	/**
	 * Return contact-center items for all modules
	 *
	 * @param array $filter
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getItems($filter = array())
	{
		$itemsList = array();
		$modules = $this->modules;
		if (!empty($filter["MODULES"]) && is_array($filter["MODULES"]))
		{
			$modules = $this->modulesIntersect($filter["MODULES"]);
		}

		foreach ($modules as $module)
		{
			$methodName = $module . "GetItems";
			if (method_exists($this, $methodName) && Loader::includeModule($module))
			{
				$result = call_user_func_array(array($this, $methodName), $filter);
				if ($result instanceof Result)
				{
					$itemsList[$module] = $result->getData();
				}
			}
		}

		return $itemsList;
	}

	/**
	 * Return all items for certain module
	 *
	 * @param $moduleId
	 * @param array $filter
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getModuleItems($moduleId, $filter = array())
	{
		$filter["MODULES"] = array($moduleId);
		$items = $this->getItems($filter);

		return $items[$moduleId];
	}

	/**
	 * Get certain block from module
	 *
	 * @param $itemCode
	 * @param $moduleId
	 * @param array $filter
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getModuleItem($itemCode, $moduleId, $filter = array())
	{
		$moduleItems = $this->getModuleItems($moduleId, $filter);
		$result = !empty($moduleItems[$itemCode]) ? $moduleItems[$itemCode] : array();

		return $result;
	}

	/**
	 * Return items from mail module
	 *
	 * @param array $filter
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function mailGetItems($filter = array())
	{
		$result = new Result();
		$module = "mail";
		$itemsList = array();

		if (!Loader::includeModule($module))
		{
			$result->addError(new Error(Loc::getMessage("CONTACT_CENTER_ERROR_MODULE_NOT_LOADED", array("#MODULE_ID" => $module)), self::CC_MODULE_NOT_LOADED));
		}
		else
		{
			$count = count(\Bitrix\Mail\MailboxTable::getUserMailboxes());
			$selected = $count > 0;

			$isAddItemToList = $this->isAddItemToList($filter["ACTIVE"], $selected);

			if ($isAddItemToList)
			{
				$itemsList["mail"] = array(
					"NAME" => Loc::getMessage("CONTACT_CENTER_MAIL"),
					"SELECTED" => $selected,
					"LOGO_CLASS" => "ui-icon ui-icon-service-email"
				);
				$itemsList["mail"]["LINK"] = ($selected ? \CUtil::JsEscape(Option::get("intranet", "path_mail_client", SITE_DIR . "mail/")) : "/mail/config/");
			}
		}

		$result->setData($itemsList);

		return $result;
	}

	/**
	 * Return items from voximplant module
	 *
	 * @param array $filter
	 *
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function voximplantGetItems($filter = array())
	{
		$result = new Result();
		$module = "voximplant";
		$itemsList = array();

		if (!Loader::includeModule($module))
		{
			$result->addError(new Error(Loc::getMessage("CONTACT_CENTER_ERROR_MODULE_NOT_LOADED", array("#MODULE_ID" => $module)), self::CC_MODULE_NOT_LOADED));
		}
		else
		{
			$canCall = true;
			if ($filter["CHECK_REGION"] !== "N")
			{
				if (Loader::includeModule("bitrix24"))
				{
					$licensePrefix = \CBitrix24::getLicensePrefix();
					$canCall = $licensePrefix !== "by";
				}
			}

			if ($canCall)
			{
				$lines = \CVoxImplantConfig::GetLines(true, true);
				$selected = count($lines) > 0;
				$isAddItemToList = $this->isAddItemToList($filter["ACTIVE"], $selected);

				if ($isAddItemToList)
				{
					$itemsList["voximplant"] = array(
						"NAME" => Loc::getMessage("CONTACT_CENTER_TELEPHONY"),
						"LINK" => \CUtil::JSEscape(SITE_DIR . "telephony/index.php"),
						"SELECTED" => $selected,
						"LOGO_CLASS" => "ui-icon ui-icon-service-call"
					);

					$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
					if(Limits::canRentMultiple() && $permissions->canModifyLines())
					{
						Extension::load(["voximplant.numberrent"]);
						$itemsList["voximplant_rent5"] = array(
							"NAME" => Loc::getMessage("CONTACT_CENTER_RENT_5_NUMBERS"),
							"ONCLICK" => "BX.Voximplant.NumberRent.create({packetSize: 5}).show();",
							"SELECTED" => \CVoxImplantPhone::hasRentedNumberPacket(5),
							"LOGO_CLASS" => "ui-icon ui-icon-package-numbers-five"

						);
						$itemsList["voximplant_rent10"] = array(
							"NAME" => Loc::getMessage("CONTACT_CENTER_RENT_10_NUMBERS"),
							"ONCLICK" => "BX.Voximplant.NumberRent.create({packetSize: 10}).show();",
							"SELECTED" => \CVoxImplantPhone::hasRentedNumberPacket(10),
							"LOGO_CLASS" => "ui-icon ui-icon-package-numbers-ten"
						);
					}
				}
			}
		}

		$result->setData($itemsList);

		return $result;
	}

	/**
	 * Return items from crm module
	 *
	 * @param array $filter
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function crmGetItems($filter = array())
	{
		$result = new Result();
		$module = "crm";
		$itemsList = array();

		if (!Loader::includeModule($module))
		{
			$result->addError(new Error(Loc::getMessage("CONTACT_CENTER_ERROR_MODULE_NOT_LOADED", array("#MODULE_ID" => $module)), self::CC_MODULE_NOT_LOADED));
		}
		else
		{
			if (\Bitrix\Crm\Tracking\Manager::isAccessible())
			{
				$itemsList["calltracking"] = $this->getCallTrackingFormListItem();
			}

			$itemsList["widget"] = $this->getButtonListItem($filter);
			$itemsList["form"] = $this->getFormListItem($filter);

			if (Loader::includeModule("voximplant") && !empty(\Bitrix\Crm\WebForm\Callback::getPhoneNumbers()))
			{
				$itemsList["call"] = $this->getCallFormListItem($filter);
			}

			if (\Bitrix\Crm\Ads\AdsForm::canUse())
			{
				$itemsList = array_merge($itemsList, $this->getAdsFormListItems($filter));
			}

			if (isset($filter["ACTIVE"]))
			{
				foreach ($itemsList as $key => $item)
				{
					$isAddItemToList = $this->isAddItemToList($filter["ACTIVE"], $item["SELECTED"]);

					if (!$isAddItemToList)
					{
						unset($itemsList[$key]);
					}
				}
			}
		}

		$result->setData($itemsList);

		return $result;
	}

	/**
	 * Return items from imopenlines(imconnector) module
	 *
	 * @param array $filter
	 *
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function imopenlinesGetItems($filter = array())
	{
		$result = new Result();
		$module = "imopenlines";
		$itemsList = array();

		if (!Loader::includeModule($module))
		{
			$result->addError(new Error(Loc::getMessage("CONTACT_CENTER_ERROR_MODULE_NOT_LOADED", array("#MODULE_ID" => $module)), self::CC_MODULE_NOT_LOADED));
		}
		elseif (!Loader::includeModule("imconnector"))
		{
			$result->addError(new Error(Loc::getMessage("CONTACT_CENTER_ERROR_MODULE_NOT_LOADED", array("#MODULE_ID" => "imconnector")), self::CC_MODULE_NOT_LOADED));
		}
		else
		{
			//For whole list of botframework instances use getListConnector()
			$connectors = \Bitrix\ImConnector\Connector::getListConnectorMenu(true);
			$statusList = \Bitrix\ImConnector\Status::getInstanceAll();
			$linkTemplate = \Bitrix\ImOpenLines\Common::getPublicFolder() . "connector/";
			$codeMap = \Bitrix\ImConnector\Connector::getIconClassMap();
			$cisOnlyConnectors = array("vkgroup", "vkgrouporder", "yandex");
			$cisCheck = $this->cisCheck() && $filter["CHECK_REGION"] !== "N";

			foreach ($connectors as $code => $connector)
			{
				if ($cisCheck && in_array($code, $cisOnlyConnectors))
				{
					continue;
				}

				$selected = false;
				$selectedOrder = false;

				if (!empty($statusList[$code]))
				{
					foreach ($statusList[$code] as $lineId => $status)
					{
						if (($status instanceof \Bitrix\ImConnector\Status))
						{
							if ($status->isStatus())
							{
								$selected = true;
								$connector["link"] = \CUtil::JSEscape( $linkTemplate . "?ID=" . $code . "&LINE=" . $lineId);

								if ($code != "vkgroup")
									break;
							}

							if ($code == "vkgroup" && !empty($status->getData()))
							{
								$data = $status->getData();
								if ($data["get_order_messages"] === "Y")
								{
									$selectedOrder = true;
								}
							}
						}
					}
				}

				$isAddItemToList = $this->isAddItemToList($filter["ACTIVE"], $selected);

				if ($isAddItemToList)
				{
					$itemsList[$code] = array(
						"NAME" => $connector["name"],
						"LINK" => !empty($connector["link"]) ? $connector["link"] : \CUtil::JSEscape( $linkTemplate . "?ID=" . $code),
						"SELECTED" => $selected,
						"LOGO_CLASS" => "ui-icon ui-icon-service-" . $codeMap[$code]
					);

					if ($code == "vkgroup")
					{
						$isAddItemToList = $this->isAddItemToList($filter["ACTIVE"], $selectedOrder);

						if ($isAddItemToList)
						{
							$uri = new Uri($itemsList["vkgroup"]["LINK"]);
							$uri->addParams(array("group_orders" => "Y"));
							$itemsList["vkgrouporder"] = array(
								"NAME" => Loc::getMessage("CONTACT_CENTER_IMOPENLINES_VK_ORDER"),
								"LINK" => \CUtil::JSEscape($uri->getUri()),
								"SELECTED" => $selectedOrder,
								"LOGO_CLASS" => "ui-icon ui-icon-service-" . $codeMap["vkgrouporder"]
							);
						}
					}
				}
			}
		}

		$result->setData($itemsList);

		return $result;
	}

	/**
	 * Return items from rest module
	 *
	 * @param array $filter
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function restGetItems($filter = array())
	{
		$result = new Result();
		$module = "rest";
		$itemsList = array();

		if (!Loader::includeModule($module))
		{
			$result->addError(new Error(Loc::getMessage("CONTACT_CENTER_ERROR_MODULE_NOT_LOADED", array("#MODULE_ID" => $module)), self::CC_MODULE_NOT_LOADED));
		}
		else
		{
			$itemsList = array(
				'ccplacement' => array(
					"NAME" => Loc::getMessage("CONTACT_CENTER_REST_CC_PLACEMENT"),
					"LOGO_CLASS" => "ui-icon ui-icon-service-rest-contact-center",
					"SELECTED" => false
				),
				'chatbot' => array(
					"NAME" => Loc::getMessage("CONTACT_CENTER_REST_CHATBOT"),
					"LOGO_CLASS" => "ui-icon ui-icon-service-chatbot",
					"SELECTED" => false
				),
				'telephonybot' => array(
					"NAME" => Loc::getMessage("CONTACT_CENTER_REST_TELEPHONYBOT"),
					"LOGO_CLASS" => "ui-icon ui-icon-service-telephonybot",
					"SELECTED" => false
				)
			);

			$dynamicItems = $this->getDynamicItems();

			if (count($dynamicItems) > 0)
			{
				$itemsList = array_merge($itemsList, $dynamicItems);
			}

			$placements = \Bitrix\Rest\PlacementTable::getHandlersList(\CIntranetRestService::CONTACT_CENTER_PLACEMENT);
			$appIdList = array();
			$appList = array();

			foreach ($placements as $placement)
			{
				$appIdList[] = $placement["APP_ID"];
			}
			$appIdList = array_unique($appIdList);
			$parameters = array("filter" => array("ID" => $appIdList));

			if (isset($filter["ACTIVE"]))
			{
				$parameters["filter"]["ACTIVE"] = $filter["ACTIVE"];
			}

			$appsCollection = \Bitrix\Rest\AppTable::getList($parameters);

			while ($app = $appsCollection->Fetch())
			{
				$appList[$app["ID"]] = $app;
			}

			foreach ($placements as $placement)
			{
				$app = $appList[$placement["APP_ID"]];
				$selected = ($app["ACTIVE"] == \Bitrix\Rest\AppTable::ACTIVE);
				$itemsList[$app["CODE"]] = array (
					"NAME" => (strlen($placement["TITLE"]) > 0) ? $placement["TITLE"] : $placement["APP_NAME"],
					"LINK" =>  \CUtil::JSEscape(SITE_DIR . "marketplace/app/" . $app["ID"] . "/"),
					"SELECTED" => $selected,
					"PLACEMENT_ID" => $placement["ID"],
					"APP_ID" => $app["ID"],
					"LOGO_CLASS" => "ui-icon ui-icon-service-common"
				);
			}
		}

		$result->setData($itemsList);

		return $result;
	}

	/**
	 * Return items from sale module
	 *
	 * @param array $filter
	 * @return Result
	 */
	public function saleGetItems($filter = array())
	{
		$result = new Result();

		$result->setData(array(
			'sale' => array(
				"NAME" => Loc::getMessage("CONTACT_CENTER_REST_ESHOP"),
				"LOGO_CLASS" => "ui-icon ui-icon-service-import",
				"SELECTED" => (\Bitrix\Rest\AppTable::getRow(['filter'=>[
					'ACTIVE' => 'Y',
					'CODE' => 'bitrix.eshop']]))
			)
		));

		return $result;
	}

	private function getDynamicItems()
	{
		$items = [];
		$userLang = LANGUAGE_ID ?? 'en';
		$cache = \Bitrix\Main\Data\Cache::createInstance();
		$cache_time = 86400;
		$cache_id = 'ccActionsRest' . $userLang;
		$cache_path = 'restItems';

		if ($cache_time > 0 && $cache->InitCache($cache_time, $cache_id, $cache_path))
		{
			$res = $cache->GetVars();

			if (is_array($res) && (count($res) > 0))
			{
				$items = $res;
			}
		}

		if (count($items) <= 0)
		{
			$marketplace = new \Bitrix\Rest\Marketplace\MarketplaceActions();
			$restItems = $marketplace->getItems('contactcenter', $userLang);

			if (!empty($restItems) && count($restItems) > 0)
			{
				$items = $this->prepareRestItems($restItems);

				if (!is_null($items))
				{
					$cache->StartDataCache($cache_time, $cache_id, $cache_path);
					$cache->EndDataCache($items);
				}
			}

		}

		return $items;
	}


	private function prepareRestItems(array $items) :array
	{
		$result = [];

		foreach ($items as $item)
		{
			if ($item['SLIDER'] == "Y")
			{
				$frame = "<iframe src=\'".$item['HANDLER']."\' style=\'width: 100%;height: -webkit-calc(100vh - 10px);height: calc(100vh - 10px);\'></iframe>";
				$onclick = preg_match("/^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)/i", $item['HANDLER'])
					? "BX.SidePanel.Instance.open('voximplant', {
						contentCallback: function () {return '".$frame."';}})"
					: "BX.SidePanel.Instance.open('/marketplace/?category=".$item['HANDLER']."')";
			}
			else
			{
				$onclick = "window.open ('".$item['HANDLER']."', '_blank')";
			}

			$result[$item['NAME']] = [
				"NAME" => $item['NAME'],
				"LOGO_CLASS" => "ui-icon",
				"SELECTED" => false,
				"ONCLICK" => $onclick,
				"IMAGE" => $item['IMAGE'],
				"COLOR" => $item['COLOR'],
			];
		}

		return $result;
	}

	/**
	 * Return widget button item with widget list
	 *
	 * @param array $filter
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function getButtonListItem($filter = array())
	{
		if ($filter["IS_LOAD_INNER_ITEMS"] !== "N")
		{
			$list = \Bitrix\Crm\SiteButton\Manager::getList();

			if (count($list) > 0)
			{
				$newItem = array(
					"NAME" => Loc::getMessage("CONTACT_CENTER_WIDGET_ADD"),
					"FIXED" => true,
					"ID" => 0
				);
				array_unshift($list, $newItem);

				foreach ($list as &$listItem)
				{
					$listItem["NAME"] = htmlspecialcharsbx($listItem["NAME"]);
					$listItem["LINK"] = $this->getSiteButtonUrl($listItem["ID"]);
				}
			}

			$selected = count($list) > 0;
		}
		else
		{
			$selected = \Bitrix\Crm\SiteButton\Manager::isInUse();
		}

		$result = array(
			"NAME" => Loc::getMessage("CONTACT_CENTER_WIDGET"),
			"SELECTED" => $selected,
			"LOGO_CLASS" => "ui-icon ui-icon-service-livechat"
		);

		if (!empty($list))
		{
			$result["LIST"] = $list;
		}

		if (!$selected)
		{
			$result["LINK"] = $this->getSiteButtonUrl(0);
		}

		return $result;
	}

	/**
	 * Return form button item with form list
	 *
	 * @param array $filter
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getFormListItem($filter = array())
	{
		if ($filter["IS_LOAD_INNER_ITEMS"] !== "N")
		{
			$formParams = array("order" => array("ID" => "DESC"));
			$formCollection = \Bitrix\Crm\WebForm\Internals\FormTable::getList($formParams);
			$list = array();

			while ($form = $formCollection->fetch())
			{
				$list[] = $form;
			}

			if (count($list) > 0)
			{
				$newItem = array(
					"NAME" => Loc::getMessage("CONTACT_CENTER_FORM_ADD"),
					"FIXED" => true,
					"ID" => 0
				);
				array_unshift($list, $newItem);

				foreach ($list as &$listItem)
				{
					$listItem["NAME"] = htmlspecialcharsbx($listItem["NAME"]);
					$listItem["LINK"] = $this->getFormUrl($listItem["ID"]);
				}
			}

			$selected = count($list) > 0;
		}
		else
		{
			$selected = \Bitrix\Crm\WebForm\Manager::isInUse();
		}

		$result = array(
			"NAME" => Loc::getMessage("CONTACT_CENTER_FORM"),
			"SELECTED" => $selected,
			"LOGO_CLASS" => "ui-icon ui-icon-service-webform"
		);

		if (!empty($list))
		{
			$result["LIST"] = $list;
		}

		if (!$selected)
		{
			$result["LINK"] = $this->getFormUrl(0);
		}

		return $result;
	}

	/**
	 * Return callback-form button item with callback-form list
	 *
	 * @param array $filter
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getCallFormListItem($filter = array())
	{
		$options = array("IS_CALLBACK_FORM" => "Y");
		if ($filter["IS_LOAD_INNER_ITEMS"] !== "N")
		{
			$listCall = array();
			$callbackFormParams = array("order" => array("ID" => "DESC"), "filter" => $options);
			$callbackFormCollection = \Bitrix\Crm\WebForm\Internals\FormTable::getList($callbackFormParams);

			while ($form = $callbackFormCollection->fetch())
			{
				$listCall[] = $form;
			}

			if (count($listCall) > 0)
			{
				$newItem = array(
					"NAME" => Loc::getMessage("CONTACT_CENTER_FORM_ADD"),
					"FIXED" => true,
					"ID" => 0
				);
				array_unshift($listCall, $newItem);

				foreach ($listCall as &$listItem)
				{
					$listItem["NAME"] = htmlspecialcharsbx($listItem["NAME"]);
					$listItem["LINK"] = $this->getFormUrl($listItem["ID"], $options);
				}
			}

			$selected = count($listCall) > 0;
		}
		else
		{
			$selected = \Bitrix\Crm\WebForm\Manager::isInUse("Y");
		}

		$result = array(
			"NAME" => Loc::getMessage("CONTACT_CENTER_CALL"),
			"SELECTED" => $selected,
			"LOGO_CLASS" => "ui-icon ui-icon-service-callback"
		);

		if (!empty($listCall))
		{
			$result["LIST"] = $listCall;
		}

		if (!$selected)
		{
			$result["LINK"] = $this->getFormUrl(0, $options);
		}

		return $result;
	}

	/**
	 * Return calltracking button item.
	 *
	 * @return array
	 */
	private function getCallTrackingFormListItem()
	{
		return [
			"NAME" => Loc::getMessage("CONTACT_CENTER_CALLTRACKING"),
			"SELECTED" => \Bitrix\Crm\Tracking\Manager::isCallTrackingConfigured(),
			"LINK" => \Bitrix\Crm\Tracking\Manager::getCallTrackingConfigUrl(),
			"LOGO_CLASS" => "ui-icon ui-icon-service-calltracking",
			"SIDEPANEL_WIDTH" => 735
		];
	}

	/**
	 * Return ads-form buttons items with form list
	 *
	 * @param array $filter
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getAdsFormListItems($filter = array())
	{
		$formParams = array("order" => array("ID" => "DESC"), "select" => array("ID", "NAME"));
		$formCollection = \Bitrix\Crm\WebForm\Internals\FormTable::getList($formParams);
		$itemsList = array();
		$list = array();

		while ($form = $formCollection->fetch())
		{
			$list[$form["ID"]] = $form;
		}

		if (!empty($list))
		{
			$serviceTypes = \Bitrix\Crm\Ads\AdsForm::getServiceTypes();
			$codeMap = \Bitrix\Crm\Ads\AdsForm::getAdsIconMap();
			$cisOnlyItems = array(\Bitrix\Seo\LeadAds\Service::TYPE_VKONTAKTE);
			$cisCheck = $this->cisCheck() && $filter["CHECK_REGION"] !== "N";

			foreach ($serviceTypes as $type)
			{
				if ($cisCheck && in_array($type, $cisOnlyItems))
				{
					continue;
				}

				$linkedFormsIds = \Bitrix\Crm\Ads\AdsForm::getLinkedForms($type);
				$name = (Loc::getMessage("CONTACT_CENTER_ADS_FORM_" . strtoupper($type)) ? : \Bitrix\Crm\Ads\AdsForm::getServiceTypeName($type));

				if ($filter["IS_LOAD_INNER_ITEMS"] !== "N")
				{
					$linkedItems = array();
					$shortName = (Loc::getMessage("CONTACT_CENTER_ADS_FORM_SHORTNAME_" . strtoupper($type)) ? : \Bitrix\Crm\Ads\AdsForm::getServiceTypeName($type));
					$notLinkedItems = $list;

					foreach ($linkedFormsIds as $id)
					{
						$item = $notLinkedItems[$id];
						$item["NAME"] = htmlspecialcharsbx($item["NAME"]);
						$item["LIST"] = array(
							0 => array(
								"LINK" => $this->getFormUrl($item["ID"]),
								"NAME" => Loc::getMessage("CONTACT_CENTER_ADS_FORM_SETTINGS_FORM")
							),
							1 => array(
								"LINK" => $this->getAdsUrl($item["ID"], $type),
								"NAME" => Loc::getMessage("CONTACT_CENTER_ADS_FORM_SETTINGS_LINK", array("#NAME#" => $shortName))
							)
						);
						$linkedItems[] = $item;
						unset($notLinkedItems[$id]);
					}

					foreach ($notLinkedItems as &$item)
					{
						$item["NAME"] = htmlspecialcharsbx($item["NAME"]);
						$item["LINK"] = $this->getAdsUrl($item["ID"], $type);
					}
					unset($item);

					$notLinkedItems = array_values($notLinkedItems);
					$selected = !empty($linkedItems);
					$newItem = array(
						"ID" => 0,
						"NAME" => Loc::getMessage("CONTACT_CENTER_FORM_CREATE"),
						"LINK" => $this->getFormUrl(0),
						"FIXED" => true,
					);

					if ($selected)
					{
						$items = $linkedItems;
						if (!empty($notLinkedItems))
						{
							array_unshift($notLinkedItems, $newItem);
							$items[] = array(
								"ID" => 0,
								"DELIMITER_BEFORE" => true,
								"NAME" => Loc::getMessage("CONTACT_CENTER_FORM_LINK", array("#NAME#" => $shortName)),
								"LIST" => $notLinkedItems
							);
						}
					}
					else
					{
						array_unshift($notLinkedItems, $newItem);
						$items = $notLinkedItems;
					}
				}
				else
				{
					$selected = count($linkedFormsIds) > 0;
				}

				$itemsList[$type . "ads"] = array(
					"NAME" => $name,
					"SELECTED" => $selected,
					"LOGO_CLASS" => "ui-icon ui-icon-service-" . $codeMap[$type]
				);

				if (!empty($items))
				{
					$itemsList[$type . "ads"]["LIST"] = $items;
				}
			}
		}

		return $itemsList;
	}

	/**
	 * Return formatted form item url with params
	 *
	 * @param $formId
	 * @param array $options
	 *
	 * @return mixed
	 */
	private function getFormUrl($formId, $options = array())
	{
		$link = $this->getFormUrlTemplate($formId);
		$options["ACTIVE"] = $formId === 0 ? "Y" : "N";
		$uri = new Uri($link);
		$uri->addParams($options);
		$result = \CUtil::JSEscape($uri->getUri());
		unset($uri);

		return $result;
	}

	/**
	 * @param int $formId
	 *
	 * @return string
	 */
	private function getFormUrlTemplate($formId = 0)
	{
		return \Bitrix\Crm\WebForm\Manager::getEditUrl($formId);
	}

	/**
	 * Return formatted sitebutton item url with params
	 *
	 * @param $buttonId
	 * @param array $options
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function getSiteButtonUrl($buttonId, $options = array())
	{
		$buttonLinkTemplate = $this->getSiteButtonUrlTemplate();
		$link = str_replace("#id#", $buttonId, $buttonLinkTemplate);
		$options["ACTIVE"] = $buttonId === 0 ? "Y" : "N";
		$uri = new Uri($link);
		$uri->addParams($options);
		$result = \CUtil::JSEscape($uri->getUri());
		unset($uri);

		return $result;
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function getSiteButtonUrlTemplate()
	{
		return Option::get("crm", "path_to_button_edit", "/crm/button/edit/#id#/");
	}

	/**
	 * Return formatted adsform item url with params
	 *
	 * @param $formId
	 * @param $adsType
	 * @param array $options
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function getAdsUrl($formId, $adsType, $options = array())
	{
		$adsLinkTemplate = $this->getAdsUrlTemplate();
		$link = \CComponentEngine::makePathFromTemplate(
			$adsLinkTemplate,
			array(
				"ads_type" => $adsType,
				"id" => $formId
			)
		);
		$uri = new Uri($link);
		$uri->addParams($options);
		$result = \CUtil::JSEscape($uri->getUri());
		unset($uri);

		return $result;
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function getAdsUrlTemplate()
	{
		return  Option::get("crm", "path_to_ads", "/crm/webform/ads/#id#/?type=#ads_type#");
	}

	/**
	 * Finds intersect between incoming modules list and supported modules
	 *
	 * @param $modules
	 *
	 * @return array
	 */
	private function modulesIntersect($modules)
	{
		$result = array();

		foreach ($modules as $module)
		{
			if (in_array(strtolower($module), $this->modules))
			{
				$result[] = strtolower($module);
			}
		}

		return $result;
	}

	/**
	 * Check selected param value to filter items
	 *
	 * @param string $filterActive
	 * @param bool $itemSelected
	 *
	 * @return bool
	 */
	private function isAddItemToList($filterActive, $itemSelected)
	{
		if ($filterActive === "Y")
		{
			$isAddItemToList = $itemSelected;
		}
		elseif ($filterActive === "N")
		{
			$isAddItemToList = !$itemSelected;
		}
		else
		{
			$isAddItemToList = true;
		}

		return $isAddItemToList;
	}

	/**
	 * Make cis-region check for bx24 only. For not bx24 always return false
	 *
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function cisCheck()
	{
		if (is_null($this->cisCheck))
		{
			$this->cisCheck = false;
			$cisDomainList = array('ru', 'kz', 'by'); //except ua domain case services rules

			$this->cisCheck = !in_array(
				Loader::includeModule('bitrix24') ? \CBitrix24::getPortalZone() : \CIntranetUtils::getPortalZone(),
				$cisDomainList
			);
		}

		return $this->cisCheck;
	}

	/**
	 * Load additional styles for all modules
	 *
	 * @return string
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getAdditionalStyles()
	{
		$style = "";

		if (Loader::includeModule("imconnector"))
		{
			$style .= \Bitrix\ImConnector\CustomConnectors::getStyleCss();
		}

		return $style;
	}
}