<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Component\BaseUfComponent;
use Bitrix\Intranet\UserField\Types\EmployeeType;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class EmployeeUfComponent
 */
class EmployeeUfComponent extends BaseUfComponent
{
	public const SELECTOR_CONTEXT = 'USERFIELD_TYPE_EMPLOYEE';

	protected static function getUserTypeId(): string
	{
		return EmployeeType::USER_TYPE_ID;
	}
}