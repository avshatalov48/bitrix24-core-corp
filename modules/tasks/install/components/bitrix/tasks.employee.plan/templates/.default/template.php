<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Tasks\Helper\RestrictionUrl;
use Bitrix\Tasks\UI;

Extension::load(['ui.design-tokens', 'ui.buttons']);

/** intranet-settings-support */
if (($arResult['IS_TOOL_AVAILABLE'] ?? null) === false)
{
	$APPLICATION->IncludeComponent("bitrix:tasks.error", "limit", [
		'LIMIT_CODE' => RestrictionUrl::TASK_LIMIT_OFF_SLIDER_URL,
		'SOURCE' => 'employee_plan',
	]);

	return;
}

$APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.topmenu',
	'',
	[
		'USER_ID' => $arParams['USER_ID'],
		'GROUP_ID' => ($arParams['GROUP_ID'] ?? null),
		'SECTION_URL_PREFIX' => '',
		'PATH_TO_GROUP_TASKS' => ($arParams['PATH_TO_GROUP_TASKS'] ?? null),
		'PATH_TO_GROUP_TASKS_TASK' => ($arParams['PATH_TO_GROUP_TASKS_TASK'] ?? null),
		'PATH_TO_GROUP_TASKS_VIEW' => ($arParams['PATH_TO_GROUP_TASKS_VIEW'] ?? null),
		'PATH_TO_GROUP_TASKS_REPORT' => ($arParams['PATH_TO_GROUP_TASKS_REPORT'] ?? null),
		'PATH_TO_USER_TASKS' => ($arParams['PATH_TO_USER_TASKS'] ?? null),
		'PATH_TO_USER_TASKS_TASK' => ($arParams['PATH_TO_USER_TASKS_TASK'] ?? null),
		'PATH_TO_USER_TASKS_VIEW' => ($arParams['PATH_TO_USER_TASKS_VIEW'] ?? null),
		'PATH_TO_USER_TASKS_REPORT' => ($arParams['PATH_TO_TASKS_REPORT'] ?? null),
		'PATH_TO_USER_TASKS_TEMPLATES' => ($arParams['PATH_TO_USER_TASKS_TEMPLATES'] ?? null),
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => ($arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'] ?? null),
		'PATH_TO_CONPANY_DEPARTMENT' => ($arParams['PATH_TO_CONPANY_DEPARTMENT'] ?? null),
		'MARK_SECTION_EMPLOYEE_PLAN' => 'Y',
		'MARK_TEMPLATES' => 'N',
		'MARK_ACTIVE_ROLE' => 'N',
	],
	$component,
	['HIDE_ICONS' => true]
);

$filter = $arResult['FILTER'];
?>

<?$arResult['HELPER']->displayFatals();?>
<?if(!$arResult['HELPER']->checkHasFatals()):?>
	<?$arResult['HELPER']->displayWarnings();?>

	<div id="<?=$arResult['HELPER']->getScopeId()?>" class="tasks-empplan tasks tasks-employee-plan-wrapper">

		<div class="tasks-employee-plan-inner js-id-empplan-filter">
			<div class="tasks-employee-plan-goal js-id-empplan-status-selector">
				<span class="tasks-employee-plan-name"><?=Loc::getMessage('TASKS_COMMON_TASK')?>:</span>
				<span class="tasks-employee-plan-item js-id-selectbox-open js-id-selectbox-current-display"></span>
			</div>
			<div class="tasks-employee-plan-department js-id-empplan-department-selector">
				<span class="tasks-employee-plan-name"><?=Loc::getMessage('TASKS_EMPLOYEEPLAN_BY_DEPARTMENT')?>:</span>
				<span class="tasks-employee-plan-item js-id-selectbox-open js-id-selectbox-current-display"></span>
			</div>
			<div class="tasks-employee-plan-worker  js-id-empplan-user-selector">
				<span class="tasks-employee-plan-name"><?=Loc::getMessage('TASKS_EMPLOYEEPLAN_OF_EMPLOYEE')?>:</span>
				<span class="tasks-employee-plan-item tasks-employee-plan-worker-item js-id-combobox-open js-id-combobox-current-display"></span>
				<span class="tasks-employee-plan-worker-inner">
					<input class="tasks-employee-plan-worker-inner-item js-id-combobox-search" type="text" />
				</span>
			</div>
			<div class="tasks-employee-plan-period js-id-empplan-date-range">
				<span class="tasks-employee-plan-period-inner">
					<span class="tasks-employee-plan-name"><?=Loc::getMessage('TASKS_EMPLOYEEPLAN_BY_PERIOD')?>:</span>

					<span class="tasks-employee-plan-period-calendar-container">
						<span class="tasks-employee-plan-period-calendar-item js-id-date-range-show"><?=htmlspecialcharsbx(UI::formatDateTimeSiteL2S($filter['TASK']['DATE_RANGE']['FROM']))?> &ndash; <?=htmlspecialcharsbx(UI::formatDateTimeSiteL2S($filter['TASK']['DATE_RANGE']['TO']))?></span>

						<span class="tasks-employee-plan-period-calendar">
							<span class="tasks-employee-plan-period-calendar-inner">
								<span class="tasks-employee-plan-period-calendar-date js-id-date-range-from-container">
									<input class="tasks-employee-plan-period-calendar-date-item js-id-datepicker-display js-id-date-range-from" type="text" value="" readonly="readonly" />
									<input class="js-id-date-range-from js-id-datepicker-value" type="hidden" name="TASK[DATE_RANGE][FROM]" value="<?=htmlspecialcharsbx($filter['TASK']['DATE_RANGE']['FROM'])?>" />
								</span>
								<span class="tasks-employee-plan-period-calendar-dash">&ndash;</span>
								<span class="tasks-employee-plan-period-calendar-date js-id-date-range-to-container">
									<input class="tasks-employee-plan-period-calendar-date-item js-id-datepicker-display js-id-date-range-to" type="text" value="" readonly="readonly" />
									<input class="js-id-date-range-to js-id-datepicker-value" type="hidden" name="TASK[DATE_RANGE][TO]" value="<?=htmlspecialcharsbx($filter['TASK']['DATE_RANGE']['TO'])?>" />
								</span>
							</span><!--tasks-employee-plan-period-calendar-inner-->
						</span><!--tasks-employee-plan-period-calendar-->

					</span><!--tasks-employee-plan-period-calendar-container-->

				</span>
			</div>
		</div><!--tasks-employee-plan-inner-->

		<div class="js-id-empplan-result grid">
		</div>

		<div class="tasks-empplan-bottom-panel">
			<button class="js-id-empplan-search-more ui-btn ui-btn-light-border no-display"><?=Loc::getMessage('TASKS_EMPLOYEEPLAN_SHOW_MORE')?></button>
		</div>

	</div>

	<?$arResult['HELPER']->initializeExtension();?>

<?endif?>