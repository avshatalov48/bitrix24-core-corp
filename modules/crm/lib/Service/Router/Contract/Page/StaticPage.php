<?php

namespace Bitrix\Crm\Service\Router\Contract\Page;

use Bitrix\Crm\Feature\BaseFeature;
use Bitrix\Crm\Service\Router\Contract\Page;
use Bitrix\Crm\Service\Router\Dto\SidePanelAnchorRule;
use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Crm\Service\Router\Route;

interface StaticPage extends Page
{
	/**
	 * @return Route[]
	 */
	public static function routes(): array;

	/**
	 * @return Scope[]
	 */
	public static function scopes(): array;

	public static function isActive(): bool;

	/**
	 * @return SidePanelAnchorRule[]
	 */
	public static function getSidePanelAnchorRules(): array;
}
