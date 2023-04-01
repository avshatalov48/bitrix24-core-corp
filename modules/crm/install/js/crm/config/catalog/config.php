<?

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$canChangeOptionCanByZero =
	Loader::includeModule('catalog')
	&& AccessController::getCurrent()->check(ActionDictionary::ACTION_SELL_NEGATIVE_COMMODITIES_SETTINGS_EDIT)
;

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
		'main.core',
		'main.core.events',
	],
	'skip_core' => false,
	'settings' => [
		'isCanChangeOptionCanByZero' => $canChangeOptionCanByZero,
	],
];
