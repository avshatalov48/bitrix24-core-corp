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
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Crm\Order;
use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Numerator\Numerator;
use Bitrix\Sale\Registry;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Catalog;

class CCrmConfigSaleSettings extends \CBitrixComponent implements Controllerable
{
	protected $optionPrefix = "csc_sale_";
	protected $listSiteId = [];

	public function configureActions()
	{
		return array();
	}

	public function onIncludeComponentLang()
	{
		$this->includeComponentLang(basename(__FILE__));
		Loc::loadMessages(__FILE__);
	}

	public function onPrepareComponentParams($params)
	{
		$params["TYPE_SETTINGS"] = (!empty($params["TYPE_SETTINGS"]) ?
			$params["TYPE_SETTINGS"] : (!empty($_GET["type"]) ? $_GET["type"] : ""));

		return $params;
	}

	public function executeComponent()
	{
		try
		{
			$this->checkRequiredParams();

			$this->checkPostRequest();

			$this->formatResult();

			$this->setTitle();

			$nameTemplate = "";
			if ($this->arResult["SETTINGS_ID"] == "common" || $this->arResult["SETTINGS_ID"] == "fields")
			{
				$nameTemplate = $this->arResult["SETTINGS_ID"];
			}

			$this->includeComponentTemplate($nameTemplate );
		}
		catch(SystemException $e)
		{
			ShowError($e->getMessage());
		}
		catch(Bitrix\Main\LoaderException $e)
		{
			ShowError($e->getMessage());
		}
	}

	public function saveCommonSettingsAction()
	{
		$this->checkRequiredParams();

		$request = Context::getCurrent()->getRequest();
		if ($request->isAjaxRequest() && $request->get("common_sale_settings") === "Y")
		{
			$post = $this->request->getPostList()->toArray();

			$siteData = array();
			$siteIterator = Bitrix\Main\SiteTable::getList(
				array("select" => array("LID"), "order" => array("SORT" => "ASC")));
			while ($site = $siteIterator->fetch())
			{
				$siteData[$site["LID"]] = $site;
			}

			$options = $this->getCommonSettingsOptions();
			foreach ($options as $moduleId => $fields)
			{
				foreach ($fields as $field)
				{
					$fieldName = $this->optionPrefix.$field;
					$optionName = $field;

					if (
						!isset($post[$fieldName])
						|| (
							is_array($post[$fieldName])
							&& empty($post[$fieldName])
						)
					)
					{
						continue;
					}

					switch ($optionName)
					{
						case "SHOP_SITE":
							if (is_array($post[$fieldName]))
							{
								foreach ($siteData as $site)
								{
									COption::RemoveOption($moduleId, "SHOP_SITE_".$site["LID"]);
								}
								foreach ($post[$fieldName] as $shopSiteValue)
								{
									COption::SetOptionString($moduleId, "SHOP_SITE_".$shopSiteValue, $shopSiteValue);
								}
							}
							break;
						case "subscribe_prod":
							$subscribeProdList = array();
							$subscribeProd = COption::GetOptionString("sale", "subscribe_prod", "");
							if ($subscribeProd <> '')
							{
								$subscribeProdList = unserialize($subscribeProd, ['allowed_classes' => false]);
							}
							foreach ($subscribeProdList as $siteLid => $subscribeProdValue)
							{
								if (in_array($siteLid, $post[$fieldName]))
								{
									$subscribeProdList[$siteLid]["use"] = "Y";
								}
								else
								{
									$subscribeProdList[$siteLid]["use"] = "N";
								}
							}
							if ($subscribeProdList)
							{
								COption::SetOptionString("sale", "subscribe_prod", serialize($subscribeProdList));
							}
							break;
						case "WEIGHT_different_set":
							COption::RemoveOption($moduleId, "weight_unit");
							COption::RemoveOption($moduleId, "weight_koef");
							if ($post[$fieldName] == "Y")
							{
								foreach ($siteData as $site)
								{
									COption::SetOptionString($moduleId, "weight_unit", trim(
										$post["weight_unit"][$site["LID"]]), false, $site["LID"]);
									COption::SetOptionString($moduleId, "weight_koef", floatval(
										$post["weight_koef"][$site["LID"]]), false, $site["LID"]);
								}
								COption::SetOptionString($moduleId, "WEIGHT_different_set", "Y");
							}
							else
							{
								$currentSiteId = $post["WEIGHT_site_id"];
								COption::SetOptionString($moduleId, "weight_unit", trim(
									$post["weight_unit"][$currentSiteId]));
								COption::SetOptionString($moduleId, "weight_koef", floatval(
									$post["weight_koef"][$currentSiteId]));
								COption::SetOptionString($moduleId, "WEIGHT_different_set", "N");
							}
							break;
						case "ADDRESS_different_set":
							COption::RemoveOption($moduleId, "location_zip");
							COption::RemoveOption($moduleId, "location");
							if ($post[$fieldName] == "Y")
							{
								foreach ($siteData as $site)
								{
									COption::SetOptionString($moduleId, "location_zip",
										$post["location_zip"][$site["LID"]], false, $site["LID"]);
									COption::SetOptionString($moduleId, "location",
										$post["location"][$site["LID"]], false, $site["LID"]);
								}
								COption::SetOptionString($moduleId, "ADDRESS_different_set", "Y");
							}
							else
							{
								$currentSiteId = $post["ADDRESS_current_site"];
								COption::SetOptionString($moduleId, "location_zip", $post["location_zip"][$currentSiteId]);
								COption::SetOptionString($moduleId, "location", $post["location"][$currentSiteId]);
								COption::SetOptionString($moduleId, "ADDRESS_different_set", "N");
							}
							break;
						case "hideNumeratorSettings":
							if (Loader::includeModule("sale"))
							{
								$numeratorsOrderType = Numerator::getOneByType(Registry::REGISTRY_TYPE_ORDER);
								$numeratorForOrdersId = ($numeratorsOrderType ? $numeratorsOrderType["id"] : "");
								if ($post[$fieldName] == "Y")
								{
									$result = (new \Bitrix\Main\Numerator\Service\NumeratorRequestManager($request))
											->saveFromRequest();
								}
								else
								{
									Numerator::delete($numeratorForOrdersId);
								}
							}
							break;

						case "tracking_check_switch":

							$tSwitch = $post[$fieldName] == 'Y' ? 'Y' : 'N';
							Option::set('sale', 'tracking_check_switch', $tSwitch);

							if($tSwitch == 'Y')
							{
								$CHECK_PERIOD = 6;
								Option::set('sale', 'tracking_check_period', $CHECK_PERIOD);

								$agentName = '\Bitrix\Sale\Delivery\Tracking\Manager::startRefreshingStatuses();';
								$res = \CAgent::GetList(array(), array('NAME' => $agentName));

								if($agent = $res->Fetch())
								{
									\CAgent::Update($agent['ID'], array('AGENT_INTERVAL' => $CHECK_PERIOD*60*60));
								}
								else
								{
									\CAgent::AddAgent(
										$agentName,
										'sale',
										"Y",
										$CHECK_PERIOD*60*60,
										"",
										"Y"
									);
								}
							}
							else
							{
								\CAgent::RemoveAgent(
									$agentName,
									'sale'
								);
							}

							break;
						default:
							if (is_string($post[$fieldName]))
							{
								COption::SetOptionString($moduleId, $field, $post[$fieldName]);
							}
					}
				}
			}
		}
	}

	public function setListSiteId(array $listSiteId)
	{
		$this->listSiteId = $listSiteId;
	}

	public function getListSiteId()
	{
		return $this->listSiteId;
	}

	/**
	 * @throws SystemException
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function checkRequiredParams()
	{
		$this->checkModules();
	}

	protected function checkPostRequest()
	{
		$this->checkRequiredParams();

		$request = Context::getCurrent()->getRequest();
		if ($request->isPost() && check_bitrix_sessid() && $this->arParams['TYPE_SETTINGS'] !== 'fields')
		{
			global $APPLICATION;
			if ($request->get("ACTION") == "save")
			{
				$postList = $request->getPostList()->toArray();

				$arAdd = array();
				$arUpdate = array();

				foreach ($postList["LIST"] as $entityId => $fields)
				{
					$iPrevSort = 0;

					$error = "";
					if (array_key_exists("REMOVE", $fields) && is_array($fields["REMOVE"]))
					{
						foreach($fields["REMOVE"] as $fieldId => $field)
						{
							$arCurrentData = $this->GetStatusById($entityId, $fieldId);
							if ($arCurrentData["SYSTEM"] == "N")
							{
								$result = \Bitrix\Sale\Internals\StatusTable::delete($arCurrentData['STATUS_ID']);
								if (!$result->isSuccess())
								{
									$error .= $result->getErrors()[0];
								}

								$primaryLangKey = array(
									'STATUS_ID' => $arCurrentData['STATUS_ID'],
									'LID' => static::getLanguageId()
								);

								\Bitrix\Sale\Internals\StatusLangTable::delete($primaryLangKey);
							}
						}

						unset($fields["REMOVE"]);
					}

					if (!empty($error))
					{
						$urlParams = "&ERROR=".$error;
						if ($_REQUEST["IFRAME"] == "Y")
						{
							$urlParams .= "&sidePanelAction=destroy";
						}
						LocalRedirect($APPLICATION->GetCurPageParam().$urlParams);
					}

					$colorSettings = array();
					foreach($fields as $id => $field)
					{
						$field["SORT"] = (int)$field["SORT"];
						if ($field["SORT"] <= $iPrevSort)
						{
							$field["SORT"] = $iPrevSort + 10;
						}
						$iPrevSort = $field["SORT"];

						if (mb_substr($id, 0, 1) == "n")
						{
							if (trim($field["VALUE"]) == "")
							{
								continue;
							}

							$arAdd["NAME"] = trim($field["VALUE"]);
							$arAdd["SORT"] = $field["SORT"];

							if ($entityId === Order\OrderStatus::NAME)
							{
								$statusID = static::getNewOrderStatusId();
							}
							else
							{
								$statusID = static::getNewDeliveryStatusId();
							}

							if ($entityId === Order\OrderStatus::NAME)
							{
								$type = Order\OrderStatus::TYPE;
							}
							else
							{
								$type = Order\DeliveryStatus::TYPE;
							}

							$newFields = array(
								'ID' => $statusID,
								'TYPE' => $type
							);

							if (isset($field['COLOR']))
							{
								$newFields['COLOR'] = $field['COLOR'];
							}

							if ((int)$arAdd['SORT'] > 0)
							{
								$newFields['SORT'] = $arAdd['SORT'];
							}

							$result = \Bitrix\Sale\Internals\StatusTable::add($newFields);

							\Bitrix\Sale\Internals\StatusLangTable::add([
								'STATUS_ID' => $result->getId(),
								'NAME' => $arAdd['NAME'],
								'LID' => static::getLanguageId()
							]);
						}
						else
						{
							$arCurrentData = $this->GetStatusById($entityId, $id);
							if (trim($field["VALUE"]) != $arCurrentData["NAME"]
								|| intval($field["SORT"]) != $arCurrentData["SORT"]
								|| trim($field["COLOR"]) != $arCurrentData["COLOR"]
							)
							{
								$arUpdate["NAME"] = trim($field["VALUE"]);
								$arUpdate["SORT"] = $field["SORT"];

								\Bitrix\Sale\Internals\StatusTable::update($arCurrentData['STATUS_ID'], [
									'SORT' => (int)$arUpdate['SORT'],
									'COLOR' => $field["COLOR"],
								]);

								if (isset($arUpdate['NAME']))
								{
									$primaryLangKey = array(
										'STATUS_ID' => $arCurrentData['STATUS_ID'],
										'LID' => static::getLanguageId()
									);

									if (\Bitrix\Sale\Internals\StatusLangTable::getByPrimary($primaryLangKey)->fetch())
									{
										\Bitrix\Sale\Internals\StatusLangTable::update($primaryLangKey, ['NAME' => $arUpdate['NAME']]);
									}
									else
									{
										\Bitrix\Sale\Internals\StatusLangTable::add([
											'STATUS_ID' => $arCurrentData['STATUS_ID'],
											'NAME' => $arUpdate['NAME'],
											'LID' => static::getLanguageId()
										]);
									}
								}
							}
						}

						if (isset($field["COLOR"]) && $field["COLOR"])
						{
							$colorSettings[$field["STATUS_ID"]]["COLOR"] = $field["COLOR"];
						}
					}

					if (!empty($colorSettings))
					{
						COption::SetOptionString("crm", "CONFIG_STATUS_".$entityId, serialize($colorSettings));
					}
				}

				$urlParams = "";
				if ($_REQUEST["IFRAME"] == "Y")
				{
					$urlParams .= "&success=Y&sidePanelAction=destroy";
				}
				LocalRedirect($APPLICATION->GetCurPageParam().$urlParams);
			}
			else
			{
				if ($_REQUEST["IFRAME"] == "Y")
				{
					LocalRedirect($APPLICATION->GetCurPageParam()."&sidePanelAction=destroy");
				}
			}
		}
	}

	/**
	 * @param $string
	 * @return int
	 */
	private static function ord($string)
	{
		$ord = "";
		$len = mb_strlen($string);
		if ($len <= 0)
		{
			return 0;
		}

		for ($i = 0; $i < $len; $i++)
		{
			$ord .= ord($string[$i]);
		}

		return (int)$ord;
	}

	public function getStatusById($entityId, $ID)
	{
		if (!is_int($ID))
		{
			$ID = (int)$ID;
		}

		if ($entityId == Order\OrderStatus::NAME)
		{
			$data = Order\OrderStatus::getListInCrmFormat();
		}
		else
		{
			$data = Order\DeliveryStatus::getListInCrmFormat();
		}

		foreach ($data as $item)
		{
			if ($item['ID'] === $ID)
			{
				return $item;
			}
		}

		return false;
	}

	/**
	 * @return string
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	private function getNewOrderStatusId()
	{
		$result = [];
		$dbRes = Order\OrderStatus::getList();
		while ($data = $dbRes->fetch())
		{
			$result[$data['ID']] = $data;
		}

		do
		{
			$newId = chr(rand(65, 90)); //A-Z
			if (is_array($result) && count($result) >= 27)
			{
				$newId .= chr(rand(65, 90));
			}
		}
		while (isset($result[$newId]));

		return $newId;
	}

	/**
	 * @return string
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	private function getNewDeliveryStatusId()
	{
		$result = [];
		$dbRes = Order\DeliveryStatus::getList();
		while ($data = $dbRes->fetch())
		{
			$result[$data['ID']] = $data;
		}

		do
		{
			$newId = chr(rand(65, 90)).chr(rand(65, 90));
		}
		while (isset($result[$newId]));

		return $newId;
	}

	/**
	 * @throws SystemException
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function checkModules()
	{
		if (!Loader::includeModule("crm"))
		{
			throw new SystemException("Module \"crm\" not found.");
		}
		if (!Loader::includeModule("sale"))
		{
			throw new SystemException("Module \"sale\" not found.");
		}
		if (!Loader::includeModule("catalog"))
		{
			throw new SystemException("Module \"catalog\" not found.");
		}
	}

	protected function formatResult()
	{
		$this->arResult = array();

		$this->arResult["TYPE_SETTINGS"] = $this->arParams["TYPE_SETTINGS"];
		$this->arResult["SETTINGS_ID"] = $this->getSettingsId($this->arParams["TYPE_SETTINGS"]);
		$this->arResult["SETTINGS"] = $this->getSettings();
		$this->arResult["PAGE_SETTINGS"] = $this->getPageSettings();
	}

	protected function setTitle()
	{
		global $APPLICATION;

		switch ($this->arResult["SETTINGS_ID"])
		{
			case "order":
			case "shipment":
				$APPLICATION->SetTitle(Loc::getMessage("CRM_".$this->arResult["SETTINGS_ID"]."_PAGE_TITLE"));
				break;
			default:
				$APPLICATION->SetTitle(Loc::getMessage("CRM_COMMON_PAGE_TITLE"));
		}

	}

	protected function getSettingsId($typeSettings)
	{
		switch ($typeSettings)
		{
			case "order":
				return Order\OrderStatus::NAME;
				break;
			case "shipment":
				return Order\DeliveryStatus::NAME;
				break;
			case "fields":
				return "fields";
				break;
			default:
				return "common";
		}
	}

	protected function getSettings()
	{
		switch ($this->arParams["TYPE_SETTINGS"])
		{
			case "order":
				$settings = $this->getOrderSettings();
				break;
			case "shipment":
				$settings = $this->getShipmentSettings();
				break;
			case "fields":
				$settings = $this->getFieldsSettings();
				break;
			default:
				$settings = $this->getCommonSettings();
		}

		return $settings;
	}

	protected function getPageSettings()
	{
		$pageSettings = array();

		$blockFixed = current(CUserOptions::getOption(
			"crm", "crm_config_status", array("fix_footer" => "on"))) ? true : false;
		$pageSettings["BLOCK_FIXED"] = $blockFixed;
		$pageSettings["TITLE_FOOTER_PIN"] = ($blockFixed ?
			Loc::getMessage("CRM_STATUS_FOOTER_PIN_OFF") : Loc::getMessage("CRM_STATUS_FOOTER_PIN_ON"));
		$pageSettings["RAND_STRING"] = $this->randString();
		$pageSettings["OPTION_PREFIX"] = $this->optionPrefix;
		$pageSettings["LANGUAGE_ID"] = LANGUAGE_ID;
		$pageSettings["LIST_SITE_ID"] = $this->getListSiteId();
		$pageSettings["AJAX_URL"] = SITE_DIR.
			"bitrix/components/bitrix/crm.config.sale.settings/ajax.php?&".bitrix_sessid_get();

		return $pageSettings;
	}

	protected function getCommonSettings()
	{
		$settings = array(
			"TABS" => array()
		);

		$settings["TABS"][] = array(
			"id" => "csc_sale",
			"name" => Loc::getMessage("CRM_COMMON_TAB_TITLE_SALE"),
			"fields" => $this->getSaleOptions()
		);
		if (\CCrmSaleHelper::isWithOrdersMode())
		{
			$settings["TABS"][] = array(
				"id" => "csc_catalog",
				"name" => Loc::getMessage("CRM_COMMON_TAB_TITLE_CATALOG"),
				"fields" => $this->getCatalogOptions()
			);
		}

		return $settings;
	}

	/**
	 * @return array
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	protected function getSaleOptions()
	{
		$listProductReserveCondition = array();
		foreach (Bitrix\Sale\Configuration::getReservationConditionList(true) as $reserveId => $reserveTitle)
		{
			$listProductReserveCondition[$reserveId] = $reserveTitle;
		}

		$listCurrency = array();
		if (Loader::includeModule("currency"))
		{
			$currencyList = CurrencyManager::getCurrencyList();
			if (!empty($currencyList) && is_array($currencyList))
			{
				foreach ($currencyList as $currency => $title)
				{
					$listCurrency[$currency] = $title;
				}
			}
		}

		$listSite = array();
		$listSiteId = array();
		$siteData = array();
		$shopSiteValues = array();
		$listAddress = array();
		$siteIterator = Bitrix\Main\SiteTable::getList(
			array("select" => array("LID", "NAME", "DEF"), "order" => array("SORT" => "ASC")));
		while ($site = $siteIterator->fetch())
		{
			$siteData[$site["LID"]] = $site;
			$listSite[$site["LID"]] = $site["NAME"]." (".$site["LID"].")";

			$shopSiteValues[] = Option::get("sale", "SHOP_SITE_".$site["LID"]);

			$listAddress[$site["LID"]] = array();
			$listAddress[$site["LID"]]["location_zip"] = Option::get("sale", "location_zip", "", $site["LID"]);
			$listAddress[$site["LID"]]["location"] = Option::get("sale", "location", "", $site["LID"]);
			$listAddress[$site["LID"]]["display"] = ((isset($site["DEF"]) && $site["DEF"] == "Y") ? true : false);

			$listSiteId[] = $site["LID"];
		}

		$this->setListSiteId($listSiteId);

		$subscribeProdList = array();
		$subscribeProd = COption::GetOptionString("sale", "subscribe_prod", "");
		if ($subscribeProd <> '')
		{
			$subscribeProdList = unserialize($subscribeProd, ['allowed_classes' => false]);
		}
		$subscribeProdValues = array();
		foreach ($subscribeProdList as $siteLid => $subscribeProdValue)
		{
			if ($subscribeProdValue["use"] == "Y")
			{
				$subscribeProdValues[] = $siteLid;
			}
		}

		$numeratorsOrderType = Numerator::getOneByType(Registry::REGISTRY_TYPE_ORDER);
		$numeratorForOrdersId = ($numeratorsOrderType ? $numeratorsOrderType['id'] : "");

		$options = array();

		$options[] = array(
			"id" => "sale_service_section",
			"name" => Loc::getMessage("CRM_CF_SERVICE_AREA"),
			"type" => "section"
		);
		$options[] = array(
			"id" => $this->optionPrefix."format_quantity",
			"name" => Loc::getMessage("CRM_CF_FORMAT_QUANTITY"),
			"type" => "list",
			"items" => array(
				"AUTO" => Loc::getMessage("CRM_CF_FORMAT_QUANTITY_AUTO"),
				"2" => Loc::getMessage("CRM_CF_FORMAT_QUANTITY_2"),
				"3" => Loc::getMessage("CRM_CF_FORMAT_QUANTITY_3"),
				"4" => Loc::getMessage("CRM_CF_FORMAT_QUANTITY_4")
			),
			"value" => Option::get("sale", "format_quantity", "AUTO")
		);
		$options[] = array(
			"id" => $this->optionPrefix."value_precision",
			"name" => Loc::getMessage("CRM_CF_VALUE_PRECISION"),
			"type" => "list",
			"items" => array(
				"0" => Loc::getMessage("CRM_CF_VALUE_PRECISION_0"),
				"1" => Loc::getMessage("CRM_CF_VALUE_PRECISION_1"),
				"2" => Loc::getMessage("CRM_CF_VALUE_PRECISION_2"),
				"3" => Loc::getMessage("CRM_CF_VALUE_PRECISION_3"),
				"4" => Loc::getMessage("CRM_CF_VALUE_PRECISION_4")
			),
			"value" => Option::get("sale", "value_precision", 2)
		);
		$options[] = array(
			"id" => $this->optionPrefix."COUNT_DELIVERY_TAX",
			"name" => Loc::getMessage("CRM_CF_COUNT_DELIVERY_TAX"),
			"type" => "checkbox",
			"value" => Option::get("sale", "COUNT_DELIVERY_TAX", "N")
		);
		$options[] = array(
			"id" => $this->optionPrefix."SALE_ADMIN_NEW_PRODUCT",
			"name" => Loc::getMessage("CRM_CF_SALE_ADMIN_NEW_PRODUCT"),
			"type" => "checkbox",
			"value" => Option::get("sale", "SALE_ADMIN_NEW_PRODUCT", "N")
		);
		$options[] = array(
			"id" => $this->optionPrefix."default_currency",
			"name" => Loc::getMessage("CRM_CF_DEF_CURRENCY"),
			"type" => "list",
			"items" => $listCurrency,
			"value" => Option::get("sale", "default_currency")
		);
		$options[] = array(
			"id" => $this->optionPrefix."SHOP_SITE[]",
			"name" => Loc::getMessage("CRM_CF_IS_SHOP"),
			"type" => "list",
			"params" => array("multiple" => true),
			"items" => $listSite,
			"value" => $shopSiteValues
		);
		$options[] = array(
			"id" => $this->optionPrefix."order_default_responsible_id",
			"name" => Loc::getMessage("CRM_CF_ORDER_DEFAULT_RESPONSIBLE_ID"),
			"type" => "label",
			"value" => $this->getOrderDefaultResponsibleIdContent(),
		);
		$options[] = array(
			"id" => $this->optionPrefix.'tracking_check_switch',
			"name" => Loc::getMessage("CRM_CF_ORDER_TRACKING_AUTOCHECK"),
			"type" => "checkbox",
			"value" => Option::get("sale", "tracking_check_switch", "N")
		);

		if (CCrmSaleHelper::isWithOrdersMode())
		{
			/* Check section */
			$options[] = array(
				"id" => "sale_advance_check_section",
				"name" => Loc::getMessage("CRM_CF_BLOCK_CHECK_TITLE"),
				"type" => "section"
			);

			$options[] = [
				"id" => $this->optionPrefix."check_type_on_pay",
				"name" => Loc::getMessage("CRM_CHECK_TYPE_ON_PAY"),
				"type" => "list",
				"items" => [
					'sell' => Loc::getMessage('CRM_CHECK_TYPE_ON_PAY_SELL'),
					'prepayment' => Loc::getMessage('CRM_CHECK_TYPE_ON_PAY_PREPAYMENT'),
					'advance' => Loc::getMessage('CRM_CHECK_TYPE_ON_PAY_ADVANCE')
				],
				"value" => Option::get("sale", "check_type_on_pay", "sell")
			];
		}

		/* Reserve section */
		if (\CCrmSaleHelper::isWithOrdersMode())
		{
			$options[] = array(
				"id" => "sale_reserve_condition_section",
				"name" => Loc::getMessage("CRM_CF_SECTION_RESERVATION"),
				"type" => "section"
			);
			$options[] = array(
				"id" => $this->optionPrefix."product_reserve_condition",
				"name" => Loc::getMessage("CRM_CF_PRODUCT_RESERVE_CONDITION"),
				"type" => "list",
				"items" => $listProductReserveCondition,
				"value" => Option::get("sale", "product_reserve_condition")
			);
			$options[] = array(
				"id" => $this->optionPrefix."product_reserve_clear_period",
				"name" => Loc::getMessage("CRM_CF_PRODUCT_RESERVE_CLEAR_PERIOD"),
				"value" => Option::get("sale", "product_reserve_clear_period", "3")
			);
		}

		/* Weight section */
		$options[] = array(
			"id" => "sale_weight_section",
			"name" => Loc::getMessage("CRM_CF_WEIGHT_TITLE"),
			"type" => "section"
		);
		$options[] = array(
			"id" => $this->optionPrefix."WEIGHT_different_set",
			"name" => Loc::getMessage("CRM_CF_DIF_SETTINGS"),
			"type" => "checkbox",
			"value" => Option::get("sale", "WEIGHT_different_set", "N"),
			"params" => ["id" => "WEIGHT_different_set"]
		);
		$options[] = array(
			"id" => "WEIGHT_site_id",
			"name" => Loc::getMessage("CRM_CF_SITE_LIST"),
			"type" => "list",
			"items" => $listSite,
			"params" => Option::get("sale", "WEIGHT_different_set", "N") == "N" ?
				["disabled" => "disabled", "id" => "WEIGHT_site_id"] : ["id" => "WEIGHT_site_id"]
		);
		foreach ($siteData as $siteId => $site)
		{
			$options[] = array(
				"id" => "weight_".$siteId,
				"type" => "label",
				"value" => $this->getWeightContent($site)
			);
		}

		/* Address section */
		$options[] = array(
			"id" => "sale_location_section",
			"name" => Loc::getMessage("CRM_CF_LOCATION_TITLE"),
			"type" => "section"
		);
		$options[] = array(
			"id" => $this->optionPrefix."ADDRESS_different_set",
			"name" => Loc::getMessage("CRM_CF_DIF_SETTINGS"),
			"type" => "checkbox",
			"value" => Option::get("sale", "ADDRESS_different_set", "N"),
			"params" => ["id" => "ADDRESS_different_set"]
		);
		$options[] = array(
			"id" => "ADDRESS_current_site",
			"name" => Loc::getMessage("CRM_CF_SITE_LIST"),
			"type" => "list",
			"items" => $listSite,
			"params" => Option::get("sale", "ADDRESS_different_set", "N") == "N" ?
				["disabled" => "disabled", "id" => "ADDRESS_current_site"] : ["id" => "ADDRESS_current_site"]
		);
		foreach ($listAddress as $siteId => $address)
		{
			$options[] = array(
				"id" => "address_".$siteId,
				"type" => "label",
				"value" => $this->getAddressContent($siteId, $address)
			);
		}

		/* Subscribe section */
		$options[] = array(
			"id" => "sale_subscribe_section",
			"name" => Loc::getMessage("CRM_CF_SUBSCRIBE_TITLE"),
			"type" => "section"
		);
		$options[] = array(
			"id" => $this->optionPrefix."subscribe_prod[]",
			"name" => Loc::getMessage("CRM_CF_NOTIFY_PRODUCT_USE"),
			"type" => "list",
			"params" => array("multiple" => true),
			"items" => $listSite,
			"value" => $subscribeProdValues
		);
		$options[] = array(
			"id" => $this->optionPrefix."subscribe_repeated_notify",
			"name" => Loc::getMessage("CRM_CF_SUBSCRIBE_REPEATED_NOTIFY"),
			"type" => "checkbox",
			"value" => Option::get("catalog", "subscribe_repeated_notify")
		);

		/* Numerator section */
		$options[] = array(
			"id" => "sale_hideNumeratorSettings_section",
			"name" => Loc::getMessage("CRM_CF_ORDER_NUMERATOR_TEMPLATE"),
			"type" => "section"
		);
		$options[] = array(
			"id" => $this->optionPrefix."hideNumeratorSettings",
			"name" => Loc::getMessage("CRM_CF_NUMERATOR_TITLE"),
			"type" => "checkbox",
			"value" => ($numeratorForOrdersId ? "Y" : "N")
		);
		$options[] = array(
			"id" => "hideNumeratorSettingsContent",
			"type" => "label",
			"value" => $this->getNumeratorContent($numeratorForOrdersId),
		);

		return $options;
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function getOrderDefaultResponsibleIdContent()
	{
		global $APPLICATION;
		ob_start();
		$APPLICATION->IncludeComponent(
			"bitrix:main.user.selector",
			"",
			[
				"ID" => "order_default_responsible_id",
				"INPUT_NAME" => $this->optionPrefix."order_default_responsible_id",
				"LIST" => [Option::get("crm", "order_default_responsible_id")]
			]
		);
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	private function getNumeratorContent($numeratorForOrdersId)
	{
		global $APPLICATION;
		ob_start();
		$APPLICATION->IncludeComponent(
			"bitrix:main.numerator.edit",
			"",
			[
				"NUMERATOR_TYPE" => Registry::REGISTRY_TYPE_ORDER,
				"IS_EMBED_FORM" => true,
				"CSS_WRAP_CLASS" => "js-numerator-form",
				"NUMERATOR_ID" => $numeratorForOrdersId,
				"IS_HIDE_NUMERATOR_NAME" => true,
				"IS_HIDE_PAGE_TITLE" => true,
				"IS_HIDE_IS_DIRECT_NUMERATION" => true,
				"IS_SLIDER" => false
			]
		);
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	private function getWeightContent($site)
	{
		$isDefSite = ((isset($site["DEF"]) && $site["DEF"] == "Y") ? true : false);
		$class = ($isDefSite ? "" : "crm-sale-settings-hidden-mode");

		$unitList = CSaleMeasure::GetList("W");
		$siteId = HtmlFilter::encode($site["LID"]);

		$content = '<div id="par_WEIGHT_'.HtmlFilter::encode($site["LID"]).'" class="'.$class.'">';

		$content .= '<div class="crm-sale-settings-option-block">';
		$content .= '<div class="crm-sale-settings-option-label">'.Loc::getMessage("CRM_CF_WEIGHT_UNIT_LABLE").'</div>';
		$content .= '<div><select id="weight_unit_tmp['.$siteId.']" name="weight_unit_tmp['.$siteId.']">';
		foreach ($unitList as $key => $unit):
			$selectedWeightUnit = COption::GetOptionString("sale", "weight_unit", Loc::getMessage(
				"CRM_CF_WEIGHT_UNIT_GRAMM"), $site["LID"]);
			$content .= '<option value="'.floatval($unit["KOEF"]).'" '.
				($selectedWeightUnit == $unit["NAME"] ? "selected" : "").'>'.HtmlFilter::encode($unit["NAME"]).'</option>';
		endforeach;
		$content .= '</select></div>';
		$content .= '</div>';

		$content .= '<div class="crm-sale-settings-option-block">';
		$content .= '<div class="crm-sale-settings-option-label">'.Loc::getMessage("CRM_CF_WEIGHT_UNIT").'</div>';
		$content .= '<div>';
		$content .= '<input type="text" id="weight_unit['.$siteId.']" name="weight_unit['.$siteId.']" size="5" value="'.
			HtmlFilter::encode(COption::GetOptionString("sale", "weight_unit", Loc::getMessage(
				"CRM_CF_WEIGHT_UNIT_GRAMM"), $site["LID"])).'" />';
		$content .= '</div>';
		$content .= '</div>';

		$content .= '<div class="crm-sale-settings-option-block">';
		$content .= '<div class="crm-sale-settings-option-label">'.Loc::getMessage("CRM_CF_WEIGHT_KOEF").'</div>';
		$content .= '<div>';
		$content .= '<input type="text" id="weight_koef['.$siteId.']" name="weight_koef['.$siteId.']" size="5" value="'.
					HtmlFilter::encode(COption::GetOptionString("sale", "weight_koef", "1", $site["LID"])).'">';
		$content .= '</div>';
		$content .= '</div>';


		$content .= '</div>';

		return $content;
	}

	private function getAddressContent($siteId, $address)
	{
		global $APPLICATION;

		$class = ($address["display"] ? "" : "crm-sale-settings-hidden-mode");

		$content = '<div id="ADDRESS_block_'.HtmlFilter::encode($siteId).'" class="'.$class.'">';

		$content .= '<div class="crm-sale-settings-option-block">';
		$content .= '<div class="crm-sale-settings-option-label">'.Loc::getMessage("CRM_CF_LOCATION_ZIP").'</div>';
		$content .= '<div><input type="text" name="location_zip['.$siteId.']"
			value="'.HtmlFilter::encode($address["location_zip"]).'"></div>';
		$content .= '</div>';

		$content .= '<div class="crm-sale-settings-option-content">';
		ob_start();
		$APPLICATION->IncludeComponent(
			"bitrix:sale.location.selector.".\Bitrix\Sale\Location\Admin\Helper::getWidgetAppearance(),
			"",
			array(
				"ID" => "",
				"CODE" => $address["location"],
				"INPUT_NAME" => "location[".$siteId."]",
				"PROVIDE_LINK_BY" => "code",
				"SHOW_ADMIN_CONTROLS" => 'N',
				"SELECT_WHEN_SINGLE" => 'N',
				"FILTER_BY_SITE" => 'N',
				"SHOW_DEFAULT_LOCATIONS" => 'N',
				"SEARCH_BY_PRIMARY" => 'Y'
			),
			false
		);
		$content .= ob_get_contents();
		ob_end_clean();
		$content .= '</div>';

		$content .= '</div>';

		return $content;
	}

	protected function getCatalogOptions()
	{
		$strQuantityTrace = Option::get("catalog", "default_quantity_trace");
		$strAllowCanBuyZero = Option::get("catalog", "default_can_buy_zero");
		$strSubscribe = Option::get("catalog", "default_subscribe");

		$options = array();

		$options[] = array(
			"id" => "product_card_section",
			"name" => Loc::getMessage("CRM_CF_PRODUCT_CARD"),
			"type" => "section"
		);

		if (Catalog\Config\Feature::isCommonProductProcessingEnabled())
		{
			$options[] = array(
				"id" => $this->optionPrefix."product_card_slider_enabled",
				"name" => Loc::getMessage("CRM_CF_PRODUCT_CARD_SLIDER_ENABLED"),
				"type" => "checkbox",
				"value" => \Bitrix\Catalog\Config\State::isProductCardSliderEnabled(),
			);
		}

		//todo different regions
		if (true)
		{
			$options[] = array(
				"id" => $this->optionPrefix."default_product_vat_included",
				"name" => Loc::getMessage("CRM_CF_PRODUCT_DEFAULT_VAT_INCLUDED"),
				"type" => "checkbox",
				"value" => Option::get("catalog", "default_product_vat_included"),
			);
		}

		$options[] = array(
			"id" => "product_card_default_values_section",
			"name" => Loc::getMessage("CRM_CF_PRODUCT_CARD_DEFAULT_VALUES"),
			"type" => "section"
		);
		$options[] = array(
			"id" => $this->optionPrefix."default_quantity_trace",
			"name" => Loc::getMessage("CRM_CF_ENABLE_QUANTITY_TRACE"),
			"type" => "label",
			"value" => ($strQuantityTrace === "Y" ? Loc::getMessage("CRM_CF_PRODUCT_SETTINGS_STATUS_YES") :
				Loc::getMessage("CRM_CF_PRODUCT_SETTINGS_STATUS_NO")),
		);
		$options[] = array(
			"id" => $this->optionPrefix."default_can_buy_zero",
			"name" => Loc::getMessage("CRM_CF_ALLOW_CAN_BUY_ZERO_EXT"),
			"type" => "label",
			"value" => ($strAllowCanBuyZero === "Y" ? Loc::getMessage("CRM_CF_PRODUCT_SETTINGS_STATUS_YES") :
				Loc::getMessage("CRM_CF_PRODUCT_SETTINGS_STATUS_NO")),
		);
		$options[] = array(
			"id" => $this->optionPrefix."default_subscribe",
			"name" => Loc::getMessage("CRM_CF_PRODUCT_SUBSCRIBE"),
			"type" => "label",
			"value" => ($strSubscribe === "Y" ? Loc::getMessage("CRM_CF_PRODUCT_SETTINGS_STATUS_YES") :
				Loc::getMessage("CRM_CF_PRODUCT_SETTINGS_STATUS_NO")),
		);
		$options[] = array(
			"id" => "product_card_default_values_button",
			"name" => "",
			"type" => "label",
			"value" => "<input class='' type='button' id='product_card_settings' value='".
				Loc::getMessage('CRM_CF_PRODUCT_SETTINGS_CHANGE')."'>",
		);
		$options[] = array(
			"id" => "store_section",
			"name" => Loc::getMessage("CRM_CF_STORE"),
			"type" => "section"
		);
		if (Catalog\Config\Feature::isInventoryManagementEnabled())
		{
			$useStore = Catalog\Component\UseStore::isUsed() ?'Y':'N';

			$options[] = array(
				"id" => "default_use_store_control",
				"name" => Loc::getMessage("CRM_CF_USE_STORE_CONTROL_1"),
				"type" => "label",
				"value" => "<input class='' type='button' data-use-store-control='".$useStore."' id='store_use_settings' value='".
					Loc::getMessage('CRM_CF_PRODUCT_SETTINGS_CHANGE')."'>",
			);
		}
		else
		{
			if (Loader::includeModule('bitrix24'))
			{
				$helpLink = null;
				if (method_exists('\Bitrix\Catalog\Config\Feature','getInventoryManagementHelpLink'))
				{
					$helpLink = Catalog\Config\Feature::getInventoryManagementHelpLink();
				}
				if (!empty($helpLink))
				{
					ob_start();
					Catalog\Config\Feature::initUiHelpScope();
					$tarifLock = ob_get_contents();
					ob_end_clean();
					$tarifLock .= '<a href="#" onclick="BX.UI.InfoHelper.show(\'limit_shop_inventory_management\');">'
						.Loc::getMessage('CRM_CF_USE_STORE_CONTROL_LOCK_TARIFF')
						.'</a>';
				}
				else
				{
					ob_start();
					\CBitrix24::showTariffRestrictionButtons('catalog_inventory_management');
					$tarifLock = ob_get_contents();
					ob_end_clean();
				}
				$options[] = array(
					"id" => $this->optionPrefix."default_use_store_control",
					"name" => Loc::getMessage("CRM_CF_USE_STORE_CONTROL_1"),
					"type" => "custom",
					"value" => $tarifLock
				);
				unset($tarifLock, $helpLink);
			}
		}
		$options[] = array(
			"id" => $this->optionPrefix."enable_reservation",
			"name" => Loc::getMessage("CRM_CF_ENABLE_RESERVATION"),
			"type" => "checkbox",
			"value" => Option::get("catalog", "enable_reservation"),
		);

		return $options;
	}

	protected function getCommonSettingsOptions()
	{
		return array(
			"sale" => array(
				"format_quantity", "value_precision", "product_reserve_condition",
				"product_reserve_clear_period", "COUNT_DELIVERY_TAX", "check_type_on_pay",
				"default_currency", "SHOP_SITE", "hideNumeratorSettings", "subscribe_prod", "ADDRESS_different_set",
				"SALE_ADMIN_NEW_PRODUCT", "WEIGHT_different_set", "tracking_check_switch"
			),
			"catalog" => array(
				"default_use_store_control", "enable_reservation", "default_product_vat_included",
				"subscribe_repeated_notify", "product_card_slider_enabled"
			),
			"crm" => array("order_default_responsible_id")
		);
	}

	protected function getOrderSettings()
	{
		$semanticInfo = array(
			"START_FIELD" => Order\OrderStatus::getInitialStatus(),
			"FINAL_SUCCESS_FIELD" => Order\OrderStatus::getFinalStatus(),
			"FINAL_UNSUCCESS_FIELD" => Order\OrderStatus::getFinalUnsuccessfulStatus(),
			"FINAL_SORT" => Order\OrderStatus::getFinalStatusSort(),
			"ADD_CAPTION" => Loc::getMessage("CRM_STATUS_ADD_STATUS"),
			"DEFAULT_NAME" => Loc::getMessage("CRM_STATUS_DEFAULT_NAME_STATUS"),
			"DELETION_CONFIRMATION" => Loc::getMessage("CRM_STATUS_DELETION_CONFIRMATION_STATUS"),
		);

		$colorData = unserialize(COption::getOptionString("crm", "CONFIG_STATUS_".Order\OrderStatus::NAME), ['allowed_classes' => false]);

		$data = [];

		$statusList = Order\OrderStatus::getListInCrmFormat();
		foreach ($statusList as $status)
		{
			$color = $colorData[$status['STATUS_ID']]['COLOR'];
			if (!empty($color))
			{
				$status['COLOR'] = $color;
			}
			$data[$status['ID']] = $status;
		}

		$semanticInfo["FINAL_SORT"] = Order\OrderStatus::getFinalStatusSort();

		return array(
			"ID" => Order\OrderStatus::NAME,
			"NAME" => Loc::getMessage("CRM_STATUS_TYPE_ORDER_STATUS"),
			"DATA" => array(Order\OrderStatus::NAME => $data),
			"TYPE" => "SEPARATED",
			"SEMANTIC_INFO" => $semanticInfo,
			"SORTED_FIELDS" => $this->getSortedFields($semanticInfo, $data),
			"COLORS_DATA" => $colorData
		);
	}

	protected function getFieldsSettings()
	{
		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
		$uriString = $request->getRequestUri();
		$uri = new \Bitrix\Main\Web\Uri($uriString);
		$isEditMode = ($request->get('mode') === 'edit');
		$uri->deleteParams(['mode', 'field_id']);
		$listPath = $uri->getUri();
		$uri->addParams(['mode' => 'edit']);
		$detailPath = $uri->getUri();
		$detailPath .= '&field_id=#field_id#' ;
		return [
			'MODE' => $isEditMode ? 'edit' : null,
			'FIELD_ID' => ($isEditMode && !is_null($request->get('field_id'))) ? $request->get('field_id') : null,
			'LIST_URL' => $listPath,
			'EDIT_URL' => $detailPath,
		];
	}

	protected function getShipmentSettings()
	{
		$semanticInfo = array(
			"START_FIELD" => Order\DeliveryStatus::getInitialStatus(),
			"FINAL_SUCCESS_FIELD" => Order\DeliveryStatus::getFinalStatus(),
			"FINAL_UNSUCCESS_FIELD" => Order\DeliveryStatus::getFinalUnsuccessfulStatus(),
			"FINAL_SORT" => Order\DeliveryStatus::getFinalStatusSort(),
			"ADD_CAPTION" => Loc::getMessage("CRM_STATUS_ADD_STATUS"),
			"DEFAULT_NAME" => Loc::getMessage("CRM_STATUS_DEFAULT_NAME_STATUS"),
			"DELETION_CONFIRMATION" => Loc::getMessage("CRM_STATUS_DELETION_CONFIRMATION_STATUS"),
		);

		$colorData = unserialize(COption::getOptionString("crm", "CONFIG_STATUS_".Order\DeliveryStatus::NAME), ['allowed_classes' => false]);

		$data = [];

		$statusList = Order\DeliveryStatus::getListInCrmFormat();
		foreach ($statusList as $status)
		{
			$color = $colorData[$status['STATUS_ID']]['COLOR'];
			if (!empty($color))
			{
				$status['COLOR'] = $color;
			}
			$data[$status['ID']] = $status;
		}

		$semanticInfo["FINAL_SORT"] = Order\DeliveryStatus::getFinalStatusSort();

		return array(
			"ID" => Order\DeliveryStatus::NAME,
			"NAME" => Loc::getMessage("CRM_STATUS_TYPE_ORDER_SHIPMENT_STATUS"),
			"DATA" => array(Order\DeliveryStatus::NAME => $data),
			"TYPE" => "SEPARATED",
			"SEMANTIC_INFO" => $semanticInfo,
			"SORTED_FIELDS" => $this->getSortedFields($semanticInfo, $data),
			"COLORS_DATA" => $colorData
		);
	}

	protected function getSortedFields(array $semanticInfo, array $data)
	{
		$sortedFields = array();
		$sortedFields["INITIAL_FIELDS"][$this->arResult["SETTINGS_ID"]] = array();
		$sortedFields["EXTRA_FIELDS"][$this->arResult["SETTINGS_ID"]] = array();
		$sortedFields["FINAL_FIELDS"][$this->arResult["SETTINGS_ID"]] = array();
		$sortedFields["EXTRA_FINAL_FIELDS"][$this->arResult["SETTINGS_ID"]] = array();
		$sortedFields["SUCCESS_FIELDS"][$this->arResult["SETTINGS_ID"]] = array();
		$sortedFields["UNSUCCESS_FIELDS"][$this->arResult["SETTINGS_ID"]] = array();

		$number = 1;
		foreach ($data as $status)
		{
			$status["NUMBER"] = $number;
			if ($status["STATUS_ID"] == $semanticInfo["START_FIELD"])
			{
				$sortedFields["INITIAL_FIELDS"][$this->arResult["SETTINGS_ID"]] = $status;
				$sortedFields["SUCCESS_FIELDS"][$this->arResult["SETTINGS_ID"]][] = $status;
			}
			elseif ($status["STATUS_ID"] == $semanticInfo["FINAL_SUCCESS_FIELD"])
			{
				$sortedFields["FINAL_FIELDS"][$this->arResult["SETTINGS_ID"]]["SUCCESSFUL"] = $status;
				$sortedFields["SUCCESS_FIELDS"][$this->arResult["SETTINGS_ID"]][] = $status;
			}
			elseif ($status["STATUS_ID"] == $semanticInfo["FINAL_UNSUCCESS_FIELD"])
			{
				$sortedFields["FINAL_FIELDS"][$this->arResult["SETTINGS_ID"]]["UNSUCCESSFUL"] = $status;
				$sortedFields["UNSUCCESS_FIELDS"][$this->arResult["SETTINGS_ID"]][] = $status;
			}
			else
			{
				if ($status["SORT"] < $semanticInfo["FINAL_SORT"])
				{
					$sortedFields["EXTRA_FIELDS"][$this->arResult["SETTINGS_ID"]][] = $status;
					$sortedFields["SUCCESS_FIELDS"][$this->arResult["SETTINGS_ID"]][] = $status;
				}
				else
				{
					$sortedFields["EXTRA_FINAL_FIELDS"][$this->arResult["SETTINGS_ID"]][] = $status;
					$sortedFields["UNSUCCESS_FIELDS"][$this->arResult["SETTINGS_ID"]][] = $status;
				}
			}
			$number++;
		}

		return $sortedFields;
	}
}
