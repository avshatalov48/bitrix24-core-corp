<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	'js' => '/bitrix/js/disk/model/external-link/input/external-link-input.js',
	'css' => '/bitrix/js/disk/model/external-link/input/style.css',
	'rel' => [
		'disk.model.item',
		'disk.model.external-link.settings',
		'ui.design-tokens',
	],
);