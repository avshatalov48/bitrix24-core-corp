<?php

namespace Bitrix\Crm\Copilot\CallAssessment\Controller;

use Bitrix\Crm\Copilot\AiQualityAssessment\Controller\AiQualityAssessmentController;
use Bitrix\Crm\Copilot\CallAssessment\CallAssessmentItem;
use Bitrix\Crm\Copilot\CallAssessment\Entity\CopilotCallAssessment;
use Bitrix\Crm\Copilot\CallAssessment\Entity\CopilotCallAssessmentTable;
use Bitrix\Crm\Copilot\PullManager;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Query\QueryHelper;
use Bitrix\Main\Type\DateTime;
use CCrmOwnerType;

final class CopilotCallAssessmentController
{
	use Singleton;

	public function add(CallAssessmentItem $callAssessmentItem): AddResult
	{
		$result = CopilotCallAssessmentTable::add($this->getFields($callAssessmentItem));
		if ($result->isSuccess())
		{
			$modifyResult = $this->modifyClientTypeIds(
				$result->getId(),
				$callAssessmentItem->getClientTypeIds(),
				false
			);

			if (!$modifyResult->isSuccess())
			{
				$result->addErrors($modifyResult->getErrors());
			}
		}

		return $result;
	}

	public function update(int $id, CallAssessmentItem $callAssessmentItem, ?Context $context = null): Result
	{
		$result = CopilotCallAssessmentTable::update($id, $this->getFields($callAssessmentItem));

		if ($result->isSuccess())
		{
			$modifyResult = $this->modifyClientTypeIds($id, $callAssessmentItem->getClientTypeIds());
			if (!$modifyResult->isSuccess())
			{
				$result->addErrors($modifyResult->getErrors());
			}

			if ($context)
			{
				(new PullManager())->sendUpdateAssessmentPullEvent($id, [
					'eventId' => $context->getEventId(),
				]);
			}
		}

		return $result;
	}

	private function getFields(CallAssessmentItem $callAssessmentItem): array
	{
		return [
			'TITLE' => $callAssessmentItem->getTitle(),
			'PROMPT' => $callAssessmentItem->getPrompt(),
			'GIST' => $callAssessmentItem->getGist(),
			'CALL_TYPE' => $callAssessmentItem->getCallTypeId(),
			'AUTO_CHECK_TYPE' => $callAssessmentItem->getAutoCheckTypeId(),
			'IS_ENABLED' => $callAssessmentItem->isEnabled(),
			'IS_DEFAULT' => $callAssessmentItem->isDefault(),
			'JOB_ID' => $callAssessmentItem->getJobId(),
			'STATUS' => $callAssessmentItem->getStatus(),
			'CODE' => $callAssessmentItem->getCode(),
			'LOW_BORDER' => $callAssessmentItem->getLowBorder(),
			'HIGH_BORDER' => $callAssessmentItem->getHighBorder(),
			'UPDATED_AT' => new DateTime(),
			'UPDATED_BY_ID' => Container::getInstance()->getContext()->getUserId(),
		];
	}

	private function modifyClientTypeIds(int $assessmentId, array $clientTypeIds, bool $deleteOldRecords = true): Result
	{
		$clientTypeController = CopilotCallAssessmentClientTypeController::getInstance();

		if ($deleteOldRecords)
		{
			$clientTypeController->deleteByAssessmentId($assessmentId);
		}

		foreach ($clientTypeIds as $clientTypeId)
		{
			$addResult = $clientTypeController->add($assessmentId, $clientTypeId);
			if (!$addResult->isSuccess())
			{
				return $addResult;
			}
		}

		return new Result();
	}

	public function getList(array $params = []): Collection
	{
		$select = $params['select'] ?? ['*', 'CLIENT_TYPES.CLIENT_TYPE_ID'];
		$filter = $params['filter'] ?? [];
		$order = $params['order'] ?? [
			'ID' => 'DESC',
		];
		$offset = $params['offset'] ?? 0;
		$limit = $params['limit'] ?? 10;

		$query = CopilotCallAssessmentTable::query()
			->setSelect($select)
			->setFilter($filter)
			->setOrder($order)
			->setOffset($offset)
			->setLimit($limit)
		;

		return $this->decompose($query);
	}

	/*
	 * based on the Bitrix\Main\ORM\Query\QueryHelper::decompose,
	 * but currently it does not support sorting. ticket #204966
	 */
	private function decompose(Query $query): ?Collection
	{
		$entity = $query->getEntity();
		$queryClass = $entity->getDataClass()::getQueryClass();
		$runtimeChains = $query->getRuntimeChains() ?? [];
		$primaryNames = $entity->getPrimaryArray();
		$originalSelect = $query->getSelect();

		// select distinct primary
		$query->setSelect($entity->getPrimaryArray());
		$query->setDistinct();

		$rows = $query->fetchAll();

		// return empty result
		if (empty($rows))
		{
			return $query->getEntity()->createCollection();
		}

		// reset query
		$query = new $queryClass($entity);
		$query->setSelect($originalSelect);
		$query->where(QueryHelper::getPrimaryFilter($primaryNames, $rows));

		foreach ($runtimeChains as $chain)
		{
			$query->registerChain('runtime', $chain);
		}

		/** @var Collection $collection query data */
		$collection = $query->fetchCollection();

		$sortedCollection = $query->getEntity()->createCollection();

		foreach ($rows as $row)
		{
			$sortedCollection->add($collection->getByPrimary($row));
		}

		return $sortedCollection;
	}

	public function getById(int $id): ?CopilotCallAssessment
	{
		return CopilotCallAssessmentTable::query()
			->setSelect(['*', 'CLIENT_TYPES.CLIENT_TYPE_ID'])
			->setFilter(['ID' => $id])
			->exec()
			->fetchObject()
		;
	}

	public function getTotalCount(array $filter = []): int
	{
		return CopilotCallAssessmentTable::query()->setFilter($filter)->queryCountTotal();
	}

	public function delete(int $id): Result
	{
		if ($this->hasAssessmentCalls($id))
		{
			return (new Result())->addError(
				new Error(Loc::getMessage('COPILOT_CALL_ASSESSMENT_CONTROLLER_HAS_ASSESSMENTED_CALLS'))
			);
		}

		CopilotCallAssessmentClientTypeController::getInstance()->deleteByAssessmentId($id);

		// clean jobs if needed
		QueueTable::deleteByItem(
			new ItemIdentifier(CCrmOwnerType::CopilotCallAssessment, $id),
		);

		return CopilotCallAssessmentTable::delete($id);
	}

	private function hasAssessmentCalls(int $assessmentId): bool
	{
		$isEmpty = AiQualityAssessmentController::getInstance()->getList([
			'select' => ['ID'],
			'filter' => ['=ASSESSMENT_SETTING_ID' => $assessmentId],
			'limit' => 1,
		])->isEmpty();

		return !$isEmpty;
	}
}
