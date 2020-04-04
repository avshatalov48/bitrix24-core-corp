<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

CUtil::InitJSCore(array('ajax', 'date'));

$UID = $arResult['UID'];
$typeID = $arResult['TYPE_ID'];
$entityTypeID = $arResult['ENTITY_TYPE_ID'];
$currentStepID = $arResult['CURRENT_STEP_ID'];
$selectedStepID = '';
$dispatcherData = array();

if($entityTypeID === CCrmOwnerType::Lead)
	echo CCrmViewHelper::RenderLeadStatusSettings();
elseif($entityTypeID === CCrmOwnerType::Deal)
	echo CCrmViewHelper::RenderDealStageSettings();
elseif($entityTypeID === CCrmOwnerType::Invoice)
	echo CCrmViewHelper::RenderInvoiceStatusSettings();

?><div id="<?=htmlspecialcharsbx($UID)?>"><div class="crm_block_container">
	<div class="crm_card p0"><?
		$i = -1;
		$c = count($arResult['ITEMS']);
		foreach($arResult['ITEMS'] as &$item):
			$dispatcherData[] = array(
				'ID' => $item['ID'],
				'TYPE_ID' => $typeID,
				'STATUS_ID' => $item['STATUS_ID'],
				'NAME' => $item['NAME']
			);
			$isSelected = $item['STATUS_ID'] === $currentStepID;

		?><div class="crm_selector_status <?=$isSelected ? " check" : ""?>">
			<input type="hidden" class="crm_entity_info" value="<?=htmlspecialcharsbx($item['ID'])?>" />
			<table style="width:100%;">
				<tbody>
					<tr>
						<td style="text-align: center;width: 40px;">
							<input type="checkbox" hidden />
							<div class="checkbox_emulator"></div>
						</td>
						<td>
							<div style="padding: 0 10px;">
								<strong><?=htmlspecialcharsbx($item['NAME'])?></strong><?
									CCrmMobileHelper::RenderProgressBar(
										array(
											'LAYOUT' => 'big',
											'ENTITY_TYPE_ID' => $entityTypeID,
											'ENTITY_ID' => 0,
											//'PREFIX' => strtolower($UID).'_',
											'CURRENT_ID' => $item['STATUS_ID']
										)
									);
							?></div>
						</td>
					</tr>
				</tbody>
			</table>
		</div><?
		//Check for separator rendering
		if(++$i < ($c - 1)):
		?><hr /><?
		endif;
		endforeach;
		unset($item);
		?>
	</div>
</div></div>

<script type="text/javascript">
	BX.ready(
		function()
		{
			var context = BX.CrmMobileContext.getCurrent();
			context.enableReloadOnPullDown(
				{
					pullText: '<?= GetMessage('M_CRM_PROGRESS_BAR_LIST_PULL_TEXT')?>',
					downText: '<?= GetMessage('M_CRM_PROGRESS_BAR_LIST_DOWN_TEXT')?>',
					loadText: '<?= GetMessage('M_CRM_PROGRESS_BAR_LIST_LOAD_TEXT')?>'
				}
			);

			var uid = "<?=CUtil::JSEscape($UID)?>";
			var dispatcher = BX.CrmEntityDispatcher.create(
				uid,
				{
					typeName: "STATUS",
					data: <?=CUtil::PhpToJSObject($dispatcherData)?>,
					serviceUrl: ""
				}
			);

			var list = BX.CrmProgressBarListView.create(
				uid,
				{
					typeName: 'STATUS',
					wrapperId: uid,
					dispatcher: dispatcher,
					mode: '<?=CUtil::JSEscape($arResult['MODE'])?>',
					entityTypeName: '<?=CUtil::JSEscape($arResult['ENTITY_TYPE_NAME'])?>',
					currentStepId: '<?=CUtil::JSEscape($currentStepID)?>',
					disabledStepIds: <?=CUtil::PhpToJSObject($arResult['DISABLED_STEP_IDS'])?>,
					contextId: '<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>'
				}
			);
			list.initializeFromExternalData();
		}
	);
</script>
