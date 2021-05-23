<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Tasks\Ui\Filter;
use \Bitrix\Tasks\Kanban;


// selected items of menu
if (isset($arParams['INCLUDE_INTERFACE_HEADER']) && $arParams['INCLUDE_INTERFACE_HEADER'] == 'Y')
{
	if(Kanban\StagesTable::getWorkMode() == Kanban\StagesTable::WORK_MODE_GROUP)
	{
		$arParams['MARK_SECTION_ALL'] = 'N';
		$arParams['MARK_ACTIVE_ROLE'] = 'N';
	}
	else
	{
		$arParams['MARK_ACTIVE_ROLE'] = 'Y';
		$arParams['MARK_SECTION_ALL'] = 'N';

		$state = Filter\Task::getListStateInstance()->getState();

		if (isset($state['SECTION_SELECTED']['CODENAME']) &&
			$state['SECTION_SELECTED']['CODENAME'] == 'VIEW_SECTION_ADVANCED_FILTER')
		{
			$arParams['MARK_SECTION_ALL'] = 'Y';
			$arParams['MARK_ACTIVE_ROLE'] = 'N';
		}

//		if (isset($state['SPECIAL_PRESETS']) && is_array($state['SPECIAL_PRESETS']))
//		{
//			foreach ($state['SPECIAL_PRESETS'] as $preset)
//			{
//				if ($preset['SELECTED'] == 'Y')
//				{
//					$arParams['MARK_SPECIAL_PRESET'] = 'Y';
//					$arParams['MARK_SECTION_ALL'] = 'N';
//					$arParams['MARK_ACTIVE_ROLE'] = 'N';
//					break;
//				}
//			}
//		}
	}
}

// kanban wo group tmp not available
if ($arParams['GROUP_ID'] == 0 && $arParams['PERSONAL'] != 'Y')
{
	$arResult['DATA']['items'] = array();
	foreach ($arResult['DATA']['columns'] as &$column)
	{
		$column['total'] = 0;
	}
	unset($column);
}