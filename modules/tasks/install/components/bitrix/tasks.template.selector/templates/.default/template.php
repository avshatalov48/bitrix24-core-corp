<?
if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<?$APPLICATION->AddHeadScript('/bitrix/components/bitrix/tasks.task.selector/templates/.default/tasks.js');?>

<?$jsObjectName = 'O_'.$arResult["NAME"];?>

<script type="text/javascript">
	var <?=$jsObjectName?> = new TasksTask("<?php echo $arResult["NAME"]?>", <?php echo $arParams["MULTIPLE"] == "Y" ? "true" : "false"?>, true);
	<?=$jsObjectName?>.ajaxUrl = '<?=$this->__component->GetPath()."/ajax.php?lang=".LANGUAGE_ID."&SITE_ID=".$arParams["SITE_ID"]?>';
	<?=$jsObjectName?>.filter = <?=CUtil::PhpToJSObject($arParams["FILTER"])?>;

	<?if(intval($arParams['TEMPLATE_ID'])):?>
		<?=$jsObjectName?>.addAjaxParameter('TEMPLATE_ID', <?=intval($arParams['TEMPLATE_ID'])?>);
	<?endif?>

	<?php foreach($arResult["CURRENT_TEMPLATES"] as $task):?>
		<?=$jsObjectName?>.arSelected[<?=$task["ID"]?>] = {id : <?=CUtil::JSEscape($task["ID"])?>, name : "<?=CUtil::JSEscape($task["TITLE"])?>", status : <?=CTasks::STATE_PENDING?>};
		<?=$jsObjectName?>.arTasksData[<?=$task["ID"]?>] = {id : <?=CUtil::JSEscape($task["ID"])?>, name : "<?=CUtil::JSEscape($task["TITLE"])?>", status : <?=CTasks::STATE_PENDING?>};
	<?php endforeach?>

	<?php foreach($arResult["LAST_TEMPLATES"] as $task):?>
		<?=$jsObjectName?>.arTasksData[<?=$task["ID"]?>] = {id : <?=CUtil::JSEscape($task["ID"])?>, name : "<?=CUtil::JSEscape($task["TITLE"])?>", status : <?=CTasks::STATE_PENDING?>};
	<?php endforeach?>

	BX.ready(function() {
		<?php if (strlen($arParams["FORM_NAME"]) > 0 && strlen($arParams["INPUT_NAME"]) > 0):?>
			<?=$jsObjectName?>.searchInput = document.forms["<?=CUtil::JSEscape($arParams["FORM_NAME"])?>"].element["<?=CUtil::JSEscape($arParams["INPUT_NAME"])?>"];
		<?php elseif(strlen($arParams["INPUT_NAME"]) > 0):?>
			<?=$jsObjectName?>.searchInput = BX("<?php echo CUtil::JSEscape($arParams["INPUT_NAME"])?>");
		<?php else:?>
			<?=$jsObjectName?>.searchInput = BX("<?php echo $arResult["NAME"]?>_task_input");
		<?php endif?>

		<?php if (strlen($arParams["ON_CHANGE"]) > 0):?>
			<?=$jsObjectName?>.onChange = <?php echo CUtil::JSEscape($arParams["ON_CHANGE"])?>;
		<?php endif?>

		<?php if (strlen($arParams["ON_SELECT"]) > 0):?>
			<?=$jsObjectName?>.onSelect= <?php echo CUtil::JSEscape($arParams["ON_SELECT"])?>;
		<?php endif?>

		BX.bind(<?=$jsObjectName?>.searchInput, "keyup", BX.debounce(BX.proxy(<?=$jsObjectName?>.search, <?=$jsObjectName?>), 700));
	});
</script>

<div id="<?php echo $arParams["NAME"]?>_selector_content" class="finder-box<?php if ($arParams["MULTIPLE"] == "Y"):?> finder-box-multiple<?php endif?>"<?php echo $arParams["POPUP"] == "Y" ? " style=\"display: none;\"" : ""?>>
	<table class="finder-box-layout" cellspacing="0">
		<tr>
			<td class="finder-box-left-column">
				<?php if (!isset($arParams["INPUT_NAME"]) || strlen($arParams["INPUT_NAME"]) == 0):?>
				<div class="finder-box-search"><input name="<?php echo $arResult["NAME"]?>_task_input" id="<?php echo $arResult["NAME"]?>_task_input" class="finder-box-search-textbox" /></div>
				<?php endif?>

				<div class="finder-box-tabs">
					<span class="finder-box-tab finder-box-tab-selected" id="<?php echo $arResult["NAME"]?>_tab_last" onclick="<?=$jsObjectName?>.displayTab('last');"><span class="finder-box-tab-left"></span><span class="finder-box-tab-text"><?php echo GetMessage("TASKS_LAST_SELECTED")?></span><span class="finder-box-tab-right"></span></span><span class="finder-box-tab" id="<?php echo $arResult["NAME"]?>_tab_search" onclick="<?=$jsObjectName?>.displayTab('search');"><span class="finder-box-tab-left"></span><span class="finder-box-tab-text"><?php echo GetMessage("TASKS_TASK_SEARCH")?></span><span class="finder-box-tab-right"></span></span>
				</div>

				<div class="popup-window-hr popup-window-buttons-hr"><i></i></div>

				<div class="finder-box-tabs-content">
					<div class="finder-box-tab-content finder-box-tab-content-selected" id="<?php echo $arResult["NAME"]?>_last">
						<table class="finder-box-tab-columns" cellspacing="0">
							<tr>
								<td>
									<?php foreach($arResult["LAST_TEMPLATES"] as $key=>$task):?>
										<div class="finder-box-item<?php echo (in_array($task["ID"], $arParams["VALUE"]) ? " finder-box-item-selected" : "")?>" id="<?php echo $arResult["NAME"]?>_last_task_<?php echo $task["ID"]?>" onclick="<?=$jsObjectName?>.select(event)">
											<?php if ($arParams["MULTIPLE"] == "Y"):?>
												<input type="checkbox" name="<?php echo $arResult["NAME"]?>[]" value="<?php echo $task["ID"]?>"<?php echo (in_array($task["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="tasks-hidden-input" />
											<?php else:?>
												<input type="radio" name="<?php echo $arResult["NAME"]?>" value="<?php echo $task["ID"]?>"<?php echo (in_array($task["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="tasks-hidden-input" />
											<?php endif?>
											<div class="finder-box-item-text"><?php echo $task["TITLE"]?></div>
											<div class="finder-box-item-icon"
												<?php if ($arParams['HIDE_ADD_REMOVE_CONTROLS'] === 'Y') echo ' style="display:none;" '; ?>
												></div>
										</div>
									<?php endforeach?>
									<?php foreach($arResult["CURRENT_TEMPLATES"] as $key=>$task):?>
										<?php if (!in_array($task, $arResult["LAST_TEMPLATES"])):?>
											<?php if ($arParams["MULTIPLE"] == "Y"):?>
												<input type="checkbox" name="<?php echo $arResult["NAME"]?>[]" value="<?php echo $task["ID"]?>"<?php echo (in_array($task["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="tasks-hidden-input" />
											<?php else:?>
												<input type="radio" name="<?php echo $arResult["NAME"]?>" value="<?php echo $task["ID"]?>"<?php echo (in_array($task["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="tasks-hidden-input" />
											<?php endif?>
										<?php endif?>
									<?php endforeach?>
								</td>
							</tr>
						</table>
					</div>
					<div class="finder-box-tab-content" id="<?php echo $arResult["NAME"]?>_search"></div>
				</div>
			</td>
			<?php if ($arParams["MULTIPLE"] == "Y"):?>
			<td class="finder-box-right-column" id="<?php echo $arResult["NAME"]?>_selected_tasks">
				<div class="finder-box-selected-title"><?php echo GetMessage("TASKS_TASKS_CURRENT_COUNT")?> (<span id="<?php echo $arResult["NAME"]?>_current_count"><?php echo sizeof($arResult["CURRENT_TEMPLATES"])?></span>)</div>
				<div class="finder-box-selected-items">
					<?php foreach($arResult["CURRENT_TEMPLATES"] as $task):?>
						<div class="finder-box-selected-item" id="<?php echo $arResult["NAME"]?>_task_selected_<?php echo $task["ID"]?>"><div class="finder-box-selected-item-icon" <?php if ($arParams['HIDE_ADD_REMOVE_CONTROLS'] === 'Y') echo ' style="display:none;" '; ?> onclick="<?=$jsObjectName?>.unselect(<?php echo $task["ID"]?>, this);" id="task-unselect-<?php echo $task["ID"]?>"></div><a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => $task["ID"], "action" => "view"))?>" target="_blank" class="finder-box-selected-item-text"><?php echo $task["TITLE"]?></a></div>
					<?php endforeach?>
				</div>
			</td>
			<?php endif?>
		</tr>
	</table>
</div>