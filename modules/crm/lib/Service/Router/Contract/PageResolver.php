<?php

namespace Bitrix\Crm\Service\Router\Contract;

use Bitrix\Main\HttpRequest;
use Bitrix\Main\Routing\Router;

interface PageResolver
{
	public function router(): Router;

	public function resolve(HttpRequest $request): ?Page;
}
