<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Text\HtmlFilter;

$taskJs = $this->__component->GetPath().'/templates/.default/tasks.js';
$APPLICATION->AddHeadScript($taskJs);

$name = $arResult["NAME"];
?>

<script>
	var O_<?=$name?> = null;
	if (typeof TasksTask === "function")
	{
		O_<?=$name?> = new TasksTask("<?=$name?>", <?=($arParams["MULTIPLE"]? "true" : "false")?>);
	}
	BX.ready(function()
	{
		BX.loadScript('<?=$taskJs?>', function() {
			if (!O_<?=$name?>)
			{
				O_<?=$name?> = new TasksTask("<?=$name?>", <?=($arParams["MULTIPLE"]? "true" : "false")?>);
			}
			TasksTask.ajaxUrl = '<?=$this->__component->GetPath()."/ajax.php?lang=".LANGUAGE_ID."&SITE_ID=".$arParams["SITE_ID"]?>';
			TasksTask.filter = <?=CUtil::PhpToJSObject($arParams["FILTER"])?>;

			O_<?=$name?>.filter = <?=CUtil::PhpToJSObject($arParams["FILTER"])?>;

			<?foreach ($arResult["CURRENT_TASKS"] as $task):?>
			O_<?=$name?>.arSelected[<?=$task["ID"]?>] = {
				id : '<?=CUtil::JSEscape($task["ID"])?>',
				name : "<?=CUtil::JSEscape($task["TITLE"])?>",
				status : <?=$task["STATUS"]?>
			};
			TasksTask.arTasksData[<?=$task["ID"]?>] = {
				id : '<?=CUtil::JSEscape($task["ID"])?>',
				name : "<?=CUtil::JSEscape($task["TITLE"])?>",
				status : <?=$task["STATUS"]?>
			};
			<?endforeach?>

			<?foreach ($arResult["LAST_TASKS"] as $task):?>
			TasksTask.arTasksData[<?=$task["ID"]?>] = {
				id : '<?=CUtil::JSEscape($task["ID"])?>',
				name : "<?=CUtil::JSEscape($task["TITLE"])?>",
				status : <?=$task["STATUS"]?>
			};
			<?endforeach?>

			<?if ((string)($arParams['PATH_TO_TASKS_TASK'] ?? null) != ''):?>
			BX.message({TASKS_PATH_TO_TASK: "<?=CUtil::JSEscape($arParams['PATH_TO_TASKS_TASK'])?>"});
			<?endif?>

			<?if ($arParams["FORM_NAME"] <> '' && $arParams["INPUT_NAME"] <> ''):?>
			O_<?=$name?>.searchInput = document.forms["<?=CUtil::JSEscape($arParams["FORM_NAME"])?>"].element["<?=CUtil::JSEscape($arParams["INPUT_NAME"])?>"];
			<?elseif ($arParams["INPUT_NAME"] <> ''):?>
			O_<?=$name?>.searchInput = BX("<?=CUtil::JSEscape($arParams["INPUT_NAME"])?>");
			<?else:?>
			O_<?=$name?>.searchInput = BX("<?=$name?>_task_input");
			<?endif?>

			<?if (($arParams["ON_CHANGE"] ?? null) <> ''):?>
			O_<?=$name?>.onChange = <?=CUtil::JSEscape($arParams["ON_CHANGE"])?>;
			<?endif?>

			<?if (($arParams["ON_SELECT"] ?? null) <> ''):?>
			O_<?=$name?>.onSelect= <?=CUtil::JSEscape($arParams["ON_SELECT"])?>;
			<?endif?>

			BX.bind(O_<?=$name?>.searchInput, "keyup", BX.debounce(
				BX.proxy(O_<?=$name?>.search, O_<?=$name?>), 700)
			);
		});
	});
</script>

<div class="finder-box<?if ($arParams["MULTIPLE"]):?> finder-box-multiple<?endif?>"<?=(($arParams["POPUP"] ?? null) == "Y"? " style=\"display: none;\"" : "")?>
	 id="<?=$name?>_selector_content">
	<table class="finder-box-layout">
		<tr>
			<td class="finder-box-left-column">
				<?if (!isset($arParams["INPUT_NAME"]) || $arParams["INPUT_NAME"] == ''):?>
					<div class="finder-box-search">
						<input class="finder-box-search-textbox" name="<?=$name?>_task_input" id="<?=$name?>_task_input"/>
					</div>
				<?endif?>

				<div class="finder-box-tabs">
					<span class="finder-box-tab finder-box-tab-selected" id="<?=$name?>_tab_last"
						  onclick="O_<?=$name?>.displayTab('last');">
						<span class="finder-box-tab-left"></span>
						<span class="finder-box-tab-text"><?=GetMessage("TASKS_LAST_SELECTED")?></span>
						<span class="finder-box-tab-right"></span>
					</span>
					<span class="finder-box-tab" id="<?=$name?>_tab_search"
						  onclick="O_<?=$name?>.displayTab('search');">
						<span class="finder-box-tab-left"></span>
						<span class="finder-box-tab-text"><?=GetMessage("TASKS_TASK_SEARCH")?></span>
						<span class="finder-box-tab-right"></span>
					</span>
				</div>

				<div class="popup-window-hr popup-window-buttons-hr"><i></i></div>

				<div class="finder-box-tabs-content">
					<div class="finder-box-tab-content finder-box-tab-content-selected" id="<?=$name?>_last">
						<table class="finder-box-tab-columns">
							<tr>
								<td>
									<?foreach ($arResult["LAST_TASKS"] as $key => $task):
										$taskId = intval($task['ID']);
										$selected = in_array($taskId, $arParams['VALUE']);
									?>
										<div class="finder-box-item<?=($selected? " finder-box-item-selected" : "")?>"
											 id="<?=$name?>_last_task_<?=$taskId?>"
											 onclick="O_<?=$name?>.select(event)">
											<?if ($arParams["MULTIPLE"]):?>
												<input class="tasks-hidden-input"
													   type="checkbox" name="<?=$name?>[]"
													   value="<?=$taskId?>"<?=($selected? " checked" : "")?>/>
											<?else:?>
												<input class="tasks-hidden-input"
													   type="radio" name="<?=$name?>"
													   value="<?=$taskId?>"<?=($selected? " checked" : "")?>/>
											<?endif?>
											<div class="finder-box-item-text"><?=HtmlFilter::encode($task["TITLE"])?> [<?=$taskId?>]</div>
											<div class="finder-box-item-icon"
												<?if ($arParams['HIDE_ADD_REMOVE_CONTROLS']) echo ' style="display:none;" ';?>
												></div>
										</div>
									<?endforeach?>
									<?foreach ($arResult["CURRENT_TASKS"] as $key => $task):
										$taskId = $task['ID'];
										$selected = in_array($taskId, $arParams['VALUE']);
									?>
										<?if (!in_array($task, $arResult["LAST_TASKS"])):?>
											<?if ($arParams["MULTIPLE"]):?>
												<input class="tasks-hidden-input" type="checkbox"
													   name="<?=$name?>[]"
													   value="<?=$taskId?>"<?=($selected? " checked" : "")?>/>
											<?else:?>
												<input class="tasks-hidden-input" type="radio"
													   name="<?=$name?>"
													   value="<?=$taskId?>"<?=($selected? " checked" : "")?>/>
											<?endif?>
										<?endif?>
									<?endforeach?>
								</td>
							</tr>
						</table>
					</div>
					<div class="finder-box-tab-content" id="<?=$name?>_search"></div>
				</div>
			</td>
			<?if ($arParams["MULTIPLE"]):?>
				<td class="finder-box-right-column" id="<?=$name?>_selected_tasks">
					<div class="finder-box-selected-title">
						<?=GetMessage("TASKS_TASKS_CURRENT_COUNT")?> (
							<span id="<?=$name?>_current_count"><?=sizeof($arResult["CURRENT_TASKS"] ?? [])?></span>
						)</div>
					<div class="finder-box-selected-items">
						<?foreach ($arResult["CURRENT_TASKS"] as $task):
							$taskId = $task['ID'];
						?>
							<div class="finder-box-selected-item" id="<?=$name?>_task_selected_<?=$taskId?>">
								<div class="finder-box-selected-item-icon"
									<?if ($arParams['HIDE_ADD_REMOVE_CONTROLS']) echo ' style="display:none;" ';?>
									 onclick="O_<?=$name?>.unselect(<?=$taskId?>, this);"
									 id="task-unselect-<?=$taskId?>">
								</div>
								<a href="<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], ["task_id" => $taskId, "action" => "view"])?>"
								   target="_blank" class="finder-box-selected-item-text">
									<?=HtmlFilter::encode($task["TITLE"])?>
								</a>
							</div>
						<?endforeach?>
					</div>
				</td>
			<?endif?>
		</tr>
	</table>
</div>