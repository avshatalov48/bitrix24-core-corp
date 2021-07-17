<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

return [
	'code' => 'store_v3/header',
	'name' => Loc::getMessage('LANDING_DEMO_STORE_HEADER_NAME'),
	'description' => null,
	'type' => 'store',
	'version' => 3,
	'fields' => [
		'RULE' => null,
		'ADDITIONAL_FIELDS' => [],
	],
	'layout' => [
		'code' => 'empty',
		'ref' => [],
	],
	'items' => [
		0 => [
			'code' => 'store.store_v3_menu_1',
			'nodes' => [
				'.landing-block-node-menu-top-link' => [
					0 => [
						'text' => Loc::getMessage("LANDING_DEMO_STORE_TEXT_1"),
						'href' => '#landing@landing[store_v3/mainpage]',
						'target' => '_self',
					],
				],
				'.landing-block-node-menu-bottom-link' => [
					0 => [
						'href' => '#landing@landing[store_v3/contacts]',
						'target' => '_self',
					],
					1 => [
						'href' => '#landing@landing[store_v3/payinfo]',
						'target' => '_self',
					],
				],
				'.landing-block-node-menu-bottom-text' => [
					0 => Loc::getMessage("LANDING_DEMO_STORE_TEXT_2"),
					1 => Loc::getMessage("LANDING_DEMO_STORE_TEXT_3"),
				],
			],
		],
		1 => [
			'code' => '01.big_with_text_3_1',
			'nodes' => [
				'.landing-block-node-img' => [
					0 => [
						'src' => 'https://cdn.bitrix24.site/bitrix/images/landing/blocks/1.jpg',
					],
				],
				'.landing-block-node-title' => [
					0 => Loc::getMessage("LANDING_DEMO_STORE_TEXT_4"),
				],
				'.landing-block-node-text' => [
					0 => Loc::getMessage("LANDING_DEMO_STORE_TEXT_5"),
				],
			],
			'style' => [
				'#wrapper' => [
					0 => 'landing-block landing-block-node-img u-bg-overlay g-flex-centered g-bg-img-hero g-height-auto g-pb-90 g-bg-size-cover g-pt-30 g-mb-20 g-bg-none--after l-d-xs-none l-d-md-none',
				],
				'.landing-block-node-container' => [
					0 => 'landing-block-node-container container text-center u-bg-overlay__inner g-mx-0',
				],
				'.landing-block-node-title' => [
					0 => 'landing-block-node-title landing-semantic-title-image-medium g-line-height-1 g-mt-20 text-left g-color-black g-text-transform-none g-font-size-46 g-font-roboto g-font-weight-600 g-mb-10',
				],
				'.landing-block-node-text' => [
					0 => 'landing-block-node-text landing-semantic-text-image-medium text-left g-color-black g-font-size-22 g-font-roboto g-font-weight-400 g-mb-0',
				],
			],
		],
	],
];