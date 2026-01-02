<?php

use Bitrix\CrmMobile\CallTracker;
use Bitrix\Main\Loader;
use Bitrix\Mobile\Config\Feature;
use Bitrix\Mobile\Feature\MenuFeature;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arResult = [];
$arResult['showCalltrackerSettings'] = false;
$arResult['isNewMenuEnabled'] = Feature::isEnabled(MenuFeature::class);

return $arResult;
