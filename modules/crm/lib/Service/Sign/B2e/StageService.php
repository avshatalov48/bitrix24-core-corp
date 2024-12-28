<?php

namespace Bitrix\Crm\Service\Sign\B2e;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\SystemException;
use Exception;

/**
 * Service for working with b2e stages.
 */
final class StageService
{
	public function getStageEntityId(int $documentCategoryId): string
	{
		return $documentCategoryId ? sprintf('SMART_B2E_DOC_STAGE_%d', $documentCategoryId) : '';
	}

	/**
	 * @throws Exception
	 */
	public function addStage(array $stage): AddResult
	{
		return StatusTable::add($stage);
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function isStagesCreated(array $stages): bool
	{
		$result = StatusTable::query()->whereIn('STATUS_ID', $stages)->fetchAll();

		return count($result) === count($stages);
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function isStagesCreatedByNames(array $stageNames, int $documentCategoryId): bool
	{
		$result = StatusTable::query()
			->where('ENTITY_ID', $this->getStageEntityId($documentCategoryId))
			->whereIn('NAME', $stageNames)
			->fetchAll()
		;

		return count($result) === count($stageNames);
	}

	public function removeByStage(string $stage, int $documentCategoryId): bool
	{
		$status = Container::getInstance()->getSignB2eStatusService()->makeName($documentCategoryId, $stage);
		$result = false;
		$stage = StatusTable::query()
			->where('ENTITY_ID', $this->getStageEntityId($documentCategoryId))
			->where('STATUS_ID', $status)
			->setLimit(1)
			->fetchObject()
		;
		if ($stage)
		{
			$deleteResult = $stage->delete();
			$result = $deleteResult->isSuccess();
		}

		return $result;
	}

	public function getByStage(string $stage, int $documentCategoryId): ?array
	{
		$status = Container::getInstance()->getSignB2eStatusService()->makeName($documentCategoryId, $stage);
		$result = null;
		$stage = StatusTable::query()
			->where('ENTITY_ID', $this->getStageEntityId($documentCategoryId))
			->where('STATUS_ID', $status)
			->setLimit(1)
			->fetchObject()
		;
		if ($stage)
		{
			$result = [
				'ID' => $stage->getId(),
				'SORT' => $stage->getSort(),
				'NAME' => $stage->getName()
			];
		}

		return $result;
	}

	public function removeStagesByEntityId(string $entityId): bool
	{
		return StatusTable::deleteByEntityId($entityId)->isSuccess();
	}
}
