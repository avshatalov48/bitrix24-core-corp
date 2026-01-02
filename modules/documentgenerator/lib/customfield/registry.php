<?php

namespace Bitrix\DocumentGenerator\CustomField;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Loader;

final class Registry
{
	private array $managers = [];

	public function __construct(
		private readonly ?int $templateId = null
	) {}

	public function getManager(string $moduleId): ?Manager
	{
		if (isset($this->managers[$moduleId]))
		{
			return $this->managers[$moduleId];
		}

		if (!Loader::includeModule($moduleId))
		{
			return null;
		}

		$managerClass = $this->getManagerClass($moduleId);
		if (!$managerClass)
		{
			return null;
		}

		$provider = new $managerClass($this->templateId);
		$this->managers[$moduleId] = $provider;

		return $provider;
	}

	private function getManagerClass(string $moduleId): ?string
	{
		$config = Configuration::getInstance($moduleId)->get('documentgenerator.customFields');
		if (empty($config))
		{
			return null;
		}

		$managerClass = $config['manager'] ?? null;
		if (empty($managerClass) || !is_a($managerClass, Manager::class, true))
		{
			return null;
		}

		return $managerClass;
	}
}
