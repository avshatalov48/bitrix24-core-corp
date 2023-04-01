<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

// prepare and some for compatibility

$arResult['ITEMS']['dropzones'] = array();

foreach ($arResult['ITEMS']['columns'] as $k => &$column)
{
	if ($column['dropzone'] || $column['alwaysShowInDropzone'])
	{
		 $element = [
			'id' => $column['id'],
			'name' => $column['name'],
			'color' => $column['color'],
			'data' => [
				'type' => $column['type'],
				'blockedIncomingMoving' => ($column['blockedIncomingMoving'] ?? false),
			],
		];

		if ($element['id'] === 'DELETED')
		{
			array_unshift($arResult['ITEMS']['dropzones'], $element);
		}
		else
		{
			$arResult['ITEMS']['dropzones'][] = $element;
		}

		if ($column['dropzone'])
		{
			unset($arResult['ITEMS']['columns'][$k]);
		}
	}

	if (!$column['dropzone'])
	{
		$column = [
			'id' => $column['id'],
			'total' => (int) $column['count'],
			'color' => $column['color'],
			'name' => htmlspecialcharsback($column['name']),
			'canAddItem' => $column['canAddItem'],
			'canSort' => ($arResult['CONFIG_BY_VIEW_MODE']['accessConfigPerms'] && $column['type'] === 'PROGRESS'),
			'data' => [
				'sort' => $column['sort'],
				'type' => $column['type'],
				'sum' => round($column['total']),
				'sum_init' => 0,
				'sum_format' => $column['total_format'] ?? null,
				'blockedIncomingMoving' => ($column['blockedIncomingMoving'] ?? false),
			],
		];
	}
}
unset($column);

foreach ($arResult['ITEMS']['items'] as $i => &$item)
{
	$item = array(
		'id' => $item['id'],
		'columnId' => $item['columnId'],
		'countable' => !isset($item['countable']) || $item['countable'],
		'droppable' => !isset($item['droppable']) || $item['droppable'],
		'draggable' => !isset($item['draggable']) || $item['draggable'],
		'data' => $item
	);
}
unset($item);
