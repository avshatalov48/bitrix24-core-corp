<?php

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\Config\State;
use Bitrix\Catalog\Product\Store\CostPriceCalculator;
use Bitrix\Catalog\StoreProductTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$canChangeOptionCanByZero = false;
$costPriceCalculationMethods = [];
$showNegativeStoreAmountPopup = false;
$storeBalancePopupLink = '';
$shouldShowBatchMethodSpotlight = false;

if (Loader::includeModule('catalog'))
{
	$canChangeOptionCanByZero = AccessController::getCurrent()
		->check(ActionDictionary::ACTION_SELL_NEGATIVE_COMMODITIES_SETTINGS_EDIT)
	;

	if (!State::isProductBatchMethodSelected())
	{
		$negativeBalanceItem = StoreProductTable::getList([
				'select' => ['ID'],
				'filter' => [
					'<AMOUNT' => 0,
				],
				'limit' => 1,
			])
			->fetch()
		;

		$showNegativeStoreAmountPopup = !empty($negativeBalanceItem);
	}

	$costPriceCalculationMethods = [
		[
			'code' => CostPriceCalculator::METHOD_AVERAGE,
			'title' => Loc::getMessage('CRM_CFG_C_SETTINGS_COST_PRICE_CALCULATION_MODE_AVERAGE'),
		],
		[
			'code' => CostPriceCalculator::METHOD_FIFO,
			'title' => Loc::getMessage('CRM_CFG_C_SETTINGS_COST_PRICE_CALCULATION_MODE_FIFO'),
		],
	];

	if ($showNegativeStoreAmountPopup)
	{
		$productGridComponent = 'bitrix:catalog.report.store_stock.products.grid';
		$productGridPath = \CComponentEngine::makeComponentPath($productGridComponent);

		$storeBalancePopupLink = getLocalPath('components' . $productGridPath . '/slider.php');
	}

	$shouldShowBatchMethodSpotlight =
		Option::get('catalog', 'should_show_batch_method_onboarding', 'N') === 'Y'
		&& (CUserOptions::GetOption('crm.catalog-settings', 'tour', [])['batch_spotlight_shown'] ?? 'N') === 'N'
		&& (State::isEnabledInventoryManagement())
		&& (!State::isProductBatchMethodSelected())
	;
}

return [
	'css' => [
		'dist/catalog.bundle.css',
		'/bitrix/components/bitrix/ui.button.panel/templates/.default/style.css',
		'/bitrix/js/catalog/product-form/src/component.css',
	],
	'js' => 'dist/catalog.bundle.js',
	'rel' => [
		'main.popup',
		'ui.buttons',
		'catalog.store-use',
		'ui.vue',
		'ui.notification',
		'ui.design-tokens',
		'ui.alerts',
		'main.core',
		'main.core.events',
	],
	'skip_core' => false,
	'settings' => [
		'isCanChangeOptionCanByZero' => $canChangeOptionCanByZero,
		'costPriceCalculationMethods' => $costPriceCalculationMethods,
		'showNegativeStoreAmountPopup' => $showNegativeStoreAmountPopup,
		'storeBalancePopupLink' => $storeBalancePopupLink,
		'shouldShowBatchMethodSpotlight' => $shouldShowBatchMethodSpotlight,
	],
];
