<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM\Repository;

use Bitrix\Tasks\V2\Internal\Integration\CRM\Entity\CrmItemCollection;

interface CrmItemRepositoryInterface
{
	public function getIdsByTaskId(int $taskId): array;

	public function getIdsByTaskIds(array $taskIds): array;

	public function getByIds(array $ids): CrmItemCollection;
}
