<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\UI;

UI\Extension::load(["ui.design-tokens", "ui.fonts.opensans", "ui.tooltip"]);
CUtil::InitJSCore(array('popup'));

$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.report/templates/.default/script.js");

$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/main/core/css/core_popup.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");

$APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.topmenu',
	'',
	array(
		'USER_ID' => $arResult['USER_ID'],
		'GROUP_ID' => $arParams['GROUP_ID'],
		'SECTION_URL_PREFIX' => '',
		'PATH_TO_GROUP_TASKS' => $arParams['PATH_TO_GROUP_TASKS'],
		'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'],
		'PATH_TO_GROUP_TASKS_VIEW' => $arParams['PATH_TO_GROUP_TASKS_VIEW'],
		'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'],
		'PATH_TO_USER_TASKS' => $arParams['PATH_TO_TASKS'],
		'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_USER_TASKS_TASK'],
		'PATH_TO_USER_TASKS_VIEW' => $arParams['PATH_TO_USER_TASKS_VIEW'],
		'PATH_TO_USER_TASKS_REPORT' => $arParams['PATH_TO_TASKS_REPORT'],
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],
		'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT'],
		'MARK_SECTION_REPORTS' => 'Y',
		'MARK_ACTIVE_ROLE' => 'N'
	),
	$component,
	array('HIDE_ICONS' => true)
);

$GLOBALS["APPLICATION"]->IncludeComponent(
	'bitrix:main.calendar',
	'',
	array(
		'SILENT' => 'Y',
	),
	null,
	array('HIDE_ICONS' => 'Y')
);

$arPeriodTypes = array(
	"month" => GetMessage("TASKS_THIS_MONTH"),
	"month-ago" => GetMessage("TASKS_PREVIOUS_MONTH"),
	"week" => GetMessage("TASKS_THIS_WEEK"),
	"week-ago" => GetMessage("TASKS_PREVIOUS_WEEK"),
	"days" => GetMessage("TASKS_LAST_N_DAYS"),
	"after" => GetMessage("TASKS_AFTER"),
	"before" => GetMessage("TASKS_BEFORE"),
	"interval" => GetMessage("TASKS_DATE_INTERVAL")
);
if (!defined('TASKS_MUL_INCLUDED')):
	$APPLICATION->IncludeComponent("bitrix:main.user.link",
		'',
		array(
			"AJAX_ONLY" => "Y",
			"PATH_TO_SONET_USER_PROFILE" => $arParams["~PATH_TO_USER_PROFILE"],
			"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["~PATH_TO_MESSAGES_CHAT"],
			"DATE_TIME_FORMAT" => $arParams["~DATE_TIME_FORMAT"],
			"SHOW_YEAR" => $arParams["SHOW_YEAR"],
			"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
			"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
			"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
			"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
		),
		false,
		array("HIDE_ICONS" => "Y")
	);
	define('TASKS_MUL_INCLUDED', 1);
endif;
?>
<div class="task-report">
	<div class="tasks-whole-company-efficiency"><?php echo GetMessage("TASKS_REPORT_EMPLOYEES_COUNT")?>: <?php echo CTaskReport::GetEmployeesCount()?>. <?php echo GetMessage("TASKS_REPORT_USE_TASKS")?>: <?php echo CTaskReport::GetEmployeesCount() > 0 ? round(($arResult["COMPANY_STATS"]["RESPONSIBLES"] / CTaskReport::GetEmployeesCount()) * 100) : 0?>% (<?php echo $arResult["COMPANY_STATS"]["RESPONSIBLES"]?>). <?php echo GetMessage("TASKS_REPORT_WHOLE_COMPANY_EFFICIENCY")?>: <?php echo $arResult["COMPANY_STATS"]["MARKED_IN_REPORT"] > 0 ? round(($arResult["COMPANY_STATS"]["POSITIVE"] / $arResult["COMPANY_STATS"]["MARKED_IN_REPORT"]) * 100) : 0?>%</div>
	<div class="task-report-left-corner"></div>
	<div class="task-report-right-corner"></div>
	<table class="task-report-table" cellspacing="0" id="task-report-table">

		<colgroup>
			<col class="task-report-employee-column" />
			<col class="task-report-new-column" />
			<col class="task-report-open-column" />
			<col class="task-report-closed-column" />
			<col class="task-report-overdue-column" />
			<col class="task-report-marked-column" />
			<col class="task-report-efficiency-column" />
		</colgroup>

		<thead>
		<tr>
			<th class="task-report-employee-column<?php if(is_array($arResult["ORDER"]) && key($arResult["ORDER"]) == "RESPONSIBLE"):?> task-column-selected task-column-order-by-<?php echo (current($arResult["ORDER"]) == "ASC" ? "asc" : "desc")?><?php endif?>" onclick="SortTable('<?php echo $APPLICATION->GetCurPageParam("SORTF=RESPONSIBLE&SORTD=".(current($arResult["ORDER"]) == "ASC" && key($arResult["ORDER"]) == "RESPONSIBLE" ? "DESC" : "ASC"), array("SORTF", "SORTD"));?>', event)">
				<div class="task-head-cell"><span class="task-head-cell-sort-order"></span><span class="task-head-cell-title"><?php echo GetMessage("TASKS_REPORT_EMPLOYEE")?></span></div>
			</th>
			<th class="task-report-new-column<?php if(is_array($arResult["ORDER"]) && key($arResult["ORDER"]) == "NEW"):?> task-column-selected task-column-order-by-<?php echo (current($arResult["ORDER"]) == "ASC" ? "asc" : "desc")?><?php endif?>" onclick="SortTable('<?php echo $APPLICATION->GetCurPageParam("SORTF=NEW&SORTD=".(current($arResult["ORDER"]) == "ASC" && key($arResult["ORDER"]) == "NEW" ? "DESC" : "ASC"), array("SORTF", "SORTD"));?>', event)">
				<div class="task-head-cell"><span class="task-head-cell-sort-order"></span><span class="task-head-cell-title"><?php echo GetMessage("TASKS_REPORT_NEW")?></span><span class="task-head-cell-subtitle"><span class="task-report-head-label"><?php echo GetMessage("TASKS_REPORT_REPORT")?></span><span> / </span><span class="task-report-head-label-all"><?php echo GetMessage("TASKS_REPORT_ALL")?></span></span></div>
			</th>
			<th class="task-report-open-column<?php if(is_array($arResult["ORDER"]) && key($arResult["ORDER"]) == "OPEN"):?> task-column-selected task-column-order-by-<?php echo (current($arResult["ORDER"]) == "ASC" ? "asc" : "desc")?><?php endif?>" onclick="SortTable('<?php echo $APPLICATION->GetCurPageParam("SORTF=OPEN&SORTD=".(current($arResult["ORDER"]) == "ASC" && key($arResult["ORDER"]) == "OPEN" ? "DESC" : "ASC"), array("SORTF", "SORTD"));?>', event)">
				<div class="task-head-cell"><span class="task-head-cell-sort-order"></span><span class="task-head-cell-title"><?php echo GetMessage("TASKS_REPORT_OPEN")?></span><span class="task-head-cell-subtitle"><span class="task-report-head-label"><?php echo GetMessage("TASKS_REPORT_REPORT")?></span><span> / </span><span class="task-report-head-label-all"><?php echo GetMessage("TASKS_REPORT_ALL")?></span></span></div>
			</th>
			<th class="task-report-closed-column<?php if(is_array($arResult["ORDER"]) && key($arResult["ORDER"]) == "CLOSED"):?> task-column-selected task-column-order-by-<?php echo (current($arResult["ORDER"]) == "ASC" ? "asc" : "desc")?><?php endif?>" onclick="SortTable('<?php echo $APPLICATION->GetCurPageParam("SORTF=CLOSED&SORTD=".(current($arResult["ORDER"]) == "ASC" && key($arResult["ORDER"]) == "CLOSED" ? "DESC" : "ASC"), array("SORTF", "SORTD"));?>', event)">
				<div class="task-head-cell"><span class="task-head-cell-sort-order"></span><span class="task-head-cell-title"><?php echo GetMessage("TASKS_REPORT_CLOSED")?></span><span class="task-head-cell-subtitle"><span class="task-report-head-label"><?php echo GetMessage("TASKS_REPORT_REPORT")?></span><span> / </span><span class="task-report-head-label-all"><?php echo GetMessage("TASKS_REPORT_ALL")?></span></span></div>
			</th>
			<th class="task-report-overdue-column<?php if(is_array($arResult["ORDER"]) && key($arResult["ORDER"]) == "OVERDUED"):?> task-column-selected task-column-order-by-<?php echo (current($arResult["ORDER"]) == "ASC" ? "asc" : "desc")?><?php endif?>" onclick="SortTable('<?php echo $APPLICATION->GetCurPageParam("SORTF=OVERDUED&SORTD=".(current($arResult["ORDER"]) == "ASC" && key($arResult["ORDER"]) == "OVERDUED" ? "DESC" : "ASC"), array("SORTF", "SORTD"));?>', event)">
				<div class="task-head-cell"><span class="task-head-cell-sort-order"></span><span class="task-head-cell-title"><?php echo GetMessage("TASKS_REPORT_OVERDUE")?></span><span class="task-head-cell-subtitle"><span class="task-report-head-label"><?php echo GetMessage("TASKS_REPORT_REPORT")?></span><span> / </span><span class="task-report-head-label-all"><?php echo GetMessage("TASKS_REPORT_ALL")?></span></span></div>
			</th>
			<th class="task-report-marked-column<?php if(is_array($arResult["ORDER"]) && key($arResult["ORDER"]) == "MARKED"):?> task-column-selected task-column-order-by-<?php echo (current($arResult["ORDER"]) == "ASC" ? "asc" : "desc")?><?php endif?>" onclick="SortTable('<?php echo $APPLICATION->GetCurPageParam("SORTF=MARKED&SORTD=".(current($arResult["ORDER"]) == "ASC" && key($arResult["ORDER"]) == "MARKED" ? "DESC" : "ASC"), array("SORTF", "SORTD"));?>', event)">
				<div class="task-head-cell"><span class="task-head-cell-sort-order"></span><span class="task-head-cell-title"><?php echo GetMessage("TASKS_REPORT_MARKED")?></span></div>
			</th>
			<th class="task-report-efficiency-column<?php if(is_array($arResult["ORDER"]) && key($arResult["ORDER"]) == "POSITIVE"):?> task-column-selected task-column-order-by-<?php echo (current($arResult["ORDER"]) == "ASC" ? "asc" : "desc")?><?php endif?>" onclick="SortTable('<?php echo $APPLICATION->GetCurPageParam("SORTF=POSITIVE&SORTD=".(current($arResult["ORDER"]) == "ASC" && key($arResult["ORDER"]) == "POSITIVE" ? "DESC" : "ASC"), array("SORTF", "SORTD"));?>', event)">
				<div class="task-head-cell"><span class="task-head-cell-sort-order"></span><span class="task-head-cell-title"><?php echo GetMessage("TASKS_REPORT_EFFICIENCY")?></span></div>
			</th>
		</tr>
		</thead>
		<tbody>
			<?php $currentDepartment = 0?>
			<?php if (sizeof($arResult["REPORTS"])):?>
				<?php foreach($arResult["REPORTS"] as $key=>$report):?>
					<?php if ($currentDepartment != $report["DEPARTMENT_ID"]):?>
						<?php $currentDepartment = $report["DEPARTMENT_ID"]?>
						<tr class="task-report-section">
							<td colspan="7" class="task-report-section-column"><a class="task-report-section" href=""><?php
								foreach ($arResult["DEPARTMENTS"][$currentDepartment]["PARENTS"] as $section):
								?><a class="task-report-section" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_CONPANY_DEPARTMENT"], array("ID" => $section["ID"]))?>"><?php echo $section["NAME"]?></a><span class="task-report-section-separator">&mdash;</span><?php endforeach;?><a class="task-report-section" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_CONPANY_DEPARTMENT"], array("ID" => $report["DEPARTMENT_ID"]))?>"><?php echo $report["DEPARTMENT_NAME"]?></a></td>
						</tr>
					<?php endif?>
					<?php $anchor_id = RandString(8);?>
					<?php $commonFUrl = $arParams["PATH_TO_TASKS"]."?F_ADVANCED=Y&F_SUBORDINATE=Y&F_RESPONSIBLE=".$report["RESPONSIBLE_ID"];?>
					<tr class="task-report-item">
						<td class="task-report-employee-column"><a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_TASKS"], array("user_id" => $report["RESPONSIBLE_ID"]))?>" id="anchor_<?php echo $anchor_id?>" bx-tooltip-user-id="<?=$report["RESPONSIBLE_ID"]?>" class="task-report-employee-link"><?=CUser::FormatName($arParams["NAME_TEMPLATE"], array("NAME" => $report["~NAME"], "LAST_NAME" => $report["~LAST_NAME"], "SECOND_NAME" => $report["~SECOND_NAME"], "LOGIN" => $report["~LOGIN"]))?><?php if ($report["IS_HEAD"]):?><div class="task-report-employee-position"><?php echo GetMessage("TASKS_REPORT_HEAD")?></div><?php endif?></td>
						<td class="task-report-new-column">
							<span class="task-report-fraction">
								<a class="task-report-numerator" href="<?php echo $commonFUrl?>&F_DATE_FROM=<?php echo $arResult["START"]?>&F_DATE_TO=<?php echo $arResult["END"]?>&F_IN_REPORT=Y"><span class="task-report-fraction-number"><?php echo $report["NEW_IN_REPORT"]?></span></a><span class="task-report-fraction-slash">/</span><a class="task-report-denominator" href="<?php echo $commonFUrl?>&F_DATE_FROM=<?php echo $arResult["START"]?>&F_DATE_TO=<?php echo $arResult["END"]?>"><span class="task-report-fraction-number"><?php echo $report["NEW"]?></span></a>
							</span>
						</td>
						<td class="task-report-open-column">
							<span class="task-report-fraction">
								<a class="task-report-numerator" href="<?php echo $commonFUrl?>&F_ACTIVE_FROM=<?php echo $arResult["START"]?>&F_ACTIVE_TO=<?php echo $arResult["END"]?>&F_IN_REPORT=Y"><span class="task-report-fraction-number"><?php echo $report["IN_REPORT"]?></span></a><span class="task-report-fraction-slash">/</span><a class="task-report-denominator" href="<?php echo $commonFUrl?>&F_ACTIVE_FROM=<?php echo $arResult["START"]?>&F_ACTIVE_TO=<?php echo $arResult["END"]?>"><span class="task-report-fraction-number"><?php echo $report["CNT"]?></span></a>
							</span>
						</td>
						<td class="task-report-closed-column">
							<span class="task-report-fraction">
								<a class="task-report-numerator" href="<?php echo $commonFUrl?>&F_CLOSED_FROM=<?php echo $arResult["START"]?>&F_CLOSED_TO=<?php echo $arResult["END"]?>&F_IN_REPORT=Y" title="<?php echo $report["IN_REPORT"] > 0 ? round(($report["CLOSED_IN_REPORT"] / $report["IN_REPORT"]) * 100) : 0?>%"><span class="task-report-fraction-number"><?php echo $report["CLOSED_IN_REPORT"]?></span></a><span class="task-report-fraction-slash">/</span><a class="task-report-denominator" href="<?php echo $commonFUrl?>&F_CLOSED_FROM=<?php echo $arResult["START"]?>&F_CLOSED_TO=<?php echo $arResult["END"]?>" title="<?php echo $report["CNT"] > 0 ? round(($report["CLOSED"] / $report["CNT"]) * 100) : 0?>%"><span class="task-report-fraction-number"><?php echo $report["CLOSED"]?></span></a>
							</span>
						</td>
						<td class="task-report-overdue-column">
							<span class="task-report-fraction">
								<a class="task-report-numerator" href="<?php echo $commonFUrl?>&F_CLOSED_FROM=<?php echo $arResult["START"]?>&F_CLOSED_TO=<?php echo $arResult["END"]?>&F_OVERDUED=Y&F_IN_REPORT=Y" title="<?php echo $report["OVERDUE_IN_REPORT"]?>"><span class="task-report-fraction-number"><?php echo $report["CLOSED_IN_REPORT"] > 0 ? round(($report["OVERDUE_IN_REPORT"] / $report["CLOSED_IN_REPORT"]) * 100) : 0?></span><span class="task-report-fraction-percent">%</span></a><span class="task-report-fraction-slash">/</span><a class="task-report-denominator" href="<?php echo $commonFUrl?>&F_CLOSED_FROM=<?php echo $arResult["START"]?>&F_CLOSED_TO=<?php echo $arResult["END"]?>&F_OVERDUED=Y" title="<?php echo $report["OVERDUE"]?>"><span class="task-report-fraction-number"><?php echo $report["CLOSED"] > 0 ? round(($report["OVERDUE"] / $report["CLOSED"]) * 100) : 0?></span><span class="task-report-fraction-percent">%</span></a>
							</span>
						</td>
						<td class="task-report-marked-column">
							<a class="task-report-marked" href="<?php echo $commonFUrl?>&F_CLOSED_FROM=<?php echo $arResult["START"]?>&F_CLOSED_TO=<?php echo $arResult["END"]?>&F_MARKED=Y&F_IN_REPORT=Y" title="<?php echo $report["MARKED_IN_REPORT"]?>"><span class="task-report-marked-number"><?php echo $report["CLOSED_IN_REPORT"] > 0 ? round(($report["MARKED_IN_REPORT"] / $report["CLOSED_IN_REPORT"]) * 100) : 0?></span><span class="task-report-marked-percent">%</span></a>
						</td>
						<td class="task-report-efficiency-column">
							<span class="task-report-efficiency" title="<?php echo $report["POSITIVE"]?>"><span class="task-report-efficiency-number"><?php echo $report["MARKED_IN_REPORT"] > 0 ? round(($report["POSITIVE"] / $report["MARKED_IN_REPORT"]) * 100) : 0?></span><span class="task-report-efficiency-percent">%</span></span>
						</td>
					</tr>
					<?php if (!is_set($arResult["REPORTS"][$key+1]) || $arResult["REPORTS"][$key]["DEPARTMENT_ID"] != $arResult["REPORTS"][$key+1]["DEPARTMENT_ID"]):?>
						<?php $stats = $arResult["DEPARTMENTS"][$arResult["REPORTS"][$key]["DEPARTMENT_ID"]]["STATS"]?>
						<tr class="task-report-item task-report-item-summary">
							<td class="task-report-employee-column"><?php echo GetMessage("TASKS_REPORT_DEPARTMENT_SUMMARY")?>:</td>
							<td class="task-report-new-column">
								<span class="task-report-fraction">
									<span class="task-report-numerator"><span class="task-report-fraction-number"><?php echo $stats["NEW_IN_REPORT"]?></span></span><span class="task-report-fraction-slash">/</span><span class="task-report-denominator"><span class="task-report-fraction-number"><?php echo $stats["NEW"]?></span></span>
								</span>
							</td>
							<td class="task-report-open-column">
								<span class="task-report-fraction">
									<span class="task-report-numerator"><span class="task-report-fraction-number"><?php echo $stats["IN_REPORT"]?></span></span><span class="task-report-fraction-slash">/</span><span class="task-report-denominator"><span class="task-report-fraction-number"><?php echo $stats["CNT"]?></span></span>
								</span>
							</td>
							<td class="task-report-closed-column">
								<span class="task-report-fraction">
									<span class="task-report-numerator" title="<?php echo $stats["IN_REPORT"] > 0 ? round(($stats["CLOSED_IN_REPORT"] / $stats["IN_REPORT"]) * 100) : 0?>%"><span class="task-report-fraction-number"><?php echo $stats["CLOSED_IN_REPORT"]?></span></span><span class="task-report-fraction-slash">/</span><span class="task-report-denominator" title="<?php echo $stats["CNT"] > 0 ? round(($stats["CLOSED"] / $stats["CNT"]) * 100) : 0?>%"><span class="task-report-fraction-number"><?php echo $stats["CLOSED"]?></span></span>
								</span>
							</td>
							<td class="task-report-overdue-column">
								<span class="task-report-fraction">
									<span class="task-report-numerator" title="<?php echo $stats["OVERDUE_IN_REPORT"]?>"><span class="task-report-fraction-number"><?php echo $stats["CLOSED_IN_REPORT"] > 0 ? round(($stats["OVERDUE_IN_REPORT"] / $stats["CLOSED_IN_REPORT"]) * 100) : 0?></span><span class="task-report-fraction-percent">%</span></span><span class="task-report-fraction-slash">/</span><span class="task-report-denominator" title="<?php echo $stats["OVERDUE"]?>"><span class="task-report-fraction-number"><?php echo $stats["CLOSED"] > 0 ? round(($stats["OVERDUE"] / $stats["CLOSED"]) * 100) : 0?></span><span class="task-report-fraction-percent">%</span></span>
								</span>
							</td>
							<td class="task-report-marked-column">
								<span class="task-report-marked" title="<?php echo $stats["MARKED_IN_REPORT"]?>"><span class="task-report-marked-number"><?php echo $stats["CLOSED_IN_REPORT"] > 0 ? round(($stats["MARKED_IN_REPORT"] / $stats["CLOSED_IN_REPORT"]) * 100) : 0?></span><span class="task-report-marked-percent">%</span></span>
							</td>
							<td class="task-report-efficiency-column">
								<span class="task-report-efficiency" title="<?php echo $stats["POSITIVE"]?>"><span class="task-report-efficiency-number"><?php echo $stats["MARKED_IN_REPORT"] > 0 ? round(($stats["POSITIVE"] / $stats["MARKED_IN_REPORT"]) * 100) : 0?></span><span class="task-report-efficiency-percent">%</span></span>
							</td>
						</tr>
					<?php endif?>
				<?php endforeach?>
			<?php else:?>
				<tr class="task-report-item" id="task-report-no-tasks"><td class="task-new-item-column" colspan="7" style="text-align: center"><?php echo GetMessage("TASKS_NO_DATA")?></td></tr>
			<?php endif?>
		</tbody>
	</table>
</div>
<br />
<?php echo $arResult["NAV_STRING"]?>

<?php $this->SetViewTarget("sidebar_tools_1", 100);?>
<div class="sidebar-block task-filter task-filter-report">
	<b class="r2"></b><b class="r1"></b><b class="r0"></b>
	<div class="sidebar-block-inner">
		<div class="filter-block-title"><?php echo GetMessage("TASKS_REPORT_FILTER")?></div>
		<div class="filter-block">
			<form method="GET" name="task-filter-form" id="task-filter-form">
				<div class="filter-field filter-field-date-combobox<?php if (isset($arResult["FILTER"]["F_DATE_TYPE"])):?> filter-field-date-combobox-<?php echo $arResult["FILTER"]["F_DATE_TYPE"]; endif?>">
					<label for="task-interval-filter" class="filter-field-title"><?php echo GetMessage("TASKS_REPORT_PERIOD")?></label>
					<select class="filter-dropdown" onchange="OnTaskIntervalChange(this)" id="task-interval-filter" name="F_DATE_TYPE">
						<?php foreach($arPeriodTypes as $key=>$type):?>
							<option value="<?php echo $key?>"<?php echo $key == $arResult["FILTER"]["F_DATE_TYPE"] ? " selected" : ""?>><?php echo $type?></option>
						<?php endforeach;?>
					</select>

					<span class="filter-date-interval<?php
						if (isset($arResult["FILTER"]["F_DATE_TYPE"]))
						{
							switch ($arResult["FILTER"]["F_DATE_TYPE"])
							{
								case "interval":
									echo " filter-date-interval-after filter-date-interval-before";
									break;
								case "before":
									echo " filter-date-interval-before";
									break;
								case "after":
									echo " filter-date-interval-after";
									break;
							}
						}
					?>"><span class="filter-date-interval-from"><input type="text" class="filter-date-interval-from" name="F_DATE_FROM" value="<?php echo isset($arResult["FILTER"]["F_DATE_FROM"]) ? $arResult["FILTER"]["F_DATE_FROM"] : ""?>" /><a class="filter-date-interval-calendar" href="" title="<?php echo GetMessage("TASKS_PICK_DATE")?>" id="filter-date-interval-calendar-from"><img border="0" src="/bitrix/components/bitrix/main.calendar/templates/.default/images/icon.gif" alt="<?php echo GetMessage("TASKS_PICK_DATE")?>"></a></span><span class="filter-date-interval-hellip">&hellip;</span><span class="filter-date-interval-to"><input type="text" class="filter-date-interval-to" name="F_DATE_TO" value="<?php echo isset($arResult["FILTER"]["F_DATE_TO"]) ? $arResult["FILTER"]["F_DATE_TO"] : ""?>" /><a href="" class="filter-date-interval-calendar" title="<?php echo GetMessage("TASKS_PICK_DATE")?>" id="filter-date-interval-calendar-to"><img border="0" src="/bitrix/components/bitrix/main.calendar/templates/.default/images/icon.gif" alt="<?php echo GetMessage("TASKS_PICK_DATE")?>"></a></span></span>
					<span class="filter-day-interval<?php if ($arResult["FILTER"]["F_DATE_TYPE"] == "days"):?> filter-day-interval-selected<?php endif?>"><input type="text" size="5" class="filter-date-days" value="<?php echo isset($arResult["FILTER"]["F_DATE_DAYS"]) ? $arResult["FILTER"]["F_DATE_DAYS"] : ""?>" name="F_DATE_DAYS" /> <?php echo GetMessage("TASKS_REPORT_DAYS")?></span>

				</div>

				<script type="text/javascript">

					function OnTaskIntervalChange(select)
					{
						select.parentNode.className = "filter-field filter-field-date-combobox " + "filter-field-date-combobox-" + select.value;

						var dateInterval = BX.findNextSibling(select, { "tag": "span", "class": "filter-date-interval" });
						var dayInterval = BX.findNextSibling(select, { "tag": "span", "class": "filter-day-interval" });

						BX.removeClass(dateInterval, "filter-date-interval-after filter-date-interval-before");
						BX.removeClass(dayInterval, "filter-day-interval-selected");

						if (select.value == "interval")
							BX.addClass(dateInterval, "filter-date-interval-after filter-date-interval-before");
						else if(select.value == "before")
							BX.addClass(dateInterval, "filter-date-interval-before");
						else if(select.value == "after")
							BX.addClass(dateInterval, "filter-date-interval-after");
						else if(select.value == "days")
							BX.addClass(dayInterval, "filter-day-interval-selected");
					}


				</script>

				<?php if(sizeof($arResult["SUBORDINATE_DEPS"])):?>
					<div class="filter-field">
						<label for="task-employee-department" class="filter-field-title"><?php echo GetMessage("TASKS_REPORT_STRUCTURE")?></label>
						<select class="filter-dropdown" id="task-employee-department" name="F_DEPARTMENT_ID">
							<option value=""><?php echo GetMessage("TASKS_REPORT_NOT_SELECTED")?></option>
							<?php $startLevel = $arResult["SUBORDINATE_DEPS"][0]["DEPTH_LEVEL"]?>
							<?php foreach($arResult["SUBORDINATE_DEPS"] as $department):?>
								<option value="<?php echo $department["ID"]?>"<?php echo $department["ID"] == $arResult["FILTER"]["F_DEPARTMENT_ID"] ? " selected" : "" ?>><?php echo str_repeat(".", $department["DEPTH_LEVEL"] - $startLevel >= 0 ? $department["DEPTH_LEVEL"] - $startLevel : 0)?><?php echo $department["NAME"]?></option>
							<?php endforeach?>
						</select>
					</div>
				<?php endif?>
				<?php if(sizeof($arResult["GROUPS"])):?>
					<div class="filter-field">
						<label for="filter-field-employee" class="filter-field-title"><?php echo GetMessage("TASKS_REPORT_WORKGROUP")?></label>
						<?php
							$groupName = "";
							if (intval($arResult["FILTER"]["F_GROUP_ID"]) > 0)
							{
								$arGroup = CSocNetGroup::GetById(intval($arResult["FILTER"]["F_GROUP_ID"]));
								if ($arGroup)
								{
									$groupName = $arGroup["NAME"];
								}
							}
						?>
						<span class="webform-field webform-field-textbox<?php if($groupName == ''):?> webform-field-textbox-empty<?php endif?> webform-field-textbox-clearable">
							<span class="webform-field-textbox-inner" id="task-report-filter-group">
								<input type="text" id="filter-field-group" class="webform-field-textbox" value="<?php echo $groupName?>" />
								<a class="webform-field-textbox-clear" href=""></a>
							</span>
						</span>
						<input type="hidden" name="F_GROUP_ID" value="<?php echo intval($arResult["FILTER"]["F_GROUP_ID"])?>" />
						<?php
							$name = $APPLICATION->IncludeComponent(
								"bitrix:socialnetwork.group.selector", ".default", array(
									"BIND_ELEMENT" => "task-report-filter-group",
									"ON_SELECT" => "onFilterGroupSelect",
									"SEARCH_INPUT" => "filter-field-group",
									"SELECTED" => $arResult["FILTER"]["F_GROUP_ID"] ? $arResult["FILTER"]["F_GROUP_ID"] : 0
								), null, array("HIDE_ICONS" => "Y")
							);
						?>
					</div>
				<?php endif?>
				<div class="filter-field">
					<label for="filter-field-employee" class="filter-field-title"><?php echo GetMessage("TASKS_REPORT_SUBORDINATE")?></label>
					<?php
						$userName = "";
						if (intval($arResult["FILTER"]["F_RESPONSIBLE_ID"]) > 0)
						{
							$rsUser = CUser::GetById(intval($arResult["FILTER"]["F_RESPONSIBLE_ID"]));
							if ($arUser = $rsUser->Fetch())
							{
								$userName = CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser);
							}
						}
					?>
					<span class="webform-field webform-field-textbox<?php if($userName == ''):?> webform-field-textbox-empty<?php endif?> webform-field-textbox-clearable">
						<span class="webform-field-textbox-inner">
							<input type="text" id="filter-field-employee" class="webform-field-textbox" value="<?php echo $userName?>" />
							<a class="webform-field-textbox-clear" href=""></a>
						</span>
					</span>
					<input type="hidden" name="F_RESPONSIBLE_ID" value="<?php echo intval($arResult["FILTER"]["F_RESPONSIBLE_ID"])?>" />
					<?php
						$name = $APPLICATION->IncludeComponent(
							"bitrix:tasks.user.selector",
							".default",
							array(
								"MULTIPLE" => "N",
								"NAME" => "FILTER_RESPONSIBLE_ID",
								"INPUT_NAME" => "filter-field-employee",
								"VALUE" => intval($arResult["FILTER"]["F_RESPONSIBLE_ID"]),
								"POPUP" => "Y",
								"ON_SELECT" => "onFilterResponsibleSelect",
								"GROUP_ID_FOR_SITE" => (intval($_GET["GROUP_ID"]) > 0 ? $_GET["GROUP_ID"] : (intval($arParams["GROUP_ID"]) > 0 ? $arParams["GROUP_ID"] : false))
							),
							null,
							array("HIDE_ICONS" => "Y")
						);
					?>
				</div>
				<div class="filter-field-buttons">
					<input type="submit" value="<?php echo GetMessage("TASKS_REPORT_FIND")?>" class="filter-submit">&nbsp;&nbsp;<input type="button" onclick="jsUtils.Redirect([], '<?php echo CUtil::JSEscape($APPLICATION->GetCurPageParam("F_CANCEL=Y", array("F_DATE_TYPE", "F_DATE_FROM", "F_DATE_TO", "F_DATE_DAYS", "F_DEPARTMENT_ID", "F_GROUP_ID", "F_RESPONSIBLE_ID", "F_FILTER")))?>');" name="del_filter_company_search" value="<?php echo GetMessage("TASKS_REPORT_CANCEL")?>" class="filter-submit">
				</div>
			</form>
			<input type="hidden" name="F_FILTER" value="Y"/>
		</div>
	</div>
	<i class="r0"></i><i class="r1"></i><i class="r2"></i>
</div>
<?php $this->EndViewTarget();?>


<? $this->SetViewTarget("pagetitle", 100);?>
<div class="task-title-buttons task-detail-title-buttons">
	<span class="task-report-title-checkbox">
		<input class="textbox" type="checkbox" id="task-title-checkbox" onclick="BX.toggleClass(BX('task-report-table'), 'task-report-table-full')" />
		<label for="task-title-checkbox"><?= GetMessage("TASKS_REPORT_SHOW_ALL")?></label>
	</span>
	<? if ($arParams["BACK_TO_TASKS"] != "N"):?>
		<span class="task-title-button-separator"></span>
		<a href="<?= CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array());?>">
			<i class="task-title-button-back-icon"></i>
			<span class="task-title-button-back-text"><?php echo GetMessage("TASKS_ADD_BACK_TO_TASKS_LIST")?></span>
		</a>
	<?php endif?>
</div>
<? $this->EndViewTarget();?>

<script>tasksReportDefaultTemplateInit()</script>