<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$UID = $arResult['UID'];
$dispatcherData = array();
?>

<div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
<ul class="crm_dealings_list">
	<?foreach($arResult['ITEMS'] as &$item):?>
		<?
		$dataItem = CCrmMobileHelper::PrepareInvoiceEventData($item);
		$dispatcherData[] = $dataItem;
		?>
		<li class="crm_history_list_item" data-entity-id="<?=$item['ID']?>">
			<div class="crm_history_title"><?=htmlspecialcharsbx($dataItem['NAME'])?></div>
			<div class="crm_history_descr"><?=$dataItem['DESCRIPTION_HTML']?></div>
			<div class="crm_history_cnt"><?=htmlspecialcharsbx($dataItem['DATE_CREATE'])?>, <?=htmlspecialcharsbx($dataItem['USER_FORMATTED_NAME'])?></div>
			<div class="clb"></div>
		</li>
	<?endforeach;?>
	<?unset($item);?>

	<?if($arResult['PAGE_NEXT_NUMBER'] <= $arResult['PAGE_NAVCOUNT']):?>
		<li class="crm_history_list_item crm_history_list_item_wait"></li>
	<?endif;?>
</ul></div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmMobileContext.getCurrent().enableReloadOnPullDown(
				{
					pullText: '<?= GetMessage('M_CRM_INVOICE_EVENT_LIST_PULL_TEXT')?>',
					downText: '<?= GetMessage('M_CRM_INVOICE_EVENT_LIST_DOWN_TEXT')?>',
					loadText: '<?= GetMessage('M_CRM_INVOICE_EVENT_LIST_LOAD_TEXT')?>'
				}
			);

			var dispatcher = BX.CrmEntityDispatcher.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					typeName: 'INVOICE_EVENT',
					data: <?=CUtil::PhpToJSObject($dispatcherData)?>,
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: <?=CUtil::PhpToJSObject(
						array(
							'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
						)
					)?>
				}
			);

			BX.CrmInvoiceEventListView.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					dispatcher: dispatcher,
					wrapperId: '<?=CUtil::JSEscape($UID)?>',
					nextPageUrl: '<?=CUtil::JSEscape($arResult['NEXT_PAGE_URL'])?>'
				}
			);
		}
	);
</script>
