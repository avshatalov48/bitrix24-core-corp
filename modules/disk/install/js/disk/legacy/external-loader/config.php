<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => '/bitrix/js/disk/external_loader.js',
	'rel' => ['core', 'disk.legacy.disk', 'disk.legacy.queue'],
];
