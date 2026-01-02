<?php

namespace Bitrix\Crm\Integration\BizProc\Document\ValueCollection;

use Bitrix\Crm;

class Deal extends Base
{
	protected $contactDocument;
	protected $companyDocument;

	protected array $contactFields;
	protected array $companyFields;

	protected function processField(string $fieldId): bool
	{
		if ($fieldId === 'CONTACT_IDS')
		{
			$this->document['CONTACT_IDS'] = Crm\Binding\DealContactTable::getDealContactIDs($this->id);

			return true;
		}

		if ($fieldId === 'ORDER_IDS')
		{
			$this->loadOrderIdValues();

			return true;
		}

		if (strpos($fieldId, 'CONTACT.') === 0)
		{
			$this->loadContactFieldValue($fieldId);

			return true;
		}

		if (strpos($fieldId, 'COMPANY.') === 0)
		{
			$this->loadCompanyFieldValue($fieldId);

			return true;
		}

		return false;
	}

	protected function loadEntityValues(): void
	{
		if (isset($this->document['ID']))
		{
			return;
		}

		$this->contactFields = $this->extractFieldsByPrefix('CONTACT.');
		$this->companyFields = $this->extractFieldsByPrefix('COMPANY.');
		$this->addSelectField($this->contactFields, 'CONTACT_ID');
		$this->addSelectField($this->companyFields, 'COMPANY_ID');

		$this->prepareFieldGroups();

		$result = \CCrmDeal::GetListEx(
			[],
			[
				'ID' => $this->id,
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			$this->select
		);

		$this->document = array_merge($this->document, $result->fetch() ?: []);
		$this->loadAdditionalValues();
		$this->document = Crm\Entity\CommentsHelper::prepareFieldsFromBizProc($this->typeId, $this->id, $this->document);
	}

	protected function loadAdditionalValues(): void
	{
		$this->appendDefaultUserPrefixes();

		$categoryId = isset($this->document['CATEGORY_ID']) ? (int)$this->document['CATEGORY_ID'] : 0;
		if (isset($this->document['CATEGORY_ID']))
		{
			$this->document['CATEGORY_ID_PRINTABLE'] = Crm\Category\DealCategory::getName($categoryId);
		}

		$stageId = $this->document['STAGE_ID'] ?? '';
		if (!empty($stageId))
		{
			$this->document['STAGE_ID_PRINTABLE'] = Crm\Category\DealCategory::getStageName($stageId, $categoryId);
		}

		if (!$this->optimizationEnabled || in_array('CONTACT_IDS', $this->select, true))
		{
			$this->document['CONTACT_IDS'] = Crm\Binding\DealContactTable::getDealContactIDs($this->id);
		}

		if (!$this->optimizationEnabled || in_array('ORDER_IDS', $this->select, true))
		{
			$this->loadOrderIdValues();
		}

		if (!empty($this->document['CONTACT_ID']))
		{
			$this->loadContactFieldValues($this->contactFields);
		}

		if (!empty($this->document['COMPANY_ID']))
		{
			$this->loadCompanyFieldValues($this->companyFields);
		}

		$this->normalizeEntityBindings(['COMPANY_ID', 'CONTACT_ID']);
		$this->loadUserFieldValues();
		$this->loadCommonFieldValues();
	}

	protected function loadOrderIdValues(): void
	{
		$this->document['ORDER_IDS'] = Crm\Binding\OrderEntityTable::getOrderIdsByOwner($this->id, \CCrmOwnerType::Deal);
	}

	protected function loadContactFieldValue($fieldId): void
	{
		$contactFieldId = substr($fieldId, strlen('CONTACT.'));
		if ($this->contactDocument[$contactFieldId] === null)
		{
			if (!$this->document['CONTACT_ID'])
			{
				$this->select = array_merge($this->select, ['CONTACT_ID']);
				$this->loadEntityValues();
			}

			if ($this->document['CONTACT_ID'])
			{
				$this->contactDocument = \CCrmDocumentContact::getDocument('CONTACT_' . $this->document['CONTACT_ID']);
			}
		}

		if ($this->contactDocument[$contactFieldId])
		{
			$this->document[$fieldId] = $this->contactDocument[$contactFieldId];
		}
	}

	protected function loadCompanyFieldValue($fieldId): void
	{
		$companyFieldId = substr($fieldId, strlen('COMPANY.'));
		if ($this->companyDocument[$companyFieldId] === null)
		{
			if (!$this->document['COMPANY_ID'])
			{
				$this->select = array_merge($this->select, ['COMPANY_ID']);
				$this->loadEntityValues();
			}

			if ($this->document['COMPANY_ID'])
			{
				$this->companyDocument = \CCrmDocumentCompany::GetDocument('COMPANY_' . $this->document['COMPANY_ID']);
			}
		}

		if ($this->companyDocument[$companyFieldId])
		{
			$this->document[$fieldId] = $this->companyDocument[$companyFieldId];
		}
	}

	protected function loadContactFieldValues(array $fields): void
	{
		$this->loadContactValues($this->document, $this->contactDocument, $fields);
	}

	protected function loadCompanyFieldValues(array $fields): void
	{
		$this->loadCompanyValues($this->document, $this->companyDocument, $fields);
	}
}
