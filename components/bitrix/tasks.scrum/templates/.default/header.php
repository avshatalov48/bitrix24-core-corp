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
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;
use Bitrix\Tasks\Helper\Filter;

Extension::load([
	'ui.forms',
	'ui.buttons.icons',
	'ui.cnt',
	'ui.dialogs.messagebox',
	'ui.icons.b24',
	'ui.draganddrop.draggable',
	'ui.label',
	'ui.entity-selector',
	'ui.confetti',
]);
Extension::load('date');
Extension::load('sidepanel');
Extension::load('popup');
Extension::load(['amcharts4', 'amcharts4_theme_animated']);

if (Loader::includeModule('pull'))
{
	Extension::load('pull.client');
}

if (Loader::includeModule('disk'))
{
	Asset::getInstance()->addJs('/bitrix/components/bitrix/disk.uf.file/templates/.default/script.js');
	Extension::load([
		'mobile_uploader',
		'disk.document',
		'disk_external_loader',
	]);
}

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

if ($arParams['PROJECT_VIEW'])
{
	$APPLICATION->includeComponent(
		'bitrix:tasks.interface.topmenu',
		'',
		[
			'GRID_ID' => $filterId,
			'FILTER_ID' => $filterId,
			'USER_ID' => $arParams['USER_ID'],
			'GROUP_ID' => $arParams['GROUP_ID'],
			'PROJECT_VIEW' => ($arParams['PROJECT_VIEW'] ? 'Y' : 'N'),
			'SECTION_URL_PREFIX' => '',

			'USE_AJAX_ROLE_FILTER' => $arParams['USE_AJAX_ROLE_FILTER'],
			'MARK_ACTIVE_ROLE' => $arParams['MARK_ACTIVE_ROLE'],
			'MARK_SECTION_ALL' => $arParams['MARK_SECTION_ALL'],
			'MARK_SPECIAL_PRESET' => $arParams['MARK_SPECIAL_PRESET'],
			'MARK_TEMPLATES' => $arParams['MARK_TEMPLATES'],
			'MARK_SECTION_PROJECTS' => $arParams['MARK_SECTION_PROJECTS'],

			'PATH_TO_GROUP_TASKS' => $arParams['PATH_TO_GROUP_TASKS'],
			'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'],
			'PATH_TO_GROUP_TASKS_VIEW' => $arParams['PATH_TO_GROUP_TASKS_VIEW'],
			'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'],

			'PATH_TO_USER_TASKS' => $arParams['PATH_TO_USER_TASKS'],
			'PATH_TO_USER_TASKS_TASK' => $arParams['PATH_TO_USER_TASKS_TASK'],
			'PATH_TO_USER_TASKS_VIEW' => $arParams['PATH_TO_USER_TASKS_VIEW'],
			'PATH_TO_USER_TASKS_REPORT' => $arParams['PATH_TO_USER_TASKS_REPORT'],
			'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
			'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],

			'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT'],
			'DEFAULT_ROLEID' => $arParams['DEFAULT_ROLEID'],
		],
		$component,
		['HIDE_ICONS' => true]
	);
}

$APPLICATION->includeComponent(
	'bitrix:tasks.interface.filter',
	'',
	[
		'FILTER_ID' => $filterId,
		'FILTER' => $filters,
		'PRESETS' => $presets,
		'TEMPLATES_LIST' => $arParams['TEMPLATES_LIST'],//todo
		'USER_ID' => $arParams['USER_ID'],
		'GROUP_ID' => $arParams['GROUP_ID'],
		'SPRINT_ID' => ($arResult['completedSprintId'] ? $arResult['completedSprintId'] : -1),
		'MENU_GROUP_ID' => $arParams['GROUP_ID'],
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
		'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'],
		'SHOW_QUICK_FORM_BUTTON' => 'N',
		'PROJECT_VIEW' => ($arParams['PROJECT_VIEW'] ? 'Y' : 'N'),
		'USE_GROUP_SELECTOR' => ($arParams['PROJECT_VIEW'] ? 'Y' : 'N'),
		'USE_EXPORT' => 'N',
		'SHOW_CREATE_TASK_BUTTON' => 'Y',
		'POPUP_MENU_ITEMS' =>
			($isKanban)
				? [
				[
					'tabId' => 'popupMenuOptions',
					'html' => '<b>' . Loc::getMessage('KANBAN_SORT_TITLE_MY') . '</b>'
				],
				[
					'tabId' => 'popupMenuOptions',
					'html' => Loc::getMessage('KANBAN_SORT_ACTUAL').
						'<span class=\"menu-popup-item-sort-field-label\">'.
						Loc::getMessage("KANBAN_SORT_ACTUAL_RECOMMENDED_LABEL").'</span>',
					'className' => ($arResult['orderNewTask'] == 'actual') ?
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
					'className' => ($arResult['orderNewTask'] == 'desc') ?
						'menu-popup-item-accept' : 'menu-popup-item-none',
					'onclick' => '"BX.Tasks.Scrum.Kanban.onClickSort(this, \'desc\')"'
				],
				[
					'tabId' => 'popupMenuOptions',
					'text' => Loc::getMessage('KANBAN_SORT_ASC'),
					'className' => ($arResult['orderNewTask'] == 'asc') ?
						'menu-popup-item-accept' : 'menu-popup-item-none',
					'onclick' => '"BX.Tasks.Scrum.Kanban.onClickSort(this, \'asc\')"'
				]
			] : [],
	],
	$component,
	['HIDE_ICONS' => true]
);

if ($isBitrix24Template)
{
	$this->setViewTarget('below_pagetitle');
}

$planTabActiveClass = ($arResult['views']['plan']['active'] ? 'tasks-scrum-switcher-tab-active' : '');
$activeTabActiveClass = ($arResult['views']['activeSprint']['active'] ? 'tasks-scrum-switcher-tab-active' : '');
$completedTabActiveClass = ($arResult['views']['completedSprint']['active'] ? 'tasks-scrum-switcher-tab-active' : '');
?>

<div class="tasks-scrum-switcher">
	<div class="tasks-scrum-switcher-views">
		<a
			href="<?=HtmlFilter::encode($arResult['views']['plan']['url'])?>"
			class="tasks-scrum-switcher-tab <?= $planTabActiveClass ?>"
		><?= $arResult['views']['plan']['name']; ?></a>
		<a
			href="<?=HtmlFilter::encode($arResult['views']['activeSprint']['url'])?>"
			class="tasks-scrum-switcher-tab <?= $activeTabActiveClass ?>"
		><?= $arResult['views']['activeSprint']['name']; ?></a>
		<a
			href="<?=HtmlFilter::encode($arResult['views']['completedSprint']['url'])?>"
			class="tasks-scrum-switcher-tab <?= $completedTabActiveClass ?>"
		><?= $arResult['views']['completedSprint']['name']; ?></a>
	</div>
</div>

<div id="tasks-scrum-counters-container" class="tasks-scrum-counters-container"></div>

<?php if ($arResult['views']['plan']['active']): ?>
	<div class="tasks-scrum-active-sprint-stats"></div>
<?php endif; ?>
<?php if ($arResult['views']['activeSprint']['active'] && $arResult['activeSprintId'] > 0): ?>
	<div id="tasks-scrum-active-sprint-stats" class="tasks-scrum-active-sprint-stats"></div>
<?php endif; ?>
<?php if ($arResult['views']['completedSprint']['active'] && $arResult['completedSprintId'] > 0): ?>
	<div id="tasks-scrum-completed-sprint-title" class="tasks-scrum-completed-sprint-title"></div>
<?php endif; ?>

<div class="tasks-scrum-buttons">
	<?php if ($arResult['views']['plan']['active']): ?>
		<div id="tasks-scrum-team-speed-button-container" class="tasks-scrum-team-speed-button-container">
			<button class="ui-btn ui-btn-primary ui-btn-xs">
				<?=Loc::getMessage('TASKS_SCRUM_TEAM_SPEED_BUTTON');?>
			</button>
		</div>
	<?php endif; ?>
	<?php if ($arResult['views']['activeSprint']['active'] && $arResult['activeSprintId'] > 0): ?>
		<div id="tasks-scrum-actions-complete-sprint" class="tasks-scrum-actions-complete-sprint">
			<button class="ui-btn ui-btn-primary-dark ui-btn-round ui-btn-xs">
				<?=Loc::getMessage('TASKS_SCRUM_ACTIVE_SPRINT_BUTTON');?>
			</button>
			<button class="ui-btn ui-btn-primary ui-btn-round ui-btn-xs">
				<?=Loc::getMessage('TASKS_SCRUM_ACTIONS_COMPLETE_SPRINT');?>
			</button>
		</div>
	<?php endif; ?>
	<?php if ($arResult['views']['completedSprint']['active'] && $arResult['completedSprintId'] > 0): ?>
		<div id="tasks-scrum-completed-sprint-title" class="tasks-scrum-completed-sprint-title"></div>
		<div id="tasks-scrum-completed-sprint-chart" class="tasks-scrum-actions-complete-sprint">
			<button class="ui-btn ui-btn-primary-dark ui-btn-round ui-btn-xs">
				<?=Loc::getMessage('TASKS_SCRUM_ACTIVE_SPRINT_BUTTON');?>
			</button>
		</div>
	<?php endif; ?>
</div>

<?php
if ($isBitrix24Template)
{
	$this->EndViewTarget();
}
