<?php

namespace Bitrix\Crm\Service\Router\Contract\Page;

use \Bitrix\Crm\Service\Router\Contract\Page;
use Bitrix\Crm\Service\Router\Route;

interface UserDefinedPage extends Page
{
	public function route(): Route;

	public function setParameters(array $parameters = []): static;
}
