<?

use \Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__DIR__.'/options.php');

return [
	'settings' => [
		'entities' => [
			[
				'id' => 'department',
				'options' => [
					'dynamicLoad' => true,
					'dynamicSearch' => true,
					'itemOptions' => [
						'default' => [
							'avatar' => '/bitrix/js/intranet/entity-selector/src/images/department.svg',
							'supertitle' => Loc::getMessage('INTRANET_ENTITY_SELECTOR_DEPARTMENT_SUPER_TITLE')
						],
					],
					'tagOptions' => [
						'default' => [
							'textColor' => '#5f6670',
							'bgColor' => '#e2e3e5',
							'avatar' => '',
						]
					],
				]
			]
		]
	]
];