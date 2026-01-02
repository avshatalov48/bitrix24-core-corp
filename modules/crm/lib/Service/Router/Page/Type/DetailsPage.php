<?php

namespace Bitrix\Crm\Service\Router\Page\Type;

use Bitrix\Crm\Service\Router\AbstractPage;
use Bitrix\Crm\Service\Router\Component\Component;
use Bitrix\Crm\Service\Router\Component\SidePanelWrapper;
use Bitrix\Crm\Service\Router\Contract;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorOptions;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorRule;
use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Crm\Service\Router\PageValidator\EntityTypeAvailabilityValidator;
use Bitrix\Crm\Service\Router\Route;
use Bitrix\Main\HttpRequest;

final class DetailsPage extends AbstractPage
{
	private const COMPONENT_NAME = 'bitrix:crm.type.detail';

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
			(new Route('type/detail/{entityTypeId}/'))
				->setRelatedComponent(self::COMPONENT_NAME)
			,
		];
	}

	public static function scopes(): array
	{
		return [
			Scope::Crm,
			Scope::AutomatedSolution,
			Scope::Automation,
		];
	}

	protected function configureSidePanel(SidePanelWrapper $sidePanel): void
	{
		$sidePanel->isUsePadding = false;
	}

	public static function getSidePanelAnchorRules(): array
	{
		return [
			(new SidePanelAnchorRule("type/detail/(\d+)"))
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
		$validators = parent::getPageValidators();
		if ($this->entityTypeId > 0)
		{
			$validators[] = new EntityTypeAvailabilityValidator($this->entityTypeId);
		}

		return $validators;
	}
}
