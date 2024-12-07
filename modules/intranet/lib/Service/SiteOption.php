<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Intranet\Contract\OptionContract;
use Bitrix\Main\Config\Option;

class SiteOption implements OptionContract
{
	public function __construct(
		protected readonly string $siteId,
		protected readonly string $moduleName = 'intranet'
	)
	{}

	public function get(string $key, mixed $default = null): mixed
	{
		return Option::get($this->moduleName, $key, null, $this->siteId)
			?? Option::get($this->moduleName, $key, $default);
	}

	public function set(string $key, mixed $value): void
	{
		Option::set($this->moduleName, $key, $value, $this->siteId);
	}
}