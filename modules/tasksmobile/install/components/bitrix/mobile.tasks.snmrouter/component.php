<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @var CUser $USER
 * @var CBitrixComponent $this
 */

use Bitrix\Main\Loader;
use Bitrix\Tasks\Util\Restriction;

$routePage = null;

if (Loader::includeModule('tasks') && Loader::includeModule('mobileapp'))
{
	$arParams['PREFIX_FOR_PATH_TO_SNM_ROUTER'] = ($arParams['PREFIX_FOR_PATH_TO_SNM_ROUTER'] ?? SITE_DIR . 'mobile/tasks/snmrouter/');
	$arParams['NAME_TEMPLATE'] = ($arParams['NAME_TEMPLATE'] ?? '');

	$snmRouterPath = $arParams['PREFIX_FOR_PATH_TO_SNM_ROUTER'];

	$params = [
		'PATH_TO_SNM_ROUTER' => "{$snmRouterPath}?routePage=__ROUTE_PAGE__&USER_ID=#USER_ID#",
		'PATH_TO_SNM_ROUTER_AJAX' => (
			isset($arParams['PATH_TO_SNM_ROUTER_AJAX'])
				? str_replace('mobile_action=task_router', 'mobile_action=task_ajax', $arParams['PATH_TO_SNM_ROUTER_AJAX'])
				: SITE_DIR . 'mobile/?mobile_action=task_ajax'
		),
		'PATH_TO_USER_TASKS_PROJECTS' => "{$snmRouterPath}?routePage=projects&USER_ID=#USER_ID#",
		'PATH_TO_USER_TASKS' => "{$snmRouterPath}?routePage=list&USER_ID=#USER_ID#",
		'PATH_TO_GROUP_TASKS' => "{$snmRouterPath}?routePage=list&GROUP_ID=#group_id#",
		'PATH_TO_USER_TASKS_LIST_SORT' => "{$snmRouterPath}?routePage=listsorter&USER_ID=#USER_ID#",
		'PATH_TO_USER_TASKS_LIST_FIELDS' => "{$snmRouterPath}?routePage=listfields&USER_ID=#USER_ID#",
		'PATH_TO_USER_TASKS_TASK' => "{$snmRouterPath}?routePage=view&USER_ID=#USER_ID#&TASK_ID=#TASK_ID#",
		'PATH_TO_USER_TASKS_EDIT' => "{$snmRouterPath}?routePage=edit&USER_ID=#USER_ID#&TASK_ID=#TASK_ID#",
		'PATH_TO_USER_TASKS_FILTER' => "{$snmRouterPath}?routePage=filter&USER_ID=#USER_ID#",
		'PATH_TO_USER_TASKS_SELECTOR' => "{$snmRouterPath}?routePage=selector",
		'DATE_TIME_FORMAT' => \CDatabase::DateFormatToPHP(FORMAT_DATETIME),
		'NAME_TEMPLATE' => ($arParams['NAME_TEMPLATE'] ?: CSite::GetNameFormat(false)),
		'PLATFORM' => 'mobile',
		'USER_ID' => (int)($this->request->getQuery('USER_ID') ?: $USER->getId()),
		'TASK_ID' => (int)$this->request->getQuery('TASK_ID'),
		'GROUP_ID' => (int)$this->request->getQuery('GROUP_ID'),
		'NEW_CARD' => ($this->request->getQuery('NEW_CARD') === 'Y' ? 'Y' : 'N'),
		'GUID' => ($this->request->getQuery('GUID') ?? ''),
		'FRAGMENT_TYPE' => ($this->request->getQuery('FRAGMENT_TYPE') ?? ''),
		'FRAGMENT_ID' => ($this->request->getQuery('FRAGMENT_ID') ?? ''),
		'RESULT_ID' => ($this->request->getQuery('RESULT_ID') ?? ''),
	];

	foreach ($params as $key => $value)
	{
		$arParams[$key] = $value;
	}

	$whiteList = [
		'roles',
		'bitrix24restricted',
		'edit',
		'filter',
		'listfields',
		'listsorter',
		'projects',
		'selector',
		'view',
		'efficiency',
		'comments',
		'fragmentrenderer',
	];

	$routePage = ($this->request->getQuery('routePage') ?: 'roles');
	$routePage = ($routePage === '__ROUTE_PAGE__'? 'view' : mb_strtolower($routePage));

	if (!in_array($routePage, $whiteList, true))
	{
		$routePage = 'roles';
	}
}

if (($routePage === 'edit' || $routePage === 'view') && !Restriction::canManageTask())
{
	$this->IncludeComponentTemplate('bitrix24restricted');
}
else
{
	$this->IncludeComponentTemplate($routePage);
}

return $arResult;
