<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

return array(
	'code' => 'store-chats-light/about',
	'name' => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-ABOUT-NAME'),
	'description' => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-ABOUT-DESC'),
	'type' => 'store',
	'version' => 3,
	'fields' => array(
		'RULE' => null,
		'ADDITIONAL_FIELDS' => array(
			'METAOG_TITLE' => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-ABOUT-RICH_NAME'),
			'METAOG_DESCRIPTION' => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-ABOUT-RICH_DESC'),
			'METAOG_IMAGE' => 'https://cdn.bitrix24.site/bitrix/images/demo/page/store-chats/about/preview.jpg',
			'VIEW_USE' => 'N',
			'VIEW_TYPE' => 'no',
			'THEME_CODE' => '3corporate',
			'THEME_CODE_TYPO' => '3corporate',
		),
	),
	
	
	'items' => array(
		'0' => array(
			'code' => '34.4.two_cols_with_text_and_icons',
			'access' => 'X',
			'cards' => array(
				'.landing-block-node-card' => array(
					'source' => array(
						0 => array(
							'value' => 0,
							'type' => 'card',
						),
						1 => array(
							'value' => 0,
							'type' => 'card',
						),
					),
				),
			),
			'nodes' => array(
				'.landing-block-node-card-icon' => array(
					0 => array(
						'classList' => array(
							0 => 'landing-block-node-card-icon fa fa-smile-o',
						),
					),
					1 => array(
						'classList' => array(
							0 => 'landing-block-node-card-icon fa fa-thumbs-o-up',
						),
					),
				),
				'.landing-block-node-card-title' => array(
					0 => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-ABOUT-TEXT1'),
					1 => Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-ABOUT-TEXT2'),
				),
				'.landing-block-node-card-text' => array(
					0 => '<p>'.Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-ABOUT-TEXT3').'</p>',
					1 => '<p>'.Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-ABOUT-TEXT4').'</p>',
				),
			),
			'style' => array(
				'.landing-block-node-card' => array(
					0 => 'landing-block-node-card js-animation animation-none col-md-6 col-lg-6 g-mb-40 g-mb-0--last g-px-20',
					1 => 'landing-block-node-card js-animation animation-none col-md-6 col-lg-6 g-mb-40 g-mb-0--last g-px-20',
				),
				'.landing-block-node-card-text' => array(
					0 => 'landing-block-node-card-text mb-0 g-color-gray-dark-v4 g-font-size-14',
					1 => 'landing-block-node-card-text mb-0 g-color-gray-dark-v4 g-font-size-14',
				),
				'.landing-block-node-card-title' => array(
					0 => 'landing-block-node-card-title h5 font-weight-bold g-color-gray-dark-v1 g-text-transform-none g-font-size-16',
					1 => 'landing-block-node-card-title h5 font-weight-bold g-color-gray-dark-v1 g-text-transform-none g-font-size-16',
				),
				'#wrapper' => array(
					0 => 'landing-block g-pt-20 g-pb-10 u-block-border u-block-border-margin-md g-rounded-6 g-bg-white',
				),
			),
		),
		'1' => array(
			'code' => '31.3.two_cols_text_img_fix',
			'access' => 'X',
			'nodes' => array(
				'.landing-block-node-title' => array(
					0 => '<span bxstyle="font-weight: normal;">'.Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-ABOUT-TEXT5').'</span>',
				),
				'.landing-block-node-text' => array(
					0 => '<p>'.Loc::getMessage('LANDING_DEMO_STORE_CHATS_LIGHT-ABOUT-TEXT6').'</p>',
				),
				'.landing-block-node-img' => array(
					0 => array(
						'alt' => '',
						'src' => 'https://cdn.bitrix24.site/bitrix/images/landing/business/540x360/img1.jpg',
					),
				),
			),
			'style' => array(
				'.landing-block-node-title' => array(
					0 => 'landing-block-node-title font-weight-bold mb-0 g-mb-15 g-color-gray-dark-v1 g-text-transform-none g-font-size-28',
				),
				'.landing-block-node-text-container' => array(
					0 => 'landing-block-node-text-container js-animation animation-none col-md-6 col-lg-6 g-pb-20 g-pb-0--md',
				),
				'.landing-block-node-text' => array(
					0 => 'landing-block-node-text g-color-gray-dark-v4',
				),
				'.landing-block-node-block' => array(
					0 => 'row landing-block-node-block align-items-center',
				),
				'.landing-block-node-img' => array(
					0 => 'landing-block-node-img js-animation animation-none img-fluid',
				),
				'#wrapper' => array(
					0 => 'landing-block g-pt-10 g-pb-20 u-block-border u-block-border-margin-md g-rounded-6 g-bg-white',
				),
			),
		),
	),
);