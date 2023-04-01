<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Filter\ItemDataProvider;
use Bitrix\Crm\Item;
use Bitrix\Crm\Kanban\Entity\Deadlines\DeadlinesStageManager;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Crm\Item\SmartInvoice as SmartInvoiceItem;

class SmartInvoiceDeadlines extends SmartInvoice
{
	private DeadlinesStageManager $deadlinesManager;

	private string $dateFieldName;

	public function __construct()
	{
		parent::__construct();
		$this->deadlinesManager = new DeadlinesStageManager(\CCrmOwnerType::SmartInvoice);
		$this->dateFieldName = DeadlinesStageManager::dateFieldByEntityType(\CCrmOwnerType::SmartInvoice);
	}

	public function getStageFieldName(): string
	{
		return DeadlinesStageManager::FILTER_FIELD_CONTAINS_STAGE;
	}

	public function getStagesList(): array
	{
		return $this->deadlinesManager->stagesList();
	}

	public function fillStageTotalSums(array $filter, array $runtime, array &$stages): void
	{
		ItemDataProvider::processStageSemanticFilter($filter, $filter);
		unset($filter[ItemDataProvider::FIELD_STAGE_SEMANTIC]);
		foreach ($stages as &$stage)
		{
			$stageFilter = $this->deadlinesManager->applyStageFilter($stage['id'], $filter);
			$stage['count'] = $this->factory->getItemsCountFilteredByPermissions($stageFilter);
		}
	}

	public function getItems(array $parameters): \CDBResult
	{
		$parameters = $this->deadlinesManager->prepareItemsFilter($parameters);
		$columnId = $parameters['columnId'] ?? '';
		unset($parameters['columnId']);
		$rawResult = parent::getItems($parameters);

		$items = $this->deadlinesManager->prepareItemsResult($rawResult, $columnId);

		$dbResult = new \CDBResult();
		$dbResult->InitFromArray($items);
		return $dbResult;
	}

	public function updateItemStage(int $id, string $stageId, array $newStateParams, array $stages): Result
	{
		if (!$this->deadlinesManager->checkIsStageAllowed($stageId))
		{
			return (new Result())->addError(
				new Error(Loc::getMessage('CRM_KANBAN_DEADLINE_VIEW_MODE_MOVE_ITEM_TO_COLUMN_BLOCKED'))
			);
		}

		/** @var $item SmartInvoiceItem */
		$item = $this->factory->getItem($id);
		if (!$item)
		{
			return (new Result())->addError(new Error(Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND')));
		}

		$item->set($this->dateFieldName, $this->deadlinesManager->calculateDateByStage($stageId));

		return
			$this->factory
				->getUpdateOperation($item)
				->launch();
	}

	public function isTotalPriceSupported(): bool
	{
		return false;
	}

	public function isRecurringSupported(): bool
	{
		return false;
	}

	public function isExclusionSupported(): bool
	{
		return false;
	}

	public function isActivityCountersFilterSupported(): bool
	{
		return $this->factory->isCountersEnabled();
	}

	public function getRequiredFieldsByStages(array $stages): array
	{
		return [];
	}
}
