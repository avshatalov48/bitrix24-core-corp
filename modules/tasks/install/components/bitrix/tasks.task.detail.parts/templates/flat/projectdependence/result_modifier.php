<?php

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\V2\Internal\DI\Container;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$userId = (int)CurrentUser::get()->getId();

$arResult['TEMPLATE_DATA']['RESTRICTED'] = !Container::getInstance()->getTariffService()->canCreateDependence($userId);
