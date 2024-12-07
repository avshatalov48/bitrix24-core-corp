<?php

namespace Bitrix\Sign\Service\Sign;

use Bitrix\Sign\Repository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Item;

class DocumentFileNameService
{
	private Repository\DocumentRepository $documentRepository;

	public function __construct(
		?Repository\DocumentRepository $documentRepository = null
	)
	{
		$this->documentRepository ??= Container::instance()->getDocumentRepository();
	}

	public function getNameByUid(string $uid): string
	{
		$document = $this->documentRepository->getByUid($uid);
		return $this->makeName($document);
	}

	public function getNameByHash(string $hash): string
	{
		$document = $this->documentRepository->getByHash($hash);
		return $this->makeName($document);
	}

	public function getNameByItem(Item\Document $document): string
	{
		return $this->makeName($document);
	}

	private function makeName(?Item\Document $document): string
	{
		if ($document?->uid === null)
		{
			return 'B24Sign.pdf';
		}

		return "B24Sign_{$document->uid}.pdf";
	}
}