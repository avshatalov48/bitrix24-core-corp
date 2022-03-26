<?php

namespace Bitrix\Crm\Service\Converter;

final class CaseCache
{
	private static $instance;

	private $cache = [];

	private function __construct()
	{
	}

	private function __clone()
	{
	}

	public static function getInstance(): self
	{
		if (!static::$instance)
		{
			static::$instance = new self();
		}

		return static::$instance;
	}

	public function clear(): self
	{
		$this->cache = [];

		return $this;
	}

	public function add(string $camelCase, string $upperCase): self
	{
		$this->cache[$camelCase] = $upperCase;

		return $this;
	}

	public function getUpperCase(string $camelCase): ?string
	{
		return $this->cache[$camelCase] ?? null;
	}

	public function getCamelCase(string $upperCase): ?string
	{
		$camelCase = array_search($upperCase, $this->cache, true);

		if (!is_string($camelCase))
		{
			return null;
		}

		return $camelCase;
	}
}
