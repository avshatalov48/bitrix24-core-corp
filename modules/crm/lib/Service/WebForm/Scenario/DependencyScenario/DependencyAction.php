<?php

namespace Bitrix\Crm\Service\WebForm\Scenario\DependencyScenario;

class DependencyAction
{
	/**
	 * @var string
	 */
	private $target;

	/**
	 * @var string
	 */
	private $type;

	private function __construct(string $target, string $type)
	{
		$this->target = $target;
		$this->type = $type;;
	}

	/**
	 * Short calling for get DependencyAction class instance.
	 * @param string $target
	 * @param string $type
	 * @return DependencyAction
	 */
	public static function of(string $target, string $type)
	{
		return new self($target, $type);
	}

	public function toArray(): array
	{
		return [
			'target' => $this->target,
			'type' => $this->type,
			'value' => null,
		];
	}
}