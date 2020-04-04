<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

return [
	'block' => [
		// 'name' => Loc::getMessage('LANDING_BLOCK_31_5-NAME'),
		// 'section' => array('text_image'),
	],
	'cards' => [
		'.landing-block-card' => [
			'name' => Loc::getMessage('LANDING_BLOCK_31_5-CARD'),
			'label' => ['.landing-block-node-img', '.landing-block-node-title'],
		],
	],
	'nodes' => [
		'.landing-block-node-subtitle' => [
			'name' => Loc::getMessage('LANDING_BLOCK_31_5-SUBTITLE'),
			'type' => 'text',
		],
		'.landing-block-node-title' => [
			'name' => Loc::getMessage('LANDING_BLOCK_31_5-TITLE'),
			'type' => 'text',
		],
		'.landing-block-node-text' => [
			'name' => Loc::getMessage('LANDING_BLOCK_31_5-TEXT'),
			'type' => 'text',
		],
		'.landing-block-node-link' => [
			'name' => Loc::getMessage('LANDING_BLOCK_31_5-LINK'),
			'type' => 'link',
		],
		'.landing-block-node-img' => [
			'name' => Loc::getMessage('LANDING_BLOCK_31_5-IMAGE'),
			'type' => 'img',
			'dimensions' => ['width' => 578],
		],
	],
	'style' => [
		'.landing-block-card' => [
			'name' => Loc::getMessage('LANDING_BLOCK_31_5-CARD'),
			'type' => ['align-items', 'animation'],
		],
		'.landing-block-node-subtitle' => [
			'name' => Loc::getMessage('LANDING_BLOCK_31_5-SUBTITLE'),
			'type' => 'typo',
		],
		'.landing-block-node-title' => [
			'name' => Loc::getMessage('LANDING_BLOCK_31_5-TITLE'),
			'type' => 'typo',
		],
		'.landing-block-node-text' => [
			'name' => Loc::getMessage('LANDING_BLOCK_31_5-TEXT'),
			'type' => 'typo',
		],
		'.landing-block-node-link' => [
			'name' => Loc::getMessage('LANDING_BLOCK_31_5-LINK'),
			'type' => 'typo-link',
		],
	],
];