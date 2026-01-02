<?php

namespace Bitrix\Crm\Service\Router\Page\AutomatedSolution;

use Bitrix\Crm\Security\Role\Manage\Manager\CustomSectionListSelection;
use Bitrix\Crm\Service\Router\AbstractPage;
use Bitrix\Crm\Service\Router\Component\Component;
use Bitrix\Crm\Service\Router\Component\SidePanelWrapper;
use Bitrix\Crm\Service\Router\Contract;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorOptions;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorRule;
use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Crm\Service\Router\PageValidator\ExternalDynamicAvailabilityValidator;
use Bitrix\Crm\Service\Router\Route;

final class PermissionsPage extends AbstractPage
{
	public function component(): Contract\Component
	{
		return new Component(
			name: 'bitrix:crm.config.perms.wrapper',
			parameters: [
				'criterion' => CustomSectionListSelection::CRITERION,
				'isAutomation' => true,
			],
		);
	}

	public static function routes(): array
	{
		return [
			new Route('type/automated_solution/permissions/'),
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
			(new SidePanelAnchorRule("type/automated_solution/permissions/?"))
				->scopes(self::scopes())
				->configureOptions(function (SidePanelAnchorOptions $options) {
					$options
						->setCacheable(false)
						->setAllowChangeHistory(false)
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

	protected function getPageValidators(): array
	{
		return [
			...parent::getPageValidators(),
			new ExternalDynamicAvailabilityValidator(),
		];
	}
}
