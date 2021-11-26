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
$dealCategoryRestriction = Crm\Restriction\RestrictionManager::getDealCategoryLimitRestriction();
if ($dealCategoryRestriction->getQuantityLimit() > 0)
{
	//Todo make another type for quantity limitations like in php
	$restrictions['dealCategory'] = [
		'quantityLimit' => $dealCategoryRestriction->getQuantityLimit(),
		'infoHelperScript' => $dealCategoryRestriction->prepareInfoHelperScript()
	];
}
$generatorRestriction = Crm\Restriction\RestrictionManager::getGeneratorRestriction();
if (!$generatorRestriction->hasPermission())
{
	$restrictions['generator'] = [
		'infoHelperScript' => $generatorRestriction->prepareInfoHelperScript()
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