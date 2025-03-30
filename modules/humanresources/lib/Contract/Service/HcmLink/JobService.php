<?php

namespace Bitrix\HumanResources\Contract\Service\HcmLink;

use Bitrix\HumanResources\Item\HcmLink\Job;
use Bitrix\HumanResources\Result\Service\HcmLink\JobServiceResult;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

interface JobService
{
	/**
	 * @param null|array{error?: array, result?: mixed} $inputData
	 */
	public function update(Job $job): ?Job;

	/**
	 * @param int $companyId
	 * @param bool $isForced - if is true, a new job will be created
	 * @return Result|JobServiceResult
	 */
	public function requestEmployeeList(int $companyId, bool $isForced = false): Result|JobServiceResult;

	public function requestFieldValue(
		int $companyId,
		array $employeeUids,
		array $fieldUids
	): JobServiceResult|Result;

	public function completeMapping(int $companyId): Result|JobServiceResult;

	public function getLastUserListJob(?DateTime $date, int $companyId, array $statuses): ?Job;

	public function sendJob(Job $job): Result;
}