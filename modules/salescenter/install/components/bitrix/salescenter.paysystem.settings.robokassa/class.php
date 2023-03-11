<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\SalesCenter;
use Bitrix\SaleScenter\Controller\Engine\ActionFilter\CheckWritePermission;

Loc::loadLanguageFile(__FILE__);

if (!Loader::includeModule('sale'))
{
	ShowError(Loc::getMessage('SALESCENTER_SPSR_COMPONENT_MODULE_SALE_REQUIRED_ERROR'));
	return;
}

CBitrixComponent::includeComponentClass('bitrix:sale.paysystem.settings.robokassa');

class SalesCenterPaySystemSettingsRobokassa extends SalePaySystemSettingsRobokassa
{
	protected static array $requiredModules = ['sale', 'salescenter'];

	public function configureActions()
	{
		return [
			'save' => [
				'+prefilters' => [
					new CheckWritePermission(),
				]
			],
		];
	}

	protected function hasPermission(): bool
	{
		return SalesCenter\Integration\SaleManager::getInstance()->isFullAccess(true);
	}
}
