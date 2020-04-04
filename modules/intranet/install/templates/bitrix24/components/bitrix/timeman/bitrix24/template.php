<?
use Bitrix\Main\Page\Frame;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$statusName = "";
$statusClass = "";
if ($arResult["START_INFO"]["STATE"] == "OPENED")
{
	$statusName = GetMessage("TM_STATUS_WORK");
	$statusClass = "";
}
elseif ($arResult["START_INFO"]["STATE"] == "CLOSED")
{
	if ($arResult["START_INFO"]["CAN_OPEN"] == "REOPEN" || !$arResult["START_INFO"]["CAN_OPEN"])
	{
		$statusName = GetMessage("TM_STATUS_COMPLETED");
		$statusClass = "timeman-completed";
	}
	else
	{
		$statusName = GetMessage("TM_STATUS_START");
		$statusClass = "timeman-start";
	}
}
elseif ($arResult["START_INFO"]["STATE"] == "PAUSED")
{
	$statusName = GetMessage("TM_STATUS_PAUSED");
	$statusClass = "timeman-paused";
}
elseif ($arResult["START_INFO"]["STATE"] == "EXPIRED")
{
	$statusName = "";
	$statusClass = "timeman-expired";
}

$bInfoRow = $arResult["START_INFO"]['PLANNER']["EVENT_TIME"] != '' || $arResult["START_INFO"]['PLANNER']["TASKS_COUNT"] > 0;
$bTaskTimeRow = isset($arResult["START_INFO"]['PLANNER']['TASKS_TIMER']) && is_array($arResult["START_INFO"]['PLANNER']['TASKS_TIMER']) && $arResult["START_INFO"]['PLANNER']['TASKS_TIMER']['TIMER_STARTED_AT'] > 0;

if($bTaskTimeRow)
{
	$ts = intval($arResult['START_INFO']['PLANNER']['TASK_ON_TIMER']['TIME_SPENT_IN_LOGS']);

	if ($arResult['START_INFO']['PLANNER']['TASKS_TIMER']['TIMER_STARTED_AT'] > 0)
		$ts += (time() - $arResult['START_INFO']['PLANNER']['TASKS_TIMER']['TIMER_STARTED_AT']);

	$taskTime = sprintf("%02d:%02d:%02d", floor($ts/3600), floor($ts/60) % 60, $ts%60);

	if($arResult['START_INFO']['PLANNER']['TASK_ON_TIMER']['TIME_ESTIMATE'] > 0)
	{
		$ts = $arResult['START_INFO']['PLANNER']['TASK_ON_TIMER']['TIME_ESTIMATE'];
		$taskTime .= " / " . sprintf("%02d:%02d", floor($ts/3600), floor($ts/60) % 60);
	}
}

$isCompositeMode = defined("USE_HTML_STATIC_CACHE");
?>
<div class="timeman-container timeman-container-<?=LANGUAGE_ID?><?=(IsAmPmMode() ? " am-pm-mode" : "")?>" id="timeman-container">
	<div class="timeman-wrap"><?
		?><span id="timeman-block" class="timeman-block <?=($isCompositeMode ? "" : $statusClass)?>"><?
			?><span class="bx-time" id="timeman-timer"></span><?
			?><span class="timeman-right-side" id="timeman-right"><?
				$frame = $this->createFrame("timeman-right", false)->begin("");
				?><span class="timeman-info" id="timeman-info"<?if(!$bInfoRow):?> style="display:none"<?endif?>><?
					?><span class="timeman-event" id="timeman-event"<?if($arResult["START_INFO"]['PLANNER']["EVENT_TIME"] == ''):?> style="display:none"<?endif?>><?=$arResult["START_INFO"]['PLANNER']["EVENT_TIME"]?></span><?
					?><span class="timeman-tasks" id="timeman-tasks"<?if($arResult["START_INFO"]['PLANNER']["TASKS_COUNT"] <= 0):?> style="display:none"<?endif?>><?=$arResult["START_INFO"]['PLANNER']["TASKS_COUNT"]?></span><?
				?></span><?
				?><span class="timeman-task-time" id="timeman-task-time"<?if(!$bTaskTimeRow):?> style="display:none"<?endif?>><i></i><span id="timeman-task-timer"><?=$taskTime?></span></span><?
				?><span class="timeman-beginning-but" id="timeman-status-block"<?if($bTaskTimeRow&&$bInfoRow):?> style="display:none"<?endif?>><i></i><span id="timeman-status"><?=$statusName?></span></span>
				<script type="text/javascript">
				<?if (!Frame::isAjaxRequest()):?>
					BX.addCustomEvent(window, "onScriptsLoaded", function() {
				<?endif?>
						BX.message({
							"TM_STATUS_OPENED" : "<?=GetMessageJS("TM_STATUS_WORK")?>",
							"TM_STATUS_CLOSED" : "<?=GetMessageJS("TM_STATUS_START")?>",
							"TM_STATUS_PAUSED" : "<?=GetMessageJS("TM_STATUS_PAUSED")?>",
							"TM_STATUS_COMPLETED" : "<?=GetMessageJS("TM_STATUS_COMPLETED")?>",
							"TM_STATUS_EXPIRED" : "<?=GetMessageJS("TM_STATUS_EXPIRED")?>"
						});

						B24.Timemanager.init(<?=CUtil::PhpToJsObject($arResult["WORK_REPORT"]);?>);

						BX.timeman("bx_tm", <?=CUtil::PhpToJsObject($arResult["START_INFO"]);?>, "<?=SITE_ID?>");

				<?if (!Frame::isAjaxRequest()):?>
					});
				<?else:?>
					BX.addClass(BX("timeman-block"), "<?=$statusClass?>");
				<?endif?>
				</script>
				<?$frame->end()?>
			</span><?
			?><span class="timeman-not-closed-block"><?
				?><span class="timeman-not-cl-icon"></span><?
				?><span class="timeman-not-cl-text"><?=GetMessage("TM_STATUS_EXPIRED")?></span><?
			?></span><?
			?><span class="timeman-background" id="timeman-background"></span>
		</span>
	</div>
</div>