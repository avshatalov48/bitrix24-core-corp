<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

interface TariffRestrictionRepositoryInterface
{
	public function getGanttLinkCount(int $userId): int;
}
