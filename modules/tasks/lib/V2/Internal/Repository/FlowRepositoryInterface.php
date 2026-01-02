<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity;

interface FlowRepositoryInterface
{
	public function getById(int $id): ?Entity\Flow;
}