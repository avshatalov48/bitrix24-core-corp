<?php

namespace Bitrix\Crm\Integration\BizProc\Document\ValueCollection;

use Bitrix\Crm;

class Deal extends Base
{
	protected $contactDocument;
	protected $companyDocument;

	public function loadValue(string $fieldId): void
	{
		if ($fieldId === 'CONTACT_IDS')
		{
			$this->document['CONTACT_IDS'] = Crm\Binding\DealContactTable::getDealContactIDs($this->id);
		}
		elseif ($fieldId === 'ORDER_IDS')
		{
			$this->loadOrderIdValues();
		}
		elseif (strpos($fieldId, 'CONTACT.') === 0)
		{
			$this->loadContactFieldValue($fieldId);
		}
		elseif (strpos($fieldId, 'COMPANY.') === 0)
		{
			$this->loadCompanyFieldValue($fieldId);
		}
		else
		{
			$this->loadEntityValues();
		}
	}

	protected function loadEntityValues(): void
	{
		if (isset($this->document['ID']))
		{
			return;
		}

		$result = \CCrmDeal::GetListEx(
			[],
			[
				'ID' => $this->id,
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			['*']
		);

		$this->document = array_merge($this->document, $result->fetch() ?: []);

		$this->appendDefaultUserPrefixes();

		$categoryId = isset($this->document['CATEGORY_ID']) ? (int)$this->document['CATEGORY_ID'] : 0;
		$this->document['CATEGORY_ID_PRINTABLE'] = Crm\Category\DealCategory::getName($categoryId);

		$stageId = $this->document['STAGE_ID'] ?? '';
		$this->document['STAGE_ID_PRINTABLE'] = Crm\Category\DealCategory::getStageName($stageId, $categoryId);

		$this->normalizeEntityBindings(['COMPANY_ID', 'CONTACT_ID']);
		$this->loadUserFieldValues();

		$this->document = Crm\Entity\CommentsHelper::prepareFieldsFromBizProc($this->typeId, $this->id, $this->document);
	}

	protected function loadOrderIdValues(): void
	{
		$this->document['ORDER_IDS'] = Crm\Binding\OrderEntityTable::getOrderIdsByOwner($this->id, \CCrmOwnerType::Deal);
	}

	protected function loadContactFieldValue($fieldId): void
	{
		if ($this->contactDocument === null)
		{
			$this->loadEntityValues();
			if ($this->document['CONTACT_ID'])
			{
				$this->contactDocument = \CCrmDocumentContact::getDocument('CONTACT_' . $this->document['CONTACT_ID']);
			}
		}

		if ($this->contactDocument)
		{
			$contactFieldId = substr($fieldId, strlen('CONTACT.'));
			$this->document[$fieldId] = $this->contactDocument[$contactFieldId];
		}
	}

	protected function loadCompanyFieldValue($fieldId): void
	{
		if ($this->companyDocument === null)
		{
			$this->loadEntityValues();
			if ($this->document['COMPANY_ID'])
			{
				$this->companyDocument = \CCrmDocumentCompany::GetDocument('COMPANY_' . $this->document['COMPANY_ID']);
			}
		}

		if ($this->companyDocument)
		{
			$companyFieldId = substr($fieldId, strlen('COMPANY.'));
			$this->document[$fieldId] = $this->companyDocument[$companyFieldId];
		}
	}
}
