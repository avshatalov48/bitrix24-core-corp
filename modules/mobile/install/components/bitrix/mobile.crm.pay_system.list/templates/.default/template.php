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
			'ID' => $item['ID'],
			'NAME' => $item['NAME']
		);
		?><li class="crm_contact_list_people">
			<div class="crm_contactlist_info crm_arrow">
				<strong><?=htmlspecialcharsbx($item['NAME'])?></strong>
				<input type="hidden" class="crm_entity_info" value="<?=htmlspecialcharsbx($item['ID'])?>" />
			</div>
		</li><?
	endforeach;
	unset($item);
?></ul></div>

<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmMobileContext.getCurrent().enableReloadOnPullDown(
				{
					pullText: "<?=GetMessage('M_CRM_PAY_SYSTEM_LIST_PULL_TEXT')?>",
					downText: "<?=GetMessage('M_CRM_PAY_SYSTEM_LIST_DOWN_TEXT')?>",
					loadText: "<?=GetMessage('M_CRM_PAY_SYSTEM_LIST_LOAD_TEXT')?>"
				}
			);

			var uid = "<?=CUtil::JSEscape($UID)?>";
			var dispatcher = BX.CrmEntityDispatcher.create(
				uid,
				{
					typeName: "PAY_SYSTEM",
					data: <?=CUtil::PhpToJSObject($dispatcherData)?>,
					serviceUrl: ""
				}
			);
			var view = BX.CrmPaySystemListView.create(
				uid,
				{
					typeName: 'PAY_SYSTEM',
					dispatcher: dispatcher,
					contextId: '<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>',
					personTypeId: '<?=CUtil::JSEscape($arResult['PERSON_TYPE_ID'])?>',
					wrapperId: uid,
					mode: '<?=CUtil::JSEscape($arResult['MODE'])?>',
					reloadUrlTemplate: "<?=CUtil::JSEscape($arResult['RELOAD_URL_TEMPLATE'])?>"
				}
			);
			view.initializeFromExternalData();
		}
	);
</script>
