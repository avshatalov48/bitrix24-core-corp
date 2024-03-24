<?php

namespace Bitrix\BIConnector\Superset\UI\SettingsPanel\Controller;

abstract class EntityEditorController
{
	private array $config = [];

	public function __construct(private string $name)
	{}

	final public function setConfig(array $config): self
	{
		$this->config = $config;

		return $this;
	}

	private function getConfig(): array
	{
		return $this->config;
	}

	private function getName(): string
	{
		return $this->name;
	}

	final public function getData(): array
	{
		return [
			'name' => $this->getName(),
			'type' => $this->getType(),
			'config' => $this->getConfig(),
		];
	}

	abstract protected function getType(): string;
}
