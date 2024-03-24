<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin;

use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;

/**
 * @mixin \Bitrix\Crm\Service\Timeline\Item\Configurable
 */
trait Document
{
	private \Bitrix\DocumentGenerator\Document | null | false $document = false;

	public static function isActive(): bool
	{
		return DocumentGeneratorManager::getInstance()->isEnabled();
	}

	private function getDocument(): ?\Bitrix\DocumentGenerator\Document
	{
		if ($this->document === false)
		{
			Loader::requireModule('documentgenerator');

			$this->document = \Bitrix\DocumentGenerator\Document::loadById($this->getDocumentId());

			// dead code for Document Activity
			// DocumentGeneratorManager::getInstance()->actualizeDocumentImmediately($this->document);
		}

		return $this->document;
	}

	private function getDocumentId(): int
	{
		return (int)$this->getModel()->getAssociatedEntityId();
	}

	private function getOpenDocumentAction(): Layout\Action
	{
		$document = $this->getDocument();
		$action =
			(new Layout\Action\JsEvent('Document:Open'))
				->addActionParamInt('documentId', $this->getDocumentId())
				->addActionParamString('title', $document?->getTitle())
				->addActionParamString('createdAt', $document?->getCreateTime()->format(\DateTimeInterface::ATOM))
		;

		$pdfUrl = $this->getPdfUrl();
		if ($pdfUrl)
		{
			$action->addActionParamString('pdfUrl', (string)$pdfUrl);
		}

		return $action;
	}

	private function getDownloadUrl(): ?Uri
	{
		return $this->getDocument()?->getDownloadUrl();
	}

	private function getPdfUrl(): ?Uri
	{
		$documentData = $this->getDocument()?->getFile(false)->getData();

		return $documentData['pdfUrl'] ?? null;
	}

	private function getPrintUrl(): ?Uri
	{
		$documentData = $this->getDocument()?->getFile(false)->getData();

		return $documentData['printUrl'] ?? null;
	}

	private function getPublicUrl(): ?Uri
	{
		return $this->getDocument()?->getPublicUrl();
	}
}
