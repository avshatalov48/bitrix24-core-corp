<?php

namespace Bitrix\HumanResources\Engine\ActionFilter;

class LogicRule
{
	/** @var list<StructureRuleItem> */
	public readonly array $rules;

	public function __construct(
		/** @var StructureAccessCheck::RULE_* $logicOperator */
		public string $logicOperator,
		StructureRuleItem ... $rules,
	)
	{
		$this->rules = $rules;
	}
}