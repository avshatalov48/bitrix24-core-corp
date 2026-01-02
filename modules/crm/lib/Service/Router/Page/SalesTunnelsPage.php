<?php

namespace Bitrix\Crm\Service\Router\Page;

use Bitrix\Crm\Service\Router\AbstractPage;
use Bitrix\Crm\Service\Router\Component\Component;
use Bitrix\Crm\Service\Router\Contract;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorOptions;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorRule;
use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Crm\Service\Router\PageValidator\EntityTypeAvailabilityValidator;
use Bitrix\Crm\Service\Router\Route;
use Bitrix\Main\HttpRequest;

final class SalesTunnelsPage extends AbstractPage
{
	private const COMPONENT_NAME = 'bitrix:crm.sales.tunnels';

	public function __construct(
		private readonly int $entityTypeId,
		HttpRequest $request,
		?Scope $currentScope,
	)
	{
		parent::__construct($request, $currentScope);
	}

	public function component(): Contract\Component
	{
		return new Component(
			name: self::COMPONENT_NAME,
			parameters: [
				'entityTypeId' => $this->entityTypeId,
			],
		);
	}

	public static function routes(): array
	{
		return [
			(new Route('type/{entityTypeId}/categories/'))
				->setRelatedComponent(self::COMPONENT_NAME)
			,
		];
	}

	public static function scopes(): array
	{
		return [
			Scope::Crm,
			Scope::AutomatedSolution,
		];
	}

	public static function getSidePanelAnchorRules(): array
	{
		return [
			(new SidePanelAnchorRule("type/(\d+)/categories/"))
				->scopes(self::scopes())
				->configureOptions(function (SidePanelAnchorOptions $options) {
					$options
						->setCustomLeftBoundary(40)
						->setAllowChangeHistory(false)
						->setCacheable(false)
					;
				})
			,
		];
	}

	protected function getPageValidators(): array
	{
		return [
			...parent::getPageValidators(),
			new EntityTypeAvailabilityValidator($this->entityTypeId),
		];
	}
}
