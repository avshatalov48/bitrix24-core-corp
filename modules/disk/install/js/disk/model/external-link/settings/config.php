<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	'js' => '/bitrix/js/disk/model/external-link/settings/external-link-settings.js',
	'css' => '/bitrix/js/disk/model/external-link/settings/style.css',
	'rel' => [
		'ui.design-tokens',
		'disk.model.item',
		'date',
	],
);