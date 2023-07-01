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
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Access\ActionDictionary;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

/**
 * Class TasksWidgetCheckListNewComponent
 */
class TasksWidgetCheckListNewComponent extends TasksBaseComponent
	implements \Bitrix\Main\Errorable, \Bitrix\Main\Engine\Contract\Controllerable
{
	protected $errorCollection;

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
			'OPTIONS' => [
				'PREFIX' => '',
			],
		],
		'SCRUM_ITEM' => [
			'FACADE' => ItemChecklistFacade::class,
			'OPTIONS' => [
				'PREFIX' => '',
			],
		],
	];
	private static $optionsMap = [];

	public function configureActions()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'saveChecklist' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'updateTaskOption' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
		];
	}

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->init();
	}

	protected function init()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$this->setUserId();
		$this->errorCollection = new \Bitrix\Tasks\Util\Error\Collection();
	}

	protected function setUserId()
	{
		$this->userId = (int) \Bitrix\Tasks\Util\User::getId();
	}

	public function getErrorByCode($code)
	{
		// TODO: Implement getErrorByCode() method.
	}

	public function getErrors()
	{
		if (!empty($this->componentId))
		{
			return parent::getErrors();
		}
		return $this->errorCollection->toArray();
	}

	public function getSignature()
	{
		$seedPhrase = ToLower($this->getName().$this->getTemplateName());
		if ($this->arParams['SIGNATURE_SEED'] ?? null)
		{
			$seedPhrase = trim(ToLower($this->arParams['SIGNATURE_SEED']));
		}
		return preg_replace('#[^a-zA-Z0-9]#', '_', $seedPhrase).'_'.$this->getInPageNumber();
	}

	public function updateTaskOptionAction($option, $value, $userId, $entityType)
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		if (empty(static::$optionsMap))
		{
			static::createOptionsMap();
		}

		$typeCallback = static::$optionsMap[mb_strtoupper($option)]['TYPE_CALLBACK'];

		$optionName = static::$map[$entityType]['OPTIONS']['PREFIX'].$option;
		$optionValue = (is_callable($typeCallback) ? $typeCallback($value) : $value);

		User::setOption($optionName, $optionValue, $userId);
	}

	public function saveChecklistAction($taskId, $items = [], $params = [])
	{
		$taskId = (int) $taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		if (!is_array($items))
		{
			$items = [];
		}

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_CHECKLIST_SAVE, $taskId, $items))
		{
			$this->addForbiddenError();
			return null;
		}

		if (isset($params['openTime']) && $params['openTime'])
		{
			$openTime = $params['openTime'];
			$lastUpdateTime = \Bitrix\Tasks\Internals\Task\LogTable::getList([
				'select' => ['CREATED_DATE'],
				'filter' => [
					'TASK_ID' => $taskId,
					'!USER_ID' => $this->userId,
					'%FIELD' => 'CHECKLIST',
				],
				'order' => ['CREATED_DATE' => 'DESC'],
				'limit' => 1,
			])->fetch();

			if ($lastUpdateTime)
			{
				$lastUpdateTime = $lastUpdateTime['CREATED_DATE']->getTimestamp();
				if ($lastUpdateTime > $openTime)
				{
					$result = [
						'PREVENT_CHECKLIST_SAVE' => 'It looks like someone has already changed checklist.'
					];
					return $result;
				}
			}
		}

		foreach ($items as $id => $item)
		{
			$item['ID'] = ((int)($item['ID'] ?? null) === 0 ? null : (int)$item['ID']);
			$item['IS_COMPLETE'] = ($item['IS_COMPLETE'] === true) || ((int)$item['IS_COMPLETE'] > 0) ;
			$item['IS_IMPORTANT'] = ($item['IS_IMPORTANT'] === true) || ((int)$item['IS_IMPORTANT'] > 0);

			if (is_array($item['MEMBERS'] ?? null))
			{
				$members = [];

				foreach ($item['MEMBERS'] as $member)
				{
					$members[key($member)] = current($member);
				}

				$item['MEMBERS'] = $members;
			}

			$items[$item['NODE_ID']] = $item;
			unset($items[$id]);
		}

		$result = \Bitrix\Tasks\CheckList\Task\TaskCheckListFacade::merge($taskId, $this->userId, $items, $params);

		$result = array_merge(
			($result->getData() ?? []),
			['OPEN_TIME' => (new DateTime())->getTimestamp()]
		);

		return $result;
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

		$this->arResult['AJAX_ACTIONS'] = static::$map[$entityType]['ACTIONS'] ?? null;
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

			$this->arResult['UF_CHECKLIST_FILES'][$id] = (($item['UF_CHECKLIST_FILES'] ?? null) ?: []);
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

	private function addForbiddenError()
	{
		$this->errorCollection->add('ACTION_NOT_ALLOWED.RESTRICTED', \Bitrix\Main\Localization\Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
	}
}