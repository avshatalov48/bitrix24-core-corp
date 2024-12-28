<?php

namespace Bitrix\Sign\Controllers\V1\Document;

use Bitrix\Main;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Access\Permission\PermissionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Item;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Service;
use Bitrix\Sign\Type;
use Bitrix\Sign\Type\Access\AccessibleItemType;

class SignedFile extends \Bitrix\Sign\Engine\Controller
{
	public function configureActions(): array
	{
		$actionsConfiguration = parent::configureActions();
		$actionsConfiguration['download']['-prefilters'] = [
			Main\Engine\ActionFilter\ContentType::class
		];

		return $actionsConfiguration;
	}

	/**
	 * @param string $documentUid
	 * @param string|null $memberUid
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	#[Attribute\ActionAccess(
		permission: ActionDictionary::ACTION_MY_SAFE_DOCUMENTS,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentUid'
	)]
	public function loadAction(string $documentUid, ?string $memberUid = null): array
	{
		$document = Service\Container::instance()->getDocumentRepository()->getByUid($documentUid);
		if (!$document)
		{
			$this->addError(new Main\Error('Document not found'));
			return [];
		}

		$operation = new Operation\GetSignedFilePdfUrl($documentUid, $memberUid);
		$result = $operation->launch();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return [];
		}

		return [
			'ready' => $operation->ready,
			'file' => [
				'url' => $operation->url,
			]
		];
	}

	public function downloadAction(string $documentUid): Main\Engine\Response\BFile | array
	{
		$document = Service\Container::instance()->getDocumentRepository()->getByUid($documentUid);
		if (!$document)
		{
			$this->addError(new Main\Error(
				'Document not found',
				'SIGN_DOCUMENT_NOT_FOUND',
			));
			return [];
		}

		$result = $this->checkDownloadAccess($document);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return [];
		}

		if ($document->status !== Type\DocumentStatus::DONE)
		{
			$this->addError(new Main\Error(
				'Document has invalid status',
				'SIGN_DOCUMENT_INVALID_STATUS',
			));
			return [];
		}

		if ($document->resultFileId <= 0)
		{
			$this->addError(new Main\Error(
				'Document has no result file',
				'SIGN_DOCUMENT_NO_RESULT_FILE',
			));
			return [];
		}

		return Main\Engine\Response\BFile::createByFileId($document->resultFileId)
			->showInline(false)
		;
	}

	private function checkDownloadAccess(Item\Document $document): Main\Result
	{
		$result = (new Operation\CheckDocumentAccess($document, SignPermissionDictionary::SIGN_MY_SAFE_DOCUMENTS))->launch();
		if ($result->isSuccess())
		{
			return $result;
		}

		return (new Operation\CheckDocumentAccess($document, PermissionDictionary::SIGN_CRM_SMART_DOCUMENT_READ))->launch();
	}
}