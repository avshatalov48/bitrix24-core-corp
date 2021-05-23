<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Component\BaseUfComponent;

/**
 * Class ElementCrmUfComponent
 */
class ElementCrmUfComponent extends BaseUfComponent
{
	protected static function getUserTypeId(): string
	{
		return ElementType::USER_TYPE_ID;
	}
}