<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Helper\CloneHelper;
use Bitrix\Sign\Item\Document\Template;
use Bitrix\Sign\Type\Template\Visibility;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Result\Operation\Document\Template\CreateTemplateResult;

final class Copy implements Contract\Operation
{
	private readonly DocumentRepository $documentRepository;
	private readonly TemplateRepository $templateRepository;

	public function __construct(
		private readonly Template $template,
		private readonly int $createdByUserId,
		?DocumentRepository $documentRepository = null,
		?TemplateRepository $templateRepository = null,
	)
	{
		$container = Container::instance();
		$this->documentRepository = $documentRepository ?? $container->getDocumentRepository();
		$this->templateRepository = $templateRepository ?? $container->getDocumentTemplateRepository();
	}

	public function launch(): Main\Result
	{
		if ($this->template->id === null)
		{
			return Result::createByErrorMessage('Template is not saved');
		}

		$copyTitle = $this->createCopyTitle($this->template->title);
		$copyTemplate = new Template($copyTitle, $this->template->createdById);
		$this->copyTemplate($this->template, $copyTemplate);
		$createTemplateResult = $this->templateRepository->add($copyTemplate);
		if (!$createTemplateResult instanceOf CreateTemplateResult)
		{
			return $createTemplateResult;
		}

		$documentForTemplate = $this->documentRepository->getByTemplateId($this->template->id);
		$copyDocumentResult = (new Operation\Document\Copy(
			document: $documentForTemplate,
			createdByUserId: $this->createdByUserId,
			templateId: $copyTemplate->id
		))->launch();
		if (!$copyDocumentResult->isSuccess())
		{
			return $copyDocumentResult;
		}
		if($this->template->createdById < 1)
		{
			return Result::createByErrorMessage('Template is not created');
		}

		$copyDocument = $copyDocumentResult->getData()['document'];
		$cloneBlankResult = (new Operation\CloneBlankForDocument($copyDocument))->launch();
		if (!$cloneBlankResult->isSuccess())
		{
			return $cloneBlankResult;
		}

		$copyDocument->templateId = $createTemplateResult->template->id;
		$copyDocument->title = $this->createCopyTitle($copyDocument->title);
		$updateDocumentResult = $this->documentRepository->update($copyDocument);
		if (!$updateDocumentResult->isSuccess())
		{
			return $updateDocumentResult;
		}

		return $updateDocumentResult->setData(['copyTemplate' => $copyTemplate]);
	}

	private function copyTemplate(Template $oldTemplate, Template $newTemplate): void
	{
		$newTemplate->createdById = $this->createdByUserId;
		$newTemplate->modifiedById = $this->createdByUserId;
		$newTemplate->dateCreate = new DateTime();
		$newTemplate->dateModify = new DateTime();
		$newTemplate->visibility = Visibility::INVISIBLE;
		CloneHelper::copyPropertiesIfPossible($oldTemplate, $newTemplate);
	}

	private function createCopyTitle(string $originalTitle): string
	{
		return Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COPY_TITLE',[
			'#TITLE#' => $originalTitle,
		]);
	}
}