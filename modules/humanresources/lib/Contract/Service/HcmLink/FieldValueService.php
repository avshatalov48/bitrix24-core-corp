<?php

namespace Bitrix\HumanResources\Contract\Service\HcmLink;

use Bitrix\HumanResources\Result\Service\HcmLink\GetFieldValueResult;
use Bitrix\HumanResources\Result\Service\HcmLink\JobServiceResult;
use Bitrix\Main\Result;

interface FieldValueService
{
	/**
	 *
	 * @param int $companyId
	 * @param list<int> $employeeIds
	 * @param list<int> $fieldIds
	 * @param array<int, int> $documentIdByEmployeeIdMap
	 *
	 * @return Result|JobServiceResult
	 */
	public function requestFieldValue(
		int $companyId,
		array $employeeIds,
		array $fieldIds,
		array $documentIdByEmployeeIdMap = [],
	): Result|JobServiceResult;

	public function getFieldValue(array $entityIds, array $fieldIds): Result|GetFieldValueResult;
}