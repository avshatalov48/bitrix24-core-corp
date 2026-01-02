<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity\Task\View;

interface ViewRepositoryInterface
{
	public function get(int $taskId, int $userId): ?View;

	public function upsert(View $view): void;
}