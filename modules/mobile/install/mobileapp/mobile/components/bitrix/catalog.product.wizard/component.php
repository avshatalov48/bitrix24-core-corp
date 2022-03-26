<?php

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;

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
];
