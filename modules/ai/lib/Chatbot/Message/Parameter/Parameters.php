<?php declare (strict_types=1);

namespace Bitrix\AI\Chatbot\Message\Parameter;

class Parameters
{
	/* @var Parameter[] $parameters */
	protected array $parameters = [];

	public function __construct()
	{
	}

	public function add(Parameter $parameter): self
	{
		$this->parameters[$parameter->getName()] = $parameter;

		return $this;
	}

	public function get(string $name): ?Parameter
	{
		return $this->parameters[$name] ?? null;
	}

	public function set(Parameter $parameter): self
	{
		$this->parameters[$parameter->getName()] = $parameter;

		return $this;
	}

	/**
	 * @return array|Parameter[]
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}

	public function getParametersArray(): array
	{
		$parameters = [];
		foreach ($this->parameters as $parameter)
		{
			$parameters[$parameter->getName()] = $parameter->getValue();
		}

		return $parameters;
	}
}