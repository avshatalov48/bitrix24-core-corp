<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!\Bitrix\Main\Loader::includeModule('ai'))
{
	return [];
}

return [
	'settings' => [
		'entities' => [
			[
				'id' => 'prompt-category',
			],
		]
	]
];
