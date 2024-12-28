<?php

namespace Bitrix\Sign\Service\Integration\HumanResources;

use Bitrix\HumanResources;
use Bitrix\Sign\Item;
use Bitrix\Main;
use Bitrix\Sign\Operation\Member\MakeB2eSignedFileName;
use Bitrix\Sign\Result\Service\Integration\HumanResources\HcmLinkSignedFileInfo;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\EntityFileCode;
use Bitrix\Sign\Type\EntityType;
use Bitrix\Sign\Type\Integration\HumanResources\EventType;

class HcmLinkSignedFileService
{
	public function processSignedDocument(
		Item\Document $document,
		Item\Member $member,
	): void
	{
		if (!$this->isAvailable())
		{
			return;
		}

		if (
			!$document->hcmLinkCompanyId
			|| !$member->dateSigned
			|| $member->documentId !== $document->id
		)
		{
			return;
		}

		$company = HumanResources\Service\Container::getHcmLinkCompanyRepository()
			->getById($document->hcmLinkCompanyId)
		;
		$employee = HumanResources\Service\Container::getHcmLinkEmployeeRepository()
			->getByIds([$member->employeeId])
			->getFirst()
		;

		if (!$company || !$employee)
		{
			return;
		}

		$event = new Main\Event(
			'sign',
			EventType::DOCUMENT_SIGNED->value,
			[
				'company' => $company->code,
				'memberId' => $member->id,
			],
		);

		$event->send();
	}

	public function getInfoByMemberId(int $memberId): Main\Result|HcmLinkSignedFileInfo
	{
		if (!$this->isAvailable())
		{
			return (new Main\Result())->addError(
				new Main\Error('Module humanresources is not available')
			);
		}

		$member = Container::instance()->getMemberRepository()->getById($memberId);
		if (!$member)
		{
			return (new Main\Result())->addError(
				new Main\Error(
					'Document not found',
					'SIGN_HCMLINK_DOCUMENT_NOT_FOUND'
				)
			);
		}

		$document = Container::instance()->getDocumentRepository()->getById($member->documentId);
		if (!$document?->hcmLinkCompanyId)
		{
			return (new Main\Result())->addError(
				new Main\Error(
					'Document not found',
					'SIGN_HCMLINK_DOCUMENT_NOT_FOUND'
				)
			);
		}

		$company = HumanResources\Service\Container::getHcmLinkCompanyRepository()
			->getById($document->hcmLinkCompanyId)
		;
		$employee = HumanResources\Service\Container::getHcmLinkEmployeeRepository()
			->getByIds([$member->employeeId])
			->getFirst()
		;

		if (!$company || !$employee)
		{
			return (new Main\Result())->addError(
				new Main\Error(
					'No employee or company linked to the document',
					'SIGN_HCMLINK_DOCUMENT_NOT_LINKED'
				)
			);
		}

		$documentFile = Container::instance()->getEntityFileRepository()
				 ->getOne(
					 entityTypeId: EntityType::MEMBER,
					 entityId: $member->id,
					 code: EntityFileCode::SIGNED,
				 )
		;

		if (!$documentFile?->fileId)
		{
			return (new Main\Result())->addError(
				new Main\Error(
					'Signed file for this document does not exist',
					'SIGN_HCMLINK_NO_SIGNED_FILE_EXIST'
				)
			);
		}

		$fileNameResult = (new MakeB2eSignedFileName($member, $documentFile))->launch();

		return new HcmLinkSignedFileInfo(
			company: $company->code,
			employee: $employee->code,
			documentDate: $document->dateCreate,
			documentName: $document->title,
			fileName: $fileNameResult->fileName,
		);
	}

	private function isAvailable(): bool
	{
		return Main\Loader::includeModule('humanresources');
	}
}