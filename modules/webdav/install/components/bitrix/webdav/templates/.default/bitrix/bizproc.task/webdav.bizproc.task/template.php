<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

?>
<div class="bizproc-page-task">
<?
if (!empty($arResult["ERROR_MESSAGE"])):
	ShowError($arResult["ERROR_MESSAGE"]);
endif;

$docID = $arResult["TASK"]["PARAMETERS"]["DOCUMENT_ID"][2];
$docUrl = str_replace(array("#ELEMENT_ID#", "#ID#"), $docID, $arParams["DOWNLOAD_URL"]);
if ($arResult["ShowMode"] == "Success"):
?>
<fieldset class="bizproc-item bizproc-task">
	<legend class="bizproc-item-legend bizproc-task-legend">
		<?= $arResult["TASK"]["NAME"] ?>
	</legend>
	<?if (strlen($arResult["TASK"]["DESCRIPTION"]) > 0):?>
	<div class="bizproc-item-description bizproc-task-description">
		<?=$arResult["TASK"]["DESCRIPTION"]?>
	</div>
	<?endif;
	if (!empty($arResult["TASK"]["URL"])):?>
	<div class="bizproc-item-description bizproc-task-document">
		<a target="_blank" href="<?=$docUrl?>"><?=GetMessage("BPAT_GOTO_DOC")?></a>
	</div>
	<?endif;?>
	<div class="bizproc-item-text bizproc-task-success">
		<?=GetMessage("BPATL_SUCCESS")?>
	</div>
</fieldset>
<?
else:
?>
<form method="post" name="task_form1" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
	<input type="hidden" name="action" value="doTask" />
	<input type="hidden" name="id" value="<?= intval($arResult["TASK"]["ID"]) ?>" />
	<input type="hidden" name="workflow_id" value="<?= htmlspecialcharsbx($arResult["TASK"]["WORKFLOW_ID"]) ?>" />
	<input type="hidden" name="back_url" value="<?= htmlspecialcharsbx($arParams["REDIRECT_URL"]) ?>" /> 
	<?= bitrix_sessid_post() ?>

<fieldset class="bizproc-item bizproc-task">
	<legend class="bizproc-item-legend bizproc-task-legend">
		<?= $arResult["TASK"]["NAME"] ?>
	</legend>
	<?if (strlen($arResult["TASK"]["~DESCRIPTION"]) > 0):?>
	<div class="bizproc-item-description bizproc-task-description">
		<br />
		<?=nl2br($arResult["TASK"]["DESCRIPTION"])?>
	</div>
	<?endif;?>
	<div class="bizproc-item-text bizproc-task-text">
		<div class="bizproc-task-document">
			<a target="_blank" href="<?=$docUrl?>"><?=GetMessage("BPAT_GOTO_DOC")?></a>
		</div>
		<div class="bizproc-field bizproc-field-text">
			<!--label class="bizproc-field-name">
				<span class="bizproc-field-title"><?=GetMessage("BPAT_COMMENT")?>: </span>
			</label-->
			<span class="bizproc-field-value">
				<table class="bizproc-table-main bizproc-task-table" cellpadding="3" border="0">
					<?= $arResult["TaskForm"]?>
				</table>
			</span>
		</div>
	</div>
	<div class="bizproc-item-buttons">
		<?=$arResult["TaskFormButtons"]?>
	</div>
</fieldset>
</form>
<?
endif;	
?>
</div>
<br><br>
<h3><?=GetMessage("BPATL_DOC_HISTORY")?></h3>
<?
$APPLICATION->IncludeComponent(
	"bitrix:bizproc.log",
	"webdav.bizproc.log",
	array(
		"COMPONENT_VERSION" => 2,
		"ID" => $arResult["TASK"]["WORKFLOW_ID"],
		"SET_TITLE" => "N",
		"INLINE_MODE" => "Y",
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"]
	),
	$component->__parent
);
?>
