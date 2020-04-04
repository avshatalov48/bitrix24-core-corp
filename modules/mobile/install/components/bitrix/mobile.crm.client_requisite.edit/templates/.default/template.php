<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$UID = $arResult['UID'];
$prefix = htmlspecialcharsbx($UID);
?>
<div class="crm_toppanel">
	<div class="crm_filter">
		<span class="crm_raport_icon"></span>
		<?=htmlspecialcharsbx(GetMessage('M_CRM_REQUISITE_EDIT_TITLE'))?>
	</div>
</div>
<div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
</div>

<script type="text/javascript">
	BX.ready(
		function()
		{
			var context = BX.CrmMobileContext.getCurrent();
			context.enableReloadOnPullDown(
				{
					pullText: '<?=GetMessageJS('M_CRM_REQUISITE_EDIT_PULL_TEXT')?>',
					downText: '<?=GetMessageJS('M_CRM_REQUISITE_EDIT_DOWN_TEXT')?>',
					loadText: '<?=GetMessageJS('M_CRM_REQUISITE_EDIT_LOAD_TEXT')?>'
				}
			);

			var uid = "<?=CUtil::JSEscape($UID)?>";
			var editor = BX.CrmClientRequisiteEditor.create(
				uid,
				{
					contextId: "<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>",
					personTypeId: <?=CUtil::JSEscape($arResult['PERSON_TYPE_ID'])?>,
					containerId: "<?=CUtil::JSEscape($UID)?>"
				}
			);

			context.createButtons(
				{
					back:
					{
						type: "right_text",
						style: "custom",
						position: "left",
						name: "<?=GetMessageJS('M_CRM_REQUISITE_EDIT_CANCEL_BTN')?>",
						callback: context.createCloseHandler()
					},
					save:
					{
						type: "right_text",
						style: "custom",
						position: "right",
						name: "<?=GetMessageJS("M_CRM_REQUISITE_EDIT_SAVE_BTN")?>",
						callback: editor.createSaveHandler()
					}
				}
			);

			editor.initializeFromExternalData();
		}
	);
</script>
