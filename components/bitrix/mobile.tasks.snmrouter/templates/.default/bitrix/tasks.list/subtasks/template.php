<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * @var CMain $APPLICATION
 * @var array $arResult
 * @var array $arParams
 * @var CBitrixComponent $component
 */
if (is_array($arResult["TASKS"]) && !empty($arResult["TASKS"]))
{
	ob_start();
?>
<div class="mobile-grid-field-subtasks-container">
<?
	foreach($arResult["TASKS"] as $task)
	{
		?><div class="mobile-grid-field-subtasks-item task-status-<?=tasksStatus2String($task["STATUS"])?>">
			<span onclick="<?=CMobileHelper::getTaskLink($task['ID'])?>"><?=($task["TITLE"])?></span>
		</div><?php
	}
?>
</div>
<?
	$html = ob_get_clean();
	$cnt = count($arResult["TASKS"]);
	if ($cnt > 3)
	{
		?><input class="mobile-grid-field-subtasks-more-input" value="<?=count($arResult["TASKS"])?>" type="checkbox" id="expand_subtasks"><?
		?><?=$html?><?
		?><label class="mobile-grid-field-subtasks-more" for="expand_subtasks"><span class="unchecked"><?=GetMessage("interface_form_show_more")?> (<span><?=(count($arResult["TASKS"])-3)?></span>)</span><span class="checked"><?=GetMessage("interface_form_hide")?></span></label><?
	}
	else
	{
		?><?=$html?><?
	}
}
