<?php
namespace Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

use Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

/**
 * Class TaskLimit
 *
 * @package Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit
 */
class TaskLimit extends Limit
{
	protected static $variableName = FeatureDictionary::VARIABLE_TASKS_LIMIT;
}