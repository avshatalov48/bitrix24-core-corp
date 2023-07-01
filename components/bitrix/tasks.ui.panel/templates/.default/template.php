<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-paddings grid-mode pagetitle-toolbar-field-view flexible-layout');

$arParams['PATH_TO_TASKS_EDIT'] = str_replace(
									array('#action#', '#task_id#'),
									array('edit', 0),
									$arParams['PATH_TO_TASKS_TASK']
								);

// top menu start ----------
$this->SetViewTarget('above_pagetitle', 100);
$menuItems = array();
$menuItemsAdd = array();
// base items
foreach ($arResult['ROLES'] as $roleId => $role)
{
	$menuItemsAdd[] = array(
		'TEXT' => $role['TEXT'],
		'URL' => $arParams['PATH_TO_TASKS_LIST'] . '?' . $role['HREF'],
		'ID' => strtolower($roleId),
		'IS_ACTIVE' => $role['IS_ACTIVE'],
		'COUNTER' => $role['COUNTER'],
		'COUNTER_ID' => $role['COUNTER_ID']
	);
}
// additional items
$sysPages = array('PLAN', 'PROJECTS', 'REPORT');
foreach ($sysPages as $page)
{
	if (
		isset($arParams['PATH_TO_TASKS_' . $page]) &&
		$arParams['PATH_TO_TASKS_' . $page] != ''
	)
	{
		$menuItemsAdd[$page] = array(
			'TEXT' => Loc::getMessage('TASKS_MENU_' . $page),
			'URL' => $arParams['PATH_TO_TASKS_' . $page],
			'ID' => strtolower($page),
			'IS_ACTIVE' => 0
		);
	}
}
// promo-link to applications (B24)
if ($arResult['BX24_RU_ZONE'])
{
	$menuItemsAdd['VIEW_APPS'] = array(
		'TEXT' => Loc::getMessage('TASKS_MENU_APPLICATIONS'),
		'URL' => '/marketplace/category/tasks/',
		'ID' => 'view_apps',
		'IS_ACTIVE' => 0
	);
}
// show
$menuId = 'menu_' . md5(implode('|', array_keys($menuItemsAdd)));
$menuItems = array_values(array_merge($menuItems, $menuItemsAdd));
$APPLICATION->IncludeComponent(
	'bitrix:main.interface.buttons',
	'',
	array(
		'ID' => $menuId,
		'ITEMS' => $menuItems,
	),
	$component,
	array('HIDE_ICONS' => true)
);
$this->EndViewTarget();

// filter
$APPLICATION->IncludeComponent(
	'bitrix:tasks.ui.filter',
	'',
	array(
		'FILTER_CLASS' => $arParams['FILTER_CLASS'],
		'ENABLE_LIVE_SEARCH' => true,
		'NAVIGATION_BAR' => array(
			'ITEMS' => $navItems,
			'BINDING' => array(
				'category' => 'tasks.navigation',
				'name' => 'index',
				'key' => 'tasks'
			)
		)
	),
	$component,
	array('HIDE_ICONS' => true)
);
// top menu end  ----------
//
// add button
$this->SetViewTarget('inside_pagetitle', 100);
?>
<div class="pagetitle-container pagetitle-align-right-container">
	<a href="<?= $arParams['PATH_TO_TASKS_EDIT']?>">
		<span class="webform-small-button webform-small-button-blue bx24-top-toolbar-add"><?= Loc::getMessage('TASKS_ADD_NEW')?></span>
	</a>
</div>
<?
$this->EndViewTarget();

// navigation bar
$navigationActive = isset($arParams['NAVIGATION_BAR_ACTIVE'])
					? strtoupper($arParams['NAVIGATION_BAR_ACTIVE'])
					: '';
$navItems = array();
foreach ($codes = array('LIST', 'KANBAN', 'GANTT', 'WIDGET') as $code)
{
	$navItems[] = array(
					'id' => $code,
					'name' => Loc::getMessage('TASKS_' . $code),
					'active' => $navigationActive == $code,
					'url' => isset($arParams['PATH_TO_TASKS_' . $code])
							? $arParams['PATH_TO_TASKS_' . $code]
							: ''
				);
}
if (!empty($navItems))
{
	$this->SetViewTarget('below_pagetitle', 100);
	?><div class="tasks-view-switcher pagetitle-align-right-container">
	<div class="tasks-view-switcher-name"><?= Loc::getMessage('TASKS_FILTER_NAV_BAR_TITLE')?>:</div>
	<div class="tasks-view-switcher-list"><?
		$itemQty = 0;
		foreach($navItems as $barItem)
		{
			$itemQty++;
			$itemID = isset($barItem['id']) ? $barItem['id'] : $itemQty;
			$itemElementID = strtolower("{$gridID}_{$itemID}");
			$className = 'tasks-view-switcher-list-item';
			if (isset($barItem['active']) && $barItem['active'])
			{
				$className = "{$className} tasks-view-switcher-list-item-active";
			}
			?><div id="<?=htmlspecialcharsbx($itemElementID)?>" class="<?=$className?>">
				<a href="<?= htmlspecialcharsbx(isset($barItem['url']) ? $barItem['url'] : '')?>">
					<?=htmlspecialcharsbx(isset($barItem['name']) ? $barItem['name'] : $itemID)?>
				</a>
			</div><?
		}
		?></div>
	</div>
	<?
	$this->EndViewTarget();
}