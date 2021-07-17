<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

return [
	'parent' => 'store-chats-light',
	'code' => 'store-chats-light/payinfo',
	'name' => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-PAYINFO-NAME'),
	'description' => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-PAYINFO-DESC'),
	'active' => true,
	'preview' => '',
	'preview2x' => '',
	'preview3x' => '',
	'preview_url' => '',
	'show_in_list' => 'N',
	'type' => 'store',
	'version' => 3,
	'lock_delete' => true,
	'fields' => [
		'TITLE' => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-PAYINFO-NAME'),
		'RULE' => null,
		'ADDITIONAL_FIELDS' => [
			'METAOG_TITLE' => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-PAYINFO-RICH_NAME'),
			'METAOG_DESCRIPTION' => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-PAYINFO-RICH_DESC'),
			'METAOG_IMAGE' => 'https://cdn.bitrix24.site/bitrix/images/demo/page/store-chats/payinfo/preview.jpg',
			'VIEW_USE' => 'N',
			'VIEW_TYPE' => 'no',
			'THEME_CODE' => '3corporate',
		],
	],
	'layout' => [],
	'items' => [
		'0' => [
			'code' => '27.one_col_fix_title_and_text_2',
			'access' => 'X',
			'nodes' => [
				'.landing-block-node-title' => [
					0 => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-PAYINFO-TEXT1'),
				],
				'.landing-block-node-text' => [
					0 => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-PAYINFO-TEXT2'),
				],
			],
			'style' => [
				'#wrapper' => [
					0 => 'landing-block js-animation animation-none g-pt-0 g-pb-25 u-block-border-none animation-none',
				],
				'.landing-block-node-title' => [
					0 => 'landing-block-node-title h2 g-color-gray-dark-v1 text-left g-font-size-27',
				],
				'.landing-block-node-text' => [
					0 => 'landing-block-node-text g-pb-1 g-color-gray-dark-v4 text-left',
				],
			],
		],
		'1' => [
			'code' => 'store.salescenter.payment.pay',
			'nodes' => [
				'bitrix:salescenter.payment.pay' => [
					'TEMPLATE_MODE' => 'lightmode',
				]
			],
		],
		'2' => [
			'code' => '26.separator',
			'access' => 'W',
			'style' => [
				'#wrapper' => [
					0 => 'landing-block g-bg-transparent g-pt-15 g-pb-10',
				],
				'.landing-block-line' => [
					0 => 'landing-block-line g-brd-transparent my-0',
				],
			],
		],
	],
];