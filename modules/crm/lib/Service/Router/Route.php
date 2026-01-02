<?php

namespace Bitrix\Crm\Service\Router;

use Bitrix\Main\Routing\RoutingConfiguration;
use Closure;

final class Route
{
	protected ?string $relatedComponent = null;
	protected ?Closure $configurationCallback = null;

	public function __construct(
		protected string $baseUrl,
	)
	{
		if (str_starts_with($this->baseUrl, '/'))
		{
			$this->baseUrl = mb_substr($this->baseUrl, 1);
		}
	}

	public function baseUrl(): string
	{
		$url = '';
		$len = mb_strlen($this->baseUrl);
		$isStart = true;

		for ($i = 0; $i < $len; $i++)
		{
			$symbol = $this->baseUrl[$i];
			if ($symbol !== '#')
			{
				$url .= $symbol;

				continue;
			}

			$url .= $isStart ? '{' : '}';
			$isStart = !$isStart;
		}

		return $url;
	}

	public function oldBaseUrl(): string
	{
		return str_replace(['{', '}'], '#', $this->baseUrl);
	}

	/**
	 * @param callable(RoutingConfiguration $configuration): void $configurationCallback
	 * @return $this
	 */
	public function configure(callable $configurationCallback): self
	{
		$this->configurationCallback = $configurationCallback;

		return $this;
	}

	public function applyConfiguration(RoutingConfiguration $configuration): self
	{
		if (is_callable($this->configurationCallback))
		{
			call_user_func($this->configurationCallback, $configuration);
		}

		return $this;
	}

	public function setRelatedComponent(string $componentName): self
	{
		$this->relatedComponent = $componentName;

		return $this;
	}

	public function getRelatedComponent(): ?string
	{
		return $this->relatedComponent;
	}

	public function hasRelatedComponent(): bool
	{
		return $this->relatedComponent !== null;
	}
}
