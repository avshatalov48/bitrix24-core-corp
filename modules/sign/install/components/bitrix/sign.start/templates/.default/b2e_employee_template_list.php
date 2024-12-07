<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
/** @var SignStartComponent $component */
$component->setMenuIndex('sign_b2e_employee_template_list');

/** @var CMain $APPLICATION */
/** @var array $arParams */

$APPLICATION->IncludeComponent(
	'bitrix:sign.b2e.employee.template.list',
	'',
	[],
	$this->getComponent(),
);
