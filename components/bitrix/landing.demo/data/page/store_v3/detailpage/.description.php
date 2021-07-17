<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

return [
	'code' => 'store_v3/detailpage',
	'name' => Loc::getMessage('LANDING_DEMO_STORE_DETAILPAGE_NAME'),
	'description' => NULL,
	'type' => 'store',
	'version' => 3,
	'fields' => [
		'RULE' => '(item)/([^/]+)',
		'ADDITIONAL_FIELDS' => [
		],
	],
	'layout' => [
		'code' => 'without_right',
		'ref' => [
			1 => 'store_v3/header2',
			2 => 'store_v3/sidebar',
			3 => 'store_v3/footer',
		],
	],
	'items' => [
		0 => [
			'code' => '52.5.link_back',
			'nodes' => [
				'.landing-block-node-title' => [
					0 => Loc::getMessage('LANDING_DEMO_STORE_DETAILPAGE_TEXT_1'),
				],
			],
			'style' => [
				'#wrapper' => [
					0 => 'landing-block g-bg-white l-d-xs-none l-d-md-none',
				],
			],
		],
		1 => [
			'code' => '27.3.one_col_fix_title',
			'nodes' => [
				'.landing-block-node-title' => [
					0 => '#title#',
				],
			],
			'style' => [
				'#wrapper' => [
					0 => 'landing-block text-center container g-pb-25 g-pt-0 l-d-xs-none l-d-md-none',
				],
				'.landing-block-node-title' => [
					0 => 'landing-block-node-title landing-semantic-title-medium g-my-0 container g-pl-0 g-pr-0 text-left g-font-size-30 g-font-weight-500',
				],
			],
		],
		2 => [
			'code' => 'store.catalog.detail_store_v3',
			'cards' => [],
			'nodes' => [],
			'style' => [],
			'attrs' => [
				'bitrix:catalog.element' => [],
			],
		],
		3 => [
			'code' => '27.3.one_col_fix_title',
			'nodes' => [
				'.landing-block-node-title' => [
					0 => Loc::getMessage('LANDING_DEMO_STORE_DETAILPAGE_TEXT_2'),
				],
			],
			'style' => [
				'#wrapper' => [
					0 => 'landing-block container g-pb-0 g-pt-0',
				],
				'.landing-block-node-title' => [
					0 => 'landing-block-node-title g-font-size-32 g-font-roboto g-mb-55 g-mt-25 g-pb-25 g-font-weight-500 g-brd-1 g-brd-black-opacity-0_1 g-brd-solid g-brd-bottom',
				],
			],
		],
		4 => [
			'code' => 'store.catalog.sections_carousel',
			'style' => [],
			'attrs' => [],
		],
	],
];