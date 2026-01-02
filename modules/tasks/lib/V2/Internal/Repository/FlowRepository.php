<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\FlowMapper;

class FlowRepository implements FlowRepositoryInterface
{
	public function __construct(
		private readonly FlowMapper $flowMapper
	)
	{

	}
	public function getById(int $id): ?Entity\Flow
	{
		$flow = FlowRegistry::getInstance()->get($id);
		if ($flow === null)
		{
			return null;
		}

		return $this->flowMapper->mapToEntity($flow);
	}
}