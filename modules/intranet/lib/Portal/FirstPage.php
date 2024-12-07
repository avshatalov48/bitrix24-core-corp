<?php

namespace Bitrix\Intranet\Portal;

use Bitrix\Intranet\Internals\Trait\Singleton;
use Bitrix\Intranet\Site\FirstPage\FirstPageProvider;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Web\Uri;

class FirstPage
{
	use Singleton;
	private const FIRST_PAGE_LINK_CACHE = 'FIRST_PAGE_LINK';
	private const FIRST_PAGE_LINK_CACHE_DIR = 'intranet/first_page';
	private ?Uri $firstPageUri = null;
	private Cache $cache;

	/**
	 * @var FirstPage[]|null $pages
	 */
	private ?array $pages;

	protected function __construct()
	{
		$this->cache = Cache::createInstance();
	}

	public function getUri(): Uri
	{
		$this->firstPageUri ??= $this->getUriFromCache();

		return $this->firstPageUri;
	}

	public function getLink(): string
	{
		return $this->getUri()->getUri();
	}

	public function clearCache(): void
	{
		$this->cache->clean(self::FIRST_PAGE_LINK_CACHE, self::FIRST_PAGE_LINK_CACHE_DIR);
	}

	public function clearCacheForAll(): void
	{
		$this->cache->cleanDir(self::FIRST_PAGE_LINK_CACHE_DIR);
	}

	private function getUriFromCache(): Uri
	{
		if (
			$this->cache->initCache(
				86400,
				self::FIRST_PAGE_LINK_CACHE,
				self::FIRST_PAGE_LINK_CACHE_DIR
			)
		)
		{
			$firstPage = $this->cache->getVars();

			if (is_string($firstPage) && !empty($firstPage))
			{
				return new Uri($firstPage);
			}
		}

		$firstPage = $this->getDefaultUri();
		$this->cache->endDataCache($firstPage->getUri());

		return $firstPage;
	}

	private function getDefaultUri(): Uri
	{
		return (new FirstPageProvider())->getAvailablePage()->getUri();
	}
}