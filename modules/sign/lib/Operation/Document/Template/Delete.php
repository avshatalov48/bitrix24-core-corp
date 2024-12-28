<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Service\Container;

class Delete implements Operation
{
	private readonly TemplateRepository $templateRepository;
	private readonly DocumentRepository $documentRepository;
	private readonly MemberRepository $memberRepository;

	public function __construct(
		private readonly Item\Document\Template $template,
		?TemplateRepository $templateRepository = null,
		?DocumentRepository $documentRepository = null,
		?MemberRepository $memberRepository = null
	)
	{
		$container = Container::instance();

		$this->templateRepository = $templateRepository ?? $container->getDocumentTemplateRepository();
		$this->documentRepository = $documentRepository ?? $container->getDocumentRepository();
		$this->memberRepository = $memberRepository ?? $container->getMemberRepository();
	}

	public function launch(): Main\Result
	{
		if ($this->template->id === null)
		{
			return Result::createByErrorData(message: 'Template not found');
		}

		$templateId = $this->template->id;

		$result = $this->templateRepository->deleteById($templateId);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$document = $this->documentRepository->getByTemplateId($templateId);
		if ($document?->id === null)
		{
			return new Main\Result();
		}

		return $this->memberRepository->deleteAllByDocumentId($document->id);
	}
}