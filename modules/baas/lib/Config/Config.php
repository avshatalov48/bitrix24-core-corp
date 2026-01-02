<?php

namespace Bitrix\Baas\Config;

use Bitrix\Main;

abstract class Config
{
	protected array $repo = [];

	abstract protected function getModuleId(): string;

	protected function set(string $name, string $value): static
	{
		$this->repo[$name] = $value;
		Main\Config\Option::set($this->getModuleId(), $name, $value);

		return $this;
	}

	protected function get(string $name, mixed $default = null): ?string
	{
		if (!isset($this->repo[$name]))
		{
			$this->repo[$name] = Main\Config\Option::get($this->getModuleId(), $name, $default);
		}

		return $this->repo[$name];
	}

	protected function delete(string $name): static
	{
		Main\Config\Option::delete($this->getModuleId(), ['name' => $name]);
		unset($this->repo[$name]);

		return $this;
	}
}
