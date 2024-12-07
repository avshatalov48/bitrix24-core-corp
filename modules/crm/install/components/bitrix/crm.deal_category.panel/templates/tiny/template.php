<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;

Extension::load([
	'crm.toolbar-component',
	'ui.buttons',
	'main.popup',
]);

$entityTypeId = CCrmOwnerType::Deal;
$guid = $arResult['GUID'];
$containerId = HtmlFilter::encode("{$guid}_container");
$buttonId = HtmlFilter::encode("{$guid}_selector");
$counterId = HtmlFilter::encode("{$guid}_counter");
$tunnelsUrl = Container::getInstance()->getRouter()->getCategoryListUrl(\CCrmOwnerType::Deal);

$menuItems = isset($arResult['ITEMS']) && is_array($arResult['ITEMS'])
	? Bitrix\Crm\UI\Tools\ToolBar::mapItems($arResult['ITEMS'])
	: [];

$menuItems = array_map(static function ($item)
{
	$item['id'] = 'toolbar-category-' . ($item['id'] ?? '');
	$item['categoryId'] = $item['id'] ?? '';

	return $item;
}, $menuItems);

if (Container::getInstance()->getUserPermissions()->isAdminForEntity($entityTypeId))
{
	$menuItems[] = [
		'delimiter' => true,
	];
	$menuItems[] = [
		'text' => Loc::getMessage('CRM_DEAL_CATEGORY_PANEL_TUNNELS2'),
		'onclick' => new JsCode("BX.SidePanel.Instance.open('{$tunnelsUrl}', { cacheable: false, customLeftBoundary: 40, allowChangeHistory: false });")
	];
}

$categoryButton = new Button([
	'id' => $buttonId,
	'icon' => defined('Bitrix\UI\Buttons\Icon::FUNNEL') ? Icon::FUNNEL : '',
	'text' => $arResult['CATEGORY_NAME'] ?: htmlspecialcharsbx($arResult['ITEMS'][0]['NAME']),
	'color' => Color::LIGHT_BORDER,
	'menu' => [
		'id' => $buttonId.'_category_menu',
		'items' => $menuItems,
		'closeByEsc' => true
	],
	'maxWidth' => '400px',
	'dataset' => [
		'role' => 'bx-crm-toolbar-categories-button',
		'entity-type-id' => \CCrmOwnerType::Deal,
		'category-id' => $arResult['CATEGORY_ID'],
		'toolbar-collapsed-icon' => defined('Bitrix\UI\Buttons\Icon::FUNNEL') ? Icon::FUNNEL : '',
	],
]);

$categoryButton->setDropdown(count($arResult['ITEMS']) > 0);
if ($arResult['CATEGORY_COUNTER'] > 0)
{
	$categoryButton->setCounter($arResult['CATEGORY_COUNTER']);
}

Toolbar::addButton($categoryButton, ButtonLocation::AFTER_TITLE);
