<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\View;

interface ViewRepositoryInterface
{
	public function isViewed(int $taskId, int $userId): bool;
}