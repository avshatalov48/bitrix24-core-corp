<?php

namespace Bitrix\Crm\Service\Router\PagePreparation;

use Bitrix\Crm\Service\Router\Contract\Page;
use Bitrix\Crm\Service\Router\Contract\PagePreparation;
use Bitrix\Crm\Service\Router\Dto\UserDefinedPageResult;
use Bitrix\Main\Routing\Route;

final class UserDefinedPagePreparation implements PagePreparation
{
	public function __construct(
		private readonly UserDefinedPageResult $result,
		private readonly Route $route,
	)
	{
	}

	public function prepare(): Page
	{
		$values = $this->route->getParametersValues()->getValues();

		return $this->result->page->setParameters($values);
	}
}
