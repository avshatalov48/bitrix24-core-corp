<?php
namespace Bitrix\Crm\Recurring\Entity\Item;

use Bitrix\Main,
	Bitrix\Main\Type\Date,
	Bitrix\Crm\EntityRequisite,
	Bitrix\Crm\Recurring\Manager,
	Bitrix\Crm\Requisite\EntityLink,
	Bitrix\Crm\InvoiceRecurTable;

class InvoiceNew extends InvoiceEntity
{
	protected $basedId = null;

	/**
	 * @return array
	 */
	protected function getChangeableFields()
	{
		return [
			'INVOICE_ID', 'PARAMS', 'IS_LIMIT', 'LIMIT_REPEAT', 'LIMIT_DATE', 'START_DATE', 'SEND_BILL', 'EMAIL_ID'
		];
	}

	public static function create()
	{
		return new self();
	}

	private function isInitializedFields()
	{
		return !empty($this->recurringFields);
	}

	public function initFields(array $fields = [])
	{
		if (!$this->isInitializedFields())
		{
			$this->setFieldsNoDemand($fields);
			if ((int)$fields['INVOICE_ID'] > 0)
			{
				$this->templateId = (int)$fields['INVOICE_ID'];
			}

			$this->onFieldChange('START_DATE');
			$this->onFieldChange('EMAIL_ID');
		}
	}

	public function setTemplateField($name, $value)
	{
		if ($name !== 'ID' && $name !== 'ACCOUNT_NUMBER')
		{
			parent::setTemplateField($name, $value);
		}
	}

	/**
	 * @return Main\Result
	 * @throws Main\ObjectException
	 */
	public function save()
	{
		$result = new Main\Result();
		if ((int)($this->templateId) <= 0 && empty($this->templateFields))
		{
			$result->addError(new Main\Error('Error saving. Template invoice ID is empty.'));
			return $result;
		}

		if (!empty($this->templateFields))
		{
			$r = $this->saveInvoiceTemplate();
			if (!$r->isSuccess())
			{
				return $r;
			}
		}

		$addResult = InvoiceRecurTable::add($this->recurringFields);
		if ($addResult->isSuccess())
		{
			$this->id = $addResult->getId();
			$result->setData([
				'ID' => $this->id,
				'INVOICE_ID' => $this->templateId,
			]);
			$eventFields = $this->recurringFields;
			$eventFields['ID'] = $this->id;

			Manager::initCheckAgent(Manager::INVOICE);

			$event = new Main\Event("crm", static::ON_INVOICE_RECURRING_ADD_EVENT, $eventFields);
			$event->send();

			$entityModifyFields = [
				'TYPE' => \CCrmOwnerType::InvoiceRecurringName,
				'ID' => $this->id,
				'FIELDS' => $eventFields
			];
			$event = new Main\Event("crm", static::ON_CRM_ENTITY_RECURRING_MODIFY, $entityModifyFields);
			$event->send();
		}

		return $result;
	}

	/**
	 * @return Main\Result
	 * @throws Main\ObjectException
	 */
	private function saveInvoiceTemplate()
	{
		$result = new Main\Result();
		$invoiceController = $this->getControllerInstance();
		$this->setTemplateField('DATE_BILL', new Date());
		$this->setTemplateField('RECURRING_ID', null);
		$this->setTemplateField('IS_RECURRING', 'Y');

		try{
			$reCalculate = false;
			$this->templateId = $invoiceController->Add($this->templateFields, $reCalculate, SITE_ID, array('REGISTER_SONET_EVENT' => true, 'UPDATE_SEARCH' => true));
		}
		catch (\Exception $exception)
		{
			$result->addError(new Main\Error($exception->getMessage(), $exception->getCode()));
			return $result;
		}

		if (!$this->templateId)
		{
			$result->addError(new Main\Error($invoiceController->LAST_ERROR));
			return $result;
		}

		$this->linkInvoiceRequisite();
		$this->setFieldNoDemand('INVOICE_ID', $this->templateId);

		return $result;
	}

	private function linkInvoiceRequisite()
	{
		$requisite = new EntityRequisite();

		$requisiteInfoLinked = $this->getLinkedRequisiteInfo();
		$requisiteIdLinked = (int)$requisiteInfoLinked['REQUISITE_ID'];
		$bankDetailIdLinked = (int)$requisiteInfoLinked['BANK_DETAIL_ID'];

		$mcRequisiteIdLinked =
		$mcBankDetailIdLinked = 0;
		if ((int)$this->templateFields['UF_MYCOMPANY_ID'] > 0)
		{
			$myCompanyRequisite = [
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
				'ENTITY_ID' => (int)$this->templateFields['UF_MYCOMPANY_ID']
			];

			$mcRequisiteInfoLinked = $requisite->getDefaultMyCompanyRequisiteInfoLinked([$myCompanyRequisite]);
			$mcRequisiteIdLinked = (int)$mcRequisiteInfoLinked['MC_REQUISITE_ID'];
			$mcBankDetailIdLinked = (int)$mcRequisiteInfoLinked['MC_BANK_DETAIL_ID'];
		}

		unset($requisite, $requisiteInfoLinked, $mcRequisiteInfoLinked);

		if ($requisiteIdLinked > 0 || $mcRequisiteIdLinked > 0)
		{
			EntityLink::register(
				\CCrmOwnerType::Invoice, $this->templateId,
				$requisiteIdLinked, $bankDetailIdLinked,
				$mcRequisiteIdLinked, $mcBankDetailIdLinked
			);
		}
	}

	/**
	 * @return array
	 */
	private function getLinkedRequisiteInfo()
	{
		$requisiteEntityList = [];
		$requisite = new EntityRequisite();
		if (isset($this->templateFields['UF_COMPANY_ID']) && $this->templateFields['UF_COMPANY_ID'] > 0)
		{
			$requisiteEntityList[] = [
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
				'ENTITY_ID' => $this->templateFields['UF_COMPANY_ID']
			];
		}

		if (isset($this->templateFields['UF_CONTACT_ID']) && $this->templateFields['UF_CONTACT_ID'] > 0)
		{
			$requisiteEntityList[] = [
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Contact,
				'ENTITY_ID' => $this->templateFields['UF_CONTACT_ID']
			];
		}

		if (!empty($requisiteEntityList))
		{
			return $requisite->getDefaultRequisiteInfoLinked($requisiteEntityList);
		}

		return [];
	}
}
