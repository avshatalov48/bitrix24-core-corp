<?php

namespace Bitrix\Crm\Service\Router\Dto;

use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Main\ArgumentOutOfRangeException;
use JsonSerializable;

final class SidePanelAnchorRule implements JsonSerializable
{
	private array $conditions;
	private ?array $stopParameters = null;
	private ?bool $allowCrossDomain = null;
	private ?bool $mobileFriendly = null;
	private array $scopes;
	private SidePanelAnchorOptions $options;

	public function __construct(
		array|string $conditions,
	)
	{
		$this->conditions = is_array($conditions) ? $conditions : [$conditions];
		$this->scopes = Scope::cases();
		$this->options = new SidePanelAnchorOptions();
	}

	/**
	 * @param callable(SidePanelAnchorOptions $options): void $configureCallback
	 * @return $this
	 */
	public function configureOptions(callable $configureCallback): self
	{
		if (is_callable($configureCallback))
		{
			$configureCallback($this->options);
		}

		return $this;
	}

	public function stopParameters(?array $parameters): self
	{
		$this->stopParameters = $parameters;

		return $this;
	}

	public function allowCrossDomain(?bool $allowCrossDomain): self
	{
		$this->allowCrossDomain = $allowCrossDomain;

		return $this;
	}

	public function mobileFriendly(?bool $mobileFriendly): self
	{
		$this->mobileFriendly = $mobileFriendly;

		return $this;
	}

	public function scopes(array|Scope $scopes): self
	{
		$this->scopes = is_array($scopes) ? $scopes : [ $scopes ];

		return $this;
	}

	public function getScopes(): array
	{
		return $this->scopes;
	}

	public function jsonSerialize(): array
	{
		return [
			'condition' => $this->conditions,
			'stopParameters' => $this->stopParameters,
			'allowCrossDomain' => $this->allowCrossDomain,
			'mobileFriendly' => $this->mobileFriendly,
			'options' => $this->options->jsonSerialize(),
		];
	}
}
