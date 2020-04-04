<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/'.BX_ROOT.'/modules/timeman/js_core_timeman.php');
?>
<span class="site-selector-separator"></span><span class="tm-dashboard"><span id="bx_tm" class="tm-dashboard-inner bx-tm-<?=strtolower($arResult['START_INFO']['STATE']);?>"><span class="tm-dashboard-arrow"></span><span class="tm-dashboard-title"><span><?=GetMessage('JS_CORE_TM_POPUP_OPEN')?></span><span class="tm-dashboard-stateicon"></span></span><span data-role="event"<?=$arResult['START_INFO']['PLANNER']['EVENT_TIME'] == '' ? ' style="display: none;"' : ''?>><span class="tm-dashboard-bell"></span><span class="tm-dashboard-text" data-role="event_time"><?=$arResult['START_INFO']['PLANNER']['EVENT_TIME']?></span></span><span class="tm-dashboard-clock"></span><span class="tm-dashboard-text"><span data-role="clock"></span><span class="tm-dashboard-subtext" data-role="state"></span></span><span data-role="tasks"<?=$arResult['START_INFO']['PLANNER']['TASKS_COUNT'] <= 0 ? ' style="display: none;"' : ''?>><span class="tm-dashboard-flag"></span><span class="tm-dashboard-text" data-role="tasks_counter"><?=$arResult['START_INFO']['PLANNER']['TASKS_COUNT']?></span></span></span></span>
<script type="text/javascript">
new JCTimeManTpl('bx_tm');
BX.timeman('bx_tm', <?=CUtil::PhpToJsObject($arResult['START_INFO']);?>, '<?=SITE_ID?>');
BX.ready(function(){BXTIMEMAN.ShowFormWeekly(<?=CUtil::PhpToJsObject($arResult['WORK_REPORT']);?>);});
</script>
<?
if ($arResult['TASKS_ENABLED']):
	$APPLICATION->IncludeComponent(
		"bitrix:tasks.iframe.popup",
		".default",
		array(
			"ON_TASK_ADDED" => "BX.DoNothing",
			"ON_TASK_CHANGED" => "BX.DoNothing",
			"ON_TASK_DELETED" => "BX.DoNothing",
		),
		null,
		array("HIDE_ICONS" => "Y")
	);
endif;
?>

