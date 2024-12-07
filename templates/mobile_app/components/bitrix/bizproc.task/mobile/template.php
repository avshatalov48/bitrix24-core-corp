<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("mobileapp"))
	die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script src="'
	.CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/bizproc_mobile.js')
	.'"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'task-card-page');

if (empty($arResult['DOCUMENT_ICON']))
{
	$moduleIcon = 'default';
	if (in_array($arResult['TASK']['MODULE_ID'], array('crm', 'disk', 'iblock', 'lists', 'tasks')))
		$moduleIcon = $arResult['TASK']['MODULE_ID'];

	$arResult['DOCUMENT_ICON'] = '/bitrix/templates/mobile_app/images/bizproc/document/bp-'.$moduleIcon.'-icon.png';
}
?>
<div class="pb-popup-mobile bp-task">
	<div class="bp-post bp-lent">
<?
if (!empty($arResult["ERROR_MESSAGE"]))
	ShowError($arResult["ERROR_MESSAGE"]);
?>
	<span class="bp-title-desc-icon">
		<img src="<?=htmlspecialcharsbx($arResult['DOCUMENT_ICON'])?>" width="36" border="0" />
	</span>
	<div class="post-text-title"><?=$arResult["TASK"]["NAME"]?></div>
	<?if ($arResult["TASK"]["DOCUMENT_NAME"]):?>
	<span class="bp-title-desc">
		<span class=""><?=$arResult["TASK"]["DOCUMENT_NAME"]?></span>
	</span>
	<?endif?>
	<div class="bp-short-process-inner">
		<?$APPLICATION->IncludeComponent(
			"bitrix:bizproc.workflow.faces",
			"",
			array(
				"WORKFLOW_ID" => $arResult["TASK"]["WORKFLOW_ID"],
				"TARGET_TASK_ID" => $arResult["TASK"]["ID"]
			),
			$component
		);
		?>
	</div>
	<?
		if ($arResult['ReadOnly']):
			echo '<span class="bp-status"></span>';
		elseif ($arResult["ShowMode"] == "Success"):
			switch ($arResult["TASK"]['USER_STATUS'])
			{
				case CBPTaskUserStatus::Yes:
					echo '<span class="bp-status-ready"><span>'.GetMessage('BPATL_USER_STATUS_YES').'</span></span>';
					break;
				case CBPTaskUserStatus::No:
				case '4': //CBPTaskUserStatus::Cancel
					echo '<span class="bp-status-cancel"><span>'.GetMessage('BPATL_USER_STATUS_NO').'</span></span>';
					break;
				default:
					echo '<span class="bp-status-ready"><span>'.GetMessage('BPATL_USER_STATUS_OK').'</span></span>';
			}
		elseif ($arResult["TASK"]['IS_INLINE'] == 'Y'):?>
			<div class="bp-btn-panel">
				<div class="">
					<?
					foreach ($arResult['TaskControls']['BUTTONS'] as $control):
						$class = $control['TARGET_USER_STATUS'] == CBPTaskUserStatus::Yes || $control['TARGET_USER_STATUS'] == CBPTaskUserStatus::Ok ? 'accept' : 'decline';
						$props = CUtil::PhpToJSObject(array(
							'TASK_ID' => $arResult["TASK"]['ID'],
							$control['NAME'] => $control['VALUE'],
						));
						?>

						<a href="javascript:void(0)" onclick="return BX.BizProcMobile.doTask(<?=$props?>, function(){BXMobileApp.UI.Page.close({drop: true});});" class="webform-small-button bp-small-button webform-small-button-<?=$class?> mobile-small-button-<?=$class?>">
							<span class="bp-button-icon"></span>
							<span class="bp-button-text"><?=$control['TEXT']?></span>
						</a>
					<?
					endforeach;
					?>
				</div>
			</div>
			<?else: echo '<br/>';?>
		<?endif?>


	<div class="bp-task-block">
		<?
		if (!empty($arResult["ERROR_MESSAGE"])):
			ShowError($arResult["ERROR_MESSAGE"]);
		endif;
		?>
		<span class="bp-task-block-title"><?=GetMessage("BPATL_TASK_TITLE")?>: </span>
		<?
		if ($arResult["TASK"]["DESCRIPTION"] <> ''):
			echo nl2br($arResult["TASK"]["DESCRIPTION"]);
		else:
			echo $arResult["TASK"]["NAME"];
		endif;

		if (!empty($arResult['TASK']['PARAMETERS']['DOCUMENT_URL'])):
?>
	<div style="margin: 7px 0">
		<a href="javascript:void(0)" onclick="BXMobileApp.PageManager.loadPageBlank({'url':'<?=$arResult['TASK']['PARAMETERS']['DOCUMENT_URL']?>'});"><?=GetMessage("BPAT_GOTO_DOC")?></a>
	</div>
<?
endif;


		if ($arResult["SKIP_BP"] == "Y" && $arResult["ShowMode"] != "Success"):?>
			<div class="bp-errortext"><?=GetMessage("MB_BP_SKIP_MSGVER_1")?></div>
		<? elseif ($arResult["ShowMode"] != "Success" && $arResult["TASK"]['IS_INLINE'] != 'Y'):?>
			<form class="bp-task-form" method="post" name="task_form1" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data" onsubmit="return false;">
				<?= bitrix_sessid_post() ?>
				<input type="hidden" name="action" value="doTask" />
				<input type="hidden" name="" value="" id="bp_task_submiter">
				<input type="hidden" name="TASK_ID" value="<?= intval($arResult["TASK"]["ID"]) ?>" />
				<input type="hidden" name="workflow_id" value="<?= htmlspecialcharsbx($arResult["TASK"]["WORKFLOW_ID"]) ?>" />
				<input type="hidden" name="back_url" value="<?= htmlspecialcharsbx($arParams["REDIRECT_URL"]) ?>" />

				<?if (!empty($arResult["TaskForm"])):?>
				<div class="bizproc-detail-block">
					<table class="bizproc-table-main bizproc-task-table" cellpadding="0" border="0">
						<?= $arResult["TaskForm"]?>
					</table>
				</div>
				<?endif?>
				<div class="bizproc-item-buttons">
					<?if (!empty($arResult['TaskControls']['BUTTONS'])):?>
						<?
						foreach ($arResult['TaskControls']['BUTTONS'] as $control):
							$class = $control['TARGET_USER_STATUS'] == CBPTaskUserStatus::Yes || $control['TARGET_USER_STATUS'] == CBPTaskUserStatus::Ok ? 'accept' : 'decline';
							$props = CUtil::PhpToJSObject(array(
								'TASK_ID' => $arResult["TASK"]['ID'],
								$control['NAME'] => $control['VALUE']
							));
							?>
							<button type="submit" name="<?=htmlspecialcharsbx($control['NAME'])?>"
									value="<?=htmlspecialcharsbx($control['VALUE'])?>"
									class="webform-small-button bp-small-button webform-small-button-<?=$class?> mobile-small-button-<?=$class?>"
									style="border: none">
								<span class="bp-button-icon"></span>
								<span class="bp-button-text"><?=htmlspecialcharsbx($control['TEXT'])?></span>
							</button>
						<?
						endforeach;
						?>
					<?else: echo $arResult["TaskFormButtons"]; endif;?>
				</div>
			</form>
		<?endif;?>
	</div>
</div>
</div>

<script>
	BX.ready(function(){

		BX.message({'MB_BP_DETAIL_ALERT': '<?=GetMessageJS('MB_BP_DETAIL_ALERT')?>'});

		var bpForm = document.forms["task_form1"],
			submiter = BX('bp_task_submiter');

		var children = BX.findChildren(bpForm, {property: {type: 'submit'}}, true);
		for (var i=0; i<children.length; i++)
		{
			var cb = function()
			{
				submiter.name =  this.name;
				submiter.value = this.value;
			};
			BX.bind(children[i], 'click', cb);
		}

		if (bpForm)
		{
			BX.bind(bpForm, "submit", function(){

				var formData = new FormData(bpForm);
				return BX.BizProcMobile.doTask(
					formData,
					function()
					{
						BXMobileApp.UI.Page.close({drop: true});
					}
				);
			});
		}
	});

	app.pullDown({
		enable:   true,
		pulltext: '<?php echo GetMessageJS('MB_BP_DETAIL_PULLDOWN_PULL'); ?>',
		downtext: '<?php echo GetMessageJS('MB_BP_DETAIL_PULLDOWN_DOWN'); ?>',
		loadtext: '<?php echo GetMessageJS('MB_BP_DETAIL_PULLDOWN_LOADING'); ?>',
		callback: function()
		{
			app.reload();
		}
	});
</script>