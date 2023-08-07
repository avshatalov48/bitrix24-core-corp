<?php

use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\PermissionsProvider;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$iblockId = 0;
$morePhotoPropertyId = 0;

if (
	Loader::includeModule('crm')
	&& Loader::includeModule('iblock')
)
{
	\CCrmCatalog::EnsureDefaultExists();
	$iblockId = \Bitrix\Crm\Product\Catalog::getDefaultId();
}
return [
	'iblock' => [
		'ID' => $iblockId,
	],
	'permissions' => PermissionsProvider::getInstance()->getPermissions(),
];
