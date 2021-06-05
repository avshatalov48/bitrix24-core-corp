<?
$module_id = "sale";
/** @global CMain $APPLICATION */
/** @global string $RestoreDefaults */
/** @global string $Update */

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Config\Option;
use Bitrix\Sale\Cashbox;
use Bitrix\Sale\Registry;
use Bitrix\Sale\SalesZone;
use Bitrix\Sale;
use Bitrix\Main\Localization\Loc;


$SALE_RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($SALE_RIGHT>="R") :

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/options.php');
IncludeModuleLangFile(__FILE__);

Main\Page\Asset::getInstance()->addJs('/bitrix/js/sale/options.js');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/sale.css");

Loader::includeModule('sale');
Loader::includeModule('currency');

$lpEnabled = CSaleLocation::isLocationProEnabled();
$lMigrated = CSaleLocation::isLocationProMigrated();

$siteList = array();
$siteIterator = SiteTable::getList(array(
	'select' => array('LID', 'NAME'),
	'order' => array('SORT' => 'ASC')
));
while ($oneSite = $siteIterator->fetch())
{
	$siteList[] = array('ID' => $oneSite['LID'], 'NAME' => $oneSite['NAME']);
}
unset($oneSite, $siteIterator);
$siteCount = count($siteList);

$bWasUpdated = false;

if ($_SERVER['REQUEST_METHOD'] == "GET" && $RestoreDefaults <> '' && $SALE_RIGHT=="W" && check_bitrix_sessid())
{
	$bWasUpdated = true;

	COption::RemoveOption("sale");
	$z = CGroup::GetList("id", "asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
	while($zr = $z->Fetch())
		$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
}

$arAllOptions =
	array(
		Array("order_email", GetMessage("SALE_EMAIL_ORDER"), "order@".$SERVER_NAME, Array("text", 30)),
		//Array("default_email", GetMessage("SALE_EMAIL_REGISTER"), "admin@".$SERVER_NAME, Array("text", 30)),
		Array("delete_after", GetMessage("SALE_DELETE_AFTER"), "", Array("text", 10)),
		Array("order_list_date", GetMessage("SALE_ORDER_LIST_DATE"), 30, Array("text", 10)),
		Array("MAX_LOCK_TIME", GetMessage("SALE_MAX_LOCK_TIME"), 30, Array("text", 10)),
		Array("GRAPH_WEIGHT", GetMessage("SALE_GRAPH_WEIGHT"), 800, Array("text", 10)),
		Array("GRAPH_HEIGHT", GetMessage("SALE_GRAPH_HEIGHT"), 600, Array("text", 10)),
		Array("path2user_ps_files", GetMessage("SALE_PATH2UPSF"), BX_PERSONAL_ROOT."/php_interface/include/sale_payment/", Array("text", 40)),
		//Array("path2custom_view_order", GetMessage("SMO_SALE_PATH2ORDER"), "", Array("text", 40)),
		Array("lock_catalog", GetMessage("SMO_LOCK_CATALOG"), "Y", Array("checkbox", 40)),
		(CBXFeatures::IsFeatureEnabled('SaleAffiliate')) ? Array("affiliate_param_name", GetMessage("SMOS_AFFILIATE_PARAM"), "partner", Array("text", 40)) : array(),
		(CBXFeatures::IsFeatureEnabled('SaleAffiliate')) ? Array("affiliate_life_time", GetMessage("SMO_AFFILIATE_LIFE_TIME"), "30", Array("text", 10)): array(),
		Array("show_order_sum", GetMessage("SMO_SHOW_ORDER_SUM"), "N", Array("checkbox", 40)),
		Array("show_order_product_xml_id", GetMessage("SMO_SHOW_ORDER_PRODUCT_XML_ID"), "N", Array("checkbox", 40)),
		Array("show_paysystem_action_id", GetMessage("SMO_SHOW_PAYSYSTEM_ACTION_ID"), "N", Array("checkbox", 40)),
		Array("measurement_path", GetMessage("SMO_MEASUREMENT_PATH"), "/bitrix/modules/sale/measurements.php", Array("text", 40)),
		//Array("use_delivery_handlers", GetMessage("SMO_USE_DELIVERY_HANDLERS"), "N", Array("checkbox", 40)),
		Array("delivery_handles_custom_path", GetMessage("SMO_DELIVERY_HANDLERS_CUSTOM_PATH"), BX_PERSONAL_ROOT."/php_interface/include/sale_delivery/", Array("text", 40)),
		Array("use_secure_cookies", GetMessage("SMO_USE_SECURE_COOKIES"), "N", Array("checkbox", 40)),
		Array("encode_fuser_id", GetMessage("SMO_ENCODE_FUSER_ID"), "N", Array("checkbox", 40)),
		//Array("recalc_product_list", GetMessage("SALE_RECALC_PRODUCT_LIST"), "N", Array("checkbox", 40)),
		//Array("recalc_product_list_period", GetMessage("SALE_RECALC_PRODUCT_LIST_PERIOD"), 7, Array("text", 10)),
		Array("COUNT_DISCOUNT_4_ALL_QUANTITY", GetMessage("SALE_OPT_COUNT_DISCOUNT_4_ALL_QUANTITY"), "N", Array("checkbox", 40)),
		Array("COUNT_DELIVERY_TAX", GetMessage("SALE_OPT_COUNT_DELIVERY_TAX"), "N", Array("checkbox", 40)),
		Array("QUANTITY_FACTORIAL", GetMessage("SALE_OPT_QUANTITY_FACTORIAL"), "N", Array("checkbox", 40)),
		Array("product_viewed_save", GetMessage("SALE_PRODUCT_VIEWED_SAVE"), "Y", Array("checkbox", 40)),
		Array("viewed_capability", GetMessage("SALE_VIEWED_CAPABILITY"), "Y", Array("checkbox", 40)),
		Array("viewed_time", GetMessage("SALE_VIEWED_TIME"), 90, Array("text", 10)),
		Array("viewed_count", GetMessage("SALE_VIEWED_COUNT"), 100, Array("text", 10)),
		Array("SALE_ADMIN_NEW_PRODUCT", GetMessage("SALE_ADMIN_NEW_PRODUCT"), "N", Array("checkbox", 40)),
		Array("use_ccards", GetMessage("SALE_ADMIN_USE_CARDS"), "N", Array("checkbox", 40)),
		Array("show_basket_props_in_order_list", GetMessage("SALE_SHOW_BASKET_PROPS_IN_ORDER_LIST"), "Y", Array("checkbox", 40)),
		);

$arOrderFlags = array("P" => GetMessage("SMO_PAYMENT_FLAG"), "C" => GetMessage("SMO_CANCEL_FLAG"), "D" => GetMessage("SMO_DELIVERY_FLAG"));
$numeratorForOrdersId = '';
$numeratorsOrderType = Main\Numerator\Numerator::getOneByType(Registry::REGISTRY_TYPE_ORDER);
if ($numeratorsOrderType)
{
	$numeratorForOrdersId = $numeratorsOrderType['id'];
}
$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "sale_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
	array("DIV" => "edit7", "TAB" => GetMessage("SALE_TAB_WEIGHT"), "ICON" => "sale_settings", "TITLE" => GetMessage("SALE_TAB_WEIGHT_TITLE")),
	array("DIV" => "edit5", "TAB" => GetMessage("SALE_TAB_ADDRESS"), "ICON" => "sale_settings", "TITLE" => GetMessage("SALE_TAB_ADDRESS_TITLE"))
);

if (CBXFeatures::IsFeatureEnabled('SaleCCards') && COption::GetOptionString($module_id, "use_ccards", "N") == "Y")
	$aTabs[] = array("DIV" => "edit2", "TAB" => GetMessage("SALE_TAB_2"), "ICON" => "sale_settings", "TITLE" => GetMessage("SMO_CRYPT_TITLE"));

$aTabs[] = array("DIV" => "edit3", "TAB" => GetMessage("SALE_TAB_3"), "ICON" => "sale_settings", "TITLE" => GetMessage("SALE_TAB_3_TITLE"));
$aTabs[] = array("DIV" => "edit4", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "sale_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS"));
$aTabs[] = array("DIV" => "edit8", "TAB" => GetMessage("SALE_TAB_AUTO"), "ICON" => "sale_settings", "TITLE" => GetMessage("SALE_TAB_AUTO_TITLE"));
$aTabs[] = array("DIV" => "edit9", "TAB" => GetMessage("SALE_TAB_ARCHIVE"), "ICON" => "sale_settings", "TITLE" => GetMessage("SALE_TAB_ARCHIVE_TITLE"));
$aTabs[] = array("DIV" => "edit10", "TAB" => GetMessage("SALE_TAB_ORDER_NUMERATOR_TEMPLATE"), "ICON" => "sale_settings", "TITLE" => GetMessage("SALE_TAB_ORDER_NUMERATOR_TEMPLATE_TITLE"));
$tabControl = new CAdminTabControl("tabControl", $aTabs);

$strWarning = "";
function addNumeratorErrorToWarningString($_numeratorResult)
{
	$numeratorWarningsString = '';
	foreach ($_numeratorResult->getErrors() as $error)
	{
		$numeratorWarningsString = $error->getMessage() . '<br>';
	}
	return $numeratorWarningsString;
}
if ($_SERVER['REQUEST_METHOD'] == "POST" && $Update <> '' && $SALE_RIGHT == "W" && check_bitrix_sessid())
{
	if (isset($_POST['hideNumeratorSettings']) && $_POST['hideNumeratorSettings'] != "Y")
	{
		$numerator = Main\Numerator\Numerator::create();
		$validationResult = $numerator->setConfig($_POST);
		if (!$validationResult->isSuccess())
		{
			$strWarning .= addNumeratorErrorToWarningString($validationResult);
		}
	}
	else
	{
		$bWasUpdated = true;

		COption::RemoveOption($module_id, "weight_unit");
		COption::RemoveOption($module_id, "weight_koef");

		if (!empty($_REQUEST["WEIGHT_dif_settings"]))
		{
			for ($i = 0; $i < $siteCount; $i++)
			{
				COption::SetOptionString($module_id, "weight_unit", trim($_REQUEST["weight_unit"][$siteList[$i]["ID"]]), false, $siteList[$i]["ID"]);
				COption::SetOptionString($module_id, "weight_koef", floatval($_REQUEST["weight_koef"][$siteList[$i]["ID"]]), false, $siteList[$i]["ID"]);
			}
			COption::SetOptionString($module_id, "WEIGHT_different_set", "Y");
		}
		else
		{
			$site_id = trim($_REQUEST["WEIGHT_current_site"]);
			COption::SetOptionString($module_id, "weight_unit", trim($_REQUEST["weight_unit"][$site_id]));
			COption::SetOptionString($module_id, "weight_koef", floatval($_REQUEST["weight_koef"][$site_id]));
			COption::SetOptionString($module_id, "WEIGHT_different_set", "N");
		}

		COption::RemoveOption($module_id, "location_zip");
		COption::RemoveOption($module_id, "location");

		if (!empty($_REQUEST["ADDRESS_dif_settings"]))
		{
			for ($i = 0; $i < $siteCount; $i++)
			{
				COption::SetOptionString($module_id, "location_zip", $_REQUEST["location_zip"][$siteList[$i]["ID"]], false, $siteList[$i]["ID"]);
				COption::SetOptionString($module_id, "location", $_REQUEST["location"][$siteList[$i]["ID"]], false, $siteList[$i]["ID"]);
			}
			COption::SetOptionString($module_id, "ADDRESS_different_set", "Y");
		}
		else
		{
			$site_id = trim($_REQUEST["ADDRESS_current_site"]);
			COption::SetOptionString($module_id, "location_zip", $_REQUEST["location_zip"][$site_id]);
			COption::SetOptionString($module_id, "location", $_REQUEST["location"][$site_id]);
			COption::SetOptionString($module_id, "ADDRESS_different_set", "N");
		}

		if(!$lMigrated )
		{
			COption::RemoveOption($module_id, "sales_zone_countries");
			COption::RemoveOption($module_id, "sales_zone_regions");
			COption::RemoveOption($module_id, "sales_zone_cities");
		}

		if(!$lpEnabled)
		{
			if (!empty($_REQUEST["ADDRESS_dif_settings"]))
			{
				for ($i = 0; $i < $siteCount; $i++)
				{
					if($lMigrated)
					{
						try
						{
							\Bitrix\Sale\SalesZone::saveSelectedTypes(array(
								'COUNTRY' => $_REQUEST["sales_zone_countries"][$siteList[$i]["ID"]],
								'REGION' => $_REQUEST["sales_zone_regions"][$siteList[$i]["ID"]],
								'CITY' => $_REQUEST["sales_zone_cities"][$siteList[$i]["ID"]]
							), $siteList[$i]["ID"]);
						}
						catch(Exception $e)
						{
						}
					}
					else
					{
						COption::SetOptionString($module_id, "sales_zone_countries", implode(":", $_REQUEST["sales_zone_countries"][$siteList[$i]["ID"]]), false, $siteList[$i]["ID"]);
						COption::SetOptionString($module_id, "sales_zone_regions", implode(":",$_REQUEST["sales_zone_regions"][$siteList[$i]["ID"]]), false, $siteList[$i]["ID"]);
						COption::SetOptionString($module_id, "sales_zone_cities", implode(":",$_REQUEST["sales_zone_cities"][$siteList[$i]["ID"]]), false, $siteList[$i]["ID"]);
					}
				}
			}
			else
			{
				$site_id = trim($_REQUEST["ADDRESS_current_site"]);

				if($lMigrated)
				{
					try
					{
						\Bitrix\Sale\SalesZone::saveSelectedTypes(array(
							'COUNTRY' => $_REQUEST["sales_zone_countries"][$site_id],
							'REGION' => $_REQUEST["sales_zone_regions"][$site_id],
							'CITY' => $_REQUEST["sales_zone_cities"][$site_id]
						), $site_id);
					}
					catch(Exception $e)
					{
					}
				}
				else
				{
					COption::SetOptionString($module_id, "sales_zone_countries", implode(":",$_REQUEST["sales_zone_countries"][$site_id]));
					COption::SetOptionString($module_id, "sales_zone_regions", implode(":",$_REQUEST["sales_zone_regions"][$site_id]));
					COption::SetOptionString($module_id, "sales_zone_cities", implode(":",$_REQUEST["sales_zone_cities"][$site_id]));
				}
			}
		}

		for ($i = 0, $intCount = count($arAllOptions); $i < $intCount; $i++)
		{
			if(!empty($arAllOptions[$i]))
			{
				$name = $arAllOptions[$i][0];
				$val = ${$name};
				if ($arAllOptions[$i][3][0]=="checkbox" && $val!="Y")
					$val = "N";

				if ($name == "path2user_ps_files" && mb_substr($val, mb_strlen($val) - 1, 1) != "/")
				{
					$val .= "/";
				}
				COption::SetOptionString("sale", $name, $val, $arAllOptions[$i][1]);
			}
		}

		$rsAgents = CAgent::GetList(array("ID"=>"DESC"), array(
			"MODULE_ID" => "sale",
			"NAME" => "\\Bitrix\\Sale\\Basket::deleteOldAgent(%",
		));

		while($arAgent = $rsAgents->Fetch())
		{
			CAgent::Delete($arAgent["ID"]);
		}

		$delete_after = (int)COption::GetOptionInt("sale", "delete_after");
		if ($delete_after > 0)
			CAgent::AddAgent("\\Bitrix\\Sale\\Basket::deleteOldAgent(".$delete_after.");", "sale", "N", 8*60*60, "", "Y");

		/*$recalc_product_list_period = intval(COption::GetOptionInt("sale", "recalc_product_list_period", 7));
		CAgent::RemoveAgent("CSaleProduct::RefreshProductList();", "sale");
		if(
			COption::GetOptionString("sale", "recalc_product_list", "N") == "Y"
			&&  $recalc_product_list_period > 0
		)
		{
			CAgent::AddAgent("CSaleProduct::RefreshProductList();", "sale", "N", 60*60*24*$recalc_product_list_period, "", "Y");
		}*/

		if(CBXFeatures::IsFeatureEnabled('SaleAffiliate'))
		{
			COption::SetOptionString("sale", "affiliate_plan_type", $affiliate_plan_type);
		}
		$arAmountSer = Array();
		foreach($amount_val as $key =>$val)
		{
			if((float)$val > 0)
				$arAmountSer[$key] = array("AMOUNT" => (float)$val, "CURRENCY" => $amount_currency[$key]);
		}
		if(!empty($arAmountSer))
			COption::SetOptionString("sale", "pay_amount", serialize($arAmountSer));

		CAgent::RemoveAgent("CSaleOrder::RemindPayment();", "sale");
		COption::RemoveOption("sale", "pay_reminder");
		if (isset($_POST["reminder"]) && is_array($_POST["reminder"]) && !empty($_POST["reminder"]))
		{
			COption::SetOptionString("sale", "pay_reminder", serialize($_POST["reminder"]));
			CAgent::AddAgent("CSaleOrder::RemindPayment();", "sale", "N", 86400, "", "Y");
		}

		//subscribe product
		$rsAgents = CAgent::GetList(
			array("ID"=>"DESC"),
			array(
				"MODULE_ID" => "sale",
				"NAME" => "CSaleBasket::ClearProductSubscribe(%",
			)
		);
		while($arAgent = $rsAgents->Fetch())
			CAgent::Delete($arAgent["ID"]);
		if(!empty($subscribProd))
		{
			foreach($siteList as $vv)
			{
				$lid = $vv["ID"];
				$val = $subscribProd[$lid];

				if ($val["use"] == "Y")
				{
					if (intval($val["del_after"]) <= 0)
						$subscribProd[$lid]["del_after"] = 30;

					CAgent::AddAgent("CSaleBasket::ClearProductSubscribe('".EscapePHPString($lid)."');", "sale", "N", intval($subscribProd[$lid]["del_after"])*24*60*60, "", "Y");
				}
			}
			COption::SetOptionString("sale", "subscribe_prod", serialize($subscribProd));
		}

		//viewed product
		if(!empty($viewed))
		{
			foreach ($viewed as $lid => $val)
			{
				if (intval($val["time"]) <= 0)
					$viewed[$lid]["time"] = 90;
				if (intval($val["count"]) <= 0)
					$viewed[$lid]["count"] = 1000;
			}
			COption::SetOptionString("sale", "viewed_product", serialize($viewed));
		}

		if(isset($_POST['viewed_capability']) && $_POST['viewed_capability'] == "Y")
		{
			COption::SetOptionString("sale", "viewed_capability", "Y");
		}
		else
		{
			COption::SetOptionString("sale", "viewed_capability", "N");
		}

		$rsAgents = CAgent::GetList(array("ID"=>"DESC"), array(
			"MODULE_ID" => "sale",
			"NAME" => "CSaleViewedProduct::ClearViewed();",
		));
		if (!$arAgent = $rsAgents->Fetch())
		{
			CAgent::AddAgent("CSaleViewedProduct::ClearViewed();", "sale", "N", 86400, "", "Y");
		}

		COption::SetOptionString("sale", "default_currency", $CURRENCY_DEFAULT);
		COption::SetOptionString("sale", "crypt_algorithm", $crypt_algorithm);
		COption::SetOptionString("sale", "sale_data_file", $sale_data_file);
		COption::SetOptionString("sale", "sale_data_file", $sale_data_file);

		if ($sale_ps_success_path == "")
			$sale_ps_success_path = "/";
		COption::SetOptionString("sale", "sale_ps_success_path", $sale_ps_success_path);

		if ($sale_ps_fail_path == "")
			$sale_ps_fail_path = "/";
		COption::SetOptionString("sale", "sale_ps_fail_path", $sale_ps_fail_path);

		if ($sale_location_selector_appearance == "")
			$sale_location_selector_appearance = "steps";
		COption::SetOptionString("sale", "sale_location_selector_appearance", $sale_location_selector_appearance);

		COption::SetOptionString("sale", "status_on_paid", $PAID_STATUS);
		COption::SetOptionString("sale", "status_on_half_paid", $HALF_PAID_STATUS);
		COption::SetOptionString("sale", "status_on_allow_delivery", $ALLOW_DELIVERY_STATUS);
		COption::SetOptionString("sale", "status_on_allow_delivery_one_of", $ALLOW_DELIVERY_ONE_OF_STATUS);

		COption::SetOptionString("sale", "status_on_shipped_shipment", $SHIPMENT_SHIPPED_STATUS);
		COption::SetOptionString("sale", "status_on_shipped_shipment_one_of", $SHIPMENT_SHIPPED_ONE_OF_STATUS);

		COption::SetOptionString("sale", "shipment_status_on_allow_delivery", $SHIPMENT_ALLOW_DELIVERY_TO_SHIPMENT_STATUS);
		COption::SetOptionString("sale", "shipment_status_on_shipped", $SHIPMENT_SHIPPED_TO_SHIPMENT_STATUS);

		COption::SetOptionString("sale", "status_on_payed_2_allow_delivery", $PAYED_2_ALLOW_DELIVERY);

		COption::SetOptionString("sale", "status_on_change_allow_delivery_after_paid", $CHANGE_ALLOW_DELIVERY_AFTER_PAID);
		COption::SetOptionString("sale", "allow_deduction_on_delivery", $ALLOW_DEDUCTION_ON_DELIVERY);

		COption::SetOptionString("sale", "format_quantity", ($FORMAT_QUANTITY == 'AUTO' ? $FORMAT_QUANTITY: intval($FORMAT_QUANTITY)));

		COption::SetOptionString("sale", "value_precision", (intval($VALUE_PRECISION) < 0 ? 2 : intval($VALUE_PRECISION)));

		$oldExpirationProcessingEvents = Option::get('sale', 'expiration_processing_events');

		COption::SetOptionString("sale", "expiration_processing_events", $EXPIRATION_PROCESSING_EVENTS);

		if ($oldExpirationProcessingEvents != $EXPIRATION_PROCESSING_EVENTS)
		{
			$eventManager = Main\EventManager::getInstance();

			if ($EXPIRATION_PROCESSING_EVENTS == "Y")
			{
				Sale\Compatible\EventCompatibility::registerEvents();

				$eventManager->registerEventHandlerCompatible('sale', 'OnBeforeBasketAdd', 'sale', '\Bitrix\Sale\Internals\ConversionHandlers', 'onBeforeBasketAdd');
				$eventManager->registerEventHandlerCompatible('sale', 'OnBasketAdd', 'sale', '\Bitrix\Sale\Internals\ConversionHandlers', 'onBasketAdd');
				$eventManager->registerEventHandlerCompatible('sale', 'OnOrderAdd', 'sale', '\Bitrix\Sale\Internals\ConversionHandlers', 'onOrderAdd');
				$eventManager->registerEventHandlerCompatible('sale', 'OnSalePayOrder', 'sale', '\Bitrix\Sale\Internals\ConversionHandlers', 'onSalePayOrder');

				$eventManager->unRegisterEventHandler('sale', 'OnSaleBasketItemSaved', 'sale', '\Bitrix\Sale\Internals\ConversionHandlers', 'onSaleBasketItemSaved');
				$eventManager->unRegisterEventHandler('sale', 'OnSaleOrderSaved', 'sale', '\Bitrix\Sale\Internals\ConversionHandlers', 'onSaleOrderSaved');
				$eventManager->unRegisterEventHandler('sale', 'OnSaleOrderPaid', 'sale', '\Bitrix\Sale\Internals\ConversionHandlers', 'onSaleOrderPaid');
			}
			else
			{
				Sale\Compatible\EventCompatibility::unRegisterEvents();

				$eventManager->unRegisterEventHandler('sale', 'OnBeforeBasketAdd', 'sale', '\Bitrix\Sale\Internals\ConversionHandlers', 'onBeforeBasketAdd');
				$eventManager->unRegisterEventHandler('sale', 'OnBasketAdd', 'sale', '\Bitrix\Sale\Internals\ConversionHandlers', 'onBasketAdd');
				$eventManager->unRegisterEventHandler('sale', 'OnOrderAdd', 'sale', '\Bitrix\Sale\Internals\ConversionHandlers', 'onOrderAdd');
				$eventManager->unRegisterEventHandler('sale', 'OnSalePayOrder', 'sale', '\Bitrix\Sale\Internals\ConversionHandlers', 'onSalePayOrder');

				$eventManager->registerEventHandler('sale', 'OnSaleBasketItemSaved', 'sale', '\Bitrix\Sale\Internals\ConversionHandlers', 'onSaleBasketItemSaved');
				$eventManager->registerEventHandler('sale', 'OnSaleOrderSaved', 'sale', '\Bitrix\Sale\Internals\ConversionHandlers', 'onSaleOrderSaved');
				$eventManager->registerEventHandler('sale', 'OnSaleOrderPaid', 'sale', '\Bitrix\Sale\Internals\ConversionHandlers', 'onSaleOrderPaid');
			}
		}

		$ORDER_HISTORY_LOG_LEVEL = intval($ORDER_HISTORY_LOG_LEVEL);
		COption::SetOptionString("sale", "order_history_log_level", $ORDER_HISTORY_LOG_LEVEL);

		$ORDER_HISTORY_ACTION_LOG_LEVEL = intval($ORDER_HISTORY_ACTION_LOG_LEVEL);
		COption::SetOptionString("sale", "order_history_action_log_level", $ORDER_HISTORY_ACTION_LOG_LEVEL);


		if (!empty($SELECTED_FIELDS) && is_array($SELECTED_FIELDS))
		{
			for ($i = 0, $intCount = count($SELECTED_FIELDS); $i < $intCount; $i++)
			{
				if ($saveValue <> '')
					$saveValue .= ",";

				$saveValue .= $SELECTED_FIELDS[$i];
			}
		}
		else
		{
			$saveValue = "ID,USER,PAY_SYSTEM,PRICE,STATUS,PAYED,PS_STATUS,CANCELED,BASKET";
		}
		COption::SetOptionString("sale", "order_list_fields", $saveValue);

		// account number generation - via numerator
		if (isset($_POST['hideNumeratorSettings']) && $_POST['hideNumeratorSettings'] == "Y")
		{
			if ($numeratorForOrdersId)
			{
				Main\Numerator\Numerator::delete($numeratorForOrdersId);
			}
		}
		else
		{
			if ($numeratorForOrdersId)
			{
				$numeratorUpdateResult = Main\Numerator\Numerator::update($numeratorForOrdersId, $_POST);
				if (!$numeratorUpdateResult->isSuccess())
				{
					$strWarning .= addNumeratorErrorToWarningString($numeratorUpdateResult);
				}
			}
			else
			{
				$numeratorOrder = Main\Numerator\Numerator::create();
				$numeratorOrderValidationResult = $numeratorOrder->setConfig($_POST);
				if ($numeratorOrderValidationResult->isSuccess())
				{
					$numeratorOrderSaveResult = $numeratorOrder->save();
					if (!$numeratorOrderSaveResult->isSuccess())
					{
						$strWarning .= addNumeratorErrorToWarningString($numeratorOrderSaveResult);
					}
				}
				else
				{
					$strWarning .= addNumeratorErrorToWarningString($numeratorOrderValidationResult);
				}
			}
		}

		//subscribe product
		if (!empty($defaultDeductStore))
		{
			COption::RemoveOption("sale", "deduct_store_id");

			foreach ($defaultDeductStore as $lid => $val)
			{
				if (isset($val["save"]) && $val["save"] == "Y")
					COption::SetOptionString("sale", "deduct_store_id", intval($val["id"]), "", $lid);
			}
		}

		//SAVE SHOP LIST SITE
		foreach($siteList as $val)
		{
			COption::RemoveOption("sale", "SHOP_SITE_".$val["ID"]);
		}
		if (isset(${"SHOP_SITE"}) AND is_array(${"SHOP_SITE"}))
		{
			foreach (${"SHOP_SITE"} as $key => $val)
			{
				COption::SetOptionString("sale", "SHOP_SITE_".$val, $val);
			}
		}


		$p2p_del_exp_old = COption::GetOptionString("sale", "p2p_del_exp", 10);

		$agentData = CAgent::GetList(array("ID"=>"DESC"), array(
			"MODULE_ID" => "sale",
			"NAME" => "\\Bitrix\\Sale\\Product2ProductTable::addProductsByAgent(%",
		));

		$agent = $agentData->Fetch();

		if ($SALE_P2P_ALLOW_COLLECT_DATA == "Y")
		{
			if (!$agent)
			{
				$limit = (int)Option::get("sale", "p2p_limit_collecting_per_hit", 100);
				CAgent::AddAgent("Bitrix\\Sale\\Product2ProductTable::addProductsByAgent($limit);", "sale", "N", 60, "", "Y");
			}
		}
		else
		{
			$SALE_P2P_ALLOW_COLLECT_DATA = "N";
			if ($agent['ID'] > 0)
			{
				CAgent::Delete($agent['ID']);
			}
		}

		COption::SetOptionString("sale", "p2p_allow_collect_data", $SALE_P2P_ALLOW_COLLECT_DATA);
		COption::SetOptionString("sale", "p2p_status_list", serialize($SALE_P2P_STATUS_LIST));
		if(intval($p2p_del_period) <= 0)
			$p2p_del_period = 10;
		COption::SetOptionString("sale", "p2p_del_period", $p2p_del_period);
		if(intval($p2p_del_exp) <= 0)
			$p2p_del_exp = 10;
		COption::SetOptionString("sale", "p2p_del_exp", $p2p_del_exp);

		$rsAgents = CAgent::GetList(array("ID"=>"DESC"), array(
			"MODULE_ID" => "sale",
			"NAME" => "\\Bitrix\\Sale\\Product2ProductTable::deleteOldProducts(%",
		));
		while($arAgent = $rsAgents->Fetch())
		{
			CAgent::Delete($arAgent["ID"]);
		}

		CAgent::AddAgent("Bitrix\\Sale\\Product2ProductTable::deleteOldProducts(".$p2p_del_exp.");", "sale", "N", 24 * 3600 * $p2p_del_period, "", "Y");

		foreach ($siteList as &$oneSite)
		{
			$valCurrency = trim(${"CURRENCY_".$oneSite['ID']});
			if ($valCurrency == '') $valCurrency = false;
			$arFields = array(
				'LID' => $oneSite['ID'],
				'CURRENCY' => $valCurrency
			);

			if ($arRes = CSaleLang::GetByID($oneSite['ID']))
			{
				if ($valCurrency!==false)
				{
					CSaleLang::Update($oneSite['ID'], $arFields);
				}
				else
				{
					CSaleLang::Delete($oneSite['ID']);
				}
			}
			else
			{
				if ($valCurrency!==false)
				{
					CSaleLang::Add($arFields);
				}
			}

			CSaleGroupAccessToSite::DeleteBySite($oneSite['ID']);
			if (isset(${"SITE_USER_GROUPS_".$oneSite['ID']})
				&& is_array(${"SITE_USER_GROUPS_".$oneSite['ID']}))
			{
				for ($i = 0, $intCount = count(${"SITE_USER_GROUPS_".$oneSite['ID']}); $i < $intCount; $i++)
				{
					$groupID = intval(${"SITE_USER_GROUPS_".$oneSite['ID']}[$i]);
					if ($groupID > 0)
					{
						CSaleGroupAccessToSite::Add(
							array(
								"SITE_ID" => $oneSite['ID'],
								"GROUP_ID" => $groupID
							)
						);
					}
				}
			}
		}

		if (isset($_POST['product_reserve_condition']))
		{
			$productReserveCondition = (string)$_POST['product_reserve_condition'];
			if (in_array($productReserveCondition, Sale\Configuration::getReservationConditionList(false)))
				Option::set('sale', 'product_reserve_condition', $productReserveCondition, '');
			unset($productReserveCondition);
		}

		if (isset($_POST['product_reserve_clear_period']))
		{
			$clearPeriod = (int)$_POST['product_reserve_clear_period'];
			if ($clearPeriod >= 0)
				Option::set('sale', 'product_reserve_clear_period', $clearPeriod, '');
			unset($clearPeriod);
		}

		if (isset($_POST['use_sale_discount_only']))
		{
			$useSaleDiscountOnly = (string)$_POST['use_sale_discount_only'];
			if ($useSaleDiscountOnly == 'Y' || $useSaleDiscountOnly == 'N')
				Option::set('sale', 'use_sale_discount_only', $useSaleDiscountOnly, '');
			unset($useSaleDiscountOnly);
		}

		if (isset($_POST['get_discount_percent_from_base_price']))
		{
			$discountPercent = (string)$_REQUEST['get_discount_percent_from_base_price'];
			if ($discountPercent == 'Y' || $discountPercent == 'N')
				Option::set('sale', 'get_discount_percent_from_base_price', $discountPercent, '');
			unset($discountPercent);
		}

		$useSaleDiscountOnly = (string)Option::get('sale', 'use_sale_discount_only');
		if ($useSaleDiscountOnly == 'N')
		{
			if (isset($_POST['discount_apply_mode']))
			{
				$discountModeApply = (int)$_POST['discount_apply_mode'];
				$modeList = Sale\Discount::getApplyModeList(false);
				if (in_array($discountModeApply, $modeList))
					Option::set('sale', 'discount_apply_mode', $discountModeApply, '');
				unset($modeList, $discountModeApply);
			}
		}
		unset($useSaleDiscountOnly);

		Option::set("sale", "regular_archive_active", $_POST['archive_regular_accept']);
		Option::set("sale", "archive_blocked_order", $_POST['archive_blocked_order_accept']);
		if ((int)($_POST['archive_period']))
			$filter['PERIOD'] = (int)($_POST['archive_period']);
		else
			$filter['PERIOD'] = 365;	
		
		if ($_POST['archive_blocked_order_accept'] !== 'Y')
		{
			$filterValues['LOCKED_BY'] = null;
			$filterValues['DATE_LOCK'] = null;
		}

		if (isset($_POST['archive_status_id']))
			$filter["STATUS_ID"] = $_POST['archive_status_id'];

		if (isset($_POST['archive_site']))
			$filter["LID"] = $_POST['archive_site'];

		if($_POST['archive_payed'] <> '')
		{
			$filter["=PAYED"] = $_POST['archive_payed'];
		}

		if($_POST['archive_canceled'] <> '')
		{
			$filter["=CANCELED"] = $_POST['archive_canceled'];
		}

		if($_POST['archive_deducted'] <> '')
		{
			$filter["=DEDUCTED"] = $_POST['archive_deducted'];
		}
		
		if ((int)($_POST['archive_limit']))
			$archiveLimit = (int)$_POST['archive_limit'];
		else
			$archiveLimit = 10;

		Option::set('sale', 'archive_limit', $archiveLimit, '');

		if ((int)($_POST['archive_time_limit']))
			$archiveTimeLimit = (int)$_POST['archive_time_limit'];
		else
			$archiveTimeLimit = 5;

		Option::set('sale', 'archive_time_limit', $archiveTimeLimit, '');

		$filter = serialize($filter);
		Option::set("sale", "archive_params", $filter);

		$agentsList = CAgent::GetList(array("ID"=>"DESC"), array(
			"MODULE_ID" => "sale",
			"NAME" => "\\Bitrix\\Sale\\Archive\\Manager::archiveOnAgent(%",
		));
		while($agent = $agentsList->Fetch())
		{
			CAgent::Delete($agent["ID"]);
		}

		if (isset($_POST['archive_regular_accept']))
		{
			CAgent::AddAgent("\\Bitrix\\Sale\\Archive\\Manager::archiveOnAgent(".$archiveLimit.",".$archiveTimeLimit.");", "sale", "N", 24*60*60, "", "Y");
		}

		Option::set("sale", "order_changes_cleaner_active", $_POST['order_changes_cleaner_active']);
		$orderChangesCleanerDays = (int)$_POST['order_changes_cleaner_days'];
		Option::set("sale", "order_changes_cleaner_days", $orderChangesCleanerDays);
		$orderChangesCleanerLimit = (int)$_POST['order_changes_cleaner_limit'];
		Option::set("sale", "order_changes_cleaner_limit",$orderChangesCleanerLimit);
		$agentsList = CAgent::GetList(array("ID"=>"DESC"), array(
			"MODULE_ID" => "sale",
			"NAME" => "\\Bitrix\\Sale\\OrderHistory::deleteOldAgent(%",
		));
		while($agent = $agentsList->Fetch())
		{
			CAgent::Delete($agent["ID"]);
		}

		if (isset($_POST['order_changes_cleaner_active']))
		{
			CAgent::AddAgent("\\Bitrix\\Sale\\OrderHistory::deleteOldAgent(\"$orderChangesCleanerDays\",\"$orderChangesCleanerLimit\");", "sale", "N", 60, "", "Y");
		}

		ob_start();
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
		ob_end_clean();

		if(isset($_POST['tracking_map_statuses']))
		{
			$mapStatuses = $_POST['tracking_map_statuses'];

			foreach($mapStatuses as $tStatusId => $sStatusId)
				if($sStatusId == '')
					unset($mapStatuses[$tStatusId]);

			Option::set('sale', 'tracking_map_statuses', serialize($mapStatuses));
			unset($mapStatuses);
		}

		$tSwitch = 'N';

		if(isset($_POST['tracking_check_switch']) && $_POST['tracking_check_switch'] == 'Y')
			$tSwitch = 'Y';

		Option::set('sale', 'tracking_check_switch', $tSwitch);

		$tPeriod = 0;

		if(isset($_POST['tracking_check_period']) && intval($_POST['tracking_check_period']) > 0)
			$tPeriod = intval($_POST['tracking_check_period']);

		Option::set('sale', 'tracking_check_period', $tPeriod);

		$agentName = '\Bitrix\Sale\Delivery\Tracking\Manager::startRefreshingStatuses();';

		if($tSwitch == 'Y' && $tPeriod > 0)
		{
			$res = \CAgent::GetList(array(), array('NAME' => $agentName));

			if($agent = $res->Fetch())
			{
				\CAgent::Update($agent['ID'], array('AGENT_INTERVAL' => $tPeriod*60*60));
			}
			else
			{
				\CAgent::AddAgent(
					$agentName,
					'sale',
					"Y",
					$tPeriod*60*60,
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

		COption::SetOptionString("sale", "check_type_on_pay", $CHECK_TYPE_ON_PAY ?: 'sell');
		COption::SetOptionInt("sale", "basket_refresh_gap", $BASKET_REFRESH_GAP);
		COption::SetOptionString("sale", "allow_pay_status", $ALLOW_PAY_STATUS);
		COption::SetOptionString("sale", "allow_guest_order_view", $ALLOW_GUEST_ORDER_VIEW);
		$ALLOW_GUEST_ORDER_VIEW_PATH = is_array($ALLOW_GUEST_ORDER_VIEW_PATH) ? $ALLOW_GUEST_ORDER_VIEW_PATH : array();
		COption::SetOptionString("sale", "allow_guest_order_view_paths", serialize($ALLOW_GUEST_ORDER_VIEW_PATH));
		$ALLOW_GUEST_ORDER_VIEW_STATUS = is_array($ALLOW_GUEST_ORDER_VIEW_STATUS) ? $ALLOW_GUEST_ORDER_VIEW_STATUS : array();
		COption::SetOptionString("sale", "allow_guest_order_view_status", serialize($ALLOW_GUEST_ORDER_VIEW_STATUS));
	}
}

$statusesWithoutNoChange = array();
$arStatuses = array("" => GetMessage("SMO_STATUS"));
$dbStatus = CSaleStatus::GetList(Array("SORT" => "ASC"), Array("LID" => LANGUAGE_ID), false, false, Array("ID", "NAME", "SORT"));
while ($arStatus = $dbStatus->GetNext())
{
	$arStatuses[$arStatus["ID"]] = "[".$arStatus["ID"]."] ".$arStatus["NAME"];
	$statusesWithoutNoChange[$arStatus["ID"]] = "[".$arStatus["ID"]."] ".$arStatus["NAME"];
}

$delieryStatuses = array("" => GetMessage("SMO_STATUS"));
$delieryStatusesList = Sale\DeliveryStatus::getAllStatusesNames();
if (!empty($delieryStatusesList) && is_array($delieryStatusesList))
{
	foreach ($delieryStatusesList as $statusId => $statusName)
	{
		$delieryStatuses[$statusId] = "[".$statusId."] ".htmlspecialcharsbx($statusName);
	}
}


if($strWarning != '')
	CAdminMessage::ShowMessage($strWarning);
elseif ($bWasUpdated)
{
	if($Update <> '' && $_REQUEST["back_url_settings"] <> '')
		LocalRedirect($_REQUEST["back_url_settings"]);
	else
		LocalRedirect($APPLICATION->GetCurPage()."?mid=".$module_id."&lang=".LANGUAGE_ID."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
}

$currentSettings = array();
$currentSettings['use_sale_discount_only'] = Option::get('sale', 'use_sale_discount_only');
$currentSettings['get_discount_percent_from_base_price'] = Option::get('sale', 'get_discount_percent_from_base_price');
$currentSettings['discount_apply_mode'] = (int)Option::get('sale', 'discount_apply_mode');
$currentSettings['product_reserve_condition'] = (string)Option::get('sale', 'product_reserve_condition');
$currentSettings['product_reserve_clear_period'] = (int)Option::get('sale', 'product_reserve_clear_period');
$currentSettings['tracking_map_statuses'] = unserialize(Option::get('sale', 'tracking_map_statuses', ''), ['allowed_classes' => false]);
$currentSettings['tracking_check_switch'] = Option::get('sale', 'tracking_check_switch', 'N');
$currentSettings['tracking_check_period'] = (int)Option::get('sale', 'tracking_check_period', '24');

$tabControl->Begin();
?><form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=$module_id?>&lang=<?=LANGUAGE_ID?>" name="opt_form">
<?=bitrix_sessid_post();
$tabControl->BeginNextTab();
?>
<tr class="heading">
	<td colspan="2"><?=GetMessage("SALE_SERVICE_AREA")?></td>
</tr>
<?
	for ($i = 0, $intCount = count($arAllOptions); $i < $intCount; $i++):
		if(empty($arAllOptions[$i]))
			continue;
		$Option = $arAllOptions[$i];
		$val = COption::GetOptionString("sale", $Option[0], $Option[2]);

		// hide setting for users, who doesn't use it
		if ($Option[0] === 'QUANTITY_FACTORIAL' && $val === 'N')
		{
			continue;
		}

		$type = $Option[3];

		if ($Option[0]=="assist_LOGIN" || $Option[0]=="assist_PASSWORD")
		{
			if ($SALE_RIGHT!="W") $val = "........";
		}
		?>
		<tr>
			<td width="40%"><?	if($type[0]=="checkbox")
							echo "<label for=\"".htmlspecialcharsbx($Option[0])."\">".$Option[1]."</label>";
						else
							echo $Option[1];?></td>
			<td width="60%">

					<?if($type[0]=="checkbox"):?>
						<input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0])?>" id="<?echo htmlspecialcharsbx($Option[0])?>" value="Y"<?if($val=="Y")echo" checked";?>>
					<?elseif($type[0]=="text"):?>
						<input type="text" size="<?echo $type[1]?>" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0])?>">
					<?elseif($type[0]=="textarea"):?>
						<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0])?>"><?echo htmlspecialcharsbx($val)?></textarea>
					<?endif?>

			</td>
		</tr>
	<?endfor;?>
	<tr>
		<td>
			<?echo GetMessage("SMO_FORMAT_QUANTITY_TITLE")?>:
		</td>
		<td>
			<?
			$val = Main\Config\Option::get("sale", "format_quantity", "AUTO");
			?>
			<select name="FORMAT_QUANTITY">
				<option value="AUTO"<?if ($val == "AUTO") echo " selected";?>><?= GetMessage("SMO_FORMAT_QUANTITY_AUTO") ?></option>
				<option value="2"<?if ($val == "2") echo " selected";?>><?= GetMessage("SMO_FORMAT_QUANTITY_2") ?></option>
				<option value="3"<?if ($val == "3") echo " selected";?>><?= GetMessage("SMO_FORMAT_QUANTITY_3") ?></option>
				<option value="4"<?if ($val == "4") echo " selected";?>><?= GetMessage("SMO_FORMAT_QUANTITY_4") ?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<?echo GetMessage("SMO_VALUE_PRECISION_TITLE")?>:
		</td>
		<td>
			<?
			$val = Main\Config\Option::get("sale", "value_precision", 2);
			?>
			<select name="VALUE_PRECISION">
				<option value="0"<?if ($val == "0") echo " selected";?>><?= GetMessage("SMO_VALUE_PRECISION_0") ?></option>
				<option value="1"<?if ($val == "1") echo " selected";?>><?= GetMessage("SMO_VALUE_PRECISION_1") ?></option>
				<option value="2"<?if ($val == "2") echo " selected";?>><?= GetMessage("SMO_VALUE_PRECISION_2") ?></option>
				<option value="3"<?if ($val == "3") echo " selected";?>><?= GetMessage("SMO_VALUE_PRECISION_3") ?></option>
				<option value="4"<?if ($val == "4") echo " selected";?>><?= GetMessage("SMO_VALUE_PRECISION_4") ?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<?echo GetMessage("SALE_DEF_CURR")?>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "default_currency");
			echo CCurrency::SelectBox("CURRENCY_DEFAULT", $val, "", true, "");
			?>
		</td>
	</tr>

	<?
	if(CBXFeatures::IsFeatureEnabled('SaleAffiliate'))
	{
		?>
	<tr>
		<td>
			<?echo GetMessage("SMO_AFFILIATE_PLAN_TYPE")?>:
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "affiliate_plan_type", "N");
			?>
			<select name="affiliate_plan_type">
				<option value="N"<?if ($val == "N") echo " selected";?>><?= GetMessage("SMO_AFFILIATE_PLAN_TYPE_N") ?></option>
				<option value="S"<?if ($val == "S") echo " selected";?>><?= GetMessage("SMO_AFFILIATE_PLAN_TYPE_S") ?></option>
			</select>
		</td>
	</tr>
		<?
	}
	?>

	<tr>
		<td>
			<label for="EXPIRATION_PROCESSING_EVENTS"><?echo GetMessage("SALE_EXPIRATION_PROCESSING_EVENTS")?></label>
		</td>
		<td>
			<?
			$valExpirationProcessingEvents = COption::GetOptionString("sale", "expiration_processing_events", "");
			?>
			<input type="checkbox" name="EXPIRATION_PROCESSING_EVENTS" id="EXPIRATION_PROCESSING_EVENTS" value="Y"<?if($valExpirationProcessingEvents == "Y")echo" checked";?>>
		</td>
	</tr>

	<tr>
		<td>
			<label for="ORDER_HISTORY_LOG_LEVEL"><?echo GetMessage("SALE_ORDER_HISTORY_LOG_LEVEL")?></label>
		</td>
		<td>
			<?
			$valOrderHistoryLogLevel = COption::GetOptionString("sale", "order_history_log_level", "");
			?>
			<input type="checkbox" name="ORDER_HISTORY_LOG_LEVEL" id="ORDER_HISTORY_LOG_LEVEL" value="1"<?if($valOrderHistoryLogLevel == "1")echo" checked";?>>
		</td>
	</tr>
	<tr>
		<td>
			<label for="ORDER_HISTORY_ACTION_LOG_LEVEL"><?echo GetMessage("SALE_ORDER_HISTORY_ACTION_LOG_LEVEL")?></label>
		</td>
		<td>
			<?
			$valOrderHistoryActionLogLevel = COption::GetOptionString("sale", "order_history_action_log_level", "");
			?>
			<input type="checkbox" name="ORDER_HISTORY_ACTION_LOG_LEVEL" id="ORDER_HISTORY_ACTION_LOG_LEVEL" value="1"<?if($valOrderHistoryActionLogLevel == "1")echo" checked";?>>
		</td>
	</tr>

	<tr>
		<td valign="top">
			<?echo GetMessage("SALE_IS_SHOP")?>
		</td>
		<td>
			<select name="SHOP_SITE[]" multiple size="5">
			<?
			foreach($siteList as $key => $val)
			{
				$site = COption::GetOptionString("sale", "SHOP_SITE_".$val["ID"], "");
				?><option value="<?=$val["ID"]?>" <? if ($site == $val["ID"]) echo "selected";  ?>    ><? echo htmlspecialcharsEx($val["NAME"])." (".htmlspecialcharsEx($val["ID"]).")";?></option><?
			}
			?>
			</select>
		</td>
	</tr>

	<!-- ps success and fail paths -->
	<tr>
		<td>
			<?echo GetMessage("SALE_PS_SUCCESS_PATH")?>
		</td>
		<td>
			<input type="text" size="40" value="<?=htmlspecialcharsbx(COption::GetOptionString("sale", "sale_ps_success_path", ""))?>" name="sale_ps_success_path">
		</td>
	</tr>
	<tr>
		<td>
			<?echo GetMessage("SALE_PS_FAIL_PATH")?>
		</td>
		<td>
			<input type="text" size="40" value="<?=htmlspecialcharsbx(COption::GetOptionString("sale", "sale_ps_fail_path", ""))?>" name="sale_ps_fail_path">
		</td>
	</tr>
	<tr>
		<td>
			<?=Main\Localization\Loc::getMessage("SALE_ALLOW_PAY_STATUS")?>:
		</td>
		<td>
			<?
			$val = \Bitrix\Main\Config\Option::get("sale", "allow_pay_status", Sale\OrderStatus::getInitialStatus());
			?>
			<select name="ALLOW_PAY_STATUS">
				<?
				foreach($statusesWithoutNoChange as $statusID => $statusName)
				{
					?><option value="<?=$statusID?>"<?if ($val == $statusID) echo " selected";?>><?=htmlspecialcharsbx($statusName)?></option><?
				}
				?>
			</select>
		</td>
	</tr>
	<!-- start of check default type -->
	<? if (Cashbox\Manager::isSupportedFFD105()) :?>
		<tr class="heading" id="check_default_type_block">
			<td colspan="2"><?=Main\Localization\Loc::getMessage('SALE_BLOCK_CHECK_TITLE')?></td>
		</tr>
		<tr>
			<td><?=Main\Localization\Loc::getMessage("SALE_CHECK_TYPE_ON_PAY")?>:</td>
			<td>
				<?
					$val = Main\Config\Option::get("sale", "check_type_on_pay", "sell");
				?>
				<select name="CHECK_TYPE_ON_PAY">
					<option value="sell" <?=($val === 'sell') ? 'selected': '';?>><?=Loc::getMessage('SALE_CHECK_TYPE_ON_PAY_SELL')?></option>
					<option value="prepayment" <?=($val === 'prepayment') ? 'selected': '';?>><?=Loc::getMessage('SALE_CHECK_TYPE_ON_PAY_PREPAYMENT')?></option>
					<option value="advance" <?=($val === 'advance') ? 'selected': '';?>><?=Loc::getMessage('SALE_CHECK_TYPE_ON_PAY_ADVANCE')?></option>
				</select>
			</td>
		</tr>
	<?endif;?>
	<!-- start of basket behavior in public -->
	<tr class="heading" id="basket_public_behavior_block">
		<td colspan="2"><?=Main\Localization\Loc::getMessage('SALE_BASKET_PUBLIC_BEHAVIOR_TITLE')?></td>
	</tr>
	<tr>
		<td><?=Main\Localization\Loc::getMessage("SALE_BASKET_REFRESH_GAP")?>:</td>
		<td>
			<?
			$refreshGapVal = (int)Main\Config\Option::get("sale", "basket_refresh_gap", 0);
			?>
			<input type="text" size="10" value="<?=$refreshGapVal?>" name="BASKET_REFRESH_GAP" id="basket_refresh_gap">
		</td>
	</tr>
	<? if ($currentSettings['use_sale_discount_only'] !== 'Y'): ?>
		<tr id="basket_refresh_gap_warning" <?=($refreshGapVal === 0 ? 'style="display: none;"' : '')?>>
			<td colspan="2" align="center">
				<div class="adm-info-message-wrap">
					<div class="adm-info-message">
						<div><?=GetMessage("SALE_BASKET_REFRESH_GAP_WARNING")?></div>
					</div>
				</div>
			</td>
		</tr>
		<script>
			BX.bind(BX('basket_refresh_gap'), 'change', function(event){
				var target = BX.getEventTarget(event);
				var warning = BX('basket_refresh_gap_warning');

				if (BX.type.isDomNode(target) && BX.type.isDomNode(warning))
				{
					warning.style.display = parseInt(target.value) === 0 ? 'none' : '';
				}
			});
		</script>
	<? endif; ?>
	<!-- start of order guest view -->
	<tr class="heading" id="guest_order_view_block">
		<td colspan="2"><a name="section_guest_order_view"></a><?=GetMessage('SALE_ALLOW_GUEST_ORDER_VIEW_TITLE')?></td>
	</tr>
	<tr>
		<td>
			<?=Main\Localization\Loc::getMessage("SALE_ALLOW_GUEST_ORDER_VIEW")?>:
		</td>
		<td>
			<?
			$val = \Bitrix\Main\Config\Option::get("sale", "allow_guest_order_view", 'N');
			?>
			<input type="checkbox" value="Y" name="ALLOW_GUEST_ORDER_VIEW" <?=(($val == 'N' || empty($val)) ?: "checked")?> onChange="showAllowGuestOrderViewPaths(this)">
		</td>
	</tr>
	<tr class="sale_allow_guest_order_view" <?=($val === "Y") ? "" : "style=\"display:none\""?>>
		<td valign="top">
			<?= Main\Localization\Loc::getMessage("SALE_ORDER_GUEST_VIEW_STATUS")?>
		</td>
		<td>
			<?
			$guestStatuses = \Bitrix\Main\Config\Option::get("sale", "allow_guest_order_view_status", "");
			$guestStatuses = ($guestStatuses <> '') ?  unserialize($guestStatuses, ['allowed_classes' => false]) : array();
			$statusList = (array_slice($arStatuses,1));
			?>

			<select name="ALLOW_GUEST_ORDER_VIEW_STATUS[]" multiple size="3">
				<?foreach($statusList as $id => $name):?>
					<option value="<?=$id?>" <?=(in_array($id, $guestStatuses) ? "selected" : "")?>>
						<?=htmlspecialcharsbx($name)?>
					</option>
				<?endforeach?>
			</select>
		</td>
	</tr>
	<?
	$paths = unserialize(\Bitrix\Main\Config\Option::get("sale", "allow_guest_order_view_paths"), ['allowed_classes' => false]);
	foreach($siteList as $site)
	{
		?>
		<tr class="sale_allow_guest_order_view" <?=($val === "Y") ? "" : "style=\"display:none\""?>>
			<td>
				<?=Main\Localization\Loc::getMessage("SALE_ALLOW_GUEST_ORDER_VIEW_PATH", array("#SITE_ID#" => $site["ID"]))?>:
			</td>
			<td>
				<input type="text" size="40" value="<?=htmlspecialcharsbx($paths[$site["ID"]])?>" name="ALLOW_GUEST_ORDER_VIEW_PATH[<?=htmlspecialcharsbx($site['ID'])?>]">
			</td>
		</tr>
		<?
	}
	?>
	<tr class="sale_allow_guest_order_view" <?=($val === "Y") ? "" : "style=\"display:none\""?>>
		<td>
			<?=Main\Localization\Loc::getMessage("SALE_ALLOW_GUEST_ORDER_VIEW_EXAMPLE")?>:
		</td>
		<td>
			/personal/orders/#order_id#
		</td>
	</tr>
	<!-- end of order guest view -->
	<tr class="heading">
		<td colspan="2"><a name="section_reservation"></a><?=GetMessage('BX_SALE_SETTINGS_SECTION_RESERVATION')?></td>
	</tr>
	<tr>
		<td width="40%"><? echo GetMessage('BX_SALE_SETTINGS_OPTION_PRODUCT_RESERVE_CONDITION'); ?></td>
		<td width="60%"><select name="product_reserve_condition">
			<?
			foreach (Sale\Configuration::getReservationConditionList(true) as $reserveId => $reserveTitle)
			{
				?><option value="<? echo $reserveId; ?>"<?
					echo ($reserveId == $currentSettings['product_reserve_condition'] ? ' selected' : '')
				?>><? echo htmlspecialcharsex($reserveTitle); ?></option>
				<?
			}
			unset($reserveId, $reserveTitle);
			?>
		</select></td>
	</tr>
	<tr>
		<td width="40%"><? echo GetMessage('BX_SALE_SETTINGS_OPTION_PRODUCT_RESERVE_CLEAR_PERIOD'); ?></td>
		<td width="60%">
			<input type="text" name="product_reserve_clear_period" value="<? echo $currentSettings['product_reserve_clear_period']; ?>">
		</td>
	</tr>
	<tr class="heading">
		<td colspan="2"><?=GetMessage('BX_SALE_SETTINGS_SECTION_LOCATIONS')?></td>
	</tr>
	<tr>
		<td>
			<?echo GetMessage("SALE_LOCATION_WIDGET_APPEARANCE")?>:
		</td>
		<td>
			<?$isSearch = Bitrix\Sale\Location\Admin\Helper::getWidgetAppearance() == 'search';?>
			<select name="sale_location_selector_appearance">
				<option <?if(!$isSearch):?>selected<?endif?> value="steps"><?=GetMessage('SALE_LOCATION_SELECTOR_APPEARANCE_STEPS')?></option>
				<option <?if($isSearch):?>selected<?endif?> value="search"><?=GetMessage('SALE_LOCATION_SELECTOR_APPEARANCE_SEARCH')?></option>
			</select>
		</td>
	</tr>

	<tr class="heading">
		<td colspan="2"><a name="section_discount"></a><?=GetMessage('BX_SALE_SETTINGS_SECTION_DISCOUNT')?></td>
	</tr>
	<tr>
		<td width="40%"><? echo GetMessage('BX_SALE_SETTINGS_OPTION_USE_SALE_DISCOUNT_ONLY'); ?></td>
		<td width="60%">
			<input type="hidden" name="use_sale_discount_only" id="use_sale_discount_only_N" value="N">
			<input type="checkbox" name="use_sale_discount_only" id="use_sale_discount_only_Y" value="Y"<? echo ($currentSettings['use_sale_discount_only'] == 'Y' ? ' checked' : ''); ?>>
		</td>
	</tr>
	<script>
		BX.bind(BX('use_sale_discount_only_Y'), 'change', function(event){
			var target = BX.getEventTarget(event);
			var warning = BX('use_sale_discount_only_warning');

			if (BX.type.isDomNode(target) && BX.type.isDomNode(warning))
			{
				warning.style.display = target.checked ? 'none' : '';
			}
		});
	</script>
	<tr id="use_sale_discount_only_warning" <?=($currentSettings['use_sale_discount_only'] === 'Y' || $refreshGapVal === 0 ? 'style="display: none;"' : '')?>>
		<td colspan="2" align="center">
			<div class="adm-info-message-wrap">
				<div class="adm-info-message">
					<div><?=GetMessage("SALE_USE_SALE_DISCOUNT_ONLY_WARNING")?></div>
				</div>
			</div>
		</td>
	</tr>
	<tr>
		<td width="40%"><? echo GetMessage('BX_SALE_SETTINGS_OPTION_PERCENT_FROM_BASE_PRICE'); ?></td>
		<td width="60%">
			<input type="hidden" name="get_discount_percent_from_base_price" id="get_discount_percent_from_base_price_N" value="N">
			<input type="checkbox" name="get_discount_percent_from_base_price" id="get_discount_percent_from_base_price_Y" value="Y"<? echo ($currentSettings['get_discount_percent_from_base_price'] == 'Y' ? ' checked' : ''); ?>>
		</td>
	</tr>
	<tr id="tr_discount_apply_mode" style="display: <?=($currentSettings['use_sale_discount_only'] == 'Y' ? 'none' : 'table-row'); ?>">
		<td width="40%"><? echo GetMessage('BX_SALE_SETTINGS_OPTION_DISCOUNT_APPLY_MODE'); ?></td>
		<td width="60%">
			<select name="discount_apply_mode" style="max-width: 300px;">
			<?
			$modeList = Sale\Discount::getApplyModeList(true);
			foreach ($modeList as $modeId => $modeTitle)
			{
				?><option value="<?=$modeId; ?>"<?=($modeId == $currentSettings['discount_apply_mode'] ? ' selected' : ''); ?>><?=htmlspecialcharsbx($modeTitle); ?></option><?
			}
			unset($modeTitle, $modeId, $modeList);
			?>
			</select>
		</td>
	</tr>

	<!-- Recommended products -->
	<tr class="heading">
		<td colspan="2"><?=GetMessage("SALE_P2P")?></td>
	</tr>
	<tr>
		<td align="right" width="40%">
			<label for="p2p_allow_collect_data"><?=GetMessage("SALE_P2P_COLLECT_DATA")?></label>
		</td>
		<td width="60%">
			<input type="checkbox" name="SALE_P2P_ALLOW_COLLECT_DATA" value="Y" id="p2p_allow_collect_data"<? echo (Option::get("sale", "p2p_allow_collect_data", "N") == 'Y' ? ' checked' : ''); ?>>
		</td>
	</tr>
	<tr>
		<td valign="top">
			<?echo GetMessage("SALE_P2P_STATUS_LIST")?>
		</td>
		<td>
			<?
			$recStatuses = COption::GetOptionString("sale", "p2p_status_list", "");
			if($recStatuses <> '')
				$recStatuses = unserialize($recStatuses, ['allowed_classes' => false]);
			else
				$recStatuses = array();

			if(!$recStatuses)
				$recStatuses = array();

			$p2pStatusesList = array_slice($arStatuses, 1);
			$p2pStatusesList = array_merge($p2pStatusesList, array(
				"F_CANCELED" => GetMessage("F_CANCELED"),
				"F_DELIVERY" => GetMessage("F_DELIVERY"),
				"F_PAY" => GetMessage("F_PAY"),
				"F_OUT" => GetMessage("F_OUT"),
			));
			?>

			<select name="SALE_P2P_STATUS_LIST[]" multiple size="5">
				<?foreach($p2pStatusesList as $id => $name):?>
					<option value="<?=$id?>" <?=(in_array($id, $recStatuses) ? "selected" : "")?>>
						<?=htmlspecialcharsEx($name)?>
					</option>
				<?endforeach?>
			</select>
		</td>
	</tr>

	<tr>
		<td>
			<?echo GetMessage("SALE_P2P_STATUS_PERIOD")?>
		</td>
		<td>
			<input type="text" size="5" value="<?=htmlspecialcharsbx(COption::GetOptionString("sale", "p2p_del_period", "10"))?>" name="p2p_del_period">
		</td>
	</tr>

	<tr>
		<td>
			<?echo GetMessage("SALE_P2P_EXP_DATE")?>
		</td>
		<td>
			<input type="text" size="5" value="<?=htmlspecialcharsbx(COption::GetOptionString("sale", "p2p_del_exp", "10"))?>" name="p2p_del_exp">
		</td>
	</tr>

	<!-- Order history cleaner -->
	<tr class="heading">
		<td colspan="2"><?=GetMessage("SALE_ORDER_HISTORY_CLEANER_TITLE")?></td>
	</tr>
	<tr>
		<td align="right" width="40%">
			<label for="order_changes_cleaner_active"><?=GetMessage("SALE_ORDER_HISTORY_CLEANER_SWITCHER")?></label>
		</td>
		<td width="60%">
			<input type="checkbox" name="order_changes_cleaner_active" value="Y" id="order_changes_cleaner_active"<? echo (Option::get("sale", "order_changes_cleaner_active", "N") == 'Y' ? ' checked' : ''); ?>>
		</td>
	</tr>

	<tr>
		<td>
			<?echo GetMessage("SALE_ORDER_HISTORY_CLEANER_DAYS")?>
		</td>
		<td>
			<input type="text" size="5" value="<?=(int)(Option::get("sale", "order_changes_cleaner_days", "365"))?>" name="order_changes_cleaner_days">
		</td>
	</tr>

	<tr>
		<td>
			<?echo GetMessage("SALE_ORDER_HISTORY_CLEANER_BY_HIT")?>
		</td>
		<td>
			<input type="text" size="5" value="<?=(int)(Option::get("sale", "order_changes_cleaner_limit", "10000"))?>" name="order_changes_cleaner_limit">
		</td>
	</tr>
	<!-- /Recommended products -->
	<?
	if (CBXFeatures::IsFeatureEnabled('SaleAccounts'))
	{
		?>
	<tr class="heading">
		<td colspan="2"><?=GetMessage("SALE_AMOUNT_NAME")?></td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<table cellspacing="0" cellpadding="0" border="0" class="internal">
				<tr class="heading">
					<td valign="top">
						<?echo GetMessage("SALE_AMOUNT_VAL")?>
					</td>
					<td valign="top">
						<?echo GetMessage("SALE_AMOUNT_CURRENCY")?>
					</td>
				</tr>
				<?
				$val = COption::GetOptionString("sale", "pay_amount", 'a:4:{i:1;a:2:{s:6:"AMOUNT";s:2:"10";s:8:"CURRENCY";s:3:"EUR";}i:2;a:2:{s:6:"AMOUNT";s:2:"20";s:8:"CURRENCY";s:3:"EUR";}i:3;a:2:{s:6:"AMOUNT";s:2:"30";s:8:"CURRENCY";s:3:"EUR";}i:4;a:2:{s:6:"AMOUNT";s:2:"40";s:8:"CURRENCY";s:3:"EUR";}}');
				$key = 0;
				if($val <> '')
				{
					$arAmount = unserialize($val, ['allowed_classes' => false]);
					foreach($arAmount as $key => $val)
					{
						?>
						<tr>
							<td><input type="text" name="amount_val[<?=$key?>]" value="<?=$val["AMOUNT"]?>"></td>
							<td><?=CCurrency::SelectBox("amount_currency[".$key."]", $val["CURRENCY"], "", True, "")?></td>
						</tr>
						<?
					}
				}
				if ((int)$key <= 0)
					$key = 0;
				?>
				<tr>
					<td><input type="text" name="amount_val[<?=++$key?>]" value=""></td>
					<td><?=CCurrency::SelectBox("amount_currency[".$key."]", $val["CURRENCY"], "", true, "")?></td>
				</tr>
				<tr>
					<td><input type="text" name="amount_val[<?=++$key?>]" value=""></td>
					<td><?=CCurrency::SelectBox("amount_currency[".$key."]", $val["CURRENCY"], "", true, "")?></td>
				</tr>
				<tr>
					<td><input type="text" name="amount_val[<?=++$key?>]" value=""></td>
					<td><?=CCurrency::SelectBox("amount_currency[".$key."]", $val["CURRENCY"], "", true, "")?></td>
				</tr>

			</table>
		</td>
	</tr>
		<?
	}
	?>
	<tr>
		<td colspan="2">
			<?
			$reminder = COption::GetOptionString("sale", "pay_reminder", "");
			$arReminder = unserialize($reminder, ['allowed_classes' => false]);

			$arSubscribeProd = array();
			$subscribeProd = COption::GetOptionString("sale", "subscribe_prod", "");
			if ($subscribeProd <> '')
				$arSubscribeProd = unserialize($subscribeProd, ['allowed_classes' => false]);

			$aTabs2 = Array();
			foreach($siteList as $val)
			{
				$aTabs2[] = Array("DIV"=>"reminder".$val["ID"], "TAB" => "[".$val["ID"]."] ".htmlspecialcharsbx($val["NAME"]), "TITLE" => "[".htmlspecialcharsbx($val["ID"])."] ".htmlspecialcharsbx($val["NAME"]));
			}
			$tabControl2 = new CAdminViewTabControl("tabControl2", $aTabs2);
			$tabControl2->Begin();
			foreach($siteList as $val)
			{
				$arStores = array();
				if (CModule::IncludeModule("catalog"))
				{
					$dbStore = CCatalogStore::GetList(array("SORT" => "DESC", "ID" => "ASC"), array("ACTIVE" => "Y", "SHIPPING_CENTER" => "Y", "+SITE_ID" => $val["ID"]));
					while ($arStore = $dbStore->GetNext())
						$arStores[] = $arStore;
				}

				$tabControl2->BeginNextTab();
				?>
				<table cellspacing="5" cellpadding="0" border="0" width="100%" align="center">

					<!-- default store -->
					<?
					$deductStore = COption::GetOptionString("sale", "deduct_store_id", "", $val["ID"]);

					$display = (count($arStores) > 1 && $valDeductOnDelivery == "Y") ? "table-row" : "none";
					?>
					<tr class="default_deduct_store_control" style="display:<?=$display?>" id="default_deduct_store_control_<?=$val["ID"]?>">
						<td align="right" width="40%"><?=GetMessage("SALE_DEDUCT_STORE")?></td>
						<td width="60%">
							<select name="defaultDeductStore[<?=$val["ID"]?>][id]" id="default_store_select_<?=$val["ID"]?>">
								<?
								foreach ($arStores as $storeId => $arStore):
								?>
									<option value="<?=$arStore["ID"]?>" <? if ($deductStore == $arStore["ID"]) echo "selected";  ?>><?=$arStore["TITLE"]." [".htmlspecialcharsEx($arStore["ID"])."]";?></option>
								<?
								endforeach;
								?>
							</select>
							<input type="hidden" id="default_store_select_save_<?=$val["ID"]?>" name="defaultDeductStore[<?=$val["ID"]?>][save]" value="<?=(count($arStores) > 1 && $valDeductOnDelivery == "Y") ? "Y" : "N"?>" />
						</td>
					</tr>
					<!-- end of default store -->

					<tr class="heading">
						<td colspan="2"><?=GetMessage("SMO_PRODUCT_SUBSCRIBE")?></td>
					</tr>
					<tr>
						<td align="right" width="40%"><label for="notify-<?=$val["ID"]?>"><?=GetMessage("SALE_NOTIFY_PRODUCT_USE")?></label></td>
						<td width="60%"><input type="checkbox" name="subscribProd[<?=$val["ID"]?>][use]" value="Y" id="notify-<?=$val["ID"]?>"<?if($arSubscribeProd[$val["ID"]]["use"] == "Y") echo " checked";?>></td>
					</tr>
					<tr>
						<td align="right"><?=GetMessage("SALE_NOTIFY_PRODUCT")?></td>
						<td><input type="text" name="subscribProd[<?=$val["ID"]?>][del_after]" value="<?=intval($arSubscribeProd[$val["ID"]]["del_after"])?>" size="5" id="del-after-<?=$val["ID"]?>"></td>
					</tr>
					<tr class="heading">
						<td colspan="2"><?=GetMessage("SMO_ORDER_PAY_REMINDER")?></td>
					</tr>
					<tr>
						<td align="right" width="40%"><label for="use-<?=$val["ID"]?>"><?=GetMessage("SMO_ORDER_PAY_REMINDER_USE")?>:</label></td>
						<td width="60%"><input type="checkbox" name="reminder[<?=$val["ID"]?>][use]" value="Y" id="use-<?=$val["ID"]?>"<?if($arReminder[$val["ID"]]["use"] == "Y") echo " checked";?>></td>
					</tr>
					<tr>
						<td align="right"><label for="after-<?=$val["ID"]?>"><?=GetMessage("SMO_ORDER_PAY_REMINDER_AFTER")?>:</label></td>
						<td><input type="text" name="reminder[<?=$val["ID"]?>][after]" value="<?=intval($arReminder[$val["ID"]]["after"])?>" size="5" id="after-<?=$val["ID"]?>"></td>
					</tr>
					<tr>
						<td align="right"><label for="frequency-<?=$val["ID"]?>"><?=GetMessage("SMO_ORDER_PAY_REMINDER_FREQUENCY")?>:</label></td>
						<td><input type="text" name="reminder[<?=$val["ID"]?>][frequency]" value="<?=intval($arReminder[$val["ID"]]["frequency"])?>" size="5" id="frequency-<?=$val["ID"]?>"></td>
					</tr>
					<tr>
						<td align="right"><label for="period-<?=$val["ID"]?>"><?=GetMessage("SMO_ORDER_PAY_REMINDER_PERIOD")?>:</label></td>
						<td><input type="text" name="reminder[<?=$val["ID"]?>][period]" value="<?=intval($arReminder[$val["ID"]]["period"])?>" size="5" id="period-<?=$val["ID"]?>"></td>
					</tr>
				</table>
				<?
			}
			$tabControl2->End();
			?>
		</td>
	</tr>

	<?$tabControl->BeginNextTab();?>
<script type="text/javascript">
var cur_site = {WEIGHT:'<?=CUtil::JSEscape($siteList[0]["ID"])?>',ADDRESS:'<?=CUtil::JSEscape($siteList[0]["ID"])?>'};
function changeSiteList(value, add_id)
{
	var SLHandler = document.getElementById(add_id + '_site_id');
	SLHandler.disabled = value;
}

function changeStoreDeductCondition(value, control_id)
{
	var SLDeductCondition = document.getElementById(control_id);
	SLDeductCondition.disabled = value;
}

function selectSite(current, add_id)
{
	if (current == cur_site[add_id]) return;

	var last_handler = document.getElementById('par_' + add_id + '_' +cur_site[add_id]);
	var current_handler = document.getElementById('par_' + add_id + '_' + current);
	var CSHandler = document.getElementById(add_id + '_current_site');

	last_handler.style.display = 'none';
	current_handler.style.display = 'inline';

	cur_site[add_id] = current;
	CSHandler.value = current;

	return;
}

function setWeightValue(obj)
{
	if (!obj.value) return;

	var selectorUnit = document.forms.opt_form['weight_unit[' + cur_site['WEIGHT'] + ']'];
	var selectorKoef = document.forms.opt_form['weight_koef[' + cur_site['WEIGHT'] + ']'];

	if (selectorKoef && selectorUnit)
	{
		selectorKoef.value = obj.value;
		selectorUnit.value = obj.options[obj.selectedIndex].text;
	}
}

function showAllowGuestOrderViewPaths(target)
{
	var allowPaths = document.getElementsByClassName('sale_allow_guest_order_view');
	for (id in allowPaths)
	{
		if (allowPaths[id] instanceof Node)
		{
			if (target.checked)
			{
				allowPaths[id].style.display = 'table-row';
			}
			else
			{
				allowPaths[id].style.display = 'none';
			}
		}
	}
}

function allowAutoDelivery(value)
{
	var allowDeliveryCheckbox = document.getElementById('PAYED_2_ALLOW_DELIVERY');

	if (value === false) {
		allowDeliveryCheckbox.disabled = true;
		allowDeliveryCheckbox.checked = false;
	} else {
		allowDeliveryCheckbox.disabled = false;
	}
}
</script>
	<tr>
		<td valign="top" width="40%"><?=GetMessage("SMO_PAR_DIF_SETTINGS")?></td>
		<td valign="top" width="60%"><input type="checkbox" name="WEIGHT_dif_settings" id="dif_settings" <? if(COption::GetOptionString($module_id, "WEIGHT_different_set", "N") == "Y") echo " checked=\"checked\"";?> OnClick="changeSiteList(!this.checked, 'WEIGHT')" /></td>
	</tr>
	<tr>
		<td><?=GetMessage("SMO_PAR_SITE_LIST")?></td>
		<td><select name="site" id="WEIGHT_site_id"<? if(COption::GetOptionString($module_id, "WEIGHT_different_set", "N") != "Y") echo " disabled=\"disabled\""; ?> OnChange="selectSite(this.value, 'WEIGHT')">
			<?
				for($i = 0; $i < $siteCount; $i++)
					echo "<option value=\"".htmlspecialcharsbx($siteList[$i]["ID"])."\">".htmlspecialcharsbx($siteList[$i]["NAME"])."</option>";
			?></select><input type="hidden" name="WEIGHT_current_site" id="WEIGHT_current_site" value="<?=htmlspecialcharsbx($siteList[0]["ID"]);?>" /></td>
	</tr>
	<tr>
		<td valign="top" colspan="2">
	<?for ($i = 0; $i < $siteCount; $i++):?>
			<div id="par_WEIGHT_<?=($siteList[$i]["ID"])?>" style="display: <?=($i == 0 ? "inline" : "none");?>">
			<table cellpadding="0" cellspacing="2" class="adm-detail-content-table edit-table">
			<tr class="heading">
				<td align="center" colspan="2"><?echo GetMessage("SMO_PAR_SITE_PARAMETERS")?></td>
			</tr>
			<tr>
				<td width="40%" class="adm-detail-content-cell-l"><?echo GetMessage("SMO_PAR_SITE_WEIGHT_UNIT_SALE")?></td>
				<td width="60%" class="adm-detail-content-cell-r"><select name="weight_unit_tmp[<?=htmlspecialcharsbx($siteList[$i]["ID"])?>]" OnChange="setWeightValue(this)">
						<?
					$arUnitList = CSaleMeasure::GetList("W");
					foreach ($arUnitList as $key => $arM)
					{
						$selectedWeightUnit = COption::GetOptionString($module_id, "weight_unit", GetMessage('SMO_PAR_WEIGHT_UNIT_GRAMM'), trim($siteList[$i]["ID"]));
						?>
						<option value="<?=floatval($arM["KOEF"])?>" <?=($selectedWeightUnit == $arM["NAME"]?"selected":"")?>><?=htmlspecialcharsbx($arM["NAME"])?></option>
						<?
					}

				?></select></td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?=GetMessage('SMO_PAR_WEIGHT_UNIT')?></td>
				<td class="adm-detail-content-cell-r"><input type="text" name="weight_unit[<?=htmlspecialcharsbx($siteList[$i]["ID"])?>]" size="5" value="<?=htmlspecialcharsbx(COption::GetOptionString($module_id, "weight_unit", GetMessage('SMO_PAR_WEIGHT_UNIT_GRAMM'), $siteList[$i]["ID"]))?>" /></td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?=GetMessage('SMO_PAR_WEIGHT_KOEF')?></td>
				<td class="adm-detail-content-cell-r"><input type="text" name="weight_koef[<?=htmlspecialcharsbx($siteList[$i]["ID"])?>]" size="5" value="<?=htmlspecialcharsbx(COption::GetOptionString($module_id, "weight_koef", "1", $siteList[$i]["ID"]))?>" /></td>
			</tr>
			</table>
			</div>
	<?endfor;?>
		</td>
	</tr>

<?$tabControl->BeginNextTab();?>
	<tr>
		<td width="40%"><?=GetMessage("SMO_DIF_SETTINGS")?></td>
		<td width="60%"><input type="checkbox" name="ADDRESS_dif_settings" id="ADDRESS_dif_settings"<? if(COption::GetOptionString($module_id, "ADDRESS_different_set", "N") != "N") echo " checked=\"checked\"";?> OnClick="changeSiteList(!this.checked, 'ADDRESS')" /></td>
	</tr>
	<tr>
		<td><?=GetMessage("SMO_SITE_LIST")?></td>
		<td><select name="site" id="ADDRESS_site_id"<? if(COption::GetOptionString($module_id, "ADDRESS_different_set", "N") != "Y") echo " disabled=\"disabled\""; ?> onChange="selectSite(this.value, 'ADDRESS')">
			<?
				for($i = 0; $i < $siteCount; $i++)
					echo "<option value=\"".htmlspecialcharsbx($siteList[$i]["ID"])."\">".htmlspecialcharsbx($siteList[$i]["NAME"])."</option>";
			?></select><input type="hidden" name="ADDRESS_current_site" id="ADDRESS_current_site" value="<?=htmlspecialcharsbx($siteList[0]["ID"]);?>" /></td>
	</tr>
	<tr>
		<td colspan="2" valign="top">
<?
for ($i = 0; $i < $siteCount; $i++):
	$location_zip = COption::GetOptionString('sale', 'location_zip', '', $siteList[$i]["ID"]);
	$location = COption::GetOptionString('sale', 'location', '', $siteList[$i]["ID"]);

	if(!$lMigrated)
	{
		$sales_zone_countries = SalesZone::getCountriesIds($siteList[$i]["ID"]);
		$sales_zone_regions = SalesZone::getRegionsIds($siteList[$i]["ID"]);
		$sales_zone_cities = SalesZone::getCitiesIds($siteList[$i]["ID"]);
	}

	if ($location_zip == 0) $location_zip = '';
?>
		<div  id="par_ADDRESS_<?=htmlspecialcharsbx($siteList[$i]["ID"])?>" style="display: <?=($i == 0 ? "inline" : "none");?>">
		<table cellpadding="0" cellspacing="2" border="0" width="60%" align="center">
			<tr class="heading">
				<td align="center" colspan="2"><?echo GetMessage("SMO_PAR_SITE_ADRES")?></td>
			</tr>
			<tr>
				<td width="40%" class="adm-detail-content-cell-l"><?echo GetMessage("SMO_LOCATION_ZIP");?></td>
				<td width="60%" class="adm-detail-content-cell-r"><input type="text" name="location_zip[<?=htmlspecialcharsbx($siteList[$i]["ID"])?>]" value="<?=htmlspecialcharsbx($location_zip)?>" size="5" /></td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?=GetMessage("SMO_LOCATION_SHOP_CITY").":";?></td>
				<td class="adm-detail-content-cell-r">

					<?if($lpEnabled):?>

						<?$APPLICATION->IncludeComponent("bitrix:sale.location.selector.".\Bitrix\Sale\Location\Admin\Helper::getWidgetAppearance(), "", array(
							"ID" => "",
							"CODE" => $location,
							"INPUT_NAME" => "location[".$siteList[$i]["ID"]."]",
							"PROVIDE_LINK_BY" => "code",
							"SHOW_ADMIN_CONTROLS" => 'N',
							"SELECT_WHEN_SINGLE" => 'N',
							"FILTER_BY_SITE" => 'N',
							"SHOW_DEFAULT_LOCATIONS" => 'N',
							"SEARCH_BY_PRIMARY" => 'Y'
							),
							false
						);?>

					<?else:?>

						<select name="location[<?=htmlspecialcharsbx($siteList[$i]["ID"])?>]">
							<option value=''></option>
							<?$dbLocationList = CSaleLocation::GetList(
								Array(
									"COUNTRY_NAME_LANG"=>"ASC",
									"REGION_NAME_LANG"=>"ASC",
									"CITY_NAME_LANG"=>"ASC"
								),
								array(),
								LANGUAGE_ID);
							?>
							<?while ($arLocation = $dbLocationList->GetNext()):
								$locationName = $arLocation["COUNTRY_NAME"];

								if ($arLocation["REGION_NAME"] <> '')
								{
									if ($locationName <> '')
										$locationName .= " - ";
									$locationName .= $arLocation["REGION_NAME"];
								}
								if ($arLocation["CITY_NAME"] <> '')
								{
									if ($locationName <> '')
										$locationName .= " - ";
									$locationName .= $arLocation["CITY_NAME"];
								}
							?>
								<option value="<?=$arLocation["ID"]?>"<?=($location == $arLocation["ID"] ? " selected=\"selected\"" : "")?>> <?echo htmlspecialcharsbx($locationName)?> </option>
							<?endwhile;?>
						</select>

					<?endif?>
				</td>
			</tr>

			<?if(!$lpEnabled):?>

			<tr>
				<td class="adm-detail-content-cell-l" valign="top">
					<?=GetMessage("SMO_LOCATION_SALES_ZONE").":";?>
					<script type="text/javascript">
						BX.ready( function(){
							BX.bind(BX("sales_zone_countries_<?=htmlspecialcharsbx($siteList[$i]["ID"])?>"), 'change', BX.Sale.Options.onCountrySelect);
							BX.bind(BX("sales_zone_regions_<?=htmlspecialcharsbx($siteList[$i]["ID"])?>"), 'change', BX.Sale.Options.onRegionSelect);
						});
					</script>
				</td>
				<td class="adm-detail-content-cell-r">

					<?if($lpEnabled):?>

						<?/*<a href="<?=\Bitrix\Sale\Location\Admin\SiteLocationHelper::getListUrl();?>"><?=GetMessage('SMO_LOCATION_SALES_ZONE_SELECT')?></a>*/?>

					<?else:?>

						<?
						$sales_zone_countries = \Bitrix\Sale\SalesZone::getCountriesIds($siteList[$i]["ID"]);
						$sales_zone_regions = \Bitrix\Sale\SalesZone::getRegionsIds($siteList[$i]["ID"]);
						$sales_zone_cities = \Bitrix\Sale\SalesZone::getCitiesIds($siteList[$i]["ID"]);
						?>

						<table><tr>
								<th><?=GetMessage("SMO_LOCATION_COUNTRIES")?></th>
								<th><?=GetMessage("SMO_LOCATION_REGIONS")?></th>
								<th><?=GetMessage("SMO_LOCATION_CITIES")?></th>
							</tr><tr>
							<td>
								<select id="sales_zone_countries_<?=htmlspecialcharsbx($siteList[$i]["ID"])?>" name="sales_zone_countries[<?=htmlspecialcharsbx($siteList[$i]["ID"])?>][]" multiple size="10" class="sale-options-location-mselect">
									<option value=''<?=in_array("", $sales_zone_countries) ? " selected" : ""?>><?=GetMessage("SMO_LOCATION_ALL")?></option>
									<option value='NULL'<?=in_array("NULL", $sales_zone_countries) ? " selected" : ""?>><?=GetMessage("SMO_LOCATION_NO_COUNTRY")?></option>
									<?$dbCountryList = CSaleLocation::GetCountryList(array("NAME_LANG"=>"ASC"))?>
									<? while ($arCountry = $dbCountryList->fetch()): ?>
										<option value="<?=$arCountry["ID"]?>"<?=in_array($arCountry["ID"], $sales_zone_countries) ? " selected" : ""?>><?= htmlspecialcharsbx($arCountry["NAME_LANG"])?></option>
									<? endwhile; ?>
								</select>
								</td><td>
								<select id="sales_zone_regions_<?=htmlspecialcharsbx($siteList[$i]["ID"])?>" name="sales_zone_regions[<?=htmlspecialcharsbx($siteList[$i]["ID"])?>][]" multiple size="10" class="sale-options-location-mselect">
									<option value=''<?=in_array("", $sales_zone_regions) ? " selected" : ""?>><?=GetMessage("SMO_LOCATION_ALL")?></option>
									<option value='NULL'<?=in_array("NULL", $sales_zone_regions) ? " selected" : ""?>><?=GetMessage("SMO_LOCATION_NO_REGION")?></option>
									<?if(!in_array("", $sales_zone_countries)):?>
										<?$arRegions = \Bitrix\Sale\SalesZone::getRegions($sales_zone_countries, LANGUAGE_ID);?>
										<?foreach($arRegions as $regionId => $arRegionName):?>
											<option value="<?=$regionId?>"<?=in_array($regionId, $sales_zone_regions) ? " selected" : ""?>><?= htmlspecialcharsbx($arRegionName)?></option>
										<?endforeach;?>
									<?endif;?>
								</select>
							</td><td>

							<select id="sales_zone_regions_<?=htmlspecialcharsbx($siteList[$i]["ID"])?>" name="sales_zone_regions[<?=htmlspecialcharsbx($siteList[$i]["ID"])?>][]" multiple size="10" class="sale-options-location-mselect">
								<option value=''<?=in_array("", $sales_zone_regions) ? " selected" : ""?>><?=GetMessage("SMO_LOCATION_ALL")?></option>
								<option value='NULL'<?=in_array("NULL", $sales_zone_regions) ? " selected" : ""?>><?=GetMessage("SMO_LOCATION_NO_REGION")?></option>
								<?if(!in_array("", $sales_zone_countries)):?>
									<?$arRegions = SalesZone::getRegions($sales_zone_countries, LANGUAGE_ID);?>
									<?foreach($arRegions as $regionId => $arRegionName):?>
										<option value="<?=$regionId?>"<?=in_array($regionId, $sales_zone_regions) ? " selected" : ""?>><?= htmlspecialcharsbx($arRegionName)?></option>
									<?endforeach;?>
								<?endif;?>
							</select>
						</td><td>
							<select id="sales_zone_cities_<?=htmlspecialcharsbx($siteList[$i]["ID"])?>" name="sales_zone_cities[<?=htmlspecialcharsbx($siteList[$i]["ID"])?>][]" multiple size="10" class="sale-options-location-mselect">
								<option value=''<?=in_array("", $sales_zone_cities) ? " selected" : ""?>><?=GetMessage("SMO_LOCATION_ALL")?></option>
								<?if(!in_array("", $sales_zone_regions)):?>
									<?$arCities = SalesZone::getCities($sales_zone_countries, $sales_zone_regions, LANGUAGE_ID);?>
									<?foreach($arCities as $cityId => $cityName):?>
										<option value="<?=$cityId?>"<?=in_array($cityId, $sales_zone_cities) ? " selected" : ""?>><?= htmlspecialcharsbx($cityName)?></option>
									<?endforeach;?>
								<?endif;?>
							</select>
						</td>
					</tr></table>

					<?endif?>

				</td>
			</tr>

			<?endif?>

		</table>
		</div>
<?

endfor;
?>

		</td>
	</tr>
<?if (CBXFeatures::IsFeatureEnabled('SaleCCards') && COption::GetOptionString($module_id, "use_ccards", "N") == "Y")
{
	?>
	<?$tabControl->BeginNextTab();?>

	<?
	if (!CSaleUserCards::CheckPassword())
	{
		?><tr>
			<td colspan="2"><?CAdminMessage::ShowMessage(str_replace("#ROOT#", $_SERVER["DOCUMENT_ROOT"], GetMessage("SMO_NO_VALID_PASSWORD")))?></td>
		</tr><?
	}
	?>
	<tr>
		<td valign="top" width="50%">

				<?= GetMessage("SMO_PATH2CRYPT_FILE") ?>

		</td>
		<td valign="middle" width="50%">

				<input type="text" size="40" value="<?= htmlspecialcharsbx(COption::GetOptionString("sale", "sale_data_file", "")) ?>" name="sale_data_file">

		</td>
	</tr>
	<tr>
		<td valign="top">

				<?= GetMessage("SMO_CRYPT_ALGORITHM") ?>

		</td>
		<td valign="middle">

				<?
				$val = COption::GetOptionString("sale", "crypt_algorithm", "RC4");
				?>
				<select name="crypt_algorithm">
					<option value="RC4"<?if ($val=="RC4") echo " selected";?>>RC4</option>
					<option value="AES"<?if ($val=="AES") echo " selected";?>>AES (Rijndael) - <?= GetMessage("SMO_NEED_MCRYPT") ?></option>
					<option value="3DES"<?if ($val=="3DES") echo " selected";?>>3DES (Triple-DES) - <?= GetMessage("SMO_NEED_MCRYPT") ?></option>
				</select>

		</td>
	</tr>
	<?
}
?>
<?$tabControl->BeginNextTab();?>
	<tr class="heading">
		<td colspan="2"><?=GetMessage("SMO_ADDITIONAL_SITE_PARAMS")?></td>
	</tr>
	<tr>
		<td colspan="2" align="center">
		<table cellspacing="0" cellpadding="0" border="0" class="internal">
		<tr class="heading">
			<td valign="top">
				<?echo GetMessage("SALE_LANG")?>
			</td>
			<td valign="top">
				<?echo GetMessage("SALE_CURRENCY")?>
			</td>
			<td valign="top">
				<?= GetMessage("SMO_GROUPS2SITE") ?>
			</td>
		</tr>
		<?
		foreach($siteList as $val)
		{
			?>
			<tr>
				<td valign="top">
					[<a href="site_edit.php?LID=<?=htmlspecialcharsbx($val["ID"])?>&lang=<?=LANGUAGE_ID?>" title="<?=GetMessage("SALE_SITE_ALT")?>"><?echo htmlspecialcharsbx($val["ID"]) ?></a>] <?echo htmlspecialcharsbx($val["NAME"]) ?>
				</td>
				<td valign="top">

					<?
					$arCurr = CSaleLang::GetByID($val["ID"]);
					echo CCurrency::SelectBox("CURRENCY_".$val["ID"], $arCurr["CURRENCY"], GetMessage("SALE_NOT_SET"), True, "");
					?>

				</td>
				<td valign="top">

					<?
					$arCurrentGroups = array();
					$dbSiteGroupsList = CSaleGroupAccessToSite::GetList(
							array(),
							array("SITE_ID" => $val["ID"])
						);
					while ($arSiteGroup = $dbSiteGroupsList->Fetch())
					{
						$arCurrentGroups[] = (int)$arSiteGroup["GROUP_ID"];
					}

					$userGroupList = array();
					$dbGroups = CGroup::GetList("c_sort", "asc", array("ANONYMOUS" => "N"));
					while ($arGroup = $dbGroups->Fetch())
					{
						$arGroup["ID"] = (int)$arGroup["ID"];

						if ($arGroup["ID"] == 1 || $arGroup["ID"] == 2)
							continue;

						$userGroupList[] = $arGroup;
					}
					?>
					<select name="SITE_USER_GROUPS_<?=htmlspecialcharsbx($val["ID"]) ?>[]" multiple size="5">
						<?
						for ($i = 0, $intCount = count($userGroupList); $i < $intCount; $i++)
						{
							?><option value="<?= $userGroupList[$i]["ID"] ?>"<?if (in_array($userGroupList[$i]["ID"], $arCurrentGroups)) echo " selected";?>><?= htmlspecialcharsEx($userGroupList[$i]["NAME"]) ?></option><?
						}
						?>
					</select>

				</td>
			</tr>
			<?
		}
		?>
		</table>
		</td>
	</tr>
<?$tabControl->BeginNextTab();?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>

<?$tabControl->BeginNextTab();?>

	<tr class="heading">
		<td colspan="2"><?=GetMessage("SALE_AUTO_ORDER_STATUS_TITLE")?></td>
	</tr>
	<tr>
		<td>
			<?echo GetMessage("SALE_PAY_TO_STATUS")?>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "status_on_paid", "");
			?>
			<select name="PAID_STATUS">
				<?
				foreach($arStatuses as $statusID => $statusName)
				{
					?><option value="<?=$statusID?>"<?if ($val == $statusID) echo " selected";?>><?=htmlspecialcharsbx($statusName)?></option><?
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<?echo GetMessage("SALE_HALF_PAY_TO_STATUS")?>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "status_on_half_paid", "");
			?>
			<select name="HALF_PAID_STATUS">
				<?
				foreach($arStatuses as $statusID => $statusName)
				{
					?><option value="<?=$statusID?>"<?if ($val == $statusID) echo " selected";?>><?=htmlspecialcharsbx($statusName)?></option><?
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<?=Main\Localization\Loc::getMessage("SALE_CHANGE_ALLOW_DELIVERY_AFTER_PAID")?>
		</td>
		<td>
			<?
			$val = Option::get("sale", "status_on_change_allow_delivery_after_paid", "");
			$isPayed2AllowDelivery = COption::GetOptionString("sale", "status_on_payed_2_allow_delivery", "");

			if ($val == "")
			{
				$val = ($isPayed2AllowDelivery == "Y") ? Sale\Configuration::ALLOW_DELIVERY_ON_FULL_PAY : "N";
			}
			?>
			<select name="CHANGE_ALLOW_DELIVERY_AFTER_PAID">
				<option value="N" <?if ($val == "N") echo " selected";?>><?=Main\Localization\Loc::getMessage("SALE_DENY_STATUS")?></option>
				<?
				foreach (Sale\Configuration::getAllowDeliveryAfterPaidConditionList(true) as $payTypeId => $payTitle)
				{
					?><option value="<? echo $payTypeId; ?>"<?= ($payTypeId == $val ? ' selected' : '') ?>><?=htmlspecialcharsEx($payTitle); ?></option><?
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			&nbsp;
		</td>
	</tr>
	<tr>
		<td>
			<?=Main\Localization\Loc::getMessage("SALE_ALLOW_DELIVERY_TO_STATUS")?>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "status_on_allow_delivery", "");
			?>
			<select name="ALLOW_DELIVERY_STATUS">
				<?
				foreach($arStatuses as $statusID => $statusName)
				{
					?><option value="<?=$statusID?>"<?if ($val == $statusID) echo " selected";?>><?=htmlspecialcharsbx($statusName)?></option><?
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<?=Main\Localization\Loc::getMessage("SALE_ALLOW_DELIVERY_ONE_OF_TO_STATUS")?>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "status_on_allow_delivery_one_of", "");
			?>
			<select name="ALLOW_DELIVERY_ONE_OF_STATUS">
				<?
				foreach($arStatuses as $statusID => $statusName)
				{
					?><option value="<?=$statusID?>"<?if ($val == $statusID) echo " selected";?>><?=htmlspecialcharsbx($statusName)?></option><?
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<?echo GetMessage("SALE_SHIPMENT_SHIPPED_TO_STATUS")?>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "status_on_shipped_shipment", "");
			?>
			<select name="SHIPMENT_SHIPPED_STATUS">
				<?
				foreach($arStatuses as $statusID => $statusName)
				{
					?><option value="<?=$statusID?>"<?if ($val == $statusID) echo " selected";?>><?=htmlspecialcharsbx($statusName)?></option><?
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<?echo GetMessage("SALE_SHIPMENT_SHIPPED_ONE_OF_TO_STATUS")?>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "status_on_shipped_shipment_one_of", "");
			?>
			<select name="SHIPMENT_SHIPPED_ONE_OF_STATUS">
				<?
				foreach($arStatuses as $statusID => $statusName)
				{
					?><option value="<?=$statusID?>"<?if ($val == $statusID) echo " selected";?>><?=htmlspecialcharsbx($statusName)?></option><?
				}
				?>
			</select>
		</td>
	</tr>


	<tr class="heading">
		<td colspan="2"><?=GetMessage("SALE_AUTO_SHIPMENT_STATUS_TITLE")?></td>
	</tr>
	<tr>
		<td>
			<?echo GetMessage("SALE_SHIPMENT_ALLOW_DELIVERY_TO_SHIPMENT_STATUS")?>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "shipment_status_on_allow_delivery", "");
			?>
			<select name="SHIPMENT_ALLOW_DELIVERY_TO_SHIPMENT_STATUS">
				<?
				foreach($delieryStatuses as $statusId => $statusName)
				{
					?><option value="<?=$statusId?>"<?if ($val == $statusId) echo " selected";?>><?=htmlspecialcharsEx($statusName)?></option><?
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<?echo GetMessage("SALE_SHIPMENT_SHIPPED_TO_SHIPMENT_STATUS")?>
		</td>
		<td>
			<?
			$val = COption::GetOptionString("sale", "shipment_status_on_shipped", "");
			?>
			<select name="SHIPMENT_SHIPPED_TO_SHIPMENT_STATUS">
				<?
				foreach($delieryStatuses as $statusId => $statusName)
				{
					?><option value="<?=$statusId?>"<?if ($val == $statusId) echo " selected";?>><?=htmlspecialcharsEx($statusName)?></option><?
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<label for="ALLOW_DEDUCTION_ON_DELIVERY"><?echo GetMessage("SALE_ALLOW_DEDUCTION_ON_DELIVERY")?></label>
		</td>
		<td>
			<?
			$valDeductOnDelivery = COption::GetOptionString("sale", "allow_deduction_on_delivery", "");
			?>
			<input type="checkbox" name="ALLOW_DEDUCTION_ON_DELIVERY" id="ALLOW_DEDUCTION_ON_DELIVERY" value="Y"<?if($valDeductOnDelivery=="Y")echo" checked";?> onclick="javascript:toggleDefaultStores(this);">
			<script type="text/javascript">
				function toggleDefaultStores(el)
				{
					var elements = document.getElementsByClassName('default_deduct_store_control');
					for (var i = 0; i < elements.length; ++i)
					{
						var site_id = elements[i].id.replace('default_deduct_store_control_', ''),
							selector = BX("default_store_select_" + site_id);

						elements[i].style.display = (el.checked && selector.length > 0) ? 'table-row' : 'none';
						BX("default_store_select_save_" + site_id).value = (el.checked && selector.length > 0) ? "Y" : "N";
					}

				}
			</script>
		</td>
	</tr>

	<tr class="heading">
		<td colspan="2"><?=GetMessage("SALE_AUTO_SHP_TR_STATUS_ON")?></td>
	</tr>

	<tr>
		<td><?=GetMessage("SALE_TRACKING_CHECK_SWITCH")?>:</td>
		<td><input id="sale-option-tracking-auto-switch" type="checkbox" value="Y" onClick="toggleTrackingAuto();" name="tracking_check_switch"<?=!empty($currentSettings["tracking_check_switch"]) && $currentSettings["tracking_check_switch"] == 'Y' ? ' checked' : ''?>></td>
	</tr>

	<tr class="sale-option-tracking-auto">
		<td><?=GetMessage("SALE_TRACKING_CHECK_PERIOD")?>:</td>
		<td><input type="text" name="tracking_check_period" value="<?=!empty($currentSettings["tracking_check_period"]) && intval($currentSettings["tracking_check_period"]) > 0 ? intval($currentSettings["tracking_check_period"]) : '0'?>"></td>
	</tr>

	<tr class="heading sale-option-tracking-auto">
		<td colspan="2"><?=GetMessage("SALE_AUTO_SHP_TR_STATUS_MAP")?></td>
	</tr>

	<?
	$shipmentStatuses = array();
	$context = Main\Application::getInstance()->getContext();

	$dbRes = Sale\Internals\StatusTable::getList(array(
		'select' => array('ID', 'Bitrix\Sale\Internals\StatusLangTable:STATUS.NAME'),
		'filter' => array(
			'=Bitrix\Sale\Internals\StatusLangTable:STATUS.LID' => $context->getLanguage(),
			'=TYPE' => 'D'
		),
		'order' => array('SORT' => 'ASC')
	));

	while ($shipmentStatus = $dbRes->fetch())
		$shipmentStatuses[$shipmentStatus["ID"]] = $shipmentStatus["SALE_INTERNALS_STATUS_SALE_INTERNALS_STATUS_LANG_STATUS_NAME"] . " [" . $shipmentStatus["ID"] . "]";

	$trackingStatuses = \Bitrix\Sale\Delivery\Tracking\Manager::getStatusesList();
	?><tr class="sale-option-tracking-auto"><td><b><?=GetMessage("SALE_TRACKING_TSTATUSES")?></b></td><td><b><?=GetMessage("SALE_TRACKING_SSTATUSES")?></b></td></tr><?
	foreach($trackingStatuses as $trackingStatusId => $trackingStatusName):?>
		<tr class="sale-option-tracking-auto">
			<td><?=$trackingStatusName?>:</td>
			<td>
				<select name="tracking_map_statuses[<?=$trackingStatusId?>]">
					<option value=""><?=GetMessage("SALE_TRACKING_NOT_USE")?></option>
					<?foreach($shipmentStatuses as $shipmentStatusId => $shipmentStatusName):?>
						<option value="<?=$shipmentStatusId?>"<?=!empty($currentSettings["tracking_map_statuses"][$trackingStatusId]) && $currentSettings["tracking_map_statuses"][$trackingStatusId] == $shipmentStatusId ? " selected" : ""?>><?=htmlspecialcharsEx($shipmentStatusName)?></option>
					<?endforeach;?>
				</select>
			</td>
		</tr>
	<?endforeach;?>
	<?$tabControl->BeginNextTab();?>
	<?
	$filterValues = Option::get('sale', 'archive_params');
	$filterValues = unserialize($filterValues, ['allowed_classes' => false]);
	?>
	<tr>
		<td>
			<label for="ORDER_ARCHIVE_REGULAR_ACCEPT"><?echo GetMessage("SALE_ORDER_ARCHIVE_ACCEPT")?>:</label>
		</td>
		<td>
			<input type="checkbox" name="archive_regular_accept" id="ORDER_ARCHIVE_REGULAR_ACCEPT" value="Y" <?if(Option::get('sale', 'regular_archive_active')) echo" checked";?>>
		</td>
	</tr>
	<tr>
		<td>
			<label for="archive_limit"><?=GetMessage("SALE_ORDER_ARCHIVE_LIMIT_BY_HIT")?>:</label>
		</td>
		<td>
			<input type="text" name="archive_limit" value="<?=(int)(Option::get('sale', 'archive_limit')) ? (int)(Option::get('sale', 'archive_limit')) : 10?>" size="5" id="archive_limit">
		</td>
	</tr>
	<tr>
		<td><label for="archive_time_limit"><?=Loc::getMessage("SALE_ORDER_ARCHIVE_MAX_TIME_BY_HIT")?>:</label></td>
		<td>
			<input type="text"
				   name="archive_time_limit"
				   value="<?=(int)(Option::get('sale', 'archive_time_limit')) ? (int)(Option::get('sale', 'archive_time_limit')) : 5?>"
				   size="5"
				   id="archive_time_limit">
			<?echo Loc::getMessage("SALE_ORDER_ARCHIVE_SEC")?>
		</td>
	</tr>
	<?
	if (
		Option::get('sale', 'regular_archive_active', false) === false
		&& Option::get('sale', 'archive_time_limit', false) === false
	)
	{
		?>
		<tr>
			<td align="center" colspan="2">
				<a href="sale_archive.php"><?= Loc::getMessage("SALE_ORDER_ARCHIVE_FIRST_START_NOTE")?></a>
			</td>
		</tr>
		<?
	}
	?>
	<tr class="heading">
		<td colspan="2"><?=GetMessage("SALE_ORDER_ARCHIVE_TITLE")?></td>
	</tr>
	<tr>
		<td><label for="archive_period"><?=GetMessage("SALE_ORDER_ARCHIVE_PERIOD")?>:</label></td>
		<td><input type="text" name="archive_period" value="<?=(int)$filterValues['PERIOD'] ? (int)$filterValues['PERIOD'] : 365?>" size="5" id="archive_period"></td>
	</tr>
	<tr>
		<td valign="top"><label for="archive_blocked_order_accept"><?echo Loc::getMessage("ARCHIVE_BLOCKED_ORDER_ACCEPT")?>:</label></td>
		<td>
			<input type="checkbox" name="archive_blocked_order_accept" id="archive_blocked_order_accept" value="Y" <?if(Option::get("sale", "archive_blocked_order") === "Y") echo "checked"?>>
		</td>
	</tr>
	<?
	if (count($siteList) > 1)
	{
	?>
		<tr valign="top">
			<td><label for="archive_site"><?=Loc::getMessage("SALE_LANG")?>:</label></td>
			<td>
				<select name="archive_site[]" id="archive_site" multiple size="<?=(count($siteList) < 5) ? count($siteList) : 5?>">
					<?
						foreach($siteList as $site)
						{
							?>
							<option
								value="<?= htmlspecialcharsbx($site['ID']) ?>"
								<?
									if (
										(is_array($filterValues['LID'])	&& in_array($site['ID'], $filterValues['LID']))
										|| empty($filterValues['LID'])
									)
										echo " selected"
								?>
							>
								<?=htmlspecialcharsbx($site['NAME'])?>
							</option>
							<?
						}
					?>
				</select>
			</td>
		</tr>
	<?
	}
	?>
	<tr>
		<td valign="top"><?echo Loc::getMessage("SALE_ORDER_ARCHIVE_STATUS")?>:</td>
		<td>
			<select name="archive_status_id[]" multiple size="3">
				<?
				$statusesList = \Bitrix\Sale\OrderStatus::getStatusesUserCanDoOperations(
					$USER->GetID(),
					array('view')
				);

				$allStatusNames = \Bitrix\Sale\OrderStatus::getAllStatusesNames();

				foreach($statusesList as  $statusCode)
				{
					if (!$statusName = $allStatusNames[$statusCode])
						continue;
					?>
					<option
						value="<?= htmlspecialcharsbx($statusCode) ?>"
						<?
							if (
								(is_array($filterValues['STATUS_ID']) && in_array($statusCode, $filterValues['STATUS_ID']))
								|| empty($filterValues['STATUS_ID'])
							)
								echo " selected"
						?>
					>
						[<?= htmlspecialcharsbx($statusCode) ?>] <?= htmlspecialcharsbx($statusName) ?>
					</option>
					<?
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<label for="ORDER_ARCHIVE_PAYED"><?echo GetMessage("SALE_ORDER_ARCHIVE_PAYED")?>:</label>
		</td>
		<td>
			<select name="archive_payed" id="ORDER_ARCHIVE_PAYED">
				<option value="" selected><?echo Loc::getMessage("SALE_ORDER_ARCHIVE_ALL")?></option>
				<option value="Y"<?if($filterValues['=PAYED'] == "Y") echo " selected"?>><?echo Loc::getMessage("SALE_ORDER_ARCHIVE_YES")?></option>
				<option value="N"<?if($filterValues['=PAYED'] == 'N') echo " selected"?>><?echo Loc::getMessage("SALE_ORDER_ARCHIVE_NO")?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<label for="ORDER_ARCHIVE_CANCELED"><?echo GetMessage("SALE_ORDER_ARCHIVE_CANCELED")?>:</label>
		</td>
		<td>
			<select name="archive_canceled" id="ORDER_ARCHIVE_CANCELED">
				<option value="" selected><?echo Loc::getMessage("SALE_ORDER_ARCHIVE_ALL")?></option>
				<option value="Y"<?if($filterValues['=CANCELED'] == "Y") echo " selected"?>><?echo Loc::getMessage("SALE_ORDER_ARCHIVE_YES")?></option>
				<option value="N"<?if($filterValues['=CANCELED'] == 'N') echo " selected"?>><?echo Loc::getMessage("SALE_ORDER_ARCHIVE_NO")?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<label for="ORDER_ARCHIVE_DEDUCTED"><?echo GetMessage("SALE_ORDER_ARCHIVE_DEDUCTED")?>:</label>
		</td>
		<td>
			<select name="archive_deducted" id="ORDER_ARCHIVE_DEDUCTED">
				<option value="" selected><?echo Loc::getMessage("SALE_ORDER_ARCHIVE_ALL")?></option>
				<option value="Y"<?if($filterValues['=DEDUCTED'] == "Y") echo " selected"?>><?echo Loc::getMessage("SALE_ORDER_ARCHIVE_YES")?></option>
				<option value="N"<?if($filterValues['=DEDUCTED'] == 'N') echo " selected"?>><?echo Loc::getMessage("SALE_ORDER_ARCHIVE_NO")?></option>
			</select>
		</td>
	</tr>
	<?$tabControl->BeginNextTab();?>
	<div class="adm-numerator-use-template-checkbox-outer">
		<span class="adm-numerator-use-template-checkbox-title"><?= Loc::getMessage('NUMERATOR_NOT_USE_CHECKBOX_TITLE'); ?></span>
		<div class="adm-numerator-use-template-checkbox-inner">
			<input type="checkbox" class="adm-designed-checkbox" name="hideNumeratorSettings" id="hideNumeratorSettings"
				   value="Y"
				<? if ($numeratorForOrdersId==''): ?>
					checked=""
				<? endif; ?>>
			<label class="adm-designed-checkbox-label" for="hideNumeratorSettings" title=""></label>
		</div>
	</div>
	<?
	$APPLICATION->IncludeComponent(
		"bitrix:main.numerator.edit",
		'admin',
		[
			"NUMERATOR_TYPE"            => 'ORDER',
			'CSS_WRAP_CLASS'            => 'js-numerator-form',
			"NUMERATOR_ID"              => $numeratorForOrdersId,
			"IS_HIDE_NUMERATOR_NAME"       => true,
			"IS_HIDE_IS_DIRECT_NUMERATION" => true,
		]
	);
	?>
<?$tabControl->Buttons();?>
<script type="text/javascript">
function RestoreDefaults()
{
	if (confirm('<?echo addslashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>'))
		window.location = "<?echo $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?echo LANGUAGE_ID?>&mid=<?echo $module_id?>";
}
</script>

<input type="submit" <?if ($SALE_RIGHT<"W") echo "disabled" ?> name="Update" value="<?echo GetMessage("MAIN_SAVE")?>" class="adm-btn-save">
<input type="hidden" name="Update" value="Y">
<?if($_REQUEST["back_url_settings"] <> ''):?>
	<input type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
	<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
<?endif;?>
<input type="button" <?if ($SALE_RIGHT<"W") echo "disabled" ?> title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="RestoreDefaults();" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
<?$tabControl->End();?>
</form>
<h2><?=GetMessage("SALE_SYSTEM_PROCEDURES") ?></h2>
	<?
	$showbasketDiscountConvert = (string)Main\Config\Option::get('sale', 'basket_discount_converted') != 'Y' && Main\ModuleManager::isModuleInstalled('catalog');
	if ($showbasketDiscountConvert)
	{
		if (CSaleBasketDiscountConvert::getAllCounter() == 0)
		{
			$adminNotifyIterator = CAdminNotify::GetList(array(), array('MODULE_ID' => 'sale', 'TAG' => 'BASKET_DISCOUNT_CONVERTED'));
			if ($adminNotifyIterator)
			{
				if ($adminNotify = $adminNotifyIterator->Fetch())
					CAdminNotify::Delete($adminNotify['ID']);
				unset($adminNotify);
			}
			unset($adminNotifyIterator);
			$showbasketDiscountConvert = false;
		}
	}
	$systemTabs[] = array('DIV' => 'saleSysTabReindex', 'TAB' => GetMessage('SALE_SYSTEM_TAB_REINDEX'), 'ICON' => 'sale_settings', 'TITLE' => GetMessage('SALE_SYSTEM_TAB_REINDEX_TITLE'));
	if ($showbasketDiscountConvert)
		$systemTabs[] = array('DIV' => 'saleSysTabConvert', 'TAB' => GetMessage('SALE_SYSTEM_TAB_CONVERT'), 'ICON' => 'sale_settings', 'TITLE' => GetMessage('SALE_SYSTEM_TAB_CONVERT_TITLE'));

	$systemTabControl = new CAdminTabControl('saleSysTabControl', $systemTabs, true, true);

	$systemTabControl->Begin();
	$systemTabControl->BeginNextTab();
	?><tr><td align="left"><?
	$firstTop = ' style="margin-top: 0;"';
	?><h4<? echo $firstTop; ?>><? echo GetMessage('SALE_SYS_PROC_REINDEX_DISCOUNT'); ?></h4>
	<input class="adm-btn-save" type="button" id="sale_discount_reindex" value="<? echo GetMessage('SALE_SYS_PROC_REINDEX_DISCOUNT_BTN'); ?>">
	<p><? echo GetMessage('SALE_SYS_PROC_REINDEX_DISCOUNT_ALERT'); ?></p><?
	$firstTop = '';
	?></td></tr><?
	if ($showbasketDiscountConvert)
	{
		$systemTabControl->BeginNextTab();
		?>
		<tr>
		<td align="left"><?
		$firstTop = ' style="margin-top: 0;"';
		?><h4<? echo $firstTop; ?>><? echo GetMessage('SALE_SYS_PROC_CONVERT_BASKET_DISCOUNT'); ?></h4>
		<input class="adm-btn-save" type="button" id="sale_basket_discount" value="<? echo GetMessage('SALE_SYS_PROC_CONVERT_BASKET_DISCOUNT_BTN'); ?>">
		<p><? echo GetMessage('SALE_SYS_PROC_CONVERT_BASKET_DISCOUNT_ALERT'); ?></p><?
		$firstTop = '';
		?></td></tr><?
	}
	$systemTabControl->End();
	?>
<script type="text/javascript">
	BX.ready(function(){
		var numeratorSettingsToggle = BX('hideNumeratorSettings');

		if (BX('hideNumeratorSettings').checked)
		{
			hideNumeratorSettings();
		}
		if (!!numeratorSettingsToggle)
		{
			BX.bind(numeratorSettingsToggle, 'click', hideNumeratorSettings);
		}
	});

	function hideNumeratorSettings()
	{
		var numForm = document.querySelector('.js-numerator-form');
		if (numForm)
		{
			if (numForm.style.display === 'none')
			{
				numForm.style.display = 'block'
			}
			else
			{
				numForm.style.display = 'none'
			}
		}
	}

	function toggleTrackingAuto()
	{
		var nodes = BX.findChildren(document, {className:"sale-option-tracking-auto"}, true),
			switchStateOn = BX("sale-option-tracking-auto-switch").checked;

		for(var i in nodes)
			nodes[i].style.display = switchStateOn ? '' : 'none';
	}

	function showDiscountReindex()
	{
		var obDiscount, params;

		params = {
			bxpublic: 'Y',
			sessid: BX.bitrix_sessid()
		};

		var obBtn = {
			title: '<? echo CUtil::JSEscape(GetMessage('SALE_OPTIONS_POPUP_WINDOW_CLOSE_BTN')) ?>',
			id: 'close',
			name: 'close',
			action: function () {
				this.parentWindow.Close();
			}
		};

		obDiscount = new BX.CAdminDialog({
			'content_url': '/bitrix/tools/sale/discount_reindex.php?lang=<? echo LANGUAGE_ID; ?>',
			'content_post': params,
			'draggable': true,
			'resizable': true,
			'buttons': [obBtn]
		});
		obDiscount.Show();
		return false;
	}
	function showBasketDiscountConvert()
	{
		var obDiscount, params;

		params = {
			bxpublic: 'Y',
			sessid: BX.bitrix_sessid()
		};

		var obBtn = {
			title: '<? echo CUtil::JSEscape(GetMessage('SALE_OPTIONS_POPUP_WINDOW_CLOSE_BTN')) ?>',
			id: 'close',
			name: 'close',
			action: function () {
				this.parentWindow.Close();
			}
		};

		obDiscount = new BX.CAdminDialog({
			'content_url': '/bitrix/tools/sale/basket_discount_convert.php?lang=<? echo LANGUAGE_ID; ?>',
			'content_post': params,
			'draggable': true,
			'resizable': true,
			'buttons': [obBtn]
		});
		obDiscount.Show();
		return false;
	}
	function showApplyDiscountMode()
	{
		var modeList = BX('tr_discount_apply_mode'),
			showMode = BX('use_sale_discount_only_Y');
		if (!BX.type.isElementNode(modeList) || !BX.type.isElementNode(showMode))
			return;
		BX.style(modeList, 'display', (showMode.checked ? 'none' : 'table-row'));
	}
	BX.ready( function(){
		BX.message["SMO_LOCATION_JS_GET_DATA_ERROR"] = "<?=GetMessage("SMO_LOCATION_JS_GET_DATA_ERROR")?>";
		BX.message["SMO_LOCATION_ALL"] = "<?=GetMessage("SMO_LOCATION_ALL")?>";
		BX.message["SMO_LOCATION_NO_COUNTRY"] = "<?=GetMessage("SMO_LOCATION_NO_COUNTRY")?>";
		BX.message["SMO_LOCATION_NO_REGION"] = "<?=GetMessage("SMO_LOCATION_NO_REGION")?>";

		var discountReindex = BX('sale_discount_reindex'),
			basketDiscount = BX('sale_basket_discount'),
			showMode = BX('use_sale_discount_only_Y');

		if (!!discountReindex)
			BX.bind(discountReindex, 'click', showDiscountReindex);
		if (!!basketDiscount)
			BX.bind(basketDiscount, 'click', showBasketDiscountConvert);
		if (BX.type.isElementNode(showMode))
			BX.bind(showMode, 'click', showApplyDiscountMode);

		toggleTrackingAuto();
	});
</script>
<?endif;?>