<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\UserField\Types\StatusType;
use Bitrix\Main\Page\Asset;

CJSCore::init(['uf']);

/**
 * @var $component StatusUfComponent
 */

$component = $this->getComponent();

if($component->isMobileMode())
{
	Asset::getInstance()->addJs(
		'/bitrix/js/mobile/userfield/mobile_field.js'
	);
	Asset::getInstance()->addJs(
		'/bitrix/components/bitrix/main.field.enum/templates/main.view/mobile.js'
	);

	StatusType::getStatusList($arResult['userField']);
}