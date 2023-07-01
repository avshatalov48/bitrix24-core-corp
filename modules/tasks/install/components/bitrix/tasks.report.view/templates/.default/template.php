<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.topmenu',
	'',
	[
		'USER_ID' => ($arResult['USER_ID'] ?? null),
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

<?php $this->SetViewTarget("report_view_prefilter", 100);?>
<style>
	.report-filter-block-title {margin-bottom: 0px!important; border-bottom: none!important;}
	.tasks-report-filter-range {border-top: solid 1px #E9E9E9; padding: 6px 0px}
	.tasks-report-filter-range-last {border-bottom: solid 1px #E9E9E9;}
	.tasks-report-filter-range label.filter-field-title {display: inline; margin-left: 7px; position: relative; top:-2px}
	.reports-description-text {border-top: solid 1px #E9E9E9; padding: 6px 0px}
</style>

<div class="filter-field">
	<div class="tasks-report-filter-range">
		<input type="checkbox" name="select_my_tasks" id="select_my_tasks" <?=isset($_GET['select_my_tasks'])?'checked':''?> title="<?=GetMessage('TASKS_REPORT_MY_TASKS_HINT')?>"><label title="<?=GetMessage('TASKS_REPORT_MY_TASKS_HINT')?>" for="select_my_tasks" class="filter-field-title"><?=GetMessage('TASKS_REPORT_MY_TASKS_ONLY')?></label>
	</div>
	<div class="tasks-report-filter-range">
		<input type="checkbox" name="select_depts_tasks" id="select_depts_tasks" <?=isset($_GET['select_depts_tasks'])?'checked':''?> title="<?=GetMessage('TASKS_REPORT_MY_DEPTS_TASKS_ONLY_HINT')?>"><label title="<?=GetMessage('TASKS_REPORT_MY_DEPTS_TASKS_ONLY_HINT')?>" for="select_depts_tasks" class="filter-field-title"><?=GetMessage('TASKS_REPORT_MY_DEPTS_TASKS_ONLY')?></label>
	</div>
	<div class="tasks-report-filter-range tasks-report-filter-range-last">
		<input type="checkbox" name="select_group_tasks" id="select_group_tasks" <?=isset($_GET['select_group_tasks'])?'checked':''?> title="<?=GetMessage('TASKS_REPORT_MY_GROUPS_TASKS_ONLY_HINT')?>"><label title="<?=GetMessage('TASKS_REPORT_MY_GROUPS_TASKS_ONLY_HINT')?>" for="select_group_tasks" class="filter-field-title"><?=GetMessage('TASKS_REPORT_MY_GROUPS_TASKS_ONLY')?></label>
	</div>
</div>
<?php $this->EndViewTarget();?>


<?php
$APPLICATION->IncludeComponent(
	'bitrix:report.view',
	'',
	[
		'USER_ID' => ($arResult['USER_ID'] ?? null),
		'GROUP_ID' => ($arParams['GROUP_ID'] ?? null),
		'REPORT_ID' => ($arParams['REPORT_ID'] ?? null),
		'USER_NAME_FORMAT' => ($arParams['NAME_TEMPLATE'] ?? null),
		'ROWS_PER_PAGE' => ($arParams['ROWS_PER_PAGE'] ?? null),
		'PATH_TO_REPORT_LIST' => ($arParams['PATH_TO_TASKS_REPORT'] ?? null),
		'PATH_TO_REPORT_CONSTRUCT' => ($arParams['PATH_TO_TASKS_REPORT_CONSTRUCT'] ?? null),
		'PATH_TO_REPORT_VIEW' => ($arParams['PATH_TO_TASKS_REPORT_VIEW'] ?? null),
		'REPORT_HELPER_CLASS' => 'CTasksReportHelper',
		'USE_CHART' => true,
		'STEXPORT_PARAMS' => ['serviceUrl' => '/bitrix/components/bitrix/tasks.report.view/stexport.ajax.php'],
	],
	false
);

$entity = Bitrix\Main\Entity\Base::getInstance('Bitrix\Tasks\TaskTable');
$status_lang = $entity->getField('STATUS')->getLangCode();
$status_lang_pseudo = $entity->getField('STATUS_PSEUDO')->getLangCode();
$priority_lang = $entity->getField('PRIORITY')->getLangCode();
$mark_lang = $entity->getField('MARK')->getLangCode();

?>

<div id="report-chfilter-examples-custom" style="display: none;">

	<div class="filter-field filter-field-user-phone chfilter-field-STATUS" callback="RTFilter_chooseBoolean">
		<label for="user-department" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select name="%NAME%" class="filter-dropdown" id="%ID%" caller="true">
			<option value=""><?=GetMessage('REPORT_IGNORE_FILTER_VALUE')?></option>
			<option value="1"><?=GetMessage($status_lang.'_VALUE_1')?></option>
			<option value="2"><?=GetMessage($status_lang.'_VALUE_2')?></option>
			<option value="3"><?=GetMessage($status_lang.'_VALUE_3')?></option>
			<option value="4"><?=GetMessage($status_lang.'_VALUE_4')?></option>
			<option value="5"><?=GetMessage($status_lang.'_VALUE_5')?></option>
			<option value="6"><?=GetMessage($status_lang.'_VALUE_6')?></option>
			<option value="7"><?=GetMessage($status_lang.'_VALUE_7')?></option>
		</select>
	</div>

	<div class="filter-field filter-field-user-phone chfilter-field-STATUS_PSEUDO" callback="RTFilter_chooseBoolean">
		<label for="user-department" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select name="%NAME%" class="filter-dropdown" id="%ID%" caller="true">
			<option value=""><?=GetMessage('REPORT_IGNORE_FILTER_VALUE')?></option>
			<option value="1"><?=GetMessage($status_lang_pseudo.'_VALUE_1')?></option>
			<option value="2"><?=GetMessage($status_lang_pseudo.'_VALUE_2')?></option>
			<option value="3"><?=GetMessage($status_lang_pseudo.'_VALUE_3')?></option>
			<option value="4"><?=GetMessage($status_lang_pseudo.'_VALUE_4')?></option>
			<option value="5"><?=GetMessage($status_lang_pseudo.'_VALUE_5')?></option>
			<option value="6"><?=GetMessage($status_lang_pseudo.'_VALUE_6')?></option>
			<option value="7"><?=GetMessage($status_lang_pseudo.'_VALUE_7')?></option>
			<option value="-1"><?=GetMessage($status_lang_pseudo.'_VALUE_-1')?></option>
		</select>
	</div>

	<div class="filter-field filter-field-user-phone chfilter-field-PRIORITY" callback="RTFilter_chooseBoolean">
		<label for="user-department" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select name="%NAME%" class="filter-dropdown" id="%ID%" caller="true">
			<option value=""><?=GetMessage('REPORT_IGNORE_FILTER_VALUE')?></option>
			<option value="1"><?=GetMessage($priority_lang.'_VALUE_1')?></option>
			<option value="2"><?=GetMessage($priority_lang.'_VALUE_2')?></option>
		</select>
	</div>

	<div class="filter-field filter-field-user-phone chfilter-field-MARK" callback="RTFilter_chooseBoolean">
		<label for="user-department" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select name="%NAME%" class="filter-dropdown" id="%ID%" caller="true">
			<option value=""><?=GetMessage('REPORT_IGNORE_FILTER_VALUE')?></option>
			<option value="P"><?=GetMessage($mark_lang.'_VALUE_P')?></option>
			<option value="N"><?=GetMessage($mark_lang.'_VALUE_N')?></option>
		</select>
	</div>

	<span class="filter-field chfilter-field-DURATION_PLAN_HOURS">
		<label class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<input type="hidden" name="%NAME%" value="%VALUE%" />
		<input type="text" size="2" name="value_days"><?=GetMessage('TASKS_REPORT_DURATION_DAYS')?>&nbsp;
		<input type="text" size="2" name="value_hours"><?=GetMessage('TASKS_REPORT_DURATION_HOURS')?>
	</span>
	<script type="text/javascript">
		function refreshDaysHoursField()
		{
			var inp, days = null, hours = null, val;
			var valueControl = this.parentNode;
			var inpVal = BX.findChild(valueControl, {'tag': 'input', 'attr': {'type': 'hidden'}}, true);
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
			var container = BX('report-filter-chfilter');
			var valueControls = BX.findChildren(
				container,
				{
					'tag': 'span',
					'class': 'filter-field chfilter-field-DURATION_PLAN_HOURS'
				},
				true,
				true
			);
			for (var i in valueControls)
			{
				inpVal = BX.findChild(valueControls[i], {'tag': 'input', 'attr': {'type': 'hidden'}}, true);
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