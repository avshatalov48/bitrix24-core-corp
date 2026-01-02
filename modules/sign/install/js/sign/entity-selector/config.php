<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!\Bitrix\Main\Loader::includeModule('sign'))
{
	return [];
}

return [
	'settings' => [
		'entities' => [
			[
				'id' => 'sign-document',
				'options' => [
					'dynamicLoad' => true,
					'dynamicSearch' => true,
					'itemOptions' => [
						'default' => [
							'avatar' => '/bitrix/js/sign/entity-selector/images/sign-document.svg',
						],
					],
				],
			],
			[
				'id' => 'sign-fired-user',
				'options' => [
					'dynamicLoad' => true,
					'dynamicSearch' => true,
				],
			],
			[
				'id' => 'signers-list',
				'options' => [
					'dynamicLoad' => true,
					'dynamicSearch' => true,
					'itemOptions' => [
						'default' => [
							'avatar' => '/bitrix/js/sign/entity-selector/images/signers-list.svg',
						],
					],
				],
			],
		]
	]
];