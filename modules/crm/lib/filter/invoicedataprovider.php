<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class InvoiceDataProvider extends EntityDataProvider
{
	/** @var InvoiceSettings|null */
	protected $settings = null;

	function __construct(InvoiceSettings $settings)
	{
		$this->settings = $settings;
	}

	/**
	 * Get Settings
	 * @return InvoiceSettings
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * Get specified entity field caption.
	 * @param string $fieldID Field ID.
	 * @return string
	 */
	protected function getFieldName($fieldID)
	{
		$name = Loc::getMessage("CRM_INVOICE_FILTER_{$fieldID}");
		if($name === null)
		{
			$name = \CCrmInvoice::GetFieldCaption($fieldID);
		}

		return $name;
	}

	/**
	 * Prepare field list.
	 * @return Field[]
	 */
	public function prepareFields()
	{
		$result =  [
			'ID' => $this->createField('ID'),
			'ACCOUNT_NUMBER' => $this->createField('ACCOUNT_NUMBER'),
			'ORDER_TOPIC' => $this->createField('ORDER_TOPIC'),
			'PRICE' => $this->createField('PRICE', ['type' => 'number']),
			'DATE_INSERT' => $this->createField('DATE_INSERT', ['type' => 'date'])
		];

		if($this->settings->checkFlag(InvoiceSettings::FLAG_RECURRING))
		{
			$result += array(
				'RESPONSIBLE_ID' => $this->createField(
					'RESPONSIBLE_ID',
					['type' => 'dest_selector', 'default' => true, 'partial' => true]
				),
				'ENTITIES_LINKS' => $this->createField(
					'ENTITIES_LINKS',
					['type' => 'dest_selector', 'default' => false, 'partial' => true]
				),
				'UF_MYCOMPANY_ID' => $this->createField(
					'UF_MYCOMPANY_ID',
					['type' => 'dest_selector', 'default' => false, 'partial' => true]
				),
				'CRM_INVOICE_RECURRING_ACTIVE' => $this->createField(
					'CRM_INVOICE_RECURRING_ACTIVE',
					array('default' => true, 'type' => 'checkbox')
				),
				'CRM_INVOICE_RECURRING_NEXT_EXECUTION' => $this->createField(
					'CRM_INVOICE_RECURRING_NEXT_EXECUTION',
					array('default' => true, 'type' => 'date')
				),
				'CRM_INVOICE_RECURRING_LIMIT_DATE' => $this->createField(
					'CRM_INVOICE_RECURRING_LIMIT_DATE',
					array('default' => true, 'type' => 'date')
				),
				'CRM_INVOICE_RECURRING_COUNTER_REPEAT' => $this->createField(
					'CRM_INVOICE_RECURRING_COUNTER_REPEAT',
					array('default' => true, 'type' => 'number')
				)
			);
		}
		else
		{
			$result += array(
				'DATE_UPDATE' => $this->createField('DATE_UPDATE', array('type' => 'date')),
				'DATE_BILL' => $this->createField(
					'DATE_BILL',
					array('default' => true, 'type' => 'date')
				),
				'DATE_PAY_BEFORE' => $this->createField(
					'DATE_PAY_BEFORE',
					array('default' => true, 'type' => 'date')
				),
				'STATUS_ID' => $this->createField(
					'STATUS_ID',
					array('default' => true, 'type' => 'list', 'partial' => true)
				),
				'DATE_STATUS' => $this->createField(
					'DATE_STATUS',
					array('default' => false, 'type' => 'date')
				),
				'PAY_VOUCHER_NUM' => $this->createField('PAY_VOUCHER_NUM'),
				'PAY_VOUCHER_DATE' => $this->createField(
					'PAY_VOUCHER_DATE',
					array('default' => false, 'type' => 'date')
				),
				'DATE_MARKED' => $this->createField(
					'DATE_MARKED',
					array('default' => false, 'type' => 'date')
				),
				'RESPONSIBLE_ID' => $this->createField(
					'RESPONSIBLE_ID',
					['type' => 'dest_selector', 'default' => true, 'partial' => true]
				),
				'ENTITIES_LINKS' => $this->createField(
					'ENTITIES_LINKS',
					['type' => 'dest_selector', 'default' => false, 'partial' => true]
				),
				'UF_MYCOMPANY_ID' => $this->createField(
					'UF_MYCOMPANY_ID',
					['type' => 'dest_selector', 'default' => false, 'partial' => true]
				),
			);
		}

		return $result;
	}

	/**
	 * Prepare complete field data for specified field.
	 * @param string $fieldID Field ID.
	 * @return array|null
	 * @throws Main\NotSupportedException
	 */
	public function prepareFieldData($fieldID)
	{
		if ($fieldID === 'RESPONSIBLE_ID')
		{
			return array(
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_INVOICE_FILTER_RESPONSIBLE_ID',
					'multiple' => 'Y',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U'
				)
			);
		}
		else if ($fieldID === 'ENTITIES_LINKS')
		{
			return array(
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_INVOICE_FILTER_ENTITY',
					'contextCode' => 'CRM',
					'useClientDatabase' => 'N',
					'enableAll' => 'N',
					'enableDepartments' => 'N',
					'enableUsers' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'enableCrm' => 'Y',
					'enableCrmCompanies' => 'Y',
					'enableCrmContacts' => 'Y',
					'enableCrmDeals' => 'Y',
					'enableCrmQuotes' => 'Y',
					'addTabCrmCompanies' => 'Y',
					'addTabCrmContacts' => 'Y',
					'addTabCrmDeals' => 'Y',
					'addTabCrmQuotes' => 'Y',
					'convertJson' => 'Y'
				)
			);
		}
		else if ($fieldID === 'UF_MYCOMPANY_ID')
		{
			return array(
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_INVOICE_FILTER_UF_MYCOMPANY_ID',
					'contextCode' => 'CRM',
					'useClientDatabase' => 'N',
					'enableAll' => 'N',
					'enableDepartments' => 'N',
					'enableUsers' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'enableCrm' => 'Y',
					'enableCrmCompanies' => 'Y',
					'convertJson' => 'Y'
				)
			);
		}
		else if ($fieldID === 'STATUS_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmStatus::GetStatusList('INVOICE_STATUS')
			);
		}

		return null;
	}
}