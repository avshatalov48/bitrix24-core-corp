<?
$siteID = isset($_REQUEST['site'])? mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2) : '';
if($siteID !== '')
{
	define('SITE_ID', $siteID);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityPopupComponent $component */

$APPLICATION->RestartBuffer();
?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LANGUAGE_ID ?>" lang="<?=LANGUAGE_ID ?>">
<head>
	<script>
		// Prevent loading page without header and footer
		if(window === window.top)
		{
			window.location = "<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('IFRAME'))); ?>";
		}
	</script>
	<?$APPLICATION->ShowHead();?>
	<style>.task-iframe-popup,
		.task-iframe-popup.task-form-page,
		.task-iframe-popup.task-detail-page{
			background: #eef2f4 !important;
			padding: 0 15px 21px 21px;
		}</style>
</head>
<body class="task-iframe-popup task-detail-page template-<?=SITE_TEMPLATE_ID?> <? $APPLICATION->ShowProperty('BodyClass'); ?>" onload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeLoad');" onunload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeUnload');">

<div class="task-iframe-workarea" id="tasks-content-outer">
<div class="task-iframe-sidebar"><?$APPLICATION->ShowViewContent("sidebar"); ?></div>
<div class="task-iframe-content"><?

if(!\Bitrix\Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
}
else
{
	$entityTypeId = isset($_REQUEST['etype']) ? (int)$_REQUEST['etype'] : 0;
	$entityId = isset($_REQUEST['eid']) ? (int)$_REQUEST['eid'] : 0;
	$presetId = isset($_REQUEST['pid']) ? (int)$_REQUEST['pid'] : 0;
	$requisiteId = isset($_REQUEST['requisite_id']) ? (int)$_REQUEST['requisite_id'] : 0;
	$pseuoId = isset($_REQUEST['pseudo_id']) ? $_REQUEST['pseudo_id'] : '';
	$requisiteData = isset($_REQUEST['requisite_data']) ? strval($_REQUEST['requisite_data']) : '';
	$requisiteDataSign = isset($_REQUEST['requisite_data_sign']) ? strval($_REQUEST['requisite_data_sign']) : '';
	$popupManagerId = isset($_REQUEST['popup_manager_id']) ? strval($_REQUEST['popup_manager_id']) : '';

	if(!(check_bitrix_sessid()
		&& \Bitrix\Crm\Security\EntityAuthorization::isAuthorized()
		&& \Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($entityTypeId, $entityId))
	)
	{
		ShowError(GetMessage('CRM_ACCESS_DENIED'));
	}
	else
	{
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
			$componentParams['PSEUDO_ID'] = $pseuoId;
			$componentParams['PRESET_ID'] = $presetId;
			$componentParams['REQUISITE_DATA'] = $requisiteData;
			$componentParams['REQUISITE_DATA_SIGN'] = $requisiteDataSign;
		}

		$componentParams['POPUP_MODE'] = 'Y';
		$componentParams['POPUP_MANAGER_ID'] = $popupManagerId;

		$componentParams['EXTERNAL_CONTEXT_ID'] = isset($_REQUEST['external_context_id']) ? $_REQUEST['external_context_id'] : '';

		$APPLICATION->IncludeComponent(
			'bitrix:crm.requisite.edit',
			'slider',
			$componentParams,
			false
		);
	}
}

?></div>
</div>
</body>
</html><?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
die();
?>