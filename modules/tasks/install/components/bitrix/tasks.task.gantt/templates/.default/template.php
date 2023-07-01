<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Slider\Exception\SliderException;
use Bitrix\Tasks\UI\ScopeDictionary;
use Bitrix\Tasks\Slider\Factory\SliderFactory;

$isIFrame = isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y';
$isBitrix24Template = (SITE_TEMPLATE_ID === 'bitrix24');

Loc::loadMessages(__FILE__);

\Bitrix\Main\UI\Extension::load([
	'ui.fonts.opensans',
	'ui.fonts.opensans',
	'popup',
	'tooltip',
	'gantt',
	'task_info_popup',
	'task-popups',
	'CJSTask',
	'ui.counter',
]);

$APPLICATION->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/script.js");
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/gantt-view.js");
$APPLICATION->AddHeadScript("/bitrix/js/tasks/task-iframe-popup.js");

$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");
$APPLICATION->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass . " " : "") . "page-one-column");

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
	"PATH_TO_TASKS_TASK" => (isset($arParams['GROUP_ID']) && $arParams['GROUP_ID'] > 0) ? $arParams['PATH_TO_GROUP_TASKS_TASK'] : $arParams["PATH_TO_USER_TASKS_TASK"],
	"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"] ?? null
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
        TASKS_PATH_TO_TASK: "<?php echo CUtil::JSEscape((isset($arParams['GROUP_ID']) && $arParams['GROUP_ID'] > 0) ? $arParams["PATH_TO_GROUP_TASKS_TASK"] : $arParams["PATH_TO_USER_TASKS_TASK"])?>",
        TASKS_CANNOT_ADD_DEPENDENCY: "<?=GetMessage("TASKS_CANNOT_ADD_DEPENDENCY")?>",
		TASKS_CLOSE_PAGE_CONFIRM: '<?=GetMessageJS('TASKS_CLOSE_PAGE_CONFIRM')?>'
    });

	<?
		$filter = $arResult["GET_LIST_PARAMS"]["legacyFilter"];
		unset($filter['ONLY_ROOT_TASKS']);
	?>
    var arFilter = <?php echo CUtil::PhpToJSObject($filter)?>;
    var arOrder = <?php echo CUtil::PhpToJSObject($arResult["GET_LIST_PARAMS"]["order"])?>;
    var tasksListAjaxUrl = "/bitrix/components/bitrix/tasks.list/ajax.php?SITE_ID=<?php echo SITE_ID?><?=(isset($arParams['GROUP_ID']) && $arParams['GROUP_ID'] > 0) ? "&GROUP_ID=" . $arParams["GROUP_ID"] : ""?>";
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
			BX.ajax.runComponentAction('bitrix:tasks.task.gantt', 'notificationThrottleRelease', {
				mode: 'class'
			}).then(
				function(response)
				{

				}.bind(this),
				function(response)
				{

				}.bind(this)
			);
        }, 1000 * 60)
    };

	BX.Tasks.GanttActions.defaultPresetId = '<?=$arResult['DEFAULT_PRESET_KEY']?>';

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
				isDragged: [],
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
                        for (var i = 0; i < updatedTasks.length; i++)
						{
							if (!updatedTasks[i].changes.length)
							{
								continue;
							}

							var delta = {};

							if (BX.util.in_array('dateDeadline', updatedTasks[i].changes))
							{
								delta['DEADLINE'] = tasksFormatDate(updatedTasks[i].dateDeadline);
							}
							if (BX.util.in_array('dateStart', updatedTasks[i].changes))
							{
								delta['START_DATE_PLAN'] = tasksFormatDate(updatedTasks[i].dateStart);
								if (updatedTasks[i].dateEnd)
								{
									delta['END_DATE_PLAN'] = tasksFormatDate(updatedTasks[i].dateEnd);
								}
							}
							if (BX.util.in_array('dateEnd', updatedTasks[i].changes))
							{
								delta['END_DATE_PLAN'] = tasksFormatDate(updatedTasks[i].dateEnd);
								if (updatedTasks[i].dateStart)
								{
									delta['START_DATE_PLAN'] = tasksFormatDate(updatedTasks[i].dateStart);
								}
							}

							BX.ajax.runComponentAction('bitrix:tasks.task', 'legacyUpdate', {
								mode: 'class',
								data: {
									taskId: updatedTasks[i].task.id,
									data: delta,
									parameters: {
										RETURN_OPERATION_RESULT_DATA: true,
										THROTTLE_MESSAGES: true
									}
								}
							}).then(function(response) {
								if (
									!response.status
									|| response.status !== 'success'
								)
								{
									BX.reload();
									return;
								}

								void BX.ajax.runAction('tasks.analytics.hit', {
									analyticsLabel: {
										gantt: 'Y',
										action: 'taskChangeDates',
										taskId: response.data.ID
									}
								});

								var shifted = response.data.OPERATION_RESULT.SHIFT_RESULT;
								this.settings.isDragged.push(parseInt(response.data.ID));

								for (var taskId in shifted)
								{
									if (this.settings.isDragged.indexOf(parseInt(taskId)) !== -1)
									{
										continue;
									}

									var task = ganttChart.getTaskById(taskId);
									if (task)
									{
										var s = shifted[taskId].START_DATE_PLAN_STRUCT;
										var e = shifted[taskId].END_DATE_PLAN_STRUCT;

										ganttChart.updateTask(taskId, {
											dateStart: new Date(s.YEAR, s.MONTH - 1, s.DAY, s.HOUR, s.MINUTE, s.SECOND),
											dateEnd: new Date(e.YEAR, e.MONTH - 1, e.DAY, e.HOUR, e.MINUTE, e.SECOND)
										});

										this.settings.isDragged.push(parseInt(taskId));
									}
								}
							}.bind(this));
                        }
                        ganttAux.notificationRelease();
                    },
					onTaskUpdate: function (task) {

					},
                    onTaskMove: function (sourceId, targetId, before, newProjectId, newParentId) {
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

						BX.ajax.runComponentAction('bitrix:tasks.task.list', 'sortTask', {
							mode: 'class',
							data: {
								data: data
							}
						}).then(
							function(response)
							{
								if (
									!response.status
									|| response.status !== 'success'
								)
								{
									BX.reload();
								}
							}.bind(this),
							function(response)
							{

							}.bind(this)
						);
                    },
                    onDependencyAdd: function (dep) {
						if (
							dep === null
							|| !dep.from
							|| !dep.to
						)
						{
							return;
						}

						BX.ajax.runComponentAction('bitrix:tasks.task.gantt', 'addDependence', {
							mode: 'class',
							data: {
								taskFrom: dep.from,
								taskTo: dep.to,
								linkType: dep.type
							}
						}).then(
							function(response) {
								if (
									!response.status
									|| response.status !== 'success'
								)
								{
									BX.reload();
									return;
								}

								void BX.ajax.runAction('tasks.analytics.hit', {
									analyticsLabel: {
										gantt: 'Y',
										action: 'taskAddDependence',
										taskFrom: dep.from,
										taskTo: dep.to,
										type: dep.type
									}
								});
							},
							function(response) {
								if (response.errors.length < 1)
								{
									return;
								}
								if (response.errors[0].code === 'TRIAL_EXPIRED')
								{
									if (dep !== null)
									{
										ganttChart.removeDependency(dep);
									}
									BX.UI.InfoHelper.show('limit_tasks_gantt');
								}
								else
								{
									BX.Tasks.alert(response.errors, function() { BX.reload(); });
								}
							}
						);
                    },
                    onDependencyDelete: function (dep) {
						if (
							dep === null
							|| !dep.from
							|| !dep.to
						)
						{
							return;
						}

						BX.ajax.runComponentAction('bitrix:tasks.task.gantt', 'deleteDependence', {
							mode: 'class',
							data: {
								taskFrom: dep.from,
								taskTo: dep.to
							}
						}).then(function(response) {
							if (
								!response.status
								|| response.status !== 'success'
							)
							{
								BX.reload();
								return;
							}

							void BX.ajax.runAction('tasks.analytics.hit', {
								analyticsLabel: {
									gantt: 'Y',
									action: 'taskDeleteDependence',
									taskFrom: dep.from,
									taskTo: dep.to,
									type: dep.type
								}
							});
						});
                    },
                    onZoomChange: function (zoomLevel) {
						BX.ajax.runComponentAction('bitrix:tasks.task.gantt', 'setViewState', {
							mode: 'class',
							data: {
								state: {
									VIEW_PARAMETERS: {
										ZOOM: zoomLevel
									}
								}
							}
						}).then(
							function(response)
							{

							}.bind(this),
							function(response)
							{

							}.bind(this)
						);
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
				$subCounter = $arResult["SUB_TASK_COUNTERS"][$arTask["ID"]] ?? null;
				tasksRenderJSON(
					$arTask, $subCounter,
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

		void BX.ajax.runAction('tasks.analytics.hit', {
			analyticsLabel: {
				gantt: 'Y',
				action: 'view'
			}
		});
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
<?php
$APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.header',
	'',
	[
		'FILTER_ID' => $arParams['FILTER_ID'] ?? null,
		'GRID_ID' => $arParams['GRID_ID'] ?? null,
		'FILTER' => $arResult['FILTER'] ?? null,
		'PRESETS' => $arResult['PRESETS'] ?? null,
		'GET_LIST_PARAMS' => $arResult['GET_LIST_PARAMS'] ?? null,
		'COMPANY_WORKTIME' => $arResult['COMPANY_WORKTIME'] ?? null,
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'] ?? null,
		'PROJECT_VIEW' => $arParams['PROJECT_VIEW'] ?? null,
		'USER_ID' => $arParams['USER_ID'] ?? null,
		'GROUP_ID' => $arParams['GROUP_ID'] ?? null,
		'MARK_ACTIVE_ROLE' => $arParams['MARK_ACTIVE_ROLE'] ?? null,
		'MARK_SECTION_ALL' => $arParams['MARK_SECTION_ALL'] ?? null,
		'MARK_SPECIAL_PRESET' => $arParams['MARK_SPECIAL_PRESET'] ?? null,
		'MARK_SECTION_PROJECTS' => $arParams['MARK_SECTION_PROJECTS'] ?? null,
		'PATH_TO_USER_TASKS' => $arParams['PATH_TO_USER_TASKS'] ?? null,
		'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_USER_TASKS_TASK'] ?? null,
		'PATH_TO_USER_TASKS_VIEW' => $arParams['PATH_TO_USER_TASKS_VIEW'] ?? null,
		'PATH_TO_USER_TASKS_REPORT' => $arParams['PATH_TO_USER_TASKS_REPORT'] ?? null,
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'] ?? null,
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'] ?? null,
		'PATH_TO_GROUP' => $arParams['PATH_TO_GROUP'] ?? null,
		'PATH_TO_GROUP_TASKS' => $arParams['PATH_TO_GROUP_TASKS'] ?? null,
		'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'] ?? null,
		'PATH_TO_GROUP_TASKS_VIEW' => $arParams['PATH_TO_GROUP_TASKS_VIEW'] ?? null,
		'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'] ?? null,
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'] ?? null,
		'PATH_TO_MESSAGES_CHAT' => $arParams['PATH_TO_MESSAGES_CHAT'] ?? null,
		'PATH_TO_VIDEO_CALL' => $arParams['PATH_TO_VIDEO_CALL'] ?? null,
		'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT'] ?? null,
		'SHOW_QUICK_FORM' => 'Y',
		'USE_EXPORT' => 'Y',
		'USE_GROUP_BY_SUBTASKS' => 'Y',
		'USE_GROUP_BY_GROUPS' => ((isset($arParams['NEED_GROUP_BY_GROUPS']) && $arParams['NEED_GROUP_BY_GROUPS'] === 'Y') ? 'Y' : 'N'),
		'GROUP_BY_PROJECT' => $arResult['GROUP_BY_PROJECT'] ?? null,
		'SHOW_USER_SORT' => 'Y',
		'SORT_FIELD' => $arParams['SORT_FIELD'] ?? null,
		'SORT_FIELD_DIR' => $arParams['SORT_FIELD_DIR'] ?? null,
		'USE_LIVE_SEARCH' => 'N',
		'SHOW_SECTION_TEMPLATES' => ((isset($arParams['GROUP_ID']) && $arParams['GROUP_ID'] > 0) ? 'N' : 'Y'),
		'DEFAULT_ROLEID' => $arParams['DEFAULT_ROLEID'] ?? null,
		'USE_AJAX_ROLE_FILTER' => 'Y',
		'SCOPE' => ScopeDictionary::SCOPE_TASKS_GANTT,
	],
	$component,
	['HIDE_ICONS' => true]
);
?>

<?php
if (
	isset($arResult['ERROR']['FATAL'])
	&& is_array($arResult['ERROR']['FATAL'])
	&& !empty($arResult['ERROR']['FATAL'])
):
	foreach ($arResult['ERROR']['FATAL'] as $error):
		echo ShowError($error['MESSAGE']);
	endforeach;
	return;
endif
?>

<? if (
	isset($arResult['ERROR']['WARNING'])
	&& is_array($arResult['ERROR']['WARNING'])
): ?>
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
		'PAGE_NUM' => $arResult['CURRENT_PAGE'] ?? null,
		'ENABLE_NEXT_PAGE' => $arResult['ENABLE_NEXT_PAGE'] ?? null,
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