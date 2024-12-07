<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Sign\File;
use Bitrix\Sign\Service;
use Bitrix\Sign\Type;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository;
use Bitrix\Main;
use Bitrix\Sign\Contract;

class GetSignedFilePdfUrl implements Contract\Operation
{
	public ?bool $ready = null;
	public ?string $url = null;

	private const AJAX_PATH = "/bitrix/services/main/ajax.php";

	public function __construct(
		private string $documentUid,
		private ?string $memberUid = null,
		private ?Repository\MemberRepository $memberRepository = null,
		private ?Repository\DocumentRepository $documentRepository = null,
		private ?Service\Sign\DocumentService $documentService = null,
	)
	{
		$this->memberRepository ??= Service\Container::instance()->getMemberRepository();
		$this->documentRepository ??= Service\Container::instance()->getDocumentRepository();
		$this->documentService ??= Service\Container::instance()->getDocumentService();
	}

	public function launch(): Main\Result
	{
		$result = new Main\Result();

		$document = Service\Container::instance()->getDocumentRepository()->getByHash($this->documentUid);
		if (!$document)
		{
			return $result->addError(new Main\Error('Document not found'));
		}

		if ($document->version === 1)
		{
			return $this->getLinkUsingOldApi($document);
		}

		if ($document->status === Type\DocumentStatus::DONE && $document->resultFileId > 0)
		{
			$this->ready = true;
			$this->url = self::AJAX_PATH . "?action=sign.api_v1.document.signedfile.download&documentUid=$document->uid";
			return $result;
		}

		$member = null;
		if ($this->memberUid)
		{
			$member = Service\Container::instance()->getMemberRepository()->getByUid($this->memberUid);
		}
		$request = new Item\Api\Document\SignedFileLoadRequest($document->uid, $member?->uid);

		$apiLoad = Service\Container::instance()->getSignedFileLoadService();
		$response = $apiLoad->load($request);
		if (!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}

		$this->ready = $response->ready;
		$this->url = $response->file?->url;

		return $result;
	}

	private function getLinkUsingOldApi(Item\Document $document): Main\Result
	{
		$result = new Main\Result();

		if ($document->resultFileId > 0)
		{
			$this->ready = true;
			$this->url = self::AJAX_PATH . "?action=sign.document.downloadResultFile&documentHash=$this->documentUid";
			return $result;
		}

		$document = \Bitrix\Sign\Document::getByHash($this->documentUid);

		$data = [
			'documentHash' => $document->getHash(),
			'secCode' => $document->getSecCode(),
		];

		if ($this->memberUid)
		{
			$data['memberHash'] = $this->memberUid;
		}

		$status = \Bitrix\Sign\Proxy::sendCommand('document.file.getStatus',
			$data
		)['status'] ?? '';

		if ($status !== 'exists')
		{
			return $result->addError(new Main\Error('Document file doesnt exist'));
		}

		$basePath = self::AJAX_PATH . '?action=sign.document.getFileForSrc&documentHash=%s';
		$this->ready = true;
		$this->url = $this->memberUid
			? sprintf(
				$basePath . '&memberHash=%s',
				$document->getHash(),
				$this->memberUid
			)
			: sprintf(
				$basePath,
				$document->getHash()
			)
		;

		return $result;
	}
}