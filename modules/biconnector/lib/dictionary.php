<?php
namespace Bitrix\BIConnector;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Dictionary
 *
 * @package Bitrix\BIConnector
 **/

class Dictionary
{
	const CACHE_TTL = 21600; //6 hours

	const USER_DEPARTMENT = 1;
	const USER_DEPARTMENT_HEAD = 2;
	const USER_STRUCTURE_DEPARTMENT = 3;
	const DEPARTMENT_PARENT_AGGREGATION = 4;
}
