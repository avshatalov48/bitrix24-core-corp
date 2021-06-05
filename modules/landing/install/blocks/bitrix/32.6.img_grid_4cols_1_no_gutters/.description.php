<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

return array(
	'block' => array(
		'name' => Loc::getMessage('LANDING_BLOCK_32.6.IMG_GRID_4_COLS_NO_GUTTERS_NAME'),
		'section' => array('image'),
		'dynamic' => false,
	),
	'cards' => array(),
	'nodes' => array(
		'.landing-block-node-img' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_32.6.IMG_GRID_4_COLS_NO_GUTTERS_NODES_LANDINGBLOCKNODEIMG'),
			'type' => 'img',
			'useInDesigner' => false,
			'dimensions' => array('width' => 1080),
			'disableLink' => true,
			'allowInlineEdit' => false,
		),
	),
	'style' => array(
		'.landing-block-node-img-container-leftleft' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_32.6.IMG_GRID_4_COLS_NO_GUTTERS_NODES_LANDINGBLOCKNODEIMG'),
			'type' => 'animation',
		),
		'.landing-block-node-img-container-left' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_32.6.IMG_GRID_4_COLS_NO_GUTTERS_NODES_LANDINGBLOCKNODEIMG'),
			'type' => 'animation',
		),
		'.landing-block-node-img-container-right' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_32.6.IMG_GRID_4_COLS_NO_GUTTERS_NODES_LANDINGBLOCKNODEIMG'),
			'type' => 'animation',
		),
		'.landing-block-node-img-container-rightright' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_32.6.IMG_GRID_4_COLS_NO_GUTTERS_NODES_LANDINGBLOCKNODEIMG'),
			'type' => 'animation',
		),
	),
	'assets' => array(
		'ext' => array('landing_gallery_cards'),
	),
);