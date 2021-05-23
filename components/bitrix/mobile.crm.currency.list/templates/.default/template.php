<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$UID = $arResult['UID'];
$typeID = $arResult['TYPE_ID'];

$dispatcherData = array();
?><div id="<?=htmlspecialcharsbx($UID)?>"><ul class="crm_contact_list_people_list"><?
	foreach($arResult['ITEMS'] as &$item):
		$dispatcherData[] = array(
			'ID' => $item['CURRENCY'],
			'NAME' => $item['FULL_NAME']
		);
		?><li class="crm_contact_list_people">
			<div class="crm_contactlist_info crm_arrow">
				<strong><?=htmlspecialcharsbx($item['FULL_NAME'])?></strong>
				<input type="hidden" class="crm_entity_info" value="<?=htmlspecialcharsbx($item['CURRENCY'])?>" />
			</div>
		</li><?
	endforeach;
	unset($item);
?></ul></div>

<script type="text/javascript">
	BX.ready(
		function()
		{

			var context = BX.CrmMobileContext.getCurrent();
			context.enableReloadOnPullDown(
				{
					pullText: '<?= GetMessage('M_CRM_CURRENCY_LIST_PULL_TEXT')?>',
					downText: '<?= GetMessage('M_CRM_CURRENCY_LIST_DOWN_TEXT')?>',
					loadText: '<?= GetMessage('M_CRM_CURRENCY_LIST_LOAD_TEXT')?>'
				}
			);

			var uid = "<?=CUtil::JSEscape($UID)?>";
			var dispatcher = BX.CrmEntityDispatcher.create(
				uid,
				{
					typeName: "CURRENCY",
					data: <?=CUtil::PhpToJSObject($dispatcherData)?>,
					serviceUrl: ""
				}
			);
			var list = BX.CrmCurrencyListView.create(
				uid,
				{
					wrapperId: uid,
					dispatcher: dispatcher,
					mode: '<?=CUtil::JSEscape($arResult['MODE'])?>',
					contextId: '<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>'
				}
			);
			list.initializeFromExternalData();
		}
	);
</script>
