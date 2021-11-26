<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!\Bitrix\Main\Loader::includeModule('imopenlines'))
{
	return [];
}

return [
	'settings' => [
		'entities' => [
			[
				'id' => 'imopenlines-crm-form',
				'options' => [
					'dynamicLoad' => true,
					'dynamicSearch' => true
				],
			],
		],
	],
];