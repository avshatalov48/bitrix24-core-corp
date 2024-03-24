<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var $APPLICATION CMain */
/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponent $component */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Kanban\Sort\Factory\MenuFactory;
use Bitrix\Tasks\Kanban\Sort\Item\MenuItem;
use Bitrix\Tasks\Kanban\Sort\Item\SortTitle;
use Bitrix\Tasks\Kanban\Sort\Menu;
use Bitrix\Tasks\UI\ScopeDictionary;

Extension::load([
	'tasks.kanban-sort',
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
	'ui.analytics',
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

$order = $arResult['orderNewTask'] ?? 'actual';
$isCustomOrder = $order === 'asc' || $order === 'desc';
if ($viewName === 'completed_sprint')
{
	unset($presets['filter_tasks_scrum']);
}

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass', ($bodyClass ? $bodyClass . ' ' : '') .
	'pagetitle-toolbar-field-view tasks-pagetitle-view ' .
	'no-all-paddings no-background tasks-scrum-wrapper'
);

$styleAccepted = 'menu-popup-item-accept';
$styleNone = 'menu-popup-item-none';
$menuId = 'popupMenuOptions';
$displayPriority = $arResult['displayPriority'] ?? '';
$popupMenuItems = [];
$scope = ScopeDictionary::SCOPE_TASKS_PLANNING;
switch ($viewName)
{
	case 'plan':
		$menu = new Menu($menuId);

		$backlogView = (new MenuItem())
			->setOnClick('"BX.Tasks.Scrum.Entry.setDisplayPriority(this, \'backlog\')"')
			->setClassName($displayPriority === 'backlog' ? $styleAccepted : $styleNone)
			->setHtml(Loc::getMessage('TASKS_SCRUM_PLAN_VIEW_BACKLOG'));

		$sprintView = (new MenuItem())
			->setOnClick('"BX.Tasks.Scrum.Entry.setDisplayPriority(this, \'sprint\')"')
			->setClassName($displayPriority === 'sprint' ? $styleAccepted : $styleNone)
			->setHtml(Loc::getMessage('TASKS_SCRUM_PLAN_VIEW_SPRINT'));
		$menu->addItem($backlogView)->addItem($sprintView);
		$popupMenuItems = $menu->toArray();
		break;
	case 'active_sprint':
		$menu = new Menu($menuId, $order);

		$sortDesc = (new MenuItem())
			->setOnClick('BX.delegate(BX.Tasks.KanbanSort.getInstance().selectCustomOrder)')
			->setType(MenuItem::TYPE_SUB)
			->setOrder(MenuItem::SORT_DESC)
			->setClassName($order === MenuItem::SORT_DESC ? $styleAccepted : $styleNone)
			->setHtml(
				'<span data-id=\'kanban-sort-desc\'>' . Loc::getMessage('TASKS_KANBAN_SORT_DESC') . '</span>'
			);

		$sortAsc = (new MenuItem())
			->setOnClick('BX.delegate(BX.Tasks.KanbanSort.getInstance().selectCustomOrder)')
			->setType(MenuItem::TYPE_SUB)
			->setOrder(MenuItem::SORT_ASC)
			->setClassName($order === MenuItem::SORT_ASC ? $styleAccepted : $styleNone)
			->setHtml(
				'<span data-id=\'kanban-sort-asc\'>' . Loc::getMessage('TASKS_KANBAN_SORT_ASC') . '</span>'
			);

		$tasksTitle = (new MenuItem())
			->setType(MenuItem::TYPE_SUB)
			->setHtml('<b>' . Loc::getMessage('TASKS_KANBAN_NEW_TASKS_SORT_TITLE') . '</b>');

		$sortTitle = (new MenuItem())
			->setHtml('<b>' . Loc::getMessage('TASKS_KANBAN_SORT_TITLE') . '</b>');

		$sortActual = (new MenuItem())
			->setOnClick('BX.delegate(BX.Tasks.KanbanSort.getInstance().disableCustomSort)')
			->setOrder(MenuItem::SORT_ACTUAL)
			->setClassName($order === MenuItem::SORT_ACTUAL ? $styleAccepted : $styleNone)
			->setHtml(
				Loc::getMessage('TASKS_KANBAN_SORT_ACTUAL')
				. '<span class=\"menu-popup-item-sort-field-label\">'
				. Loc::getMessage("TASKS_KANBAN_SORT_ACTUAL_RECOMMENDED_LABEL")
				. '</span>'
			);

		$sortMy = (new MenuItem())
			->setOnClick('BX.delegate(BX.Tasks.KanbanSort.getInstance().enableCustomSort)')
			->setClassName($menu->isCustomSort() ? $styleAccepted : $styleNone)
			->addItem($tasksTitle)
			->addItem($sortDesc)
			->addItem($sortAsc)
			->setHtml(Loc::getMessage('TASKS_KANBAN_SORT_MY_SORT'));

		$menu
			->addItem($sortTitle)
			->addItem($sortActual)
			->addItem($sortMy)
			->addItem($tasksTitle)
			->addItem($sortDesc)
			->addItem($sortAsc);

		$popupMenuItems = $menu->toArray();
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
		'SPRINT_ID' => ($arResult['completedSprintId'] ?? -1),
		'MENU_GROUP_ID' => $arParams['GROUP_ID'] ?? null,
		'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['PATH_TO_USER_TASKS_TEMPLATES'] ?? null,
		'PATH_TO_GROUP_TASKS_TASK' => $arParams['PATH_TO_GROUP_TASKS_TASK'] ?? null,
		'SHOW_QUICK_FORM_BUTTON' => 'N',
		'PROJECT_VIEW' => ((array_key_exists('PROJECT_VIEW', $arParams) && $arParams['PROJECT_VIEW']) ? 'Y' : 'N'),
		'USE_GROUP_SELECTOR' => ((array_key_exists('PROJECT_VIEW', $arParams) && $arParams['PROJECT_VIEW']) ? 'Y'
			: 'N'),
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
					'USER_ID' => (int)$arParams['OWNER_ID'],
					'GROUP_ID' => (int)$arParams['GROUP_ID'],
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
?>
<script>
	BX.ready(function()
	{

	})
</script>