<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content\Tool;

use Bitrix\Intranet\User;
use Bitrix\Main\Loader;

class PerformanceReview extends BaseTool
{
	public static function isAvailable(User $user): bool
	{
		if (!Loader::includeModule('stafftools'))
		{
			return false;
		}

		$isExtranetSite = Loader::includeModule('extranet') && \CExtranet::IsExtranetSite();

		return !$isExtranetSite && $user->isIntranet();
	}

	public function getName(): string
	{
		return 'perfReview';
	}

	public function getConfiguration(): array
	{
		return [
			'title' => 'Performance review',
			'path' => SITE_DIR . 'company/stafftools/performance_review/passing/',
		];
	}
}
