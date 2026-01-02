<?php

namespace Bitrix\Crm\Service\Router\Page\Item;

use Bitrix\Crm\Service\Router\AbstractPage;
use Bitrix\Crm\Service\Router\Component\Component;
use Bitrix\Crm\Service\Router\Component\SidePanelWrapper;
use Bitrix\Crm\Service\Router\Contract;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorOptions;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorRule;
use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Crm\Service\Router\Route;

final class AutomationPage extends AbstractListPage
{
	private const COMPONENT_NAME = 'bitrix:crm.item.automation';

	public static function routes(): array
	{
		return [
			(new Route('type/{entityTypeId}/automation/{categoryId}/'))
				->setRelatedComponent(self::COMPONENT_NAME)
			,
		];
	}

	protected function getComponentName(): string
	{
		return self::COMPONENT_NAME;
	}

	protected function configureSidePanel(SidePanelWrapper $sidePanel): void
	{
		$sidePanel->isUseToolbar = true;
		$sidePanel->isUseBitrix24Theme = true;
		$sidePanel->defaultBitrix24Theme = 'light:robots';
	}

	public static function getSidePanelAnchorRules(): array
	{
		return [
			(new SidePanelAnchorRule("type/(\d+)/automation/(\d+)/"))
				->scopes(self::scopes())
				->stopParameters(['id'])
				->configureOptions(function (SidePanelAnchorOptions $options) {
					$options
						->setLoader('bizproc:automation-loader')
						->setCacheable(false)
						->setCustomLeftBoundary(0)
					;
				})
			,
			(new SidePanelAnchorRule("type/(\d+)/automation/(\d+)/"))
				->scopes(self::scopes())
				->configureOptions(function (SidePanelAnchorOptions $options) {
					$options
						->setLoader('bizproc:automation-loader')
						->setCacheable(false)
					;
				})
			,
		];
	}
}
