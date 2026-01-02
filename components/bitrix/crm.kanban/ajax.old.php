<?php

/**
 * @deprecated
 * Warning! This code will be deleted soon.
 * Please, use new ajax.php class for new features
 */

use Bitrix\Crm\Component\Utils\JsonCompatibleConverter;
use Bitrix\Crm\Kanban\ViewMode;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Web\Json;

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();

$action = $request->get('action');
$id = $request->get('entity_id');
$minId = $request->get('min_entity_id');
$type = $request->get('entity_type');
$page = $request->get('page');
$column = $request->get('column');
$newState = $request->get('status');
$extra = $request->get('extra');
$force = $request->get('force');
$onlyItems = $request->get('onlyItems');
$viewMode = $request->get('viewMode');
$useItemPlanner = $request->get('useItemPlanner');
$skipColumnsCountCheck = $request->get('skipColumnCountCheck');
$result = null;

$version = $request->get('version');
$isVersion2 = ((int)$version === 2);

global $APPLICATION;

//get one or more items
if ($action === 'get')
{
	$params = [
		'IS_AJAX' => 'Y',
		'ENTITY_TYPE' => $type,
		'EXTRA' => $extra,
		'VIEW_MODE' => $viewMode,
		'USE_ITEM_PLANNER' => $useItemPlanner,
		'SKIP_COLUMN_COUNT_CHECK' => $skipColumnsCountCheck,
	];

	if (!empty($id) || $minId)
	{
		$params = array_merge($params, [
			'GET_AVATARS' => 'Y',
			'FORCE_FILTER' => $force,
			'ADDITIONAL_FILTER' => (empty($id) ? ['>ID' => $minId] : ['ID' => $id]),
			'ONLY_ITEMS' => ($onlyItems ?? 'N'),
			'FULL_CONFIG' => 'N',
		]);
	}

	$result = $APPLICATION->IncludeComponent(
		'bitrix:crm.kanban',
		'',
		$params
	);
}
//get next page
elseif ($action === 'page' && !empty($column))
{
	$result = $APPLICATION->IncludeComponent(
		'bitrix:crm.kanban',
		'',
		[
			'IS_AJAX' => 'Y',
			'ENTITY_TYPE' => $type,
			'ADDITIONAL_FILTER' => ['COLUMN' => $column],
			'PAGE' => $page,
			'EXTRA' => $extra,
			'ONLY_ITEMS' => ($onlyItems ?? 'N'),
			'VIEW_MODE' => $viewMode,
			'USE_ITEM_PLANNER' => $useItemPlanner,
			'SKIP_COLUMN_COUNT_CHECK' => $skipColumnsCountCheck,
		]
	);
}
//change stage
elseif ($action === 'status' && !empty($id) && !empty($newState))
{
	$params = [
		'IS_AJAX' => 'Y',
		'ENTITY_TYPE' => $type,
		'EXTRA' => $extra,
		'VIEW_MODE' => $viewMode,
		'USE_ITEM_PLANNER' => $useItemPlanner,
		'SKIP_COLUMN_COUNT_CHECK' => $skipColumnsCountCheck,
		'FULL_CONFIG' => 'N',
	];

	// in version 2 we don't need in items
	if ($isVersion2)
	{
		$params['EMPTY_RESULT'] = 'Y';
	}
	else
	{
		$params['ONLY_COLUMNS'] = 'Y';
	}

	$result = $APPLICATION->IncludeComponent('bitrix:crm.kanban', '', $params);
}
//activity items
elseif ($action === 'activities' && !empty($id))
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.activity.todo',
		'',
		[
			'OWNER_TYPE_ID' => $type,
			'OWNER_ID' => $id,
			'IS_AJAX' => 'Y',
			'COMPLETED' => 'N',
		]
	);
}
//another format work with action in ver 2
elseif ($isVersion2)
{
	$result = $APPLICATION->IncludeComponent(
		'bitrix:crm.kanban',
		'',
		[
			'IS_AJAX' => 'Y',
			'ENTITY_TYPE' => $type,
			'EMPTY_RESULT' => 'Y',
			'EXTRA' => $extra,
			'VIEW_MODE' => $viewMode,
			'USE_ITEM_PLANNER' => $useItemPlanner,
			'SKIP_COLUMN_COUNT_CHECK' => $skipColumnsCountCheck,
		]
	);
}
else
{
	$result = ['ERROR' => 'Unknown action or params'];
}

// for compatibility
if ($isVersion2)
{
	if (isset($result['ITEMS']['columns']))
	{
		$result['ITEMS']['dropzones'] = [];

		$entityTypeId = CCrmOwnerType::ResolveID($type);
		$isAdminForEntity = Container::getInstance()->getUserPermissions()->isAdminForEntity($entityTypeId);

		foreach ($result['ITEMS']['columns'] as $k => &$column)
		{
			if ($column['dropzone'] || $column['alwaysShowInDropzone'])
			{
				$element = [
					'id' => $column['id'],
					'name' => $column['name'],
					'color' => $column['color'],
					'data' => [
						'type' => $column['type'],
					],
				];

				if ($element['id'] === 'DELETED')
				{
					array_unshift($result['ITEMS']['dropzones'], $element);
				}
				else
				{
					$result['ITEMS']['dropzones'][] = $element;
				}

				if ($column['dropzone'])
				{
					unset($result['ITEMS']['columns'][$k]);
				}
			}

			$currency = $column['currency'] ?? null;

			if (!$column['dropzone'])
			{
				$canSort = (
					$isAdminForEntity
					&& $column['type'] === 'PROGRESS'
					&& !ViewMode::isDatesBasedView($viewMode)
				);

				$column = [
					'id' => $column['id'],
					'total' => (int) $column['count'],
					'color' => $column['color'],
					'name' => htmlspecialcharsback($column['name']),
					'canSort' => $canSort,
					'canAddItem' => $column['canAddItem'],
					'data' => [
						'sort' => $column['sort'],
						'type' => $column['type'],
						'sum' => round($column['total'] ?? 0),
						'sum_init' => 0,
						'sum_format' => $column['total_format'] ?? null,
						'blockedIncomingMoving' => ($column['blockedIncomingMoving'] ?? false),
						'hiddenTotalSum' => ($column['hiddenTotalSum'] ?? false),
						'currencyFormat' => ($column['currencyFormat'] ?? false),
					],
				];
			}
		}
		unset($column);

		$result['ITEMS']['dropzones'] = array_values($result['ITEMS']['dropzones']);
		$result['ITEMS']['columns'] = array_values($result['ITEMS']['columns']);
	}
	else if (!isset($result['data']) && mb_strtolower($action) === 'modifystage')
	{
		$result['data'] = [
			'type' => $result['type'] ?? 'PROGRESS',
			'sum' => $result['sum'] ?? 0,
			'sum_format' => isset($result['sum_format']) ? $result['sum'] : '',
			'sum_init' => 0,
		];
	}

	if (isset($result['ITEMS']['items']))
	{
		foreach ($result['ITEMS']['items'] as &$item)
		{
			$isCountable = ($item['countable'] ?? true);
			$isDroppable = ($item['droppable'] ?? true);
			$isDraggable = ($item['draggable'] ?? true);

			$item = [
				'id' => $item['id'],
				'columnId' => $item['columnId'],
				'data' => $item,
			];

			if (!$isCountable)
			{
				$item['countable'] = false;
			}
			if (!$isDroppable)
			{
				$item['droppable'] = false;
			}
			if (!$isDraggable)
			{
				$item['draggable'] = false;
			}
		}
		unset($item);
	}
}

//output
if ($result !== null)
{
	$GLOBALS['APPLICATION']->RestartBuffer();

	header('Content-Type: application/json');

	if (!empty($result['ERROR']))
	{
		$data = [
			'error' => $result['ERROR'],
			'fatal' => $result['FATAL'] ?? false,
		];

		print Json::encode(JsonCompatibleConverter::convert($data, true));
	}
	elseif (isset($result['ITEMS']))
	{
		print Json::encode(JsonCompatibleConverter::convert($result['ITEMS'], true));
	}
	else
	{
		print Json::encode(JsonCompatibleConverter::convert($result, true));
	}
}

CMain::finalActions();
die();
