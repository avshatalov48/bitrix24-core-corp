<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

return [
	'block' => [
		'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NAME'),
		'section' => ['feedback'],
		'type' => ['page', 'store', 'smn'],
	],
	'cards' => [
		'.landing-block-node-card' => [
			'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_CARDS_LANDINGBLOCKNODECARD'),
			'label' => ['.landing-block-node-card-photo', '.landing-block-node-card-name'],
		],
	],
	'nodes' => [
		'.landing-block-node-bgimg' => [
			'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_LANDINGBLOCKNODEBGIMG'),
			'type' => 'img',
			'allowInlineEdit' => false,
			'dimensions' => ['width' => 1920, 'height' => 1080],
		],
		'.landing-block-node-title' => [
			'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_LANDINGBLOCKNODETITLE'),
			'type' => 'text',
		],
		'.landing-block-node-text' => [
			'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_LANDINGBLOCKNODETEXT'),
			'type' => 'text',
		],
		'.landing-block-node-card-photo' => [
			'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_LANDINGBLOCKNODECARDPHOTO'),
			'type' => 'img',
			'dimensions' => ['width' => 240, 'height' => 240],
		],
		'.landing-block-node-card-text' => [
			'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_LANDINGBLOCKNODECARDTEXT'),
			'type' => 'text',
		],
		'.landing-block-node-card-name' => [
			'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_LANDINGBLOCKNODECARDNAME'),
			'type' => 'text',
		],
	],
	'style' => [
		'block' => [
			'type' => ['block-default-wo-background'],
		],
		'nodes' => [
			'.landing-block-node-title' => [
				'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_LANDINGBLOCKNODETITLE'),
				'type' => ['typo', 'heading'],
			],
			'.landing-block-node-text' => [
				'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_LANDINGBLOCKNODETEXT'),
				'type' => 'typo',
			],
			'.landing-block-node-card-text' => [
				'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_LANDINGBLOCKNODECARDTEXT'),
				'type' => 'typo',
			],
			'.landing-block-node-card-name' => [
				'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_LANDINGBLOCKNODECARDNAME'),
				'type' => 'typo',
			],
			'.landing-block-node-bgimg' => [
				'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_LANDINGBLOCKNODEBGIMG'),
				'type' => ['background-overlay', 'background-attachment'],
			],
			'.landing-block-node-header' => [
				'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_LANDINGBLOCKNODEHEADER'),
				'type' => ['animation', 'heading'],
			],
			'.landing-block-node-card-container' => [
				'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_CARDS_LANDINGBLOCKNODECARD'),
				'type' => 'animation',
			],
			'.landing-block-node-card-photo' => [
				'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_LANDINGBLOCKNODECARDPHOTO'),
				'type' => 'border-radius',
			],
			'.landing-block-img-container' => [
				'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_LANDINGBLOCKNODECARDPHOTO'),
				'type' => 'align-self',
			],
			'.landing-block-node-card' => [
				'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_CARD'),
				'type' => 'align-self',
			],
			'.landing-block-text-container' => [
				'name' => Loc::getMessage('LANDING_BLOCK_43.3.COVER_WITH_FEEDBACK_NODES_LANDINGBLOCKNODECARDTEXT'),
				'type' => 'align-self',
			],
		],
	],
	'assets' => [
		'ext' => ['landing_carousel'],
	],
];