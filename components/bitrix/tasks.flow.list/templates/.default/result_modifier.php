<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Tasks\Flow\Grid\Columns;

/** @var $APPLICATION CMain */
/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponent $component */

$converter = new Converter(Converter::UC_FIRST | Converter::TO_CAMEL);

$canDoAction = ($arResult['isFeatureEnabled'] || $arResult['canTurnOnTrial']);

foreach ($arResult['rows'] as $key => $row)
{
	$data = $row['data'];
	$actions = $row['actions'];
	$counters = $row['counters'];

	foreach (Columns::getAll() as $column)
	{
		$columnId = $column->getId();

		$pathToColumnFile = __DIR__ . '/column/' . mb_strtolower($columnId) . '.php';

		$prepareFunctionName = 'prepare' . $converter->process($columnId) . 'ColumnData';
		$counterFunctionName = 'prepare' . $converter->process($columnId) . 'ColumnCounter';
		$renderFunctionName = 'render' . $converter->process($columnId) . 'Column';

		if (
			isset($data[$columnId])
			&& file_exists($pathToColumnFile)
		)
		{
			$columnData = $data[$columnId];
			if (is_array($columnData))
			{
				$columnData = array_merge($columnData, ['editable' => $row['editable']]);
			}

			require_once $pathToColumnFile;

			if (
				$column->hasCounter()
				&& function_exists($prepareFunctionName)
			)
			{
				$columnData = array_merge(
					$columnData,
					$prepareFunctionName($columnData, $arResult)
				);
			}

			if (
				$column->hasCounter()
				&& function_exists($counterFunctionName)
			)
			{
				$arResult['rows'][$key]['counters'][$columnId] = array_merge(
					$counters[$columnId],
					$counterFunctionName($columnData, $arResult, $row['active']),
				);
			}

			if (function_exists($renderFunctionName))
			{
				$arResult['rows'][$key]['data'][$columnId] = $renderFunctionName(
					$columnData,
					$arResult,
					$row['active']
				);
			}
		}
	}

	foreach ($actions as $actionKey => $action)
	{
		$id = $action['id'];
		$data = $action['data'];
		$isDemoFlow = (($data['demo'] ?? null) === true);
		$isActive = (($data['isActive'] ?? null) === true);
		$demoFlow = $isDemoFlow ? 'Y' : 'N';

		$editHandler = "BX.Tasks.Flow.EditForm.createInstance({ flowId: {$data['flowId']}, demoFlow: '{$demoFlow}' })";

		switch ($id)
		{
			case \Bitrix\Tasks\Flow\Grid\Action\Edit::ID:
				if ($canDoAction)
				{
					$actions[$actionKey]['onclick'] = $editHandler;
				}
				else
				{
					$actions[$actionKey]['onclick'] = "BX.Tasks.Flow.Grid.showFlowLimit()";
				}
				$actions[$actionKey]['className'] = "menu-popup-no-icon tasks-flow-action-edit";
				break;
			case \Bitrix\Tasks\Flow\Grid\Action\Activate::ID:
				if ($canDoAction || $isActive)
				{
					$actions[$actionKey]['onclick'] = (
						$isDemoFlow
							? $editHandler
							: "BX.Tasks.Flow.Grid.activateFlow({$data['flowId']})"
					);
				}
				else
				{
					$actions[$actionKey]['onclick'] = "BX.Tasks.Flow.Grid.showFlowLimit()";
				}
				$actions[$actionKey]['className'] = "menu-popup-no-icon tasks-flow-action-activate";
				break;
			case \Bitrix\Tasks\Flow\Grid\Action\Pin::ID:
				if ($canDoAction)
				{
					$actions[$actionKey]['onclick'] = "BX.Tasks.Flow.Grid.pinFlow({$data['flowId']})";
				}

				$actions[$actionKey]['className'] = "menu-popup-no-icon tasks-flow-action-pin";
				break;
			case \Bitrix\Tasks\Flow\Grid\Action\Remove::ID:
				$actions[$actionKey]['onclick'] = "BX.Tasks.Flow.Grid.removeFlow({$data['flowId']})";
				$actions[$actionKey]['className'] = "menu-popup-no-icon tasks-flow-action-remove";
				break;
		}
	}

	$arResult['rows'][$key]['actions'] = $actions;
}
