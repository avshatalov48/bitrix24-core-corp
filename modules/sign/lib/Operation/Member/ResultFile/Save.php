<?php

namespace Bitrix\Sign\Operation\Member\ResultFile;

use Bitrix\Main;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\EntityFileRepository;
use Bitrix\Sign\Repository\FileRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\B2e\MyDocumentsGrid\EventService;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Integration\HumanResources\HcmLinkSignedFileService;
use Bitrix\Sign\Service\Sign\LegalLogService;
use Bitrix\Sign\Type;

class Save implements Contract\Operation
{
	private readonly FileRepository $fileRepository;
	private readonly EntityFileRepository $entityFileRepository;
	private readonly LegalLogService $legalLogService;
	private readonly MemberRepository $memberRepository;
	private readonly HcmLinkSignedFileService $hcmLinkSignedFileService;
	private readonly EventService $myDocumentGridEventService;

	public function __construct(
		private readonly Item\Document $document,
		private readonly Item\Member $member,
		private readonly Item\Fs\File $resultFile,
	)
	{
		$this->fileRepository = Container::instance()->getFileRepository();
		$this->memberRepository = Container::instance()->getMemberRepository();
		$this->legalLogService = Container::instance()->getLegalLogService();
		$this->entityFileRepository = Container::instance()->getEntityFileRepository();
		$this->hcmLinkSignedFileService = Container::instance()->getHcmLinkSignedFileService();
		$this->myDocumentGridEventService = Container::instance()->getMyDocumentGridEventService();
	}

	public function launch(): Main\Result
	{
		$result = $this->validateArguments();
		if (!$result->isSuccess())
		{
			return $result;
		}
		$result = $this->saveResultFileIfItsNot();
		if (!$result->isSuccess())
		{
			return $result;
		}

		$member = $this->member;
		$isDone = $this->addEntityFile();

		if (!$isDone->isSuccess())
		{
			Result::createByErrorMessage('Failed to save file');

			return $result;
		}

		$documentItem = $this->document;

		$this->logMemberFileSaved($documentItem, $member, $this->resultFile);
		$this->updateMemberDateSigned($member);

		if (!empty($documentItem->hcmLinkCompanyId))
		{
			$this->hcmLinkSignedFileService
				->processSignedDocument($documentItem, $member)
			;
		}

		$this->myDocumentGridEventService->onMemberResultFileSave($documentItem, $member);

		return $result;
	}

	private function addEntityFile(): Main\Result
	{
		$result = $this->validateArguments();
		if (!$result->isSuccess())
		{
			return $result;
		}
		$result = $this->saveResultFileIfItsNot();
		if (!$result->isSuccess())
		{
			return $result;
		}

		$fileItem = new Item\EntityFile(
			id: null,
			entityTypeId: Type\EntityType::MEMBER,
			entityId: $this->member->id,
			code: Type\EntityFileCode::SIGNED,
			fileId: $this->resultFile->id,
		);

		return $this->entityFileRepository->add($fileItem);
	}

	private function logMemberFileSaved(Item\Document $document, Item\Member $member, Item\Fs\File $fsFile): void
	{
		if (!$this->validateArguments()->isSuccess())
		{
			return;
		}
		if (!$this->saveFileIfItsNot($fsFile)->isSuccess())
		{
			return;
		}

		$this->legalLogService->registerMemberFileSaved($document, $member, $fsFile->id);
	}

	private function updateMemberDateSigned(Item\Member $member): void
	{
		if (!$this->validateArguments()->isSuccess())
		{
			return;
		}

		if ($member->dateSigned === null)
		{
			$member->dateSigned = new Main\Type\DateTime();
			$this->memberRepository->update($member);
		}
	}

	private function validateArguments(): Main\Result
	{
		$result = new Main\Result();
		if ($this->member->id === null)
		{
			$result->addError(new Main\Error('Member id is required'));
		}

		if ($this->document->id === null)
		{
			$result->addError(new Main\Error('Document id is required'));
		}

		return $result;
	}

	private function saveResultFileIfItsNot(): Main\Result
	{
		return $this->saveFileIfItsNot($this->resultFile);
	}

	private function saveFileIfItsNot(Item\Fs\File $file): Main\Result
	{
		$result = new Main\Result();
		if ($file->id === null)
		{
			$saveResult = $this->fileRepository->put($file);
			if (!$saveResult->isSuccess())
			{
				$result->addErrors($saveResult->getErrors());
			}
		}

		return $result;
	}
}
