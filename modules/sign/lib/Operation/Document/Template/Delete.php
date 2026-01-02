<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation as OperationContract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Repository\BlankRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Service\Container;

class Delete implements OperationContract
{
	private readonly TemplateRepository $templateRepository;
	private readonly DocumentRepository $documentRepository;
	private readonly MemberRepository $memberRepository;
	private readonly BlankRepository $blankRepository;

	public function __construct(
		private readonly Item\Document\Template $template,
		?TemplateRepository $templateRepository = null,
		?DocumentRepository $documentRepository = null,
		?MemberRepository $memberRepository = null,
	)
	{
		$container = Container::instance();

		$this->templateRepository = $templateRepository ?? $container->getDocumentTemplateRepository();
		$this->documentRepository = $documentRepository ?? $container->getDocumentRepository();
		$this->memberRepository = $memberRepository ?? $container->getMemberRepository();
		$this->blankRepository = $container->getBlankRepository();
	}

	public function launch(): Main\Result
	{
		$invariantCheckResult = $this->check();
		if (!$invariantCheckResult->isSuccess())
		{
			return $invariantCheckResult;
		}

		$result = $this->templateRepository->deleteById($this->template->id);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = $this->deleteDocument();
		if (!$result->isSuccess())
		{
			return $result;
		}

		return new Main\Result();
	}

	private function check(): Main\Result
	{
		if ($this->template->id === null)
		{
			return Result::createByErrorData(message: 'Template not found');
		}

		return new Main\Result();
	}

	private function deleteDocument(): Main\Result
	{
		$document = $this->documentRepository->getByTemplateId($this->template->id);
		if ($document?->id === null)
		{
			return new Main\Result();
		}

		$result = $this->memberRepository->deleteAllByDocumentId($document->id);
		if (!$result->isSuccess())
		{
			return $result;
		}

		if ($document->blankId !== null)
		{
			$result = $this->deleteBlank($document->blankId);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return $this->documentRepository->delete($document);
	}

	private function deleteBlank(int $blankId): Main\Result
	{
		$blank = $this->blankRepository->getById($blankId);
		if ($blank === null)
		{
			return new Main\Result();
		}

		$result = (new Operation\Document\Blank\Delete($blank))->launch();
		$errorCode = $result->getError()?->getCode();

		if (
			$errorCode !== null
			&& !in_array(
				$errorCode,
				[
					Operation\Document\Blank\Delete::ERROR_BLANK_USED_FOR_RESENT_DOCUMENTS,
					Operation\Document\Blank\Delete::ERROR_BLANK_USED_IN_DOCUMENTS,
				],
				true,
			)
		)
		{
			return $result;
		}

		return new Main\Result();
	}
}
