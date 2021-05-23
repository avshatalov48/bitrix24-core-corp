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
			'TYPE_ID' => $typeID,
			'STATUS_ID' => $item['STATUS_ID'],
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
			var uid = "<?=CUtil::JSEscape($UID)?>";
			var dispatcher = BX.CrmEntityDispatcher.create(
				uid,
				{
					typeName: "STATUS",
					data: <?=CUtil::PhpToJSObject($dispatcherData)?>,
					serviceUrl: ""
				}
			);
			BX.CrmStatusListView.create(
				uid,
				{
					typeName: 'STATUS',
					wrapperId: uid,
					dispatcher: dispatcher,
					mode: '<?=CUtil::JSEscape($arResult['MODE'])?>',
					contextId: '<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>'
				}
			);
		}
	);
</script>
