<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main;
use \Bitrix\Baas;

$settings = [];

if (Main\Loader::includeModule('baas'))
{
	$settings['pull'] = Baas\Integration\Pull\Channel::getSettings();
	Baas\Integration\Pull\Channel::subscribe();

	$settings['isCurrentUserAdmin'] = Baas\Entity\CurrentUser::get()->isAdmin();
	$settings['isBaasActive'] = Baas\Baas::getInstance()->isActive();
	$settings['canBaasOnlyBePurchasedByAdmin'] = Baas\Baas::getInstance()->isSellableToAll() === false;

	$settings['services'] = [];
	foreach (Baas\Baas::getInstance()->getServiceManager()->getAll() as $service)
	{
		$settings['services'][$service->getCode()] = [
			'isAvailable' => $service->isAvailable(),
			'isActive' => $service->isActive(),
			'advertisingStrategy' => $service->getAdvertisingStrategy(),
		];
	}
}

return [
	'js' => 'dist/service.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
	],
	'skip_core' => false,
	'settings' => $settings
];
