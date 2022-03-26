<?php

namespace Bitrix\Crm\Service\WebForm\Scenario\DependencyScenario;

class DependencyCondition
{
	/**
	 * @var string
	 */
	private $event;

	/**
	 * @var string
	 */
	private $operation;

	/**
	 * @var string;
	 */
	private $target;

	private function __construct(string $event, string $operation, string $target)
	{
		$this->event = $event;
		$this->operation = $operation;
		$this->target = $target;
	}

	/**
	 * Short calling for get DependencyAction class instance.
	 *
	 * @param string $event
	 * @param string $operation
	 * @param string $target
	 * @return DependencyCondition
	 */
	public static function of(string $event, string $operation, string $target)
	{
		return new self($event, $operation, $target);
	}

	public function toArray(): array
	{
		return [
			'event' => $this->event,
			'operation' => $this->operation,
			'target' => $this->target,
		];
	}
}