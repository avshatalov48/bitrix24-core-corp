<?php

namespace Bitrix\Crm\Service\WebForm\Scenario\DependencyScenario;

class DependencyListItem
{
	/**
	 * @var string
	 */
	private $action;

	/**
	 * @var string
	 */
	private $condition;

	private function __construct(DependencyAction $action, DependencyCondition $condition)
	{
		$this->action = $action;
		$this->condition = $condition;
	}

	/**
	 * Short calling for get DependencyAction class instance.
	 *
	 * @param DependencyAction $action
	 * @param DependencyCondition $condition
	 * @return DependencyListItem
	 */
	public static function of(DependencyAction $action, DependencyCondition $condition): DependencyListItem
	{
		return new self($action, $condition);
	}

	public function toArray(): array
	{
		return [
			'action' => $this->action->toArray(),
			'condition' => $this->condition->toArray(),
		];
	}
}