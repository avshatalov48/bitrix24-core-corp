<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

$this->IncludeLangFile('template.php');

CUtil::InitJSCore(array('popup', 'tooltip', 'gantt', 'tasks_util_query', 'task_info_popup', 'task-popups'));

$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/script.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/gantt-view.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/tasks/task-iframe-popup.js");

$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");


$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."page-one-column");

$GLOBALS["APPLICATION"]->IncludeComponent(
	'bitrix:main.calendar',
	'',
	array(
		'SILENT' => 'Y',
	),
	null,
	array('HIDE_ICONS' => 'Y')
);

$arPaths = array(
	"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
	"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"]
);

$APPLICATION->IncludeComponent(
	"bitrix:tasks.iframe.popup",
	".default",
	array(
//		"ON_BEFORE_HIDE" => "onBeforeHide",
//		"ON_AFTER_HIDE" => "onAfterHide",
//		"ON_BEFORE_SHOW" => "onBeforeShow",
//		"ON_AFTER_SHOW" => "onAfterShow",


//		"ON_TASK_ADDED" => "onPopupTaskAdded",
//		'ON_TASK_ADDED_MULTIPLE' => 'onPopupTaskAdded',
//		"ON_TASK_CHANGED" => "onPopupTaskChanged",
//		"ON_TASK_DELETED" => "onPopupTaskDeleted"
	),
	null,
	array("HIDE_ICONS" => "Y")
);

$cs = \Bitrix\Tasks\UI::translateCalendarSettings($arResult['CALENDAR_SETTINGS']);

$holidays = $cs['HOLIDAYS'];
$hours = $cs['HOURS'];
$weekEnds = $cs['WEEK_END'];
$weekStart = $cs['WEEK_START'];

if(CModule::IncludeModule('bitrix24'))
{
	CBitrix24::initLicenseInfoPopupJS("tasks");
}

$trialMessage = preg_replace("#(\r\n|\n)#", "<br />", GetMessageJS('TASKS_LIST_TRIAL_EXPIRED_TEXT'));

$currentGroupId = $arResult["TASK_TYPE"] === "group" ? intval($arParams["GROUP_ID"]) : 0;
$canDragTasks = false;

if ($currentGroupId)
{
	$canDragTasks = $arResult["SORTF"] === "SORTING" &&
		\CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $currentGroupId, "tasks", "edit_tasks");
}
else
{
	$canDragTasks = $arResult["SORTF"] === "SORTING" && $arParams["USER_ID"] == $USER->GetID();
}


?>
<?include('process.php');?>

<script type="text/javascript">
BX.message({
	TASKS_PATH_TO_USER_PROFILE : "<?php echo CUtil::JSEscape($arParams["PATH_TO_USER_PROFILE"])?>",
	TASKS_PATH_TO_TASK : "<?php echo CUtil::JSEscape($arParams["PATH_TO_TASKS_TASK"])?>",
	TASKS_CANNOT_ADD_DEPENDENCY: "<?=GetMessage("TASKS_CANNOT_ADD_DEPENDENCY")?>"
});

	// tmp BEGIN
	/*
	window.getUTCDate = function(oldDate){

		var date = BX.clone(oldDate);

		return date.getUTCDate()+'.'+date.getUTCMonth()+'.'+date.getUTCFullYear()+' '+date.getUTCHours()+':'+date.getUTCMinutes()+':'+date.getUTCSeconds();
	}
	window.getTaskDates = function(taskId){
		return getUTCDate(ganttChart.tasks[taskId].dateStart)+' - '+getUTCDate(ganttChart.tasks[taskId].dateEnd);
	}
	window.getTimeStamp = function(oldDate){
		var date = BX.clone(oldDate);

		return (oldDate.getTime() / 1000);
	}

	window.getGmtInfo = function(oldDate){
		var date = BX.clone(oldDate);

		return getUTCDate(date)+' ('+getTimeStamp(date)+')';
	}
	*/
	// tmp END

	var TaskGanttFilterPopup = {
		popup : null,

		init : function(bindElement)
		{
			if (this.popup != null)
				return;

			this.popup = new BX.PopupWindow("task-gantt-filter", bindElement, {
				content : BX("task-gantt-filter"),
				offsetLeft : -263 + bindElement.offsetWidth - 10,
				offsetTop : 3,
				className : "task-filter-popup-window",
				zIndex: -2,
				events: {
					onPopupClose: function(popupWindow) {
						if (tasksTagsPopUp != null)
						{
							tasksTagsPopUp.popupWindow.close();
						}
					}
				}
			});

			BX.bind(BX("task-gantt-filter"), "click", BX.delegate(this.onFilterSwitch, this));
		},

		show : function(bindElement)
		{
			if (!this.popup)
				this.init(bindElement);

			if (BX.hasClass(bindElement, "task-title-button-filter-pressed"))
			{
				this.popup.close();
				BX.removeClass(bindElement, "task-title-button-filter-pressed");
				this.adjustGanttHeight();
			}
			else
			{
				this.popup.show();
				BX.addClass(bindElement, "task-title-button-filter-pressed");
				this.adjustGanttHeight();
			}
		},
		
		adjustGanttHeight : function()
		{
			var ganttContainer = BX("gantt-container", true);
			var ganttHeight = ganttContainer.offsetHeight - (parseInt(ganttContainer.style.paddingBottom) || 0);
			var filterHeight = this.popup ? this.popup.popupContainer.offsetHeight : 0;

			if (filterHeight > ganttHeight)
				BX("gantt-container", true).style.paddingBottom = filterHeight - ganttHeight + "px";
			else
				BX("gantt-container", true).style.paddingBottom = "0px";
				
		},
		
		onFilterSwitch : function(event)
		{
			event = event || window.event;
			var target = event.target || event.srcElement;
			if (BX.hasClass(target, "task-filter-mode-selected"))
				this.adjustGanttHeight();
		}
	};
	
	var arFilter = <?php echo CUtil::PhpToJSObject($arResult["FILTER"])?>;
	var arOrder = <?php echo CUtil::PhpToJSObject($arResult["ORDER"])?>;
	var tasksListAjaxUrl = "/bitrix/components/bitrix/tasks.list/ajax.php?SITE_ID=<?php echo SITE_ID?><?php echo $arResult["TASK_TYPE"] == "group" ? "&GROUP_ID=".$arParams["GROUP_ID"] : ""?>";
	var ajaxUrl = tasksListAjaxUrl;
	var tasksIFrameList = <?php echo CUtil::PhpToJSObject(array_keys($arResult["TASKS"]))?>;
	var ganttChart;

	var ganttAux = {
		unAttachedDeps: [],
		tryAttachDeps: function(deps)
		{
			if(typeof deps == 'undefined' || !BX.type.isArray(deps))
			{
				return;
			}
			for(var k = 0; k < deps.length; k++)
			{
				if(ganttChart.addDependencyFromJSON(deps[k]) == null)
				{
					ganttAux.unAttachedDeps.push(deps[k]);
				}
			}
		},
		tryReattachUnattached: function()
		{
			var stillUnattached = [];
			for(var k = 0; k < ganttAux.unAttachedDeps.length; k++)
			{
				if(ganttChart.addDependencyFromJSON(ganttAux.unAttachedDeps[k]) == null)
				{
					stillUnattached.push(ganttAux.unAttachedDeps[k]);
				}
			}

			ganttAux.unAttachedDeps = stillUnattached;
		},
		notificationRelease: BX.debounce(function(){
			query.deleteAll();
			query.add('task.notification.throttleRelease');
			query.execute();
		}, 1000 * 60)
	};

	var query = new BX.Tasks.Util.Query({
		url: '/bitrix/components/bitrix/tasks.task.list/ajax.php'
	});

	query.bindEvent('executed', function(result) {

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

					//console.dir('SHIFT>>>');
					for(var taskId in shifted)
					{
						if(parseInt(taskId) == parseInt(lastDraggedTask))
						{
							continue; // do not move the main task, it will be very annoying on slow connections
						}

						var task = ganttChart.getTaskById(taskId);
						if(task)
						{
							var s = shifted[task.id].START_DATE_PLAN_STRUCT;
							var e = shifted[task.id].END_DATE_PLAN_STRUCT;

							// backward correction
							ganttChart.updateTask(task.id, {
								dateStart: 	new Date(s.YEAR, s.MONTH - 1, s.DAY, s.HOUR, s.MINUTE, s.SECOND),
								dateEnd: 	new Date(e.YEAR, e.MONTH - 1, e.DAY, e.HOUR, e.MINUTE, e.SECOND)
							});
						}
					}
				}
				else if (result.data[k].OPERATION == 'task.dependence.add')
				{
					if (!result.data[k].SUCCESS)
					{
						var from = result.data[k].ARGUMENTS.taskidfrom;
						var to = result.data[k].ARGUMENTS.taskidto;

						var dep = ganttChart.getDependency(from, to);
						if (dep !== null)
						{
							ganttChart.removeDependency(dep);

							var messageDesc = [];
							var trialExpired = false;
							for (var m in result.data[k].ERRORS)
							{
								var error = result.data[k].ERRORS[m];

								if(error.TYPE != 'FATAL')
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

							if (trialExpired && typeof B24 != 'undefined' && typeof B24.licenseInfoPopup != 'undefined')
							{
								B24.licenseInfoPopup.show('popupTaskTrial', '<?=GetMessageJS('TASKS_LIST_TRIAL_EXPIRED_TITLE_V2')?>', '<?=$trialMessage?>');
							}
							else
							{
								if (messageDesc.length > 0)
								{
									messageDesc = ': '+messageDesc.join(', ').toLowerCase();
								}

								showAjaxErrorPopup(BX.message('TASKS_CANNOT_ADD_DEPENDENCY') + messageDesc);
							}
						}
					}
				}
			}
			catch (e)
			{
				BX.debug('operation failed');
				BX.debug(result.data[k]);
			}
		}

	});

	BX.ready(function() {
		ganttChart = new BX.GanttChart(
			BX("gantt-container"),
			<?php $ts = time() + CTimeZone::GetOffset(); ?>
			new Date(
				<?php echo (int) date("Y", $ts); ?>, 
				<?php echo (int) (date("n", $ts) - 1); ?>, 
				<?php echo (int) date("j", $ts); ?>, 
				<?php echo (int) date("G", $ts); ?>, 
				<?php echo (int) date("i", $ts); ?>, 
				<?php echo (int) date("s", $ts); ?>
			),
			{
				disableItemNameClickHandler: true,
				disableDetailClickHandler: true,
				datetimeFormat : BX.message("FORMAT_DATETIME"),
				dateFormat : BX.message("FORMAT_DATE"),
				userProfileUrl : "<?php echo CUtil::JSEscape($arParams["PATH_TO_USER_PROFILE"])?>",
				<?php $options =  CUserOptions::GetOption("tasks", "gantt", array("gutter_offset" => 300));?>
				gutterOffset : <?php echo intval($options["gutter_offset"])?>,

				zoomLevel: "<?=$arResult['VIEW_STATE']['VIEW_PARAMETERS']['ZOOM']?>",
				weekends: <?=CUtil::PhpToJSObject($weekEnds, false, false, true)?>,
				holidays: <?=CUtil::PhpToJSObject($holidays, false, false, true)?>,
				firstWeekDay: <?=intval($weekStart)?>,
				worktime: "<?=$hours?>",
				canDragTasks: <?= CUtil::PhpToJSObject($canDragTasks)?>,
				oneGroupMode: <?=($currentGroupId > 0 ? "true" : "false")?>,
				treeMode: <?
				if ($arResult['VIEW_STATE']['SUBMODES']['VIEW_SUBMODE_WITH_SUBTASKS']['SELECTED'] === 'Y')
					echo 'true';
				else
					echo 'false';
				?>,
				events : {
					onGutterResize : function(gutterOffset) {
						BX.userOptions.save('tasks', 'gantt', 'gutter_offset', gutterOffset);
					},
					onProjectOpen : function(project) {
						BX.userOptions.save('tasks', 'opened_projects', project.id, project.opened);
						TaskGanttFilterPopup.adjustGanttHeight();
					},
					onTaskOpen : function(task, callback) {

						if (task.opened && task.hasChildren && task.childTasks.length == 0)
						{
							var data = {
								sessid : BX.message("bitrix_sessid"),
								id : task.id,
								filter: arFilter,
								order: arOrder,
								path_to_user: BX.message("TASKS_PATH_TO_USER_PROFILE"),
								path_to_task: BX.message("TASKS_PATH_TO_TASK"),
								type: "json",
								bGannt : true,
								mode : "load",
								DISABLE_IFRAME_POPUP: true
							};
							
							var GanttObject = this;
							
							BX.ajax({
								"method": "POST",
								"dataType": "json",
								"url": tasksListAjaxUrl,
								"data":  data,
								"processData" : true,
								"onsuccess": (function() {
									var func = function(data) {
										for(var i = 0, count = data.length; i < count; i++)
										{
											__RenewMenuItems(data[i]);
										}

										GanttObject.addTasksFromJSON(data);
										callback();

										// try to add unAttached
										ganttAux.tryReattachUnattached();

										// add dependences here...
										for(var i = 0; i < data.length; i++)
										{
											ganttAux.tryAttachDeps(data[i].links, true);
										}
									};

									return func;
								})()
							});

						}
						else
						{
							callback();
						}
					},
					onTaskChange : function(updatedTasks) {

						query.deleteAll();

						for (var i = 0; i < updatedTasks.length; i++)
						{
							if (updatedTasks[i].changes.length)
							{
								var delta = {};
								if(BX.util.in_array("dateDeadline", updatedTasks[i].changes))
								{
									delta['DEADLINE'] = tasksFormatDate(updatedTasks[i].dateDeadline);
								}
								if (BX.util.in_array("dateStart", updatedTasks[i].changes))
								{
									delta['START_DATE_PLAN'] = tasksFormatDate(updatedTasks[i].dateStart);
								}
								if (BX.util.in_array("dateEnd", updatedTasks[i].changes))
								{
									delta['END_DATE_PLAN'] = tasksFormatDate(updatedTasks[i].dateEnd);
								}

								query.add('task.update', {
									id: updatedTasks[i].task.id,
									data: delta,
									parameters: {
										RETURN_OPERATION_RESULT_DATA: true,
										THROTTLE_MESSAGES: true
									}
								}, {
									code: 'task_update'
								});
							}
						}

						query.execute();

						ganttAux.notificationRelease();
					},
					onTaskMove: function(sourceId, targetId, before, newProjectId, newParentId) {
						//console.log("onTaskMove", sourceId, targetId, before, newProjectId, newParentId);
						query.deleteAll();

						var data = {
							sourceId: sourceId,
							targetId: targetId,
							before: before,
							currentGroupId : <?=$currentGroupId?>
						};

						if (newProjectId !== null)
						{
							data.newGroupId = newProjectId;
						}

						if (newParentId !== null)
						{
							data.newParentId = newParentId;
						}

						query.add('task.sorting.move', { data: data }, { code: 'task_move' });
						query.execute();
					},
					onDependencyAdd: function(dep) {
						if(dep !== null && dep.from && dep.to && dep.type >= 0)
						{
							query.deleteAll();
							query.add('task.dependence.add', {taskIdFrom: dep.from, taskIdTo: dep.to, linkType: dep.type});
							query.execute();
						}
					},
					onDependencyDelete: function(dep) {
						if(dep !== null && dep.from && dep.to)
						{
							query.deleteAll();
							query.add('task.dependence.delete', {taskIdFrom: dep.from, taskIdTo: dep.to});
							query.execute();
						}
					},
					onZoomChange: function(zoomLevel)
					{
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
		window.COMPANY_WORKTIME = {h: <?=intval($arResult['COMPANY_WORKTIME']['END']['H'])?>, m: <?=intval($arResult['COMPANY_WORKTIME']['END']['M'])?>};

		var projects = [
			<? $i = 0?>
			<? foreach($arResult["GROUPS"] as $arGroup):?>
				<? $i++ ?>
				{
					id : <?=$arGroup["ID"]?>,
					name : "<?=CUtil::JSEscape($arGroup["NAME"])?>",
					opened : <?=CUtil::PhpToJSObject($arGroup["EXPANDED"])?>,
					canCreateTasks: <?=CUtil::PhpToJSObject($arGroup["CAN_CREATE_TASKS"])?>,
					canEditTasks: <?=CUtil::PhpToJSObject($arGroup["CAN_EDIT_TASKS"])?>
				}<? if ($i != sizeof($arResult["GROUPS"])):?>,<?endif?>
			<? endforeach?>
		];
		ganttChart.addProjectsFromJSON(projects);

		var tasks = [
			<?php
			$i = 0;
			foreach($arResult["TASKS"] as $arTask)
			{
				$i++;
				tasksRenderJSON(
					$arTask, $arResult["CHILDREN_COUNT"]["PARENT_".$arTask["ID"]], 
					$arPaths, false, true, false, $arParams["NAME_TEMPLATE"], array(), false, array(
						'DISABLE_IFRAME_POPUP' => true
					)
				);

				if ($i != sizeof($arResult["TASKS"]))
				{
					?>,<?php
				}
			}
			?>
		];
		
		for(var i = 0, count = tasks.length; i < count; i++)
		{
			__RenewMenuItems(tasks[i]);
		}

		ganttChart.addTasksFromJSON(tasks);

		<?
		$deps = array();
		foreach($arResult["TASKS"] as $arTask)
		{
			if(is_array($arTask['LINKS']) && !empty($arTask['LINKS']))
			{
				foreach($arTask['LINKS'] as $link)
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
	BX.addCustomEvent(window, 'tasksTaskEvent', function(eventType, params){

		if(BX.type.isNotEmptyString(eventType))
		{
			var cbAction = eventType.toString().toUpperCase();

			params = params || {};
			params.task = params.task || {};

			var taskId = parseInt(params.task.ID);

			if (cbAction == 'DELETE' && !isNaN(taskId) && taskId)
			{
				onPopupTaskDeleted(params.task.ID);
			}
			else if(cbAction == 'ADD')
			{
				if(params.taskUgly)
				{
					onPopupTaskAdded(params.taskUgly);
				}
			}
			else if(cbAction == 'UPDATE')
			{
				if(params.taskUgly)
				{
					onPopupTaskChanged(params.taskUgly);
				}
			}
		}
	});

</script>
<?php $APPLICATION->ShowViewContent("task_menu"); ?>
<div id="gantt-container"></div>
<br />
<?php echo $arResult["NAV_STRING"]?>

<div id="task-gantt-filter" class="task-gantt-filter">
	<div class="task-filter<?php if ($arResult["ADV_FILTER"]["F_ADVANCED"] == "Y"):?> task-filter-advanced-mode<?php endif?>">

		<?php
			$name = $APPLICATION->IncludeComponent(
				"bitrix:tasks.filter.v2",
				".default",
				array(
					"ADV_FILTER" => $arResult["ADV_FILTER"],
					'USE_ROLE_FILTER' => 'N',
					"VIEW_TYPE" => $arResult["VIEW_TYPE"],
					"COMMON_FILTER" => $arResult["COMMON_FILTER"],
					"USER_ID" => $arParams["USER_ID"],
					"HIGHLIGHT_CURRENT" => $arResult["ADV_FILTER"]["F_ADVANCED"] == "Y" ? "N" : "Y",
					"ROLE_FILTER_SUFFIX" => $arResult["ROLE_FILTER_SUFFIX"],
					"PATH_TO_TASKS" => $arParams["PATH_TO_TASKS"],
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"]
				),
				null,
				array("HIDE_ICONS" => "Y")
			);
		?>

		<?php if ($arParams["USER_ID"] == $USER->GetID()):?>
			<div class="task-filter-extra-pages">
				<ul class="task-filter-items">
					<li class="task-filter-item">
						<a class="task-filter-item-link" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TEMPLATES"], array());?>"><span class="task-filter-item-left"></span><span class="task-filter-item-text"><?php echo GetMessage("TASKS_TEMPLATES")?></span><span class="task-filter-item-number"><?php echo CTaskTemplates::GetCount()?></span></a>
					</li>
					<li class="task-filter-item">
						<a class="task-filter-item-link" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORTS"], array());?>"><span class="task-filter-item-left"></span><span class="task-filter-item-text"><?php echo GetMessage("TASKS_REPORTS")?></span></a>
					</li>
				</ul>
			</div>
		<?php endif?>

		<ul class="task-filter-extra-links">
			<li><i class="task-list-to-excel"></i><a href="<?=($APPLICATION->GetCurPageParam("EXCEL=Y&ncc=1", array("PAGEN_".$arResult["NAV_PARAMS"]["PAGEN"], "SHOWALL_".$arResult["NAV_PARAMS"]["PAGEN"], "VIEW")))?>"><?php echo GetMessage("TASKS_EXPORT_EXCEL")?></a></li>
			<li><i class="task-list-to-outlook"></i><a href="javascript:<?echo CIntranetUtils::GetStsSyncURL(array('LINK_URL' => $arParams['PATH_TO_TASKS']), 'tasks')?>"><?php echo GetMessage("TASKS_EXPORT_OUTLOOK")?></a></li>
		</ul>
	</div>
</div>

<?php
if (!isset($arParams["HIDE_VIEWS"]) || $arParams["HIDE_VIEWS"] != "Y")
{
	$arComponentParams = $arParams;
	$arComponentParams['VIEW_TYPE'] = $arResult['VIEW_TYPE'];
	$arComponentParams['GROUP'] = $arResult['GROUP'];
	$arComponentParams['USER'] = $arResult['USER'];
	$arComponentParams['TEMPLATES'] = $arResult['TEMPLATES'];

	$filterName = '';
	if (strlen($arResult['SELECTED_PRESET_NAME']))
		$filterName .= ': ' . htmlspecialcharsbx($arResult['SELECTED_PRESET_NAME']);

	$arComponentParams['SELECTED_PRESET_NAME'] = $arResult['SELECTED_PRESET_NAME'];

	$arComponentParams['ADDITIONAL_HTML'] = '<span class="task-title-button-filter" 
		onclick="TaskGanttFilterPopup.show(this);">'
		. '<span class="task-title-button-filter-left"></span>'
		. '<span class="task-title-button-filter-text">'
			. GetMessage("TASKS_FILTER") . $filterName
		. '</span><span class="task-title-button-filter-right"></span></span>';

	$ynNotGroupList = 'Y';
	if ($arParams['GROUP_ID'] != 0)
		$ynNotGroupList = 'N';

	$arComponentParams = array_merge(
		$arComponentParams,
		array(
			'SHOW_TAB_PANEL'        => 'Y',
			'ADV_FILTER'            => $arResult["ADV_FILTER"],
			'VIEW_COUNTERS'         =>  $arResult['VIEW_COUNTERS'],
			'SHOW_SECTIONS_BAR'     => 'Y',
			'SHOW_FILTER_BAR'       => 'Y',
			'SHOW_COUNTERS_BAR'     =>  $ynNotGroupList,
			'SHOW_SECTION_PROJECTS' =>  $ynNotGroupList,
			'SHOW_SECTION_MANAGE'   => 'A',
			'SHOW_SECTION_COUNTERS' =>  $ynNotGroupList,
			'MARK_ACTIVE_ROLE'      => 'Y',
			'SORTING'               => $arResult["SORTING"],
			'FILTER'                => $arResult["ORIGINAL_FILTER"],
			'ORDER'                 => $arResult["ORDER"],
			'NAVIGATION'            => $arResult["FETCH_LIST_PARAMS"],
			'SELECT'                => $arResult["SELECT"],
			'COMPANY_WORKTIME'      => $arResult["COMPANY_WORKTIME"],
			'GANTT_MODE'            => true,
			"SECTION_URL_PREFIX"    => CComponentEngine::makePathFromTemplate($arParams["PATH_TO_TASKS"], array()),
		)
	);

	if ($arParams['USER_ID'] > 0)
	{
		$arComponentParams['PATH_TO_PROJECTS'] = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],
			array('user_id' => $arParams['USER_ID'])
		);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:tasks.list.controls',
		'.default',
		$arComponentParams,
		null,
		array('HIDE_ICONS' => 'Y')
	);
}
