<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!\Bitrix\Main\Loader::includeModule('sign'))
{
	return [];
}

$signUntilService = \Bitrix\Sign\Service\Container::instance()->getSignUntilService();

return [
	'css' => 'dist/datetime-limit-selector.bundle.css',
	'js' => 'dist/datetime-limit-selector.bundle.js',
	'rel' => [
		'main.core',
		'ui.notification',
		'main.core.events',
		'main.date',
		'sign.v2.api',
		'ui.design-tokens',
	],
	'skip_core' => false,
	'settings' => [
		'signingMinMinutes' => $signUntilService->getMinSigningPeriodInMinutes(),
		'signingMaxMonth' => (int)$signUntilService->getMaxSigningPeriod()->format('%m'),
		'defaultSignUntilDate' => $signUntilService
			->calcDefaultSignUntilDate(new \Bitrix\Main\Type\DateTime())
			->toUserTime()
			->format('Y-m-d H:i:s')
		,
	],
];
