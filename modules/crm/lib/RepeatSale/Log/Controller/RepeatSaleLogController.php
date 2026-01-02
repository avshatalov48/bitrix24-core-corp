<?php

namespace Bitrix\Crm\RepeatSale\Log\Controller;

use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\RepeatSale\Log\Entity\RepeatSaleLog;
use Bitrix\Crm\RepeatSale\Log\Entity\RepeatSaleLogTable;
use Bitrix\Crm\RepeatSale\Log\LogItem;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Query\QueryHelper;
use Bitrix\Main\Type\DateTime;

class RepeatSaleLogController
{
	use Singleton;

	final public function add(LogItem $logItem): AddResult
	{
		return RepeatSaleLogTable::add($this->getFields($logItem));
	}

	final public function update(int $id, LogItem $logItem): UpdateResult
	{
		return RepeatSaleLogTable::update($id, $this->getFields($logItem));
	}

	final public function updateStageSemanticId(
		string $stageSemanticId,
		ItemIdentifier $itemIdentifier,
	): void
	{
		if (!PhaseSemantics::isDefined($stageSemanticId))
		{
			return;
		}

		$repeatSaleLogItem = RepeatSaleLogController::getInstance()->getByItemIdentifier($itemIdentifier);
		if (!$repeatSaleLogItem)
		{
			return;
		}

		$repeatSaleLogItem->setStageSemanticId($stageSemanticId);
		$repeatSaleLogItem->setUpdatedAt(new DateTime());
		$repeatSaleLogItem->save();

		$this->sendAnalytics($stageSemanticId);
	}

	private function sendAnalytics(string $stageSemanticId): void
	{
		$availableIds = [PhaseSemantics::SUCCESS, PhaseSemantics::FAILURE];
		if (!in_array($stageSemanticId, $availableIds, true))
		{
			return;
		}

		$event = new AnalyticsEvent(
			'rs-close-queue-item',
			Dictionary::TOOL_CRM,
			Dictionary::CATEGORY_SYSTEM_INFORM,
		);

		try
		{
			$event
				->setElement($stageSemanticId === PhaseSemantics::SUCCESS ? 'won' : 'lose')
				->send()
			;
		}
		catch (\Exception $e)
		{

		}
	}

	private function getFields(LogItem $logItem): array
	{
		return [
			'JOB_ID' => $logItem->getJobId(),
			'SEGMENT_ID' => $logItem->getSegmentId(),
			'ENTITY_TYPE_ID' => $logItem->getEntityTypeId(),
			'ENTITY_ID' => $logItem->getEntityId(),
			'PHASE_SEMANTIC_ID' => $logItem->getStageSemanticId(),
			'UPDATED_AT' => new DateTime(),
		];
	}

	public function getList(array $params = []): Collection
	{
		$select = $params['select'] ?? ['*'];
		$filter = $params['filter'] ?? [];
		$order = $params['order'] ?? [
			'ID' => 'DESC',
		];
		$offset = $params['offset'] ?? 0;
		$limit = $params['limit'] ?? 10;
		$cacheTtl = $params['cacheTtl'] ?? 0;

		$query = RepeatSaleLogTable::query()
			->setSelect($select)
			->setFilter($filter)
			->setOrder($order)
			->setOffset($offset)
			->setLimit($limit)
			->setCacheTtl($cacheTtl)
		;

		return QueryHelper::decompose($query);
	}

	final public function getById(int $id): ?RepeatSaleLog
	{
		return RepeatSaleLogTable::query()
			->setSelect(['*'])
			->setFilter(['=ID' => $id])
			->fetchObject()
		;
	}

	final public function getByItemIdentifier(ItemIdentifier $itemIdentifier): ?RepeatSaleLog
	{
		return RepeatSaleLogTable::query()
			->setSelect(['*'])
			->setFilter([
				'=ENTITY_TYPE_ID' => $itemIdentifier->getEntityTypeId(),
				'=ENTITY_ID' => $itemIdentifier->getEntityId(),
			])
			->setLimit(1)
			->fetchObject()
		;
	}

	final public function delete(int $id): DeleteResult
	{
		return RepeatSaleLogTable::delete($id);
	}

	final public function deleteByItemIdentifier(ItemIdentifier $itemIdentifier): Result
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();
		$table = RepeatSaleLogTable::getTableName();

		$entityTypeId = $itemIdentifier->getEntityTypeId();
		$entityId = $itemIdentifier->getEntityId();

		$sql = '
			DELETE FROM ' . $sqlHelper->quote($table)
			. ' WHERE ENTITY_TYPE_ID =' . $entityTypeId . ' AND ENTITY_ID = ' . $entityId;

		return Application::getConnection()->query($sql);
	}
}
