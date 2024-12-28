<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Operation\Document\UnserializePortableBlank;
use Bitrix\Sign\Result\Operation\Document\Template\InstallPresetTemplatesResult;
use Bitrix\Sign\Result\Operation\Document\UnserializePortableBlankResult;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\B2e\B2eTariffRestrictionService;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\PresetTemplatesService;

class InstallPresetTemplates implements Operation
{
	private const VERSION = 1;
	private const VERSION_OPTION_NAME = 'template_preset_version';

	private readonly B2eTariffRestrictionService $b2ETariffRestrictionService;
	private readonly PresetTemplatesService $presetTemplatesService;

	public function __construct(
		?B2eTariffRestrictionService $b2ETariffRestrictionService = null,
		?PresetTemplatesService $presetTemplatesService = null,
	)
	{
		$container = Container::instance();
		$this->b2ETariffRestrictionService = $b2ETariffRestrictionService ?? $container->getB2eTariffRestrictionService();
		$this->presetTemplatesService = $presetTemplatesService ?? $container->getPresetTemplatesService();
	}

	public function launch(): Main\Result|InstallPresetTemplatesResult
	{
		if (!$this->getLock())
		{
			return Result::createByErrorMessage('Cant get install preset templates lock');
		}

		$this->presetTemplatesService->resetModuleOptionCache();

		$result = $this->isNeedInstall() ? $this->install() : new Main\Result();

		$this->releaseLock();

		if (!$result->isSuccess())
		{
			return $result;
		}

		return new InstallPresetTemplatesResult(isOptionsReloaded: true);
	}

	private function install(): Main\Result
	{
		$result = $this->b2ETariffRestrictionService->check();
		if (!$result->isSuccess())
		{
			return $result;
		}

		foreach ($this->presetTemplatesService->getSerializedTemplatesPathsToInstall() as $filesystemEntry)
		{
			$templateName = $filesystemEntry->getName();
			if ($this->presetTemplatesService->isTemplateInstalled($templateName))
			{
				continue;
			}

			if (!$filesystemEntry->isExists() || !$filesystemEntry->isFile())
			{
				return Result::createByErrorMessage("Unexpected filesystem entry {$filesystemEntry->getPath()}");
			}

			$content = (new Main\IO\File($filesystemEntry->getPhysicalPath()))->getContents();
			if (!$content)
			{
				return Result::createByErrorMessage("No contents in {$filesystemEntry->getPath()}");
			}

			$importResult = $this->unserializeAndImport($content);
			if (!$importResult->isSuccess())
			{
				return $importResult;
			}

			$this->presetTemplatesService->setTemplateInstalled($templateName);
		}

		$this->saveInstalledVersion();

		return new Main\Result();
	}

	private function getVersion(): int
	{
		return self::VERSION;
	}

	private function getInstalledVersion(): int
	{
		return (int)Main\Config\Option::get('sign', self::VERSION_OPTION_NAME, 0);
	}

	private function isNeedInstall(): bool
	{
		return $this->getInstalledVersion() < $this->getVersion();
	}

	private function saveInstalledVersion(): void
	{
		Main\Config\Option::set('sign', self::VERSION_OPTION_NAME, $this->getVersion());
	}

	private function unserializeAndImport(string $serializedTemplate): Main\Result
	{
		$result = (new UnserializePortableBlank($serializedTemplate))->launch();
		if (!$result instanceof UnserializePortableBlankResult)
		{
			return $result;
		}

		return (new ImportTemplate($result->blank))->launch();
	}

	private function getLock(): bool
	{
		return Main\Application::getConnection()->lock($this->getLockName());
	}

	private function releaseLock(): bool
	{
		return Main\Application::getConnection()->unlock($this->getLockName());
	}

	private function getLockName(): string
	{
		return "sign_template_preset_install";
	}
}