<?php

namespace Bitrix\Crm\Service\Router\Dto;

use Bitrix\Crm\Service\Router\Contract\Page\UserDefinedPage;

final class UserDefinedPageResult
{
	public function __construct(
		public readonly UserDefinedPage $page,
	)
	{
	}
}
