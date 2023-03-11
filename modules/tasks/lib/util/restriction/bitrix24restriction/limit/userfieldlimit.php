<?php

namespace Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

use Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

class UserFieldLimit extends Limit
{
	protected static $variableName = FeatureDictionary::VARIABLE_USER_FIELD_LIMIT;
}