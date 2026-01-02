<?php

namespace Bitrix\Sign\Operation\Document;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation as OperationContract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Repository\BlankRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\Container;

class Delete implements OperationContract
{
	private readonly DocumentRepository $documentRepository;
	private readonly MemberRepository $memberRepository;
	private readonly BlankRepository $blankRepository;

	public function __construct(
		private readonly Item\Document $document,
		?DocumentRepository $documentRepository = null,
		?MemberRepository $memberRepository = null,
	)
	{
		$container = Container::instance();

		$this->documentRepository = $documentRepository ?? $container->getDocumentRepository();
		$this->memberRepository = $memberRepository ?? $container->getMemberRepository();
		$this->blankRepository = $container->getBlankRepository();
	}

	public function launch(): Main\Result
	{
		$checkResult = $this->check();
		if (!$checkResult->isSuccess())
		{
			return $checkResult;
		}

		$result = $this->deleteMembers();
		if (!$result->isSuccess())
		{
			return $result;
		}

		if ($this->document->blankId !== null)
		{
			$result = $this->deleteBlank($this->document->blankId);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return $this->documentRepository->delete($this->document);
	}

	private function check(): Main\Result
	{
		if ($this->document->id === null)
		{
			return Result::createByErrorData(message: 'Document not found');
		}

		return new Main\Result();
	}

	private function deleteMembers(): Main\Result
	{
		return $this->memberRepository->deleteAllByDocumentId($this->document->id);
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
