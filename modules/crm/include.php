<?php

use Bitrix\Main;
use Bitrix\Sale;

define('CRM_MODULE_CALENDAR_ID', 'calendar');

// Permissions -->
define('BX_CRM_PERM_NONE', '');
define('BX_CRM_PERM_SELF', 'A');
define('BX_CRM_PERM_DEPARTMENT', 'D');
define('BX_CRM_PERM_SUBDEPARTMENT', 'F');
define('BX_CRM_PERM_OPEN', 'O');
define('BX_CRM_PERM_ALL', 'X');
define('BX_CRM_PERM_CONFIG', 'C');
// <-- Permissions

// Sonet entity types -->
define('SONET_CRM_LEAD_ENTITY', 'CRMLEAD');
define('SONET_CRM_CONTACT_ENTITY', 'CRMCONTACT');
define('SONET_CRM_COMPANY_ENTITY', 'CRMCOMPANY');
define('SONET_CRM_DEAL_ENTITY', 'CRMDEAL');
define('SONET_CRM_ACTIVITY_ENTITY', 'CRMACTIVITY');
define('SONET_CRM_INVOICE_ENTITY', 'CRMINVOICE');
define('SONET_CRM_ORDER_ENTITY', 'CRMORDER');

define('SONET_CRM_SUSPENDED_LEAD_ENTITY', 'CRMSULEAD');
define('SONET_SUSPENDED_CRM_CONTACT_ENTITY', 'CRMSUCONTACT');
define('SONET_SUSPENDED_CRM_COMPANY_ENTITY', 'CRMSUCOMPANY');
define('SONET_CRM_SUSPENDED_DEAL_ENTITY', 'CRMSUDEAL');
define('SONET_CRM_SUSPENDED_ACTIVITY_ENTITY', 'CRMSUACTIVITY');
//<-- Sonet entity types

//region Entity View
define('BX_CRM_VIEW_UNDEFINED', 0);
define('BX_CRM_VIEW_LIST', 1);
define('BX_CRM_VIEW_WIDGET', 2);
define('BX_CRM_VIEW_KANBAN', 3);
define('BX_CRM_VIEW_CALENDAR', 4);
//endregion

define('REGISTRY_TYPE_CRM_INVOICE', 'CRM_INVOICE');
define('REGISTRY_TYPE_CRM_QUOTE', 'CRM_QUOTE');

define('ENTITY_CRM_COMPANY', 'ENTITY_CRM_COMPANY');
define('ENTITY_CRM_CONTACT', 'ENTITY_CRM_CONTACT');
define('ENTITY_CRM_CONTACT_COMPANY_COLLECTION', 'ENTITY_CRM_CONTACT_COMPANY_COLLECTION');

global $APPLICATION, $DBType, $DB;

IncludeModuleLangFile(__FILE__);

require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/crm/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/crm/classes/general/crm_usertypecrmstatus.php');
require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/crm/classes/general/crm_usertypecrm.php');

CModule::AddAutoloadClasses(
	'crm',
	array(
		'CAllCrmLead' => 'classes/general/crm_lead.php',
		'CCrmLead' => 'classes/'.$DBType.'/crm_lead.php',
		'CCrmLeadWS' => 'classes/general/ws_lead.php',
		'CCRMLeadRest' => 'classes/general/rest_lead.php',
		'CAllCrmDeal' => 'classes/general/crm_deal.php',
		'CCrmDeal' => 'classes/'.$DBType.'/crm_deal.php',
		'CAllCrmCompany' => 'classes/general/crm_company.php',
		'CCrmCompany' => 'classes/'.$DBType.'/crm_company.php',
		'CAllCrmContact' => 'classes/general/crm_contact.php',
		'CCrmContact' => 'classes/'.$DBType.'/crm_contact.php',
		'CCrmContactWS' => 'classes/general/ws_contact.php',
		'CCrmPerms' => 'classes/general/crm_perms.php',
		'CCrmRole' => 'classes/general/crm_role.php',
		'CCrmFields' => 'classes/general/crm_fields.php',
		'CCrmUserType' => 'classes/general/crm_usertype.php',
		'CCrmGridOptions' => 'classes/general/crm_grids.php',
		'CCrmStatus' => 'classes/general/crm_status.php',
		'CCrmFieldMulti' => 'classes/general/crm_field_multi.php',
		'CCrmEvent' => 'classes/general/crm_event.php',
		'CCrmEMail' => 'classes/general/crm_email.php',
		'CCrmVCard' => 'classes/general/crm_vcard.php',
		'CCrmActivityTask' => 'classes/general/crm_activity_task.php',
		'CCrmActivityCalendar' => 'classes/general/crm_activity_calendar.php',
		'CUserTypeCrm' => 'classes/general/crm_usertypecrm.php',
		'CUserTypeCrmStatus' => 'classes/general/crm_usertypecrmstatus.php',
		'CCrmSearch' => 'classes/general/crm_search.php',
		'CCrmBizProc' => 'classes/general/crm_bizproc.php',
		'CCrmDocument' => 'classes/general/crm_document.php',
		'CCrmDocumentLead' => 'classes/general/crm_document_lead.php',
		'CCrmDocumentContact' => 'classes/general/crm_document_contact.php',
		'CCrmDocumentCompany' => 'classes/general/crm_document_company.php',
		'CCrmDocumentDeal' => 'classes/general/crm_document_deal.php',
		'CCrmReportHelper' => 'classes/general/crm_report_helper.php',
		'\Bitrix\Crm\StatusTable' => 'lib/status.php',
		'\Bitrix\Crm\EventTable' => 'lib/event.php',
		'\Bitrix\Crm\EventRelationsTable' => 'lib/event.php',
		'\Bitrix\Crm\DealTable' => 'lib/deal.php',
		'\Bitrix\Crm\LeadTable' => 'lib/lead.php',
		'\Bitrix\Crm\ContactTable' => 'lib/contact.php',
		'\Bitrix\Crm\CompanyTable' => 'lib/company.php',
		'\Bitrix\Crm\StatusTable' => 'lib/status.php',
		'\Bitrix\Crm\DealTable' => 'lib/deal.php',
		'\Bitrix\Crm\LeadTable' => 'lib/lead.php',
		'\Bitrix\Crm\ContactTable' => 'lib/contact.php',
		'\Bitrix\Crm\CompanyTable' => 'lib/company.php',
		'\Bitrix\Crm\QuoteTable' => 'lib/quote.php',
		'CCrmExternalSale' => 'classes/general/crm_external_sale.php',
		'CCrmExternalSaleProxy' => 'classes/general/crm_external_sale_proxy.php',
		'CCrmExternalSaleImport' => 'classes/general/crm_external_sale_import.php',
		'CCrmUtils' => 'classes/general/crm_utils.php',
		'CCrmEntityHelper' => 'classes/general/entity_helper.php',
		'CAllCrmCatalog' => 'classes/general/crm_catalog.php',
		'CCrmCatalog' => 'classes/'.$DBType.'/crm_catalog.php',
		'CCrmCurrency' => 'classes/general/crm_currency.php',
		'CCrmCurrencyHelper' => 'classes/general/crm_currency_helper.php',
		'CCrmProductResult' => 'classes/general/crm_product_result.php',
		'CCrmProduct' => 'classes/general/crm_product.php',
		'CCrmProductHelper' => 'classes/general/crm_product_helper.php',
		'CAllCrmProductRow' => 'classes/general/crm_product_row.php',
		'CCrmProductRow' => 'classes/'.$DBType.'/crm_product_row.php',
		'CAllCrmInvoice' => 'classes/general/crm_invoice.php',
		'CCrmInvoice' => 'classes/'.$DBType.'/crm_invoice.php',
		'CAllCrmQuote' => 'classes/general/crm_quote.php',
		'CCrmQuote' => 'classes/'.$DBType.'/crm_quote.php',
		'CCrmOwnerType' => 'classes/general/crm_owner_type.php',
		'CCrmOwnerTypeAbbr' => 'classes/general/crm_owner_type.php',
		'Bitrix\Crm\ProductTable' => 'lib/product.php',
		'Bitrix\Crm\ProductRowTable' => 'lib/productrow.php',
		'Bitrix\Crm\IBlockElementProxyTable' => 'lib/iblockelementproxy.php',
		'Bitrix\Crm\IBlockElementGrcProxyTable' => 'lib/iblockelementproxy.php',
		'\Bitrix\Crm\ProductTable' => 'lib/product.php',
		'\Bitrix\Crm\ProductRowTable' => 'lib/productrow.php',
		'\Bitrix\Crm\IBlockElementProxyTable' => 'lib/iblockelementproxy.php',
		'\Bitrix\Crm\IBlockElementGrcProxyTable' => 'lib/iblockelementproxy.php',
		'CCrmAccountingHelper' => 'classes/general/crm_accounting_helper.php',
		'Bitrix\Crm\ExternalSaleTable' => 'lib/externalsale.php',
		'\Bitrix\Crm\ExternalSaleTable' => 'lib/externalsale.php',
		'CCrmExternalSaleHelper' => 'classes/general/crm_external_sale_helper.php',
		'CCrmEntityListBuilder' => 'classes/general/crm_entity_list_builder.php',
		'CCrmComponentHelper' => 'classes/general/crm_component_helper.php',
		'CCrmInstantEditorHelper' => 'classes/general/crm_component_helper.php',
		'CAllCrmActivity' => 'classes/general/crm_activity.php',
		'CCrmActivity' => 'classes/'.$DBType.'/crm_activity.php',
		'CCrmActivityType' => 'classes/general/crm_activity.php',
		'CCrmActivityStatus' => 'classes/general/crm_activity.php',
		'CCrmActivityPriority' => 'classes/general/crm_activity.php',
		'CCrmActivityNotifyType' => 'classes/general/crm_activity.php',
		'CCrmActivityStorageType' => 'classes/general/crm_activity.php',
		'CCrmContentType' => 'classes/general/crm_activity.php',
		'CCrmEnumeration' => 'classes/general/crm_enumeration.php',
		'CCrmEntitySelectorHelper' => 'classes/general/crm_entity_selector_helper.php',
		'CCrmBizProcHelper' => 'classes/general/crm_bizproc_helper.php',
		'CCrmBizProcEventType' => 'classes/general/crm_bizproc_helper.php',
		'CCrmUrlUtil' => 'classes/general/crm_url_util.php',
		'CCrmAuthorizationHelper' => 'classes/general/crm_authorization_helper.php',
		'CCrmWebDavHelper' => 'classes/general/crm_webdav_helper.php',
		'CCrmActivityDirection' => 'classes/general/crm_activity.php',
		'CCrmViewHelper' => 'classes/general/crm_view_helper.php',
		'CCrmSecurityHelper' => 'classes/general/crm_security_helper.php',
		'CCrmMailHelper' => 'classes/general/crm_mail_helper.php',
		'CCrmNotifier' => 'classes/general/crm_notifier.php',
		'CCrmNotifierSchemeType' => 'classes/general/crm_notifier.php',
		'CCrmActivityConverter' => 'classes/general/crm_activity_converter.php',
		'CCrmDateTimeHelper' => 'classes/general/datetime_helper.php',
		'CCrmEMailCodeAllocation' => 'classes/general/crm_email.php',
		'CCrmActivityCalendarSettings' => 'classes/general/crm_activity.php',
		'CCrmActivityCalendarSettings' => 'classes/general/crm_activity.php',
		'CCrmProductReportHelper' => 'classes/general/crm_report_helper.php',
		'CCrmReportManager' => 'classes/general/crm_report_helper.php',
		'CCrmCallToUrl' => 'classes/general/crm_url_util.php',
		'CCrmUrlTemplate' => 'classes/general/crm_url_util.php',
		'CCrmFileProxy' => 'classes/general/file_proxy.php',
		'CAllCrmMailTemplate' => 'classes/general/mail_template.php',
		'CCrmMailTemplate' => 'classes/'.$DBType.'/mail_template.php',
		'CCrmMailTemplateScope' =>  'classes/general/mail_template.php',
		'CCrmTemplateAdapter' =>  'classes/general/template_adapter.php',
		'CCrmTemplateMapper' =>  'classes/general/template_mapper.php',
		'CCrmTemplateManager' =>  'classes/general/template_manager.php',
		'CCrmGridContext' => 'classes/general/crm_grids.php',
		'CCrmUserCounter' => 'classes/general/user_counter.php',
		'CCrmUserCounterSettings' => 'classes/general/user_counter.php',
		'CCrmMobileHelper' => 'classes/general/mobile_helper.php',
		'CCrmStatusInvoice' => 'classes/general/crm_status_invoice.php',
		'CCrmTax' => 'classes/general/crm_tax.php',
		'CCrmVat' => 'classes/general/crm_vat.php',
		'CCrmLocations' => 'classes/general/crm_locations.php',
		'CCrmPaySystem' => 'classes/general/crm_pay_system.php',
		'CCrmRestService' => 'classes/general/restservice.php',
		'ICrmRestProxy' => 'classes/general/restservice.php',
		'CCrmRestEventDispatcher' => 'classes/general/restservice.php',
		'CCrmFieldInfo' => 'classes/general/field_info.php',
		'CCrmFieldInfoAttr' => 'classes/general/field_info.php',
		'CCrmActivityEmailSender' => 'classes/general/crm_activity.php',
		'CCrmProductSection' => 'classes/general/crm_product_section.php',
		'CCrmProductSectionDbResult' => 'classes/general/crm_product_section.php',
		'CCrmActivityDbResult' => 'classes/general/crm_activity.php',
		'CCrmInvoiceRestService' => 'classes/general/restservice_invoice.php',
		'CCrmInvoiceEvent' => 'classes/general/crm_invoice_event.php',
		'CCrmInvoiceEventFormat' => 'classes/general/crm_invoice_event.php',
		'CCrmLeadReportHelper' => 'classes/general/crm_report_helper.php',
		'CCrmInvoiceReportHelper' => 'classes/general/crm_report_helper.php',
		'CCrmActivityReportHelper' => 'classes/general/crm_report_helper.php',
		'CCrmLiveFeed' => 'classes/general/livefeed.php',
		'CCrmLiveFeedMessageRestProxy' => 'classes/general/restservice.php',
		'CCrmLiveFeedEntity' => 'classes/general/livefeed.php',
		'CCrmLiveFeedEvent' => 'classes/general/livefeed.php',
		'CCrmLiveFeedFilter' => 'classes/general/livefeed.php',
		'CCrmLiveFeedComponent' => 'classes/general/livefeed.php',
		'CAllCrmSonetRelation' => 'classes/general/sonet_relation.php',
		'CCrmSonetRelationType' => 'classes/general/sonet_relation.php',
		'CCrmSonetRelation' => 'classes/'.$DBType.'/sonet_relation.php',
		'CAllCrmSonetSubscription' => 'classes/general/sonet_subscription.php',
		'CCrmSonetSubscriptionType' => 'classes/general/sonet_subscription.php',
		'CCrmSonetSubscription' => 'classes/'.$DBType.'/sonet_subscription.php',
		'CCrmSipHelper' => 'classes/general/sip_helper.php',
		'CCrmSaleHelper' => 'classes/general/sale_helper.php',
		'CCrmProductFile' => 'classes/general/crm_product_file.php',
		'CCrmProductFileControl' => 'classes/general/crm_product_file.php',
		'CCrmProductPropsHelper' => 'classes/general/crm_productprops_helper.php',
		'CCrmProductSectionHelper' => 'classes/general/crm_product_section_helper.php',
		'CCrmTaxEntity' => 'lib/invoice/compatible/taxentity.php',
		'CCrmInvoiceTax' => 'lib/invoice/compatible/invoicetax.php',
		'\Bitrix\Crm\Honorific' => 'lib/honorific.php',
		'\Bitrix\Crm\Category\DealCategory' => 'lib/category/dealcategory.php',
		'\Bitrix\Crm\Conversion\LeadConverter' => 'lib/conversion/leadconverter.php',
		'\Bitrix\Crm\Conversion\EntityConversionConfigItem' => 'lib/conversion/entityconversionconfigitem.php',
		'\Bitrix\Crm\Conversion\EntityConversionMapItem' => 'lib/conversion/entityconversionmapitem.php',
		'\Bitrix\Crm\Conversion\EntityConversionMap' => 'lib/conversion/entityconversionmap.php',
		'\Bitrix\Crm\Conversion\LeadConversionMapper' => 'lib/conversion/leadconversionmapper.php',
		'\Bitrix\Crm\Conversion\LeadConversionWizard' => 'lib/conversion/leadconversionwizard.php',
		'\Bitrix\Crm\Conversion\LeadConversionPhase' => 'lib/conversion/leadconversionphase.php',
		'\Bitrix\Crm\Conversion\LeadConversionConfig' => 'lib/conversion/leadconversionconfig.php',
		'\Bitrix\Crm\Conversion\LeadConversionScheme' => 'lib/conversion/leadconversionscheme.php',
		'\Bitrix\Crm\Conversion\DealConversionConfig' => 'lib/conversion/dealconversionconfig.php',
		'\Bitrix\Crm\Conversion\DealConversionScheme' => 'lib/conversion/dealconversionscheme.php',
		'\Bitrix\Crm\Conversion\EntityConversionFileViewer' => 'lib/conversion/entityconversionfileviewer.php',
		'\Bitrix\Crm\Conversion\Entity\EntityConversionMapTable' => 'lib/conversion/entity/entityconversionmap.php',
		'\Bitrix\Crm\Conversion\ConversionWizardStep' => 'lib/conversion/conversionwizardstep.php',
		'\Bitrix\Crm\Conversion\ConversionWizard' => 'lib/conversion/conversionwizard.php',
		'\Bitrix\Crm\Synchronization\UserFieldSynchronizer' => 'lib/synchronization/userfieldsynchronizer.php',
		'\Bitrix\Crm\Synchronization\UserFieldSynchronizationException' => 'lib/synchronization/userfieldsynchronizationexception.php',
		'\Bitrix\Crm\UserField\UserFieldHistory' => 'lib/userfield/userfieldhistory.php',
		'\Bitrix\Crm\UserField\FileViewer' => 'lib/userfield/fileviewer.php',
		'\Bitrix\Crm\Integration\Bitrix24Manager' => 'lib/integration/bitrix24manager.php',
		'\Bitrix\Crm\Restriction\Restriction' => 'lib/restriction/restriction.php',
		'\Bitrix\Crm\Restriction\RestrictionManager' => 'lib/restriction/restrictionmanager.php',
		'\Bitrix\Crm\Restriction\AccessRestriction' => 'lib/restriction/accessrestriction.php',
		'\Bitrix\Crm\Restriction\SqlRestriction' => 'lib/restriction/sqlrestriction.php',
		'\Bitrix\Crm\Restriction\Bitrix24AccessRestriction' => 'lib/restriction/bitrix24accessrestriction.php',
		'\Bitrix\Crm\Restriction\Bitrix24SqlRestriction' => 'lib/restriction/bitrix24sqlrestriction.php',
		'\Bitrix\Crm\Restriction\Bitrix24RestrictionInfo' => 'lib/restriction/bitrix24restrictioninfo.php',
		'\Bitrix\Crm\EntityAddress' => 'lib/entityaddress.php',
		'\Bitrix\Crm\EntityRequisite' => 'lib/entityrequisite.php',
		'\Bitrix\Crm\RequisiteTable' => 'lib/requisite.php',
		'\Bitrix\Crm\Integration\StorageType' => 'lib/integration/storagetype.php',
		'\Bitrix\Crm\Statistics\DealActivityStatisticEntry' => 'lib/statistics/dealactivitystatisticentry.php',
		'\Bitrix\Crm\Statistics\LeadActivityStatisticEntry' => 'lib/statistics/leadactivitystatisticentry.php',
		'\Bitrix\Crm\ActivityTable' => 'lib/activity.php',
		'\Bitrix\Crm\PhaseSemantics' => 'lib/phasesemantics.php',
		'\Bitrix\Crm\Activity\Planner' => 'lib/activity/planner.php',
		'\Bitrix\Crm\Activity\Provider\Base' => 'lib/activity/provider/base.php',
		'\Bitrix\Crm\Activity\Provider\Call' => 'lib/activity/provider/call.php',
		'\Bitrix\Crm\Activity\Provider\Email' => 'lib/activity/provider/email.php',
		'\Bitrix\Crm\Activity\Provider\ExternalChannel' => 'lib/activity/provider/externalchannel.php',
		'\Bitrix\Crm\Activity\Provider\Livefeed' => 'lib/activity/provider/livefeed.php',
		'\Bitrix\Crm\Activity\Provider\Meeting' => 'lib/activity/provider/meeting.php',
		'\Bitrix\Crm\Activity\Provider\Task' => 'lib/activity/provider/task.php',
		'\Bitrix\Crm\Activity\Provider\WebForm' => 'lib/activity/provider/webform.php',
		'\Bitrix\Crm\Rest\CCrmExternalChannelConnector' => 'lib/rest/externalchannelconnector.php',
		'\Bitrix\Crm\Rest\CCrmExternalChannelImport' => 'lib/rest/externalchannelimport.php',
		'\Bitrix\Crm\Rest\CCrmExternalChannelImportPreset' => 'lib/rest/externalchannelimportpreset.php',
		'\Bitrix\Crm\Rest\CCrmExternalChannelImportActivity' => 'lib/rest/externalchannel.php',
		'\Bitrix\Crm\Rest\CCrmExternalChannelImportAgent' => 'lib/rest/externalchannel.php',
		'\Bitrix\Crm\Rest\CCrmExternalChannelActivityType' => 'lib/rest/externalchannelactivitytype.php',
		'\Bitrix\Crm\Rest\CCrmExternalChannelType' => 'lib/rest/externalchanneltype.php',
		'\Bitrix\Crm\Recurring\Manager' => 'lib/recurring/manager.php',
		'\Bitrix\Crm\Recurring\Calculator' => 'lib/recurring/calculator.php',
		'\Bitrix\Crm\Recurring\DateType\Day' => 'lib/recurring/datetype/day.php',
		'\Bitrix\Crm\Recurring\DateType\Month' => 'lib/recurring/datetype/month.php',
		'\Bitrix\Crm\Recurring\DateType\Week' => 'lib/recurring/datetype/week.php',
		'\Bitrix\Crm\Recurring\DateType\Year' => 'lib/recurring/datetype/year.php',
		'\Bitrix\Crm\InvoiceRecurTable' => 'lib/invoicerecur.php',
		'\Bitrix\Crm\DealRecurTable' => 'lib/dealrecur.php',
		'\Bitrix\Crm\Order\Matcher\Internals\OrderPropsMatchTable' => 'lib/order/matcher/internals/orderpropsmatchtable.php',
		'\Bitrix\Crm\Order\Matcher\Internals\QueueTable' => 'lib/order/matcher/internals/queuetable.php',
		'\Bitrix\Crm\Order\Matcher\Internals\FormTable' => 'lib/order/matcher/internals/formtable.php',
		'\Bitrix\Crm\Invoice\Internals\InvoiceDiscountTable' => 'lib/invoice/internals/invoicediscount.php',
		'\Bitrix\Crm\Invoice\Internals\InvoiceCouponsTable' => 'lib/invoice/internals/invoicediscount.php',
		'\Bitrix\Crm\Invoice\Internals\InvoiceModulesTable' => 'lib/invoice/internals/invoicediscount.php',
		'\Bitrix\Crm\Invoice\Internals\InvoiceDiscountDataTable' => 'lib/invoice/internals/invoicediscount.php',
		'\Bitrix\Crm\Invoice\Internals\InvoiceRulesTable' => 'lib/invoice/internals/invoicediscount.php',
		'\Bitrix\Crm\Invoice\Internals\InvoiceRulesDescrTable' => 'lib/invoice/internals/invoicediscount.php',
		'\Bitrix\Crm\Invoice\Internals\InvoiceRoundTable' => 'lib/invoice/internals/invoiceround.php',
		'\Bitrix\Crm\Communication\Type' => 'lib/communication/type.php',
		'\Bitrix\Crm\Order\Manager' => 'lib/order/manager.php',
		'\Bitrix\Crm\Preview\Company' => 'lib/preview/company.php',
		'\Bitrix\Crm\Preview\Contact' => 'lib/preview/contact.php',
		'\Bitrix\Crm\Preview\Deal' => 'lib/preview/deal.php',
		'\Bitrix\Crm\Preview\Invoice' => 'lib/preview/invoice.php',
		'\Bitrix\Crm\Preview\Lead' => 'lib/preview/lead.php',
		'\Bitrix\Crm\Preview\Product' => 'lib/preview/product.php',
		'\Bitrix\Crm\Preview\Quote' => 'lib/preview/quote.php',
		'\Bitrix\Crm\Preview\Route' => 'lib/preview/route.php',
		'\Bitrix\Crm\Order\Manager' => 'lib/order/manager.php',
		'\Bitrix\Crm\AddressTable' => 'lib/address.php',
		'\Bitrix\Crm\UtmTable' => 'lib/utm.php',

	)
);


$classAliases = [
	['Bitrix\Crm\Communication\Type', 'Bitrix\Crm\CommunicationType'],
];
foreach ($classAliases as $classAlias)
{
	class_alias($classAlias[0], $classAlias[1]);
}

CJSCore::RegisterExt('crm_activity_planner', array(
	'js' => array('/bitrix/js/crm/activity_planner.js', '/bitrix/js/crm/communication_search.js'),
	'css' => '/bitrix/js/crm/css/crm-activity-planner.css',
	'lang' => '/bitrix/modules/crm/lang/'.LANGUAGE_ID.'/install/js/activity_planner.php',
	'rel' => array('core', 'popup', 'date', 'fx', 'socnetlogdest'),
));

CJSCore::RegisterExt('crm_recorder', array(
	'js' => array('/bitrix/js/crm/recorder.js'),
	'css' => '/bitrix/js/crm/css/crm-recorder.css',
	'rel' => array('webrtc_adapter', 'recorder'),
));

CJSCore::RegisterExt('crm_visit_tracker', array(
	'js' => array('/bitrix/js/crm/visit.js'),
	'css' => array('/bitrix/js/crm/css/visit.css', '/bitrix/components/bitrix/crm.activity.visit/templates/.default/style.css', '/bitrix/components/bitrix/crm.card.show/templates/.default/style.css'),
	'lang' => '/bitrix/modules/crm/lang/'.LANGUAGE_ID.'/install/js/visit.php',
	'rel' => array('crm_recorder', 'ui.fonts.opensans'),
));

CJSCore::RegisterExt('crm_form_loader', array(
	'js' => array('/bitrix/js/crm/form_loader.js'),
));

CJSCore::RegisterExt('crm_import_csv', array(
		'js' => '/bitrix/js/crm/import_csv.js',
		'css' => '/bitrix/js/crm/css/import_csv.css',
		'lang' => '/bitrix/modules/crm/lang/'.LANGUAGE_ID.'/install/js/import_csv.php',
));

if (IsModuleInstalled('socialnetwork'))
{
	CJSCore::RegisterExt('crm_sonet_commentaux', array(
		'js' => '/bitrix/js/crm/socialnetwork.js'
	));
}

\Bitrix\Main\Page\Asset::getInstance()->addJsKernelInfo("crm", array("/bitrix/js/crm/crm.js"));
