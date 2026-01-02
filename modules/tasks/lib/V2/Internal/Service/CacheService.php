<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service;

use CCacheManager;

class CacheService
{
	private CCacheManager $cacheManager;

	public function __construct()
	{
		global $CACHE_MANAGER;

		$this->cacheManager = $CACHE_MANAGER;
	}

	public function clearByTag(string $tag): void
	{
		$this->cacheManager->ClearByTag($tag);
	}

	public function clearByTagMulti(string $tagPart, array $ids, string $separator = '_'): void
	{
		foreach ($ids as $id)
		{
			$this->clearByTag("{$tagPart}{$separator}{$id}");
		}
	}
}