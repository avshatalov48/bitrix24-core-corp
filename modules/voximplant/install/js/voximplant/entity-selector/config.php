<?

use \Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'settings' => [
		'entities' => [
			[
				'id' => 'voximplant_group',
				'options' => [
					'dynamicLoad' => true,
					'dynamicSearch' => true,
					'itemOptions' => [
						'default' => [
							'avatar' => '/bitrix/js/voximplant/entity-selector/src/images/telephonygroup.svg',
							'supertitle' => Loc::getMessage('VOXIMPLANT_ENTITY_SELECTOR_SUPERTITLE')
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