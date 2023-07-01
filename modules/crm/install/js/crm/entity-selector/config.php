<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Integration\UI\EntitySelector\CountryProvider;
use Bitrix\Crm\Integration\UI\EntitySelector\TimelinePingProvider;
use Bitrix\Main\Loader;

if (!Loader::includeModule('crm'))
{
	return [];
}

return [
	'js' => 'dist/crm-entity-selector.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.entity-selector',
	],
	'skip_core' => true,
	'settings' => [
		'entities' => [
			[
				'id' => CountryProvider::ENTITY_ID,
				'options' => [
					'dynamicLoad' => true,
					'itemOptions' => [
						'default' => [
							'avatar' => CountryProvider::getIconByCode(CountryProvider::GLOBAL_COUNTRY_CODE),
						],
					],
					'tagOptions'  => [
						'default' => [
							'avatar' => CountryProvider::getIconByCode(CountryProvider::GLOBAL_COUNTRY_CODE),
						],
					],
				],
			],
			[
				'id' => TimelinePingProvider::ENTITY_ID,
				'options' => [
					'dynamicLoad' => true,
					'dynamicSearch' => false,
				]
			]
		],
	],
];
