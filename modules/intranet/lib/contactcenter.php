<?php
namespace Bitrix\Intranet;

use Bitrix\Crm\WebForm;
use Bitrix\ImOpenLines\Common;
use Bitrix\ImOpenlines\Security\Helper;
use Bitrix\ImOpenlines\Security\Permissions;
use Bitrix\ImConnector;
use Bitrix\Intranet\Binding\Marketplace;
use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Notifications\FeatureStatus;
use Bitrix\Notifications\Limit;
use Bitrix\Notifications\Settings;
use Bitrix\Voximplant\Limits;

Loc::loadMessages(__FILE__);

class ContactCenter
{
	public const CC_MODULE_NOT_LOADED = 1;

	protected const LANDING_CRM_SHOP_CODE = 'store_v3';

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
	public function __construct()
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
				$result = call_user_func_array(array($this, $methodName), array_values($filter));
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

			$isAddItemToList = $this->isAddItemToList($filter["ACTIVE"] ?? null, $selected);

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
	public function voximplantGetItems($filter = [])
	{
		$result = new Result();
		$module = "voximplant";
		$itemsList = [];

		if (!Loader::includeModule($module))
		{
			$result->addError(new Error(Loc::getMessage("CONTACT_CENTER_ERROR_MODULE_NOT_LOADED", array("#MODULE_ID" => $module)), self::CC_MODULE_NOT_LOADED));
		}
		else
		{
			$canCall = true;
			if (!isset($filter["CHECK_REGION"]) || $filter["CHECK_REGION"] !== "N")
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
				$isAddItemToList = $this->isAddItemToList($filter["ACTIVE"] ?? null, $selected);

				if ($isAddItemToList)
				{
					$itemsList["voximplant"] = [
						"NAME" => Loc::getMessage("CONTACT_CENTER_TELEPHONY"),
						"LINK" => \CUtil::JSEscape(SITE_DIR . "telephony/index.php"),
						"SELECTED" => $selected,
						"LOGO_CLASS" => "ui-icon ui-icon-service-call"
					];

					$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
					if(Limits::canRentMultiple() && $permissions->canModifyLines())
					{
						Extension::load(["voximplant.numberrent"]);
						$canManageTelephony = (
							!method_exists(\Bitrix\Voximplant\Limits::class,"canManageTelephony")
							|| Limits::canManageTelephony()
						);
						$itemsList["voximplant_rent5"] = [
							"NAME" => Loc::getMessage("CONTACT_CENTER_RENT_5_NUMBERS"),
							"ONCLICK" => $canManageTelephony ?
								"BX.Voximplant.NumberRent.create({packetSize: 5}).show();"
								: "BX.Voximplant.openLimitSlider('limit_contact_center_telephony_number_rent');",
							"SELECTED" => \CVoxImplantPhone::hasRentedNumberPacket(5),
							"LOGO_CLASS" => "ui-icon ui-icon-package-numbers-five"

						];
						$itemsList["voximplant_rent10"] = [
							"NAME" => Loc::getMessage("CONTACT_CENTER_RENT_10_NUMBERS"),
							"ONCLICK" => $canManageTelephony ?
								"BX.Voximplant.NumberRent.create({packetSize: 10}).show();"
								: "BX.Voximplant.openLimitSlider('limit_contact_center_telephony_number_rent');",
							"SELECTED" => \CVoxImplantPhone::hasRentedNumberPacket(10),
							"LOGO_CLASS" => "ui-icon ui-icon-package-numbers-ten"
						];
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
			return $result->addError(new Error(Loc::getMessage("CONTACT_CENTER_ERROR_MODULE_NOT_LOADED", array("#MODULE_ID" => $module)), self::CC_MODULE_NOT_LOADED));
		}

		if (\Bitrix\Crm\Tracking\Manager::isAccessible())
		{
			$itemsList["calltracking"] = $this->getCallTrackingFormListItem();
		}

		$itemsList = array_merge($itemsList, $this->getCrmShopListItems());

		$itemsList["widget"] = $this->getButtonListItem($filter);
		$itemsList["form"] = $this->getFormListItem($filter);

		if (!empty(WebForm\Callback::getPhoneNumbers()))
		{
			$itemsList["call"] = $this->getCallFormListItem($filter);
		}
		if (WebForm\WhatsApp::canUse())
		{
			$itemsList["formWhatsapp"] = $this->getWhatsAppFormListItem($filter);
		}

		if (\Bitrix\Crm\Ads\AdsForm::canUse())
		{
			foreach ($this->getAdsFormListItems($filter) as $key => $item)
			{
				$itemsList[$key] = $item;
			}
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

		$result->setData($itemsList);

		return $result;
	}

	/**
	 * Return items from imopenlines(imconnector) module
	 *
	 * @param array $filter
	 *
	 * @return Result
	 */
	public function imopenlinesGetItems($filter = []): Result
	{
		$result = new Result();
		$module = 'imopenlines';
		$itemsList = array();

		if (!Loader::includeModule($module))
		{
			$result->addError(new Error(Loc::getMessage('CONTACT_CENTER_ERROR_MODULE_NOT_LOADED', ['#MODULE_ID' => $module]), self::CC_MODULE_NOT_LOADED));
		}
		elseif (!Loader::includeModule('imconnector'))
		{
			$result->addError(new Error(Loc::getMessage('CONTACT_CENTER_ERROR_MODULE_NOT_LOADED', ['#MODULE_ID' => 'imconnector']), self::CC_MODULE_NOT_LOADED));
		}
		else
		{
			//For whole list of botframework instances use getListConnector()
			$connectors = ImConnector\Connector::getListConnectorMenu(true);
			$statusList = ImConnector\Status::getInstanceAll();
			$linkTemplate = Common::getContactCenterPublicFolder() . "connector/";
			$codeMap = ImConnector\Connector::getIconClassMap();
			//TODO: Delete after exiting 92ee8cf838a0
			$cisOnlyConnectors = ['vkgroup', 'vkgrouporder', 'yandex'];
			$cisCheck = $this->cisCheck() && $filter["CHECK_REGION"] !== "N";
			//TODO: End
			$configList = $this->getImopenlinesConfigList();

			foreach ($connectors as $code => $connector)
			{
				//TODO: Delete after exiting 92ee8cf838a0
				if ($cisCheck && in_array($code, $cisOnlyConnectors))
				{
					continue;
				}
				//TODO: End

				$selected = false;
				$selectedOrder = false;
				$connectionInfoHelperLimit = false;

				if (!empty($statusList[$code]))
				{
					foreach ($statusList[$code] as $lineId => $status)
					{
						if (($status instanceof ImConnector\Status))
						{
							if ($status->isStatus())
							{
								$selected = true;
								$connector['link'] = \CUtil::JSEscape( $linkTemplate . '?ID=' . $code . '&LINE=' . $lineId);

								if ($code !== 'vkgroup')
								{
									break;
								}
							}

							if (
								$code === 'vkgroup'
								&& !empty($status->getData())
							)
							{
								$data = $status->getData();
								if ($data["get_order_messages"] === 'Y')
								{
									$selectedOrder = true;
								}
							}
						}
					}
				}

				if(
					$selected === false
					&& ImConnector\Limit::canUseConnector($code) !== true)
				{
					$connectionInfoHelperLimit = ImConnector\Limit::getIdInfoHelperConnector($code);
				}

				$isAddItemToList = $this->isAddItemToList($filter["ACTIVE"] ?? null, $selected);

				if ($isAddItemToList)
				{
					$itemsList[$code] = [
						"NAME" => $connector["name"],
						"SELECTED" => $selected,
						"CONNECTION_INFO_HELPER_LIMIT" => $connectionInfoHelperLimit,
						"LOGO_CLASS" => "ui-icon ui-icon-service-" . $codeMap[$code]
					];

					$link = \CUtil::JSEscape( $linkTemplate . "?ID=" . $code);
					if (empty($connector["link"]))
					{
						$itemsList[$code]["LINK"] = $link;
					}
					else
					{
						$itemsList[$code]["LIST"] =  $this->getConnectorListItem($code, $configList, $statusList);
						if (empty($itemsList[$code]["LIST"]))
						{
							$itemsList[$code]["LINK"] = $link;
						}
					}

					if ($code === "vkgroup")
					{
						$isAddItemToList = $this->isAddItemToList($filter["ACTIVE"] ?? null, $selectedOrder);

						//Hack for vkgroup order
						if ($isAddItemToList)
						{
							$uri = new Uri($link);
							$uri->addParams(array("group_orders" => "Y"));
							$itemsList["vkgrouporder"] = array(
								"NAME" => Loc::getMessage("CONTACT_CENTER_IMOPENLINES_VK_ORDER"),
								"LINK" => \CUtil::JSEscape($uri->getUri()),
								"SELECTED" => $selectedOrder,
								"CONNECTION_INFO_HELPER_LIMIT" => false,
								"LOGO_CLASS" => "ui-icon ui-icon-service-" . $codeMap["vkgrouporder"]
							);
						}
					}
					if ($code === \Bitrix\ImConnector\Library::ID_NOTIFICATIONS_CONNECTOR
						&& Loader::includeModule('notifications')
						&& class_exists(Settings::class)
					)
					{
						$crmPaymentStatus = Settings::getScenarioAvailability(Settings::SCENARIO_CRM_PAYMENT);
						if ($crmPaymentStatus === FeatureStatus::UNAVAILABLE)
						{
							unset($itemsList[$code]);
						}
						else
						{
							if (!empty($itemsList[$code]["LIST"]))
							{
								$uri = new Uri($link);
								$uri->addParams(["LINE" => \Bitrix\Notifications\Integration\ImConnector::getLineId()]);
								$link = $uri->getUri();

								unset($itemsList[$code]["LIST"]);
								$itemsList[$code]["LINK"] = \CUtil::JSEscape($link);
							}
							$itemsList[$code]["SELECTED"] = Settings::isScenarioEnabled(Settings::SCENARIO_CRM_PAYMENT);
						}

						$virtualWhatsAppStatus = Settings::getScenarioAvailability(Settings::SCENARIO_VIRTUAL_WHATSAPP);
						if ($virtualWhatsAppStatus !== FeatureStatus::UNAVAILABLE)
						{
							$infoHelperCode = $virtualWhatsAppStatus === FeatureStatus::LIMITED
								? Limit::getInfoHelperCodeForScenario(Settings::SCENARIO_VIRTUAL_WHATSAPP)
								: false
							;
							$uri = new Uri($link);
							$uri->addParams(["scenario" => Settings::SCENARIO_VIRTUAL_WHATSAPP]);
							$itemsList["virtual_whatsapp"] = [
								"NAME" => Loc::getMessage("CONTACT_CENTER_IMOPENLINES_NOTIFICATION_VIRTUAL_WA"),
								"LINK" => \CUtil::JSEscape($uri->getUri()),
								"SELECTED" => Settings::isScenarioEnabled(Settings::SCENARIO_VIRTUAL_WHATSAPP),
								"CONNECTION_INFO_HELPER_LIMIT" => $infoHelperCode,
								"LOGO_CLASS" => "ui-icon ui-icon-service-" . $codeMap["notifications_virtual_wa"]
							];
						}

						if (Settings::getScenarioAvailability(Settings::SCENARIO_REVERSE_WHATSAPP) !== FeatureStatus::UNAVAILABLE)
						{
							$uri = new Uri($link);
							$uri->addParams(["scenario" => Settings::SCENARIO_REVERSE_WHATSAPP]);
							$itemsList["reverse_whatsapp"] = [
								"NAME" => Loc::getMessage("CONTACT_CENTER_IMOPENLINES_NOTIFICATION_REVERSE_WA"),
								"LINK" => \CUtil::JSEscape($uri->getUri()),
								"SELECTED" => Settings::isScenarioEnabled(Settings::SCENARIO_REVERSE_WHATSAPP),
								"CONNECTION_INFO_HELPER_LIMIT" => false,
								"LOGO_CLASS" => "ui-icon ui-icon-service-" . $codeMap["notifications_reverse_wa"]
							];
						}
					}
				}
			}
		}
		$this->getKufarItem($itemsList);

		$result->setData($itemsList);

		return $result;
	}

	private function getImopenlinesConfigList(): array
	{
		if (!Loader::includeModule("imopenlines"))
		{
			return [];
		}
		$userPermissions = Permissions::createWithCurrentUser();

		$allowedUserIds = Helper::getAllowedUserIds(
			Helper::getCurrentUserId(),
			$userPermissions->getPermission(Permissions::ENTITY_CONNECTORS, Permissions::ACTION_MODIFY)
		);

		$limit = null;
		if (is_array($allowedUserIds))
		{
			$limit = array();
			$orm = \Bitrix\ImOpenlines\Model\QueueTable::getList(Array(
				'filter' => Array(
					'=USER_ID' => $allowedUserIds
				)
			));
			while ($row = $orm->fetch())
			{
				$limit[$row['CONFIG_ID']] = $row['CONFIG_ID'];
			}
		}

		$configManager = new \Bitrix\ImOpenLines\Config();
		$result = $configManager->getList([
			'select' => [
				'ID',
				'NAME' => 'LINE_NAME',
				'IS_ACTIVE' => 'ACTIVE',
				'MODIFY_USER_ID'
			],
			'filter' => ['=TEMPORARY' => 'N'],
			'order' => ['LINE_NAME']
		]);
		foreach ($result as $id => $config)
		{
			if (!is_null($limit))
			{
				if (!isset($limit[$config['ID']]) && !in_array($config['MODIFY_USER_ID'], $allowedUserIds, true))
				{
					unset($result[$id]);
					continue;
				}
			}

			$result[$id] = $config;
		}

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
			$itemsList = [];

			$marketplaceApps = $this->getMarketplaceAppsByTag(['contact_center', 'partners', static::getZone()]);
			if (!empty($marketplaceApps['ITEMS']))
			{
				$itemsList = $this->prepareMarketplaceApps($marketplaceApps);
			}

			$itemsList = array_merge($itemsList, array(
				'ccplacement' => array(
					"NAME" => Loc::getMessage("CONTACT_CENTER_REST_CC_PLACEMENT_2"),
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
			));

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
					"NAME" => ($placement["TITLE"] <> '') ? $placement["TITLE"] : $placement["APP_NAME"],
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
	 * Return true if portal is cloud.
	 *
	 * @return bool
	 */
	public static function isCloud()
	{
		return Loader::includeModule('bitrix24');
	}

	/**
	 * Return true if region is Russian.
	 *
	 * @return bool
	 */
	public static function isRegionRussian()
	{
		return in_array(self::getZone(), ['ru', 'kz', 'by']);
	}

	private static function getZone()
	{
		if (self::isCloud())
		{
			return \CBitrix24::getPortalZone();
		}

		return \CIntranetUtils::getPortalZone();
	}

	/**
	 * Return items from sale module
	 *
	 * @param array $filter
	 * @return Result
	 */
	public function saleGetItems($filter = [])
	{
		$data = [];

		if (static::isRegionRussian())
		{
			$data[] = [
				"NAME" => Loc::getMessage("CONTACT_CENTER_REST_ESHOP"),
				"LOGO_CLASS" => "ui-icon ui-icon-service-import",
				"SELECTED" => (\Bitrix\Rest\AppTable::getRow([
					'filter'=> [
						'ACTIVE' => 'Y',
						'CODE' => 'bitrix.eshop',
					],
				])),
				"ONCLICK" => "BX.SidePanel.Instance.open(" . Marketplace::getMainDirectory() . "'detail/bitrix.eshop/?from=contact_center_eshop')",
			];
		}

		$result = new Result();
		$result->setData($data);

		return $result;
	}

	private function getKufarItem(array &$itemList): void
	{
		if (Application::getInstance()->getLicense()->getRegion() !== 'by')
		{
			return;
		}
		$id = '';

		if(array_key_exists('b24kufar', $itemList))
		{
			$id = 'b24kufar';
		}
		elseif(array_key_exists('b24_kufar', $itemList))
		{
			$id = 'b24_kufar';
		}

		if($id === '')
		{
			$itemList['b24kufar'] = [
				'NAME' => 'Kufar',
				'SELECTED' => false,
				'LOGO_CLASS' => "ui-icon ui-icon-service-kufar",
				'LINK' => Marketplace::getMainDirectory() . "detail/integrations24.kufar/"
			];

			return;
		}

		$itemList[$id]['SELECTED'] = true;
		$itemList[$id]['LOGO_CLASS'] = "ui-icon ui-icon-service-light-kufar";

		return;
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
					: "BX.SidePanel.Instance.open('" . Marketplace::getMainDirectory() . "?category=".$item['HANDLER']."')";
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

	private function getMarketplaceAppsByTag(array $tag, bool $page = false, bool $pageSize = false)
	{
		$cacheTtl = 43200;
		$cacheId = md5(serialize([$tag, $page, $pageSize]));
		$cachePath = '/intranet/contact_center/tag/';
		$cache = Application::getInstance()->getCache();
		if($cache->initCache($cacheTtl, $cacheId, $cachePath))
		{
			$marketplaceApps = $cache->getVars();
		}
		else
		{
			$marketplaceApps = \Bitrix\Rest\Marketplace\Client::getByTag($tag, $page, $pageSize);
			if(!empty($marketplaceApps['ITEMS']))
			{
				$cache->startDataCache();
				$cache->endDataCache($marketplaceApps);
			}
		}

		return $marketplaceApps;
	}

	private function prepareMarketplaceApps(array $marketplaceApps): array
	{
		$result = [];

		$installedMarketplaceApps = $this->getInstalledMarketplaceApps();
		foreach ($marketplaceApps['ITEMS'] as $marketplaceApp)
		{
			$onclick = "BX.SidePanel.Instance.open('" . Marketplace::getMainDirectory() . "detail/{$marketplaceApp['CODE']}/')";
			if (isset($installedMarketplaceApps[$marketplaceApp['CODE']]))
			{
				$applicationId = $installedMarketplaceApps[$marketplaceApp['CODE']]['ID'];
				$appCode = $installedMarketplaceApps[$marketplaceApp['CODE']]['CODE'];
				$onclick = "new BX.ContactCenter.MarketplaceApp('{$applicationId}', '{$appCode}')";
			}

			$title = $marketplaceApp['NAME'];
			if (mb_strlen($title) > 50)
			{
				$title = mb_substr($title, 0, 50).'...';
			}

			$img = $marketplaceApp['ICON_PRIORITY'] ?: $marketplaceApp['ICON'];
			$img = str_replace(' ', '%20', $img);

			$result[$marketplaceApp['CODE']] = [
				"NAME" => $title,
				"LOGO_CLASS" => 'ui-icon intranet-contact-marketplace-app',
				"IMAGE" => $img,
				"ONCLICK" => $onclick,
				"SELECTED" => isset($installedMarketplaceApps[$marketplaceApp['CODE']]),
				"MARKETPLACE_APP" => true,
			];
		}

		\Bitrix\Main\Type\Collection::sortByColumn($result, ['SELECTED' => SORT_DESC]);

		return $result;
	}

	private function getInstalledMarketplaceApps(): array
	{
		static $marketplaceInstalledApps = [];
		if(!empty($marketplaceInstalledApps))
		{
			return $marketplaceInstalledApps;
		}

		$appIterator = \Bitrix\Rest\AppTable::getList([
			'select' => ['ID', 'CODE'],
			'filter' => [
				'=ACTIVE' => 'Y',
			]
		]);
		while ($row = $appIterator->fetch())
		{
			$marketplaceInstalledApps[$row['CODE']] = $row;
		}

		return $marketplaceInstalledApps;
	}

	private function getConnectorListItem(string $connectorCode, array $configList, array $statusList): array
	{
		if (!Loader::includeModule("imconnector") || !Loader::includeModule("imopenlines"))
		{
			return [];
		}

		$connectorCode = htmlspecialcharsbx(\CUtil::JSescape($connectorCode));
		$sliderWidth = 700;

		$openLineSliderPath = Common::getContactCenterPublicFolder() . "connector/?ID={$connectorCode}&LINE=#LINE#&action-line=create";
		$infoConnectors = ImConnector\InfoConnectors::getInfoConnectorsList();

		if (count($configList) > 0)
		{
			foreach ($configList as &$configItem)
			{
				//getting status if connector is connected for the open line
				$status = $statusList[$connectorCode][$configItem["ID"]];
				if (!empty($status) && ($status instanceof ImConnector\Status) && $status->isStatus())
				{
					$configItem["STATUS"] = 1;
				}
				else
				{
					$configItem["STATUS"] = 0;
				}

				//getting connected channel name
				$channelInfo = $infoConnectors[$configItem["ID"]];
				try
				{
					$channelData = JSON::decode($channelInfo['DATA']);
					if (isset($channelData[$connectorCode]['name']) && is_string($channelData[$connectorCode]['name']))
					{
						$channelName = trim($channelData[$connectorCode]['name']);
					}
					else
					{
						$channelName = '';
					}
				}
				catch (\Bitrix\Main\ArgumentException $exception)
				{
					$channelName = '';
				}

				$configItem["NAME"] = htmlspecialcharsbx($configItem["NAME"]);
				if (!empty($channelName))
				{
					$channelName = htmlspecialcharsbx($channelName);
					$configItem["NAME"] .= " ({$channelName})";
				}
				elseif ($configItem["STATUS"] === 1)
				{
					$connectedMessage = Loc::getMessage("CONTACT_CENTER_IMOPENLINES_CONNECTED_CONNECTOR");
					$configItem["NAME"] .= " ({$connectedMessage})";
				}

				$itemPath = str_replace('#LINE#', $configItem["ID"], $openLineSliderPath);
				$configItem["ONCLICK"] = "BX.SidePanel.Instance.open('$itemPath', {width: $sliderWidth})";
			}
			unset($configItem);

			//configured open lines are higher than not configured
			usort($configList, static function($first, $second){
				return ($second['STATUS'] - $first['STATUS']);
			});

			//delimiter between configured open lines and not configured
			foreach ($configList as $key => $configItem)
			{
				if ($configItem['STATUS'] === 0)
				{
					$configList[$key]['DELIMITER_BEFORE'] = true;
					break;
				}
			}

			$userPermissions = Permissions::createWithCurrentUser();
			if ($userPermissions->canPerform(Permissions::ENTITY_LINES, Permissions::ACTION_MODIFY))
			{
				$configList[] = [
					'NAME' => Loc::getMessage("CONTACT_CENTER_IMOPENLINES_CREATE_OPEN_LINE"),
					'ID' => 0,
					'DELIMITER_BEFORE' => true,
					'ONCLICK' => "new BX.Imopenlines.CreateLine({path:'{$openLineSliderPath}', sliderWidth:{$sliderWidth}});",
				];
			}
		}

		return $configList;
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
		if (!isset($filter["IS_LOAD_INNER_ITEMS"]) || $filter["IS_LOAD_INNER_ITEMS"] !== "N")
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
		if (!isset($filter["IS_LOAD_INNER_ITEMS"]) || $filter["IS_LOAD_INNER_ITEMS"] !== "N")
		{
			if (method_exists(\Bitrix\Crm\WebForm\Internals\FormTable::class, 'getDefaultTypeList'))
			{
				$formCollection = \Bitrix\Crm\WebForm\Internals\FormTable::getDefaultTypeList([
					"select" => ["ID"]
				]);
			}
			else
			{
				$formCollection = \Bitrix\Crm\WebForm\Internals\FormTable::getList([
					"select" => ["ID"]
				]);
			}
			$selected = $formCollection->getSelectedRowsCount() > 0;
		}
		else
		{
			$selected = \Bitrix\Crm\WebForm\Manager::isInUse();
		}

		return array(
			"NAME" => Loc::getMessage("CONTACT_CENTER_FORM"),
			"SELECTED" => $selected,
			"LOGO_CLASS" => "ui-icon ui-icon-service-webform"
		);
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
		if (!isset($filter["IS_LOAD_INNER_ITEMS"]) || $filter["IS_LOAD_INNER_ITEMS"] !== "N")
		{
			if (method_exists(\Bitrix\Crm\WebForm\Internals\FormTable::class, 'getDefaultTypeList'))
			{
				$callbackFormCollection = \Bitrix\Crm\WebForm\Internals\FormTable::getDefaultTypeList([
					"filter" => ["IS_CALLBACK_FORM" => "Y"]
				]);
			}
			else
			{
				$callbackFormCollection = \Bitrix\Crm\WebForm\Internals\FormTable::getList([
					"filter" => ["IS_CALLBACK_FORM" => "Y"]
				]);
			}

			$selected = $callbackFormCollection->getSelectedRowsCount() > 0;
		}
		else
		{
			$selected = \Bitrix\Crm\WebForm\Manager::isInUse("Y");
		}

		return [
			"NAME" => Loc::getMessage("CONTACT_CENTER_CALL"),
			"SELECTED" => $selected,
			"LOGO_CLASS" => "ui-icon ui-icon-service-callback"
		];
	}

	/**
	 * Return whatsapp-form button item with whatsapp-form list
	 *
	 * @param array $filter
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getWhatsAppFormListItem($filter = array()): array
	{
		if (WebForm\WhatsApp::isSetupCompleted())
		{
			$link = WebForm\WhatsApp::getDefaultFormEditUrl();
			$linkType = 'newWindow';
			$selected = true;
		}
		else
		{
			$link = WebForm\WhatsApp::getSetupLink();
			$linkType = 'slider';
			$selected = false;
		}

		return [
			"NAME" => Loc::getMessage("CONTACT_CENTER_WHATSAPP"),
			"SELECTED" => $selected,
			"FORM_ID" => WebForm\WhatsApp::getDefaultFormId(),
			"LOGO_CLASS" => "ui-icon ui-icon-service-whatsapp",
			"LINK_TYPE" => $linkType,
			"LINK" => $link,
		];
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

		$formsDb = \Bitrix\Crm\WebForm\Internals\FormTable::query()
			->setSelect(["ID"])
			->setCacheTtl(300)
			->exec();

		if ($formsDb->getSelectedRowsCount() === 0)
		{
			return [];
		}

		$codeMap = \Bitrix\Crm\Ads\AdsForm::getAdsIconMap();
		$cisCheck = $this->cisCheck() && (!isset($filter["CHECK_REGION"]) || $filter["CHECK_REGION"] !== "N");
		$isRuPortal = $this->isRuZone() && (!isset($filter["CHECK_REGION"]) || $filter["CHECK_REGION"] !== "N");

		$itemsList = [];
		foreach (\Bitrix\Crm\Ads\AdsForm::getServiceTypes() as $type)
		{
			if ($cisCheck && $type === \Bitrix\Seo\LeadAds\Service::TYPE_VKONTAKTE)
			{
				continue;
			}

			if ($isRuPortal && $type === \Bitrix\Seo\LeadAds\Service::TYPE_FACEBOOK)
			{
				continue;
			}

			$linkedFormsIds = \Bitrix\Crm\Ads\AdsForm::getLinkedForms($type);
			$selected = count($linkedFormsIds) > 0;
			$typeCaption = Loc::getMessage("CONTACT_CENTER_ADS_FORM_".mb_strtoupper($type));

			$itemsList["{$type}ads"] = [
				"NAME" => $typeCaption?: \Bitrix\Crm\Ads\AdsForm::getServiceTypeName($type),
				"SELECTED" => $selected,
				"LOGO_CLASS" => "ui-icon ui-icon-service-{$codeMap[$type]}"
			];
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

	private function getCrmShopSiteInfo(): ?array
	{
		\Bitrix\Landing\Rights::setGlobalOff();
		$site = \Bitrix\Landing\Site::getList([
			'select' => ['ID', 'ACTIVE',],
			'filter' => [
				'=TPL_CODE' => static::LANDING_CRM_SHOP_CODE,
			],
			'order' => [
				'ID' => 'desc'
			],
			'limit' => 1,
		])->fetch();
		\Bitrix\Landing\Rights::setGlobalOn();

		return is_array($site) ? $site : null;
	}

	private function getCrmShopListItems(): array
	{
		$items = [];

		if (!Loader::includeModule('landing'))
		{
			return $items;
		}

		$shopItem = [
			"NAME" => Loc::getMessage("CONTACT_CENTER_CRM_SHOP_ITEM"),
			"LOGO_CLASS" => "ui-icon intranet-contact-center-crm-shop-item intranet-contact-center-crm-shop-item-color",
			"COLOR_CLASS" => "intranet-contact-center-crm-shop-item-color",
			"IS_NEW" => true,
		];
		$site = $this->getCrmShopSiteInfo();
		if ($site !== null)
		{
			$sitePublicUrl = null;
			if ($site['ACTIVE'] !== 'N')
			{
				$sitePublicUrl = \Bitrix\Landing\Site::getPublicUrl($site['ID']);
			}
			$shopItem["SELECTED"] = true;
			$shopItem["LIST"] = [];
			$dealsUrl = \CComponentEngine::makePathFromTemplate('#SITE_DIR#crm/deal/?redirect_to');
			if ($dealsUrl)
			{
				$shopItem["LIST"][] = [
					"ONCLICK" => "window.open('".\CUtil::JSEscape($dealsUrl)."', '_blank');",
					"NAME" => Loc::getMessage('CONTACT_CENTER_CRM_SHOP_ITEM_DEALS'),
				];
			}
			if (Loader::includeModule('salescenter'))
			{
				$paySystemPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem.panel');
				$paySystemPath = getLocalPath('components'.$paySystemPath.'/slider.php');
				$paySystemPath = new \Bitrix\Main\Web\Uri($paySystemPath);
				$paySystemPath->addParams([
					'analyticsLabel' => 'contactCenterClickPaymentTile',
					'type' => 'main',
					'mode' => 'main'
				]);
				$shopItem["LIST"][] = [
					"ONCLICK" => "BX.SidePanel.Instance.open('".\CUtil::JSEscape($paySystemPath->getUri())."');",
					"NAME" => Loc::getMessage('CONTACT_CENTER_CRM_SHOP_ITEM_PAYSYSTEMS'),
				];
			}

			$shopItem['LIST'][] = [
				'NAME' => Loc::getMessage('CONTACT_CENTER_CRM_SHOP_ITEM_GO_TO_SITE'),
				'ONCLICK' => $site['ACTIVE'] === 'Y' ? "window.open('".\CUtil::JSEscape($sitePublicUrl)."', '_blank');" : null,
				'DISABLED' => $site['ACTIVE'] !== 'Y',
			];

			$shopItem["LIST"][] = [
				"NAME" => Loc::getMessage('CONTACT_CENTER_CRM_SHOP_ITEM_HELP'),
				"ONCLICK" => "top.BX.Helper.show('redirect=detail&code=13651476')",
				"DELIMITER_BEFORE" => true,
			];
		}
		else
		{
			$shopItem["SELECTED"] = false;
			$shopItem["SIDEPANEL_PARAMS"] = [
				'allowChangeHistory' => false,
				'width' => 1200,
				'data' => [
					'rightBoundary' => 0,
				],
			];
			$shopItem["LINK"] = '/shop/stores/site/edit/0/?super=Y';
		}

		$items['crm_shop'] = $shopItem;

		return $items;
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
			if (in_array(mb_strtolower($module), $this->modules))
			{
				$result[] = mb_strtolower($module);
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
	 * Checks if the portal is in RU zone.
	 *
	 * @return boolean
	 */
	public function isRuZone(): bool
	{
		$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();
		if (is_null($region) || mb_strtolower($region) === 'ru')
		{
			return true;
		}

		return false;
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
			$style .= ImConnector\CustomConnectors::getStyleCss();
		}

		return $style;
	}
}
