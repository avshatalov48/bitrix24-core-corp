<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>

<?
if (!empty($arResult['BUTTONS']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'flat',
		array(
			'BUTTONS' => $arResult['BUTTONS']
		),
		$component,
		array(
			'HIDE_ICONS' => 'Y'
		)
	);
}
if (is_array($arResult['STEXPORT_PARAMS']))
{
	\Bitrix\Main\UI\Extension::load('ui.progressbar');
	\Bitrix\Main\UI\Extension::load('ui.buttons');
	\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
	\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/export.js');
	?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.Crm.ExportManager.create(
					"<?=CUtil::JSEscape($arResult['STEXPORT_PARAMS']['managerId'])?>",
					<?=CUtil::PhpToJSObject($arResult['STEXPORT_PARAMS'])?>
				);

				BX.CrmLongRunningProcessDialog.messages = {
					startButton: "<?=GetMessageJS('CRM_PRODUCT_LRP_DLG_BTN_START')?>",
					stopButton: "<?=GetMessageJS('CRM_PRODUCT_LRP_DLG_BTN_STOP')?>",
					closeButton: "<?=GetMessageJS('CRM_PRODUCT_LRP_DLG_BTN_CLOSE')?>",
					wait: "<?=GetMessageJS('CRM_PRODUCT_LRP_DLG_WAIT')?>",
					requestError: "<?=GetMessageJS('CRM_PRODUCT_LRP_DLG_REQUEST_ERR')?>"
				};
			}
		);
	</script><?php
}
