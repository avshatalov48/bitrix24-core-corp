<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

/**
 * Class TasksWidgetCheckListNewComponent
 */
class TasksWidgetCheckListNewComponent extends TasksBaseComponent
{
	private static $nodeId = 0;
	private static $facade;
	private static $map = [
		'TASK' => [
			'FACADE' => TaskCheckListFacade::class,
			'ACTIONS' => [
				'RENEW' => 'tasks.task.checklist.renew',
				'COMPLETE' => 'tasks.task.checklist.complete',
			],
			'OPTIONS' => [
				'PREFIX' => 'task_options_checklist_',
			],
		],
		'TEMPLATE' => [
			'FACADE' => TemplateCheckListFacade::class,
			'ACTIONS' => [
				'RENEW' => 'tasks.template.checklist.renew',
				'COMPLETE' => 'tasks.template.checklist.complete',
			],
			'OPTIONS' => [
				'PREFIX' => 'template_options_checklist_',
			],
		],
	];

	/**
	 * @return bool
	 */
	protected function checkParameters()
	{
		$this->arResult['DATA'] = static::tryParseArrayParameter($this->arParams['DATA']);
		$this->arResult['CONVERTED'] = static::tryParseBooleanParameter($this->arParams['CONVERTED']);
		$this->arResult['INPUT_PREFIX'] = static::tryParseStringParameter($this->arParams['INPUT_PREFIX']);
		$this->arResult['CAN_ADD_ACCOMPLICE'] = static::tryParseBooleanParameter(
			$this->arParams['CAN_ADD_ACCOMPLICE'],
			true
		);
		$this->arResult['PATH_TO_USER_PROFILE'] = static::tryParseStringParameter(
			$this->arParams['PATH_TO_USER_PROFILE'],
			'/company/personal/user/#user_id#/'
		);

		$userId = static::tryParseIntegerParameter($this->arParams['USER_ID']);
		$entityId = static::tryParseIntegerParameter($this->arParams['ENTITY_ID']);
		$entityType = static::tryParseStringParameter($this->arParams['ENTITY_TYPE']);

		$showCompletedOptionName = static::$map[$entityType]['OPTIONS']['PREFIX'].'show_completed';

		/** @var CheckListFacade $facade */
		$facade = static::$map[$entityType]['FACADE'];
		static::$facade = $facade;

		$this->arResult['USER_ID'] = $userId;
		$this->arResult['ENTITY_ID'] = $entityId;
		$this->arResult['ENTITY_TYPE'] = $entityType;

		$this->arResult['SHOW_COMPLETED'] = User::getOption($showCompletedOptionName, $userId, true);
		$this->arResult['AJAX_ACTIONS'] = static::$map[$entityType]['ACTIONS'];

		return $this->errors->checkNoFatals();
	}

	/**
	 * @throws NotImplementedException
	 */
	protected function getData()
	{
		$userId = $this->arResult['USER_ID'];
		$entityId = $this->arResult['ENTITY_ID'];
		$checkListItems = $this->arResult['DATA'];

		$commonActions = [
			'CAN_ADD' => true,
			'CAN_REORDER' => true,
			'CAN_ADD_ACCOMPLICE' => true,
		];

		if (!$checkListItems)
		{
			$this->arResult['UF_CHECKLIST_FILES'] = [];

			if (!$entityId)
			{
				$this->arResult['DATA']['TREE_ARRAY'] = [];
				$this->arResult['COMMON_ACTION'] = $commonActions;
				return;
			}
		}

		foreach ($checkListItems as $id => $item)
		{
			if (!$entityId)
			{
				$checkListItems[$id]['ACTION']['MODIFY'] = true;
				$checkListItems[$id]['ACTION']['REMOVE'] = true;
				$checkListItems[$id]['ACTION']['TOGGLE'] = true;
			}
			$checkListItems[$id]['ACTION']['DRAG'] = $item['ACTION']['MODIFY'];
			$this->arResult['UF_CHECKLIST_FILES'][$id] = ($item['UF_CHECKLIST_FILES'] ?: []);
		}

		$this->arResult['UF_CHECKLIST_FILES'] = array_filter(
			$this->arResult['UF_CHECKLIST_FILES'],
			static function($item)
			{
				return !empty($item);
			}
		);

		$objectTreeStructure = $this->buildTreeStructure($checkListItems);
		$objectTreeStructure = $this->fillInfo($objectTreeStructure);

		if ($entityId && $userId)
		{
			$commonActions = [
				'CAN_ADD' => static::$facade::isActionAllowed($entityId, null, $userId, static::$facade::ACTION_ADD),
				'CAN_REORDER' => static::$facade::isActionAllowed($entityId, null, $userId, static::$facade::ACTION_REORDER),
				'CAN_ADD_ACCOMPLICE' => $this->arResult['CAN_ADD_ACCOMPLICE'],
			];
		}

		$this->arResult['DATA']['TREE_ARRAY'] = $objectTreeStructure->toTreeArray();
		$this->arResult['COMMON_ACTION'] = $commonActions;
	}

	/**
	 * @param $checkListItems
	 * @return CheckList
	 * @throws NotImplementedException
	 */
	private function buildTreeStructure($checkListItems)
	{
		$result = new CheckList(static::$nodeId, $this->userId, static::$facade);

		$sortIndex = 0;
		$displaySortIndex = false;
		$keyToSort = static::getKeyToSort($checkListItems);

		$arrayTreeStructure = TaskCheckListFacade::getArrayStructuredRoots($checkListItems, $keyToSort);

		foreach ($arrayTreeStructure as $id => $root)
		{
			$result->add($this->makeTree($root, $sortIndex, $displaySortIndex));
			$sortIndex++;
		}

		return $result;
	}

	/**
	 * @param $items
	 * @return string
	 */
	private static function getKeyToSort($items)
	{
		$keyToSort = 'PARENT_ID';

		foreach ($items as $item)
		{
			if (array_key_exists('PARENT_NODE_ID', $item))
			{
				$keyToSort = 'PARENT_NODE_ID';
				break;
			}
		}

		return $keyToSort;
	}

	/**
	 * @param $root
	 * @param $sortIndex
	 * @param $displaySortIndex
	 * @return CheckList
	 * @throws NotImplementedException
	 */
	private function makeTree($root, $sortIndex, $displaySortIndex)
	{
		static::$nodeId++;

		$root['TITLE'] = htmlspecialcharsbx($root['TITLE']);
		$root['SORT_INDEX'] = $sortIndex;
		$root['DISPLAY_SORT_INDEX'] = htmlspecialcharsbx($displaySortIndex);

		$localSortIndex = 0;
		$tree = new CheckList(static::$nodeId, $this->userId, static::$facade, $root);

		foreach ($root['SUB_TREE'] as $item)
		{
			++$localSortIndex;
			$nextDisplaySortIndex = ($displaySortIndex === false? $localSortIndex : "$displaySortIndex.$localSortIndex");
			$tree->add(static::makeTree($item, $localSortIndex - 1, $nextDisplaySortIndex));
		}

		return $tree;
	}

	/**
	 * @param CheckList $tree
	 * @return CheckList
	 */
	private function fillInfo(CheckList $tree)
	{
		$completedCount = 0;

		foreach ($tree->getDescendants() as $descendant)
		{
			/** @var CheckList $descendant */
			$fields = $descendant->getFields();

			if ($fields['IS_COMPLETE'])
			{
				$completedCount++;
			}

			static::fillInfo($descendant);
		}

		$tree->setFields(['COMPLETED_COUNT' => $completedCount]);

		return $tree;
	}

	/**
	 * @return array
	 */
	public static function getAllowedMethods()
	{
		return [
			'updateTaskOption',
		];
	}

	/**
	 * @param $option
	 * @param $value
	 * @param $userId
	 * @param $entityType
	 */
	public static function updateTaskOption($option, $value, $userId, $entityType)
	{
		$optionName = static::$map[$entityType]['OPTIONS']['PREFIX'].$option;
		$optionValue = (bool)$value;

		User::setOption($optionName, $optionValue, $userId);
	}
}