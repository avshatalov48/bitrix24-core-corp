<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

use Bitrix\Crm;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\PhaseSemantics;

Loc::loadMessages(__FILE__);

class QuoteDataProvider extends Main\Filter\EntityDataProvider
{
	/** @var QuoteSettings|null */
	protected $settings = null;

	function __construct(QuoteSettings $settings)
	{
		$this->settings = $settings;
	}

	/**
	 * Get Settings
	 * @return QuoteSettings
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
		$name = Loc::getMessage("CRM_QUOTE_FILTER_{$fieldID}");
		if($name === null)
		{
			$name = \CCrmQuote::GetFieldCaption($fieldID);
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
			'QUOTE_NUMBER' => $this->createField('QUOTE_NUMBER'),
			'TITLE' => $this->createField('TITLE'),
			'ASSIGNED_BY_ID' => $this->createField(
				'ASSIGNED_BY_ID',
				array('type' => 'dest_selector', 'default' => true, 'partial' => true)
			),

			'OPPORTUNITY' => $this->createField(
				'OPPORTUNITY',
				array('type' => 'number')
			),
			'CURRENCY_ID' => $this->createField(
				'CURRENCY_ID',
				array('type' => 'list', 'partial' => true)
			),
			'STATUS_ID' => $this->createField(
				'STATUS_ID',
				array('type' => 'list', 'partial' => true)
			),
			'BEGINDATE' => $this->createField(
				'BEGINDATE',
				array('type' => 'date')
			),
			'CLOSEDATE' => $this->createField(
				'CLOSEDATE',
				array('type' => 'date', 'default' => true)
			),
			'CLOSED' => $this->createField(
				'CLOSED',
				array('type' => 'checkbox')
			),
			'LEAD_ID' => $this->createField(
				'LEAD_ID',
				array('type' => 'dest_selector', 'partial' => true)
			),
			'DEAL_ID' => $this->createField(
				'DEAL_ID',
				array('type' => 'dest_selector', 'partial' => true)
			),
			'CONTACT_ID' => $this->createField(
				'CONTACT_ID',
				array('type' => 'dest_selector', 'default' => true, 'partial' => true)
			),
			'CONTACT_FULL_NAME' => $this->createField('CONTACT_FULL_NAME'),
			'COMPANY_ID' => $this->createField(
				'COMPANY_ID',
				array('type' => 'dest_selector', 'default' => true, 'partial' => true)
			),
			'COMPANY_TITLE' => $this->createField('COMPANY_TITLE'),
			'MYCOMPANY_ID' => $this->createField(
				'MYCOMPANY_ID',
				array('type' => 'dest_selector', 'partial' => true)
			),
			'MYCOMPANY_TITLE' => $this->createField('MYCOMPANY_TITLE'),
			'COMMENTS' => $this->createField('COMMENTS'),
			'DATE_CREATE' => $this->createField(
				'DATE_CREATE',
				array('type' => 'date')
			),
			'DATE_MODIFY' => $this->createField(
				'DATE_MODIFY',
				array('type' => 'date')
			),
			'CREATED_BY_ID' => $this->createField(
				'CREATED_BY_ID',
				array('type' => 'dest_selector', 'partial' => true)
			),
			'MODIFY_BY_ID' => $this->createField(
				'MODIFY_BY_ID',
				array('type' => 'dest_selector', 'partial' => true)
			),
			'PRODUCT_ROW_PRODUCT_ID' => $this->createField(
				'PRODUCT_ROW_PRODUCT_ID',
				array('type' => 'dest_selector', 'partial' => true)
			),
			'ENTITIES_LINKS' => $this->createField(
				'ENTITIES_LINKS',
				array('type' => 'dest_selector', 'partial' => true)
			),
			'WEBFORM_ID' => $this->createField(
				'WEBFORM_ID',
				array('type' => 'list', 'partial' => true)
			),
		);

		Crm\Tracking\UI\Filter::appendFields($result, $this);

		//region UTM
		foreach (Crm\UtmTable::getCodeNames() as $code => $name)
		{
			$result[$code] = $this->createField($code, array('name' => $name));
		}
		//endregion

		return $result;
	}

	/**
	 * Prepare complete field data for specified field.
	 * @param string $fieldID Field ID.
	 * @return array|null
	 */
	public function prepareFieldData($fieldID)
	{

		if($fieldID === 'CURRENCY_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmCurrencyHelper::PrepareListItems()
			);
		}
		elseif($fieldID === 'STATUS_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmStatus::GetStatusList('QUOTE_STATUS')
			);
		}
		elseif($fieldID === 'ASSIGNED_BY_ID')
		{
			return array(
				'params' => array(
					'context' => 'CRM_QUOTE_FILTER_ASSIGNED_BY_ID',
					'multiple' => 'Y',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U',
				)
			);
		}
		elseif($fieldID === 'LEAD_ID')
		{
			return array(
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_QUOTE_FILTER_LEAD_ID',
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
					'enableCrmLeads' => 'Y',
					'convertJson' => 'Y'
				)
			);
		}
		elseif($fieldID === 'DEAL_ID')
		{
			return array(
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_QUOTE_FILTER_DEAL_ID',
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
					'enableCrmDeals' => 'Y',
					'convertJson' => 'Y'
				)
			);
		}
		elseif($fieldID === 'CONTACT_ID')
		{
			return array(
				'alias' => 'ASSOCIATED_CONTACT_ID',
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_QUOTE_FILTER_CONTACT_ID',
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
					'enableCrmContacts' => 'Y',
					'convertJson' => 'Y'
				)
			);
		}
		elseif($fieldID === 'COMPANY_ID')
		{
			return array(
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_QUOTE_FILTER_COMPANY_ID',
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
		elseif($fieldID === 'MYCOMPANY_ID')
		{
			return array(
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_QUOTE_FILTER_MYCOMPANY_ID',
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
		elseif($fieldID === 'ENTITIES_LINKS')
		{
			return array(
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_QUOTE_FILTER_ENTITY',
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
					'enableCrmLeads' => 'Y',
					'addTabCrmCompanies' => 'Y',
					'addTabCrmContacts' => 'Y',
					'addTabCrmDeals' => 'Y',
					'addTabCrmLeads' => 'Y',
					'convertJson' => 'Y'
				)
			);
		}
		elseif($fieldID === 'CREATED_BY_ID')
		{
			return array(
				'params' => array(
					'context' => 'CRM_QUOTE_FILTER_CREATED_BY_ID',
					'multiple' => 'Y',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U',
				)
			);
		}
		elseif($fieldID === 'MODIFY_BY_ID')
		{
			return array(
				'params' => array(
					'context' => 'CRM_QUOTE_FILTER_MODIFY_BY_ID',
					'multiple' => 'Y',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U',
				)
			);
		}
		elseif($fieldID === 'PRODUCT_ROW_PRODUCT_ID')
		{
			return array(
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_QUOTE_FILTER_PRODUCT_ID',
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
					'enableCrmProducts' => 'Y',
					'convertJson' => 'Y'
				)
			);
		}
		elseif(Crm\Tracking\UI\Filter::hasField($fieldID))
		{
			return Crm\Tracking\UI\Filter::getFieldData($fieldID);
		}
		elseif($fieldID === 'WEBFORM_ID')
		{
			return array(
				'params' => array('multiple' => 'N'),
				'items' => Crm\WebForm\Manager::getListNames()
			);
		}
		return null;
	}
}