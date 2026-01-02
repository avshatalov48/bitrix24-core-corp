<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Internal\Factory;

use Bitrix\Tasks\Onboarding\Transfer\JobIds;

final class JobIdsFactory
{
	public static function createIds(array $ids): JobIds
	{
		return new JobIds($ids);
	}
}