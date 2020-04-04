<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($arResult["NEED_AUTH"] == "Y")
{
	$APPLICATION->AuthForm("");
}
elseif (strlen($arResult["FatalError"]) > 0)
{
	?>
	<span class='errortext'><?=$arResult["FatalError"]?></span><br /><br />
	<?
}
else
{
	if (strlen($arResult["ErrorMessage"]) > 0)
	{
		?>
		<span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br />
		<?
	}
	?>

	<?$APPLICATION->AddHeadScript('/bitrix/js/main/utils.js');?>

	<script type="text/javascript">
	//<![CDATA[
		if (typeof oObjectITS != "object")
			var oObjectITS = {};
	//]]>
	</script>

	<?if (StrLen($arResult["NAV_STRING"]) > 0):?>
		<?=$arResult["NAV_STRING"]?><br /><br />
	<?endif;?></p>

	<table cellpadding="0" cellspacing="0" border="0" width="100%" class="intask-main data-table">
		<thead>
		<tr class="intask-row">
			<th class="intask-cell">&nbsp;</th>
			<? if ($arResult["TasksPropsShow"]) { ?>
			<?foreach ($arResult["TasksPropsShow"] as $field):?>
			<th class="intask-cell"><?= $arResult["TaskFields"][$field]["NAME"] ?></th>
			<?endforeach;?>
			<? } else {?>
			<th class="intask-cell">&nbsp;</th>
			<? } ?>
		</tr>
		</thead>
		<tbody>
		<?if (StrLen($arResult["ParentSectionUrl"]) > 0):?>
			<tr class="intask-row-up" onmouseover="this.className+=' intask-row-over';" onmouseout="this.className=this.className.replace(' intask-row-over', '');" ondblclick="window.location='<?= $arResult["ParentSectionUrl"] ?>'" title="<?= GetMessage("INTDT_DC_UP") ?>">
				<td class="intask-cell">&nbsp;</td>
				<td class="intask-cell" colspan="<?= Count($arResult["TasksPropsShow"]) ?>">
					<div class="section-icon"></div>
					<a href="<?= $arResult["ParentSectionUrl"] ?>"> . . </a>
				</td>
			</tr>
		<?endif;?>
		<?$iCount = 0;?>
		<?if ($arResult["Sections"]):?>
			<?foreach ($arResult["Sections"] as $section):?>
				<tr class="intask-row<?=(($iCount % 2) == 0 ? " selected" : "")?>" onmouseover="this.className+=' intask-row-over';" onmouseout="this.className=this.className.replace(' intask-row-over', '');" ondblclick="window.location='<?= $section["FIELDS"]["ShowUrl"] ?>'" title="<?= GetMessage("INTDT_DC_FOLDER") ?>">
					<td class="intask-cell" valign="top">
						<script>
						function HideThisMenuS<?= $section["FIELDS"]["ID"] ?>()
						{
							if (window.ITSDropdownMenu != null)
							{
								window.ITSDropdownMenu.ShowMenu(this, oObjectITS['intask_s<?= $section["FIELDS"]["ID"] ?>'], document.getElementById('intask_s<?= $section["FIELDS"]["ID"] ?>'))
								window.ITSDropdownMenu.PopupHide();
							}
							else
							{
								alert('NULL');
							}	
						}
						oObjectITS['intask_s<?= $section["FIELDS"]["ID"] ?>'] = <?= CUtil::PhpToJSObject($section["ACTIONS"]) ?>;
						</script>
						<table cellpadding="0" cellspacing="0" border="0" class="intask-dropdown-pointer" onmouseover="this.className+=' intask-dropdown-pointer-over';" onmouseout="this.className=this.className.replace(' intask-dropdown-pointer-over', '');" onclick="if(window.ITSDropdownMenu != null){window.ITSDropdownMenu.ShowMenu(this, oObjectITS['intask_s<?= $section["FIELDS"]["ID"] ?>'], document.getElementById('intask_s<?= $section["FIELDS"]["ID"] ?>'))}" title="<?= GetMessage("INTDT_ACTIONS") ?>" id="intask_table_s<?= $section["FIELDS"]["ID"] ?>"><tr>
							<td>
								<div class="controls controls-view show-action">
									<a href="javascript:void(0);" class="action">
										<div id="intask_s<?= $section["FIELDS"]["ID"] ?>" class="empty"></div>
									</a>
								</div></td>
						</tr></table>
					</td>
					<td class="intask-cell" colspan="<?= Count($arResult["TasksPropsShow"]) ?>" valign="top">
						<div class="section-icon"></div>
						<a href="<?= $section["FIELDS"]["ShowUrl"] ?>"><?= $section["FIELDS"]["NAME"] ?></a>
					</td>
				</tr>
				<?$iCount++;?>
			<?endforeach;?>
		<?endif;?>
		<?if ($arResult["Tasks"]):?>
			<?foreach ($arResult["Tasks"] as $task):?>
				<tr class="intask-row<?=(($iCount % 2) == 0 ? " selected" : "")?> task-<?= $task["TASK_STATUS"] ?> taskPriority-<?= $task["TASK_PRIORITY"] ?>" onmouseover="this.className+=' intask-row-over';" onmouseout="this.className=this.className.replace(' intask-row-over', '');" ondblclick="window.location='<?= $task["VIEW_URL"] ?>'" title="<?= $task["TASK_ALT"] ?>">
					<td class="intask-cell" valign="top">
						<script>
						function HideThisMenu<?= $task["FIELDS"]["ID"] ?>()
						{
							if (window.ITSDropdownMenu != null)
							{
								window.ITSDropdownMenu.ShowMenu(this, oObjectITS['intask_<?= $task["FIELDS"]["ID"] ?>'], document.getElementById('intask_<?= $task["FIELDS"]["ID"] ?>'))
								window.ITSDropdownMenu.PopupHide();
							}
							else
							{
								alert('NULL');
							}	
						}
						oObjectITS['intask_<?= $task["FIELDS"]["ID"] ?>'] = <?= CUtil::PhpToJSObject($task["ACTIONS"]) ?>;
						</script>
						<table cellpadding="0" cellspacing="0" border="0" class="intask-dropdown-pointer" onmouseover="this.className+=' intask-dropdown-pointer-over';" onmouseout="this.className=this.className.replace(' intask-dropdown-pointer-over', '');" onclick="if(window.ITSDropdownMenu != null){window.ITSDropdownMenu.ShowMenu(this, oObjectITS['intask_<?= $task["FIELDS"]["ID"] ?>'], document.getElementById('intask_<?= $task["FIELDS"]["ID"] ?>'))}" title="<?= GetMessage("INTDT_ACTIONS") ?>" id="intask_table_<?= $task["FIELDS"]["ID"] ?>"><tr>
							<td>
								<div class="controls controls-view show-action">
									<a href="javascript:void(0);" class="action">
										<div id="intask_<?= $task["FIELDS"]["ID"] ?>" class="empty"></div>
									</a>
								</div></td>
						</tr></table>
					</td>
					<?foreach ($arResult["TasksPropsShow"] as $field):?>
						<td class="intask-cell" valign="top"><?
							if ($field == "NAME")
								echo "<a href=\"".$task["VIEW_URL"]."\">";
							if (Is_Array($task["FIELDS"][$field."_PRINTABLE"]))
							{
								$bFirst = true;
								foreach ($task["FIELDS"][$field."_PRINTABLE"] as $v)
								{
									if (!$bFirst)
										echo ", ";
									echo $v;
									$bFirst = false;
								}
							}
							else
							{
								echo $task["FIELDS"][$field."_PRINTABLE"];
							}
							if ($field == "NAME")
							{
								echo "</a>";
								if (IntVal($task["COMMENTS"]) > 0)
								{
									$iComments = IntVal($task["COMMENTS"]);
									?> <a href="<?= $task["VIEW_URL"] ?>" class="element-properties element-comments" title="<?=GetMessage("INT_TASK_COMMENTS").": ".$iComments?>"><?=$iComments?></a><?
								}
							}
						?></td>
					<?endforeach;?>
				</tr>
				<?$iCount++;?>
			<?endforeach;?>
		<?endif;?>
		<?if (!$arResult["Sections"] && !$arResult["Tasks"]):?>
			<tr class="intask-row">
				<td class="intask-cell" valign="top">&nbsp;</td>
				<td class="intask-cell" valign="top" colspan="<?= Count($arResult["TasksPropsShow"])?>"><?= GetMessage("INTDT_NO_TASKS") ?></td>
			</tr>
		<?endif;?>
		</tbody>
	</table>
	<br />
	<?if (StrLen($arResult["NAV_STRING"]) > 0):?>
		<?=$arResult["NAV_STRING"]?>
		<br /><br />
	<?endif;?>

	<?
}
?>