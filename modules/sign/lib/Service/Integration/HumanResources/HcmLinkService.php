<?php

namespace Bitrix\Sign\Service\Integration\HumanResources;

use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\HcmLink\JobStatus;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Result\Service\Integration\HumanResources\HcmLinkJobsCheckResult;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\HumanResources;
use Bitrix\Main;

class HcmLinkService
{
	public function isAvailable(): bool
	{
		if (!Main\Loader::includeModule('humanresources'))
		{
			return false;
		}

		if (!class_exists('\Bitrix\HumanResources\Config\Feature'))
		{
			return false;
		}

		return HumanResources\Config\Feature::instance()->isHcmLinkAvailable();
	}

	public function fillOneLinkedMembersWithEmployeeId(
		Document $document,
		MemberCollection $members,
		int $representativeId,
	): void
	{
		if (!$this->isAvailable() || !$document->hcmLinkCompanyId)
		{
			return;
		}

		if (!Loader::includeModule('humanresources'))
		{
			return;
		}

		$userIds = [];
		foreach ($members as $member)
		{
			if ($member->entityType === EntityType::USER)
			{
				$userIds[$member->entityId] = $member->entityId;
			}
		}
		$userIds[$representativeId] = $representativeId;
		$userIds = array_values($userIds);

		$employeesByUserIds = Container::getHcmLinkMapperService()
			->listMappedUserIdWithOneEmployeePosition($document->hcmLinkCompanyId, ...$userIds)
		;

		foreach ($members as $member)
		{
			$userId = null;
			if ($member->entityType === EntityType::USER)
			{
				$userId = $member->entityId;
			}
			elseif ($member->entityType === EntityType::COMPANY)
			{
				$userId = $representativeId;
			}

			if ($userId)
			{
				$member->employeeId = $employeesByUserIds[$userId] ?? null;
			}
		}
	}

	public function isAllJobsDone(array $jobIds): Result|HcmLinkJobsCheckResult
	{
		if (!$this->isAvailable() || !Loader::includeModule('humanresources'))
		{
			return (new Result())->addError(new Error('Integration not available'));
		}

		foreach ($jobIds as $jobId)
		{
			$job = HumanResources\Service\Container::getHcmLinkJobRepository()->getById($jobId);
			if (!$job)
			{
				return (new Result())->addError(new Error('No job found','NO_JOB_FOUND'));
			}

			if ($job->status === JobStatus::CANCELED)
			{
				return (new Result())->addError(new Error('Job canceled','JOB_CANCELED'));
			}

			if ($job->status === JobStatus::EXPIRED)
			{
				return (new Result())->addError(new Error('Job expired','JOB_EXPIRED'));
			}

			if ($job->status !== JobStatus::DONE)
			{
				return new HcmLinkJobsCheckResult(false);
			}
		}

		return new HcmLinkJobsCheckResult(true);
	}
}