<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\SocialNetwork;

$isIFrame = $_REQUEST['IFRAME'] == 'Y';

Loc::loadMessages(__FILE__);
CUtil::InitJSCore(array('popup', 'tooltip', 'gantt', 'tasks_util_query', 'task_info_popup', 'task-popups', 'CJSTask'));

\Bitrix\Main\UI\Extension::load([
	'ui.counter',
]);

$APPLICATION->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/script.js");
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/gantt-view.js");
$APPLICATION->AddHeadScript("/bitrix/js/tasks/task-iframe-popup.js");

$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");
$APPLICATION->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass . " " : "") . "page-one-column");

if (\Bitrix\Tasks\Util\DisposableAction::needConvertTemplateFiles())
{
	$APPLICATION->IncludeComponent("bitrix:tasks.util.process",
		'',
		array(),
		false,
		array("HIDE_ICONS" => "Y")
	);
}
$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", []);
$APPLICATION->IncludeComponent(
	'bitrix:main.calendar',
	'',
	['SILENT' => 'Y'],
	null,
	['HIDE_ICONS' => 'Y']
);
$APPLICATION->IncludeComponent(
	"bitrix:tasks.iframe.popup",
	".default",
	[],
	null,
	["HIDE_ICONS" => "Y"]
);

$arPaths = [
	"PATH_TO_TASKS_TASK" => $arParams['GROUP_ID'] > 0 ? $arParams['PATH_TO_GROUP_TASKS_TASK'] : $arParams["PATH_TO_USER_TASKS_TASK"],
	"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"]
];

$cs = \Bitrix\Tasks\UI::translateCalendarSettings($arResult['CALENDAR_SETTINGS']);
$holidays = $cs['HOLIDAYS'];
$hours = $cs['HOURS'];
$weekEnds = $cs['WEEK_END'];
$weekStart = $cs['WEEK_START'];

$currentGroupId = $arParams['GROUP_ID'];
$canDragTasks = false;

if ($currentGroupId)
{
	$canDragTasks =
		array_key_exists('SORTING', $arResult['GET_LIST_PARAMS']['order']) &&
		SocialNetwork\Group::can($currentGroupId, SocialNetwork\Group::ACTION_SORT_TASKS);
}
else
{
	$canDragTasks =
		array_key_exists('SORTING', $arResult['GET_LIST_PARAMS']['order']) &&
		$arParams["USER_ID"] == $USER->GetID();
}


?>

<script type="text/javascript">
    BX.message({
        TASKS_PATH_TO_USER_PROFILE: "<?php echo CUtil::JSEscape($arParams["PATH_TO_USER_PROFILE"])?>",
        TASKS_PATH_TO_TASK: "<?php echo CUtil::JSEscape($arParams['GROUP_ID'] > 0 ? $arParams["PATH_TO_GROUP_TASKS_TASK"] : $arParams["PATH_TO_USER_TASKS_TASK"])?>",
        TASKS_CANNOT_ADD_DEPENDENCY: "<?=GetMessage("TASKS_CANNOT_ADD_DEPENDENCY")?>",
		TASKS_CLOSE_PAGE_CONFIRM: '<?=GetMessageJS('TASKS_CLOSE_PAGE_CONFIRM')?>'
    });

	<?
		$filter = $arResult["GET_LIST_PARAMS"]["legacyFilter"];
		unset($filter['ONLY_ROOT_TASKS']);
	?>
    var arFilter = <?php echo CUtil::PhpToJSObject($filter)?>;
    var arOrder = <?php echo CUtil::PhpToJSObject($arResult["GET_LIST_PARAMS"]["order"])?>;
    var tasksListAjaxUrl = "/bitrix/components/bitrix/tasks.list/ajax.php?SITE_ID=<?php echo SITE_ID?><?=$arParams['GROUP_ID'] ? "&GROUP_ID=" . $arParams["GROUP_ID"] : ""?>";
    var ajaxUrl = tasksListAjaxUrl;
    var tasksIFrameList = <?php echo CUtil::PhpToJSObject(array_keys($arResult["LIST"]))?>;
    var ganttChart;
    var ganttFilterId = '<?=$arParams['FILTER_ID']?>';

    var ganttAux = {
        unAttachedDeps: [],
        tryAttachDeps: function (deps) {
            if (typeof deps == 'undefined' || !BX.type.isArray(deps)) {
                return;
            }
            for (var k = 0; k < deps.length; k++) {
                if (ganttChart.addDependencyFromJSON(deps[k]) == null) {
                    ganttAux.unAttachedDeps.push(deps[k]);
                }
            }
        },
        tryReattachUnattached: function () {
            var stillUnattached = [];
            for (var k = 0; k < ganttAux.unAttachedDeps.length; k++) {
                if (ganttChart.addDependencyFromJSON(ganttAux.unAttachedDeps[k]) == null) {
                    stillUnattached.push(ganttAux.unAttachedDeps[k]);
                }
            }

            ganttAux.unAttachedDeps = stillUnattached;
        },
        notificationRelease: BX.debounce(function () {
            query.deleteAll();
            query.add('task.notification.throttleRelease');
            query.execute();
        }, 1000 * 60)
    };

	BX.Tasks.GanttActions.defaultPresetId = '<?=$arResult['DEFAULT_PRESET_KEY']?>';

    var query = new BX.Tasks.Util.Query({
        url: '/bitrix/components/bitrix/tasks.task.gantt/ajax.php'
    });

    query.bindEvent('executed', function (result)
	{
        if (!result.success || !result.data)
        {
            return showAjaxErrorPopup(result.clientProcessErrors, result.serverProcessErrors);
        }

        for (var k in result.data)
        {
            if (!result.data[k].SUCCESS && result.data[k].OPERATION !== 'task.dependence.add')
            {
                return showAjaxErrorPopup(result.data[k].ERRORS);
            }

            try
			{
                if (result.data[k].OPERATION == 'task.update')
                {
                    var shifted = result.data[k].RESULT.OPERATION_RESULT.SHIFT_RESULT;

                    var lastDraggedTask = result.data[k].ARGUMENTS.id;

                    for (var taskId in shifted)
                    {
                        if (parseInt(taskId) == parseInt(lastDraggedTask))
                        {
                            continue; // do not move the main task, it will be very annoying on slow connections
                        }

                        var task = ganttChart.getTaskById(taskId);
                        if (task)
                        {
                            var s = shifted[task.id].START_DATE_PLAN_STRUCT;
                            var e = shifted[task.id].END_DATE_PLAN_STRUCT;

                            // backward correction
                            ganttChart.updateTask(task.id, {
                                dateStart: new Date(s.YEAR, s.MONTH - 1, s.DAY, s.HOUR, s.MINUTE, s.SECOND),
                                dateEnd: new Date(e.YEAR, e.MONTH - 1, e.DAY, e.HOUR, e.MINUTE, e.SECOND)
                            });
                        }
                    }
                }
                else if (result.data[k].OPERATION == 'task.dependence.add')
                {
                    if (!result.data[k].SUCCESS)
                    {
                        var from = result.data[k].ARGUMENTS.taskIdFrom;
                        var to = result.data[k].ARGUMENTS.taskIdTo;

                        var dep = ganttChart.getDependency(from, to);
                        if (dep !== null)
                        {
                            ganttChart.removeDependency(dep);

                            var messageDesc = [];
                            var trialExpired = false;

                            for (var m in result.data[k].ERRORS)
                            {
                                var error = result.data[k].ERRORS[m];

                                if (error.TYPE != 'FATAL')
                                {
                                    continue;
                                }

                                if (error.CODE == 'ACTION_FAILED_REASON')
                                {
                                    messageDesc.push(error.MESSAGE);
                                }

                                if (error.CODE == 'TRIAL_EXPIRED')
                                {
                                    trialExpired = true;
                                    break;
                                }
                            }

                            if (trialExpired)
                            {
								BX.UI.InfoHelper.show('limit_tasks_gantt');
                            }
                            else
							{
                                if (messageDesc.length > 0)
                                {
                                    messageDesc = ': ' + messageDesc.join(', ').toLowerCase();
                                }

                                showAjaxErrorPopup(BX.message('TASKS_CANNOT_ADD_DEPENDENCY') + messageDesc);
                            }
                        }
                    }
                }
            }
            catch (e) {
                BX.debug('operation failed');
                BX.debug(result.data[k]);
            }
        }

    });

    BX.ready(function () {
        ganttChart = new BX.GanttChart(
            BX("gantt-container"),
			<?php $ts = time() + CTimeZone::GetOffset(); ?>
            new Date(
				<?php echo (int)date("Y", $ts); ?>,
				<?php echo (int)(date("n", $ts) - 1); ?>,
				<?php echo (int)date("j", $ts); ?>,
				<?php echo (int)date("G", $ts); ?>,
				<?php echo (int)date("i", $ts); ?>,
				<?php echo (int)date("s", $ts); ?>
            ),
            {
                disableItemNameClickHandler: true,
                disableDetailClickHandler: true,
                datetimeFormat: BX.message("FORMAT_DATETIME"),
                dateFormat: BX.message("FORMAT_DATE"),
                userProfileUrl: "<?php echo CUtil::JSEscape($arParams["PATH_TO_USER_PROFILE"])?>",
				<?php $options = CUserOptions::GetOption("tasks", "gantt", array("gutter_offset" => 300));?>
                gutterOffset: <?php echo intval($options["gutter_offset"])?>,

                zoomLevel: "<?=$arParams['VIEW_STATE']['VIEW_PARAMETERS']['ZOOM']?>",
				weekEnds: <?=CUtil::PhpToJSObject($weekEnds, false, false, true)?>,
                holidays: <?=CUtil::PhpToJSObject($holidays, false, false, true)?>,
                firstWeekDay: <?=intval($weekStart)?>,
                worktime: "<?=$hours?>",
                canDragTasks: <?= CUtil::PhpToJSObject($canDragTasks)?>,
                oneGroupMode: <?=($currentGroupId > 0 ? "true" : "false")?>,
                treeMode: <?
				if ($arParams['VIEW_STATE']['SUBMODES']['VIEW_SUBMODE_WITH_SUBTASKS']['SELECTED'] === 'Y')
					echo 'true';
				else
					echo 'false';
				?>,
                events: {
                    onGutterResize: function (gutterOffset) {
                        BX.userOptions.save('tasks', 'gantt', 'gutter_offset', gutterOffset);
                    },
                    onProjectOpen: function (project) {
                        BX.userOptions.save('tasks', 'opened_projects', project.id, project.opened);
                    },
                    onTaskOpen: function (task, callback)
					{
                        if (task.opened && task.hasChildren && task.childTasks.length == 0)
                        {
                            var data = {
                                sessid: BX.message("bitrix_sessid"),
                                id: task.id,
                                filter: arFilter,
                                order: arOrder,
                                path_to_user: BX.message("TASKS_PATH_TO_USER_PROFILE"),
                                path_to_task: BX.message("TASKS_PATH_TO_TASK"),
                                type: "json",
                                bGannt: true,
                                mode: "load",
                                DISABLE_IFRAME_POPUP: true
                            };

                            var ganttObject = this;

                            BX.ajax({
                                method: "POST",
                                dataType: "json",
                                url: tasksListAjaxUrl,
                                data: data,
                                processData: true,
                                onsuccess: BX.delegate(function(data)
								{
									for (var i = 0, count = data.length; i < count; i++)
									{
										__RenewMenuItems(data[i]);
									}

									ganttObject.addTasksFromJSON(data);
									callback();

									// try to add unAttached
									ganttAux.tryReattachUnattached();

									// add dependences here...
									for (i = 0; i < data.length; i++)
									{
										ganttAux.tryAttachDeps(data[i].links, true);
									}
                                }, this)
                            });
                        }
                        else
						{
                            callback();
                        }
                    },
                    onTaskChange: function(updatedTasks) {
                        query.deleteAll();
                        for (var i = 0; i < updatedTasks.length; i++)
						{
                            if (updatedTasks[i].changes.length)
							{
                                var delta = {};

                                if (BX.util.in_array("dateDeadline", updatedTasks[i].changes))
								{
                                    delta['DEADLINE'] = tasksFormatDate(updatedTasks[i].dateDeadline);
                                }
                                if (BX.util.in_array("dateStart", updatedTasks[i].changes))
								{
                                    delta['START_DATE_PLAN'] = tasksFormatDate(updatedTasks[i].dateStart);
									if (updatedTasks[i].dateEnd)
									{
										delta['END_DATE_PLAN'] = tasksFormatDate(updatedTasks[i].dateEnd);
									}
                                }
                                if (BX.util.in_array("dateEnd", updatedTasks[i].changes))
								{
                                    delta['END_DATE_PLAN'] = tasksFormatDate(updatedTasks[i].dateEnd);
									if (updatedTasks[i].dateStart)
									{
										delta['START_DATE_PLAN'] = tasksFormatDate(updatedTasks[i].dateStart);
									}
                                }

                                query.add(
									'task.update',
									{
										id: updatedTasks[i].task.id,
										data: delta,
										parameters: {
											RETURN_OPERATION_RESULT_DATA: true,
											THROTTLE_MESSAGES: true
										}
									},
									{
                                    	code: 'task_update'
                                	}
								);
                            }
                        }
                        query.execute();
                        ganttAux.notificationRelease();
                    },
                    onTaskMove: function (sourceId, targetId, before, newProjectId, newParentId) {
                        query.deleteAll();

                        var data = {
                            sourceId: sourceId,
                            targetId: targetId,
                            before: before,
                            currentGroupId: <?=$currentGroupId?>
                        };

                        if (newProjectId !== null) {
                            data.newGroupId = newProjectId;
                        }

                        if (newParentId !== null) {
                            data.newParentId = newParentId;
                        }

                        query.add('task.sorting.move', {data: data}, {code: 'task_move'});
                        query.execute();
                    },
                    onDependencyAdd: function (dep) {
                        if (dep !== null && dep.from && dep.to && dep.type >= 0) {
                            query.deleteAll();
                            query.add('task.dependence.add', {
                                taskIdFrom: dep.from,
                                taskIdTo: dep.to,
                                linkType: dep.type
                            });
                            query.execute();
                        }
                    },
                    onDependencyDelete: function (dep) {
                        if (dep !== null && dep.from && dep.to) {
                            query.deleteAll();
                            query.add('task.dependence.delete', {taskIdFrom: dep.from, taskIdTo: dep.to});
                            query.execute();
                        }
                    },
                    onZoomChange: function (zoomLevel) {
                        query.deleteAll();
                        query.add('this.setviewstate', {
                            state: {
                                VIEW_PARAMETERS: {
                                    ZOOM: zoomLevel
                                }
                            }
                        });
                        query.execute();
                    }
                }
            }
        );

		<?// hellish hack, sorry for that?>
        window.COMPANY_WORKTIME = {
            h: <?=intval($arResult['COMPANY_WORKTIME']['END']['H'])?>,
            m: <?=intval($arResult['COMPANY_WORKTIME']['END']['M'])?>};

        var projects = [
			<? $i = 0?>
			<? foreach((array)$arResult["GROUPS"] as $arGroup):?>
			<? $i++ ?>
            {
                id: <?=$arGroup["ID"]?>,
                name: "<?=CUtil::JSEscape($arGroup["NAME"])?>",
                opened: <?=CUtil::PhpToJSObject($arGroup["EXPANDED"])?>,
                canCreateTasks: <?=CUtil::PhpToJSObject($arGroup["CAN_CREATE_TASKS"])?>,
                canEditTasks: <?=CUtil::PhpToJSObject($arGroup["CAN_EDIT_TASKS"])?>
            }<? if ($i != sizeof($arResult["GROUPS"])):?>,<?endif?>
			<? endforeach?>
        ];
        ganttChart.addProjectsFromJSON(projects);

        var tasks = [
			<?php
			$i = 0;
			foreach($arResult["LIST"] as $arTask)
			{
			$i++;
			tasksRenderJSON(
				$arTask, $arResult["SUB_TASK_COUNTERS"][$arTask["ID"]],
				$arPaths, false, true, false, $arParams["NAME_TEMPLATE"], array(), false, array(
					'DISABLE_IFRAME_POPUP' => true,
					'USER_ID'              => $arParams['USER_ID']
				)
			);

			if ($i != sizeof($arResult["LIST"]))
			{
			?>,<?php
			}
			}
			?>
        ];

        for (var i = 0, count = tasks.length; i < count; i++) {
            __RenewMenuItems(tasks[i]);
        }

        ganttChart.addTasksFromJSON(tasks);

		<?
		$deps = array();
		foreach ($arResult["TASKS_LINKS"] as $arTasksLinks)
		{
			if (is_array($arTasksLinks) && !empty($arTasksLinks))
			{
				foreach ($arTasksLinks as $link)
				{
					$deps[] = array('from' => intval($link['DEPENDS_ON_ID']), 'to' => intval($link['TASK_ID']), 'type' => intval($link['TYPE']));
				}
			}
		}
		?>
        var deps = <?=CUtil::PhpToJSObject($deps, false, false, true)?>;
        ganttAux.tryAttachDeps(deps, true);

        ganttChart.draw();
    });

    // :(
    BX.addCustomEvent(window, 'tasksTaskEvent', function (eventType, params) {

        if (BX.type.isNotEmptyString(eventType)) {
            var cbAction = eventType.toString().toUpperCase();

            params = params || {};
            params.task = params.task || {};

            var taskId = parseInt(params.task.ID);

            if (cbAction == 'DELETE' && !isNaN(taskId) && taskId) {
                onPopupTaskDeleted(params.task.ID);
            }
            else if (cbAction == 'ADD') {
                if (params.taskUgly) {
                    onPopupTaskAdded(params.taskUgly);
                }
            }
            else if (cbAction == 'UPDATE') {
                if (params.taskUgly) {
                    onPopupTaskChanged(params.taskUgly);
                }
            }
        }
    });

</script>



<?php
if ($isBitrix24Template)
{
    $this->SetViewTarget('inside_pagetitle');
}
?>
<?php $APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.header',
	'',
	array(
		'FILTER_ID' => $arParams["FILTER_ID"],
		'GRID_ID' => $arParams["GRID_ID"],
		'FILTER' => $arResult['FILTER'],
		'PRESETS' => $arResult['PRESETS'],

		'SHOW_QUICK_FORM' => 'Y',
		'GET_LIST_PARAMS' => $arResult['GET_LIST_PARAMS'],
		'COMPANY_WORKTIME' => $arResult['COMPANY_WORKTIME'],
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
		'GANTT_MODE' => true,
		'PROJECT_VIEW' => $arParams['PROJECT_VIEW'],

		'USER_ID' => $arParams['USER_ID'],
		'GROUP_ID' => $arParams['GROUP_ID'],

		'MARK_ACTIVE_ROLE' => $arParams['MARK_ACTIVE_ROLE'],
		'MARK_SECTION_ALL' => $arParams['MARK_SECTION_ALL'],
		'MARK_SPECIAL_PRESET' => $arParams['MARK_SPECIAL_PRESET'],
		'MARK_SECTION_PROJECTS' => $arParams['MARK_SECTION_PROJECTS'],


		'PATH_TO_USER_TASKS' => $arParams['PATH_TO_USER_TASKS'],
		'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_USER_TASKS_TASK'],
		'PATH_TO_USER_TASKS_VIEW' => $arParams['PATH_TO_USER_TASKS_VIEW'],
		'PATH_TO_USER_TASKS_REPORT' => $arParams['PATH_TO_USER_TASKS_REPORT'],
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],

		'PATH_TO_GROUP' => $arParams['PATH_TO_GROUP'],
		'PATH_TO_GROUP_TASKS' => $arParams['PATH_TO_GROUP_TASKS'],
		'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'],
		'PATH_TO_GROUP_TASKS_VIEW' => $arParams['PATH_TO_GROUP_TASKS_VIEW'],
		'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'],

		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
		'PATH_TO_MESSAGES_CHAT' => $arParams['PATH_TO_MESSAGES_CHAT'],
		'PATH_TO_VIDEO_CALL' => $arParams['PATH_TO_VIDEO_CALL'],
		'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT'],

		'USE_EXPORT' => 'Y',
		'USE_GROUP_BY_SUBTASKS' => 'Y',
		'USE_GROUP_BY_GROUPS' => $arParams['NEED_GROUP_BY_GROUPS'] === 'Y' ? 'Y' : 'N',
		'GROUP_BY_PROJECT' => $arResult['GROUP_BY_PROJECT'],
		'SHOW_USER_SORT' => 'Y',
		'SORT_FIELD'=>$arParams['SORT_FIELD'],
		'SORT_FIELD_DIR'=>$arParams['SORT_FIELD_DIR'],
		'USE_LIVE_SEARCH' => 'N',
		'SHOW_SECTION_TEMPLATES'=>$arParams['GROUP_ID'] > 0 ? 'N' : 'Y',
		'DEFAULT_ROLEID'=>$arParams['DEFAULT_ROLEID'],
		'USE_AJAX_ROLE_FILTER'=>'Y'
	),
	$component,
	array('HIDE_ICONS' => true)
); ?>

<?php
if (is_array($arResult['ERROR']['FATAL']) && !empty($arResult['ERROR']['FATAL'])):
	foreach ($arResult['ERROR']['FATAL'] as $error):
		echo ShowError($error['MESSAGE']);
	endforeach;
	return;
endif
?>

<? if (is_array($arResult['ERROR']['WARNING'])): ?>
	<? foreach ($arResult['ERROR']['WARNING'] as $error): ?>
		<?= ShowError($error['MESSAGE']) ?>
	<? endforeach ?>
<? endif ?>
<?php
if ($isBitrix24Template)
{
    $this->EndViewTarget();
}
?>


<div id="gantt-container" class="tasks-gantt"></div>

<br/>
<?php

//region Navigation
ob_start();
$APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.pagenavigation',
	'',
	[
		'PAGE_NUM' => $arResult['CURRENT_PAGE'],
		'ENABLE_NEXT_PAGE' => $arResult['ENABLE_NEXT_PAGE'],
		'URL' => $APPLICATION->GetCurPageParam('F_STATE=sVg0', [ 'F_STATE' ]),
	],
	$component,
	array('HIDE_ICONS' => 'Y')
);
$navigationHtml = ob_get_contents();
ob_end_clean();
//endregion
?>

<?= $navigationHtml; ?>

<script>
	BX.message({
		TASKS_DELETE_SUCCESS: '<?=GetMessage('TASKS_DELETE_SUCCESS')?>'
	});
</script>