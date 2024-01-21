<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Restriction\AvailabilityManager;

$availabilityManager = AvailabilityManager::getInstance();
print $availabilityManager->getEntityTypeInaccessibilityContent(\CCrmOwnerType::Invoice);