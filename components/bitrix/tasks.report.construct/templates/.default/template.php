<?php

use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Helper\RestrictionUrl;
use Bitrix\Tasks\Integration\Intranet\Settings;
use Bitrix\Tasks\Internals\Task\MetaStatus;
use Bitrix\Tasks\Internals\Task\Priority;
use Bitrix\Tasks\Internals\Task\Status;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** intranet-settings-support */
$settings = new Settings();
if (!$settings->isToolAvailable(Settings::TOOLS['base_tasks']) || !$settings->isToolAvailable(Settings::TOOLS['report']))
{
	$APPLICATION->IncludeComponent("bitrix:tasks.error", "limit", [
		'LIMIT_CODE' => RestrictionUrl::TASK_LIMIT_OFF_SLIDER_URL,
		'SOURCE' => 'report-construct',
	]);

	return;
}

$arResult['enumValues'] = [
	'STATUS' => [
		Status::PENDING,
		Status::IN_PROGRESS,
		Status::SUPPOSEDLY_COMPLETED,
		Status::COMPLETED,
		Status::DEFERRED,
	],
	'STATUS_PSEUDO' => [
		Status::PENDING,
		Status::IN_PROGRESS,
		Status::SUPPOSEDLY_COMPLETED,
		Status::COMPLETED,
		Status::DEFERRED,
		MetaStatus::EXPIRED,
	],
	'PRIORITY' => [
		Priority::AVERAGE,
		Priority::HIGH,
	],
];

IncludeModuleLangFile('/modules/tasks/lib/task.php');
?>

<!-- hide compares for User and Group -->
<style>
.report-filter-compare-User {display: none;}
.report-filter-compare-Group {display: none;}
</style>

<?php $APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.topmenu',
	'',
	[
		'USER_ID' => ($arParams['USER_ID'] ?? null),
		'GROUP_ID' => ($arParams['GROUP_ID'] ?? null),
		'SECTION_URL_PREFIX' => '',
		'PATH_TO_GROUP_TASKS' => ($arParams['PATH_TO_GROUP_TASKS'] ?? null),
		'PATH_TO_GROUP_TASKS_TASK' => ($arParams['PATH_TO_GROUP_TASKS_TASK'] ?? null),
		'PATH_TO_GROUP_TASKS_VIEW' => ($arParams['PATH_TO_GROUP_TASKS_VIEW'] ?? null),
		'PATH_TO_GROUP_TASKS_REPORT' => ($arParams['PATH_TO_GROUP_TASKS_REPORT'] ?? null),
		'PATH_TO_USER_TASKS' => ($arParams['PATH_TO_USER_TASKS'] ?? null),
		'PATH_TO_USER_TASKS_TASK' => ($arParams['PATH_TO_USER_TASKS_TASK'] ?? null),
		'PATH_TO_USER_TASKS_VIEW' => ($arParams['PATH_TO_USER_TASKS_VIEW'] ?? null),
		'PATH_TO_USER_TASKS_REPORT' => ($arParams['PATH_TO_USER_TASKS_REPORT'] ?? null),
		'PATH_TO_USER_TASKS_TEMPLATES' => ($arParams['PATH_TO_USER_TASKS_TEMPLATES'] ?? null),
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => ($arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'] ?? null),
		'PATH_TO_CONPANY_DEPARTMENT' => ($arParams['PATH_TO_CONPANY_DEPARTMENT'] ?? null),
		'MARK_SECTION_REPORTS' => 'Y',
		'MARK_TEMPLATES' => 'N',
		'MARK_ACTIVE_ROLE' => 'N',
	],
	$component,
	['HIDE_ICONS' => true]
);

?>
<div class="<?= !$arResult['tasksReportEnabled'] ? 'task-report-locked' : '' ?>">
<?php
$APPLICATION->IncludeComponent(
	'bitrix:report.construct',
	'',
	[
		'USER_ID' => ($arParams['USER_ID'] ?? null),
		'GROUP_ID' => ($arParams['GROUP_ID'] ?? null),
		'REPORT_ID' => ($arParams['REPORT_ID'] ?? null),
		'ACTION' => ($arParams['ACTION'] ?? null),
		'PATH_TO_REPORT_LIST' => ($arParams['PATH_TO_TASKS_REPORT'] ?? null),
		'PATH_TO_REPORT_CONSTRUCT' => ($arParams['PATH_TO_TASKS_REPORT_CONSTRUCT'] ?? null),
		'PATH_TO_REPORT_VIEW' => ($arParams['PATH_TO_TASKS_REPORT_VIEW'] ?? null),
		'NAME_TEMPLATE' => ($arParams['NAME_TEMPLATE'] ?? null),
		'REPORT_HELPER_CLASS' => 'CTasksReportHelper',
		'USE_CHART' => true,
	],
	false
);
?>
</div>
<?php

$entity = Bitrix\Main\Entity\Base::getInstance('Bitrix\Tasks\TaskTable');
$status_lang = $entity->getField('STATUS')->getLangCode();
$status_lang_pseudo = $entity->getField('STATUS_PSEUDO')->getLangCode();
$priority_lang = $entity->getField('PRIORITY')->getLangCode();
$mark_lang = $entity->getField('MARK')->getLangCode();

$pathToTasks = (preg_match('#^/\w#', $arResult['pathToTasks']) ? $arResult['pathToTasks']: '/');
?>

<!-- filter value control examples -->
<div id="report-filter-value-control-examples-custom" style="display: none">
	<span name="report-filter-value-control-STATUS" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('REPORT_IGNORE_FILTER_VALUE')?></option>
			<? foreach($arResult['enumValues']['STATUS'] as $val): ?>
				<option value="<?=$val?>"><?=GetMessage($status_lang.'_VALUE_'.$val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-STATUS_PSEUDO" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('REPORT_IGNORE_FILTER_VALUE')?></option>
			<? foreach($arResult['enumValues']['STATUS_PSEUDO'] as $val): ?>
				<option value="<?=$val?>"><?=GetMessage($status_lang_pseudo.'_VALUE_'.$val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-PRIORITY" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('REPORT_IGNORE_FILTER_VALUE')?></option>
			<? foreach ($arResult['enumValues']['PRIORITY'] as $val): ?>
				<option value="<?=$val?>"><?=GetMessage($priority_lang.'_VALUE_'.$val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-MARK" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('REPORT_IGNORE_FILTER_VALUE')?></option>
			<option value="P"><?=GetMessage($mark_lang.'_VALUE_P')?></option>
			<option value="N"><?=GetMessage($mark_lang.'_VALUE_N')?></option>
		</select>
	</span>

	<span name="report-filter-value-control-DURATION_PLAN_HOURS" class="report-filter-vcc" callback="setDaysHoursField">
		<input type="hidden" name="value" value="" caller="true" />
		<input type="text" size="2" name="value_days"><?=GetMessage('TASKS_REPORT_DURATION_DAYS')?>&nbsp;
		<input type="text" size="2" name="value_hours"><?=GetMessage('TASKS_REPORT_DURATION_HOURS')?>
	</span>
	<script>
		function setDaysHoursField(control) {}
		function setDaysHoursFieldCatch(val)
		{
			var inpVal = setDaysHoursField_LAST_CALLER;
			if (inpVal) inpVal.value = val;
		}
		function refreshDaysHoursField()
		{
			var inp, days = null, hours = null, val;
			var valueControl = this.parentNode;
			var inpVal = BX.findChild(valueControl, {'tag': 'input', 'attr': {'name': 'value'}}, true);
			switch(this.name)
			{
				case 'value_days':
					inp = BX.findChild(valueControl, {'tag': 'input', 'attr': {'name': 'value_hours'}}, true);
					days = parseInt(this.value);
					hours = parseInt(inp.value);
					break;
				case 'value_hours':
					inp = BX.findChild(valueControl, {'tag': 'input', 'attr': {'name': 'value_days'}}, true);
					days = parseInt(inp.value);
					hours = parseInt(this.value);
					break;
			}
			if (inpVal)
			{
				val = null;
				if (days) val += days * 24;
				if (hours) val += hours;
				inpVal.value = val;
			}
		}
		function initDaysHoursFields()
		{
			var inpVal, inpDays, inpHours, days, hours;
			var container = BX('reports-filter-columns-container');
			var valueControls = BX.findChildren(
				container,
				{
					'tag': 'span',
					'attr': {'name': 'report-filter-value-control-DURATION_PLAN_HOURS'}
				},
				true,
				true
			);
			for (var i in valueControls)
			{
				inpVal = BX.findChild(valueControls[i], {'tag': 'input', 'attr': {'name': 'value'}}, true);
				inpDays = BX.findChild(valueControls[i], {'tag': 'input', 'attr': {'name': 'value_days'}}, true);
				inpHours = BX.findChild(valueControls[i], {'tag': 'input', 'attr': {'name': 'value_hours'}}, true);
				days = Math.floor(parseInt(inpVal.value)/24);
				if (days) inpDays.value = days;
				hours = parseInt(inpVal.value)%24;
				if (hours) inpHours.value = hours;
				BX.bind(inpDays, 'change', refreshDaysHoursField);
				BX.bind(inpHours, 'change', refreshDaysHoursField);
			}
		}
		BX.ready(function () {
			initDaysHoursFields();
		});
	</script>
</div>

<script>
	BX.ready(function() {
		const tasksReportEnabled = <?= Json::encode($arResult['tasksReportEnabled']) ?>;

		if (!tasksReportEnabled)
		{
			BX.addCustomEvent('SidePanel.Slider:onClose', (event) => {
				if (event.getSlider().getUrl() === 'ui:info_helper')
				{
					window.location.href = '<?= CUtil::JSEscape($pathToTasks) ?>';
				}
			});

			BX.Runtime.loadExtension('tasks.limit').then((exports) => {
				const { Limit } = exports;
				Limit.showInstance({
					featureId: '<?= $arResult['tasksReportFeatureId'] ?>',
					limitAnalyticsLabels: {
						module: 'tasks',
						source: 'view',
					},
				});
			});
		}
	});
</script>