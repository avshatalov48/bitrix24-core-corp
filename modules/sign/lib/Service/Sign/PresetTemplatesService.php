<?php

namespace Bitrix\Sign\Service\Sign;

use Bitrix\Main;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\Path;

class PresetTemplatesService
{
	private const SERIALIZED_FILES_PATH = '/bitrix/modules/sign/assets/presets/template/';
	private const TEMPLATE_INSTALLED_OPTION_PREFIX = 'template_preset_installed_';

	/**
	 * @return array<Main\IO\FileSystemEntry> Array of path
	 */
	public function getSerializedTemplatesPathsToInstall(): array
	{
		$region = Main\Application::getInstance()->getLicense()->getRegion();
		if (empty($region))
		{
			return [];
		}

		$directory =  $this->getRegionSerializedTemplatesDirectory($region);
		if (!$directory->isExists())
		{
			return [];
		}

		return $directory->getChildren();
	}

	private function getRegionSerializedTemplatesDirectory(string $region): Directory
	{
		$documentRoot = Main\Application::getDocumentRoot();

		return new Directory($documentRoot . self::SERIALIZED_FILES_PATH . Path::DIRECTORY_SEPARATOR . $region);
	}

	public function isTemplateInstalled(string $templateName): bool
	{
		$optionName = $this->getTemplateOptionName($templateName);

		return Main\Config\Option::get('sign', $optionName, 'N') === 'Y';
	}

	public function setTemplateInstalled(string $templateName): void
	{
		$optionName = $this->getTemplateOptionName($templateName);

		Main\Config\Option::set('sign', $optionName, 'Y');
	}

	private function getTemplateOptionName(string $templateName): string
	{
		return self::TEMPLATE_INSTALLED_OPTION_PREFIX . $templateName;
	}

	public function resetModuleOptionCache(): void
	{
		Main\Config\Option::set('sign', '~fake_reset_cache_option', uniqid('sign', true));
	}
}