<?php

namespace Bitrix\DocumentGenerator\Integration\Intranet\Binding;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Diag\ExceptionHandlerFormatter;
use Bitrix\Main\Loader;

class CodeBuilder
{
	private const CONFIG_PATH = 'documentgenerator.intranet.binding';
	private const MODULE_ID = 'documentgenerator';

	/** @var Array<string, \Closure|null> */
	private $menuCodeResolvers = [];

	public function getMenuCode(string $moduleId, string $provider, $value): string
	{
		return $this->getMenuCodeFromResolver($moduleId, $provider, $value) ?? $this->getDefaultMenuCode($provider);
	}

	private function getDefaultMenuCode(string $provider): string
	{
		$chunks = explode('\\', $provider);
		$className = array_pop($chunks);

		return mb_strtolower($className);
	}

	private function getMenuCodeFromResolver(string $moduleId, string $provider, $value): ?string
	{
		$resolver = $this->getMenuCodeResolver($moduleId);
		if (is_null($resolver))
		{
			return null;
		}

		$menuCode = null;
		try
		{
			$menuCode = $resolver($provider, $value);
		}
		catch (\Throwable $throwable)
		{
			AddMessage2Log(
				"Got an error while calling a menuCodeResolver closure from module '{$moduleId}': "
				. ExceptionHandlerFormatter::format($throwable),
				static::MODULE_ID
			);
		}

		return is_string($menuCode) ? $menuCode : null;
	}

	private function getMenuCodeResolver(string $moduleId): ?\Closure
	{
		if (!array_key_exists($moduleId, $this->menuCodeResolvers))
		{
			$resolver = $this->getClosureFromConfig($moduleId, 'menuCodeResolver');

			$this->menuCodeResolvers[$moduleId] = $resolver;
		}

		return $this->menuCodeResolvers[$moduleId];
	}

	private function getClosureFromConfig(string $moduleId, string $closureName): ?\Closure
	{
		if (!Loader::includeModule($moduleId))
		{
			return null;
		}

		$config = Configuration::getInstance($moduleId)->get(static::CONFIG_PATH);
		if (empty($config))
		{
			return null;
		}

		$closure = $config[$closureName] ?? null;
		if (!($closure instanceof \Closure))
		{
			return null;
		}

		return $closure;
	}
}
