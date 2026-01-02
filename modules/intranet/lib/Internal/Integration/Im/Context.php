<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Integration\Im;

use Bitrix\Main\Loader;

class Context
{
	public function isDesktop(): bool
	{
		$available = Loader::includeModule('im');

		return $available && \Bitrix\Im\V2\Application\Context::getCurrent()->isDesktop();
	}
}
