<?php

namespace Bitrix\Sign\Engine\ActionFilter;

use Bitrix\Sign\Access\AccessController;

class LogicRule
{
	/** @var list<RuleWithPayload> */
	public readonly array $rules;

	public function __construct(
		/** @var AccessController::RULE_* $logicOperator */
		public string $logicOperator,
		RuleWithPayload ... $rules,
	)
	{
		$this->rules = $rules;
	}
}