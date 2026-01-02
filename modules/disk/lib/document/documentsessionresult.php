<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document;

use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Main\Result;

class DocumentSessionResult extends Result
{
	public function setDocumentSession(DocumentSession $documentSession): void
	{
		$this->setData(['session' => $documentSession]);
	}

	public function getDocumentSession(): ?DocumentSession
	{
		return $this->getData()['session'];
	}

}