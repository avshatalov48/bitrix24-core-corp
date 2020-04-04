<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$UID = $arResult['UID'];
$prefix = htmlspecialcharsbx($UID);

?><div class="crm_block_container">
	<div class="crm_card p0">
		<div class="crm_card" style="padding: 0 15px;">
			<div class="crm_card_image">
			<?if($arResult['USER_PHOTO_URL'] !== ''):?>
				<img src="<?=htmlspecialcharsbx($arResult['USER_PHOTO_URL'])?>" alt="" />
			<?endif;?>
			</div>
			<div class="crm_card_name_meeting"><?=htmlspecialcharsbx($arResult['USER_ACTUAL_NAME'])?></div>
			<div class="crm_card_description_meeting"><?=htmlspecialcharsbx($arResult['USER_ACTUAL_EMAIL'])?></div>
			<div class="clearboth"></div>
		</div>
		<br/>
		<hr/>
		<div class="crm_card_name tar" style="margin-left: 0;">
			<div style="padding: 0 15px;">
				<div class="crm_input_desc p0 tal"><?=htmlspecialcharsbx(GetMessage("M_CRM_CONFIG_USER_EMAIL_INPUT_LEGEND"))?>:</div>
				<input id="<?=$prefix?>_email" class="crm_input_text" type="text" placeholder="" value="<?=htmlspecialcharsbx($arResult["USER_ACTUAL_ADDRESSER"])?>"/>
			</div>
		</div>
	</div>
	<div class="clearboth"></div>
</div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var context = BX.CrmMobileContext.getCurrent();
			context.enableReloadOnPullDown(
				{
					pullText: "<?=GetMessageJS('M_CRM_CONFIG_USER_EMAIL_PULL_TEXT')?>",
					downText: "<?=GetMessageJS('M_CRM_CONFIG_USER_EMAIL_DOWN_TEXT')?>",
					loadText: "<?=GetMessageJS('M_CRM_CONFIG_USER_EMAIL_LOAD_TEXT')?>"
				}
			);

			var config = BX.CrmUserEmailConfigurator.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					prefix: "<?=CUtil::JSEscape($UID)?>",
					contextId: "<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>",
					serviceUrl: "<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>"
				}
			);

			context.createButtons(
				{
					back:
					{
						type: 'right_text',
						style: 'custom',
						position: 'left',
						name: '<?=GetMessageJS('M_CRM_CONFIG_USER_EMAIL_CANCEL_BTN')?>',
						callback: context.createCloseHandler()
					},
					save:
					{
						type: 'right_text',
						style: 'custom',
						position: 'right',
						name: '<?=GetMessageJS("M_CRM_CONFIG_USER_EMAIL_SAVE_BTN")?>',
						callback: config.createSaveHandler()
					}
				}
			);
		}
	);
</script>
