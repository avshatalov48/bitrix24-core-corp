<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

Extension::load(['ui.alerts', 'ui.icons', 'ui.fonts.opensans']);

Loc::loadMessages(__FILE__);

$isIFrame = (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y');
$taskLimitExceeded = $arResult['TASK_LIMIT_EXCEEDED'];
$kpiLimitExceeded = $arResult['KPI_LIMIT_EXCEEDED'];

if (isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y")
{
	$APPLICATION->RestartBuffer(); //сбрасываем весь вывод
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<? $APPLICATION->ShowHead(); ?>
	</head>
	<body class="template-<?=SITE_TEMPLATE_ID?> <?$APPLICATION->ShowProperty("BodyClass");?> <?if ($isIFrame):?>task-iframe-popup-side-slider<?php endif?> <?if($taskLimitExceeded || $kpiLimitExceeded):?>task-report-locked<?php endif?>"
		  onload="window.top.BX.onCustomEvent(window.top, 'tasksIframeLoad');"
		  onunload="window.top.BX.onCustomEvent(window.top, 'tasksIframeUnload');">
	<div class="tasks-iframe-header">
		<div class="pagetitle-wrap">
			<div class="pagetitle-inner-container">
				<div class="pagetitle-menu" id="pagetitle-menu"><?
					$APPLICATION->ShowViewContent("pagetitle")
					?></div>
				<div class="pagetitle" <? if ($isIFrame): ?>style="padding-left: 20px;padding-right:20px;"<? endif ?>>
					<span id="pagetitle" class="pagetitle-item"><? $APPLICATION->ShowTitle(false); ?></span>
				</div>
			</div>
		</div>
	</div>
<?}?>

<?php
if (isset($arResult["ERROR"]) && !empty($arResult["ERROR"]))
{
	foreach ($arResult["ERROR"] as $error)
	{
		?>
			<div class="ui-alert ui-alert-icon-warning ui-alert-danger">
				<span class="ui-alert-message"><?= htmlspecialcharsbx($error['MESSAGE'])?></span>
			</div>
		<?
	}

	if (isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y")
	{
		require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
		exit;
	}

	return;
}

if ($taskLimitExceeded || $kpiLimitExceeded)
{
	$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", []);
}
?>

<?php $APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.topmenu',
	'',
	array(
		'USER_ID' => $arParams['USER_ID'],

		'SECTION_URL_PREFIX' => '',
		'MARK_SECTION_EFFECTIVE' => ($arParams['MARK_TEMPLATES'] ?? null),

		'PATH_TO_USER_TASKS' => $arParams['PATH_TO_USER_TASKS'],
		'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_USER_TASKS_TASK'],
		'PATH_TO_USER_TASKS_VIEW' => $arParams['PATH_TO_USER_TASKS_VIEW'],
		'PATH_TO_USER_TASKS_REPORT' => $arParams['PATH_TO_USER_TASKS_REPORT'],
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],

		'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT']
	),
	$component,
	array('HIDE_ICONS' => true)
); ?>
<div class="task-iframe-workarea <?if($taskLimitExceeded || $kpiLimitExceeded):?>task-report-locked<?php endif?>" <?if($isIFrame):?>style="padding:0 20px;"<?php endif?>>
	<?php
	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.grid',
		'',
		array(
			'GRID_ID' => $arParams['GRID_ID'],
			'HEADERS' => isset($arParams['HEADERS']) ? $arParams['HEADERS'] : array(),
			'ROWS' => $arResult['ROWS'],

			'AJAX_MODE' => 'Y',
			//Strongly required
			"AJAX_OPTION_JUMP" => "N",
			"AJAX_OPTION_STYLE" => "N",
			"AJAX_OPTION_HISTORY" => "N",

			"SHOW_CHECK_ALL_CHECKBOXES" => false,
			"SHOW_ROW_CHECKBOXES" => false,
			"SHOW_SELECTED_COUNTER" => false,

			"ALLOW_COLUMNS_SORT" => false,
			"ALLOW_COLUMNS_RESIZE" => false,
			"ALLOW_PIN_HEADER" => true,
			"SHOW_PAGINATION" => $arParams['USE_PAGINATION'],

			"NAV_OBJECT" => $arResult['NAV_OBJECT'],

			"TOTAL_ROWS_COUNT" => $arResult['TOTAL_RECORD_COUNT'],

			"SHOW_PAGESIZE" => true,
			"PAGE_SIZES" => $arParams['PAGE_SIZES'],
			"DEFAULT_PAGE_SIZE" => $arParams['DEFAULT_PAGE_SIZE']
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
	?>
</div>

<script type="text/javascript">
	BX.ready(function() {
		new BX.Tasks.TasksReportEffectiveInProgress(<?=Json::encode([
			'taskLimitExceeded' => $arResult['TASK_LIMIT_EXCEEDED'],
			'kpiLimitExceeded' => $arResult['KPI_LIMIT_EXCEEDED'],
			'pathToTasks' => str_replace('#user_id#', $arParams['USER_ID'], $arParams['PATH_TO_USER_TASKS']),
		])?>);
	});
</script>

<?php
if (isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y")
{
	require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
	exit;
}
?>
