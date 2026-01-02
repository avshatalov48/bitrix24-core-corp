<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Operation\Document\UnserializePortableBlank;
use Bitrix\Sign\Result\Operation\Document\Template\InstallPresetTemplatesResult;
use Bitrix\Sign\Result\Operation\Document\UnserializePortableBlankResult;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\B2e\B2eTariffRestrictionService;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\Document\TemplateService;
use Bitrix\Sign\Service\Sign\PresetTemplatesService;

class InstallOnboardingTemplate implements Operation
{
	private readonly B2eTariffRestrictionService $b2eTariffRestrictionService;
	private readonly PresetTemplatesService $presetTemplatesService;
	private readonly TemplateService $templateService;
	private readonly Storage $storage;

	public function __construct(
		private readonly int $createdById,
		?B2eTariffRestrictionService $b2eTariffRestrictionService = null,
		?PresetTemplatesService $presetTemplatesService = null,
		?TemplateService $templateService = null,
		?Storage $storage = null,
	)
	{
		$container = Container::instance();
		$this->b2eTariffRestrictionService = $b2eTariffRestrictionService ?? $container->getB2eTariffRestrictionService();
		$this->presetTemplatesService = $presetTemplatesService ?? $container->getPresetTemplatesService();
		$this->templateService = $templateService ?? $container->getDocumentTemplateService();
		$this->storage = $storage ?? Storage::instance();
	}

	public function launch(): Main\Result|InstallPresetTemplatesResult
	{

		$this->presetTemplatesService->resetModuleOptionCache();

		$result = $this->install();

		if (!$result->isSuccess())
		{
			return $result;
		}

		return new InstallPresetTemplatesResult(isOptionsReloaded: false);
	}

	private function install(): Main\Result
	{
		$result = $this->b2eTariffRestrictionService->check();
		if (!$result->isSuccess())
		{
			return $result;
		}

		$filesystemEntry = $this->presetTemplatesService->getSerializedTemplatePathByName($this->storage->getOnboardingTemplateName());
		if (!$filesystemEntry || !$filesystemEntry->isExists() || !$filesystemEntry->isFile())
		{
			return Result::createByErrorMessage("Unexpected filesystem entry for onboarding template");
		}

		$content = (new Main\IO\File($filesystemEntry->getPhysicalPath()))->getContents();
		if (!$content)
		{
			return Result::createByErrorMessage("No contents in onboarding template");
		}

		$importResult = $this->unserializeAndImport($content);
		if (!$importResult->isSuccess())
		{
			return $importResult;
		}

		$templateId = $importResult->getData()['document']->templateId ?? 0;
		if ($templateId < 1)
		{
			return Result::createByErrorMessage('Onboarding template id is empty');
		}

		$onboardingTemplate = $this->templateService->getById($templateId);

		return (new CompleteOnboardingTemplateFilling($onboardingTemplate))->launch();
	}

	private function unserializeAndImport(string $serializedTemplate): Main\Result
	{
		$result = (new UnserializePortableBlank($serializedTemplate))->launch();
		if (!$result instanceof UnserializePortableBlankResult)
		{
			return $result;
		}

		return (new ImportTemplate($result->blank, $this->createdById))->launch();
	}
}