<?php

namespace Bitrix\Sign\Service\Integration\Crm;

use Bitrix\DocumentGenerator\Document;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Sign\Blank;
use Bitrix\SignSafe\Exception\DocumentNotFoundException;

interface DocumentService
{
	/**
	 * Create sign document from deal document
	 *
	 * @param int $fileId
	 * @param Document $document
	 * @param int $smartDocumentId
	 * @param bool $isNew
	 *
	 * @throws \Bitrix\SignSafe\Exception\DocumentNotFoundException
	 */
	public function createSignDocumentFromDealDocument(
		int $fileId,
		Document $document,
		int $smartDocumentId,
		bool $isNew = false
	): Result;

	/**
	 * Create sign document from previous blank
	 *
	 * @param int $fileId
	 * @param int $oldBlank
	 * @param Document $document
	 * @param int $smartDocumentId
	 * @return mixed
	 */
	public function createSignDocumentFromBlank(
		int $fileId,
		int $oldBlankId,
		Document $document,
		int $smartDocumentId
	): Result;

	/**
	 * Get last sign document by blank id
	 * @param int $blankId
	 * @return \Bitrix\Sign\Document|null
	 */
	public function getLastDocumentByBlank(int $blankId): ?\Bitrix\Sign\Document;

	/**
	 * @param int $templateId
	 * @return mixed
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getLinkedBlankForDocumentGeneratorTemplate(int $templateId);

	/**
	 * @param \Bitrix\Sign\Item\Document $document
	 *
	 * @return mixed
	 */
	public function configureMembers(\Bitrix\Sign\Item\Document $document);
}