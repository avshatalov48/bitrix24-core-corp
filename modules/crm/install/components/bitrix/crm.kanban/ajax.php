<?php
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
$version = $request->get('version');
$force = $request->get('force');
$result = null;

//get one or more items
if ($action == 'get' && (!empty($id) || $minId))
{
	$result = $APPLICATION->IncludeComponent('bitrix:crm.kanban', '', array(
		'IS_AJAX' => 'Y',
		'ENTITY_TYPE' => $type,
		'GET_AVATARS' => 'Y',
		'FORCE_FILTER' => $force,
		'ADDITIONAL_FILTER' =>
			!empty($id)
			? array('ID' => $id)
			: array('>ID' => $minId),
		'EXTRA' => $extra
	));
}
//refresh Kanban
elseif ($action == 'get')
{
	$result = $APPLICATION->IncludeComponent('bitrix:crm.kanban', '', array(
		'IS_AJAX' => 'Y',
		'ENTITY_TYPE' => $type,
		'EXTRA' => $extra
	));
}
//get next page
elseif ($action == 'page' && !empty($column))
{
	$result = $APPLICATION->IncludeComponent('bitrix:crm.kanban', '', array(
		'IS_AJAX' => 'Y',
		'ENTITY_TYPE' => $type,
		'ADDITIONAL_FILTER' => array('COLUMN' => $column),
		'PAGE' => $page,
		'EXTRA' => $extra
	));
}
//change stage
elseif ($action == 'status' && !empty($id) && !empty($newState))
{
	$params = array(
		'IS_AJAX' => 'Y',
		'ENTITY_TYPE' => $type,
		'EXTRA' => $extra
	);
	// in version 2 we don't need in items
	if ($version == 2)
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
elseif ($action == 'activities' && !empty($id))
{
	$APPLICATION->IncludeComponent('bitrix:crm.activity.todo', '', array(
		'OWNER_TYPE_ID' => $type,
		'OWNER_ID' => $id,
		'IS_AJAX' => 'Y',
		'COMPLETED' => 'N'
	));
}
//another foramt work with action in ver 2
elseif ($version == 2)
{
	$result = $APPLICATION->IncludeComponent('bitrix:crm.kanban', '', array(
		'IS_AJAX' => 'Y',
		'ENTITY_TYPE' => $type,
		'EMPTY_RESULT' => 'Y',
		'EXTRA' => $extra
	));
}
else
{
	$result = array('ERROR' => 'Unknown action or params');
}

// for compatibility
if ($version == 2)
{
	if (isset($result['ITEMS']['columns']))
	{
		$result['ITEMS']['dropzones'] = array();
		foreach ($result['ITEMS']['columns'] as $k => &$column)
		{
			if ($column['dropzone'])
			{
				$result['ITEMS']['dropzones'][] = array(
					'id' => $column['id'],
					'name' => $column['name'],
					'color' => $column['color'],
					'data' => array(
						'type' => $column['type']
					)
				);
				unset($result['ITEMS']['columns'][$k]);
			}
			else
			{
				$column = array(
					'id' => $column['id'],
					'total' => (int) $column['count'],
					'color' => $column['color'],
					'name' => htmlspecialcharsback($column['name']),
					'canSort' => !($column['type'] == 'WIN'),
					'canAddItem' => $column['canAddItem'],
					'data' => array(
						'sort' => $column['sort'],
						'type' => $column['type'],
						'sum' => round($column['total']),
						'sum_init' => 0,
						'sum_format' => $column['total_format']
					)
				);
			}
		}
		unset($column);
		$result['ITEMS']['dropzones'] = array_values($result['ITEMS']['dropzones']);
		$result['ITEMS']['columns'] = array_values($result['ITEMS']['columns']);
	}
	else if (!isset($result['data']) && strtolower($action) == 'modifystage')
	{
		$result['data'] = array(
			'type' => isset($result['type'])
						? $result['type']
						: 'PROGRESS',
			'sum' => isset($result['sum'])
						? $result['sum']
						: 0,
			'sum_format' => isset($result['sum_format'])
						? $result['sum']
						: '',
			'sum_init' => 0
		);
	}

	if (isset($result['ITEMS']['items']))
	{
		foreach ($result['ITEMS']['items'] as &$item)
		{
			$item = array(
				'id' => $item['id'],
				'countable' => !isset($item['countable']) || $item['countable'],
				'droppable' => !isset($item['droppable']) || $item['droppable'],
				'draggable' => !isset($item['draggable']) || $item['draggable'],
				'columnId' => $item['columnId'],
				'data' => $item
			);
		}
		unset($item);
	}
}

//output
if ($result !== null)
{
	$GLOBALS['APPLICATION']->RestartBuffer();
	if (SITE_CHARSET != 'UTF-8')
	{
		$result = $GLOBALS['APPLICATION']->ConvertCharsetArray($result, SITE_CHARSET, 'UTF-8');
	}

	header('Content-Type: application/json');

	if (isset($result['ERROR']) && $result['ERROR']!='')
	{
		echo \CUtil::PhpToJSObject(array(
			'error' => $result['ERROR'],
			'fatal' => isset($result['FATAL']) ? $result['FATAL'] : false
		), false, false, true);
	}
	elseif (isset($result['ITEMS']))
	{
		echo \CUtil::PhpToJSObject($result['ITEMS'], false, false, true);
	}
	else
	{
		echo \CUtil::PhpToJSObject($result, false, false, true);
	}
}

\CMain::finalActions();
die();