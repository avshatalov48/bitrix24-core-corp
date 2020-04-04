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

/** @global CMain $APPLICATION*/
/** @global CUser $USER*/
global $USER, $APPLICATION;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

if (!$USER->IsAuthorized() || !check_bitrix_sessid())
{
	ShowError(GetMessage('CRM_LOAD_POPUP_ERROR'));
}
else
{
	$entityTypeID = isset($_REQUEST['entityTypeId']) ? (int)$_REQUEST['entityTypeId'] : 0;
	$entityID = isset($_REQUEST['entityId']) ? (int)$_REQUEST['entityId'] : 0;
	$presetID = isset($_REQUEST['presetId']) ? (int)$_REQUEST['presetId'] : 0;
	$requisiteIndex = isset($_REQUEST['requisiteIndex']) ? (int)$_REQUEST['requisiteIndex'] : 0;
	$requisitePseudoID = isset($_REQUEST['requisitePseudoId']) ? $_REQUEST['requisitePseudoId'] : '';
	$requisiteID = isset($_REQUEST['requisiteId']) ? (int)$_REQUEST['requisiteId'] : 0;
	$requisiteData = isset($_REQUEST['requisiteData']) ? $_REQUEST['requisiteData'] : '';
	$requisiteDataSign = isset($_REQUEST['requisiteDataSign']) ? $_REQUEST['requisiteDataSign'] : '';
	$fieldNameTemplate = isset($_REQUEST['fieldNameTemplate']) ? $_REQUEST['fieldNameTemplate'] : '';

	$params = array();
	if ($requisiteId > 0)
	{
		$params['ELEMENT_ID'] = $requisiteId;
		if($fieldNameTemplate !== '')
		{
			$params['FIELD_NAME_TEMPLATE'] = str_replace('#ELEMENT_ID#', $requisiteId, $fieldNameTemplate);
		}
	}
	else
	{
		$params['ENTITY_TYPE_ID'] = $entityTypeID;
		$params['ENTITY_ID'] = $entityID;
		$params['ELEMENT_ID'] = 0;
		$params['PSEUDO_ID'] = $requisitePseudoID ? $requisitePseudoID : "n{$requisiteIndex}";
		$params['PRESET_ID'] = $presetID;
		$params['REQUISITE_DATA'] = $requisiteData;
		$params['REQUISITE_DATA_SIGN'] = $requisiteDataSign;

		if($fieldNameTemplate !== '')
		{
			$params['FIELD_NAME_TEMPLATE'] = str_replace('#ELEMENT_ID#', $params['PSEUDO_ID'], $fieldNameTemplate);
		}
	}

	$params['INNER_FORM_MODE'] = 'Y';

	$APPLICATION->ShowAjaxHead();
	$APPLICATION->IncludeComponent('bitrix:crm.requisite.edit', '', $params, false);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>