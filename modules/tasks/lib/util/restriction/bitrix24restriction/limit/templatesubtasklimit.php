<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;


use Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

class TemplateSubtaskLimit extends Limit
{
	protected static $variableName = FeatureDictionary::VARIABLE_TEMPLATE_SUBTASKS;
}