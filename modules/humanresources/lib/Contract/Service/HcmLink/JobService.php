<?php

namespace Bitrix\HumanResources\Contract\Service\HcmLink;

use Bitrix\HumanResources\Item\HcmLink\Job;
use Bitrix\HumanResources\Result\Service\HcmLink\JobServiceResult;
use Bitrix\HumanResources\Type\HcmLink\JobStatus;
use Bitrix\Main\Result;

interface JobService
{
	/**
	 * @param null|array{error?: array, result?: mixed} $inputData
	 */
	public function update(Job $job): ?Job;

	public function requestEmployeeList(int $companyId): Result|JobServiceResult;

	public function requestFieldValue(
		int $companyId,
		array $employeeUids,
		array $fieldUids
	): JobServiceResult|Result;

	public function completeMapping(int $companyId): Result|JobServiceResult;
}