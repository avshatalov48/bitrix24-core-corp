<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
$arParams["SHOW_WORKFLOW"] = ($arParams["SHOW_WORKFLOW"] == "N" ? "N" : "Y");
$arCurrentUserGroups = $arResult["CurrentUserGroups"];

/********************************************************************
				/Input params
********************************************************************/
if (!empty($arResult["ERROR_MESSAGE"])):
	ShowError($arResult["ERROR_MESSAGE"]);
endif;
?>
<form action="<?=POST_FORM_ACTION_URI?>" method="POST" class="wd-form" enctype="multipart/form-data">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="ELEMENT_ID" value="<?=$arParams["ELEMENT_ID"]?>" />
	<input type="hidden" name="edit" value="Y" />
	<input type="hidden" name="ACTION" value="<?=$arParams["ACTION"]?>" />

<table cellpadding="0" cellspacing="0" border="0" width="100%" class="wd-main webdav-form">
	<tbody class="info">
		<tr><th><?=GetMessage("WD_FILE")?>:</th>
			<td>
				<div class="element-icon ic<?=substr($arResult["ELEMENT"]["EXTENTION"], 1)?>"></div>
				<a target="_blank" href="<?=$arResult["ELEMENT"]["URL"]["DOWNLOAD"]?>" title="<?=GetMessage("WD_OPEN_FILE")?>" <?
					if (in_array($arResult["ELEMENT"]["EXTENTION"], array(".doc", ".docx", ".xls", ".xlsx", ".rtf", ".ppt", ".pptx")))
					{
						?> onclick="return EditDocWithProgID('<?=CUtil::JSEscape($arResult["ELEMENT"]["URL"]["FILE"])?>')"<?
					}
				?>><?=$arResult["ELEMENT"]["FULL_NAME"]?></a>
				<span class="wd-item-controls element_view"><a href="<?=$arResult["ELEMENT"]["URL"]["VIEW"]?>" title="<?=GetMessage("WD_VIEW_FILE_TITLE")?>">
					<?=GetMessage("WD_VIEW_FILE")?></a></span>
			</td>
		</tr>
		<tr>
			<th><?=GetMessage("WD_FILE_CREATED")?>:</th>
			<td><?=$arResult["ELEMENT"]["DATE_CREATE"]?>
				<?$arUser = $arResult["USERS"][$arResult["ELEMENT"]["CREATED_BY"]];
				if (empty($arUser)):
					?><span class="wd-user"><?=$arResult["ELEMENT"]["CREATED_BY"]?><?
				else: 
					?><a href="<?=$arUser["URL"]?>"><?=$arUser["NAME"]." ".$arUser["LAST_NAME"]?></a></span><?
				endif;?>
			</td>
		</tr>
		<tr>
			<th><?=GetMessage("WD_FILE_MODIFIED")?>: </th>
			<td><?=$arResult["ELEMENT"]["TIMESTAMP_X"]?> 
				<?$arUser = $arResult["USERS"][$arResult["ELEMENT"]["MODIFIED_BY"]];
				if (empty($arUser)):
					?><span class="wd-user"><?=$arResult["ELEMENT"]["MODIFIED_BY"]?><?
				else: 
					?><a href="<?=$arUser["URL"]?>"><?=$arUser["NAME"]." ".$arUser["LAST_NAME"]?></a></span><?
				endif;?>
			</td>
		</tr>
		<tr>
			<th><?=GetMessage("WD_FILE_SIZE")?>: </th>
			<td><?
				if ($arParams["USE_WORKFLOW"] != "Y"):
					?><?=$arResult["ELEMENT"]["FILE_SIZE"]?> <?
					?><span class="wd-item-controls element_download"><a target="_blank" href="<?=$arResult["ELEMENT"]["URL"]["DOWNLOAD"]?>"><?=GetMessage("WD_DOWNLOAD_FILE")?></a></span><?
				else:
					?><?=GetMessage("WD_WF_CURRENT_VERSION")?> <?=$arResult["ELEMENT"]["FILE_SIZE"]?> <?
					?><span class="wd-item-controls element_download"><a target="_blank" href="<?=$arResult["ELEMENT"]["URL"]["DOWNLOAD"]?>"><?=GetMessage("WD_DOWNLOAD_FILE")?></a></span><?
					
					if (!empty($arResult["ELEMENT"]["ORIGINAL"])):
					?><br /><?=GetMessage("WD_WF_ORIGINAL")?> <?=$arResult["ELEMENT"]["ORIGINAL"]["FILE_SIZE"]?> <?
					?><span class="wd-item-controls element_download"><a target="_blank" href="<?=$arResult["ELEMENT"]["URL"]["DOWNLOAD_ORIGINAL"]?>"><?=GetMessage("WD_DOWNLOAD_FILE")?></a></span><?
					endif;
				endif;
?>
			</td>
		</tr>
	</tbody>
	<tbody class="main">
		<tr class="header"><th colspan="2"><?=GetMessage("WD_MAIN_PARAMS")?></th></tr>
		<tr>
			<th><span class="required starrequired">*</span><?=GetMessage("WD_NAME")?>:</th>
			<td><input type="text" class="text_file" name="NAME" value="<?=$arResult["ELEMENT"]["NAME"]?>" /><?=$arResult["ELEMENT"]["EXTENTION"]?></td>
		</tr>
		<tr>
			<th><?=GetMessage("WD_TAGS")?>:</th>
			<td>
<?
	if (IsModuleInstalled("search")):
		$APPLICATION->IncludeComponent(
			"bitrix:search.tags.input", 
			"", 
			array(
				"VALUE" => $arResult["ELEMENT"]["~TAGS"], 
				"NAME" => "TAGS"), 
			null,
			array(
				"HIDE_ICONS" => "Y"));
	elseif ($arParams["SHOW_TAGS"] == "Y"):
		?><input type="text" class="text" name="TAGS" value="<?=$arResult["ELEMENT"]["TAGS"]?>" /><?
	endif;
?>
			</td>
		</tr>
		<tr>
			<th><?=GetMessage("WD_FILE_REPLACE")?>:</th>
			<td><input type="file" class="file" name="<?=$arParams["NAME_FILE_PROPERTY"]?>" value="" /></td>
		</tr>
		<tr>
			<th><?=GetMessage("WD_FILE_ACTIVITY")?>:</th>
			<td>
				<input type="checkbox" class="checkbox" name="ACTIVE" id="WEBDAV_ACTIVE" value="Y" <?=($arResult["ELEMENT"]["ACTIVE"]=="Y" ? "checked" : "")?> />
				<label for="WEBDAV_ACTIVE"><?=GetMessage("WD_ACTIVE")?></label>
			</td>
		</tr>
		<tr>
			<th><?=GetMessage("WD_DESCRIPTION")?>:</th>
			<td><textarea name="PREVIEW_TEXT"><?=$arResult["ELEMENT"]["PREVIEW_TEXT"]?></textarea></td>
		</tr>
		<tr>
			<th><?=GetMessage("WD_PARENT_SECTION")?>:</th>
			<td>
				<select name="IBLOCK_SECTION_ID" class="select">
					<option value="0" <?=($arResult["ELEMENT"]["IBLOCK_SECTION_ID"] == 0 ? "selected" : "")?>><?=GetMessage("WD_CONTENT")?></option>
	<?
	foreach ($arResult["SECTION_LIST"] as $res)
	{
	?>
					<option value="<?=intVal($res["ID"])?>" <?=($arResult["ELEMENT"]["IBLOCK_SECTION_ID"] == $res["ID"] ? "selected=\"selected\" class=\"selected\" " : "")?>>
						<?=str_repeat(".", $res["DEPTH_LEVEL"])?><?=($res["NAME"])?></option>
	<?
	}
	?>
				</select>
			</td>
		</tr>
	</tbody>
<?
if ($arParams["USE_WORKFLOW"] == "Y")
{
?>
	<tbody class="wowkflow">
		<tr class="header"><th colspan="2"><?=GetMessage("WD_WF_PARAMS")?></th></tr>
<?if ($arParams["SHOW_WORKFLOW"] != "N"):?>
		<tr>
			<th><?=GetMessage("WD_FILE_STATUS")?>:</th>
			<td>
				<select name="WF_STATUS_ID">
				<?foreach ($arResult["WF_STATUSES"] as $key => $val):?>
					<option value="<?=$key?>"<?
					if ($key == $arResult["ELEMENT"]["WF_STATUS_ID"]):
						?> selected="selected" <?
					endif;
				?>><?=htmlspecialcharsEx($val)?></option><?
				endforeach;
				?>
				</select>
			</td>
		</tr>
<?endif;?>
		<tr>
			<th><?=GetMessage("WD_FILE_COMMENTS")?>:</th>
			<td><textarea name="WF_COMMENTS"><?=htmlspecialcharsEx($_REQUEST["WF_COMMENTS"])?></textarea></td>
		</tr>
	</tbody>
<?
}
elseif ($arParams["USE_BIZPROC"] == "Y")
{
?>
	<tbody class="wowkflow bizproc">
		<tr class="header">
			<th colspan="2">
				<a href="<?=$arResult["URL"]["BP"]?>"><?=GetMessage("IBEL_E_TAB_BIZPROC")?></a>
			</th>
		</tr>
		<tr>
			<th><?=GetMessage("IBEL_E_PUBLISHED")?></th>
			<td><?=($arResult["ELEMENT"]["BP_PUBLISHED"] == "Y" ? GetMessage("WD_Y") : GetMessage("WD_N"))?></td>
		</tr>
<?
	CBPDocument::AddShowParameterInit("webdav", "only_users", $arParams["BIZPROC"]["DOCUMENT_TYPE"], $arParams["BIZPROC"]["ENTITY"]);
	$arDocumentStates = CBPDocument::GetDocumentStates(
		array("webdav", $arParams["BIZPROC"]["ENTITY"], $arParams["BIZPROC"]["DOCUMENT_TYPE"]),
		array("webdav", $arParams["BIZPROC"]["ENTITY"], $arParams["ELEMENT_ID"]));

	$bizProcIndex = 0;
	$bizProcCounter = 0;

	if (!empty($arDocumentStates))	
	{
		foreach ($arDocumentStates as $arDocumentState)
		{
			$bizProcIndex++;
			$canViewWorkflow = CBPDocument::CanUserOperateDocument(
				CBPCanUserOperateOperation::ViewWorkflow,
				$GLOBALS["USER"]->GetID(),
				array("webdav", $arParams["BIZPROC"]["ENTITY"], 0),
				array(
					"DocumentType" => $arParams["BIZPROC"]["DOCUMENT_TYPE"], 
					"IBlockPermission" => $arParams["PERMISSION"], 
					"AllUserGroups" => $arCurrentUserGroups, 
					"DocumentStates" => $arDocumentStates, 
					"WorkflowId" => ($arDocumentState["ID"] > 0 ? $arDocumentState["ID"] : $arDocumentState["TEMPLATE_ID"])));
			if (!$canViewWorkflow)
				continue;
			elseif (strlen($arDocumentState["WORKFLOW_STATUS"]) <= 0)
/*			elseif (intVal($arDocumentState["WORKFLOW_STATUS"]) >= 0)*/
				continue;

			$bizProcCounter++;
		
			$proc = array(
				"title" => "");
ob_start();
		
			if (strlen($arDocumentState["STATE_NAME"]) > 0 || 
				(strlen($arDocumentState["ID"]) > 0 && strlen($arDocumentState["WORKFLOW_STATUS"]) > 0))
			{
			?>
			<tr>
				<th><?=GetMessage("IBEL_BIZPROC_STATE")?></th>
				<td><?=(strlen($arDocumentState["STATE_TITLE"]) > 0 ? $arDocumentState["STATE_TITLE"] : $arDocumentState["STATE_NAME"])?> <?
				if (strlen($arDocumentState["ID"]) > 0 && strlen($arDocumentState["WORKFLOW_STATUS"]) > 0):?>
					[ <a href="<?=$APPLICATION->GetCurPageParam("edit=Y&stop_bizproc=".$arDocumentState["ID"]."&".bitrix_sessid_get(), 
						array("stop_bizproc", "sessid", "edit"))?>"><?=GetMessage("IBEL_BIZPROC_STOP")?></a> ]
				<?endif;
				if (strlen($arDocumentState["ID"]) > 0):?>
					[ <a href="<?=CComponentEngine::MakePathFromTemplate($arParams["WEBDAV_BIZPROC_LOG_URL"], 
						array(
							"ID" => $arDocumentState["ID"], 
							"SECTION_ID" => $arResult["ELEMENT"]["IBLOCK_SECTION_ID"], 
							"ELEMENT_ID" => $arParams["ELEMENT_ID"]))?>"><?=GetMessage("WD_HISTORY")?></a> ]
				<?endif;?>
				</td>
			</tr><?
			}
			
			if (strlen($arDocumentState["ID"]) <= 0)
			{
				CBPDocument::StartWorkflowParametersShow($arDocumentState["TEMPLATE_ID"],
					$arDocumentState["TEMPLATE_PARAMETERS"], "form_element_".$arParameters["IBLOCK_ID"]."_form",
					($_SERVER['REQUEST_METHOD'] == "POST")					
				);
			}
			
			$arEvents = CBPDocument::GetAllowableEvents($GLOBALS["USER"]->GetID(), $arCurrentUserGroups, $arDocumentState);
			if (count($arEvents) > 0)
			{
			?>
			<tr>
				<th><?=GetMessage("IBEL_BIZPROC_RUN_CMD")?></th>
				<td>
					<input type="hidden" name="bizproc_id_<?= $bizProcIndex ?>" value="<?= $arDocumentState["ID"] ?>" />
					<input type="hidden" name="bizproc_template_id_<?= $bizProcIndex ?>" value="<?= $arDocumentState["TEMPLATE_ID"] ?>" />
					<select name="bizproc_event_<?= $bizProcIndex ?>">
						<option value=""><?=GetMessage("IBEL_BIZPROC_RUN_CMD_NO")?></option>
						<?
						foreach ($arEvents as $e)
						{
							?><option value="<?= htmlspecialchars($e["NAME"]) ?>"<?= ($_REQUEST["bizproc_event_".$bizProcIndex] == $e["NAME"]) ? " selected" : ""?>><?
							?><?=htmlspecialchars($e["TITLE"]) ?></option><?
						}
						?>
					</select>
				</td>
			</tr>
			<?
			}

			if (strlen($arDocumentState["ID"]) > 0)
			{
				$arTasks = CBPDocument::GetUserTasksForWorkflow($USER->GetID(), $arDocumentState["ID"]);
				if (count($arTasks) > 0)
				{
					?>
					<tr>
						<th><?=GetMessage("IBEL_BIZPROC_TASKS")?></th>
						<td>
							<?
							foreach ($arTasks as $arTask)
							{
								$url = CComponentEngine::MakePathFromTemplate($arParams["WEBDAV_TASK_URL"], array("ID" => $arTask["ID"]));
								$url .= (strpos($url, "?") === false ? "?" : "&")."back_url=".urlencode($APPLICATION->GetCurPageParam("", array()));
								?><a href="<?=$url?>" title="<?= htmlspecialchars($arTask["DESCRIPTION"]) ?>"><?= $arTask["NAME"] ?></a><br /><?
							}
							?>
						</td>
					</tr>
					<?
				}
			}
$res = ob_get_contents();
ob_end_clean();
			if (!empty($res))
			{
?>
		<tr class="header2">
			<th colspan="2"><?=$arDocumentState["TEMPLATE_NAME"]?></th>
		</tr>
		<?=$res?>
<?
			}
		}
	}
	?>
		<input type="hidden" name="bizproc_index" value="<?=$bizProcIndex?>" />
	</tbody>
	<?
}

?>
	<tfoot>
		<tr>
			<td colspan="2">
				<input type="submit" name="save" value="<?=GetMessage("WD_SAVE")?>" />
				<input type="submit" name="apply" value="<?=GetMessage("WD_APPLY")?>" />
				<input type="submit" name="cancel" value="<?=GetMessage("WD_CANCEL")?>" />
			</td>
		</tr>
	</tfoot>
</table>
</form>