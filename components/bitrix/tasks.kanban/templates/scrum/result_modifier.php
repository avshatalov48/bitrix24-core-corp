<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */

use Bitrix\Tasks\Component\Kanban\ScrumManager;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

$scrumManager = new ScrumManager($arParams['GROUP_ID']);

$columns = $arResult['DATA']['columns'];
$items = $arResult['DATA']['items'];
$parentTasks = [];

$taskRegistry = TaskRegistry::getInstance();

list($items, $columns, $parentTasks) = $scrumManager->groupBySubTasks($taskRegistry, $items, $columns);

$arResult['DATA']['columns'] = $columns;
$arResult['DATA']['items'] = $items;
$arResult['DATA']['parentTasks'] = $parentTasks;
