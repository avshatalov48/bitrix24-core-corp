<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Service\Container;

class GetFillAndStartProgress implements Operation
{
	private readonly DocumentRepository $documentRepository;

	public function __construct(
		private readonly string $uid,
		?DocumentRepository $documentRepository = null,
	)
	{
		$this->documentRepository = $documentRepository ?? Container::instance()->getDocumentRepository();
	}

	public function launch(): Main\Result
	{
		$document = $this->documentRepository->getByUid($this->uid);
		if ($document === null)
		{
			return (new Main\Result())
				->addError(new Main\Error("Document with id `$this->uid` doesnt exist"))
			;
		}

		return (new GetFillAndStartProgressByDocument($document))->launch();
	}
}