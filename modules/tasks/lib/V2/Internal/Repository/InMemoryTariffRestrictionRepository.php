<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

class InMemoryTariffRestrictionRepository implements TariffRestrictionRepositoryInterface
{
	private readonly TariffRestrictionRepositoryInterface $tariffRestrictionRepository;

	private array $storage = [];

	public function __construct(TariffRestrictionRepository $tariffRestrictionRepository)
	{
		$this->tariffRestrictionRepository = $tariffRestrictionRepository;
	}

	public function getGanttLinkCount(int $userId): int
	{
		if (!isset($storage['gantt_link_count'][$userId]))
		{
			$this->storage['gantt_link_count'][$userId] = $this->tariffRestrictionRepository->getGanttLinkCount($userId);
		}

		return $this->storage['gantt_link_count'][$userId];
	}
}
