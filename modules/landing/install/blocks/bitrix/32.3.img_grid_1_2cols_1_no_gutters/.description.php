<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

return array(
	'block' =>
		array(
			'name' => Loc::getMessage('LANDING_BLOCK_32.1.IMG_GRID_1_NO_GUTTERS_NAME'),
			'section' => array('image'),
		),
	'cards' => array(),
	'nodes' =>
		array(
			'.landing-block-node-img-big' =>
				array(
					'name' => Loc::getMessage('LANDING_BLOCK_32.1.IMG_GRID_1_NO_GUTTERS_NODES_LANDINGBLOCKNODEIMG1'),
					'type' => 'img',
					'dimensions' => array('width' => 1440, 'height' => 960),
					'allowInlineEdit' => false,
				),
			'.landing-block-node-img-small' =>
				array(
					'name' => Loc::getMessage('LANDING_BLOCK_32.1.IMG_GRID_1_NO_GUTTERS_NODES_LANDINGBLOCKNODEIMG2'),
					'type' => 'img',
					'dimensions' => array('width' => 960, 'height' => 960),
					'allowInlineEdit' => false,
				),
		),
	'style' =>
		array(
			'.landing-block-node-img-container-left' => array(
				'name' => Loc::getMessage('LANDING_BLOCK_32.1.IMG_GRID_1_NO_GUTTERS_NODES_LANDINGBLOCKNODEIMG'),
				'type' => 'animation',
			),
			'.landing-block-node-img-container-right-top' => array(
				'name' => Loc::getMessage('LANDING_BLOCK_32.1.IMG_GRID_1_NO_GUTTERS_NODES_LANDINGBLOCKNODEIMG'),
				'type' => 'animation',
			),
			'.landing-block-node-img-container-right-bottom' => array(
				'name' => Loc::getMessage('LANDING_BLOCK_32.1.IMG_GRID_1_NO_GUTTERS_NODES_LANDINGBLOCKNODEIMG'),
				'type' => 'animation',
			),
		),
	'assets' => array(
		'ext' => array('landing_gallery_cards'),
	),
);