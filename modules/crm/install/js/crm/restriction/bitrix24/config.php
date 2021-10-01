<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
use \Bitrix\Crm;

$restrictions = [];
$visitRestriction = Crm\Restriction\RestrictionManager::getVisitRestriction();

if (!$visitRestriction->hasPermission())
{
	$restrictions['visit'] = [
		'infoHelperScript' => $visitRestriction->prepareInfoHelperScript()
	];
}

return [
	'css' => 'dist/index.bundle.css',
	'js' => 'dist/index.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
	'settings' => $restrictions,
];