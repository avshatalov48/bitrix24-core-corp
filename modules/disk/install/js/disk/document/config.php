<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	'js' => [
		'/bitrix/js/disk/document/editprocess.js',
		'/bitrix/js/disk/document/createprocess.js',
		'/bitrix/js/disk/document/local.js',
	],
	'css' => '/bitrix/js/disk/document/style.css',
	'rel' => ['disk', 'disk_information_popups', 'ui.viewer', 'im_desktop_utils'],
);