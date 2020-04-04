<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$UID = $arResult['UID'];
$prefix = $UID;
$prefixHtml = htmlspecialcharsbx($UID);

//prefix: uid,

?><div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
	<div class="crm_block_container">
		<div id="<?=$prefixHtml?>_product_name" class="crm_block_title fln" style="padding-bottom: 0;"><?=htmlspecialcharsbx($arResult['PRODUCT_NAME'])?></div>
		<hr/>
		<div class="crm_card tac whsnw" style="padding-bottom: 10px;padding-top: 10px;">
			<div id="<?=$prefixHtml?>_quantity_decrement" class="crm_colminus"></div>
			<input id="<?=$prefixHtml?>_quantity" class="crm_input_text dib tac vam" style="font-size: 21px;width: 90px;" type="text" value="<?=htmlspecialcharsbx($arResult['QUANTITY'])?>" />
			<div id="<?=$prefixHtml?>_quantity_increment" class="crm_colplus"></div>
		</div>
		<hr/>
		<div class="crm_card tac whsnw" style="padding-bottom: 10px;padding-top: 10px;">
			<input id="<?=$prefixHtml?>_price" class="crm_input_text dib tac" style="font-size: 21px;width: 180px;" type="text" value="<?=htmlspecialcharsbx($arResult['PRICE'])?>" />
		</div>
		<hr/>
		<div class="crm_card tac whsnw" style="padding-bottom: 10px;padding-top: 10px;font-size: 21px;">
			<span style="color: #000;"><?=htmlspecialcharsbx(GetMessage("M_CRM_PRODUCT_ROW_EDIT_SUM"))?>: </span><span id="<?=$prefixHtml?>_formatted_sum"><?=htmlspecialcharsbx($arResult['FORMATTED_SUM'])?></span>
		</div>
		<div class="clearboth"></div>
	</div>
</div>

<script type="text/javascript">
	BX.ready(
		function()
		{
			var context = BX.CrmMobileContext.getCurrent();
			context.enableReloadOnPullDown(
				{
					pullText: '<?=GetMessageJS('M_CRM_PRODUCT_ROW_PULL_TEXT')?>',
					downText: '<?=GetMessageJS('M_CRM_PRODUCT_ROW_DOWN_TEXT')?>',
					loadText: '<?=GetMessageJS('M_CRM_PRODUCT_ROW_LOAD_TEXT')?>'
				}
			);

			var editor = BX.CrmProductRowEditor.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					prefix: "<?=CUtil::JSEscape($prefix)?>",
					serviceUrl: "<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>"
				}
			);

			editor.initializeFromExternalData();

			context.createButtons(
				{
					save:
					{
						type: "right_text",
						style: "custom",
						position: "right",
						name: "<?=GetMessageJS('M_CRM_PRODUCT_ROW_EDIT_SAVE_BTN')?>",
						callback: editor.createSaveHandler()
					}
				}
			);
		}
	);
</script>
