<?

use \Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__DIR__.'/options.php');

return [
	'js' => [
	],
	'rel' => [
		'ui.entity-selector',
	],
	'settings' => [
		'entities' => [
			[
				'id' => 'structure-node',
				'options' => [
					'dynamicLoad' => true,
					'dynamicSearch' => true,
				]
			]
		]
	]
];