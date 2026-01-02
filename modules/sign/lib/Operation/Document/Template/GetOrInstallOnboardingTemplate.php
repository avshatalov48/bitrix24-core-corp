<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Document\Template;
use Bitrix\Sign\Repository\BlankRepository;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Result\Service\Sign\Document\CreateTemplateResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Template\Status;

class GetOrInstallOnboardingTemplate implements Operation
{
	private readonly TemplateRepository $templateRepository;
	private readonly DocumentRepository $documentRepository;
	private readonly BlankRepository $blankRepository;
	private readonly Storage $storage;

	public function __construct(
		?TemplateRepository $templateRepository = null,
		?DocumentRepository $documentRepository = null,
		?BlankRepository $blankRepository = null,
		?Storage $storage = null,
	)
	{
		$container = Container::instance();
		$this->templateRepository = $templateRepository ?? $container->getDocumentTemplateRepository();
		$this->documentRepository = $documentRepository ?? $container->getDocumentRepository();
		$this->blankRepository = $blankRepository ?? $container->getBlankRepository();
		$this->storage = $storage ?? Storage::instance();
	}

	public function launch(): \Bitrix\Main\Result|CreateTemplateResult
	{
		return $this->getOrInstallOnboardingTemplate();
	}

	/**
	 * @return \Bitrix\Main\Result|CreateTemplateResult
	 */
	private function getOrInstallOnboardingTemplate(): \Bitrix\Main\Result|CreateTemplateResult
	{
		$templateForOnboarding = $this->getOnboardingTemplate();
		if (!$templateForOnboarding)
		{
			$createdById = (int)CurrentUser::get()->getId();
			if($createdById < 1)
			{
				return Result::createByErrorMessage('User not found');
			}

			$result = (new InstallOnboardingTemplate($createdById))->launch();
			if (!$result->isSuccess())
			{
				return $result;
			}

			$templateForOnboarding = $this->getOnboardingTemplate();
			if (!$templateForOnboarding)
			{
				return Result::createByErrorMessage('Template not found');
			}
		}

		if ($templateForOnboarding->status !== Status::COMPLETED)
		{
			$result = (new CompleteOnboardingTemplateFilling($templateForOnboarding))->launch();
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return new CreateTemplateResult($templateForOnboarding);
	}

	private function getOnboardingTemplate(): ?Template
	{
		$templateForOnboarding = null;
		$onboardingTemplateTitle = $this->getOnboardingTemplateTitle();
		$templatesByOnboardingName = $this->templateRepository->getHiddenTemplatesByTitle($onboardingTemplateTitle);
		foreach ($templatesByOnboardingName as $template)
		{
			$document = $this->documentRepository->getByTemplateId($template->id);
			if (!$document)
			{
				return $templateForOnboarding;
			}

			$blank = $this->blankRepository->getById((int)$document->blankId);
			if (!$blank)
			{
				return $templateForOnboarding;
			}

			$file = $blank->fileCollection->first();
			if (!$file)
			{
				return $templateForOnboarding;
			}

			if ($this->storage->getOnboardingTemplateSha256() === hash('sha256', $file->content->data))
			{
				$templateForOnboarding = $template;
			}
		}

		return $templateForOnboarding;
	}

	private function getOnboardingTemplateTitle(): string
	{
		return Loc::getMessage('SIGN_B2E_ONBOARDING_TEMPLATE_TITLE', [], 'ru') ?? '';
	}
}