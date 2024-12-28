<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Blank\Export\PortableBlank;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Document\Template;
use Bitrix\Sign\Operation\Document\UnserializePortableBlank;
use Bitrix\Sign\Repository\BlankRepository;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Result\Operation\Document\UnserializePortableBlankResult;
use Bitrix\Sign\Service\B2e\B2eTariffRestrictionService;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\PresetTemplatesService;
use Bitrix\Sign\Type\Template\Status;
use Bitrix\Sign\Result\Result;

class FixDismissalPresetTemplate implements Operation
{
	private const TEMPLATE_DISMISSAL_FIXED_OPTION = 'template_preset_fix_dismissal';
	private const TEMPLATE_DISMISSAL_NAME = 'dismissal.json';
	private const TEMPLATE_DISMISSAL_FIXED_TIME = 1733431079;
	private const OLD_TEMPLATE_SHA1 = '06026c722bab115fb325570dcdde4b3be8aa5d4a';

	private readonly TemplateRepository $templateRepository;
	private readonly DocumentRepository $documentRepository;
	private readonly MemberRepository $memberRepository;
	private readonly BlankRepository $blankRepository;
	private readonly B2eTariffRestrictionService $b2ETariffRestrictionService;
	private readonly PresetTemplatesService $presetTemplatesService;

	public function __construct(
		private readonly bool $isOptionsReloaded = false,
		?TemplateRepository $templateRepository = null,
		?DocumentRepository $documentRepository = null,
		?MemberRepository $memberRepository = null,
		?BlankRepository $blankRepository = null,
		?B2eTariffRestrictionService $b2ETariffRestrictionService = null,
		?PresetTemplatesService $presetTemplatesService = null,
	)
	{
		$container = Container::instance();
		$this->templateRepository = $templateRepository ?? $container->getDocumentTemplateRepository();
		$this->documentRepository = $documentRepository ?? $container->getDocumentRepository();
		$this->memberRepository = $memberRepository ?? $container->getMemberRepository();
		$this->blankRepository = $blankRepository ?? $container->getBlankRepository();
		$this->b2ETariffRestrictionService = $b2ETariffRestrictionService ?? $container->getB2eTariffRestrictionService();
		$this->presetTemplatesService = $presetTemplatesService ?? $container->getPresetTemplatesService();
	}

	public function launch(): Main\Result
	{
		if (!$this->getLock())
		{
			return Result::createByErrorMessage('Cant get install preset templates lock');
		}

		$result = $this->fixIfNeed();

		$this->releaseLock();

		return $result;
	}

	private function fixIfNeed(): Main\Result
	{
		if (!$this->isOptionsReloaded)
		{
			$this->presetTemplatesService->resetModuleOptionCache();
		}

		if ($this->isDismissalTemplateFixed())
		{
			return new Main\Result();
		}

		$result = $this->b2ETariffRestrictionService->check();
		if (!$result->isSuccess())
		{
			return $result;
		}

		foreach ($this->presetTemplatesService->getSerializedTemplatesPathsToInstall() as $filesystemEntry)
		{
			$templateName = $filesystemEntry->getName();
			if ($templateName !== self::TEMPLATE_DISMISSAL_NAME)
			{
				continue;
			}

			if (!$this->presetTemplatesService->isTemplateInstalled($templateName))
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

			$result = $this->removeOldAndInstallCorrect($content);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		$this->setDismissalTemplateFixed();

		return new Main\Result();
	}

	private function removeOldAndInstallCorrect(string $serializedTemplate): Main\Result
	{
		$result = (new UnserializePortableBlank($serializedTemplate))->launch();
		if (!$result instanceof UnserializePortableBlankResult)
		{
			return $result;
		}

		$portableBlank = $result->blank;
		$installedTemplate = $this->getInstalledTemplate($portableBlank);
		if ($installedTemplate === null)
		{
			return new Main\Result();
		}

		$document = $this->documentRepository->getByTemplateId($installedTemplate->id);
		if (!$document)
		{
			return new Main\Result();
		}

		if ($this->memberRepository->countByDocumentId($document->id))
		{
			return new Main\Result();
		}

		if (!$this->isOldFile($document))
		{
			return new Main\Result();
		}

		$result = (new Delete($installedTemplate))->launch();
		if (!$result->isSuccess())
		{
			return $result;
		}

		return (new ImportTemplate($portableBlank))->launch();
	}

	private function getInstalledTemplate(PortableBlank $portableBlank): ?Template
	{
		$dateBeforeFix =  Main\Type\DateTime::createFromTimestamp(self::TEMPLATE_DISMISSAL_FIXED_TIME);

		$filter = (new Main\ORM\Query\Filter\ConditionTree())
			->logic('and')
			->where('TITLE', $portableBlank->title)
			->where('DATE_CREATE', '<', $dateBeforeFix)
			->where('STATUS', Status::NEW->toInt())
		;

		return $this->templateRepository
			->getB2eEmployeeTemplateList($filter, 1)
			->getFirst();
	}

	private function isOldFile(Document $document): bool
	{
		$blank = $this->blankRepository->getById((int)$document->blankId);
		if (!$blank)
		{
			return false;
		}

		$file = $blank->fileCollection->first();
		if (!$file)
		{
			return false;
		}

		return self::OLD_TEMPLATE_SHA1 === sha1($file->content->data);
	}

	private function isDismissalTemplateFixed(): bool
	{
		return Main\Config\Option::get('sign', self::TEMPLATE_DISMISSAL_FIXED_OPTION, 'N') === 'Y';
	}

	private function setDismissalTemplateFixed(): void
	{
		Main\Config\Option::set('sign', self::TEMPLATE_DISMISSAL_FIXED_OPTION, 'Y');
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
		return "sign_template_preset_dismissal_fix";
	}

}