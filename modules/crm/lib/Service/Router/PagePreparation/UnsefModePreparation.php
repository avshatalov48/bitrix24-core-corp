<?php

namespace Bitrix\Crm\Service\Router\PagePreparation;

use Bitrix\Crm\Service\Router;
use Bitrix\Crm\Service\Router\Component\Component;
use Bitrix\Crm\Service\Router\Contract\Page;
use Bitrix\Crm\Service\Router\Contract\PagePreparation;
use Bitrix\Main\HttpRequest;
use CCrmOwnerType;

final class UnsefModePreparation implements PagePreparation
{
	public function __construct(
		private readonly Router $routerService,
		private readonly HttpRequest $request,
	)
	{
	}

	public function prepare(): ?Page
	{
		$parseResult = $this->routerService->parseRequestParameters($this->request);
		if (!$parseResult->isFound())
		{
			return null;
		}

		$component = new Component(
			$parseResult->getComponentName(),
			$parseResult->getComponentParameters(),
			$parseResult->getTemplateName(),
		);

		$entityTypeId = $parseResult->getEntityTypeId();
		if (CCrmOwnerType::IsDefined($entityTypeId))
		{
			$component->setParameter('entityTypeId', $entityTypeId);
		}

		return new Router\Page\ComponentPage($component);
	}
}
