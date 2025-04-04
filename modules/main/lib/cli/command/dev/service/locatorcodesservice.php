<?php

declare(strict_types=1);

namespace Bitrix\Main\Cli\Command\Dev\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Cli\Command\Dev\Service\Locator\LocatorCodesDto;
use Bitrix\Main\Cli\Command\Dev\Templates\LocatorCodesTemplate;
use Bitrix\Main\Cli\Helper\Renderer;
use Bitrix\Main\Cli\Helper\Renderer\Template;
use Bitrix\Main\Config\Configuration;
use ReflectionFunction;
use Symfony\Component\Console\Exception\InvalidArgumentException;

final class LocatorCodesService
{
	public function generateContent(LocatorCodesDto $dto): string
	{
		return $this->getTemplate($dto)->getContent();
	}

	public function generateFile(LocatorCodesDto $dto): void
	{
		$filePath = $this->getFilePath($dto->module);
		$withTag = !file_exists($filePath);
		$template = $this->getTemplate($dto, $withTag);
		$renderer = new Renderer();

		$renderer->replaceFileContent(
			$filePath,
			$template,
			"#region autogenerated services for module {$dto->module}",
			"#endregion",
		);
	}

	private function getServicesByModule(string $module): array
	{
		$config = Configuration::getInstance($module);
		$serviceConfig = $config->get('services');

		$services = [];
		foreach ($serviceConfig as $serviceName => $serviceData)
		{
			$serviceClass = $this->getServiceClass($serviceData);
			if ($serviceClass !== null)
			{
				$services[$serviceName] = $serviceClass;
			}
		}

		return $services;
	}

	private function getServiceClass(array $serviceData): ?string
	{
		$className = $serviceData['className'] ?? null;
		if ($className !== null)
		{
			return '\\' . $className . '::class';
		}

		$constructor = $serviceData['constructor'] ?? null;
		if ($constructor !== null)
		{
			$reflector = new ReflectionFunction($constructor);
			if ($reflector->hasReturnType())
			{
				return '\\' .$reflector->getReturnType() . '::class';
			}
		}

		return null;
	}

	private function getFilePath(string $module): string
	{
		$bitrixPath = Application::getDocumentRoot() . Application::getPersonalRoot() . '/modules/' . $module;
		if (is_dir($bitrixPath))
		{
			return $bitrixPath . '/.phpstorm.meta.php';
		}

		$localPath = Application::getDocumentRoot() . '/local/modules/' . $module;
		if (is_dir($localPath))
		{
			return $localPath . '/.phpstorm.meta.php';
		}

		throw new InvalidArgumentException('No such module');
	}

	private function getTemplate(LocatorCodesDto $dto, bool $withTag = false): Template
	{
		$services = $this->getServicesByModule($dto->module);

		return new LocatorCodesTemplate($dto->code, $dto->module, $services, $withTag);
	}
}