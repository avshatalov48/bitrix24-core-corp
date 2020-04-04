<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

return array(
	'parent' => 'store-chats-light',
	'code' => 'store-chats-light/footer',
	'name' => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-FOOTER-NAME'),
	'description' => '',
	'active' => true,
	'preview' => '',
	'preview2x' => '',
	'preview3x' => '',
	'preview_url' => '',
	'show_in_list' => 'N',
	'type' => 'store',
	'version' => 3,
	'fields' => array(
		'TITLE' => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-FOOTER-NAME'),
		'RULE' => null,
		'ADDITIONAL_FIELDS' => array(
			'VIEW_USE' => 'N',
			'VIEW_TYPE' => 'no',
			'THEME_CODE' => '3corporate',
			'THEME_CODE_TYPO' => '3corporate',
		),
	),
	'layout' => array(),
	'items' => array(
		'0' => array(
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
						'href' => '#landing@landing[store-chats-light/payinfo]',
						'target' => '_self',
					),
					3 => array(
						'href' => '#landing@landing[store-chats-light/webform]',
						'target' => '_self',
					),
				),
				'.landing-block-node-link-text' => array(
					0 => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-FOOTER-TEXT1'),
					1 => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-FOOTER-TEXT2'),
					2 => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-FOOTER-TEXT3'),
					3 => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-FOOTER-TEXT4'),
				),
			),
			'style' => array(
				'.landing-block-node-list-container' => array(
					0 => 'landing-block-node-list-container row no-gutters justify-content-center',
				),
				'.landing-block-node-list-item' => array(
					0 => 'landing-block-node-list-item g-brd-bottom g-brd-1 g-py-12 js-animation animation-none landing-card g-brd-gray-light-v5 g-font-size-18',
					1 => 'landing-block-node-list-item g-brd-bottom g-brd-1 g-py-12 js-animation animation-none landing-card g-brd-gray-light-v5 g-font-size-18',
					2 => 'landing-block-node-list-item g-brd-bottom g-brd-1 g-py-12 js-animation animation-none landing-card g-brd-gray-light-v5 g-font-size-18',
					3 => 'landing-block-node-list-item g-brd-bottom g-brd-1 g-py-12 js-animation animation-none landing-card g-brd-gray-light-v5 g-font-size-18',
				),
				'.landing-block-node-link' => array(
					0 => 'landing-block-node-link row no-gutters justify-content-between align-items-center g-text-decoration-none--hover g-color-primary--hover g-font-size-18 g-color-gray-dark-v1',
					1 => 'landing-block-node-link row no-gutters justify-content-between align-items-center g-text-decoration-none--hover g-color-primary--hover g-font-size-18 g-color-gray-dark-v1',
					2 => 'landing-block-node-link row no-gutters justify-content-between align-items-center g-text-decoration-none--hover g-color-primary--hover g-font-size-18 g-color-gray-dark-v1',
					3 => 'landing-block-node-link row no-gutters justify-content-between align-items-center g-text-decoration-none--hover g-color-primary--hover g-font-size-18 g-color-gray-dark-v1',
				),
				'#wrapper' => array(
					0 => 'landing-block g-pt-10 g-pb-10 g-pl-15 g-pr-15 u-block-border u-block-border-margin-md g-rounded-6 g-bg-white',
				),
			),
		),
		'1' => array(
			'code' => '17.copyright',
			'nodes' => array(
				'.landing-block-node-text' => array(
					0 => '2019 &copy; All rights reserved',
				),
			),
		),
	),
);