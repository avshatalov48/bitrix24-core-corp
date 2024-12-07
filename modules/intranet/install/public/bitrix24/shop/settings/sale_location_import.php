<?php
if (!defined('SELF_FOLDER_URL'))
{
	define('SELF_FOLDER_URL', '/shop/settings/');
}
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

CModule::IncludeModule('sale');

if(CSaleLocation::isLocationProEnabled() || $adminSidePanelHelper->isPublicSidePanel())
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/admin/location_import.php");
else
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/admin/location_import_old.php");