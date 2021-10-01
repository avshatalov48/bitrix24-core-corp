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
use Bitrix\Tasks\Integration\Network\MemberSelector;
use Bitrix\Tasks\Scrum\Checklist\TypeChecklistFacade;
use Bitrix\Tasks\Scrum\Checklist\ItemChecklistFacade;
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
				'COMPLETE_ALL' => 'tasks.task.checklist.completeAll',
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
				'COMPLETE_ALL' => 'tasks.template.checklist.completeAll',
			],
			'OPTIONS' => [
				'PREFIX' => 'template_options_checklist_',
			],
		],
		'SCRUM_ENTITY' => [
			'FACADE' => TypeChecklistFacade::class,
		],
		'SCRUM_ITEM' => [
			'FACADE' => ItemChecklistFacade::class,
		],
	];
	private static $optionsMap = [];

	public function getSignature()
	{
		$seedPhrase = ToLower($this->getName().$this->getTemplateName());
		if ($this->arParams['SIGNATURE_SEED'])
		{
			$seedPhrase = trim(ToLower($this->arParams['SIGNATURE_SEED']));
		}
		return preg_replace('#[^a-zA-Z0-9]#', '_', $seedPhrase).'_'.$this->getInPageNumber();
	}

	/**
	 * @return bool
	 */
	protected function checkParameters()
	{
		$this->arResult['DATA'] = static::tryParseArrayParameter($this->arParams['DATA']);
		$this->arResult['CONVERTED'] = static::tryParseBooleanParameter($this->arParams['CONVERTED']);
		$this->arResult['INPUT_PREFIX'] = static::tryParseStringParameter($this->arParams['INPUT_PREFIX']);
		$this->arResult['TASK_GUID'] = static::tryParseStringParameter($this->arParams['TASK_GUID']);
		$this->arResult['DISK_FOLDER_ID'] = static::tryParseIntegerParameter($this->arParams['DISK_FOLDER_ID']);
		$this->arResult['CAN_ADD_ACCOMPLICE'] = static::tryParseBooleanParameter(
			$this->arParams['CAN_ADD_ACCOMPLICE'],
			true
		);
		$this->arResult['PATH_TO_USER_PROFILE'] = static::tryParseStringParameter(
			$this->arParams['PATH_TO_USER_PROFILE'],
			'/company/personal/user/#user_id#/'
		);
		$this->arResult['SHOW_COMPLETE_ALL_BUTTON'] = static::tryParseBooleanParameter(
			$this->arParams['SHOW_COMPLETE_ALL_BUTTON']
		);
		$this->arResult['COLLAPSE_ON_COMPLETE_ALL'] = static::tryParseBooleanParameter(
			$this->arParams['COLLAPSE_ON_COMPLETE_ALL'],
			true
		);

		$mode = mb_strtolower(static::tryParseStringParameter($this->arParams['MODE'], 'view'));
		$userId = static::tryParseIntegerParameter($this->arParams['USER_ID']);
		$entityId = static::tryParseIntegerParameter($this->arParams['ENTITY_ID']);
		$entityType = static::tryParseStringParameter($this->arParams['ENTITY_TYPE']);

		/** @var CheckListFacade $facade */
		$facade = static::$map[$entityType]['FACADE'];
		static::$facade = $facade;

		$this->arResult['USER_ID'] = $userId;
		$this->arResult['ENTITY_ID'] = $entityId;
		$this->arResult['ENTITY_TYPE'] = $entityType;
		$this->arResult['MODE'] = (in_array(mb_strtolower($mode), ['view', 'edit'], true) ? $mode : 'view');
		$this->arResult['IS_NETWORK_ENABLED'] = MemberSelector::isNetworkEnabled();

		$this->arResult['AJAX_ACTIONS'] = static::$map[$entityType]['ACTIONS'];
		$this->arResult['USER_OPTIONS'] = $this->getUserOptions($entityType, $userId);

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
			'CAN_ADD_ACCOMPLICE' => $this->arResult['CAN_ADD_ACCOMPLICE'],
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
			if (!is_array($item))
			{
				unset($checkListItems[$id]);
				continue;
			}
			$checkListItems[$id] = $this->prepareItemActions($item);
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
				'CAN_REORDER' => true, // static::$facade::isActionAllowed($entityId, null, $userId, static::$facade::ACTION_REORDER),
				'CAN_ADD_ACCOMPLICE' => $this->arResult['CAN_ADD_ACCOMPLICE'],
			];
		}

		$this->arResult['DATA']['TREE_ARRAY'] = $objectTreeStructure->toTreeArray();
		$this->arResult['COMMON_ACTION'] = $commonActions;
	}

	/**
	 * @param $item
	 * @return array
	 */
	private function prepareItemActions($item): array
	{
		if (!$this->arResult['ENTITY_ID'])
		{
			$item['ACTION']['MODIFY'] = true;
			$item['ACTION']['REMOVE'] = true;
			$item['ACTION']['TOGGLE'] = true;

			return $item;
		}

		if (array_key_exists('ACTION', $item))
		{
			$item['ACTION']['MODIFY'] = $this->isTrue($item['ACTION']['MODIFY']);
			$item['ACTION']['REMOVE'] = $this->isTrue($item['ACTION']['REMOVE']);
			$item['ACTION']['TOGGLE'] = $this->isTrue($item['ACTION']['TOGGLE']);
		}
		$item['ACTION']['DRAG'] = $item['ACTION']['MODIFY'];

		return $item;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	private function isTrue($value): bool
	{
		return ($value === true || $value === 'true' || $value === 'Y');
	}

	/**
	 * @param $checkListItems
	 * @return CheckList
	 * @throws NotImplementedException
	 */
	private function buildTreeStructure($checkListItems): CheckList
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
	private static function getKeyToSort($items): string
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
	private function makeTree($root, $sortIndex, $displaySortIndex): CheckList
	{
		static::$nodeId++;

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
	private function fillInfo(CheckList $tree): CheckList
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

	private static function createOptionsMap(): void
	{
		static::$optionsMap = [
			'SHOW_COMPLETED' => [
				'DEFAULT_VALUE' => true,
				'TYPE_CALLBACK' => static function($value)
				{
					return (bool)$value;
				},
			],
			'DEFAULT_MEMBER_SELECTOR_TYPE' => [
				'DEFAULT_VALUE' => 'auditor',
				'TYPE_CALLBACK' => static function($value)
				{
					return (string)$value;
				},
			],
		];
	}

	/**
	 * @param string $entityType
	 * @param int $userId
	 * @return array
	 */
	private function getUserOptions($entityType, $userId): array
	{
		$userOptions = [];

		if (empty(static::$optionsMap))
		{
			self::createOptionsMap();
		}

		foreach (static::$optionsMap as $key => $value)
		{
			$defaultValue = $value['DEFAULT_VALUE'];
			$typeCallback = $value['TYPE_CALLBACK'];

			$optionName = static::$map[$entityType]['OPTIONS']['PREFIX'].mb_strtolower($key);
			$userOptions[$key] = (
				is_callable($typeCallback)
					? $typeCallback(User::getOption($optionName, $userId, $defaultValue))
					: User::getOption($optionName, $userId, $defaultValue)
			);
		}

		return $userOptions;
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
	public static function updateTaskOption($option, $value, $userId, $entityType): void
	{
		if (empty(static::$optionsMap))
		{
			static::createOptionsMap();
		}

		$typeCallback = static::$optionsMap[mb_strtoupper($option)]['TYPE_CALLBACK'];

		$optionName = static::$map[$entityType]['OPTIONS']['PREFIX'].$option;
		$optionValue = (is_callable($typeCallback) ? $typeCallback($value) : $value);

		User::setOption($optionName, $optionValue, $userId);
	}
}