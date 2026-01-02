<?php

/**
 * Bitrix vars
 *
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

use Bitrix\Main\UI\Extension;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
Extension::load('crm.timeline.menubar');

$guid = $arResult['guid'];

$menuItems = [];
$items = $arResult['items'];
$toolbarId = CUtil::JSEscape($guid);
/** @var Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item $item */
foreach ($items as $item)
{
	$menuItem = [
		'TEXT' => \Bitrix\Main\Text\HtmlFilter::encode($item->getName()),
		'TITLE' => \Bitrix\Main\Text\HtmlFilter::encode($item->getTitle()),
		'ID' => $item->getId(),
		'URL' => 'javascript:void(0);',
		'ON_CLICK' => "BX.Crm.Timeline.MenuBar.getById('" . $toolbarId . "').onMenuItemClick('" . \CUtil::JSEscape($item->getId()) . "')",
		'IS_NEW' => $item->isNew(),
	];

	if (
		\Bitrix\Main\Loader::includeModule('tasks')
		&& $item instanceof \Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item\Task
	)
	{
		$analytics = \Bitrix\Tasks\Helper\Analytics::getInstance($USER->getId());

		$subSection = str_replace('_section', '', $arResult["extras"]["analytics"]["c_section"]);

		$analyticsData = [
			'tool' => $analytics::TOOL,
			'category' => $analytics::TASK_CATEGORY,
			'event' => $analytics::EVENT['click_create'],
			'type' => $analytics::TASK_TYPE,
			'section' => $analytics::SECTION['crm'],
			'subSection' => $subSection,
			'element' => $analytics::ELEMENT['create_button'],
			'p2' => $analytics->getUserTypeParameter(),
		];

		if (method_exists($analytics, 'getIsDemoParameter'))
		{
			$analyticsData['p1'] = $analytics->getIsDemoParameter();
		}

		$menuItem['ON_CLICK'] .= "; BX.Runtime.loadExtension('ui.analytics').then(() => {
			BX.UI.Analytics.sendData(" . \Bitrix\Main\Web\Json::encode($analyticsData) .");
		});";
	}

	if ($item->hasTariffRestrictions())
	{
		$menuItem['IS_LOCKED'] = true;
	}

	$menuItems[] = $menuItem;

	$item->loadAssets();
}

$menuId = $arParams['MENU_ID'] ?? 'timeline_toolbar-menu';
?>
<div class="crm-entity-stream-section-menu"><?php
	$APPLICATION->IncludeComponent(
		'bitrix:main.interface.buttons',
		'',
		[
			'ID' => $menuId,
			'ITEMS' => $menuItems,
			'EDIT_MODE' => $arResult['editMode'] ?? false,
			'THEME' => 'compact',
		]
	);
?></div>
<?php
$editorsContainerId = $guid . '_editors_container';
$jsParams = [
	'entityTypeId' => $arResult['entityTypeId'],
	'entityId' => $arResult['entityId'],
	'entityCategoryId' => $arResult['entityCategoryId'],
	'isReadonly' => $arResult['isReadonly'],
	'extras' => $arResult['extras'] ?? [],
	'containerId' => $editorsContainerId,
	'menuId' => $menuId,
	'items' => [],
];
foreach ($items as $item)
{
	$settings = $item->getSettings();
	$jsParams['items'][] = [
		'id' => $item->getId(),
		'entityTypeId' => $arResult['entityTypeId'],
		'entityId' => $arResult['entityId'],
		'settings' => !empty($settings) ? $settings : null,
	];
}

?>
<div id="<?=$editorsContainerId?>"></div>

<script>
	BX.ready(() => {
		BX.Crm.Timeline.MenuBar.setDefault(BX.Crm.Timeline.MenuBar.create('<?=$toolbarId?>', <?=\Bitrix\Main\Web\Json::encode($jsParams)?>));
	});
</script>
