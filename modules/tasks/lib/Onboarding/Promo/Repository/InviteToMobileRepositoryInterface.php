<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Promo\Repository;

interface InviteToMobileRepositoryInterface
{
	public function needToShow(int $userId): ?bool;
	public function setNeedToShow(int $userId): void;
	public function setShown(int $userId): void;
}
