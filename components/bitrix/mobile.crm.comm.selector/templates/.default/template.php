<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$UID = $arResult['UID'];
$searchContainerID = $UID.'_search';
$dispatcherData = array();
?><div id="<?=htmlspecialcharsbx($searchContainerID)?>"<?=!$arResult['SHOW_SEARCH_PANEL'] ? ' style="display: none;"' : ''?> class="crm_search active">
	<div class="crm_input_container"><span class="crm_lupe"></span><input class="crm_search_input" type="text" placeholder="<?=htmlspecialcharsbx(GetMessage($arResult['SEARCH_PLACEHOLDER']))?>" /></div>
	<a class="crm_button"><?=htmlspecialcharsbx(GetMessage('M_CRM_COMM_SELECT_SEARCH_BUTTON'))?></a>
</div><?
?><div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
<ul class="crm_list_tel_list"><?
	foreach($arResult['ITEMS'] as &$item):
		$dispatcherDataItem = array(
			'OWNER_ID' => $item['OWNER_ID'],
			'OWNER_TYPE_NAME' => CCrmOwnerType::ResolveName($item['OWNER_TYPE_ID']),
			'TITLE' => $item['TITLE'],
			'DESCRIPTION' => $item['DESCRIPTION'],
			'IMAGE_URL' => $item['IMAGE_URL'],
			'COMMUNICATIONS' => $item['COMMUNICATIONS'],
		);
		$dispatcherDataItem['ID'] = $dispatcherDataItem['OWNER_TYPE_NAME'].'_'.$dispatcherDataItem['OWNER_ID'];

		?><li class="crm_list_tel" data-item-key="<?="COMMUNICATION_{$dispatcherDataItem['OWNER_TYPE_NAME']}_{$dispatcherDataItem['OWNER_ID']}"?>">
			<div class="crm_contactlist_tel_info crm_arrow">
				<img src="<?=htmlspecialcharsbx($item['IMAGE_URL'])?>"/>
				<strong><?=htmlspecialcharsbx($item['TITLE'])?></strong>
				<span><?=htmlspecialcharsbx($item['DESCRIPTION'])?></span>
				<strong style="font-size: 12px;"><?
					$commCount = count($item['COMMUNICATIONS']);
					for($i = 0; $i < $commCount; $i++)
						echo ($i > 0 ? ', ' : ''), htmlspecialcharsbx($item['COMMUNICATIONS'][$i]['VALUE']);
				?></strong>
			</div>
			<div class="clb"></div>
		</li><?
		$dispatcherData[] = $dispatcherDataItem;
		unset($dispatcherDataItem);
	endforeach;
	unset($item);
?></ul>
</div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmMobileContext.getCurrent().enableReloadOnPullDown(
				{
					pullText: "<?=GetMessage('M_CRM_COMM_SELECTOR_PULL_TEXT')?>",
					downText: "<?=GetMessage('M_CRM_COMM_SELECTOR_DOWN_TEXT')?>",
					loadText: "<?=GetMessage('M_CRM_COMM_SELECTOR_LOAD_TEXT')?>"
				}
			);

			var dispatcher = BX.CrmEntityDispatcher.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					typeName: "COMMUNICATION",
					data: <?=CUtil::PhpToJSObject($dispatcherData)?>
				}
			);

			BX.CrmCommSelectorView.messages =
			{
				nothingFound: "<?=GetMessage('M_CRM_COMM_SELECTOR_NOTHING_FOUND')?>"
			};

			var view = BX.CrmCommSelectorView.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					dispatcher: dispatcher,
					contextId: "<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>",
					communicationType: "<?=CUtil::JSEscape($arResult['COMMUNICATION_TYPE'])?>",
					ownerId: "<?=CUtil::JSEscape($arResult['OWNER_ID'])?>",
					ownerType: "<?=CUtil::JSEscape($arResult['OWNER_TYPE_NAME'])?>",
					wrapperId: "<?=CUtil::JSEscape($UID)?>",
					searchContainerId: "<?=CUtil::JSEscape($searchContainerID)?>",
					searchPageUrl: "<?=CUtil::JSEscape($arResult['SEARCH_PAGE_URL'])?>",
					reloadUrlTemplate: "<?=CUtil::JSEscape($arResult['RELOAD_URL_TEMPLATE'])?>"
				}
			);
			view.initializeFromExternalData();
		}
	);
</script>
