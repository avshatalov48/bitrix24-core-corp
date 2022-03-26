<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	'js' => [
		'/bitrix/js/disk/viewer/document-item/item.js',
	],
	'rel' => ['disk', 'ui.viewer', 'disk.viewer.onlyoffice-item'],
);