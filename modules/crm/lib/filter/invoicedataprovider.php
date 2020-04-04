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
		$result =  array(
			'ID' => $this->createField('ID'),
			'ACCOUNT_NUMBER' => $this->createField('ACCOUNT_NUMBER'),
			'ORDER_TOPIC' => $this->createField('ORDER_TOPIC'),
			'PRICE' => $this->createField('PRICE', array('type' => 'number')),
			'DATE_INSERT' => $this->createField('DATE_INSERT', array('type' => 'date')),
			'RESPONSIBLE_ID' => $this->createField(
				'RESPONSIBLE_ID',
				array('type' => 'custom_entity', 'default' => true, 'partial' => true)
			),
			'ENTITIES_LINKS' => $this->createField(
				'ENTITIES_LINKS',
				array('type' => 'custom_entity', 'default' => false, 'partial' => true)
			),
			'UF_MYCOMPANY_ID' => $this->createField(
				'UF_MYCOMPANY_ID',
				array('type' => 'custom_entity', 'default' => false, 'partial' => true)
			),
		);

		if($this->settings->checkFlag(InvoiceSettings::FLAG_RECURRING))
		{
			$result += array(
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
				'DATE_PAY_BEFORE' => $this->createField(
					'DATE_PAY_BEFORE',
					array('default' => true, 'type' => 'date')
				),
				'STATUS_ID' => $this->createField(
					'STATUS_ID',
					array('default' => true, 'type' => 'list', 'partial' => true)
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
				'selector' => array(
					'TYPE' => 'user',
					'DATA' => array('ID' => 'responsible', 'FIELD_ID' => 'RESPONSIBLE_ID')
				)
			);
		}
		else if ($fieldID === 'ENTITIES_LINKS')
		{
			return array(
				'selector' => array(
					'TYPE' => 'crm_entity',
					'DATA' => array(
						'ID' => 'entities_links',
						'FIELD_ID' => 'ENTITIES_LINKS',
						'ENTITY_TYPE_NAMES' => array(
							\CCrmOwnerType::DealName,
							\CCrmOwnerType::QuoteName,
							\CCrmOwnerType::CompanyName,
							\CCrmOwnerType::ContactName
						),
						'IS_MULTIPLE' => false
					)
				)
			);
		}
		else if ($fieldID === 'UF_MYCOMPANY_ID')
		{
			return array(
				'selector' => array(
					'TYPE' => 'crm_entity',
					'DATA' => array(
						'ID' => 'uf_mycompany',
						'FIELD_ID' => 'UF_MYCOMPANY_ID',
						'ENTITY_TYPE_NAMES' => array(\CCrmOwnerType::CompanyName),
						'IS_MULTIPLE' => false
					)
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