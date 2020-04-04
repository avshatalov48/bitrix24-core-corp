<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

return array(
	'block' => array(
		'name' => Loc::getMessage('LANDING_BLOCK_41.6.TEXT_BLOCKS_SLIDER_ON_COLOR_BG_NAME_NEW'),
		'section' => array('text_image'),
	),
	'cards' => array(
		'.landing-block-node-card' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_41.6.TEXT_BLOCKS_SLIDER_ON_COLOR_BG_CARDS_LANDINGBLOCKNODECARD'),
			'label' => array('.landing-block-node-card-photo', '.landing-block-node-card-title'),
		),
	),
	'nodes' => array(
		'.landing-block-node-card-subtitle' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_41.6.TEXT_BLOCKS_SLIDER_ON_COLOR_BG_NODES_LANDINGBLOCKNODECARDSUBTITLE'),
			'type' => 'text',
		),
		'.landing-block-node-card-title' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_41.6.TEXT_BLOCKS_SLIDER_ON_COLOR_BG_NODES_LANDINGBLOCKNODECARDTITLE'),
			'type' => 'text',
		),
		'.landing-block-node-card-text' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_41.6.TEXT_BLOCKS_SLIDER_ON_COLOR_BG_NODES_LANDINGBLOCKNODECARDTEXT'),
			'type' => 'text',
		),
		'.landing-block-node-card-price' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_41.6.TEXT_BLOCKS_SLIDER_ON_COLOR_BG_NODES_LANDINGBLOCKNODECARDPRICE'),
			'type' => 'text',
		),
		'.landing-block-node-card-photo' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_41.6.TEXT_BLOCKS_SLIDER_ON_COLOR_BG_NODES_LANDINGBLOCKNODECARDPHOTO'),
			'type' => 'img',
			'dimensions' => array('width' => 540, 'height' => 810),
		),
	),
	'style' => array(
		'.landing-block-node-card-subtitle' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_41.6.TEXT_BLOCKS_SLIDER_ON_COLOR_BG_STYLE_LANDINGBLOCKNODECARDSUBTITLE'),
			'type' => 'typo',
		),
		'.landing-block-node-card-title' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_41.6.TEXT_BLOCKS_SLIDER_ON_COLOR_BG_STYLE_LANDINGBLOCKNODECARDTITLE'),
			'type' => array('typo', 'background-color-before', 'animation'),
		),
		'.landing-block-node-card-text' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_41.6.TEXT_BLOCKS_SLIDER_ON_COLOR_BG_STYLE_LANDINGBLOCKNODECARDTEXT'),
			'type' => array('typo', 'animation'),
		),
		'.landing-block-node-card-price' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_41.6.TEXT_BLOCKS_SLIDER_ON_COLOR_BG_STYLE_LANDINGBLOCKNODECARDPRICE'),
			'type' => 'typo',
		),
		'.landing-block-node-card-container' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_41.6.TEXT_BLOCKS_SLIDER_ON_COLOR_BG_NODES_LANDINGBLOCKNODECARDPHOTO'),
			'type' => array('align-items'),
		),
		'.landing-block-node-card' => array(
			'name' => Loc::getMessage('LANDING_BLOCK_41.6.TEXT_BLOCKS_SLIDER_ON_COLOR_BG_CARDS_LANDINGBLOCKNODECARD'),
			'type' => 'align-self',
		),
	),
	'assets' => array(
		'ext' => array('landing_carousel'),
	),
);