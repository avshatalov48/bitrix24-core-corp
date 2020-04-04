<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */
use Bitrix\Main\Localization\Loc;

$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/utils.js');
if(isset($_GET['old']))
{
	CBPDocument::AddShowParameterInit(
			$arResult["DOCUMENT_DATA"]["WEBDAV"]["DOCUMENT_TYPE"][0],
			"only_users",
			$arResult["DOCUMENT_DATA"]["WEBDAV"]["DOCUMENT_TYPE"][2],
			$arResult["DOCUMENT_DATA"]["WEBDAV"]["DOCUMENT_TYPE"][1],
			$arResult["DOCUMENT_DATA"]["WEBDAV"]["DOCUMENT_ID"][2]
	);
}
else
{
	CBPDocument::AddShowParameterInit(
			$arResult["DOCUMENT_DATA"]["DISK"]["DOCUMENT_TYPE"][0],
			"only_users",
			$arResult["DOCUMENT_DATA"]["DISK"]["DOCUMENT_TYPE"][2],
			$arResult["DOCUMENT_DATA"]["DISK"]["DOCUMENT_TYPE"][1],
			$arResult["DOCUMENT_DATA"]["DISK"]["DOCUMENT_ID"][2]
	);
}
?>
<div class="bizproc-page-workflow-start">
<?
if ($arResult["SHOW_MODE"] == "StartWorkflowSuccess")
{
	if(!empty($arResult["TEMPLATES"][$arParams["TEMPLATE_ID"]]["NAME"]))
	{
		ShowNote(str_replace("#TEMPLATE#", $arResult["TEMPLATES"][$arParams["TEMPLATE_ID"]]["NAME"], Loc::getMessage("BPABS_MESSAGE_SUCCESS")));
	}
	else
	{
		ShowNote(str_replace("#TEMPLATE#", $arResult["TEMPLATES_OLD"][$arParams["TEMPLATE_ID"]]["NAME"], Loc::getMessage("BPABS_MESSAGE_SUCCESS")));
	}
}
elseif ($arResult["SHOW_MODE"] == "StartWorkflowError")
{
	if(!empty($arResult["TEMPLATES"][$arParams["TEMPLATE_ID"]]["NAME"]))
	{
		ShowNote(str_replace("#TEMPLATE#", $arResult["TEMPLATES"][$arParams["TEMPLATE_ID"]]["NAME"], Loc::getMessage("BPABS_MESSAGE_ERROR")));
	}
	else
	{
		ShowNote(str_replace("#TEMPLATE#", $arResult["TEMPLATES_OLD"][$arParams["TEMPLATE_ID"]]["NAME"], Loc::getMessage("BPABS_MESSAGE_ERROR")));
	}
}
elseif ($arResult["SHOW_MODE"] == "WorkflowParameters")
{
	if(isset($_GET['old']))
	{?>
		<form method="post" name="start_workflow_form1" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
			<input type="hidden" name="workflow_template_id" value="<?=intval($arParams["TEMPLATE_ID"]) ?>" />
			<input type="hidden" name="document_type" value="<?= htmlspecialcharsbx($arResult["DOCUMENT_DATA"]["WEBDAV"]["DOCUMENT_TYPE"][2]) ?>" />
			<input type="hidden" name="document_id" value="<?= htmlspecialcharsbx($arResult["DOCUMENT_DATA"]["WEBDAV"]["DOCUMENT_ID"][2]) ?>" />
			<input type="hidden" name="back_url" value="<?= htmlspecialcharsbx($arResult["back_url"]) ?>" />
			<?= bitrix_sessid_post() ?>
			<fieldset class="bizproc-item bizproc-workflow-template">
				<legend class="bizproc-item-legend bizproc-workflow-template-title">
					<?=$arResult["TEMPLATES_OLD"][$arParams["TEMPLATE_ID"]]["NAME"]?>
				</legend>
				<?if($arResult["TEMPLATES_OLD"][$arParams["TEMPLATE_ID"]]["DESCRIPTION"]!=''):?>
					<div class="bizproc-item-description bizproc-workflow-template-description">
						<?= $arResult["TEMPLATES_OLD"][$arParams["TEMPLATE_ID"]]["DESCRIPTION"] ?>
					</div>
				<?endif;

				if (!empty($arResult["TEMPLATES_OLD"][$arParams["TEMPLATE_ID"]]["PARAMETERS"]))
				{
					?>
					<div class="bizproc-item-text">
						<ul class="bizproc-list bizproc-workflow-template-params">
							<?
							foreach ($arResult["TEMPLATES_OLD"][$arParams["TEMPLATE_ID"]]["PARAMETERS"] as $parameterKey => $arParameter)
							{
								if ($parameterKey == "TargetUser")
									continue;
								?>
								<li class="bizproc-list-item bizproc-workflow-template-param">
									<div class="bizproc-field bizproc-field-<?=$arParameter["Type"]?>">
										<label class="bizproc-field-name">
											<?=($arParameter["Required"] ? "<span class=\"required\">*</span> " : "")?>
											<span class="bizproc-field-title"><?=htmlspecialcharsbx($arParameter["Name"])?></span><?
											if (strlen($arParameter["Description"]) > 0):
												?><span class="bizproc-field-description"> (<?=htmlspecialcharsbx($arParameter["Description"])?>)</span><?
											endif;
											?>:
										</label>
			<span class="bizproc-field-value"><?
				echo $arResult["DocumentService"]->GetFieldInputControl(
					$arResult["DOCUMENT_DATA"]["WEBDAV"]["DOCUMENT_TYPE"],
					$arParameter,
					array("Form" => "start_workflow_form1", "Field" => $parameterKey),
					$arResult["PARAMETERS_VALUES"][$parameterKey],
					false,
					true
				);
				?></span>
									</div>
								</li>
							<?
							}
							?>
						</ul>
					</div>
				<?
				}
				?>
				<div class="bizproc-item-buttons bizproc-workflow-start-buttons">
					<input type="submit" name="DoStartParamWorkflow" value="<?= Loc::getMessage("BPABS_DO_START") ?>" />
					<input type="submit" name="CancelStartParamWorkflow" value="<?= Loc::getMessage("BPABS_DO_CANCEL") ?>" />
				</div>
			</fieldset>
		</form>
	<?}
	else
	{?>
		<form method="post" name="start_workflow_form1" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
		<input type="hidden" name="workflow_template_id" value="<?=intval($arParams["TEMPLATE_ID"]) ?>" />
		<input type="hidden" name="document_type" value="<?= htmlspecialcharsbx($arResult["DOCUMENT_DATA"]["DISK"]["DOCUMENT_TYPE"][2]) ?>" />
		<input type="hidden" name="document_id" value="<?= htmlspecialcharsbx($arResult["DOCUMENT_DATA"]["DISK"]["DOCUMENT_ID"][2]) ?>" />
		<input type="hidden" name="back_url" value="<?= htmlspecialcharsbx($arResult["back_url"]) ?>" />
		<?= bitrix_sessid_post() ?>
		<fieldset class="bizproc-item bizproc-workflow-template">
			<legend class="bizproc-item-legend bizproc-workflow-template-title">
				<?=$arResult["TEMPLATES"][$arParams["TEMPLATE_ID"]]["NAME"]?>
			</legend>
			<?if($arResult["TEMPLATES"][$arParams["TEMPLATE_ID"]]["DESCRIPTION"]!=''):?>
				<div class="bizproc-item-description bizproc-workflow-template-description">
					<?= $arResult["TEMPLATES"][$arParams["TEMPLATE_ID"]]["DESCRIPTION"] ?>
				</div>
			<?endif;

			if (!empty($arResult["TEMPLATES"][$arParams["TEMPLATE_ID"]]["PARAMETERS"]))
			{
				?>
				<div class="bizproc-item-text">
					<ul class="bizproc-list bizproc-workflow-template-params">
						<?
						foreach ($arResult["TEMPLATES"][$arParams["TEMPLATE_ID"]]["PARAMETERS"] as $parameterKey => $arParameter)
						{
							if ($parameterKey == "TargetUser")
								continue;
							?>
							<li class="bizproc-list-item bizproc-workflow-template-param">
								<div class="bizproc-field bizproc-field-<?=$arParameter["Type"]?>">
									<label class="bizproc-field-name">
										<?=($arParameter["Required"] ? "<span class=\"required\">*</span> " : "")?>
										<span class="bizproc-field-title"><?=htmlspecialcharsbx($arParameter["Name"])?></span><?
										if (strlen($arParameter["Description"]) > 0):
											?><span class="bizproc-field-description"> (<?=htmlspecialcharsbx($arParameter["Description"])?>)</span><?
										endif;
										?>:
									</label>
			<span class="bizproc-field-value"><?
				echo $arResult["DocumentService"]->GetFieldInputControl(
					$arResult["DOCUMENT_DATA"]["DISK"]["DOCUMENT_TYPE"],
					$arParameter,
					array("Form" => "start_workflow_form1", "Field" => $parameterKey),
					$arResult["PARAMETERS_VALUES"][$parameterKey],
					false,
					true
				);
				?></span>
								</div>
							</li>
						<?
						}
						?>
					</ul>
				</div>
			<?
			}
			?>
			<div class="bizproc-item-buttons bizproc-workflow-start-buttons">
				<input type="submit" name="DoStartParamWorkflow" value="<?= Loc::getMessage("BPABS_DO_START") ?>" />
				<input type="submit" name="CancelStartParamWorkflow" value="<?= Loc::getMessage("BPABS_DO_CANCEL") ?>" />
			</div>
		</fieldset>
		</form>
	<?}
}
elseif ($arResult["SHOW_MODE"] == "SelectWorkflow" && (count($arResult["TEMPLATES"]) > 0 || count($arResult["TEMPLATES_OLD"]) > 0))
{
?>
	<ul class="bizproc-list bizproc-workflow-templates">
		<?foreach ($arResult["TEMPLATES"] as $workflowTemplateId => $workflowTemplate):?>
			<li class="bizproc-list-item bizproc-workflow-template">
				<div class="bizproc-item-title">
					<a href="<?=$arResult["TEMPLATES"][$workflowTemplate["ID"]]["URL"]?>"><?=$workflowTemplate["NAME"]?></a>
				</div>
				<?if (strlen($workflowTemplate["DESCRIPTION"]) > 0):?>
				<div class="bizproc-item-description">
					<?= $workflowTemplate["DESCRIPTION"] ?>
				</div>
				<?endif;?>
			</li>
		<?endforeach;?>
	</ul>
	<? if(!empty($arResult["TEMPLATES_OLD"])) { ?>
	<p><?= Loc::getMessage("DISK_TITLE_TEMPLATES_OLD") ?></p>
	<hr>
	<ul class="bizproc-list bizproc-workflow-templates">
		<?foreach ($arResult["TEMPLATES_OLD"] as $workflowTemplateId => $workflowTemplate):?>
			<li class="bizproc-list-item bizproc-workflow-template">
				<div class="bizproc-item-title">
					<a href="<?=$arResult["TEMPLATES_OLD"][$workflowTemplate["ID"]]["URL"]?>"><?=$workflowTemplate["NAME"]?></a>
				</div>
				<?if (strlen($workflowTemplate["DESCRIPTION"]) > 0):?>
					<div class="bizproc-item-description">
						<?= $workflowTemplate["DESCRIPTION"] ?>
					</div>
				<?endif;?>
			</li>
		<?endforeach;?>
	<ul>
	<? } ?>
<?
}
elseif ($arResult["SHOW_MODE"] == "SelectWorkflow")
{
	ShowNote(Loc::getMessage("BPABS_NO_TEMPLATES"));
}
?>
</div>