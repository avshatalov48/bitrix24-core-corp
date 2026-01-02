<?php

namespace Bitrix\Crm\Controller\Autorun;

use Bitrix\Crm\Controller\Autorun\Dto\PreparedData;
use Bitrix\Crm\Controller\Autorun\Dto\SetStagePreparedData;
use Bitrix\Crm\Integration\Analytics\Builder\Entity\ChangeStageEvent;
use Bitrix\Crm\Integration\Analytics\Builder\Entity\CloseEvent;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Operation\TransactionWrapper;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Result;

final class SetStage extends Base
{
	protected function isEntityTypeSupported(Factory $factory): bool
	{
		return $factory->isStagesEnabled();
	}

	protected function getPreparedDataDtoClass(): string
	{
		return SetStagePreparedData::class;
	}

	protected function prepareData(
		string $hash,
		string $gridId,
		int $entityTypeId,
		array $filter,
		array $params,
		Factory $factory
	): Dto\SetStagePreparedData
	{
		$stageId = $params['stageId'] ?? null;
		if (is_string($stageId))
		{
			if (!$factory->getStage($stageId))
			{
				// we want DTO validation to fail if a stage with this id doesn't exist
				$stageId = null;
			}
		}

		return new SetStagePreparedData([
			'hash' => $hash,
			'gridId' => $gridId,
			'entityTypeId' => $entityTypeId,
			'filter' => $filter,
			'stageId' => $stageId,
		]);
	}

	protected function isItemShouldBeSkipped(Factory $factory, Item $item, PreparedData $data): bool
	{
		if (!($data instanceof SetStagePreparedData))
		{
			throw new ArgumentTypeException('data', SetStagePreparedData::class);
		}

		return $item->getStageId() === $data->stageId;
	}

	protected function isWrapItemProcessingInTransaction(): bool
	{
		return false;
	}

	protected function processItem(Factory $factory, Item $item, PreparedData $data): Result
	{
		if (!($data instanceof SetStagePreparedData))
		{
			throw new ArgumentTypeException('data', SetStagePreparedData::class);
		}

		$item->setStageId($data->stageId);

		$operation = $factory->getUpdateOperation($item);

		return (new TransactionWrapper($operation))->launch();
	}

	protected function sendAnalyticsData(PreparedData $data, array $response): void
	{
		/** @var SetStagePreparedData $data */
		$stageSemantic = Container::getInstance()
			->getFactory($data->entityTypeId)
			?->getStageSemantics($data->stageId)
		;

		if (PhaseSemantics::isFinal($stageSemantic))
		{
			$this->sendCloseEventAnalyticsData($data);
		}
		elseif ($stageSemantic === PhaseSemantics::PROCESS)
		{
			$this->sendChangeStageEventAnalyticsData($data);
		}
	}

	private function sendCloseEventAnalyticsData(PreparedData $data): void
	{
		$stageSemantic = Container::getInstance()
			->getFactory($data->entityTypeId)
			?->getStageSemantics($data->stageId)
		;
		$element = $stageSemantic === PhaseSemantics::SUCCESS
			? Dictionary::ELEMENT_GRID_GROUP_ACTIONS_WON_STAGE
			: Dictionary::ELEMENT_GRID_GROUP_ACTIONS_LOSE_STAGE;

		if ($this->progress->hasSuccessIds())
		{
			$this->submitCloseEvent($data->entityTypeId, $element, $this->progress->getSuccessIds(), Dictionary::STATUS_SUCCESS);
		}

		if ($this->progress->hasErrorIds())
		{
			$this->submitCloseEvent($data->entityTypeId, $element, $this->progress->getErrorIds(), Dictionary::STATUS_ERROR);
		}
	}

	private function submitCloseEvent(int $entityTypeId, string $element, array $ids, string $status): void
	{
		$builder = CloseEvent::createDefault($entityTypeId)
			->setSection(Dictionary::getAnalyticsEntityType($entityTypeId) . '_section')
			->setSubSection(Dictionary::SUB_SECTION_LIST)
			->setElement($element)
		;

		$statusData = $builder
			->setP2('id', implode(',', $ids))
			->setStatus($status)
		;

		$statusData->buildEvent()->send();
	}

	private function sendChangeStageEventAnalyticsData(SetStagePreparedData|PreparedData $data): void
	{
		if ($this->progress->hasSuccessIds())
		{
			$this->submitChangeStageEvent($data->entityTypeId, $this->progress->getSuccessIds(), Dictionary::STATUS_SUCCESS);
		}

		if ($this->progress->hasErrorIds())
		{
			$this->submitChangeStageEvent($data->entityTypeId, $this->progress->getErrorIds(), Dictionary::STATUS_ERROR);
		}
	}

	private function submitChangeStageEvent(int $entityTypeId, array $ids, string $status): void
	{
		$builder = ChangeStageEvent::createDefault($entityTypeId)
			->setSection(Dictionary::getAnalyticsEntityType($entityTypeId) . '_section')
			->setSubSection(Dictionary::SUB_SECTION_LIST)
			->setElement(Dictionary::ELEMENT_GRID_ROW_CONTEXT_MENU)
		;

		$statusData = $builder
			->setP2('entityCount', count($ids))
			->setStatus($status)
		;

		$statusData->buildEvent()->send();
	}
}
