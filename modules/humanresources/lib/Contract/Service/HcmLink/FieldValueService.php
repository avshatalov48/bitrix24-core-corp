<?php

namespace Bitrix\HumanResources\Contract\Service\HcmLink;

use Bitrix\HumanResources\Result\Service\HcmLink\GetFieldValueResult;
use Bitrix\HumanResources\Result\Service\HcmLink\JobServiceResult;
use Bitrix\Main\Result;

interface FieldValueService
{
	public function requestFieldValue(int $companyId, array $employeeIds, array $fieldIds): Result|JobServiceResult;

	public function getFieldValue(array $employeeIds, array $fieldIds): Result|GetFieldValueResult;
}