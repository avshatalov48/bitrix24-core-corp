<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

return array(
	'parent' => 'store-chats-light',
	'code' => 'store-chats-light/mainpage',
	'name' => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-MAIN-NAME'),
	'description' => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-MAIN-DESC_NEW'),
	'type' => 'store',
	'version' => 3,
	'fields' => array(
		'RULE' => null,
		'ADDITIONAL_FIELDS' => array(
			'METAOG_TITLE' => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-MAIN-RICH_NAME'),
			'METAOG_DESCRIPTION' => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-MAIN-RICH_DESC'),
			'METAOG_IMAGE' => 'https://cdn.bitrix24.site/bitrix/images/demo/page/store-chats/mainpage/preview.jpg',
			'VIEW_USE' => 'N',
			'VIEW_TYPE' => 'no',
			'THEME_CODE' => '3corporate',

			'BACKGROUND_USE' => 'Y',
			'BACKGROUND_POSITION' => 'center',
			'BACKGROUND_PICTURE' => 'https://cdn.bitrix24.site/bitrix/images/landing/business/1600x1920/img6.jpg',
		),
	),
	'layout' => array(
		'code' => 'empty',
	),
	
	'disable_import' => 'Y',
	'site_group_item' => 'Y',
	'site_group_parent' => 'store-chats',
	
	'items' => array(
		'0' => array(
			'code' => '35.10.header_shop_top_and_phone_bottom',
			'access' => 'X',
			'nodes' => array(
				'.landing-block-node-text' => array(
					0 => Loc::getMessage("LANDING_DEMO_STORE_CHATS_LIGHT-MAIN-TEXT5"),
				),
			),
			'style' => array(
				'.landing-block-node-title' => array(
					0 => 'landing-block-node-title g-color-black g-font-size-45 g-font-montserrat g-font-weight-700',
				),
				'.landing-block-node-text' => array(
					0 => 'landing-block-node-text g-color-black-opacity-0_8 g-font-size-22 g-font-montserrat',
				),
				'#wrapper' => array(
					0 => 'landing-block g-pt-100 g-pb-100',
				),
			),
		),
		'1' => array(
			'code' => '55.1.list_of_links',
			'access' => 'X',
			'cards' => array(
				'.landing-block-node-list-item' => array(
					'source' => array(
						0 => array(
							'value' => 0,
							'type' => 'card',
						),
						1 => array(
							'value' => 0,
							'type' => 'card',
						),
						2 => array(
							'value' => 0,
							'type' => 'card',
						),
						3 => array(
							'value' => 0,
							'type' => 'card',
						),
						4 => array(
							'value' => 0,
							'type' => 'card',
						),
					),
				),
			),
			'nodes' => array(
				'.landing-block-node-link' => array(
					0 => array(
						'href' => '#landing@landing[store-chats-light/about]',
						'target' => '_self',
					),
					1 => array(
						'href' => '#landing@landing[store-chats-light/contacts]',
						'target' => '_self',
					),
					2 => array(
						'href' => '#landing@landing[store-chats-light/cutaway]',
						'target' => '_self',
					),
					3 => array(
						'href' => '#landing@landing[store-chats-light/payinfo]',
						'target' => '_self',
					),
					4 => array(
						'href' => '#landing@landing[store-chats-light/webform]',
						'target' => '_self',
					),
				),
				'.landing-block-node-link-text' => [
					0 => Loc::getMessage("LANDING_DEMO_STORE_CHATS_LIGHT-MAIN-TEXT1"),
					1 => Loc::getMessage("LANDING_DEMO_STORE_CHATS_LIGHT-MAIN-TEXT2"),
					2 => Loc::getMessage("LANDING_DEMO_STORE_CHATS_LIGHT-MAIN-TEXT6"),
					3 => Loc::getMessage("LANDING_DEMO_STORE_CHATS_LIGHT-MAIN-TEXT3"),
					4 => Loc::getMessage("LANDING_DEMO_STORE_CHATS_LIGHT-MAIN-TEXT4"),
				],
			),
			'style' => array(
				'.landing-block-node-list-container' => array(
					0 => 'landing-block-node-list-container row no-gutters justify-content-center',
				),
				'.landing-block-node-list-item' => array(
					0 => 'landing-block-node-list-item g-brd-bottom g-brd-1 g-py-12 js-animation animation-none landing-card g-brd-gray-light-v5 g-font-size-18 g-pt-18 g-pb-18',
					1 => 'landing-block-node-list-item g-brd-bottom g-brd-1 g-py-12 js-animation animation-none landing-card g-brd-gray-light-v5 g-font-size-18 g-pt-18 g-pb-18',
					2 => 'landing-block-node-list-item g-brd-bottom g-brd-1 g-py-12 js-animation animation-none landing-card g-brd-gray-light-v5 g-font-size-18 g-pt-18 g-pb-18',
					3 => 'landing-block-node-list-item g-brd-bottom g-brd-1 g-py-12 js-animation animation-none landing-card g-brd-gray-light-v5 g-font-size-18 g-pt-18 g-pb-18',
					4 => 'landing-block-node-list-item g-brd-bottom g-brd-1 g-py-12 js-animation animation-none landing-card g-brd-gray-light-v5 g-font-size-18 g-pt-18 g-pb-18',
				),
				'.landing-block-node-link' => array(
					0 => 'landing-block-node-link row no-gutters justify-content-between align-items-center g-text-decoration-none--hover g-color-primary--hover g-font-size-18 g-color-gray-dark-v1',
					1 => 'landing-block-node-link row no-gutters justify-content-between align-items-center g-text-decoration-none--hover g-color-primary--hover g-font-size-18 g-color-gray-dark-v1',
					2 => 'landing-block-node-link row no-gutters justify-content-between align-items-center g-text-decoration-none--hover g-color-primary--hover g-font-size-18 g-color-gray-dark-v1',
					3 => 'landing-block-node-link row no-gutters justify-content-between align-items-center g-text-decoration-none--hover g-color-primary--hover g-font-size-18 g-color-gray-dark-v1',
					4 => 'landing-block-node-link row no-gutters justify-content-between align-items-center g-text-decoration-none--hover g-color-primary--hover g-font-size-18 g-color-gray-dark-v1',
				),
				'#wrapper' => array(
					0 => 'landing-block g-pt-10 g-pb-10 g-pl-15 g-pr-15 u-block-border-none g-bg-white-opacity-0_8',
				),
			),
		),
	),
);