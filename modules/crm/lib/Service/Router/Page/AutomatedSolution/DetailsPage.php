<?php

namespace Bitrix\Crm\Service\Router\Page\AutomatedSolution;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router\AbstractPage;
use Bitrix\Crm\Service\Router\Component\Component;
use Bitrix\Crm\Service\Router\Component\SidePanelWrapper;
use Bitrix\Crm\Service\Router\Contract;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorOptions;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorRule;
use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Crm\Service\Router\PageValidator\ExternalDynamicAvailabilityValidator;
use Bitrix\Crm\Service\Router\Route;
use Bitrix\Main\HttpRequest;

final class DetailsPage extends AbstractPage
{
	private const COMPONENT_NAME = 'bitrix:crm.automated_solution.details';

	public function __construct(
		private readonly int $id,
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
				'id' => $this->id,
			],
		);
	}

	protected function configureSidePanel(SidePanelWrapper $sidePanel): void
	{
		$sidePanel->pageMode = false;
		$sidePanel->pageModeBackUrl = Container::getInstance()->getRouter()->getAutomatedSolutionListUrl();
	}

	public static function routes(): array
	{
		return [
			(new Route('type/automated_solution/details/{id}/'))
				->setRelatedComponent(self::COMPONENT_NAME)
			,
		];
	}

	public static function scopes(): array
	{
		return [
			Scope::Automation,
		];
	}

	public static function getSidePanelAnchorRules(): array
	{
		return [
			(new SidePanelAnchorRule("type/automated_solution/details/(\d+)/?$"))
				->scopes(self::scopes())
				->configureOptions(function (SidePanelAnchorOptions $options) {
					$options
						->setWidth(876)
						->setCacheable(false)
						->setAllowChangeHistory(false)
					;
				})
			,
		];
	}

	protected function getPageValidators(): array
	{
		return [
			...parent::getPageValidators(),
			new ExternalDynamicAvailabilityValidator(),
		];
	}
}
