<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder\Conditions;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;

interface Condition
{
	public function toOrmCondition(bool $forJoin = false): ConditionTree;

	public function toArray(): array;
}