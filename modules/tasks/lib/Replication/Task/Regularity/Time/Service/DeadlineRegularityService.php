<?php

namespace Bitrix\Tasks\Replication\Task\Regularity\Time\Service;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Replication\Task\Regularity\Exception\RegularityException;
use Bitrix\Tasks\Replication\RepositoryInterface;

class DeadlineRegularityService
{
	public function __construct(private RepositoryInterface $repository)
	{
	}

	/**
	 * @throws RegularityException
	 */
	public function getRecalculatedDeadline(?DateTime $deadline = null): DateTime
	{
		$startTime = (new ExecutionService($this->repository))->getNextRegularityDateTime($deadline);

		return $startTime->add("+ {$this->getDeadlineOffsetInDays()} days");
	}

	public function getDeadlineOffsetInDays(): int
	{
		$regularity = $this->repository->getEntity()->getRegular();
		return (int)($regularity?->getRegularParameters()['DEADLINE_OFFSET'] ?? null);
	}
}