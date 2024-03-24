<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Rpa;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Filter\Item\Provider;
use Bitrix\Rpa\Model\FieldTable;

if(!\Bitrix\Main\Loader::includeModule('rpa'))
{
	return;
}

class RpaKanbanComponent extends \Bitrix\Rpa\Components\ItemList implements \Bitrix\Main\Engine\Contract\Controllerable
{
	protected $availableNextStages;
	protected $kanbanStageIds = [];

	protected const COLUMN_PAGE_SIZE = 10;

	protected function init(): void
	{
		parent::init();

		if(!$this->getErrors())
		{
			$this->processStageFilter();
		}
	}

	public function executeComponent()
	{
		$this->init();

		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		$urlManager->setUserItemListView($this->type->getId(), Rpa\UrlManager::ITEMS_LIST_VIEW_KANBAN);
		$this->arResult['title'] = $this->type->getTitle();
		$this->arResult['messages'] = Loc::loadLanguageFile(__FILE__);

		$this->arResult['kanban'] = $this->prepareKanban();

		$this->arResult['documentType'] = Rpa\Integration\Bizproc\Document\Item::makeComplexType(
			$this->arParams['typeId']
		);

		$this->getApplication()->setTitle(htmlspecialcharsbx($this->type->getTitle()));

		$this->includeComponentTemplate();
	}

	protected function prepareKanban(): array
	{
		$kanban = [];

		$stages = $this->getStages();
		$userPermissions = Driver::getInstance()->getUserPermissions();
		$userIds = [];
		$itemsFilter = $this->getListFilter();
		$stagesTotal = $this->getItemsTotalByStage($itemsFilter);

		$stageController = new Rpa\Controller\Stage();
		$itemController = new Rpa\Controller\Item();

		$kanban['columns'] = [];
		$kanban['items'] = [];
		foreach($stages as $stage)
		{
			$kanban['columns'][] = [
				'id' => $stage->getId(),
				'name' => $stage->getName(),
				'sort' => $stage->getSort(),
				'total' => $stagesTotal[$stage->getId()] ?? null,
				'color' => $stage->getColor(),
				'canSort' => !$stage->isFinal(),
				'data' => $stageController->prepareData($stage),
			];

			$items = $stage->getUserSortedItems([
				'filter' => $itemsFilter,
				'limit' => static::COLUMN_PAGE_SIZE,
			]);
			foreach($items as $item)
			{
				/** @var Rpa\Model\Item $item */
				$data = $itemController->prepareItemData($item, [
					'withUsers' => false,
				]);
				$this->getDisplay()->addValues($item->getId(), $data);
				$kanban['items'][] = [
					'id' => $item->getId(),
					'columnId' => $item->getStageId(),
					'name' => $item->getName(),
					'data' => $data,
				];
				$userIds = array_merge($userIds, $item->getUserIds());
			}
		}

		foreach($kanban['items'] as &$item)
		{
			$item['data']['display'] = $this->getDisplay()->getValues($item['id']);
		}
		unset($item);

		$users = static::getUsers($userIds);

		$kanban['data'] = [
			'typeId' => $this->type->getId(),
			'pullTag' => Driver::getInstance()->getPullManager()->subscribeOnKanbanUpdate($this->type->getId()),
			'taskCountersPullTag' => Driver::getInstance()->getPullManager()->subscribeOnTaskCounters(),
			'moduleId' => Driver::MODULE_ID,
			'userId' => Driver::getInstance()->getUserId(),
			'pageSize' => static::COLUMN_PAGE_SIZE,
			'editTypeUrl' => Driver::getInstance()->getUrlManager()->getTypeDetailUrl($this->type->getId()),
			'signedParameters' => $this->getSignedParameters(),
			'users' => $users,
			'fields' => $this->getFields(),
			'eventIds' => $this->eventIds,
			'isCreateItemRestricted' => Driver::getInstance()->getBitrix24Manager()->isCreateItemRestricted($this->type->getId()),
		];

		$kanban['canAddColumn'] =
		$kanban['canEditColumn'] =
		$kanban['canSortColumn'] =
		$kanban['canRemoveColumn'] = $userPermissions->canModifyType($this->type->getId());
		$kanban['canAddItem'] = $userPermissions->canAddItemsToType($this->type->getId());
		$kanban['canSortItem'] = true;

		$kanban['dropZones'] = $this->getDropZones();

		return $kanban;
	}

	protected function getCode(): string
	{
		return 'rpa';
	}

	protected function processStageFilter()
	{
		$stages = $this->type->getStages();
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->getFilterId());
		$requestFilter = $filterOptions->getFilter($this->getDefaultFilterFields());
		if(isset($requestFilter['STAGE_ID']))
		{
			$this->kanbanStageIds = $requestFilter['STAGE_ID'];
		}
		else
		{
			$this->kanbanStageIds = $stages->getIdList();
		}
		if(!isset($requestFilter[Provider::FIELD_STAGE_SEMANTIC]) && !isset($requestFilter['STAGE_ID']))
		{
			$requestFilter[Provider::FIELD_STAGE_SEMANTIC] = [
				Provider::FIELD_STAGE_SEMANTIC_IN_WORK,
			];
		}
		if(isset($requestFilter[Provider::FIELD_STAGE_SEMANTIC]))
		{
			foreach($this->kanbanStageIds as $key => $stageId)
			{
				$stage = $stages->getByPrimary($stageId);
				if(!$stage)
				{
					unset($this->kanbanStageIds[$key]);
					continue;
				}
				if(!in_array(Provider::FIELD_STAGE_SEMANTIC_IN_WORK, $requestFilter[Provider::FIELD_STAGE_SEMANTIC]) && !$stage->isFinal())
				{
					unset($this->kanbanStageIds[$key]);
					continue;
				}
				if(!in_array(Provider::FIELD_STAGE_SEMANTIC_SUCCESS, $requestFilter[Provider::FIELD_STAGE_SEMANTIC]) && $stage->isSuccess())
				{
					unset($this->kanbanStageIds[$key]);
					continue;
				}
				if(!in_array(Provider::FIELD_STAGE_SEMANTIC_FAIL, $requestFilter[Provider::FIELD_STAGE_SEMANTIC]) && $stage->isFail())
				{
					unset($this->kanbanStageIds[$key]);
					continue;
				}
			}
		}
	}

	protected function getStages(): array
	{
		$stages = $this->type->getStages();
		$result = [];
		foreach($stages as $stage)
		{
			if(in_array($stage->getId(), $this->kanbanStageIds))
			{
				$result[] = $stage;
			}
		}

		return $result;
	}

	protected function getDropZones(): array
	{
		$result = [];

		$result[] = [
			'id' => 'delete',
			'name' => Loc::getMessage('RPA_COMMON_ACTION_DELETE'),
			'color' => '',
		];

		$stages = $this->type->getStages();
		foreach($stages as $stage)
		{
			if(!in_array($stage->getId(), $this->kanbanStageIds))
			{
				$result[] = [
					'id' => $stage->getId(),
					'name' => $stage->getName(),
					'color' => $stage->getColor(),
					'data' => [
						'isColumn' => true,
					],
				];
			}
		}

		return $result;
	}

	protected function getItemsTotalByStage(array $filter): array
	{
		$result = [];
		$itemDataClass = Driver::getInstance()->getFactory()->getItemDataClass($this->type);
		$count = $itemDataClass::getList([
			'select' => [
				'STAGE_ID',
				new ExpressionField('COUNT', 'COUNT(*)'),
			],
			'group' => ['STAGE_ID'],
			'filter' => $filter,
			'runtime' => [$itemDataClass::getFullTextReferenceField()],
		]);

		while($stageCount = $count->fetch())
		{
			$result[$stageCount['STAGE_ID']] = (int) $stageCount['COUNT'];
		}

		return $result;
	}

	public function configureActions(): array
	{
		return [];
	}

	protected function listKeysSignedParameters(): array
	{
		return [
			'typeId',
		];
	}

	public function getAction(): ?array
	{
		$this->init();

		if($this->getErrors())
		{
			return null;
		}

		return [
			'kanban' => $this->prepareKanban(),
		];
	}

	public function getColumnAction(int $stageId, PageNavigation $pageNavigation = null): ?array
	{
		$this->init();

		if($this->getErrors())
		{
			return null;
		}

		$stage = $this->type->getStages()->getByPrimary($stageId);
		if(!$stage)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('RPA_KANBAN_STAGE_NOT_FOUND'));
			return null;
		}

		$result = [
			'items' => [],
		];
		$controller = new \Bitrix\Rpa\Controller\Item();

		$items = $stage->getUserSortedItems([
			'filter' => $this->getListFilter(),
			'offset' => $pageNavigation ? $pageNavigation->getOffset() : 0,
			'limit' => $pageNavigation ? $pageNavigation->getLimit() : static::COLUMN_PAGE_SIZE,
		]);

		foreach($items as $item)
		{
			$data = $controller->prepareItemData($item);
			$this->getDisplay()->addValues($item->getId(), $data);
			$result['items'][] = $data;
		}

		foreach($result['items'] as &$item)
		{
			$item['display'] = $this->getDisplay()->getValues($item['id']);
		}

		return $result;
	}

	protected function canMoveToStage(int $stageId)
	{
		$userPermissions = Driver::getInstance()->getUserPermissions();
		if($this->availableNextStages === null)
		{
			$this->availableNextStages = [];
			$stages = clone $this->type->getStages();
			reset($stages);
			foreach($stages as $stage)
			{
				if($userPermissions->canMoveFromStage($stage->getType(), $stage->getId()))
				{
					foreach($stage->getPossibleNextStageIds() as $nextStageId)
					{
						$this->availableNextStages[$nextStageId] = $nextStageId;
					}
				}
			}
		}

		return isset($this->availableNextStages[$stageId]);
	}

	protected function getKanbanVisibleFieldNames(): array
	{
		return [
			'id' => [
				'title' => 'ID',
			],
			'createdBy' => [
				'title' => Loc::getMessage('RPA_ITEM_CREATED_BY'),
				'type' => 'employee',
			],
			'updatedBy' => [
				'title' => Loc::getMessage('RPA_ITEM_UPDATED_BY'),
				'type' => 'employee',
			],
			'movedBy' => [
				'title' => Loc::getMessage('RPA_ITEM_MOVED_BY'),
				'type' => 'employee',
			],
			'createdTime' => [
				'title' => Loc::getMessage('RPA_ITEM_CREATED_TIME'),
				'type' => 'datetime',
			],
			'updatedTime' => [
				'title' => Loc::getMessage('RPA_ITEM_UPDATED_TIME'),
				'type' => 'datetime',
			],
			'movedTime' => [
				'title' => Loc::getMessage('RPA_ITEM_MOVED_TIME'),
				'type' => 'datetime',
			],
		];
	}

	protected function getFields(): array
	{
		$kanbanSettings = FieldTable::getGroupedList($this->type->getId(), 0);
		$userFields = $this->type->getUserFieldCollection();
		$result = [];

		foreach($this->getKanbanVisibleFieldNames() as $fieldName => $description)
		{
			$data = $description;
			$data['isVisibleOnKanban'] = isset($kanbanSettings[FieldTable::VISIBILITY_KANBAN][$fieldName]);
			$data['canBeEdited'] = false;
			$data['isTitle'] = false;

			$result[$fieldName] = $data;
		}

		foreach($userFields as $userField)
		{
			$data = [];
			$data['title'] = $userField->getTitle();
			$data['isVisibleOnKanban'] = isset($kanbanSettings[FieldTable::VISIBILITY_KANBAN][$userField->getName()]);
			$data['canBeEdited'] = true;
			$data['isTitle'] = ($userField->getName() === $this->type->getItemUfNameFieldName());

			$result[$userField->getName()] = $data;
		}

		return $result;
	}

	protected function getToolbarParameters(): array
	{
		$parameters = parent::getToolbarParameters();
		$parameters['views']['kanban']['isActive'] = true;

		return $parameters;
	}
}