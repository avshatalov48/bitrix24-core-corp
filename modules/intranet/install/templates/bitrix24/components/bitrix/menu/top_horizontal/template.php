<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$this->setFrameMode(true);

global $APPLICATION;

if (!is_array($arResult) || empty($arResult))
{
	return;
}

$parents = [];
$items = [];
foreach ($arResult as $item)
{
	$menuItem = [
		'TEXT' => $item['TEXT'],
		'URL' => $item["PARAMS"]["real_link"] ?? $item["LINK"],
		'ID' => $item['PARAMS']['menu_item_id'],
		'COUNTER' =>
			isset($item['PARAMS']['counter_num']) && (int)$item['PARAMS']['counter_num']
				? (int)$item['PARAMS']['counter_num']
				: ''
		,
		'COUNTER_ID' => $item['PARAMS']['counter_id'] ?? '',
		'IS_ACTIVE' => $item['SELECTED'],
		'IS_LOCKED' => isset($item['PARAMS']['is_locked']) && $item['PARAMS']['is_locked'] === true,
		'IS_NEW' => isset($item['PARAMS']['is_new']) && $item['PARAMS']['is_new'] === true,
		'SUPER_TITLE' =>
			isset($item['PARAMS']['super_title']) && is_array($item['PARAMS']['super_title'])
			? $item['PARAMS']['super_title']
			: ''
		,
		'ON_CLICK' =>
			isset($item['PARAMS']['onclick']) && is_string($item['PARAMS']['onclick'])
				? $item['PARAMS']['onclick']
				: ''
		,
		'SUB_LINK' => $item["PARAMS"]["sub_link"] ?? '',
		'CLASS' => $item['PARAMS']['class'],
		'IS_DISABLED' => $item['PARAMS']['is_disabled'] ?? false,
		'IS_DELIMITER' => $item['PARAMS']['is_delimiter'] ?? false,
		// 'CLASS_SUBMENU_ITEM',
	];

	if (isset($item['PARAMS']['action']) && is_array($item['PARAMS']['action']))
	{
		if (isset($item['PARAMS']['action']['ID']) && $item['PARAMS']['action']['ID'] == 'CREATE')
		{
			$menuItem['SUB_LINK'] = [
				//'CLASS' => 'crm-menu-plus-btn',
				'URL' => $item['PARAMS']['action']['URL'],
			];
		}
	}

	if (isset($item['PARAMS']['sub_menu']) && is_array($item['PARAMS']['sub_menu']))
	{
		$menuItem['ITEMS'] = $item['PARAMS']['sub_menu'];
	}

	$index = $item['DEPTH_LEVEL'] - 1;
	if (isset($parents[$index]))
	{
		if (!isset($parents[$index]['ITEMS']))
		{
			$parents[$index]['ITEMS'] = [];
		}

		if ($menuItem['IS_ACTIVE'])
		{
			for ($i = $index; $i >= 1; $i--)
			{
				$parents[$i]['IS_ACTIVE'] = true;
			}
		}

		$parents[$index]['ITEMS'][] = $menuItem;

		$parents[$item['DEPTH_LEVEL']] = &$parents[$index]['ITEMS'][count($parents[$index]['ITEMS']) - 1];
	}
	else
	{
		$items[] = $menuItem;
		$parents[$item['DEPTH_LEVEL']] = &$items[count($items) - 1];
	}
}

$menuId = 'top_panel_menu';

//hack for complex component (/company/personal/ pages)
$topMenuSectionDir = $APPLICATION->GetPageProperty('topMenuSectionDir');
if (!empty($topMenuSectionDir))
{
	$arParams['MENU_DIR'] = $topMenuSectionDir;
}

if (isset($arParams['MENU_DIR']) && !empty($arParams['MENU_DIR']))
{
	$menuId = str_replace('/', '_', trim($arParams['MENU_DIR'], '/'));
	$menuId = 'top_menu_id_' . $menuId;
}

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.buttons',
	'',
	[
		'ID' => $menuId,
		'ITEMS' => $items,
	]
);