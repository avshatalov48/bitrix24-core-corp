<?php

namespace Bitrix\Crm\Integration\BizProc\Document\ValueCollection;

use Bitrix\Crm;
use Bitrix\Main\Localization\Loc;

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
		elseif (strpos($fieldId, 'PRODUCT_IDS') === 0)
		{
			$this->loadProductValues();
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
			['*', 'UF_*']
		);

		$this->document = array_merge($this->document, $result->fetch() ?: []);

		$this->appendDefaultUserPrefixes();

		$categoryId = isset($this->document['CATEGORY_ID']) ? (int)$this->document['CATEGORY_ID'] : 0;
		$this->document['CATEGORY_ID_PRINTABLE'] = Crm\Category\DealCategory::getName($categoryId);

		$stageId = $this->document['STAGE_ID'] ?? '';
		$this->document['STAGE_ID_PRINTABLE'] = Crm\Category\DealCategory::getStageName($stageId, $categoryId);

		if ($this->document['COMPANY_ID'] <= 0)
		{
			$this->document['COMPANY_ID'] = null;
		}

		if ($this->document['CONTACT_ID'] <= 0)
		{
			$this->document['CONTACT_ID'] = null;
		}

		$this->loadUserFieldValues();
	}

	protected function loadOrderIdValues(): void
	{
		$orderIds = Crm\Binding\OrderDealTable::getList([
			'select' => ['ORDER_ID'],
			'filter' => [
				'=DEAL_ID' => $this->id,
			],
			'order' => ['ORDER_ID' => 'DESC'],
		])->fetchAll();

		$this->document['ORDER_IDS'] = array_column($orderIds, 'ORDER_ID');
	}

	protected function loadProductValues(): void
	{
		$productRows = Crm\ProductRowTable::getList([
			'select' => ['ID', 'PRODUCT_ID', 'CP_PRODUCT_NAME', 'SUM_ACCOUNT'],
			'filter' => [
				'=OWNER_TYPE' => \CCrmOwnerTypeAbbr::Deal,
				'=OWNER_ID' => $this->id,
			],
			'order' => ['SORT' => 'ASC'],
		])->fetchAll();

		$this->document['PRODUCT_IDS'] = array_column($productRows, 'ID');
		$this->document['PRODUCT_IDS_PRINTABLE'] = '';

		if (!empty($productRows))
		{
			$this->document['PRODUCT_IDS_PRINTABLE'] = $this->getProductRowsPrintable($productRows);
		}
	}

	protected function getProductRowsPrintable(array $rows): string
	{
		$text = sprintf(
			'[table][tr][th]%s[/th][th]%s[/th][/tr]',
			Loc::getMessage('CRM_DOCUMENT_FIELD_PRODUCT_NAME'),
			Loc::getMessage('CRM_DOCUMENT_FIELD_PRODUCT_SUM')
		);

		$currencyId = \CCrmCurrency::GetAccountCurrencyID();

		foreach ($rows as $row)
		{
			$text .= sprintf(
				'[tr][td]%s[/td][td]%s[/td][/tr]',
				$row['CP_PRODUCT_NAME'],
				\CCrmCurrency::MoneyToString($row['SUM_ACCOUNT'], $currencyId)
			);
		}

		return $text . '[/table]';
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
