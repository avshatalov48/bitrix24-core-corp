<?php

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Helper\RestrictionUrl;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$APPLICATION->ShowViewContent('task_menu');
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$bodyClass = ($bodyClass ? "{$bodyClass} page-one-column" : 'page-one-column');
$APPLICATION->SetPageProperty('BodyClass', $bodyClass);

/** intranet-settings-support */
if (!$arResult['IS_TOOL_AVAILABLE'])
{
	$APPLICATION->IncludeComponent("bitrix:tasks.error", "limit", [
		'LIMIT_CODE' => RestrictionUrl::TASK_LIMIT_OFF_SLIDER_URL,
		'SOURCE' => 'report',
	]);

	return;
}

$APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.topmenu',
	'',
	array(
		'USER_ID' => ($arResult['USER_ID'] ?? null),
		'GROUP_ID' => ($arParams['GROUP_ID'] ?? null),
		'SECTION_URL_PREFIX' => '',
		'PATH_TO_GROUP_TASKS' => ($arParams['PATH_TO_GROUP_TASKS'] ?? null),
		'PATH_TO_GROUP_TASKS_TASK' => ($arParams['PATH_TO_GROUP_TASKS_TASK'] ?? null),
		'PATH_TO_GROUP_TASKS_VIEW' => ($arParams['PATH_TO_GROUP_TASKS_VIEW'] ?? null),
		'PATH_TO_GROUP_TASKS_REPORT' => ($arParams['PATH_TO_GROUP_TASKS_REPORT'] ?? null),
		'PATH_TO_USER_TASKS' => ($arParams['PATH_TO_TASKS'] ?? null),
		'PATH_TO_USER_TASKS_TASK' => ($arParams['PATH_TO_USER_TASKS_TASK'] ?? null),
		'PATH_TO_USER_TASKS_VIEW' => ($arParams['PATH_TO_USER_TASKS_VIEW'] ?? null),
		'PATH_TO_USER_TASKS_REPORT' => ($arParams['PATH_TO_TASKS_REPORT'] ?? null),
		'PATH_TO_USER_TASKS_TEMPLATES' => ($arParams['PATH_TO_USER_TASKS_TEMPLATES'] ?? null),
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => ($arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'] ?? null),
		'PATH_TO_CONPANY_DEPARTMENT' => ($arParams['PATH_TO_CONPANY_DEPARTMENT'] ?? null),
		'MARK_SECTION_REPORTS' => 'Y',
		'MARK_TEMPLATES' => 'N',
		'MARK_ACTIVE_ROLE' => 'N',
	),
	$component,
	['HIDE_ICONS' => true]
);
$isIframe = (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y');

?>
<div class="<?= !$arResult['tasksReportEnabled'] ? 'task-report-locked' : '' ?>">
<?php

$APPLICATION->IncludeComponent(
	'bitrix:report.list',
	'',
	[
		'USER_ID' => $arResult['USER_ID'],
		'GROUP_ID' => ($arParams['GROUP_ID'] ?? null),
		'PATH_TO_REPORT_LIST' => ($arParams['PATH_TO_TASKS_REPORT'] ?? null) . ($isIframe ? '?IFRAME=Y' : ''),
		'PATH_TO_REPORT_CONSTRUCT' => ($arParams['PATH_TO_TASKS_REPORT_CONSTRUCT'] ?? null) . ($isIframe ? '?IFRAME=Y' : ''),
		'PATH_TO_REPORT_VIEW' => ($arParams['PATH_TO_TASKS_REPORT_VIEW'] ?? null) . ($isIframe ? '?IFRAME=Y' : ''),
		'REPORT_HELPER_CLASS' => 'CTasksReportHelper',
	],
	false
);

$pathToTasks = (preg_match('#^/\w#', $arResult['pathToTasks']) ? $arResult['pathToTasks']: '/');
?>
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
						source: 'list',
					},
				});
			});
		}
	});
</script>
