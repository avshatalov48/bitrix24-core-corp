<?php

use Bitrix\DiskMobile\AirDiskFeature;
use Bitrix\Mobile\Config\Feature;

return [
	'isTasksMobileInstalled' => \Bitrix\Main\ModuleManager::isModuleInstalled('tasksmobile'),
	'isAirDiskFeatureEnable' => Feature::isEnabled(AirDiskFeature::class),
];
