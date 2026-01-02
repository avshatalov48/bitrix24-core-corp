<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Internal\Queue;

use Bitrix\Main\Result;
use Bitrix\Tasks\Onboarding\Transfer\JobCodes;
use Bitrix\Tasks\Onboarding\Transfer\Pair;
use Bitrix\Tasks\Onboarding\Transfer\CommandModelCollection;
use Bitrix\Tasks\Onboarding\Transfer\JobIds;
use Bitrix\Tasks\Onboarding\Transfer\UserJob;

interface QueueServiceInterface
{
	public function add(CommandModelCollection $commandModels): Result;
	public function markAsProcessing(JobIds $jobIds): Result;
	public function unmarkAsProcessing(JobIds $jobIds): Result;
	public function deleteByIds(JobIds $jobIds): Result;
	public function deleteByCodes(JobCodes $jobCodes): Result;
	public function deleteByPair(Pair $pair): Result;
	public function deleteByUserJob(UserJob $userJob): Result;
}