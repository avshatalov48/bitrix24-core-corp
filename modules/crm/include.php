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
define('BX_CRM_VIEW_ACTIVITY', 5);
define('BX_CRM_VIEW_DEADLINES', 6);
//endregion

define('REGISTRY_TYPE_CRM_INVOICE', 'CRM_INVOICE');
define('REGISTRY_TYPE_CRM_QUOTE', 'CRM_QUOTE');

define('ENTITY_CRM_COMPANY', 'ENTITY_CRM_COMPANY');
define('ENTITY_CRM_CONTACT', 'ENTITY_CRM_CONTACT');
define('ENTITY_CRM_CONTACT_COMPANY_COLLECTION', 'ENTITY_CRM_CONTACT_COMPANY_COLLECTION');
define('ENTITY_CRM_ORDER_ENTITY_BINDING', 'ENTITY_CRM_ORDER_ENTITY_BINDING');

global $APPLICATION, $DB;

IncludeModuleLangFile(__FILE__);

require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/crm/functions.php');
require_once __DIR__.'/autoload.php';

CJSCore::RegisterExt('crm_common', array(
	'js' => [
		'/bitrix/js/crm/common.js'
	],
	'css' => [
		'/bitrix/js/crm/css/crm.css'
	],
	'rel' => [
		'ui.design-tokens',
		'ui.fonts.opensans',
	]
));

CJSCore::RegisterExt('crm_activity_planner', array(
	'js' => array('/bitrix/js/crm/activity_planner.js', '/bitrix/js/crm/communication_search.js'),
	'css' => '/bitrix/js/crm/css/crm-activity-planner.css',
	'lang' => '/bitrix/modules/crm/lang/'.LANGUAGE_ID.'/install/js/activity_planner.php',
	'rel' => array('core', 'popup', 'date', 'fx', 'socnetlogdest', 'ui.design-tokens', 'ui.fonts.opensans'),
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

if (IsModuleInstalled('disk'))
{
	CJSCore::RegisterExt('crm_disk_uploader', array(
		'js' => '/bitrix/js/crm/disk_uploader.js',
		'css' => '/bitrix/js/disk/css/legacy_uf_common.css'
	));
}

\Bitrix\Main\Page\Asset::getInstance()->addJsKernelInfo("crm", array("/bitrix/js/crm/crm.js"));

\Bitrix\Crm\Engine\AutoWire\Binder::registerDefaultAutoWirings();
