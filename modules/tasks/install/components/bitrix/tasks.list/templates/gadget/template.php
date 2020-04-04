<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?
if(count($arResult["TASKS"]) <= 0)
	echo GetMessage("INTASK_LIST_EMPTY");
$bFirst = true;
if (Array_Key_Exists("TASKS", $arResult) && Is_Array($arResult["TASKS"]))
{
	foreach ($arResult["TASKS"] as $arTask)
	{
		if (!$bFirst)
		{
			?><div class="sonet-forum-line"></div><?
		}
		if ($arTask["DEADLINE"])
		{
			?><span class="sonet-forum-post-date"><?php
			echo GetMessage("TASKS_DEADLINE").": ".tasksFormatDate($arTask["DEADLINE"]);

			if (date("H:i", strtotime($arTask["DEADLINE"])) != "00:00")
				echo ' ' . FormatDateFromDB($arTask["DEADLINE"], CSite::getTimeFormat());
			?></span><br /><?php
		}
		?>
		<b><a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => $arTask["ID"], "action" => "view"))?>"><?
			echo $arTask["TITLE"];
		?></a></b><br />

		<?if ($arResult["TASK_TYPE"] == "group"):?>
			<small><br /><?=GetMessage("INTASK_TASKASSIGNEDTO")?>: <?php echo tasksFormatNameShort($arTask["RESPONSIBLE_NAME"], $arTask["RESPONSIBLE_LAST_NAME"], $arTask["RESPONSIBLE_LOGIN"], $arTask["RESPONSIBLE_SECOND_NAME"], $arParams["NAME_TEMPLATE"])?></small>
		<?endif;?>

		<br clear="left"/>

		<span class="sonet-forum-post-info">
			<span class="sonet-forum-eye"><?=GetMessage("INTASK_TASKPRIORITY")?></span>:&nbsp;<?php echo GetMessage("TASKS_PRIORITY_".$arTask["PRIORITY"])?>&nbsp;
			<span class="sonet-forum-comment-num "><?=GetMessage("INTASK_TASKSTATUS")?></span>:&nbsp;<?php echo GetMessage("TASKS_STATUS_".$arTask["REAL_STATUS"])?>
		</span>
		<?
		$bFirst = false;
	}
}
?>