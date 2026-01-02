<?php

namespace Bitrix\Crm\Service\Router\Page;

use Bitrix\Crm\Service\Router\AbstractPage;
use Bitrix\Crm\Service\Router\Component\Component;
use Bitrix\Crm\Service\Router\Component\SidePanelWrapper;
use Bitrix\Crm\Service\Router\Contract;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorOptions;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorRule;
use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Crm\Service\Router\Route;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Request;

final class PermissionsPage extends AbstractPage
{
	public function __construct(
		private readonly string $criterion,
		HttpRequest $request,
		?Scope $currentScope,
	)
	{
		parent::__construct($request, $currentScope);
	}

	public function component(): Contract\Component
	{
		return new Component(
			name: 'bitrix:crm.config.perms.wrapper',
			parameters: [
				'criterion' => $this->criterion,
			],
		);
	}

	public static function routes(): array
	{
		return [
			new Route('perms/{criterion}/'),
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
			(new SidePanelAnchorRule("perms/[A-Za-z0-9-_]+/?"))
				->scopes(self::scopes())
				->configureOptions(function (SidePanelAnchorOptions $options) {
					$options
						->setCacheable(false)
						->setAllowChangeHistory(false)
						->setCustomLeftBoundary(0)
					;
				})
			,
			(new SidePanelAnchorRule([ "perms/?", "perms/[A-Za-z0-9-_]+/?" ]))
				->scopes(Scope::AutomatedSolutionWithoutPage)
				->configureOptions(function (SidePanelAnchorOptions $options){
					$options
						->setCacheable(false)
						->setAllowChangeHistory(true)
						->setCustomLeftBoundary(0)
					;
				})
			,
		];
	}

	protected function configureSidePanel(SidePanelWrapper $sidePanel): void
	{
		$sidePanel->isUsePadding = false;
		$sidePanel->isUseToolbar = false;
		$sidePanel->isHideToolbar = true;
	}
}
