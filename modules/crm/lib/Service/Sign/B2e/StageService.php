<?php

namespace Bitrix\Crm\Service\Sign\B2e;

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

	public function removeStagesByEntityId(string $entityId): bool
	{
		return StatusTable::deleteByEntityId($entityId)->isSuccess();
	}
}
