<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity;

class InMemoryFlowRepository implements FlowRepositoryInterface
{
	private FlowRepositoryInterface $flowRepository;

	private array $cache = [];

	public function __construct(FlowRepository $flowRepository)
	{
		$this->flowRepository = $flowRepository;
	}

	public function getById(int $id): ?Entity\Flow
	{
		if (!isset($this->cache[$id]))
		{
			$this->cache[$id] = $this->flowRepository->getById($id);
		}

		return $this->cache[$id];
	}
}