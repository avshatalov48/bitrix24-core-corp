<?php

use \Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'popup',
	'tooltip',
	'taskQuickPopups',
	'task_info_popup',
	'task-popups',
]);

$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/script.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/table-view.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/tasks/task-iframe-popup.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/resizecolumns.js");
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/dd.js');
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/movecolumns.js");

$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");

// for sure
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/tasks/install/components/bitrix/tasks.task.detail.parts/templates/default/buttons/template.php');
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/tasks/install/components/bitrix/tasks.task.detail.parts/templates/flat/buttons/template.php');

?>

<?include('process.php');?>

<script>
BX.message(<?=CUtil::PhpToJSObject(array(
	'TASKS_TASK_CONFIRM_START_TIMER_TITLE' => Loc::getMessage('TASKS_TASK_CONFIRM_START_TIMER_TITLE'),
	'TASKS_TASK_CONFIRM_START_TIMER' => Loc::getMessage('TASKS_TASK_CONFIRM_START_TIMER')
))?>);
</script>
<?
$cls1 = 'page-one-column';
$cls2 = 'flexible-layout';

if (isset($arParams['PREVENT_PAGE_ONE_COLUMN']) && ($arParams['PREVENT_PAGE_ONE_COLUMN'] === 'Y'))
	$cls1 = '';

if (isset($arParams['PREVENT_FLEXIBLE_LAYOUT']) && ($arParams['PREVENT_FLEXIBLE_LAYOUT'] === 'Y'))
	$cls2 = '';

if ($cls1 && $cls2)
{
	$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
	$APPLICATION->SetPageProperty("BodyClass", $bodyClass." ".$cls1." ".$cls2);
}

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

if ($arParams["USER_ID"] == $USER->getId())
{
	$arTaskIframeParams = array(
		"ON_TASK_ADDED" => "onPopupTaskAdded",
		'ON_TASK_ADDED_MULTIPLE' => 'onPopupTaskAdded',
		"ON_TASK_CHANGED" => "onPopupTaskChanged",
		"ON_TASK_DELETED" => "onPopupTaskDeleted",
		'PATH_TO_USER_TASKS_TASK'  => $arParams['PATH_TO_USER_TASKS_TASK']
	);
}
else
{
	$arTaskIframeParams = array(
		"ON_TASK_ADDED" => "#SHOW_ADDED_TASK_DETAIL#",
		"ON_TASK_CHANGED" => "onPopupTaskChanged",
		"ON_TASK_DELETED" => "onPopupTaskDeleted",
		'PATH_TO_USER_TASKS_TASK'  => $arParams['PATH_TO_USER_TASKS_TASK']
	);
}

if (!defined('TASKS_MUL_INCLUDED')):

	$APPLICATION->IncludeComponent("bitrix:main.user.link",
		'',
		array(
			"AJAX_ONLY" => "Y",
			"PATH_TO_SONET_USER_PROFILE" => ($arParams["~PATH_TO_USER_PROFILE"] ?? null),
			"PATH_TO_SONET_MESSAGES_CHAT" => ($arParams["~PATH_TO_MESSAGES_CHAT"] ?? null),
			"DATE_TIME_FORMAT" => ($arParams["~DATE_TIME_FORMAT"] ?? null),
			"SHOW_YEAR" => ($arParams["SHOW_YEAR"] ?? null),
			"NAME_TEMPLATE" => ($arParams["~NAME_TEMPLATE"] ?? null),
			"SHOW_LOGIN" => ($arParams["SHOW_LOGIN"] ?? null),
			"PATH_TO_CONPANY_DEPARTMENT" => ($arParams["~PATH_TO_CONPANY_DEPARTMENT"] ?? null),
			"PATH_TO_VIDEO_CALL" => ($arParams["~PATH_TO_VIDEO_CALL"] ?? null),
		),
		false,
		array("HIDE_ICONS" => "Y")
	);
	define('TASKS_MUL_INCLUDED', 1);
endif;

if ($arResult["USER"])
{
	$userName 		= $arResult["USER"]["NAME"];
	$userLastName 	= $arResult["USER"]["LAST_NAME"];
	$userSecondName = $arResult["USER"]["SECOND_NAME"];
	$userLogin 		= $arResult["USER"]["LOGIN"];
}
else
{
	$userName 		= $USER->GetFirstName();
	$userLastName	= $USER->GetLastName();
	$userSecondName	= $USER->GetSecondName();
	$userLogin		= $USER->GetLogin();
}

$minColumnWidth     = CTaskColumnPresetManager::MINIMAL_COLUMN_WIDTH;
$lastColumnMinWidth = 29;
?>
<script type="text/javascript">
BX.message({
	TASKS_PRIORITY : '<?php echo GetMessageJS('TASKS_PRIORITY')?>',
	TASKS_APPLY : '<?php echo GetMessageJS('TASKS_APPLY')?>',
	TASKS_PRIORITY_LOW : '<?php echo GetMessageJS('TASKS_PRIORITY_0')?>',
	TASKS_PRIORITY_MIDDLE : '<?php echo GetMessageJS('TASKS_PRIORITY_1')?>',
	TASKS_PRIORITY_HIGH : '<?php echo GetMessageJS('TASKS_PRIORITY_2')?>',
	TASKS_MARK : '<?php echo GetMessageJS('TASKS_MARK_MSGVER_1')?>',
	TASKS_MARK_NONE : '<?php echo GetMessageJS('TASKS_MARK_NONE')?>',
	TASKS_MARK_P : '<?php echo GetMessageJS('TASKS_MARK_P_MSGVER_1')?>',
	TASKS_MARK_N : '<?php echo GetMessageJS('TASKS_MARK_N_MSGVER_1')?>',
	TASKS_DURATION : '<?php echo GetMessageJS('TASKS_DURATION')?>',
	TASKS_OK : '<?php echo GetMessageJS('TASKS_OK')?>',
	TASKS_CANCEL : '<?php echo GetMessageJS('TASKS_CANCEL')?>',
	TASKS_DECLINE : '<?php echo GetMessageJS('TASKS_DECLINE_TASK')?>',
	TASKS_DECLINE_REASON : '<?php echo GetMessageJS('TASKS_DECLINE_REASON')?>',
	TASKS_NO_TITLE : '<?php echo GetMessageJS('TASKS_NO_TITLE')?>',
	TASKS_NO_RESPONSIBLE : '<?php echo GetMessageJS('TASKS_NO_RESPONSIBLE')?>',
	TASKS_PATH_TO_USER_PROFILE : '<?php echo CUtil::JSEscape($arParams['PATH_TO_USER_PROFILE'])?>',
	TASKS_PATH_TO_TASK : '<?php echo CUtil::JSEscape($arParams['PATH_TO_TASKS_TASK'])?>',
	PATH_TO_GROUP_TASKS : '<?php echo CUtil::JSEscape($arParams['PATH_TO_GROUP_TASKS'])?>',
	TASKS_DOUBLE_CLICK : '<?php echo GetMessageJS('TASKS_DOUBLE_CLICK')?>',
	TASKS_MENU : '<?php echo GetMessageJS('TASKS_MENU')?>',
	TASKS_FINISH : '<?php echo GetMessageJS('TASKS_FINISH')?>',
	TASKS_FINISHED : '<?php echo GetMessageJS('TASKS_FINISHED')?>',
	TASKS_QUICK_IN_GROUP : '<?php echo GetMessageJS('TASKS_QUICK_IN_GROUP')?>',

	TASKS_ADD_TASK : '<?php echo GetMessageJS('TASKS_ADD_TASK')?>',
	TASKS_FILES: '<?php echo GetMessageJS('TASKS_TASK_FILES')?>',
	TASKS_START: '<?php echo GetMessageJS('TASKS_START')?>',
	TASKS_WAINTING_CONFIRM: '<?php echo GetMessageJS('TASKS_WAINTING_CONFIRM')?>',
	TASKS_MULTITASK: '<?php echo GetMessageJS('TASKS_MULTITASK')?>',
	TASKS_LIST_GROUP_ACTION_DAYS_PLURAL_0    : '<?php echo GetMessageJS('TASKS_LIST_GROUP_ACTION_DAYS_PLURAL_0'); ?>',
	TASKS_LIST_GROUP_ACTION_DAYS_PLURAL_1    : '<?php echo GetMessageJS('TASKS_LIST_GROUP_ACTION_DAYS_PLURAL_1'); ?>',
	TASKS_LIST_GROUP_ACTION_DAYS_PLURAL_2    : '<?php echo GetMessageJS('TASKS_LIST_GROUP_ACTION_DAYS_PLURAL_2'); ?>',
	TASKS_LIST_GROUP_ACTION_WEEKS_PLURAL_0   : '<?php echo GetMessageJS('TASKS_LIST_GROUP_ACTION_WEEKS_PLURAL_0'); ?>',
	TASKS_LIST_GROUP_ACTION_WEEKS_PLURAL_1   : '<?php echo GetMessageJS('TASKS_LIST_GROUP_ACTION_WEEKS_PLURAL_1'); ?>',
	TASKS_LIST_GROUP_ACTION_WEEKS_PLURAL_2   : '<?php echo GetMessageJS('TASKS_LIST_GROUP_ACTION_WEEKS_PLURAL_2'); ?>',
	TASKS_LIST_GROUP_ACTION_MONTHES_PLURAL_0 : '<?php echo GetMessageJS('TASKS_LIST_GROUP_ACTION_MONTHES_PLURAL_0'); ?>',
	TASKS_LIST_GROUP_ACTION_MONTHES_PLURAL_1 : '<?php echo GetMessageJS('TASKS_LIST_GROUP_ACTION_MONTHES_PLURAL_1'); ?>',
	TASKS_LIST_GROUP_ACTION_MONTHES_PLURAL_2 : '<?php echo GetMessageJS('TASKS_LIST_GROUP_ACTION_MONTHES_PLURAL_2'); ?>',
	<?php
	foreach (array_keys($arResult['KNOWN_COLUMNS']) as $columnId)
	{
		$langKey = $columnId;
		if ($langKey == 6)
		{
			$langKey = '6_MSGVER_1';
		}
		echo 'TASKS_LIST_COLUMN_' . $columnId . ": '" . GetMessageJS('TASKS_LIST_COLUMN_' . $langKey) . "',";
	}
	?>
	TASKS_LIST_MENU_RESET_TO_DEFAULT_PRESET : '<?php echo GetMessageJS('TASKS_LIST_MENU_RESET_TO_DEFAULT_PRESET'); ?>',
	TASKS_LIST_CONFIRM_ACTION_FOR_ALL_ITEMS : '<?php echo GetMessageJS('TASKS_LIST_CONFIRM_ACTION_FOR_ALL_ITEMS'); ?>',
	TASKS_LIST_CONFIRM_REMOVE_FOR_SELECTED_ITEMS : '<?php echo GetMessageJS('TASKS_LIST_CONFIRM_REMOVE_FOR_SELECTED_ITEMS'); ?>',
	TASKS_LIST_CONFIRM_REMOVE_FOR_ALL_ITEMS : '<?php echo GetMessageJS('TASKS_LIST_CONFIRM_REMOVE_FOR_ALL_ITEMS'); ?>',
	TASKS_LIST_GROUP_ACTION_PLEASE_WAIT     : '<?php echo GetMessageJS('TASKS_LIST_GROUP_ACTION_PLEASE_WAIT'); ?>'
});

var arFilter = <?php echo CUtil::PhpToJSObject($arResult["FILTER"])?>;
var arOrder = <?php echo CUtil::PhpToJSObject($arResult["ORDER"])?>;
var tasksListAjaxUrl = "/bitrix/components/bitrix/tasks.list/ajax.php?SITE_ID=<?php echo SITE_ID?><?php echo $arResult["TASK_TYPE"] == "group" ? "&GROUP_ID=".$arParams["GROUP_ID"] : ""?>&nt=<?php echo urlencode($arParams['NAME_TEMPLATE']); ?>";
var ajaxUrl = tasksListAjaxUrl;

var currentUser = <?php echo $USER->GetID()?>;
var tasksIFrameList = <?php echo CUtil::PhpToJSObject(array_keys($arResult["TASKS"]))?>;

BX.ready(function(){
	var knownColumnsIds    = [<?php echo implode(', ', array_keys($arResult['KNOWN_COLUMNS'])); ?>];
	var selectedColumnsIds = [];
	<?php
	$selectedColumns = array();
	foreach ($arResult['COLUMNS'] as $columnId)
	{
		?>selectedColumnsIds.push(<?php echo (int) $columnId['ID']; ?>);<?php
	}
	?>

	tasksListNS.onReady(
		BX('task-list-table'),
		'task_list_group_action_ID',	// name for group actions checkboxes
		BX('task_list_group_action_select_all_on_page'),	// checkbox which selects all other checkboxes
		BX('task_list_group_action_all'),	// checkbox all items on all pages
		<?php echo $minColumnWidth; ?>,
		<?php echo $lastColumnMinWidth; ?>,
		<?php echo $arResult['COLUMNS_CONTEXT_ID']; ?>,
		BX('task-head-set-icon'),
		knownColumnsIds,
		selectedColumnsIds,
		<?php echo CUtil::PhpToJSObject($arResult['ORIGINAL_FILTER']); ?>,
		'<?php echo CUtil::JSEscape($USER->getFormattedName($bUseBreaks = false, $bHTMLSpec = false)); ?>'
	);
});

</script>
<?php
if (
	! isset($arParams['HIDE_MENU'])
	|| ($arParams['HIDE_MENU'] !== 'Y')
)
{
	$APPLICATION->ShowViewContent("task_menu");
}
?>
<div id="task-list-container" class="task-list"><?php
	if ($arParams['GROUP_ID'])
	{
		?>
		<input type="hidden"
			id="task-current-project"
			value="<?php echo (int) $arParams['GROUP_ID']; ?>"
			>
		<?php
	}

	if (isset($arParams['HIDE_GROUP_ACTIONS']) && ($arParams['HIDE_GROUP_ACTIONS'] === 'Y'))
		$firstColumnWidth = 0;
	else
		$firstColumnWidth = 31;

	$bTableWidth = $firstColumnWidth + $lastColumnMinWidth;
	foreach ($arResult['COLUMNS'] as $column)
	{
		if ( ! ($column['WIDTH'] > 0) )
		{
			$bTableWidth = null;
			break;
		}

		$bTableWidth += (int) $column['WIDTH'];
	}
	?>
	<div class="task-list-left-corner"></div>
	<div class="task-list-right-corner"></div>
	<table
		class="task-list-table <?php if ($firstColumnWidth == 0) echo 'task-table-hide-first-column'; ?>"
		cellspacing="0"
		id="task-list-table"
			<?php
			if ($bTableWidth)
			{
				echo 'style="width:' . $bTableWidth . 'px"';
			}
			?>
	>
		<thead>
			<tr>
				<th style="width: <?php echo $firstColumnWidth; ?>px;">
					<div style="width: <?php echo $firstColumnWidth; ?>;">
						<input id="task_list_group_action_select_all_on_page" class="task-list-inp" type="checkbox" />
					</div>
				</th>
				<?php
				foreach ($arResult['COLUMNS'] as $column)
				{
					$clsSort = '';
					$urlSort = '';
					$fieldName = $arResult['KNOWN_COLUMNS'][$column['ID']]['DB_COLUMN'];
					if (in_array($fieldName, $arResult['SUPPORTED_FIELDS_FOR_SORT'], true))
					{
						if ($arResult['SORTF'] === $fieldName)
						{
							if (($arResult['SORTD'] === 'ASC') || ($arResult['SORTD'] === 'ASC,NULLS'))
							{
								$clsSort = 'class="task-column-selected task-column-order-by-asc"';

								if ($fieldName === 'DEADLINE')
									$newSortDirection = 'DESC,NULLS';
								else
									$newSortDirection = 'DESC';
							}
							else
							{
								$clsSort = 'class="task-column-selected task-column-order-by-desc"';

								if ($fieldName === 'DEADLINE')
									$newSortDirection = 'ASC,NULLS';
								else
									$newSortDirection = 'ASC';
							}
						}
						else
						{
							if ($fieldName === 'DEADLINE')
								$newSortDirection = 'ASC,NULLS';
							else
								$newSortDirection = 'ASC';
						}

						$urlSort = $APPLICATION->GetCurPageParam(
							'SORTF=' . $fieldName . '&SORTD=' . $newSortDirection,
							array('SORTF', 'SORTD')
						);
					}

					if ($column['WIDTH'] > 0)
					{
						if ($column['WIDTH'] > $minColumnWidth)
							$columnWidthStr = 'width: ' . (int) $column['WIDTH'] . 'px';
						else
							$columnWidthStr = 'width: ' . $minColumnWidth . 'px';
					}
					else
						$columnWidthStr = '';

					?>
					<th style="<?php echo $columnWidthStr; ?>"
						title="<?php echo ($column['ID'] == 6) ? GetMessage('TASKS_LIST_COLUMN_' . $column['ID'] . '_MSGVER_1') : GetMessage('TASKS_LIST_COLUMN_' . $column['ID']); ?>"
						<?php echo $clsSort; ?>
					>
						<div class="task-head-cell-wrap" style="min-width:<?php echo $minColumnWidth; ?>px; <?php echo $columnWidthStr; ?>">
							<input type="hidden" name="COLUMN_ID" value="<?php echo $column['ID']; ?>" />
							<div class="task-head-drag-btn"><span class="task-head-drag-btn-inner"></span></div>
							<div class="task-head-cell"
								<?php
								if ($urlSort)
								{
									?>
									onclick="SortTable(
										'<?php echo htmlspecialchars(CUtil::JSEscape($urlSort));?>',
										event
									)"
									<?php
								}
								?>
							>
								<span class="task-head-cell-sort-order"></span>
								<span class="task-head-cell-title"><?php echo ($column['ID'] == 6) ? GetMessage('TASKS_LIST_COLUMN_' . $column['ID'] . '_MSGVER_1') : GetMessage('TASKS_LIST_COLUMN_' . $column['ID']); ?></span>
						</div>
					</th>
					<?php
				}
				?>
				<th id="task-elastic-column" style="width:<?php echo $lastColumnMinWidth; ?>px;"><div style="min-width:<?php echo $lastColumnMinWidth; ?>px;"><span id="task-head-set-icon" title="<?php echo GetMessage('TASKS_CONFIGURE_LIST'); ?>" class="task-head-set-icon"></span></div></th>
			</tr>
		</thead>
		<tbody id="task-list-table-body">
			<?php
			$tableColumnsCount = count($arResult['COLUMNS']) + 2;

			if (sizeof($arResult["TASKS"]) > 0)
			{
				$currentProject = false;

				foreach($arResult["TASKS"] as $key=>$task)
				{
					if ($task["GROUP_ID"] > 0)
						$task["GROUP_NAME"] =  $arResult["GROUPS"][$task["GROUP_ID"]]["NAME"];

					if ($arResult["TASK_TYPE"] != "group" && $task["GROUP_ID"] && $task["GROUP_ID"] != $currentProject)
					{
						$currentProject = $task["GROUP_ID"];
						?>
						<tr class="task-list-item task-list-project-item task-depth-0" data-project-id="<?=$task["GROUP_ID"]?>" id="task-project-<?=$task["GROUP_ID"]?>">
							<td class="task-project-column" colspan="<?php echo $tableColumnsCount; ?>">
								<div class="task-project-column-inner">
									<div class="task-project-name"><span class="task-project-folding<?php
									if ( ! $arResult['GROUPS'][$task['GROUP_ID']]['EXPANDED'] )
										echo ' task-project-folding-closed';
										?>" onclick="ToggleProjectTasks(<?php echo $arResult["GROUPS"][$task["GROUP_ID"]]["ID"]?>, event);"></span><a class="task-project-name-link" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_TASKS"], array("group_id" => $arResult["GROUPS"][$task["GROUP_ID"]]["ID"]))?>" onclick="ToggleProjectTasks(<?php echo $arResult["GROUPS"][$task["GROUP_ID"]]["ID"]?>, event);"><?php echo $arResult["GROUPS"][$task["GROUP_ID"]]["NAME"]?></a></div>
									<?php if (is_object($USER) && $USER->IsAuthorized() && $arParams["HIDE_VIEWS"] != "Y"):?>
										<div class="task-project-actions"><a class="task-project-action-link" href="<?php $path = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit")); echo $path.(mb_strstr($path, "?")? "&" : "?")."GROUP_ID=".$arResult["GROUPS"][$task["GROUP_ID"]]["ID"].($arResult["IS_IFRAME"] ? "&IFRAME=Y" : "");?>"><i class="task-project-action-icon"></i><span class="task-project-action-text"><?php echo GetMessage("TASKS_ADD_TASK")?></span></a></div>
									<?php endif?>
								</div>
							</td>
						</tr>
						<?php
					}

					$projectExpanded = true;
					if (($arParams['TASKS_ALWAYS_EXPANDED'] ?? null) != 'Y' && isset($arResult["GROUPS"][$task["GROUP_ID"]])
						&& isset($arResult["GROUPS"][$task["GROUP_ID"]]["EXPANDED"])
						&& ( ! $arResult["GROUPS"][$task["GROUP_ID"]]["EXPANDED"] )
					)
					{
						$projectExpanded = false;
					}

					$APPLICATION->IncludeComponent(
						'bitrix:tasks.list.items',
						'.default',
						array(
							"PATHS"         => $arPaths,
							"PLAIN"         => ($arResult['VIEW_STATE']['SUBMODES']['VIEW_SUBMODE_WITH_SUBTASKS']['SELECTED'] === 'N'),
							"VIEW_STATE"    => $arResult['VIEW_STATE'],
							"DEFER"         => false,
							"SITE_ID"       => SITE_ID,
							"TASK_ADDED"    => false,
							"PATH_TO_GROUP" => ($arParams['PATH_TO_GROUP'] ?? null),
							"IFRAME"        => 'N',
							"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
							"COLUMNS"       => $arResult['COLUMNS'],
							'DATA_COLLECTION' => array(
								array(
									"CHILDREN_COUNT"   => isset($arResult["CHILDREN_COUNT"]["PARENT_".$task["ID"]]) ? $arResult["CHILDREN_COUNT"]["PARENT_".$task["ID"]] : 0,
									"DEPTH"            => 0,
									"UPDATES_COUNT"    => isset($arResult["UPDATES_COUNT"][$task["ID"]]) ? $arResult["UPDATES_COUNT"][$task["ID"]] : 0,
									"PROJECT_EXPANDED" => $projectExpanded,
									'ALLOWED_ACTIONS'  => $task['META:ALLOWED_ACTIONS'],
									"TASK"             => $task,
								)
							)
						), null, array("HIDE_ICONS" => "Y")
					);
				}
				?>
				<tr class="task-list-item" id="task-list-no-tasks" style="display:none;"><td class="task-new-item-column" colspan="<?php echo $tableColumnsCount; ?>" style="text-align: center"><?php echo GetMessage("TASKS_NO_TASKS"); ?></td></tr>
				<?php
			}
			else
			{
				?>
				<tr class="task-list-item" id="task-list-no-tasks"><td class="task-new-item-column" colspan="<?php echo $tableColumnsCount; ?>" style="text-align: center"><?php echo GetMessage("TASKS_NO_TASKS"); ?></td></tr>
				<?php
			}
			?>
		</tbody>
	</table>

	<div class="task-table-footer-wrap" <?php if (isset($arParams['HIDE_GROUP_ACTIONS']) && ($arParams['HIDE_GROUP_ACTIONS'] === 'Y')) echo 'style="display:none;"' ?>>
	<form action="<?=POST_FORM_ACTION_URI?>" id="task-list-group-operations">
		<div class="task-table-footer">
			<input type="checkbox" <?//onclick="active_btn(this)"?> id="task_list_group_action_all" class="task-table-foot-checkbox" title="<?=GetMessage('TASKS_LIST_TOOLTIP_FOR_ALL_ITEMS')?>"><label class="task-table-foot-label" for="task_list_group_action_all"><?=GetMessage('TASKS_LIST_GROUP_ACTION_FOR_ALL')?>
			</label><?/*<span class="task-table-footer-btn task-btn-edit"></span><span class="task-table-footer-btn task-btn-del"></span>*/?>
			<select id="task-list-group-action-selector" class="bx24-dropdown task-table-select" onchange="tasksListNS.onActionSelect(this);">
				<option value="noaction"></option>
				<option value="complete"><?php
					echo htmlspecialcharsbx(GetMessage('TASKS_LIST_GROUP_ACTION_COMPLETE'));
				?></option>
				<option value="set_deadline"><?php
					echo htmlspecialcharsbx(GetMessage('TASKS_LIST_GROUP_ACTION_SET_DEADLINE'));
				?></option>
				<option value="adjust_deadline"><?php
					echo htmlspecialcharsbx(GetMessage('TASKS_LIST_GROUP_ACTION_ADJUST_DEADLINE'));
				?></option>
				<option value="substract_deadline"><?php
					echo htmlspecialcharsbx(GetMessage('TASKS_LIST_GROUP_ACTION_SUBSTRACT_DEADLINE'));
				?></option>
				<option value="change_responsible"><?php
					echo htmlspecialcharsbx(GetMessage('TASKS_LIST_GROUP_ACTION_CHANGE_RESPONSIBLE'));
				?></option>
				<option value="change_originator"><?php
					echo htmlspecialcharsbx(GetMessage('TASKS_LIST_GROUP_ACTION_CHANGE_ORIGINATOR'));
				?></option>
				<option value="add_auditor"><?php
					echo htmlspecialcharsbx(GetMessage('TASKS_LIST_GROUP_ACTION_ADD_AUDITOR'));
				?></option>
				<option value="add_accomplice"><?php
					echo htmlspecialcharsbx(GetMessage('TASKS_LIST_GROUP_ACTION_ADD_ACCOMPLICE'));
				?></option>

				<?if(!(($arResult['VIEW_STATE']['SPECIAL_PRESET_SELECTED']['CODENAME'] ?? null) == 'FAVORITE' && $arResult['VIEW_STATE']['SECTION_SELECTED']['CODENAME'] == 'VIEW_SECTION_ADVANCED_FILTER')):?>
					<?// adding is blocked when filtering by this preset?>
					<option value="add_favorite"><?php
						echo htmlspecialcharsbx(GetMessage('TASKS_LIST_GROUP_ACTION_ADD_FAVORITE'));
					?></option>
				<?endif?>

				<option value="delete_favorite"><?php
					echo htmlspecialcharsbx(GetMessage('TASKS_LIST_GROUP_ACTION_DELETE_FAVORITE'));
				?></option>
				<option value="set_group"><?php
					echo htmlspecialcharsbx(GetMessage('TASKS_LIST_GROUP_ACTION_SET_GROUP'));
				?></option>
				<option value="remove"><?php
					echo htmlspecialcharsbx(GetMessage('TASKS_LIST_GROUP_ACTION_REMOVE'));
				?></option>
			</select>
			<span class="task-table-footer-inp-wrap" style="display:none;">
				<input class="bx24-field" id="task-list-group-action-date-selector"
					type="text"
					autocomplete="off"
					class="task-table-footer-inp"
					onclick="(function(self){
						BX.calendar({
							node           : self,
							field          : self,
							form           : '',
							bTime          : true,
							bHideTimebar   : false,
							value          : BX.CJSTask.ui.getInputDateTimeValue(BX('task-list-group-action-date-selector')),
							callback_after : function(value){

								var defaultTime = BX.CJSTask.ui.extractDefaultTimeFromDataAttribute(self);

								self.value = BX.CJSTask.addTimeToDateTime(self.value, defaultTime);

								BX('task-list-group-action-value').value = self.value;
								BX.addClass(BX('task-list-group-action-date-selector').parentNode, 'task-table-footer-inp-del');
							}
						});
					})(this);
					"
					data-default-hour="<?=intval($arResult['COMPANY_WORKTIME']['END']['H'])?>"
					data-default-minute="<?=intval($arResult['COMPANY_WORKTIME']['END']['M'])?>"
				/><span id="task-list-group-action-date-delete" class="task-table-inp-del-icon"></span


			></span><span class="task-table-footer-inp-wrap task-table-footer-inp-del" style="display:none;">
				<input id="task-list-group-action-group-selector" autocomplete="off" type="text" class="bx24-field">
				<span id="task-list-group-action-group-delete" class="task-table-inp-del-icon"></span>
			</span>

			<span class="task-table-footer-inp-wrap">
				<input id="task-list-group-action-user-selector"
					type="text"
					autocomplete="off" class="task-table-footer-inp bx24-field"
					style="display:none;" />
			</span>

			<span class="task-table-footer-inp-wrap">
				<input id="task-list-group-action-days_count-selector"
					onchange="tasksListNS.onGroupActionDaysChanged();"
					onkeyup="tasksListNS.onGroupActionDaysChanged();"
					type="text" autocomplete="off" class="task-table-footer-inp bx24-field"
					style="width: 40px; display:none;" maxlenght="4" />
			</span>

			<select id="task-list-group-action-days_type-selector"
				onchange="tasksListNS.onGroupActionDaysChanged();"
				onkeyup="tasksListNS.onGroupActionDaysChanged();"
				class="task-table-select-days bx24-dropdown"
				style="margin-left:10px; display: none;"
			>
				<option value="days"><?=htmlspecialcharsbx(GetMessage('TASKS_LIST_GROUP_ACTION_DAYS_PLURAL_0'))?></option>
				<option value="weeks"><?=htmlspecialcharsbx(GetMessage('TASKS_LIST_GROUP_ACTION_WEEKS_PLURAL_0'))?></option>
				<option value="monthes"><?php
					echo htmlspecialcharsbx(GetMessage('TASKS_LIST_GROUP_ACTION_MONTHES_PLURAL_0'));
				?></option>
			</select>

			<input id="task-list-group-action-value" type="hidden" value="" />

			<span id="task-list-group-action-submit" class="webform-small-button webform-small-button-transparent task-table-btn" onclick="
					(function(){
						var e = BX('task-list-group-action-selector');
						var subAction = e.options[e.selectedIndex].value;
						var confirmText;

						if (BX.hasClass(BX('task-list-group-action-submit'), 'task-noclass-disabled'))
							return;

						if (subAction === 'noaction')
							return;

						if (subAction === 'remove')
						{
							if (BX('task_list_group_action_all').checked)
								confirmText = BX.message('TASKS_LIST_CONFIRM_REMOVE_FOR_ALL_ITEMS');
							else
								confirmText = BX.message('TASKS_LIST_CONFIRM_REMOVE_FOR_SELECTED_ITEMS');

							if ( ! confirm(confirmText) )
								return;
						}

						BX.addClass(BX('task-list-group-action-submit'), 'task-noclass-disabled');

						tasksListNS.submitGroupAction(BX('task-list-group-action-submit'), subAction, false, BX('task-list-group-operations').action);
					})();
				">

				<span class="webform-small-button-left"></span>
				<span class="webform-small-button-text"><?=htmlspecialcharsbx(GetMessage('TASKS_LIST_SUBMIT'))?></span>
				<span class="webform-small-button-right"></span>
			</span>
		</div>
	</form>
	</div>
</div>

<script type="text/javascript">
<?php
// This js-code MUST be inline
?>
(function(){
	var table    = BX('task-list-table');
	var topCells = table.rows[0].cells;
	var outerDivWidth;

	var enumerateColumns = function()
	{
		var columns = [], i, iMax, cell;

		iMax = topCells.length - 1;	// skip last column
		for (i = 0; i < iMax; ++i)
		{
			cell = topCells[i];
			columns.push({
				cell        : cell,
				internalDiv : BX.findChild(
					cell,
					{ tagName: 'div' },
					false,		// recursive
					false		// get_all
				),
				width       : cell.offsetWidth,
				canBeScaled : ((cell.style.width === "") ? true : false)
			});
		}

		// last (elastic) column
		columns.push({
			cell        : topCells[iMax],
			internalDiv : null,
			width       : topCells[iMax].offsetWidth,
			canBeScaled : false
		});

		return (columns);
	};

	var recalcColumnsWidth = function(columns, curTableWidth, newTableWidth)
	{
		var i, iMax, column, elasticColumn, unallocatedWidth, multiplier,
			adjustedColumnWidth, adjustedColumnCount = 0,
			scalableColumnsWidth = 0, scalableColumnsCount = 0,
			delta = newTableWidth - curTableWidth;

		iMax = columns.length;
		for (i = 0; i < iMax; ++i)
		{
			column = columns[i];
			if (column.canBeScaled)
			{
				++scalableColumnsCount;
				scalableColumnsWidth += column.width;
			}
		}

		elasticColumn = columns[iMax - 1];

		if (scalableColumnsCount == 0)
		{
			// there is no scalable columns, so adjust elstic column width
			elasticColumn.width += delta;
		}
		else
		{
			// adjust proportionally all scalable columns
			multiplier = (scalableColumnsWidth + delta) / scalableColumnsWidth;
			unallocatedWidth = delta;

			iMax = columns.length;
			for (i = 0; i < iMax; ++i)
			{
				column = columns[i];
				if ( ! column.canBeScaled )
					continue;

				++adjustedColumnCount;

				// last scalable column?
				if (adjustedColumnCount == scalableColumnsCount)
				{
					column.width += unallocatedWidth;
					break;
				}
				else
				{
					adjustedColumnWidth = Math.floor(column.width * multiplier);
					if (adjustedColumnWidth < column.width)
						adjustedColumnWidth = column.width;

					unallocatedWidth -= (adjustedColumnWidth - column.width);

					column.width = adjustedColumnWidth;
				}
			}
		}

		return (columns);
	};

	var setNewColumnsWidth = function(columns)
	{
		var i, iMax, column;

		iMax = columns.length;
		for (i = 0; i < iMax; ++i)
		{
			column = columns[i];

			column.cell.style.width = column.width + 'px';

			if (column.internalDiv)
				column.internalDiv.style.width = column.width + 'px';
		}
	};

	var expandTable = function(newTableWidth)
	{
		var columns, tableWidth;

		tableWidth = parseInt(table.offsetWidth);

		if (tableWidth >= newTableWidth)
			return;

		columns = enumerateColumns();
		columns = recalcColumnsWidth(columns, tableWidth, newTableWidth);
		table.style.width = newTableWidth + 'px';
		setNewColumnsWidth(columns);
	};

	outerDivWidth = parseInt(table.parentNode.offsetWidth);

	expandTable(outerDivWidth - 20);
})();
</script>

<? if($arResult["NAV_STRING"] <> ''): ?>
	<br/><?= $arResult["NAV_STRING"] ?>
<? endif?>

<?php if (!isset($arParams["HIDE_VIEWS"]) || $arParams["HIDE_VIEWS"] != "Y"):?>
	<?php
	if ($arResult['VIEW_STATE']['SECTION_SELECTED']['CODENAME'] !== 'VIEW_SECTION_ROLES')
	{
		?>
		<div id="task-list-filter" class="task-gantt-filter">
			<div class="task-filter<?php if (isset($arResult["ADV_FILTER"]["F_ADVANCED"]) && $arResult["ADV_FILTER"]["F_ADVANCED"] == "Y"):?> task-filter-advanced-mode<?php endif?>">

				<?php
					$name = $APPLICATION->IncludeComponent(
						"bitrix:tasks.filter.v2",
						".default",
						array(
							"ADV_FILTER" => isset($arResult["ADV_FILTER"]) ? $arResult["ADV_FILTER"] : null,
							'USE_ROLE_FILTER' => 'N',
							"VIEW_TYPE" => $arResult["VIEW_TYPE"],
							"COMMON_FILTER" => $arResult["COMMON_FILTER"],
							"USER_ID" => $arParams["USER_ID"],
							"HIGHLIGHT_CURRENT" => isset($arResult["ADV_FILTER"]["F_ADVANCED"]) && $arResult["ADV_FILTER"]["F_ADVANCED"] == "Y" ? "N" : "Y",
							"ROLE_FILTER_SUFFIX" => $arResult["ROLE_FILTER_SUFFIX"],
							"PATH_TO_TASKS" => $arParams["PATH_TO_TASKS"],
							"GROUP_ID" => $arParams["GROUP_ID"],
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
	}

	$arComponentParams = array_merge(
		$arParams,
		array(
			'VIEW_TYPE'           => $arResult['VIEW_TYPE'],
			'GROUP'               => $arResult['GROUP'],
			'USER'                => $arResult['USER'],
			'SHOW_QUICK_TASK_ADD' => 'Y',
			'SHOW_HELP_ICON'      => 'N',
			'ADV_FILTER'          => $arResult["ADV_FILTER"],
			'SORTING'             => $arResult["SORTING"],
			'FILTER'              => $arResult["ORIGINAL_FILTER"],
			'ORDER'               => $arResult["ORDER"],
			'NAVIGATION'          => $arResult["FETCH_LIST_PARAMS"],
			'SELECT'              => $arResult["SELECT"],
			'COMPANY_WORKTIME'    => $arResult["COMPANY_WORKTIME"],
			"SECTION_URL_PREFIX"  => CComponentEngine::makePathFromTemplate($arParams["PATH_TO_TASKS"], array()),
		)
	);

	$ynNotGroupList = 'Y';
	if ($arParams['GROUP_ID'] != 0)
		$ynNotGroupList = 'N';

	$arComponentParams = array_merge(
		$arComponentParams,
		array(
			'SHOW_TAB_PANEL'        => 'Y',
			'VIEW_COUNTERS'         =>  $arResult['VIEW_COUNTERS'],
			'SHOW_SECTIONS_BAR'     => 'Y',
			'SHOW_FILTER_BAR'       => 'Y',
			'SHOW_COUNTERS_BAR'     =>  $ynNotGroupList,
			'SHOW_SECTION_MANAGE'   => 'A',
			'SHOW_SECTION_COUNTERS' =>  $ynNotGroupList,
			'MARK_ACTIVE_ROLE'      => 'Y'
		)
	);

	$filterName = '';
	if($arResult['SELECTED_PRESET_NAME'] <> '')
	{
		$filterName .= ': '.htmlspecialcharsbx($arResult['SELECTED_PRESET_NAME']);
	}

	$arComponentParams['SELECTED_PRESET_NAME'] = $arResult['SELECTED_PRESET_NAME'];

	$arComponentParams['ADDITIONAL_HTML'] = '';
	$arComponentParams['SHOW_TASK_LIST_MODES'] = 'N';

	if ($arParams['USER_ID'] > 0)
	{
		$arComponentParams['PATH_TO_PROJECTS'] = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],
			array('user_id' => $arParams['USER_ID'])
		);
	}

	if(intval($arParams['GROUP_ID']) && !CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $arParams['GROUP_ID'], "tasks", "create_tasks"))
	{
		$arComponentParams['SHOW_ADD_TASK_BUTTON'] = 'N';
		$arComponentParams['SHOW_QUICK_TASK_ADD'] = 'N';
		$arComponentParams['SHOW_TEMPLATES_TOOLBAR'] = 'N';
	}

	$APPLICATION->IncludeComponent(
		'bitrix:tasks.list.controls',
		'.default',
		$arComponentParams,
		null,
		array('HIDE_ICONS' => 'Y')
	);
endif;
?>

<script>tasksListTemplateDefaultInit()</script>
<script>tasksListTemplateDefaultTableViewInit()</script>