<?
use Bitrix\Main\Loader;
define("SALE_DEBUG", false); // Debug

global $DBType;

IncludeModuleLangFile(__FILE__);

$GLOBALS["SALE_FIELD_TYPES"] = array(
	"TEXT" => GetMessage("SALE_TYPE_TEXT"),
	"CHECKBOX" => GetMessage("SALE_TYPE_CHECKBOX"),
	"SELECT" => GetMessage("SALE_TYPE_SELECT"),
	"MULTISELECT" => GetMessage("SALE_TYPE_MULTISELECT"),
	"TEXTAREA" => GetMessage("SALE_TYPE_TEXTAREA"),
	"LOCATION" => GetMessage("SALE_TYPE_LOCATION"),
	"RADIO" => GetMessage("SALE_TYPE_RADIO"),
	"FILE" => GetMessage("SALE_TYPE_FILE")
);

if (!Loader::includeModule('currency'))
	return false;

// Number of processed recurring records at one time
define("SALE_PROC_REC_NUM", 3);
// Number of recurring payment attempts
define("SALE_PROC_REC_ATTEMPTS", 3);
// Time between recurring payment attempts (in seconds)
define("SALE_PROC_REC_TIME", 43200);

define("SALE_PROC_REC_FREQUENCY", 7200);
// Owner ID base name used by CSale<etnity_name>ReportHelper clases for managing the reports.
define("SALE_REPORT_OWNER_ID", 'sale');
//cache orders flag for real-time exhange with 1C
define("CACHED_b_sale_order", 3600*24);

global $SALE_TIME_PERIOD_TYPES;
$SALE_TIME_PERIOD_TYPES = array(
	"H" => GetMessage("I_PERIOD_HOUR"),
	"D" => GetMessage("I_PERIOD_DAY"),
	"W" => GetMessage("I_PERIOD_WEEK"),
	"M" => GetMessage("I_PERIOD_MONTH"),
	"Q" => GetMessage("I_PERIOD_QUART"),
	"S" => GetMessage("I_PERIOD_SEMIYEAR"),
	"Y" => GetMessage("I_PERIOD_YEAR")
);

define("SALE_VALUE_PRECISION", 4);
define("SALE_WEIGHT_PRECISION", 3);

define('BX_SALE_MENU_CATALOG_CLEAR', 'Y');

$GLOBALS["AVAILABLE_ORDER_FIELDS"] = array(
	"ID" => array("COLUMN_NAME" => "ID", "NAME" => GetMessage("SI_ORDER_ID"), "SELECT" => "ID,DATE_INSERT", "CUSTOM" => "Y", "SORT" => "ID"),
	"LID" => array("COLUMN_NAME" => GetMessage("SI_SITE"), "NAME" => GetMessage("SI_SITE"), "SELECT" => "LID", "CUSTOM" => "N", "SORT" => "LID"),
	"PERSON_TYPE" => array("COLUMN_NAME" => GetMessage("SI_PAYER_TYPE"), "NAME" => GetMessage("SI_PAYER_TYPE"), "SELECT" => "PERSON_TYPE_ID", "CUSTOM" => "Y", "SORT" => "PERSON_TYPE_ID"),
	"PAYED" => array("COLUMN_NAME" => GetMessage("SI_PAID"), "NAME" => GetMessage("SI_PAID_ORDER"), "SELECT" => "PAYED,DATE_PAYED,EMP_PAYED_ID", "CUSTOM" => "Y", "SORT" => "PAYED"),
	"PAY_VOUCHER_NUM" => array("COLUMN_NAME" => GetMessage("SI_NO_PP"), "NAME" => GetMessage("SI_NO_PP_DOC"), "SELECT" => "PAY_VOUCHER_NUM", "CUSTOM" => "N", "SORT" => "PAY_VOUCHER_NUM"),
	"PAY_VOUCHER_DATE" => array("COLUMN_NAME" => GetMessage("SI_DATE_PP"), "NAME" => GetMessage("SI_DATE_PP_DOC"), "SELECT" => "PAY_VOUCHER_DATE", "CUSTOM" => "N", "SORT" => "PAY_VOUCHER_DATE"),
	"DELIVERY_DOC_NUM" => array("COLUMN_NAME" => GetMessage("SI_DATE_PP_DELIVERY_DOC_NUM"), "NAME" => GetMessage("SI_DATE_PP_DOC_DELIVERY_DOC_NUM"), "SELECT" => "DELIVERY_DOC_NUM", "CUSTOM" => "N", "SORT" => "DELIVERY_DOC_NUM"),
	"DELIVERY_DOC_DATE" => array("COLUMN_NAME" => GetMessage("SI_DATE_PP_DELIVERY_DOC_DATE"), "NAME" => GetMessage("SI_DATE_PP_DOC_DELIVERY_DOC_DATE"), "SELECT" => "DELIVERY_DOC_DATE", "CUSTOM" => "N", "SORT" => "DELIVERY_DOC_DATE"),
	"CANCELED" => array("COLUMN_NAME" => GetMessage("SI_CANCELED"), "NAME" => GetMessage("SI_CANCELED_ORD"), "SELECT" => "CANCELED,DATE_CANCELED,EMP_CANCELED_ID", "CUSTOM" => "Y", "SORT" => "CANCELED"),
	"STATUS" => array("COLUMN_NAME" => GetMessage("SI_STATUS"), "NAME" => GetMessage("SI_STATUS_ORD"), "SELECT" => "STATUS_ID,DATE_STATUS,EMP_STATUS_ID", "CUSTOM" => "Y", "SORT" => "STATUS_ID"),
	"PRICE_DELIVERY" => array("COLUMN_NAME" => GetMessage("SI_DELIVERY"), "NAME" => GetMessage("SI_DELIVERY"), "SELECT" => "PRICE_DELIVERY,CURRENCY", "CUSTOM" => "Y", "SORT" => "PRICE_DELIVERY"),
	"ALLOW_DELIVERY" => array("COLUMN_NAME" => GetMessage("SI_ALLOW_DELIVERY"), "NAME" => GetMessage("SI_ALLOW_DELIVERY1"), "SELECT" => "ALLOW_DELIVERY,DATE_ALLOW_DELIVERY,EMP_ALLOW_DELIVERY_ID", "CUSTOM" => "Y", "SORT" => "ALLOW_DELIVERY"),
	"PRICE" => array("COLUMN_NAME" => GetMessage("SI_SUM"), "NAME" => GetMessage("SI_SUM_ORD"), "SELECT" => "PRICE,CURRENCY", "CUSTOM" => "Y", "SORT" => "PRICE"),
	"SUM_PAID" => array("COLUMN_NAME" => GetMessage("SI_SUM_PAID"), "NAME" => GetMessage("SI_SUM_PAID1"), "SELECT" => "SUM_PAID,CURRENCY", "CUSTOM" => "Y", "SORT" => "SUM_PAID"),
	"USER" => array("COLUMN_NAME" => GetMessage("SI_BUYER"), "NAME" => GetMessage("SI_BUYER"), "SELECT" => "USER_ID", "CUSTOM" => "Y", "SORT" => "USER_ID"),
	"PAY_SYSTEM" => array("COLUMN_NAME" => GetMessage("SI_PAY_SYS"), "NAME" => GetMessage("SI_PAY_SYS"), "SELECT" => "PAY_SYSTEM_ID", "CUSTOM" => "Y", "SORT" => "PAY_SYSTEM_ID"),
	"DELIVERY" => array("COLUMN_NAME" => GetMessage("SI_DELIVERY_SYS"), "NAME" => GetMessage("SI_DELIVERY_SYS"), "SELECT" => "DELIVERY_ID", "CUSTOM" => "Y", "SORT" => "DELIVERY_ID"),
	"DATE_UPDATE" => array("COLUMN_NAME" => GetMessage("SI_DATE_UPDATE"), "NAME" => GetMessage("SI_DATE_UPDATE"), "SELECT" => "DATE_UPDATE", "CUSTOM" => "N", "SORT" => "DATE_UPDATE"),
	"PS_STATUS" => array("COLUMN_NAME" => GetMessage("SI_PAYMENT_PS"), "NAME" => GetMessage("SI_PS_STATUS"), "SELECT" => "PS_STATUS,PS_RESPONSE_DATE", "CUSTOM" => "N", "SORT" => "PS_STATUS"),
	"PS_SUM" => array("COLUMN_NAME" => GetMessage("SI_PS_SUM"), "NAME" => GetMessage("SI_PS_SUM1"), "SELECT" => "PS_SUM,PS_CURRENCY", "CUSTOM" => "Y", "SORT" => "PS_SUM"),
	"TAX_VALUE" => array("COLUMN_NAME" => GetMessage("SI_TAX"), "NAME" => GetMessage("SI_TAX_SUM"), "SELECT" => "TAX_VALUE,CURRENCY", "CUSTOM" => "Y", "SORT" => "TAX_VALUE"),
	"BASKET" => array("COLUMN_NAME" => GetMessage("SI_ITEMS"), "NAME" => GetMessage("SI_ITEMS_ORD"), "SELECT" => "", "CUSTOM" => "Y", "SORT" => "")
);

CModule::AddAutoloadClasses(
	"sale",
	array(
		"sale" => "install/index.php",
		"CSaleDelivery" => $DBType."/delivery.php",
		"CSaleDeliveryHandler" => $DBType."/delivery_handler.php",
		"CSaleDeliveryHelper" => "general/delivery_helper.php",
		"CSaleDelivery2PaySystem" => "general/delivery_2_pay_system.php",
		"CSaleLocation" => $DBType."/location.php",
		"CSaleLocationGroup" => $DBType."/location_group.php",

		"CSaleBasket" => $DBType."/basket.php",
		"CSaleBasketHelper" => "general/basket_helper.php",
		"CSaleUser" => $DBType."/basket.php",

		"CSaleOrder" => $DBType."/order.php",
		"CSaleOrderPropsGroup" => $DBType."/order_props_group.php",
		"CSaleOrderPropsVariant" => $DBType."/order_props_variant.php",
		"CSaleOrderUserProps" => $DBType."/order_user_props.php",
		"CSaleOrderUserPropsValue" => $DBType."/order_user_props_value.php",
		"CSaleOrderTax" => $DBType."/order_tax.php",
		"CSaleOrderHelper" => "general/order_helper.php",

		"CSalePaySystem" => $DBType."/pay_system.php",
		"CSalePaySystemAction" => $DBType."/pay_system_action.php",
		"CSalePaySystemsHelper" => "general/pay_system_helper.php",
		"CSalePaySystemTarif" => "general/pay_system_tarif.php",

		"CSaleTax" => $DBType."/tax.php",
		"CSaleTaxRate" => $DBType."/tax_rate.php",

		"CSalePersonType" => $DBType."/person_type.php",
		"CSaleDiscount" => $DBType."/discount.php",
		"CSaleBasketDiscountConvert" => "general/step_operations.php",
		"CSaleDiscountReindex" => "general/step_operations.php",
		"CSaleDiscountConvertExt" => "general/step_operations.php",
		"CSaleUserAccount" => $DBType."/user.php",
		"CSaleUserTransact" => $DBType."/user_transact.php",
		"CSaleUserCards" => $DBType."/user_cards.php",
		"CSaleRecurring" => $DBType."/recurring.php",


		"CSaleLang" => $DBType."/settings.php",
		"CSaleGroupAccessToSite" => $DBType."/settings.php",
		"CSaleGroupAccessToFlag" => $DBType."/settings.php",

		"CSaleAuxiliary" => $DBType."/auxiliary.php",

		"CSaleAffiliate" => $DBType."/affiliate.php",
		"CSaleAffiliatePlan" => $DBType."/affiliate_plan.php",
		"CSaleAffiliatePlanSection" => $DBType."/affiliate_plan_section.php",
		"CSaleAffiliateTier" => $DBType."/affiliate_tier.php",
		"CSaleAffiliateTransact" => $DBType."/affiliate_transact.php",
		"CSaleExport" => "general/export.php",
		"ExportOneCCRM" => "general/export.php",
		"CSaleOrderLoader" => "general/order_loader.php",

		"CSaleMeasure" => "general/measurement.php",
		"CSaleProduct" => $DBType."/product.php",

		"CSaleViewedProduct" => $DBType."/product.php",

		"CSaleHelper" => "general/helper.php",
		"CSaleMobileOrderUtils" => "general/mobile_order.php",
		"CSaleMobileOrderPull" => "general/mobile_order.php",
		"CSaleMobileOrderPush" => "general/mobile_order.php",
		"CSaleMobileOrderFilter" => "general/mobile_order.php",

		"CBaseSaleReportHelper" => "general/sale_report_helper.php",
		"CSaleReportSaleOrderHelper" => "general/sale_report_helper.php",
		"CSaleReportUserHelper" => "general/sale_report_helper.php",
		"CSaleReportSaleFuserHelper" => "general/sale_report_helper.php",

		"IBXSaleProductProvider" => "general/product_provider.php",
		"CSaleStoreBarcode" => $DBType."/store_barcode.php",

		"CSaleOrderChange" => $DBType."/order_change.php",
		"CSaleOrderChangeFormat" => "general/order_change.php",

		"\\Bitrix\\Sale\\Internals\\FuserTable" => "lib/internals/fuser.php",
		"\\Bitrix\\Sale\\Fuser" => "lib/fuser.php",

		// begin lists
		'\Bitrix\Sale\Internals\Input\Manager' => 'lib/internals/input.php',
		'\Bitrix\Sale\Internals\Input\Base'    => 'lib/internals/input.php',
		'\Bitrix\Sale\Internals\Input\File'    => 'lib/internals/input.php',
		'\Bitrix\Sale\Internals\Input\StringInput'    => 'lib/internals/input.php',

		'\Bitrix\Sale\Internals\SiteCurrencyTable' => 'lib/internals/sitecurrency.php',

		'CSaleStatus' => 'general/status.php',
		'\Bitrix\Sale\StatusBase' => 'lib/statusbase.php',
		'\Bitrix\Sale\OrderStatus' => 'lib/orderstatus.php',
		'\Bitrix\Sale\DeliveryStatus' => 'lib/deliverystatus.php',
		'\Bitrix\Sale\Internals\StatusTable' => 'lib/internals/status.php',
		'\Bitrix\Sale\Internals\StatusLangTable' => 'lib/internals/status_lang.php',
		'\Bitrix\Sale\Internals\StatusGroupTaskTable' => 'lib/internals/status_grouptask.php',
		'CSaleOrderProps'                                => 'general/order_props.php',
		'CSaleOrderPropsAdapter'                         => 'general/order_props.php',
		'CSaleOrderPropsValue'                           => $DBType.'/order_props_values.php',
		'\Bitrix\Sale\PropertyValueCollection'           => 'lib/propertyvaluecollection.php',
		'\Bitrix\Sale\Internals\OrderPropsTable'         => 'lib/internals/orderprops.php',
		'\Bitrix\Sale\Internals\OrderPropsGroupTable'    => 'lib/internals/orderprops_group.php',
		'\Bitrix\Sale\Internals\OrderPropsValueTable'    => 'lib/internals/orderprops_value.php',
		'\Bitrix\Sale\Internals\OrderPropsVariantTable'  => 'lib/internals/orderprops_variant.php',
		'\Bitrix\Sale\Internals\OrderPropsRelationTable' => 'lib/internals/orderprops_relation.php',
		'\Bitrix\Sale\Internals\UserPropsValueTable'     => 'lib/internals/userpropsvalue.php',
		'\Bitrix\Sale\Internals\UserPropsTable'          => 'lib/internals/userprops.php',
		'\Bitrix\Sale\BusinessValue'                            => 'lib/businessvalue.php',
		'\Bitrix\Sale\IBusinessValueProvider'                   => 'lib/businessvalueproviderinterface.php',
		'\Bitrix\Sale\Internals\BusinessValueTable'             => 'lib/internals/businessvalue.php',
		'\Bitrix\Sale\Internals\BusinessValuePersonDomainTable' => 'lib/internals/businessvalue_persondomain.php',
		'\Bitrix\Sale\Internals\BusinessValueCode1CTable'       => 'lib/internals/businessvalue_code_1c.php',
		'\Bitrix\Sale\Internals\PaySystemActionTable' => 'lib/internals/paysystemaction.php',
		'\Bitrix\Sale\Internals\PaySystemInner' => 'lib/internals/paysysteminner.php',
		'\Bitrix\Sale\Internals\DeliveryPaySystemTable' => 'lib/internals/delivery_paysystem.php',
		'\Bitrix\Sale\UserMessageException' => 'lib/exception.php',
		// end lists

		"\\Bitrix\\Sale\\Configuration" => "lib/configuration.php",
		"\\Bitrix\\Sale\\Order" => "lib/order.php",
		"\\Bitrix\\Sale\\PersonType" => "lib/persontype.php",

		"CSaleReportSaleGoodsHelper" => "general/sale_report_helper.php",
		"CSaleReportSaleProductHelper" => "general/sale_report_helper.php",

		"\\Bitrix\\Sale\\Internals\\ProductTable" => "lib/internals/product.php",
		"\\Bitrix\\Sale\\Internals\\GoodsSectionTable" => "lib/internals/goodssection.php",
		"\\Bitrix\\Sale\\Internals\\SectionTable" => "lib/internals/section.php",
		"\\Bitrix\\Sale\\Internals\\StoreProductTable" => "lib/internals/storeproduct.php",

		"\\Bitrix\\Sale\\SalesZone" => "lib/saleszone.php",
		"Bitrix\\Sale\\Internals\\OrderDeliveryReqTable" => "lib/internals/orderdeliveryreq.php",
		"\\Bitrix\\Sale\\Internals\\OrderDeliveryReqTable" => "lib/internals/orderdeliveryreq.php",

		"Bitrix\\Sale\\SenderEventHandler" => "lib/senderconnector.php",
		"Bitrix\\Sale\\SenderConnectorBuyer" => "lib/senderconnector.php",

		"\\Bitrix\\Sale\\UserConsent" => "lib/userconsent.php",

		"\\Bitrix\\Sale\\Internals\\Product2ProductTable" => "lib/internals/product2product.php",

		"Bitrix\\Sale\\Internals\\OrderProcessingTable" => "lib/internals/orderprocessing.php",

		"\\Bitrix\\Sale\\OrderBase" => "lib/orderbase.php",
		"\\Bitrix\\Sale\\Internals\\Entity" => "lib/internals/entity.php",
		"\\Bitrix\\Sale\\Internals\\EntityCollection" => "lib/internals/entitycollection.php",
		"\\Bitrix\\Sale\\Internals\\CollectionBase" => "lib/internals/collectionbase.php",

		"\\Bitrix\\Sale\\Shipment" => "lib/shipment.php",
		"\\Bitrix\\Sale\\ShipmentCollection" => "lib/shipmentcollection.php",
		"\\Bitrix\\Sale\\ShipmentItemCollection" => "lib/shipmentitemcollection.php",
		"\\Bitrix\\Sale\\ShipmentItem" => "lib/shipmentitem.php",
		"\\Bitrix\\Sale\\ShipmentItemStoreCollection" => "lib/shipmentitemstorecollection.php",
		"\\Bitrix\\Sale\\ShipmentItemStore" => "lib/shipmentitemstore.php",

		"\\Bitrix\\Sale\\PaymentCollectionBase" => "lib/internals/paymentcollectionbase.php",
		"\\Bitrix\\Sale\\PaymentCollection" => "lib/paymentcollection.php",
		"\\Bitrix\\Sale\\Payment" => "lib/payment.php",
		"\\Bitrix\\Sale\\PaysystemService" => "lib/paysystemservice.php",
		"\\Bitrix\\Sale\\Internals\\Fields" => "lib/internals/fields.php",
		"\\Bitrix\\Sale\\Result" => "lib/result.php",
		"\\Bitrix\\Sale\\ResultError" => "lib/result.php",
		"\\Bitrix\\Sale\\ResultSerializable" => "lib/resultserializable.php",
		"\\Bitrix\\Sale\\EventActions" => "lib/eventactions.php",

		"\\Bitrix\\Sale\\BasketBase" => "lib/basketbase.php",
		"\\Bitrix\\Sale\\BasketItemBase" => "lib/basketitembase.php",
		"\\Bitrix\\Sale\\Basket" => "lib/basket.php",

		"\\Bitrix\\Sale\\Internals\\BasketItemBase" => "lib/internals/basketitembase.php",
		"\\Bitrix\\Sale\\BasketItem" => "lib/basketitem.php",
		"\\Bitrix\\Sale\\BasketBundleCollection" => "lib/basketbundlecollection.php",

		"\\Bitrix\\Sale\\OrderProperties" => "lib/orderprops.php",
		"\\Bitrix\\Sale\\PropertyValue" => "lib/propertyvalue.php",

		"\\Bitrix\\Sale\\Compatible\\Internals\\EntityCompatibility" => "lib/compatible/internals/entitycompatibility.php",
		"\\Bitrix\\Sale\\Compatible\\OrderCompatibility" => "lib/compatible/ordercompatibility.php",
		"\\Bitrix\\Sale\\Compatible\\BasketCompatibility" => "lib/compatible/basketcompatibility.php",
		"\\Bitrix\\Sale\\Compatible\\EventCompatibility" => "lib/compatible/eventcompatibility.php",

		'\Bitrix\Sale\Compatible\OrderQuery'   => 'lib/compatible/compatible.php',
		'\Bitrix\Sale\Compatible\OrderQueryLocation'   => 'lib/compatible/compatible.php',
		'\Bitrix\Sale\Compatible\FetchAdapter' => 'lib/compatible/compatible.php',
		'\Bitrix\Sale\Compatible\Test'         => 'lib/compatible/compatible.php',

		"\\Bitrix\\Sale\\OrderUserProperties" => "lib/userprops.php",

		"\\Bitrix\\Sale\\BasketPropertiesCollectionBase" => "lib/basketpropertiesbase.php",
		"\\Bitrix\\Sale\\BasketPropertiesCollection" => "lib/basketproperties.php",
		"\\Bitrix\\Sale\\BasketPropertyItemBase" => "lib/basketpropertiesitembase.php",
		"\\Bitrix\\Sale\\BasketPropertyItem" => "lib/basketpropertiesitem.php",

		"\\Bitrix\\Sale\\Tax" => "lib/tax.php",

		"\\Bitrix\\Sale\\Internals\\OrderTable" => "lib/internals/order.php",

		"\\Bitrix\\Sale\\Internals\\BasketTable" => "lib/internals/basket.php",

		"\\Bitrix\\Sale\\Internals\\ShipmentTable" => "lib/internals/shipment.php",
		"\\Bitrix\\Sale\\Internals\\ShipmentItemTable" => "lib/internals/shipmentitem.php",

		"\\Bitrix\\Sale\\Internals\\PaySystemServiceTable" => "lib/internals/paysystemservice.php",
		"\\Bitrix\\Sale\\Internals\\PaymentTable" => "lib/internals/payment.php",

		"\\Bitrix\\Sale\\Internals\\ShipmentItemStoreTable" => "lib/internals/shipmentitemstore.php",
		"\\Bitrix\\Sale\\Internals\\ShipmentExtraService" => "lib/internals/shipmentextraservice.php",

		"\\Bitrix\\Sale\\Internals\\OrderUserPropertiesTable" => "lib/internals/userprops.php",

		"\\Bitrix\\Sale\\Internals\\CollectableEntity" => "lib/internals/collectableentity.php",

		"\\Bitrix\\Sale\\Provider" => "lib/provider.php",
		"\\Bitrix\\Sale\\ProviderBase" => "lib/providerbase.php",

		'\Bitrix\Sale\Internals\Catalog\Provider' => "lib/internals/catalog/provider.php",
		'\Bitrix\Sale\SaleProviderBase' => "lib/saleproviderbase.php",
		'Bitrix\Sale\SaleProviderBase' => "lib/saleproviderbase.php",
		'\Bitrix\Sale\Internals\TransferDataProvider' => "lib/internals/transferdataprovider.php",
		'\Bitrix\Sale\Internals\PoolQuantity' => "lib/internals/poolquantity.php",

		'\Bitrix\Sale\Internals\ProviderCreator' => "lib/internals/providercreator.php",
		'\Bitrix\Sale\Internals\ProviderBuilderBase' => "lib/internals/providerbuilderbase.php",
		'\Bitrix\Sale\Internals\ProviderBuilder' => "lib/internals/providerbuilder.php",
		'\Bitrix\Sale\Internals\ProviderBuilderCompatibility' => "lib/internals/providerbuildercompatibility.php",


		"\\Bitrix\\Sale\\OrderHistory" => "lib/orderhistory.php",

		'\Bitrix\Sale\Internals\CallbackRegistryTable' => "lib/internals/callbackregistry.php",

		"\\Bitrix\\Sale\\Internals\\BasketPropertyTable" => "lib/internals/basketproperties.php",
		"\\Bitrix\\Sale\\Internals\\CompanyTable" => "lib/internals/company.php",
		"\\Bitrix\\Sale\\Internals\\CompanyGroupTable" => "lib/internals/companygroup.php",
		"\\Bitrix\\Sale\\Internals\\CompanyResponsibleGroupTable" => "lib/internals/companyresponsiblegroup.php",

		"\\Bitrix\\Sale\\Internals\\PersonTypeTable" => "lib/internals/persontype.php",
		"\\Bitrix\\Sale\\Internals\\PersonTypeSiteTable" => "lib/internals/persontypesite.php",

		"\\Bitrix\\Sale\\Internals\\Pool" => "lib/internals/pool.php",
		"\\Bitrix\\Sale\\Internals\\UserBudgetPool" => "lib/internals/userbudgetpool.php",
		"\\Bitrix\\Sale\\Internals\\EventsPool" => "lib/internals/eventspool.php",
		"\\Bitrix\\Sale\\Internals\\Events" => "lib/internals/events.php",

		"\\Bitrix\\Sale\\PriceMaths" => "lib/pricemaths.php",
		"\\Bitrix\\Sale\\BasketComponentHelper" => "lib/basketcomponenthelper.php",
		"\\Bitrix\\Sale\\Registry" => "lib/registry.php",

		"IPaymentOrder" => "lib/internals/paymentinterface.php",
		"IShipmentOrder" => "lib/internals/shipmentinterface.php",
		"IEntityMarker" => "lib/internals/entitymarkerinterface.php",

		//archive
		"\\Bitrix\\Sale\\Internals\\OrderArchiveTable" => "lib/internals/orderarchive.php",
		"\\Bitrix\\Sale\\Internals\\BasketArchiveTable" => "lib/internals/basketarchive.php",
		"\\Bitrix\\Sale\\Internals\\OrderArchivePackedTable" => "lib/internals/orderarchivepacked.php",
		"\\Bitrix\\Sale\\Internals\\BasketArchivePackedTable" => "lib/internals/basketarchivepacked.php",
		"\\Bitrix\\Sale\\Archive\\Manager" => "lib/archive/manager.php",
		"\\Bitrix\\Sale\\Archive\\Recovery\\Base" => "lib/archive/recovery/base.php",
		"\\Bitrix\\Sale\\Archive\\Recovery\\Scheme" => "lib/archive/recovery/scheme.php",
		"\\Bitrix\\Sale\\Archive\\Recovery\\Version1" => "lib/archive/recovery/version1.php",


		"Bitrix\\Sale\\Tax\\RateTable" => "lib/tax/rate.php",

		////////////////////////////
		// new location 2.0
		////////////////////////////

		// data entities
		"Bitrix\\Sale\\Location\\LocationTable" => "lib/location/location.php",
		"Bitrix\\Sale\\Location\\Tree" => "lib/location/tree.php",
		"Bitrix\\Sale\\Location\\TypeTable" => "lib/location/type.php",
		"Bitrix\\Sale\\Location\\GroupTable" => "lib/location/group.php",
		"Bitrix\\Sale\\Location\\ExternalTable" => "lib/location/external.php",
		"Bitrix\\Sale\\Location\\ExternalServiceTable" => "lib/location/externalservice.php",

		// search
		"Bitrix\\Sale\\Location\\Search\\Finder" => "lib/location/search/finder.php",
		"Bitrix\\Sale\\Location\\Search\\WordTable" => "lib/location/search/word.php",
		"Bitrix\\Sale\\Location\\Search\\ChainTable" => "lib/location/search/chain.php",
		"Bitrix\\Sale\\Location\\Search\\SiteLinkTable" => "lib/location/search/sitelink.php",

		// lang entities
		"Bitrix\\Sale\\Location\\Name\\NameEntity" => "lib/location/name/nameentity.php",
		"Bitrix\\Sale\\Location\\Name\\LocationTable" => "lib/location/name/location.php",
		"Bitrix\\Sale\\Location\\Name\\TypeTable" => "lib/location/name/type.php",
		"Bitrix\\Sale\\Location\\Name\\GroupTable" => "lib/location/name/group.php",

		// connector from locations to other entities
		"Bitrix\\Sale\\Location\\Connector" => "lib/location/connector.php",

		// link entities
		"Bitrix\\Sale\\Location\\GroupLocationTable" => "lib/location/grouplocation.php",
		"Bitrix\\Sale\\Location\\SiteLocationTable" => "lib/location/sitelocation.php",
		"Bitrix\\Sale\\Location\\DefaultSiteTable" => "lib/location/defaultsite.php",

		// db util
		"Bitrix\\Sale\\Location\\DB\\CommonHelper" => "lib/location/db/commonhelper.php",
		"Bitrix\\Sale\\Location\\DB\\Helper" => "lib/location/db/".ToLower($DBType)."/helper.php",
		"Bitrix\\Sale\\Location\\DB\\BlockInserter" => "lib/location/db/blockinserter.php",

		// admin logic
		"Bitrix\\Sale\\Location\\Admin\\Helper" => "lib/location/admin/helper.php",
		"Bitrix\\Sale\\Location\\Admin\\NameHelper" => "lib/location/admin/namehelper.php",
		"Bitrix\\Sale\\Location\\Admin\\LocationHelper" => "lib/location/admin/locationhelper.php",
		"Bitrix\\Sale\\Location\\Admin\\TypeHelper" => "lib/location/admin/typehelper.php",
		"Bitrix\\Sale\\Location\\Admin\\GroupHelper" => "lib/location/admin/grouphelper.php",
		"Bitrix\\Sale\\Location\\Admin\\DefaultSiteHelper" => "lib/location/admin/defaultsitehelper.php",
		"Bitrix\\Sale\\Location\\Admin\\SiteLocationHelper" => "lib/location/admin/sitelocationhelper.php",
		"Bitrix\\Sale\\Location\\Admin\\ExternalServiceHelper" => "lib/location/admin/externalservicehelper.php",
		"Bitrix\\Sale\\Location\\Admin\\SearchHelper" => "lib/location/admin/searchhelper.php",


		// util
		"Bitrix\\Sale\\Location\\Util\\Process" => "lib/location/util/process.php",
		"Bitrix\\Sale\\Location\\Util\\CSVReader" => "lib/location/util/csvreader.php",
		"Bitrix\\Sale\\Location\\Util\\Assert" => "lib/location/util/assert.php",

		// processes for step-by-step actions
		"Bitrix\\Sale\\Location\\Import\\ImportProcess" => "lib/location/import/importprocess.php",
		"Bitrix\\Sale\\Location\\Search\\ReindexProcess" => "lib/location/search/reindexprocess.php",

		// exceptions
		"\\Bitrix\\Sale\\Location\\Tree\\NodeNotFoundException" => "lib/location/tree/exception.php",
		"\\Bitrix\\Sale\\Location\\Tree\\NodeIncorrectException" => "lib/location/tree/exception.php",
		"\\Bitrix\\Sale\\Location\\Exception" => "lib/location/exception.php",

		// old
		"CSaleProxyAdminResult" => "general/proxyadminresult.php", // for admin
		"CSaleProxyAdminUiResult" => "general/proxyadminresult.php",
		"CSaleProxyResult" => "general/proxyresult.php", // for public
		// other
		"Bitrix\\Sale\\Location\\Migration\\CUpdaterLocationPro" => "lib/location/migration/migrate.php", // class of migrations

		////////////////////////////
		// linked entities
		////////////////////////////

		"Bitrix\\Sale\\Delivery\\DeliveryLocationTable" => "lib/delivery/deliverylocation.php",
		"Bitrix\\Sale\\Delivery\\DeliveryLocationExcludeTable" => "lib/delivery/deliverylocationexclude.php",
		"Bitrix\\Sale\\Tax\\RateLocationTable" => "lib/tax/ratelocation.php",
		////////////////////////////

		"CSaleBasketFilter" => "general/sale_cond.php",
		"CSaleCondCtrl" => "general/sale_cond.php",
		"CSaleCondCtrlComplex" => "general/sale_cond.php",
		"CSaleCondCtrlGroup" => "general/sale_cond.php",
		"CSaleCondCtrlBasketGroup" => "general/sale_cond.php",
		"CSaleCondCtrlBasketFields" => "general/sale_cond.php",
		"CSaleCondCtrlBasketItemConditions" => "general/sale_cond.php",
		"CSaleCondCtrlBasketProperties" => "general/sale_cond.php",
		"CSaleCondCtrlOrderFields" => "general/sale_cond.php",
		"CSaleCondCtrlCommon" => "general/sale_cond.php",
		"CSaleCondTree" => "general/sale_cond.php",
		"CSaleCondCtrlPastOrder" => "general/sale_cond.php",
		"CSaleCondCumulativeCtrl" => "general/sale_cond.php",
		"CSaleCumulativeAction" => "general/sale_act.php",
		"CSaleActionCtrl" => "general/sale_act.php",
		"CSaleActionCtrlComplex" => "general/sale_act.php",
		"CSaleActionCtrlGroup" => "general/sale_act.php",
		"CSaleActionCtrlAction" => "general/sale_act.php",
		"CSaleDiscountActionApply" => "general/sale_act.php",
		"CSaleActionCtrlDelivery" => "general/sale_act.php",
		"CSaleActionGift" => "general/sale_act.php",
		"CSaleActionGiftCtrlGroup" => "general/sale_act.php",
		"CSaleActionCtrlBasketGroup" => "general/sale_act.php",
		"CSaleActionCtrlSubGroup" => "general/sale_act.php",
		"CSaleActionCondCtrlBasketFields" => "general/sale_act.php",
		"CSaleActionTree" => "general/sale_act.php",
		"CSaleDiscountConvert" => "general/discount_convert.php",

		"CSalePdf" => "general/pdf.php",
		"CSaleYMHandler" => "general/ym_handler.php",
		"CSaleYMLocation" => "general/ym_location.php",

		"Bitrix\\Sale\\Delivery\\CalculationResult" => "lib/delivery/calculationresult.php",
		"Bitrix\\Sale\\Delivery\\Services\\Table" => "lib/delivery/services/table.php",
		"Bitrix\\Sale\\Delivery\\Restrictions\\Table" => "lib/delivery/restrictions/table.php",
		"Bitrix\\Sale\\Delivery\\Services\\Manager" => "lib/delivery/services/manager.php",
		"Bitrix\\Sale\\Delivery\\Restrictions\\Base" => "lib/delivery/restrictions/base.php",
		"Bitrix\\Sale\\Delivery\\Restrictions\\Manager" => "lib/delivery/restrictions/manager.php",
		"Bitrix\\Sale\\Delivery\\Services\\Base" => "lib/delivery/services/base.php",
		"Bitrix\\Sale\\Delivery\\Menu" => "lib/delivery/menu.php",
		"Bitrix\\Sale\\Delivery\\Services\\ObjectPool" => "lib/delivery/services/objectpool.php",

		'\Bitrix\Sale\TradingPlatformTable' => 'lib/internals/tradingplatform.php',
		'\Bitrix\Sale\TradingPlatform\Ebay\Policy' => 'lib/tradingplatform/ebay/policy.php',
		'\Bitrix\Sale\TradingPlatform\Helper' => 'lib/tradingplatform/helper.php',
		'\Bitrix\Sale\TradingPlatform\YMarket\YandexMarket' => 'lib/tradingplatform/ymarket/yandexmarket.php',
		'\Bitrix\Sale\TradingPlatform\Platform' => 'lib/tradingplatform/platform.php',
		'\Bitrix\Sale\TradingPlatform\Logger' => 'lib/tradingplatform/logger.php',

		'Bitrix\Sale\Internals\ShipmentExtraServiceTable' => 'lib/internals/shipmentextraservice.php',
		'Bitrix\Sale\Delivery\ExtraServices\Manager' => 'lib/delivery/extra_services/manager.php',
		'Bitrix\Sale\Delivery\ExtraServices\Base' => 'lib/delivery/extra_services/base.php',
		'Bitrix\Sale\Delivery\ExtraServices\Table' => 'lib/delivery/extra_services/table.php',
		'Bitrix\Sale\Delivery\Tracking\Manager' => 'lib/delivery/tracking/manager.php',
		'Bitrix\Sale\Delivery\Tracking\Table' => 'lib/delivery/tracking/table.php',
		'Bitrix\Sale\Delivery\ExternalLocationMap' => 'lib/delivery/externallocationmap.php',

		'Bitrix\Sale\Internals\ServiceRestrictionTable' => 'lib/internals/servicerestriction.php',
		'Bitrix\Sale\Services\Base\RestrictionManager' => 'lib/services/base/restrictionmanager.php',
		'\Bitrix\Sale\Services\Base\SiteRestriction' => 'lib/services/base/siterestriction.php',
		'\Bitrix\Sale\Services\Base\TradeBindingRestriction' => 'lib/services/base/tradebindingrestriction.php',

		'\Bitrix\Sale\Compatible\DiscountCompatibility' => 'lib/compatible/discountcompatibility.php',
		'\Bitrix\Sale\Config\Feature' => 'lib/config/feature.php',
		'\Bitrix\Sale\Discount\Context\BaseContext' => 'lib/discount/context/basecontext.php',
		'\Bitrix\Sale\Discount\Context\Fuser' => 'lib/discount/context/fuser.php',
		'\Bitrix\Sale\Discount\Context\User' => 'lib/discount/context/user.php',
		'\Bitrix\Sale\Discount\Context\UserGroup' => 'lib/discount/context/usergroup.php',
		'\Bitrix\Sale\Discount\Gift\Collection' => 'lib/discount/gift/collection.php',
		'\Bitrix\Sale\Discount\Gift\Gift' => 'lib/discount/gift/gift.php',
		'\Bitrix\Sale\Discount\Gift\Manager' => 'lib/discount/gift/manager.php',
		'\Bitrix\Sale\Discount\Gift\RelatedDataTable' => 'lib/discount/gift/relateddata.php',
		'\Bitrix\Sale\Discount\Index\IndexElementTable' => 'lib/discount/index/indexelement.php',
		'\Bitrix\Sale\Discount\Index\IndexSectionTable' => 'lib/discount/index/indexsection.php',
		'\Bitrix\Sale\Discount\Index\Manager' => 'lib/discount/index/manager.php',
		'\Bitrix\Sale\Discount\Migration\OrderDiscountMigrator' => 'lib/discount/migration/orderdiscountmigrator.php',
		'\Bitrix\Sale\Discount\Prediction\Manager' => 'lib/discount/prediction/manager.php',
		'\Bitrix\Sale\Discount\Preset\ArrayHelper' => 'lib/discount/preset/arrayhelper.php',
		'\Bitrix\Sale\Discount\Preset\BasePreset' => 'lib/discount/preset/basepreset.php',
		'\Bitrix\Sale\Discount\Preset\HtmlHelper' => 'lib/discount/preset/htmlhelper.php',
		'\Bitrix\Sale\Discount\Preset\Manager' => 'lib/discount/preset/manager.php',
		'\Bitrix\Sale\Discount\Preset\SelectProductPreset' => 'lib/discount/preset/selectproductpreset.php',
		'\Bitrix\Sale\Discount\Preset\State' => 'lib/discount/preset/state.php',
		'\Bitrix\Sale\Discount\Result\CompatibleFormat' => 'lib/discount/result/compatibleformat.php',
		'\Bitrix\Sale\Discount\RuntimeCache\DiscountCache' => 'lib/discount/runtimecache/discountcache.php',
		'\Bitrix\Sale\Discount\RuntimeCache\FuserCache' => 'lib/discount/runtimecache/fusercache.php',
		'\Bitrix\Sale\Discount\Actions' => 'lib/discount/actions.php',
		'\Bitrix\Sale\Discount\Analyzer' => 'lib/discount/analyzer.php',
		'\Bitrix\Sale\Discount\CumulativeCalculator' => 'lib/discount/cumulativecalculator.php',
		'\Bitrix\Sale\Discount\Formatter' => 'lib/discount/formatter.php',
		'\Bitrix\Sale\Internals\DiscountTable' => 'lib/internals/discount.php',
		'\Bitrix\Sale\Internals\DiscountCouponTable' => 'lib/internals/discountcoupon.php',
		'\Bitrix\Sale\Internals\DiscountEntitiesTable' => 'lib/internals/discountentities.php',
		'\Bitrix\Sale\Internals\DiscountGroupTable' => 'lib/internals/discountgroup.php',
		'\Bitrix\Sale\Internals\DiscountModuleTable' => 'lib/internals/discountmodule.php',
		'\Bitrix\Sale\Internals\OrderDiscountTable' => 'lib/internals/orderdiscount.php',
		'\Bitrix\Sale\Internals\OrderDiscountDataTable' => 'lib/internals/orderdiscount.php',
		'\Bitrix\Sale\Internals\OrderCouponsTable' => 'lib/internals/orderdiscount.php',
		'\Bitrix\Sale\Internals\OrderModulesTable' => 'lib/internals/orderdiscount.php',
		'\Bitrix\Sale\Internals\OrderRoundTable' => 'lib/internals/orderround.php',
		'\Bitrix\Sale\Internals\OrderRulesTable' => 'lib/internals/orderdiscount.php',
		'\Bitrix\Sale\Internals\OrderRulesDescrTable' => 'lib/internals/orderdiscount.php',
		'\Bitrix\Sale\Internals\AccountNumberGenerator' => 'lib/internals/accountnumber.php',
		'\Bitrix\Sale\Discount' => 'lib/discount.php',
		'\Bitrix\Sale\DiscountBase' => 'lib/discountbase.php',
		'\Bitrix\Sale\DiscountCouponsManager' => 'lib/discountcouponsmanager.php',
		'\Bitrix\Sale\DiscountCouponsManagerBase' => 'lib/discountcouponsmanagerbase.php',
		'\Bitrix\Sale\OrderDiscount' => 'lib/orderdiscount.php',
		'\Bitrix\Sale\OrderDiscountBase' => 'lib/orderdiscountbase.php',
		'\Bitrix\Sale\OrderDiscountManager' => 'lib/orderdiscountmanager.php',

		'\Bitrix\Sale\PaySystem\Logger' => 'lib/paysystem/logger.php',
		'\Bitrix\Sale\PaySystem\RestService' => 'lib/paysystem/restservice.php',
		'\Bitrix\Sale\PaySystem\RestHandler' => 'lib/paysystem/resthandler.php',
		'\Bitrix\Sale\Services\Base\RestClient' => 'lib/services/base/restclient.php',
		'\Bitrix\Sale\PaySystem\Service' => 'lib/paysystem/service.php',
		'\Bitrix\Sale\Internals\PaySystemRestHandlersTable' => 'lib/internals/paysystemresthandlers.php',
		'\Bitrix\Sale\PaySystem\Manager' => 'lib/paysystem/manager.php',
		'\Bitrix\Sale\PaySystem\BaseServiceHandler' => 'lib/paysystem/baseservicehandler.php',
		'\Bitrix\Sale\PaySystem\ServiceHandler' => 'lib/paysystem/servicehandler.php',
		'\Bitrix\Sale\PaySystem\IRefund' => 'lib/paysystem/irefund.php',
		'\Bitrix\Sale\PaySystem\IPdf' => 'lib/paysystem/ipdf.php',
		'\Bitrix\Sale\PaySystem\IRequested' => 'lib/paysystem/irequested.php',
		'\Bitrix\Sale\PaySystem\IRefundExtended' => 'lib/paysystem/irefundextended.php',
		'\Bitrix\Sale\PaySystem\Cert' => 'lib/paysystem/cert.php',
		'\Bitrix\Sale\PaySystem\IPayable' => 'lib/paysystem/ipayable.php',
		'\Bitrix\Sale\PaySystem\ICheckable' => 'lib/paysystem/icheckable.php',
		'\Bitrix\Sale\PaySystem\IPrePayable' => 'lib/paysystem/iprepayable.php',
		'\Bitrix\Sale\PaySystem\CompatibilityHandler' => 'lib/paysystem/compatibilityhandler.php',
		'\Bitrix\Sale\PaySystem\IHold' => 'lib/paysystem/ihold.php',
		'\Bitrix\Sale\PaySystem\IPartialHold' => 'lib/paysystem/ipartialhold.php',
		'\Bitrix\Sale\Internals\PaymentLogTable' => 'lib/internals/paymentlog.php',
		'\Bitrix\Sale\Services\PaySystem\Restrictions\Manager' => 'lib/services/paysystem/restrictions/manager.php',
		'\Bitrix\Sale\Services\Base\Restriction' => 'lib/services/base/restriction.php',
		'\Bitrix\Sale\Services\Base\RestrictionManager' => 'lib/services/base/restrictionmanager.php',
		'\Bitrix\sale\Internals\YandexSettingsTable' => 'lib/internals/yandexsettings.php',

		'\Bitrix\Sale\Services\Company\Manager' => 'lib/services/company/manager.php',
		'\Bitrix\Sale\Internals\CollectionFilterIterator' => 'lib/internals/collectionfilteriterator.php',

		'\Bitrix\Sale\Cashbox\Internals\Pool' => 'lib/cashbox/internals/pool.php',
		'\Bitrix\Sale\Cashbox\Internals\CashboxTable' => 'lib/cashbox/internals/cashbox.php',
		'\Bitrix\Sale\Cashbox\Internals\CashboxCheckTable' => 'lib/cashbox/internals/cashboxcheck.php',
		'\Bitrix\Sale\Cashbox\Internals\CashboxZReportTable' => 'lib/cashbox/internals/cashboxzreport.php',
		'\Bitrix\Sale\Cashbox\Internals\CashboxErrLogTable' => 'lib/cashbox/internals/cashboxerrlog.php',
		'\Bitrix\Sale\Cashbox\Cashbox' => 'lib/cashbox/cashbox.php',
		'\Bitrix\Sale\Cashbox\Manager' => 'lib/cashbox/manager.php',
		'\Bitrix\Sale\Cashbox\IPrintImmediately' => 'lib/cashbox/iprintimmediately.php',
		'\Bitrix\Sale\Cashbox\Restrictions\Manager' => 'lib/cashbox/restrictions/manager.php',

		'\Bitrix\Sale\Notify' => 'lib/notify.php',
		'\Bitrix\Sale\BuyerStatistic'=> '/lib/buyerstatistic.php',
		'\Bitrix\Sale\Internals\BuyerStatisticTable'=> '/lib/internals/buyerstatistic.php',

		'CAdminSaleList' => 'general/admin_lib.php',
		'\Bitrix\Sale\Helpers\Admin\SkuProps' => 'lib/helpers/admin/skuprops.php',
		'\Bitrix\Sale\Helpers\Admin\Product' => 'lib/helpers/admin/product.php',
		'\Bitrix\Sale\Helpers\Order' => 'lib/helpers/order.php',
		'\Bitrix\Sale\Location\Comparator\Replacement' => 'lib/location/comparator/ru/replacement.php',
		'\Bitrix\Sale\Location\Comparator\TmpTable' => 'lib/location/comparator/tmptable.php',
		'\Bitrix\Sale\Location\Comparator' => 'lib/location/comparator.php',
		'\Bitrix\Sale\Location\Comparator\MapResult' => 'lib/location/comparator/mapresult.php',
		'\Bitrix\Sale\Location\Comparator\Mapper' => 'lib/location/comparator/mapper.php',

		'\Bitrix\Sale\Exchange\OneC\DocumentImport' => '/lib/exchange/compatibility/documents.php',

		'\Bitrix\Sale\Exchange\OneC\CollisionOrder' => '/lib/exchange/onec/importcollision.php',
		'\Bitrix\Sale\Exchange\OneC\CollisionShipment' => '/lib/exchange/onec/importcollision.php',
		'\Bitrix\Sale\Exchange\OneC\CollisionPayment' => '/lib/exchange/onec/importcollision.php',
		'\Bitrix\Sale\Exchange\OneC\CollisionProfile' => '/lib/exchange/onec/importcollision.php',
		'\Bitrix\Sale\Exchange\OneC\PaymentCashDocument'=> '/lib/exchange/onec/paymentdocument.php',
		'\Bitrix\Sale\Exchange\OneC\PaymentCashLessDocument'=> '/lib/exchange/onec/paymentdocument.php',
		'\Bitrix\Sale\Exchange\OneC\PaymentCardDocument'=> '/lib/exchange/onec/paymentdocument.php',
		'\Bitrix\Sale\Exchange\OneC\ImportCriterionBase' => '/lib/exchange/onec/importcriterion.php',
		'\Bitrix\Sale\Exchange\OneC\ImportCriterionOneCCml2' => '/lib/exchange/onec/importcriterion.php',
		'\Bitrix\Sale\Exchange\OneC\CriterionOrder' => '/lib/exchange/onec/importcriterion.php',
		'\Bitrix\Sale\Exchange\OneC\CriterionShipment' => '/lib/exchange/onec/importcriterion.php',
		'\Bitrix\Sale\Exchange\OneC\CriterionShipmentInvoice' => '/lib/exchange/onec/importcriterion.php',
		'\Bitrix\Sale\Exchange\OneC\CriterionPayment' => '/lib/exchange/onec/importcriterion.php',
		'\Bitrix\Sale\Exchange\OneC\CriterionProfile' => '/lib/exchange/onec/importcriterion.php',
		'\Bitrix\Sale\Exchange\Entity\OrderImportLoader'=> '/lib/exchange/entity/entityimportloader.php',
		'\Bitrix\Sale\Exchange\Entity\InvoiceImportLoader'=> '/lib/exchange/entity/entityimportloader.php',
		'\Bitrix\Sale\Exchange\Entity\PaymentImportLoader'=> '/lib/exchange/entity/entityimportloader.php',
		'\Bitrix\Sale\Exchange\Entity\ShipmentImportLoader'=> '/lib/exchange/entity/entityimportloader.php',
		'\Bitrix\Sale\Exchange\Entity\UserProfileImportLoader'=> '/lib/exchange/entity/entityimportloader.php',
		'\Bitrix\Sale\Exchange\Entity\PaymentCashLessImport'=> '/lib/exchange/entity/paymentimport.php',
		'\Bitrix\Sale\Exchange\Entity\PaymentCardImport'=> '/lib/exchange/entity/paymentimport.php',
		'\Bitrix\Sale\Exchange\Entity\PaymentCashImport'=> '/lib/exchange/entity/paymentimport.php',

		'\Bitrix\Sale\Location\GeoIp' => '/lib/location/geoip.php',

		'\Bitrix\Sale\Delivery\Requests\Manager' => '/lib/delivery/requests/manager.php',
		'\Bitrix\Sale\Delivery\Requests\Helper' => '/lib/delivery/requests/helper.php',
		'\Bitrix\Sale\Delivery\Requests\HandlerBase' => '/lib/delivery/requests/handlerbase.php',
		'\Bitrix\Sale\Delivery\Requests\RequestTable' => '/lib/delivery/requests/request.php',
		'\Bitrix\Sale\Delivery\Requests\ShipmentTable' => '/lib/delivery/requests/shipment.php',
		'\Bitrix\Sale\Delivery\Requests\Result' => '/lib/delivery/requests/result.php',
		'\Bitrix\Sale\Delivery\Requests\ResultFile' => '/lib/delivery/requests/resultfile.php',

		'\Bitrix\Sale\Delivery\Packing\Packer' => '/lib/delivery/packing/packer.php',

		'\Bitrix\Sale\Recurring' => '/lib/recurring.php',

		'\Bitrix\Sale\Location\Normalizer\Builder' => '/lib/location/normalizer/builder.php',
		'\Bitrix\Sale\Location\Normalizer\IBuilder' => '/lib/location/normalizer/ibuilder.php',
		'\Bitrix\Sale\Location\Normalizer\Normalizer' => '/lib/location/normalizer/normalizer.php',
		'\Bitrix\Sale\Location\Normalizer\INormalizer' => '/lib/location/normalizer/inormalizer.php',
		'\Bitrix\Sale\Location\Normalizer\CommonNormalizer' => '/lib/location/normalizer/commonnormalizer.php',
		'\Bitrix\Sale\Location\Normalizer\NullNormalizer' => '/lib/location/normalizer/nullnormalizer.php',
		'\Bitrix\Sale\Location\Normalizer\SpaceNormalizer' => '/lib/location/normalizer/spacenormalizer.php',
		'\Bitrix\Sale\Location\Normalizer\LanguageNormalizer' => '/lib/location/normalizer/languagenormalizer.php',
		'\Bitrix\Sale\Location\Normalizer\Helper' => '/lib/location/normalizer/helper.php',

		'\Sale\Handlers\Delivery\Additional\RusPost\Reliability\Service' => '/handlers/delivery/additional/ruspost/reliability/service.php'
	)
);

class_alias('Bitrix\Sale\TradingPlatform\YMarket\YandexMarket', 'Bitrix\Sale\TradingPlatform\YandexMarket');
class_alias('\Bitrix\Sale\PaySystem\Logger', '\Bitrix\Sale\PaySystem\ErrorLog');
class_alias('\Bitrix\Sale\Internals\OrderTable', '\Bitrix\Sale\OrderTable');
class_alias('\Bitrix\Sale\Internals\FuserTable', '\Bitrix\Sale\FuserTable');
class_alias('\Bitrix\Sale\Internals\Product2ProductTable', '\Bitrix\Sale\Product2ProductTable');
class_alias('\Bitrix\Sale\Internals\StoreProductTable', '\Bitrix\Sale\StoreProductTable');
class_alias('\Bitrix\Sale\Internals\PersonTypeTable', '\Bitrix\Sale\PersonTypeTable');
class_alias('\Bitrix\Sale\Internals\ProductTable', '\Bitrix\Sale\ProductTable');
class_alias('\Bitrix\Sale\Internals\SectionTable', '\Bitrix\Sale\SectionTable');
class_alias('\Bitrix\Sale\Internals\OrderProcessingTable', '\Bitrix\Sale\OrderProcessingTable');
class_alias('\Bitrix\Sale\Internals\GoodsSectionTable', '\Bitrix\Sale\GoodsSectionTable');

$psConverted = \Bitrix\Main\Config\Option::get('main', '~sale_paysystem_converted');
if ($psConverted == '')
{
	CAdminNotify::Add(
		array(
			"MESSAGE" => GetMessage("SALE_PAYSYSTEM_CONVERT_ERROR", array('#LANG#' => LANGUAGE_ID)),
			"TAG" => "SALE_PAYSYSTEM_CONVERT_ERROR",
			"MODULE_ID" => "sale",
			"ENABLE_CLOSE" => "Y",
			"PUBLIC_SECTION" => "N"
		)
	);
}

function GetBasketListSimple($bSkipFUserInit = true)
{
	$fUserID = (int)CSaleBasket::GetBasketUserID($bSkipFUserInit);
	if ($fUserID > 0)
		return CSaleBasket::GetList(
			array("NAME" => "ASC"),
			array("FUSER_ID" => $fUserID, "LID" => SITE_ID, "ORDER_ID" => "NULL")
		);
	else
		return False;
}

function GetBasketList($bSkipFUserInit = true)
{
	$fUserID = (int)CSaleBasket::GetBasketUserID($bSkipFUserInit);
	$arRes = array();
	if ($fUserID > 0)
	{
		$basketID = array();
		$db_res = CSaleBasket::GetList(
			array(),
			array("FUSER_ID" => $fUserID, "LID" => SITE_ID, "ORDER_ID" => false),
			false,
			false,
			array('ID', 'CALLBACK_FUNC', 'PRODUCT_PROVIDER_CLASS', 'MODULE', 'PRODUCT_ID', 'QUANTITY', 'NOTES')
		);
		while ($res = $db_res->Fetch())
		{
			$res['CALLBACK_FUNC'] = (string)$res['CALLBACK_FUNC'];
			$res['PRODUCT_PROVIDER_CLASS'] = (string)$res['PRODUCT_PROVIDER_CLASS'];
			if ($res['CALLBACK_FUNC'] != '' || $res['PRODUCT_PROVIDER_CLASS'] != '')
				CSaleBasket::UpdatePrice($res["ID"], $res["CALLBACK_FUNC"], $res["MODULE"], $res["PRODUCT_ID"], $res["QUANTITY"], 'N', $res["PRODUCT_PROVIDER_CLASS"], $res['NOTES']);
			$basketID[] = $res['ID'];
		}
		unset($res, $db_res);
		if (!empty($basketID))
		{
			$basketIterator = CSaleBasket::GetList(
				array('NAME' => 'ASC'),
				array('ID' => $basketID)
			);
			while ($basket = $basketIterator->GetNext())
				$arRes[] = $basket;
			unset($basket, $basketIterator);
		}
		unset($basketID);
	}
	return $arRes;
}

function SaleFormatCurrency($fSum, $strCurrency, $OnlyValue = false, $withoutFormat = false)
{
	if ($withoutFormat === true)
	{
		if ($fSum === '')
			return '';

		$currencyFormat = CCurrencyLang::GetFormatDescription($strCurrency);
		if ($currencyFormat === false)
		{
			$currencyFormat = CCurrencyLang::GetDefaultValues();
		}

		$intDecimals = $currencyFormat['DECIMALS'];
		if (round($fSum, $currencyFormat["DECIMALS"]) == round($fSum, 0))
			$intDecimals = 0;

		return number_format($fSum, $intDecimals, '.','');
	}

	return CCurrencyLang::CurrencyFormat($fSum, $strCurrency, !($OnlyValue === true));
}

function AutoPayOrder($ORDER_ID)
{
	$ORDER_ID = (int)$ORDER_ID;
	if ($ORDER_ID <= 0)
		return false;

	$arOrder = CSaleOrder::GetByID($ORDER_ID);
	if (!$arOrder)
		return false;
	if ($arOrder["PS_STATUS"] != "Y")
		return false;
	if ($arOrder["PAYED"] != "N")
		return false;

	if ($arOrder["CURRENCY"] == $arOrder["PS_CURRENCY"]
		&& DoubleVal($arOrder["PRICE"]) == DoubleVal($arOrder["PS_SUM"]))
	{
		if (CSaleOrder::PayOrder($arOrder["ID"], "Y", true, false))
			return true;
	}

	return false;
}

function CurrencyModuleUnInstallSale()
{
	$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SALE_INCLUDE_CURRENCY"), "SALE_DEPENDES_CURRENCY");
	return false;
}

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/ru/include.php"))
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/ru/include.php");

function PayUserAccountDeliveryOrderCallback($productID, $userID, $bPaid, $orderID, $quantity = 1)
{
	global $DB;

	$productID = IntVal($productID);
	$userID = IntVal($userID);
	$bPaid = ($bPaid ? True : False);
	$orderID = IntVal($orderID);

	if ($userID <= 0)
		return False;

	if ($orderID <= 0)
		return False;

	if (!($arOrder = CSaleOrder::GetByID($orderID)))
		return False;

	$baseLangCurrency = CSaleLang::GetLangCurrency($arOrder["LID"]);
	$arAmount = unserialize(COption::GetOptionString("sale", "pay_amount", 'a:4:{i:1;a:2:{s:6:"AMOUNT";s:2:"10";s:8:"CURRENCY";s:3:"EUR";}i:2;a:2:{s:6:"AMOUNT";s:2:"20";s:8:"CURRENCY";s:3:"EUR";}i:3;a:2:{s:6:"AMOUNT";s:2:"30";s:8:"CURRENCY";s:3:"EUR";}i:4;a:2:{s:6:"AMOUNT";s:2:"40";s:8:"CURRENCY";s:3:"EUR";}}'));
	if (!array_key_exists($productID, $arAmount))
		return False;

	$currentPrice = $arAmount[$productID]["AMOUNT"] * $quantity;
	$currentCurrency = $arAmount[$productID]["CURRENCY"];
	if ($arAmount[$productID]["CURRENCY"] != $baseLangCurrency)
	{
		$currentPrice = CCurrencyRates::ConvertCurrency($arAmount[$productID]["AMOUNT"], $arAmount[$productID]["CURRENCY"], $baseLangCurrency) * $quantity;
		$currentCurrency = $baseLangCurrency;
	}

	if (!CSaleUserAccount::UpdateAccount($userID, ($bPaid ? $currentPrice : -$currentPrice), $currentCurrency, "MANUAL", $orderID, "Payment to user account"))
		return False;

	return True;
}

/*
* Formats user name. Used everywhere in 'sale' module
*
*/
function GetFormatedUserName($userId, $bEnableId = true, $createEditLink = true)
{
	static $formattedUsersName = array();
	static $siteNameFormat = '';

	$result = (!is_array($userId)) ? '' : array();
	$newUsers = array();

	if (is_array($userId))
	{
		foreach ($userId as $id)
		{
			if (!isset($formattedUsersName[$id]))
				$newUsers[] = $id;
		}
	}
	else if(!isset($formattedUsersName[$userId]))
	{
		$newUsers[] = $userId;
	}

	if (count($newUsers) > 0)
	{
		$resUsers = \Bitrix\Main\UserTable::getList(
			array(
				'select' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'EMAIL'),
				'filter' => array('ID' => $newUsers)
			)
		);
		while ($arUser = $resUsers->Fetch())
		{
			if (strlen($siteNameFormat) == 0)
				$siteNameFormat = CSite::GetNameFormat(false);
			$formattedUsersName[$arUser['ID']] = CUser::FormatName($siteNameFormat, $arUser, true, true);
		}
	}

	$publicMode = (defined("PUBLIC_MODE") && PUBLIC_MODE == 1);
	$selfFolderUrl = (defined("SELF_FOLDER_URL") ? SELF_FOLDER_URL : "/bitrix/admin/");
	if ($publicMode)
	{
		$bEnableId = false;
		global $adminSidePanelHelper;
		if (!is_object($adminSidePanelHelper))
		{
			require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/admin_lib.php");
			$adminSidePanelHelper = new CAdminSidePanelHelper();
		}
	}
	if (is_array($userId))
	{
		foreach ($userId as $uId)
		{
			if (CBXFeatures::IsFeatureEnabled('SaleAccounts') && !$createEditLink)
			{
				$userUrl = $selfFolderUrl."sale_buyers_profile.php?USER_ID=".$uId."&lang=".LANGUAGE_ID;
			}
			else
			{
				$userUrl = $selfFolderUrl."user_edit.php?ID=".$uId."&lang=".LANGUAGE_ID;
			}
			if ($publicMode)
			{
				$userUrl = $adminSidePanelHelper->editUrlToPublicPage($userUrl);
			}
			$formatted = '';
			if ($bEnableId)
				$formatted = '[<a href="/bitrix/admin/user_edit.php?ID='.$uId.'&lang='.LANGUAGE_ID.'">'.$uId.'</a>] ';

			$formatted .= '<a href="'.$userUrl.'">';
			$formatted .= $formattedUsersName[$uId];

			$formatted .= '</a>';

			$result[$uId] = $formatted;
		}
	}
	else
	{
		if ($bEnableId)
			$result .= '[<a href="/bitrix/admin/user_edit.php?ID='.$userId.'&lang='.LANGUAGE_ID.'">'.$userId.'</a>] ';

		if (CBXFeatures::IsFeatureEnabled('SaleAccounts') && !$createEditLink)
		{
			$userUrl = $selfFolderUrl."sale_buyers_profile.php?USER_ID=".$userId."&lang=".LANGUAGE_ID;
		}
		else
		{
			$userUrl = $selfFolderUrl."user_edit.php?ID=".$userId."&lang=".LANGUAGE_ID;
		}
		if ($publicMode)
		{
			$userUrl = $adminSidePanelHelper->editUrlToPublicPage($userUrl);
		}

		$result .= '<a href="'.$userUrl.'">';

		$result .= $formattedUsersName[$userId];

		$result .= '</a>';
	}

	return $result;
}

/*
 * Updates basket item arrays with information about measures from catalog
 * Basically adds MEASURE_TEXT field with the measure name to each basket item array
 *
 * @param array $arBasketItems - array of basket items' arrays
 * @return array|bool
 */
function getMeasures($arBasketItems)
{
	static $measures = array();
	$newMeasure = array();
	if (Loader::includeModule('catalog'))
	{
		$arDefaultMeasure = CCatalogMeasure::getDefaultMeasure(true, true);
		$arElementId = array();
		$basketLinks = array();
		foreach ($arBasketItems as $keyBasket => $arItem)
		{
			if (isset($arItem['MEASURE_NAME']) && strlen($arItem['MEASURE_NAME']) > 0)
			{
				$measureText = $arItem['MEASURE_NAME'];
				$measureCode = intval($arItem['MEASURE_CODE']);
			}
			else
			{
				$productID = (int)$arItem["PRODUCT_ID"];
				if (!isset($basketLinks[$productID]))
					$basketLinks[$productID] = array();
				$basketLinks[$productID][] = $keyBasket;
				$arElementId[] = $productID;

				$measureText = $arDefaultMeasure['~SYMBOL_RUS'];
				$measureCode = 0;
			}

			$arBasketItems[$keyBasket]['MEASURE_TEXT'] = $measureText;
			$arBasketItems[$keyBasket]['MEASURE'] = $measureCode;
		}
		unset($productID, $keyBasket, $arItem);

		if (!empty($arElementId))
		{
			$arBasket2Measure = array();
			$dbres = CCatalogProduct::GetList(
				array(),
				array("ID" => $arElementId),
				false,
				false,
				array("ID", "MEASURE")
			);
			while ($arRes = $dbres->Fetch())
			{
				$arRes['ID'] = (int)$arRes['ID'];
				$arRes['MEASURE'] = (int)$arRes['MEASURE'];
				if ($arRes['MEASURE'] <= 0)
					continue;
				if (!isset($arBasket2Measure[$arRes['MEASURE']]))
					$arBasket2Measure[$arRes['MEASURE']] = array();
				$arBasket2Measure[$arRes['MEASURE']][] = $arRes['ID'];

				if (!isset($measures[$arRes['MEASURE']]) && !in_array($arRes['MEASURE'], $newMeasure))
					$newMeasure[] = $arRes['MEASURE'];
			}
			unset($arRes, $dbres);

			if (!empty($newMeasure))
			{
				$dbMeasure = CCatalogMeasure::GetList(
					array(),
					array("ID" => array_values($newMeasure)),
					false,
					false,
					array('ID', 'SYMBOL_RUS', 'CODE')
				);
				while ($arMeasure = $dbMeasure->Fetch())
					$measures[$arMeasure['ID']] = $arMeasure;
			}

			foreach ($arBasket2Measure as $measureId => $productIds)
			{
				if (!isset($measures[$measureId]))
					continue;
				foreach ($productIds as $productId)
				{
					if (isset($basketLinks[$productId]) && !empty($basketLinks[$productId]))
					{
						foreach ($basketLinks[$productId] as $keyBasket)
						{
							$arBasketItems[$keyBasket]['MEASURE_TEXT'] = $measures[$measureId]['SYMBOL_RUS'];
							$arBasketItems[$keyBasket]['MEASURE'] = $measures[$measureId]['ID'];
						}
					}
				}
			}
		}
	}
	return $arBasketItems;
}

/*
 * Updates basket items' arrays with information about ratio from catalog
 * Basically adds MEASURE_RATIO field with the ratio coefficient to each basket item array
 *
 * @param array $arBasketItems - array of basket items' arrays
 * @return mixed
 */
function getRatio($arBasketItems)
{
	if (Loader::includeModule('catalog'))
	{
		static $cacheRatio = array();

		$helperCacheRatio = \Bitrix\Sale\BasketComponentHelper::getRatioDataCache();
		if (is_array($helperCacheRatio) && !empty($helperCacheRatio))
		{
			$cacheRatio = array_merge($cacheRatio, $helperCacheRatio);
		}

		$map = array();
		$arElementId = array();
		foreach ($arBasketItems as $key => $arItem)
		{
			if (
				(isset($arBasketItems[$key]['MEASURE_RATIO_VALUE']) && (float)$arBasketItems[$key]['MEASURE_RATIO_VALUE'] > 0)
				&& (isset($arBasketItems[$key]['MEASURE_RATIO_ID']) && (int)$arBasketItems[$key]['MEASURE_RATIO_ID'] > 0)
			)
				continue;

			$hash = md5((!empty($arItem['PRODUCT_PROVIDER_CLASS']) ? $arItem['PRODUCT_PROVIDER_CLASS']: "")."|".(!empty($arItem['MODULE']) ? $arItem['MODULE']: "")."|".$arItem["PRODUCT_ID"]);

			if (isset($cacheRatio[$hash]))
			{
				if (isset($cacheRatio[$hash]['RATIO']))
				{
					$arBasketItems[$key]["MEASURE_RATIO"] = $cacheRatio[$hash]['RATIO']; // old key
					$arBasketItems[$key]["MEASURE_RATIO_VALUE"] = $cacheRatio[$hash]["RATIO"];
				}

				if (isset($cacheRatio[$hash]['ID']))
				{
					$arBasketItems[$key]["MEASURE_RATIO_ID"] = $cacheRatio[$hash]["ID"];
				}

			}
			else
			{
				$arElementId[$arItem["PRODUCT_ID"]] = $arItem["PRODUCT_ID"];
			}

			if (!isset($map[$arItem["PRODUCT_ID"]]))
			{
				$map[$arItem["PRODUCT_ID"]] = array();
			}

			$map[$arItem["PRODUCT_ID"]][] = $key;
		}

		if (!empty($arElementId))
		{
			$dbRatio = \Bitrix\Catalog\MeasureRatioTable::getList(array(
				'select' => array('*'),
				'filter' => array('@PRODUCT_ID' => $arElementId, '=IS_DEFAULT' => 'Y')
			));
			while ($arRatio = $dbRatio->fetch())
			{
				if (empty($map[$arRatio["PRODUCT_ID"]]))
					continue;

				foreach ($map[$arRatio["PRODUCT_ID"]] as $key)
				{
					$arBasketItems[$key]["MEASURE_RATIO"] = $arRatio["RATIO"]; // old key
					$arBasketItems[$key]["MEASURE_RATIO_ID"] = $arRatio["ID"];
					$arBasketItems[$key]["MEASURE_RATIO_VALUE"] = $arRatio["RATIO"];

					$itemData = $arBasketItems[$key];

					$hash = md5((!empty($itemData['PRODUCT_PROVIDER_CLASS']) ? $itemData['PRODUCT_PROVIDER_CLASS']: "")."|".(!empty($itemData['MODULE']) ? $itemData['MODULE']: "")."|".$itemData["PRODUCT_ID"]);

					$cacheRatio[$hash] = $arRatio;
				}
				unset($key);
			}
			unset($arRatio, $dbRatio);
		}
		unset($arElementId, $map);
	}
	return $arBasketItems;
}

/*
 * Creates an array of iblock properties for the elements with certain IDs
 *
 * @param array $arElementId - array of element id
 * @param array $arSelect - properties to select
 * @return array - array of properties' values in the form of array("ELEMENT_ID" => array of props)
 */
function getProductProps($arElementId, $arSelect)
{
	if (!Loader::includeModule("iblock"))
		return array();

	if (empty($arElementId))
		return array();

	$arSelect = array_filter($arSelect, 'checkProductPropCode');
	foreach (array_keys($arSelect) as $index)
	{
		if (substr($arSelect[$index], 0, 9) === 'PROPERTY_')
		{
			if (substr($arSelect[$index], -6) === '_VALUE')
				$arSelect[$index] = substr($arSelect[$index], 0, -6);
		}
	}
	unset($index);

	$arProductData = array();
	$arElementData = array();
	$res = CIBlockElement::GetList(
		array(),
		array("=ID" => array_unique($arElementId)),
		false,
		false,
		array("ID", "IBLOCK_ID")
	);
	while ($arElement = $res->Fetch())
		$arElementData[$arElement["IBLOCK_ID"]][] = $arElement["ID"]; // two getlists are used to support 1 and 2 type of iblock properties

	foreach ($arElementData as $iblockId => $arElemId) // todo: possible performance bottleneck
	{
		$res = CIBlockElement::GetList(
			array(),
			array("IBLOCK_ID" => $iblockId, "=ID" => $arElemId),
			false,
			false,
			$arSelect
		);
		while ($arElement = $res->GetNext())
		{
			$id = $arElement["ID"];
			foreach ($arElement as $key => $value)
			{
				if (!isset($arProductData[$id]))
					$arProductData[$id] = array();

				if (isset($arProductData[$id][$key])
					&& !is_array($arProductData[$id][$key])
					&& !in_array($value, explode(", ", $arProductData[$id][$key]))
				) // if we have multiple property value
				{
					$arProductData[$id][$key] .= ", ".$value;
				}
				elseif (empty($arProductData[$id][$key]))
				{
					$arProductData[$id][$key] = $value;
				}
			}
		}
	}

	return $arProductData;
}

function checkProductPropCode($selectItem)
{
	return ($selectItem !== null && $selectItem !== '' && $selectItem !== 'PROPERTY_');
}

function updateBasketOffersProps($oldProps, $newProps)
{
	if (!is_array($oldProps) || !is_array($newProps))
		return false;

	$result = array();
	if (empty($newProps))
		return $oldProps;
	if (empty($oldProps))
		return $newProps;
	foreach ($oldProps as &$oldValue)
	{
		$found = false;
		$key = false;
		$propId = (isset($oldValue['CODE']) ? (string)$oldValue['CODE'] : '').':'.$oldValue['NAME'];
		foreach ($newProps as $newKey => $newValue)
		{
			$newId = (isset($newValue['CODE']) ? (string)$newValue['CODE'] : '').':'.$newValue['NAME'];
			if ($newId == $propId)
			{
				$key = $newKey;
				$found = true;
				break;
			}
		}
		if ($found)
		{
			$oldValue['VALUE'] = $newProps[$key]['VALUE'];
			unset($newProps[$key]);
		}
		$result[] = $oldValue;
	}
	unset($oldValue);
	if (!empty($newProps))
	{
		foreach ($newProps as &$newValue)
		{
			$result[] = $newValue;
		}
		unset($newValue);
	}
	return $result;
}