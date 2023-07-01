<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Action;

use Bitrix\Crm\Service\Timeline\Layout\Base;

class Analytics extends Base
{
	private array $labels;
	private ?string $endpoint;

	public function __construct(array $labels = [], ?string $endpoint = null)
	{
		$this->labels = $labels;
		$this->endpoint = $endpoint;
	}

	public function setEndpoint(string $endpoint): self
	{
		$this->endpoint = $endpoint;

		return $this;
	}

	public function getEndpoint(): ?string
	{
		return $this->endpoint;
	}

	public function toArray(): array
	{
		$this->labels['hit'] = $this->getEndpoint();

		return $this->labels;
	}
}
