<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;

if (!Loader::includeModule('biconnector'))
{
	return [];
}

return [
	'js' => 'dist/biconnector-entity-selector.bundle.js',
	'rel' => [
		'main.core',
		'ui.entity-selector',
	],
	'skip_core' => false,
	'settings' => [
		'entities' => [
			[
				'id' => 'biconnector-superset-dashboard-tag',
				'options' => [
					'dynamicLoad' => true,
					'dynamicSearch' => true,
					'itemOptions' => [
						'default' => [
							'avatar' => '/bitrix/js/biconnector/entity-selector/src/images/default-tag.svg',
							'badgesOptions' => [
								'fitContent' => true,
								'maxWidth' => 256,
							],
						],
					],
				],
			],
		],
	],
];