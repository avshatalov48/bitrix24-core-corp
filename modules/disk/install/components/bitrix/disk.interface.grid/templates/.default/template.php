<?php
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;

$uri = new \Bitrix\Main\Web\Uri(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRequestUri());
$uri->deleteParams(\Bitrix\Main\HttpRequest::getSystemParameters());

$navString = '';
if(!empty($arParams['DATA_FOR_PAGINATION']['ENABLED']))
{
	$nextNavString = $prevNavString = $innerNavString = '';
	if($arParams['DATA_FOR_PAGINATION']['CURRENT_PAGE'] > 1)
	{
		$prevNavString = '<a class="bx-disk-nav-page" href="' . $uri->addParams(array('pageNumber' => $arParams['DATA_FOR_PAGINATION']['CURRENT_PAGE'] - 1))->getUri() . '">' . Loc::getMessage('DISK_INTERFACE_GRID_LABEL_GRID_PAGE_PREV_2') . '</a>';
	}
	if($arParams['DATA_FOR_PAGINATION']['SHOW_NEXT_PAGE'])
	{
		$nextNavString = '<a class="bx-disk-nav-page"  href="' . $uri->addParams(array('pageNumber' => $arParams['DATA_FOR_PAGINATION']['CURRENT_PAGE'] + 1))->getUri() . '">' . Loc::getMessage('DISK_INTERFACE_GRID_LABEL_GRID_PAGE_NEXT_2') . '</a>';
	}
	if($arParams['DATA_FOR_PAGINATION']['CURRENT_PAGE'] > 1)
	{
		$currentPageNumber = $arParams['DATA_FOR_PAGINATION']['CURRENT_PAGE'];
		for($i = $currentPageNumber - 2; $i < $currentPageNumber; $i++)
		{
			if($i > 0)
			{
				$innerNavString .= '<a href="' . $uri->addParams(array('pageNumber' => $i))->getUri() . '">' . $i . '</a>&nbsp;';
			}
		}

		if($arParams['DATA_FOR_PAGINATION']['CURRENT_PAGE'] > 3)
		{
			$innerNavString = '<a href="' . $uri->addParams(array('pageNumber' => 1))->getUri() . '">' . 1 . '</a>...&nbsp;' . $innerNavString;
		}
	}
	if($prevNavString || $nextNavString)
	{
		$navString = Loc::getMessage('DISK_INTERFACE_GRID_LABEL_GRID_PAGE_LABEL') . ": {$prevNavString} {$innerNavString} <span>{$arParams['DATA_FOR_PAGINATION']['CURRENT_PAGE']}</span> {$nextNavString}";
		$arParams['~NAV_STRING'] = $navString;
	}
}

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.grid',
	'',
	array(
		'MODE' => empty($arParams['MODE'])? 'grid' : $arParams['MODE'],
		'GRID_ID' => isset($arParams['~GRID_ID'])? $arParams['~GRID_ID'] : null,
		'HEADERS' => isset($arParams['~HEADERS'])? $arParams['~HEADERS'] : null,
		'SORT' => isset($arParams['~SORT'])? $arParams['~SORT'] : null,
		'SORT_VARS' => isset($arParams['~SORT_VARS'])? $arParams['~SORT_VARS'] : null,
		'ROWS' => isset($arParams['~ROWS'])? $arParams['~ROWS'] : null,
		'FOOTER' => isset($arParams['~FOOTER'])? $arParams['~FOOTER'] : null,
		'EDITABLE' => isset($arParams['~EDITABLE'])? $arParams['~EDITABLE'] : null,
		'ALLOW_EDIT' => isset($arParams['~ALLOW_EDIT'])? $arParams['~ALLOW_EDIT'] : null,
		'ALLOW_INLINE_EDIT' => isset($arParams['~ALLOW_INLINE_EDIT'])? $arParams['~ALLOW_INLINE_EDIT'] : null,
		'ACTIONS' => isset($arParams['~ACTIONS'])? $arParams['~ACTIONS'] : null,
		'ACTION_ALL_ROWS' => isset($arParams['~ACTION_ALL_ROWS'])? $arParams['~ACTION_ALL_ROWS'] : null,
		'NAV_OBJECT' => isset($arParams['~NAV_OBJECT'])? $arParams['~NAV_OBJECT'] : null,
		'NAV_STRING' => isset($arParams['~NAV_STRING'])? $arParams['~NAV_STRING'] : null,
		'FORM_ID' => isset($arParams['~FORM_ID'])? $arParams['~FORM_ID'] : null,
		'TAB_ID' => isset($arParams['~TAB_ID'])? $arParams['~TAB_ID'] : null,
		'CURRENT_URL' => isset($arParams['~CURRENT_URL'])? $arParams['~CURRENT_URL'] : null,
		'AJAX_MODE' => isset($arParams['~AJAX_MODE'])? $arParams['~AJAX_MODE'] : null,
		'AJAX_ID' => isset($arParams['~AJAX_ID']) ? $arParams['~AJAX_ID'] : '',
		'AJAX_OPTION_JUMP' => isset($arParams['~AJAX_OPTION_JUMP']) ? $arParams['~AJAX_OPTION_JUMP'] : 'N',
		'AJAX_OPTION_HISTORY' => isset($arParams['~AJAX_OPTION_HISTORY']) ? $arParams['~AJAX_OPTION_HISTORY'] : 'N',
		'AJAX_INIT_EVENT' => isset($arParams['~AJAX_INIT_EVENT']) ? $arParams['~AJAX_INIT_EVENT'] : '',
		'FILTER' => isset($arParams['~FILTER'])? $arParams['~FILTER'] : null,
		'FILTER_PRESETS' => isset($arParams['~FILTER_PRESETS'])? $arParams['~FILTER_PRESETS'] : null,
		'RENDER_FILTER_INTO_VIEW' => isset($arParams['~RENDER_FILTER_INTO_VIEW']) ? $arParams['~RENDER_FILTER_INTO_VIEW'] : '',
		'HIDE_FILTER' => isset($arParams['~HIDE_FILTER']) ? $arParams['~HIDE_FILTER'] : false,
		'FILTER_TEMPLATE' => isset($arParams['~FILTER_TEMPLATE']) ? $arParams['~FILTER_TEMPLATE'] : '',
		'MANAGER' => isset($arParams['~MANAGER']) ? $arParams['~MANAGER'] : null,
		'DISABLE_SETTINGS' => isset($arParams['~DISABLE_SETTINGS']) ? $arParams['~DISABLE_SETTINGS'] : null,

	),
	$component, array('HIDE_ICONS' => 'Y')
);
?>
