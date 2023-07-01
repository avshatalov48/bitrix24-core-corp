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
	];

	if ($item->hasTariffRestrictions())
	{
		$menuItem['IS_LOCKED'] = true;
	}

	$menuItems[] = $menuItem;

	$item->loadAssets();
}
$editMode = ($arParams['ALLOW_MOVE_ITEMS'] ?? false)  ? 'common' : false;
$menuId = $arParams['MENU_ID'] ?? 'timeline_toolbar-menu';
?>
<div class="crm-entity-stream-section-menu"><?php
	$APPLICATION->IncludeComponent(
		'bitrix:main.interface.buttons',
		'',
		[
			'ID' => $menuId,
			'ITEMS' => $menuItems,
			'EDIT_MODE' => $editMode,
			'THEME' => 'compact',
		]
	);
?></div>
<?php
$editorsContainerId = $guid . '_editors_container';
$jsParams = [
	'entityTypeId' => $arResult['entityTypeId'],
	'entityId' => $arResult['entityId'],
	'isReadonly' => $arResult['isReadonly'],
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

<script type="text/javascript">
	BX.ready(() => {
		BX.Crm.Timeline.MenuBar.setDefault(BX.Crm.Timeline.MenuBar.create('<?=$toolbarId?>', <?=\Bitrix\Main\Web\Json::encode($jsParams)?>));
	});
</script>
