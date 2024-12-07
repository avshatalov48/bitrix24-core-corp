<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Integration\CRM;
use Bitrix\Sign\Blank;
use Bitrix\Sign\Document;

\CBitrixComponent::includeComponentClass('bitrix:sign.base');

class SignEditorComponent extends SignBaseComponent
{
	/**
	 * Returns full list of CRM entity fields.
	 * @return array
	 */
	private function getCrmEntityFields(): array
	{
		$fields = [];
		$treeFields = \Bitrix\Sign\Integration\CRM::getEntityFields();

		foreach ($treeFields as $category => $categoryItem)
		{
			if (empty($categoryItem['FIELDS']))
			{
				continue;
			}

			foreach ($categoryItem['FIELDS'] as $fieldItem)
			{
				$fields[$fieldItem['name']] = $fieldItem['caption'];
			}
		}

		return $fields;
	}

	/**
	 * @todo meta saved to document after first editor invocation
	 */
	private function getInitiatorName(Document $document): ?string
	{
		$documentMeta = $document->getMeta();
		$initiatorName = $documentMeta['initiatorName'] ?? '';
		if (empty($initiatorName))
		{
			$initiatorName = $documentMeta['companyTitle'] ?? Loc::getMessage('SIGN_CMP_EDITOR_MEMBER_1_NAME');
		}
		return $initiatorName;
	}

	/**
	 * Executes component.
	 * @return void
	 */
	public function exec(): void
	{
		$document = Document::getById($this->getIntParam('DOC_ID'));
		$layout = $document ? $document->getLayout() : [];
		$this->setResult('DOCUMENT', $document);
		$this->setResult('LAYOUT', $layout['layout'] ?? []);

		if (!$document)
		{
			return;
		}

		if (!$document->isRegistered())
		{
			$document->register(true);
			$this->setTemplate('loader');
			return;
		}

		if (!$layout)
		{
			$this->setTemplate('loader');
			return;
		}

		if ($blank = $document->getBlank())
		{
			$this->setResult('DOCUMENT_BLOCKS', $blank->getBlocksData());
		}

		$this->setResult('INITIATOR_NAME', $this->getInitiatorName($document));
		$this->setResult('SHOW_EDITOR_DEMO', \CUserOptions::getOption('sign', 'hide_editor_demo') !== 'Y');

		$this->setResult('BLOCKS', Blank\Block::getRepository());
		$this->setResult('CRM_ENTITY_REQ_COMPANY_PRESET_ID', CRM::getMyDefaultPresetId(
			$document->getEntityId(), $document->getCompanyId()
		));
		$this->setResult('CRM_ENTITY_REQ_CONTACT_PRESET_ID', CRM::getOtherSidePresetId($document->getEntityId()));
		$this->setResult('CRM_OWNER_TYPE_CONTACT', CRM::getOwnerTypeContact());
		$this->setResult('CRM_OWNER_TYPE_COMPANY', CRM::getOwnerTypeCompany());
		$this->setResult('CRM_NUMERATOR_URL', CRM::getNumeratorUrl());
		$this->setResult('CRM_ENTITY_FIELDS', $this->getCrmEntityFields());
	}
}
