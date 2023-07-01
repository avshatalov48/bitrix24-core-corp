<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var $APPLICATION \CMain */
/** @var array $arResult */
/** @var array $arParams */
/** @var \CBitrixComponent $component */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\UI\ScopeDictionary;

Extension::load([
	'tasks',
	'ui.forms',
	'ui.layout-form',
	'ui.buttons.icons',
	'ui.cnt',
	'ui.dialogs.messagebox',
	'ui.icons.b24',
	'ui.draganddrop.draggable',
	'ui.label',
	'ui.entity-selector',
	'ui.short-view',
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

Extension::load('date');
Extension::load('sidepanel');
Extension::load('popup');

if (Loader::includeModule('pull'))
{
	Extension::load('pull.client');
}

if (Loader::includeModule('disk'))
{
	Asset::getInstance()->addJs('/bitrix/components/bitrix/disk.uf.file/templates/.default/script.js');

	Extension::load([
		'file_dialog',
		'mobile_uploader',
		'disk.document',
		'disk_external_loader',
		'uploader',
	]);
}

Asset::getInstance()->addCss('/bitrix/components/bitrix/tasks.interface.toolbar/templates/.default/style.css');

$isKanban = $isKanban ?? false;
$viewName = $this->getComponent()->getTemplatePage();

$messages = Loc::loadLanguageFile(__FILE__);

$isBitrix24Template = (SITE_TEMPLATE_ID === 'bitrix24');

/** @var Filter $filterInstance */
$filterInstance = $arResult['filterInstance'];

$filterId = $filterInstance->getId();
$filters = $filterInstance->getFilters();
$presets = Filter::getPresets($filterInstance);

if ($viewName === 'completed_sprint')
{
	unset($presets['filter_tasks_scrum']);
}

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass', ($bodyClass ? $bodyClass.' ' : '').
	'pagetitle-toolbar-field-view tasks-pagetitle-view '.
	'no-all-paddings no-background tasks-scrum-wrapper'
);

$popupMenuItems = [];
$scope = ScopeDictionary::SCOPE_TASKS_PLANNING;
switch ($viewName)
{
	case 'plan':
		$popupMenuItems = [
			[
				'tabId' => 'popupMenuOptions',
				'text' => Loc::getMessage('TASKS_SCRUM_PLAN_VIEW_BACKLOG'),
				'className' => ($arResult['displayPriority'] == 'backlog') ?
					'menu-popup-item-accept' : 'menu-popup-item-none',
				'onclick' => '"BX.Tasks.Scrum.Entry.setDisplayPriority(this, \'backlog\')"'
			],
			[
				'tabId' => 'popupMenuOptions',
				'text' => Loc::getMessage('TASKS_SCRUM_PLAN_VIEW_SPRINT'),
				'className' => ($arResult['displayPriority'] == 'sprint') ?
					'menu-popup-item-accept' : 'menu-popup-item-none',
				'onclick' => '"BX.Tasks.Scrum.Entry.setDisplayPriority(this, \'sprint\')"'
			],
		];
		break;
	case 'active_sprint':
	case 'completed_sprint':
		$popupMenuItems = [
			[
				'tabId' => 'popupMenuOptions',
				'html' => '<b>' . Loc::getMessage('KANBAN_SORT_TITLE_MY') . '</b>'
			],
			[
				'tabId' => 'popupMenuOptions',
				'html' => Loc::getMessage('KANBAN_SORT_ACTUAL').
					'<span class=\"menu-popup-item-sort-field-label\">'.
					Loc::getMessage("KANBAN_SORT_ACTUAL_RECOMMENDED_LABEL").'</span>',
				'className' => (isset($arResult['orderNewTask']) && $arResult['orderNewTask'] === 'actual') ?
					'menu-popup-item-accept' : 'menu-popup-item-none',
				'onclick' => '"BX.Tasks.Scrum.Kanban.onClickSort(this, \'actual\')"'
			],
			[
				'tabId' => 'popupMenuOptions',
				'html' => '<b>' . Loc::getMessage('KANBAN_SORT_TITLE') . '</b>'
			],
			[
				'tabId' => 'popupMenuOptions',
				'text' => Loc::getMessage('KANBAN_SORT_DESC'),
				'className' => (isset($arResult['orderNewTask']) && $arResult['orderNewTask'] === 'desc') ?
					'menu-popup-item-accept' : 'menu-popup-item-none',
				'onclick' => '"BX.Tasks.Scrum.Kanban.onClickSort(this, \'desc\')"'
			],
			[
				'tabId' => 'popupMenuOptions',
				'text' => Loc::getMessage('KANBAN_SORT_ASC'),
				'className' => (isset($arResult['orderNewTask']) && $arResult['orderNewTask'] === 'asc') ?
					'menu-popup-item-accept' : 'menu-popup-item-none',
				'onclick' => '"BX.Tasks.Scrum.Kanban.onClickSort(this, \'asc\')"'
			]
		];
		$scope = ScopeDictionary::SCOPE_TASKS_KANBAN_SPRINT;
		break;
}

$APPLICATION->includeComponent(
	'bitrix:tasks.interface.filter',
	'',
	[
		'FILTER_ID' => $filterId,
		'FILTER' => $filters,
		'PRESETS' => $presets,
		'TEMPLATES_LIST' => $arParams['TEMPLATES_LIST'] ?? null,
		'USER_ID' => $arParams['USER_ID'] ?? null,
		'GROUP_ID' => $arParams['GROUP_ID'] ?? null,
		'SPRINT_ID' => (isset($arResult['completedSprintId']) ? $arResult['completedSprintId'] : -1),
		'MENU_GROUP_ID' => $arParams['GROUP_ID'] ?? null,
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'] ?? null,
		'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'] ?? null,
		'SHOW_QUICK_FORM_BUTTON' => 'N',
		'PROJECT_VIEW' => ((array_key_exists('PROJECT_VIEW', $arParams) && $arParams['PROJECT_VIEW']) ? 'Y' : 'N'),
		'USE_GROUP_SELECTOR' => ((array_key_exists('PROJECT_VIEW', $arParams) && $arParams['PROJECT_VIEW']) ? 'Y' : 'N'),
		'USE_EXPORT' => 'N',
		'SHOW_CREATE_TASK_BUTTON' => 'Y',
		'POPUP_MENU_ITEMS' => $popupMenuItems,
		'SCOPE' => $scope,
	],
	$component,
	['HIDE_ICONS' => true]
);

if ($isBitrix24Template)
{
	$this->setViewTarget('below_pagetitle');
}
?>

<div class="task-interface-toolbar">
	<div id="tasks-scrum-switcher" class="task-interface-toolbar--item --visible"></div>
	<?php
		if ($viewName === 'plan')
		{
			$APPLICATION->IncludeComponent(
				'bitrix:tasks.interface.counters',
				'',
				[
					'USER_ID' => (int) $arParams['OWNER_ID'],
					'GROUP_ID' => (int) $arParams['GROUP_ID'],
					'ROLE' => 'view_all',
					'COUNTERS' => [
						CounterDictionary::COUNTER_NEW_COMMENTS,
						CounterDictionary::COUNTER_GROUP_COMMENTS,
					],
					'GRID_ID' => $filterId,
					'FILTER_FIELD' => 'PROBLEM',
					'SCOPE' => $scope,
				],
				$component
			);
		}
	?>
	<div id="tasks-scrum-sprint-stats" class=
		"tasks-scrum-sprint-stats task-interface-toolbar--item --without-bg --align-right"></div>
	<div id="tasks-scrum-right-container" class="task-interface-toolbar--item --align-right">
	</div>
</div>

<?php
if ($isBitrix24Template)
{
	$this->EndViewTarget();
}
