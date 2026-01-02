<?php

namespace Bitrix\Crm\Service\Router\Dto;

use Bitrix\Crm\Service\Router\Enum\Scope;

final class StaticPageResult
{
	public function __construct(
		public readonly string $pageClass,
		public readonly ?Scope $scope,
	)
	{
	}
}
