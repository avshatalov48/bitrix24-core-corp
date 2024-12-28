<?php

use Bitrix\DiskMobile\LegacyUploaderFeature;
use Bitrix\Mobile\Config\Feature;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'isAirUploaderEnabled' => Feature::isDisabled(LegacyUploaderFeature::class),
];
