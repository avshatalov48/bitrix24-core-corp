<?php

namespace Bitrix\HumanResources\Builder\Structure\Filter\SelectionCondition;

use Bitrix\HumanResources\Builder\Structure\Filter\BaseFilter;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

abstract class BaseSelectionConditionFilter extends BaseFilter
{
	abstract public function prepareFilter(): ConditionTree;
}