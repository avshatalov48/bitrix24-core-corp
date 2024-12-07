<?php

namespace Bitrix\Recyclebin\Controller\Action;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Recyclebin\Internals\BatchActionManager;
use Bitrix\Recyclebin\Internals\Filter\Filter;
use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;
use Bitrix\Recyclebin\Recyclebin;

trait ProcessTrait
{
	protected function doAction(array $params): array
	{
		$hash = $params['hash'];

		$batchActionManager = new BatchActionManager();
		$data = $batchActionManager->getFromSession(
			$this->getDataSessionName(),
			$hash
		);

		if (!is_array($data))
		{
			return [
				'status' => 'COMPLETED',
				'processedItems' => 0,
				'totalItems' => 0,
			];
		}

		$hash = $data['HASH'] ?? '';
		$gridId = $data['GRID_ID'] ?? '';

		$progressData = $batchActionManager->getFromSession(
			$this->getProgressSessionName(),
			$hash
		);

		$entityIds = $data['ENTITY_IDS'] ?? null;
		if (is_array($entityIds))
		{
			$result = $this->doActionByIds($hash, $gridId, $entityIds, $progressData);
		}
		else
		{
			$filter = new Filter($gridId, [
				'moduleId' => $params['moduleId'] ?? null,
			]);

			$result = $this->doActionByFilter($hash, $gridId, $filter, $progressData);
		}

		if (isset($result['STATUS']) && $result['STATUS'] === 'COMPLETED')
		{
			$batchActionManager->deleteFromSession(
				$this->getProgressSessionName(),
				$hash
			);
			$batchActionManager->deleteFromSession(
				$this->getDataSessionName(),
				$hash
			);
		}
		else
		{
			$batchActionManager->addToSession(
				$this->getProgressSessionName(),
				$hash,
				$progressData
			);
		}

		return $result;
	}

	protected function doActionByIds(string $hash, string $gridId, array $entityIds, array &$progressData = null): array
	{
		if (!is_array($progressData))
		{
			$progressData = [
				'HASH' => $hash,
				'GRID_ID' => $gridId,
				'CURRENT_ENTITY_INDEX' => 0,
				'PROCESSED_COUNT' => 0
			];
		}

		$currentEntityIndex = (int)($progressData['CURRENT_ENTITY_INDEX'] ?? 0);
		$processedCount = (int)($progressData['PROCESSED_COUNT'] ?? 0);

		$totalCount = count($entityIds);
		if ($currentEntityIndex < 0)
		{
			$currentEntityIndex = 0;
		}

		$errors = [];
		for ($i = 0; $i < self::LIMIT; $i++)
		{
			if($currentEntityIndex >= $totalCount)
			{
				break;
			}

			$currentEntityId = $entityIds[$currentEntityIndex];
			if ($currentEntityId)
			{
				$result = $this->doProcessAction($currentEntityId);

				if (!$result || ($result instanceof Result && !$result->isSuccess()))
				{
					$message = (
						$result instanceof Result
							? implode(', ', $result->getErrorMessages())
							: null
					);

					$this->addActionError($errors, $currentEntityId, $message);
				}
			}

			$processedCount++;
			$currentEntityIndex++;
		}

		$progressData['PROCESSED_COUNT'] = $processedCount;
		$progressData['CURRENT_ENTITY_INDEX'] = $currentEntityIndex;

		$resultData = [
			'status' => ($processedCount < $totalCount) ? 'PROGRESS' : 'COMPLETED',
			'processedItems' => $processedCount,
			'totalItems' => $totalCount,
		];

		if (!empty($errors))
		{
			$resultData['errors'] = $errors;
		}

		return $resultData;
	}

	protected function doActionByFilter($hash, $gridId, Filter $filter, array &$progressData = null): array
	{
		$fields = $filter->getPreparedFields();

		if (!is_array($progressData))
		{
			$totalCount = RecyclebinTable::getCount($fields);

			$progressData = [
				'HASH' => $hash,
				'GRID_ID' => $gridId,
				'CURRENT_ENTITY_ID' => 0,
				'PROCESSED_COUNT' => 0,
				'TOTAL_COUNT' => $totalCount,
			];
		}

		$currentEntityId = (int)($progressData['CURRENT_ENTITY_ID'] ?? 0);
		$processedCount = (int)($progressData['PROCESSED_COUNT'] ?? 0);
		$totalCount = (int)($progressData['TOTAL_COUNT'] ?? 0);

		if ($currentEntityId > 0)
		{
			$fields['>ID'] = $currentEntityId;
		}

		$list = RecyclebinTable::getList([
			'select' => [
				'ID',
			],
			'order' => [
				'ID' => 'ASC',
			],
			'filter' => $fields,
			'limit' => self::LIMIT,
		]);
		$entityIds = $list->fetchCollection()->getIdList();

		$errors = [];
		if (!empty($entityIds))
		{
			foreach ($entityIds as $entityId)
			{
				$currentEntityId = $entityId;
				$result = $this->doProcessAction($currentEntityId);

				if (!$result || $result instanceof Error)
				{
					$this->addActionError($errors, $currentEntityId);
				}

				$processedCount++;
			}
		}
		elseif ($processedCount !== $totalCount)
		{
			$processedCount = $totalCount;
		}

		$progressData['PROCESSED_COUNT'] = $processedCount;
		$progressData['CURRENT_ENTITY_ID'] = $currentEntityId;

		$resultData = [
			'status' => ($processedCount < $totalCount) ? 'PROGRESS' : 'COMPLETED',
			'processedItems' => $processedCount,
			'totalItems' => $totalCount,
		];

		if (!empty($errors))
		{
			$resultData['errors'] = $errors;
		}

		return $resultData;
	}

	private function addActionError(array &$errors, int $currentEntityId, ?string $errorMessage = null): void
	{
		$entity = Recyclebin::getEntityData($currentEntityId);

		$title = (
			$entity
				? $entity->getTitle()
				: Loc::getMessage('RECYCLEBIN_PROCESS_ACTION_ITEM', ['#NUMBER#' => $currentEntityId])
		);

		$errors[] = new Error(
			$errorMessage ?? $this->getErrorMessage(),
			0,
			[
				'info' => [
					'title' => $title,
				],
			]
		);
	}
}
