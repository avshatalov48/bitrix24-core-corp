<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Provider;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Internal\EO_FlowCopilotCollectedData_Collection;
use Bitrix\Tasks\Flow\Internal\FlowCopilotAdviceTable;
use Bitrix\Tasks\Flow\Internal\FlowCopilotCollectedDataTable;
use Bitrix\Tasks\Internals\Log\Logger;
use Throwable;

class CollectedDataProvider
{
	public function get(int $flowId): CollectedData
	{
		try
		{
			$object = FlowCopilotCollectedDataTable::getById($flowId)->fetchObject();
			$data = $object?->getData() ?? [];
			$status = CollectedDataStatus::tryFrom($object?->getStatus() ?? '');

			return new CollectedData($flowId, $data, $status);
		}
		catch (Throwable $t)
		{
			Logger::logThrowable($t);

			return new CollectedData($flowId);
		}
	}

	public function getFlowAdviceInfoByFlowIds(int ...$flowIds): array
	{
		$result = [];

		foreach ($flowIds as $flowId)
		{
			$result[$flowId] = [
				'STATUS' => null,
				'IS_ADVICE_EXISTS' => false,
			];
		}

		try {
			$query = FlowCopilotCollectedDataTable::query()
				->setSelect([
					'FLOW_ID',
					'STATUS',
					new ExpressionField(
						'IS_ADVICE_EXISTS',
						'CASE WHEN %s IS NOT NULL THEN 1 ELSE 0 END',
						['ADVICE_JOIN.ADVICE']
					)
				])
				->registerRuntimeField(
					new Reference(
						'ADVICE_JOIN',
						FlowCopilotAdviceTable::class,
						Join::on('this.FLOW_ID', 'ref.FLOW_ID'),
						Join::TYPE_INNER,
					)
				)
				->whereIn('FLOW_ID', $flowIds)
			;

			foreach ($query->fetchAll() as $item)
			{
				$status = CollectedDataStatus::tryFrom($item['STATUS'] ?? '');

				$result[$item['FLOW_ID']] = [
					'STATUS' => $status,
					'IS_ADVICE_EXISTS' => (bool)$item['IS_ADVICE_EXISTS'],
				];
			}
		}
		catch (Throwable $t)
		{
			Logger::logThrowable($t);
		}
		finally
		{
			return $result;
		}
	}

	public function getFlowIdsByStatus(CollectedDataStatus $status, int $limit = 0): ?EO_FlowCopilotCollectedData_Collection
	{
		try
		{
			return FlowCopilotCollectedDataTable::query()
				->setSelect(['FLOW_ID'])
				->where('STATUS', $status->value)
				->setLimit($limit)
				->fetchCollection()
			;
		}
		catch (SystemException $e)
		{
			Logger::logThrowable($e);

			return null;
		}
	}

	public function getStatus(int $flowId): ?CollectedDataStatus
	{
		try
		{
			$data = FlowCopilotCollectedDataTable::query()
				->setSelect(['STATUS'])
				->where('FLOW_ID', $flowId)
				->fetch()
			;

			return CollectedDataStatus::tryFrom($data['STATUS'] ?? '');
		}
		catch (SystemException $e)
		{
			Logger::logThrowable($e);

			return null;
		}
	}
}
