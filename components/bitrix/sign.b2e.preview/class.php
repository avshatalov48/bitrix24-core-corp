<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Document\EntityType;

\CBitrixComponent::includeComponentClass('bitrix:sign.base');

class SignB2ePreviewComponent extends SignBaseComponent
{
	/**
	 * Executing before actions.
	 * @return void
	 */
	protected function beforeActions(): void
	{
		$document = null;

		$entityId = (int)$this->getRequest($this->getStringParam('VAR_DOC_ID'));
		$documentId = (int)$this->getRequest($this->getStringParam('VAR_DOCUMENT_ID'));
		if ($entityId > 0)
		{
			$document = Container::instance()
				->getDocumentRepository()
				->getByEntityIdAndType($entityId, EntityType::SMART_B2E)
			;
		}
		elseif ($documentId > 0)
		{
			$document = Container::instance()
				->getDocumentRepository()
				->getById($documentId)
			;
		}

		if ($document && $this->isAllowed($document))
		{
			$this->setResult('DOCUMENT', $document);
		}
	}

	protected function isAllowed(Document $document): bool
	{
		return $this
			->accessController
			->checkByItem(ActionDictionary::ACTION_B2E_DOCUMENT_READ, $document)
		;
	}

}
