<?php

namespace Bitrix\HumanResources\Contract\Repository\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\JobCollection;
use Bitrix\HumanResources\Item\HcmLink\Job;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Type\HcmLink\JobStatus;
use Bitrix\Main\Type\DateTime;

interface JobRepository
{
	public function add(Job $job): Job;

	public function update(Job $job): Job;

	public function checkIsDone(int $jobId): bool;

	public function getById(int $jobId): ?Job;

	/**
	 * @param list<int> $statusList
	 * @param DateTime $date
	 * @param int $limit
	 *
	 * @return JobCollection
	 */
	public function listByStatusListAndDate(
		array $statusList,
		DateTime $date,
		int $limit = 100,
	): Item\Collection\HcmLink\JobCollection;

	/**
	 * @param list<int> $ids
	 * @param JobStatus $status
	 *
	 * @return void
	 */
	public function updateStatusByIds(array $ids, JobStatus $status): void;

	/**
	 * @param DateTime $olderThanDate
	 * @param int $limit
	 *
	 * @return list<int>
	 */
	public function listIdsByDate(DateTime $olderThanDate, int $limit = 100): array;

	/**
	 * @param list<int> $ids
	 *
	 * @return void
	 */
	public function removeByIds(array $ids): void;
}
