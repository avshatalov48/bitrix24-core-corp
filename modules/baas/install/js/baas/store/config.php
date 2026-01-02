<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main;
use \Bitrix\Baas;
use \Bitrix\Baas\Public\Provider\ServiceProvider;

$settings = [
	'isBitrix24License' => Main\ModuleManager::isModuleInstalled('bitrix24'),
];

if (Main\Loader::includeModule('baas'))
{
	$settings['pull'] = Baas\Integration\Pull\Channel::getSettings();
	Baas\Integration\Pull\Channel::subscribe();

	$settings['isCurrentUserAdmin'] = Baas\Entity\CurrentUser::get()->isAdmin();
	$settings['isBaasActive'] = Baas\Baas::getInstance()->isActive();
	$settings['canBaasOnlyBePurchasedByAdmin'] = Baas\Baas::getInstance()->isSellableToAll() === false;

	$services = [];
	foreach (ServiceProvider::create()->getList() as $service)
	{
		$services[$service->getCode()] = $service->jsonSerialize();
	}
	$settings['services'] = $services;
}

return [
	'css' => 'dist/list.bundle.css',
	'js' => 'dist/list.bundle.js',
	'rel' => [
		'ui.icons.b24',
		'ui.progressbar',
		'main.date',
		'ui.label',
		'bitrix24.license',
		'ui.notification',
		'ui.analytics',
		'ui.buttons',
		'main.popup',
		'ui.icon-set.api.core',
		'ui.popup-with-header',
		'ui.info-helper',
		'main.core',
		'main.core.events',
	],
	'skip_core' => false,
	'settings' => $settings
];
