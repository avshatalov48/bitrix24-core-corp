<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

$this->SetViewTarget("sidebar", 200);

?>

<div class="sidebar-widget sidebar-widget-tasks">
	<div class="sidebar-widget-top">
		<div class="sidebar-widget-top-title"><?=GetMessage("TASKS_FILTER_TITLE")?></div>
		<a href="<?=SITE_DIR?>company/personal/user/<?=\Bitrix\Tasks\Util\User::getId()?>/tasks/task/edit/0/" class="plus-icon"></a>
	</div>
	<div class="sidebar-widget-item-wrap">
	<? foreach($arResult["PREDEFINED_FILTERS"]["ROLE"] as $key=>$filter):?>
		<a href="<?= $arParams["PATH_TO_TASKS"]."?FILTERR=".$key?>" class="task-item task-item-<?=$filter["CLASS"]?>"><span class="task-item-text"><?=$filter["TITLE"]?></span><span class="task-item-index"><?= $filter["COUNT"]?></span></a>
	<? endforeach?>
	</div>
</div>
