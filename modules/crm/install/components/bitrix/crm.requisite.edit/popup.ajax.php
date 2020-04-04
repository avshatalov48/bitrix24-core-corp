<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

$siteID = isset($_REQUEST['site']) ? substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2) : '';
if($siteID !== '')
{
	define('SITE_ID', $siteID);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

CUtil::JSPostUnescape();

/** @global $APPLICATION CMain */
global $USER, $APPLICATION;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

if (!$USER->IsAuthorized() || !check_bitrix_sessid())
{
	ShowError(GetMessage('CRM_LOAD_POPUP_ERROR'));
}
else
{
	$entityTypeId = isset($_REQUEST['etype']) ? (int)$_REQUEST['etype'] : 0;
	unset($_GET['etype'], $_POST['etype'], $_REQUEST['etype']);
	$entityId = isset($_REQUEST['eid']) ? (int)$_REQUEST['eid'] : 0;
	unset($_GET['eid'], $_POST['eid'], $_REQUEST['eid']);
	$presetId = isset($_REQUEST['pid']) ? (int)$_REQUEST['pid'] : 0;
	unset($_GET['pid'], $_POST['pid'], $_REQUEST['pid']);
	$requisiteId = isset($_REQUEST['requisite_id']) ? (int)$_REQUEST['requisite_id'] : 0;
	unset($_GET['requisite_id'], $_POST['requisite_id'], $_REQUEST['requisite_id']);
	$requisiteData = isset($_REQUEST['requisite_data']) ? strval($_REQUEST['requisite_data']) : '';
	unset($_GET['requisite_data'], $_POST['requisite_data'], $_REQUEST['requisite_data']);
	$requisiteDataSign = isset($_REQUEST['requisite_data_sign']) ? strval($_REQUEST['requisite_data_sign']) : '';
	unset($_GET['requisite_data_sign'], $_POST['requisite_data_sign'], $_REQUEST['requisite_data_sign']);
	$popupManagerId = isset($_REQUEST['popup_manager_id']) ? strval($_REQUEST['popup_manager_id']) : '';
	unset($_GET['popup_manager_id'], $_POST['popup_manager_id'], $_REQUEST['popup_manager_id']);

	// decode user fields
	foreach ($_REQUEST as $fieldName => $fieldValue)
	{
		if (strncmp($fieldName, 'UF_CRM_', 7) === 0)
			$GLOBALS[$fieldName] = $fieldValue;
	}

	$componentParams = array();
	if ($requisiteId > 0)
	{
		$componentParams['ELEMENT_ID'] = $requisiteId;
	}
	else
	{
		$componentParams['ENTITY_TYPE_ID'] = $entityTypeId;
		$componentParams['ENTITY_ID'] = $entityId;
		$componentParams['ELEMENT_ID'] = 0;
		$componentParams['PRESET_ID'] = $presetId;
		$componentParams['REQUISITE_DATA'] = $requisiteData;
		$componentParams['REQUISITE_DATA_SIGN'] = $requisiteDataSign;
	}
	$componentParams['POPUP_MODE'] = 'Y';
	$componentParams['POPUP_MANAGER_ID'] = $popupManagerId;

	$APPLICATION->ShowAjaxHead();

	$APPLICATION->IncludeComponent(
		'bitrix:crm.requisite.edit',
		'',
		$componentParams,
		false
	);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>