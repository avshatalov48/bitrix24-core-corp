<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Tasks\Internals\Counter;
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CBitrixComponent $component */

if (
	$arParams['SHOW_SECTIONS_BAR'] !== 'Y' &&
	$arParams['SHOW_FILTER_BAR'] !== 'Y' &&
	$arParams['SHOW_COUNTERS_BAR'] !== 'Y'
)
{
	return;
}

\Bitrix\Main\UI\Extension::load([
	'popup',
	'tooltip',
	'ajax',
	'date',
	'tasks_util_query',
	'socnetlogdest',
	'CJSTask',
	'ui.fonts.opensans',
]);

$taskListUserOpts = CUserOptions::GetOption('tasks', 'task_list');
$taskListGlobalOpts = array(
	'enable_gantt_hint' => \Bitrix\Main\Config\Option::get('tasks', 'task_list_enable_gantt_hint')
);

if(SITE_TEMPLATE_ID === "bitrix24")
{
	$this->SetViewTarget($arParams["MENU_TARGET"], 100);
}

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."top-menu-mode");

if ($arParams["SHOW_SECTIONS_BAR"] === "Y")
{
	$menuItems = array();

	$oCounters = Counter::getInstance($arParams['USER_ID']);
	$groupId = (int) $arParams['GROUP_ID'];

	$menuItems[] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_TASKS"),
		"URL" => $arParams["SECTION_URL_PREFIX"].$arResult["VIEW_SECTION_ADVANCED_FILTER_HREF"]
				 .'&clear_filter=Y&apply_filter=Y',
		"ID" => "view_all",
		'COUNTER' => $oCounters->get(\Bitrix\Tasks\Internals\Counter\CounterDictionary::COUNTER_MEMBER_TOTAL, $groupId),
		'COUNTER_ID' => \CTaskCountersProcessor::COUNTER_TASKS_TOTAL,
		'COUNTER_ACTIVE'=> 'Y',
		"IS_ACTIVE" => $arResult["MARK_SECTION_ALL"] === "Y",
		'SUB_LINK'=>array('CLASS'=>'', 'URL'=>CComponentEngine::makePathFromTemplate(
			$arParams['PATH_TO_USER_TASKS_TASK'],
			array(
				'action'   => 'edit',
				'task_id'  => 0,
				'user_id'  => $arParams['USER_ID'],
				'group_id' => $arParams['GROUP_ID']
			)
		)),
	);

	$counters = array(
		"view_role_responsible" => \Bitrix\Tasks\Internals\Counter\CounterDictionary::COUNTER_MY,
		"view_role_accomplice" => \Bitrix\Tasks\Internals\Counter\CounterDictionary::COUNTER_ACCOMPLICES,
		"view_role_auditor" => \Bitrix\Tasks\Internals\Counter\CounterDictionary::COUNTER_AUDITOR,
		"view_role_originator" => \Bitrix\Tasks\Internals\Counter\CounterDictionary::COUNTER_ORIGINATOR
	);

	$myPlace = $arParams['USER_ID'] == \Bitrix\Tasks\Util\User::getId();

	foreach ($arResult["VIEW_STATE"]["ROLES"] as $roleCodename => $arRoleData)
	{
		$selected =
			$arParams["MARK_ACTIVE_ROLE"] === "Y" &&
			$arRoleData["SELECTED"] === "Y" &&
			$arResult["SELECTED_SECTION_NAME"] === "VIEW_SECTION_ROLES"
		;

		$href = $arParams["SECTION_URL_PREFIX"].$arResult["VIEW_HREFS"]["ROLES"][$roleCodename];

		$counter = "";
		if ($arParams["SHOW_SECTION_COUNTERS"] === "Y")
		{
			$counterName = $counters[mb_strtolower($roleCodename)];

			$counter = $oCounters->get($counterName, $groupId);
		}

		if (!$arParams["PATH_TO_REPORTS"])
		{
			$arParams["PATH_TO_REPORTS"] = $arParams["SECTION_URL_PREFIX"]."report/";
		}

		$role = mb_strtolower($roleCodename);
		$menuItems[] = array(
			"TEXT" => trim($arRoleData["TITLE"]),
			"URL" => $href.'&STATUS[]=2&STATUS[]=3&ROLEID='.$role.'&apply_filter=Y',
			"ID" => mb_strtolower($role),
			"IS_ACTIVE" => $selected,
			"COUNTER" => $counter > 0 ? $counter : "",
			// do not update counters dynamically if I am at other user`s list now
			"COUNTER_ID" => isset($counters[$role]) && $myPlace ? $counters[$role] : ""
		);
	}

	$menuItems[] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_KANBAN"),
		"URL" => $arParams["SECTION_URL_PREFIX"].'board/',
		"ID" => "view_kanban"
	);



	$menuItems[] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_EFFECTIVE"),
		"URL" => $arParams["SECTION_URL_PREFIX"].'effective/',
		"ID" => "view_effective",
		'MAX_COUNTER_SIZE'=>100,
		'COUNTER' => Counter::getInstance($arParams['USER_ID'])->get(Counter\CounterDictionary::COUNTER_EFFECTIVE)
	);


	if ($arResult["SHOW_SECTION_MANAGE"] === "Y")
	{
		$counter = intval($arResult["SECTION_MANAGE_COUNTER"]);
		$menuItems[] = array(
			"TEXT" => GetMessage("TASKS_PANEL_TAB_MANAGE"),
			"URL" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_DEPARTMENTS"], array()),
			"ID" => "view_departments",
			"COUNTER" => $counter ?: "",
			'COUNTER_ID' => 'departments_counter',
			"IS_ACTIVE" => $arParams["MARK_SECTION_MANAGE"] === "Y",
		);
	}

	if (!\Bitrix\Tasks\Integration\Extranet\User::isExtranet() && !intval($arParams['GROUP_ID']))
	{
		$menuItems[] = array(
			"TEXT" => GetMessage("TASKS_PANEL_TAB_EMPLOYEE_PLAN"),
			"URL" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_EMPLOYEE_PLAN"], array()),
			"ID" => "view_employee_plan",
			"IS_ACTIVE" => $arParams["MARK_SECTION_EMPLOYEE_PLAN"] === "Y",
			'IS_DISABLED'=>true
		);
	}

	if (\Bitrix\Main\ModuleManager::isModuleInstalled("recyclebin"))
	{
		$menuItems[] = array(
			"TEXT" => GetMessage("TASKS_PANEL_TAB_RECYCLEBIN"),
			"URL" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_RECYCLEBIN"], array()),
			"ID" => "view_recyclebin",
			"IS_ACTIVE" => $arResult["MARK_SECTION_RECYCLEBIN"] === "Y",
			'IS_DISABLED'=>true
		);
	}

	$menuItems[] = array(
		"TEXT" => GetMessage("TASKS_PANEL_TAB_REPORTS"),
		"URL" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORTS"], array()),
		"ID" => "view_reports",
		"IS_ACTIVE" => $arParams["MARK_SECTION_REPORTS"] === "Y",
		'IS_DISABLED'=>true
	);

	if ($arResult["BX24_RU_ZONE"])
	{
		$menuItems[] = array(
			"TEXT" => GetMessage("TASKS_PANEL_TAB_APPLICATIONS_2"),
			"URL" => "/marketplace/category/tasks/",
			"ID" => "view_apps",
		);
	}

	if(!$arParams['GROUP_ID'])
	{
		$menuItems[] = array(
			"TEXT"      => GetMessage("TASKS_PANEL_TAB_TEMPLATES"),
			"URL"       => $arParams["SECTION_URL_PREFIX"].'templates/',
			"ID"        => "view_templates",
			"IS_ACTIVE" => $arParams["MARK_TEMPLATES"] == "Y",
			'IS_DISABLED'=>true
		);
	}
	$postfix = '';//$arParams["USER_ID"] === \Bitrix\Tasks\Util\User::getId() ? "" : "_".$arParams["USER_ID"];
	$menuId = intval($arParams["GROUP_ID"]) ? "tasks_panel_menu_group" : "tasks_panel_menu".$postfix;
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.buttons",
		"",
		array(
			"ID" => $menuId,
			"ITEMS" => $menuItems,
		)
	);
}
if(SITE_TEMPLATE_ID === "bitrix24")
{
	$this->EndViewTarget();
}

if(SITE_TEMPLATE_ID === "bitrix24")
{
	$this->SetViewTarget($arParams["CONTROLS_TARGET"], 200);
}

if ($arParams['SHOW_COUNTERS_BAR'] === 'Y')
{
	$arStrings = array();

	if (
		isset($arResult['TASKS_NEW_COUNTER']['VALUE'])
		&& $arResult['TASKS_NEW_COUNTER']['VALUE']
	)
	{
		$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES']['VIEW_TASK_CATEGORY_NEW'];
		$arStrings[] = '<a href="' . $href . '" class="task-green-text">'
			. $arResult['TASKS_NEW_COUNTER']['VALUE']
			. ' '
			. GetMessage(
				'TASKS_PANEL_EXPLANATION_NEW_TASKS_SUFFIX_PLURAL_'
				. $arResult['TASKS_NEW_COUNTER']['PLURAL']
			)
			. '</a>';
	}

	if (
		isset($arResult['TASKS_EXPIRED_COUNTER']['VALUE'])
		&& $arResult['TASKS_EXPIRED_COUNTER']['VALUE']
	)
	{
		$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES']['VIEW_TASK_CATEGORY_EXPIRED'];
		$arStrings[] = '<a href="' . $href . '" class="task-red-text">'
			. $arResult['TASKS_EXPIRED_COUNTER']['VALUE']
			. ' '
			. GetMessage(
				'TASKS_PANEL_EXPLANATION_EXPIRED_TASKS_SUFFIX_PLURAL_'
				. $arResult['TASKS_EXPIRED_COUNTER']['PLURAL']
			)
			. '</a>';
	}

	if (
		isset($arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['VALUE'])
		&& $arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['VALUE']
	)
	{
		$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES']['VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES'];
		$arStrings[] = '<a href="' . $href . '" class="task-brown-text">'
			. $arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['VALUE']
			. ' '
			. GetMessage(
				'TASKS_PANEL_EXPLANATION_EXPIRED_SOON_TASKS_SUFFIX_PLURAL_'
				. $arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['PLURAL']
			)
			. '</a>';
	}

	if (
		isset($arResult['TASKS_WAIT_CTRL_COUNTER']['VALUE'])
		&& $arResult['TASKS_WAIT_CTRL_COUNTER']['VALUE']
	)
	{
		$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES']['VIEW_TASK_CATEGORY_WAIT_CTRL'];
		$arStrings[] = '<a href="' . $href . '" class="task-brown-text">'
			. $arResult['TASKS_WAIT_CTRL_COUNTER']['VALUE']
			. ' '
			. GetMessage(
				'TASKS_PANEL_EXPLANATION_WAIT_CTRL_TASKS_SUFFIX_PLURAL_'
				. $arResult['TASKS_WAIT_CTRL_COUNTER']['PLURAL']
			)
			. '</a>';
	}

	if (
		isset($arResult['TASKS_WO_DEADLINE_COUNTER']['VALUE'])
		&& $arResult['TASKS_WO_DEADLINE_COUNTER']['VALUE']
	)
	{
		$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES']['VIEW_TASK_CATEGORY_WO_DEADLINE'];
		$woDlString = '<a href="' . $href . '" class="task-brown-text">'
			. $arResult['TASKS_WO_DEADLINE_COUNTER']['VALUE']
			. ' '
			. GetMessage(
				'TASKS_PANEL_EXPLANATION_WO_DEADLINE_TASKS_SUFFIX_PLURAL_'
				. $arResult['TASKS_WO_DEADLINE_COUNTER']['PLURAL']
			);
		$roles = $arResult['VIEW_STATE']['ROLES'];
		if($roles['VIEW_ROLE_RESPONSIBLE']['SELECTED'] == 'Y')
		{
			$woDlString .= ' '.GetMessage('TASKS_PANEL_EXPLANATION_WO_DEADLINE_TASKS_RESPONSIBLE');
		}
		elseif($roles['VIEW_ROLE_ORIGINATOR']['SELECTED'] == 'Y')
		{
			$woDlString .= ' '.GetMessage('TASKS_PANEL_EXPLANATION_WO_DEADLINE_TASKS_ORIGINATOR');
		}
		$woDlString .= '</a>';
		$arStrings[] = $woDlString;
	}

	$stringsCount = count($arStrings);
	if ($stringsCount)
	{
		?>
		<div class="task-top-panel-tre">
			<div class="task-main-notification-icon-counter"><?
				echo $arResult['SELECTED_ROLE_COUNTER']['VALUE'];
				?></div>
			<span><? echo GetMessage('TASKS_PANEL_EXPLANATION_PREFIX'); ?></span>
			<?
			$stringsPrinted = 0;

			foreach ($arStrings as $string)
			{
				echo $string;
				$stringsPrinted++;

				$stringsRemain = $stringsCount - $stringsPrinted;

				if ($stringsRemain == 1)
					echo ' ' . GetMessage('TASKS_PANEL_EXPLANATION_AND_WORD') . ' ';
				elseif ($stringsRemain >= 2)
					echo ', ';
			}
			?>
		</div>
		<?
	}
}

if ($arParams['SHOW_FILTER_BAR'] === 'Y')
{
	$selectedRoleCodename = $arResult['VIEW_STATE']['ROLE_SELECTED']['CODENAME'];
	$categoryHref = $arParams['SECTION_URL_PREFIX'].$arResult['VIEW_HREFS']['TASK_CATEGORIES'][$arResult['VIEW_STATE']['TASK_CATEGORY_SELECTED']['CODENAME']];

	// names
	if ($arResult['F_CREATED_BY'])
	{
		if ($arResult['F_CREATED_BY'] == $USER->getId())
			$creatorName = GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_RESPONSIBLE_IS_ME');
		else
			$creatorName = htmlspecialcharsbx($arResult['~USER_NAMES'][$arResult['F_CREATED_BY']]);
	}
	else
		$creatorName = GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_ANY_ORIGINATOR');

	if ($arResult['F_RESPONSIBLE_ID'])
		$responsibleName = htmlspecialcharsbx($arResult['~USER_NAMES'][$arResult['F_RESPONSIBLE_ID']]);
	else
		$responsibleName = GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_ANY_RESPONSIBLE');

	$currentSortingName = "---";
	foreach ($arParams['SORTING'] as $sortItem)
	{
		if ($sortItem["SELECTED"])
		{
			$currentSortingName = GetMessage("TASKS_LIST_COLUMN_".$sortItem["INDEX"]);
			break;
		}
	}

	$projectId = 0;
	$projectName = GetMessage("TASKS_QUICK_IN_GROUP");
	if (isset($arParams["~GROUP"]))
	{
		$projectId = $arParams["~GROUP"]["ID"];
		$projectName = $arParams["~GROUP"]["NAME"];
	}
	?>
	<div class="task-top-notification" id="task-new-item-notification">
		<div class="task-top-notification-inner">
			<?=GetMessage("TASKS_QUICK_FORM_AFTER_SAVE_MESSAGE", array("#TASK_NAME#" => '<span class="task-top-notification-message" id="task-new-item-message"></span>'))?>
			<a href="" class="task-top-notification-link" id="task-new-item-open"><?=GetMessage("TASKS_QUICK_FORM_OPEN_TASK")?></a>
			<span class="task-top-notification-link" id="task-new-item-highlight"><?=GetMessage("TASKS_QUICK_FORM_HIGHLIGHT_TASK")?></span>
		</div>
		<span class="task-top-panel-tab-close task-top-panel-tab-close-active task-top-notification-hide" id="task-new-item-notification-hide"></span>
	</div>
	<div class="task-top-panel-create">
		<div class="task-top-panel-righttop" id="task-new-item">
			<form id="task-new-item-form" action="">
			<span class="task-top-panel-create-container">
				<input type="text" autocomplete="off" placeholder="<?=GetMessage("TASKS_RESPONSIBLE")?>"
					tabindex="3" id="task-new-item-responsible" name="task-new-item-responsible"
					value="<?
					echo tasksFormatName(
						$arParams["~USER"]["NAME"],
						$arParams["~USER"]["LAST_NAME"],
						$arParams["~USER"]["LOGIN"],
						$arParams["~USER"]["SECOND_NAME"],
						$arParams["NAME_TEMPLATE"]
					);
					?>">
				<input type="hidden" id="task-new-item-responsible-id" value="<? echo $arParams["USER_ID"]?>">
			</span>
			<span class="task-top-panel-create-container">
				<input type="text" autocomplete="off" placeholder="<?=GetMessage("TASKS_QUICK_DEADLINE")?>" tabindex="2"
					id="task-new-item-deadline"
					name="task-new-item-deadline"
					data-default-hour="<?=intval($arParams["COMPANY_WORKTIME"]["END"]["H"])?>"
					data-default-minute="<?=intval($arParams["COMPANY_WORKTIME"]["END"]["M"])?>">
			</span>
			<span class="task-top-panel-create-container task-top-panel-create-container-big">
				<span class="task-top-panel-create-menu" id="task-new-item-menu"></span>
				<input type="text" placeholder="<?=GetMessage("TASKS_QUICK_FORM_TITLE_PLACEHOLDER")?>" tabindex="1" id="task-new-item-title">
			</span>
			<span class="task-top-panel-middle">
				<span class="task-top-panel-leftmiddle" id="task-new-item-description-block">
					<span id="task-new-item-project-link" class="task-top-panel-tab"><?=$projectName?></span><span class="task-top-panel-tab-close<?=($projectId > 0 ? " task-top-panel-tab-close-active" : "")?>" id="task-new-item-project-clearing"></span><span class="task-top-panel-tab task-top-panel-leftmiddle-description" id="task-new-item-description-link" href=""><?=GetMessage("TASKS_QUICK_DESCRIPTION")?></span>
					<input type="hidden" id="task-new-item-project-id" value="<?=$projectId?>">
					<textarea cols="30" rows="10" placeholder="<?=GetMessage("TASKS_QUICK_FORM_DESC_PLACEHOLDER")?>" tabindex="4" id="task-new-item-description"></textarea>
				</span>
				<button class="ui-btn-light-border" id="task-new-item-save"><?=GetMessage("TASKS_QUICK_SAVE")?></button>
				<button class="ui-btn ui-btn-link" id="task-new-item-cancel"><?=GetMessage("TASKS_QUICK_CANCEL")?></button>
			</span>
			</form><?
			$canAddMailUsers = (
				\Bitrix\Main\ModuleManager::isModuleInstalled('mail')
				&& \Bitrix\Main\ModuleManager::isModuleInstalled('intranet')
				&& (
					!\Bitrix\Main\Loader::includeModule('bitrix24')
					|| \CBitrix24::isEmailConfirmed()
				)
			);
			?><script>
				new BX.Tasks.QuickForm("task-new-item", {
					nameTemplate: "<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>",
					filter: "<?=CUtil::JSEscape(serialize($arParams["FILTER"]))?>",
					order: "<?=CUtil::JSEscape(serialize($arParams["ORDER"]))?>",
					navigation: "<?=CUtil::JSEscape(serialize($arParams["NAVIGATION"]))?>",
					select: "<?=CUtil::JSEscape(serialize($arParams["SELECT"]))?>",
					ganttMode: <?= (isset($arParams["GANTT_MODE"]) ? "true" : "false")?>,
					destination: <?=CUtil::PhpToJSObject($arResult["DESTINATION"])?>,
					canAddMailUsers: <?=CUtil::PhpToJSObject($canAddMailUsers)?>,
					currentGroupId: <?=(int)$arResult['GROUP_ID']?>,
					canManageTask: <?=CUtil::PhpToJSObject(\Bitrix\Tasks\Util\Restriction::canManageTask())?>,
					messages: {
						taskInProject: "<?=GetMessageJs("TASKS_QUICK_IN_GROUP")?>"
					}
				});
			</script>
		</div>
		<div class="task-top-panel-leftbottom">
		<span class="task-top-panel-two-inright">
			<span class="task-top-panel-create-text"><?=GetMessage("TASKS_PANEL_SORTED_BY")?>:</span>
			<span class="task-top-panel-create-link" id="task-top-panel-sorting-selector"><span<?if (LANGUAGE_ID !== "de"):?> style="text-transform: lowercase"<?endif?>><?=$currentSortingName?></span></span>
			<span id="task-top-panel-view-mode-selector" class="webform-small-button webform-small-button-transparent bx-filter-button">
				<span class="webform-small-button-text"><?
					$selectedViewCodename = $arResult['VIEW_STATE']['VIEW_SELECTED']['CODENAME'];
					echo $arResult['VIEW_STATE']['VIEWS'][$selectedViewCodename]['SHORT_TITLE'];
					?></span><span class="webform-small-button-icon"></span>
			</span>
		</span>

		<span class="task-top-panel-inleft">

			<?
			if ($arResult['MARK_SECTION_ALL'] === 'Y') // "all" + advanced filter
			{
				?><div class="task-main-top-menu-advanced-filter">&nbsp;<?

				$filterName = '';
				if($arParams['SELECTED_PRESET_NAME'] <> '')
				{
					$filterName .= ': '.$arParams['SELECTED_PRESET_NAME'];
				}

				if ($arParams["VIEW_TYPE"] == "gantt")
				{
					?><span class="webform-small-button task-list-toolbar-filter webform-small-button-transparent bx-filter-button" onclick="showGanttFilter(this)"><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?
						echo GetMessage("TASK_TOOLBAR_FILTER_BUTTON") . $filterName;
						?></span><span class="webform-small-button-icon"></span><span class="webform-small-button-right"></span></span><?
				}
				else
				{
					?><span class="webform-small-button task-list-toolbar-filter webform-small-button-transparent bx-filter-button" onclick="showTaskListFilter(this)"><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?
						echo GetMessage("TASK_TOOLBAR_FILTER_BUTTON") . $filterName;
						?></span><span class="webform-small-button-icon"></span><span class="webform-small-button-right"></span></span><?
				}
				?></div><?
			}
			else
			{
				?><span class="task-top-panel-create-text"><?=GetMessage("TASKS_PANEL_FILTER_STATUS_LABEL")?>:</span>
				<span id="task-top-panel-task-category-selector" class="task-top-panel-create-link"><span><?
						$selectedCategoryCodename = $arResult['VIEW_STATE']['TASK_CATEGORY_SELECTED']['CODENAME'];
						echo $arResult['VIEW_STATE']['TASK_CATEGORIES'][$selectedCategoryCodename]['TITLE'];
						?></span><?
				?></span>,<?

				$showCreatorSelector = true;
				$showResponsibleSelector = true;
				if ($arResult["SELECTED_SECTION_NAME"] === "VIEW_SECTION_ROLES" &&
					in_array($selectedRoleCodename, array("VIEW_ROLE_RESPONSIBLE", "VIEW_ROLE_ORIGINATOR")))
				{
					$showCreatorSelector = $selectedRoleCodename === "VIEW_ROLE_RESPONSIBLE";
					$showResponsibleSelector = $selectedRoleCodename === "VIEW_ROLE_ORIGINATOR";
					?><span class="task-top-panel-create-text task-top-panel-from-to"><?= ($showCreatorSelector ? GetMessage("TASKS_PANEL_HUMAN_FILTER_STRING_FROM") : GetMessage("TASKS_PANEL_HUMAN_FILTER_STRING_FOR"))?></span><?
				}
				else
				{
					?><span class="task-top-panel-create-link task-top-panel-switch" id="task-top-panel-from-for-switch">
						<span data-bx-ui-id="from-for-switch-label" data-label="FROM"><?=GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_FROM')?></span>
						<span data-bx-ui-id="from-for-switch-label" data-label="FOR"><?=GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_FOR')?></span>
					</span><?
				}

				if ($showCreatorSelector)
				{
					?><span id="task-top-panel-task-originator-selector" class="task-top-panel-create-link"><span><?=$creatorName?></span></span><?
				}

				if ($showResponsibleSelector)
				{
					?><span id="task-top-panel-task-responsible-selector" class="task-top-panel-create-link"><span><?=$responsibleName?></span></span><?
				}
			}
			?>
		</span>
	</div>
	</div>

	<?if($taskListGlobalOpts['enable_gantt_hint'] != 'N' && $taskListUserOpts['enable_gantt_hint'] != 'N' && $arParams["VIEW_TYPE"] == "gantt"):?>
		<div class="task-widg-white-tooltip" id="gantt-hint">
			<div class="task-widg-white-text">
				<?=GetMessage('TASKS_PANEL_GANTT_HINT_TITLE')?>
			</div>
			<div class="task-widg-white-text">
				<?=GetMessage('TASKS_PANEL_GANTT_HINT_BODY')?>
			</div>
			<img src="<?=$templateFolder?>/images/gant-task-pict.png" class="task-widg-gant" alt="" />
			<div class="task-widg-white-close" id="gantt-hint-close"></div>
		</div>
	<?endif?>

	<script>
	(function(){
		BX.ready(function(){
			BX.Tasks.ListControlsNS.menu.create('views_menu');
			<?
			foreach ($arResult['VIEW_STATE']['VIEWS'] as $viewCodeName => $viewData)
			{
				$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['VIEWS'][$viewCodeName];
				?>BX.Tasks.ListControlsNS.menu.addItem(
					'views_menu',
					'<? echo CUtil::JSEscape($viewData['TITLE']); ?>',
					'<?=($viewData["SELECTED"] === "Y" ? "menu-popup-item-accept" : "task-menu-popup-no-icon")?>',
					'<? echo $href; ?>'
				);
				<?
			}
			?>

			BX.Tasks.ListControlsNS.menu.addDelimiter('views_menu');

			<?
			foreach ($arResult['VIEW_STATE']['SUBMODES'] as $submodeCodeName => $submodeData)
			{
				$cls = (($submodeData['SELECTED'] === 'Y') ? 'menu-popup-item-accept' : 'menu-popup-no-icon');
				$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['SUBMODES'][$submodeCodeName];

				if ($submodeCodeName === 'VIEW_SUBMODE_WITH_GROUPS')
				{
					continue; // inactive field should not be displayed

					$cls .= ' task-top-panel-disabled-menu-item';
					$href = "javascript:void(0);";
				}

				?>BX.Tasks.ListControlsNS.menu.addItem(
					'views_menu',
					'<? echo CUtil::JSEscape($submodeData['TITLE']); ?>',
					'<? echo $cls; ?>',
					'<? echo $href; ?>'
				);
				<?
			}
			?>

			BX.Tasks.ListControlsNS.menu.create('categories_menu');
			<?
			foreach ($arResult['VIEW_STATE']['TASK_CATEGORIES'] as $categoryCodeName => $categoryData)
			{
				$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES'][$categoryCodeName];

				?>BX.Tasks.ListControlsNS.menu.addItem(
					'categories_menu',
					'<? echo CUtil::JSEscape($categoryData['TITLE']); ?>',
					'menu-popup-no-icon',
					'<? echo $href; ?>'
				);
				<?

				// add delimiter after completed tasks
				if ($categoryCodeName === 'VIEW_TASK_CATEGORY_COMPLETED')
					break;
			}
			?>

			BX.Tasks.ListControlsNS.menu.create('sorting_menu');
			<?
			$currentSorting = null;
			foreach ($arParams['SORTING'] as $sortItem)
			{
				if ($sortItem["SELECTED"])
				{
					$currentSorting = $sortItem;
				}
				?>BX.Tasks.ListControlsNS.menu.addItem(
					'sorting_menu',
					'<? echo GetMessageJS("TASKS_LIST_COLUMN_".$sortItem["INDEX"]) ?>',
					'<?= ($sortItem["SELECTED"] ? " menu-popup-item-accept" : "task-menu-popup-no-icon") ?>',
					'<?= CUtil::JSEscape($sortItem["ASC_DIRECTION"] ? $sortItem["ASC_URL"] : $sortItem["DESC_URL"]) ?>'
				);<?
			}

			if ($currentSorting && $currentSorting["INDEX"] !== "SORTING")
			{
				?>
				BX.Tasks.ListControlsNS.menu.addDelimiter('sorting_menu');
				BX.Tasks.ListControlsNS.menu.addItem(
					'sorting_menu',
					'<?= GetMessageJS("TASKS_PANEL_SORTING_DIRECTION_ASC") ?>',
					'<?= ($currentSorting["ASC_DIRECTION"] ? "menu-popup-item-accept" : "task-menu-popup-no-icon")?>',
					'<?= CUtil::JSEscape($currentSorting["ASC_URL"]) ?>'
				);
				BX.Tasks.ListControlsNS.menu.addItem(
					'sorting_menu',
					'<?= GetMessageJS("TASKS_PANEL_SORTING_DIRECTION_DESC") ?>',
					'<?= (!$currentSorting["ASC_DIRECTION"] ? "menu-popup-item-accept" : "task-menu-popup-no-icon")?>',
					'<?= CUtil::JSEscape($currentSorting["DESC_URL"]) ?>'
				);<?
			}
			?>

			BX.Tasks.ListControlsNS.init();

			BX.bind(
				BX('task-top-panel-view-mode-selector'),
				'click',
				function(){ BX.Tasks.ListControlsNS.menu.show('views_menu', BX('task-top-panel-view-mode-selector')); }
			);

			BX.bind(
				BX('task-top-panel-task-category-selector'),
				'click',
				function(){ BX.Tasks.ListControlsNS.menu.show('categories_menu', BX('task-top-panel-task-category-selector'), {useAppendParams: true}); }
			);

			BX.bind(
				BX('task-top-panel-sorting-selector'),
				'click',
				function(){ BX.Tasks.ListControlsNS.menu.show('sorting_menu', BX('task-top-panel-sorting-selector')); }
			);


			var userInputs = [];

			if (BX('task-top-panel-task-originator-selector'))
			{
				userInputs.push({
					inputNode        : BX('task-top-panel-task-originator-selector'),
					menuId           : 'originators_menu',
					pathPrefix       : '<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('F_CREATED_BY', 'F_RESPONSIBLE_ID')));?>',
					strAnyOriginator : '<?=GetMessageJS('TASKS_PANEL_HUMAN_FILTER_STRING_ANY_ORIGINATOR');?>',
					operation        : 'tasks.list::getOriginators()',
					urlParamName     : 'F_CREATED_BY'
				});
			}

			if (BX('task-top-panel-task-responsible-selector'))
			{
				userInputs.push({
					inputNode        : BX('task-top-panel-task-responsible-selector'),
					menuId           : 'responsibles_menu',
					pathPrefix       : '<? echo CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('F_CREATED_BY', 'F_RESPONSIBLE_ID'))); ?>',
					strAnyOriginator : '<? echo GetMessageJS('TASKS_PANEL_HUMAN_FILTER_STRING_ANY_RESPONSIBLE'); ?>',
					operation        : 'tasks.list::getResponsibles()',
					urlParamName     : 'F_RESPONSIBLE_ID'
				});
			}

			BX.Tasks.ListControlsNS.createGanttHint();

			userInputs.forEach(function(userInput){
				BX.Tasks.ListControlsNS.menu.create(userInput.menuId);
				BX.Tasks.ListControlsNS.menu.addItem(
					userInput.menuId,
					userInput.strAnyOriginator,
					'menu-popup-no-icon',
					'<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('F_CREATED_BY', 'F_RESPONSIBLE_ID')))?>'
				);

				BX.bind(
					userInput.inputNode,
					'click',
					(function(userInput){
						var menuInited = false;

						return function(){
							if (menuInited)
							{
								BX.Tasks.ListControlsNS.menu.show(userInput.menuId, userInput.inputNode, {useAppendParams: true});
								return;
							}

							menuInited = true;

							BX.CJSTask.batchOperations(
								[{
									operation : userInput.operation,
									userId    : <? echo (int) $arParams['USER_ID']; ?>,
									groupId   : <? echo (int) $arParams['GROUP_ID']; ?>,
									rawState  : '<? echo CUtil::JSEscape($arResult['VIEW_STATE_RAW']); ?>'
								}],
								{
									callbackOnSuccess : (function(){
										return function(reply){
											var loggedInUserId = BX.message('USER_ID');
											var menuItems = [];

											reply['rawReply']['data'][0]['returnValue'].forEach(function(item){
												var menuItem = null;
												var name = '';

												if (item['USER_ID'] == loggedInUserId)
													name = '<? echo GetMessageJS("TASKS_PANEL_HUMAN_FILTER_STRING_RESPONSIBLE_IS_ME"); ?>';
												else
												{
													<?// NAME_FORMATTED may vary, but we want "LAST_NAME NAME" format?>
													var name = [];
													if(typeof item['LAST_NAME'] != 'undefined')
														name.push(item['LAST_NAME']);
													if(typeof item['NAME'] != 'undefined')
														name.push(item['NAME']);

													name = name.join(' ');
												}

												menuItem = {
													title : BX.util.htmlspecialchars(name) + ' (' + parseInt(item['TASKS_CNT']) + ')',
													path  : userInput.pathPrefix
														+ ((userInput.pathPrefix.indexOf('?') !== -1) ? '&' : '?')
														+ userInput.urlParamName
														+ '='
														+ parseInt(item['USER_ID'])
												};

												if (item['USER_ID'] == loggedInUserId)
													menuItems.unshift(menuItem);
												else
													menuItems.push(menuItem);
											});

											if (menuItems.length)
												BX.Tasks.ListControlsNS.menu.addDelimiter(userInput.menuId);

											menuItems.forEach(function(item){
												BX.Tasks.ListControlsNS.menu.addItem(
													userInput.menuId,
													item.title,
													'menu-popup-no-icon',
													item.path
												);
											});

											BX.Tasks.ListControlsNS.menu.show(userInput.menuId, userInput.inputNode, {useAppendParams: true});
										};
									})()
								}
							);
						};
					})(userInput)
				);
			});

			<?if($taskListUserOpts['enable_viewmode_hint'] != 'N' && $arParams['VIEW_TYPE'] == 'gantt'):?>

				BX.message(<?=CUtil::PhpToJSObject(array(
					'TASKS_PANEL_VM_HINT_TITLE' => GetMessage('TASKS_PANEL_VM_HINT_TITLE'),
					'TASKS_PANEL_VM_HINT_BODY' => GetMessage('TASKS_PANEL_VM_HINT_BODY'),
					'TASKS_PANEL_VM_HINT_DISABLE' => GetMessage('TASKS_PANEL_VM_HINT_DISABLE')
				))?>);

				BX.Tasks.ListControlsNS.createViewModeHint();

			<?endif?>
		});
	})();

	// from-for switch

	var switchFromFor = function(way){

		var sw = BX('task-top-panel-from-for-switch');

		if(typeof sw != 'undefined' && sw != null)
		{
			var buttons = sw.querySelectorAll('span');

			if(buttons != null)
			{
				// switch label itself
				for(var k = 0; k < buttons.length; k++)
				{
					var label = BX.data(buttons[k], 'label');

					if (label == way)
					{
						buttons[k].style.display = "inline-block";
					}
					else
					{
						buttons[k].style.display = "none";
					}
				}

				// switch buttons
				if(way == 'FOR')
				{
					BX('task-top-panel-task-originator-selector').style.display = "none";
					BX('task-top-panel-task-responsible-selector').style.display = "inline-block";
				}
				else
				{
					BX('task-top-panel-task-originator-selector').style.display = "inline-block";
					BX('task-top-panel-task-responsible-selector').style.display = "none";
				}

				BX.Tasks.ListControlsNS.params.appendUrlParams.SW_FF = way;
			}
		}
	}

	BX.bindDelegate(BX('task-top-panel-from-for-switch'), 'click', {tagName: 'span'}, function(){
		var label = BX.data(this, 'label');

		label = label == 'FOR' ? 'FROM' : 'FOR'; // invert on click

		switchFromFor.apply(window, [label]);
	});
	switchFromFor('<?=$arResult['FROM_FOR_SWITCH']?>');
	</script>
	<?
}
if(SITE_TEMPLATE_ID === "bitrix24")
{
	$this->EndViewTarget();
}