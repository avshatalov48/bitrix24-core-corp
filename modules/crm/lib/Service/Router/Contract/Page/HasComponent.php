<?php

namespace Bitrix\Crm\Service\Router\Contract\Page;

use Bitrix\Crm\Service\Router\Contract\Component;

interface HasComponent
{
	public function component(): Component;
}
