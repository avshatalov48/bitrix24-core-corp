<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

use Bitrix\Crm;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\PhaseSemantics;

Loc::loadMessages(__FILE__);

class QuoteDataProvider extends EntityDataProvider
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
				array('type' => 'custom_entity', 'default' => true, 'partial' => true)
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
				array('type' => 'custom_entity', 'partial' => true)
			),
			'DEAL_ID' => $this->createField(
				'DEAL_ID',
				array('type' => 'custom_entity', 'partial' => true)
			),
			'CONTACT_ID' => $this->createField(
				'CONTACT_ID',
				array('type' => 'custom_entity', 'default' => true, 'partial' => true)
			),
			'CONTACT_FULL_NAME' => $this->createField('CONTACT_FULL_NAME'),
			'COMPANY_ID' => $this->createField(
				'COMPANY_ID',
				array('type' => 'custom_entity', 'default' => true, 'partial' => true)
			),
			'COMPANY_TITLE' => $this->createField('COMPANY_TITLE'),
			'MYCOMPANY_ID' => $this->createField(
				'MYCOMPANY_ID',
				array('type' => 'custom_entity', 'partial' => true)
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
				array('type' => 'custom_entity', 'partial' => true)
			),
			'MODIFY_BY_ID' => $this->createField(
				'MODIFY_BY_ID',
				array('type' => 'custom_entity', 'partial' => true)
			),
			'PRODUCT_ROW_PRODUCT_ID' => $this->createField(
				'PRODUCT_ROW_PRODUCT_ID',
				array('type' => 'custom_entity', 'partial' => true)
			),
			'ENTITIES_LINKS' => $this->createField(
				'ENTITIES_LINKS',
				array('type' => 'custom_entity', 'partial' => true)
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
				'params' => array('multiple' => 'Y'),
				'selector' => array(
					'TYPE' => 'user',
					'DATA' => array('ID' => 'assigned_by', 'FIELD_ID' => 'ASSIGNED_BY_ID')
				)
			);
		}
		elseif($fieldID === 'LEAD_ID')
		{
			return array(
				'selector' => array(
					'TYPE' => 'crm_entity',
					'DATA' => array(
						'ID' => 'lead',
						'FIELD_ID' => 'LEAD_ID',
						'ENTITY_TYPE_NAMES' => array(\CCrmOwnerType::LeadName),
						'IS_MULTIPLE' => false
					)
				)
			);
		}
		elseif($fieldID === 'DEAL_ID')
		{
			return array(
				'selector' => array(
					'TYPE' => 'crm_entity',
					'DATA' => array(
						'ID' => 'deal',
						'FIELD_ID' => 'DEAL_ID',
						'ENTITY_TYPE_NAMES' => array(\CCrmOwnerType::DealName),
						'IS_MULTIPLE' => false
					)
				)
			);
		}
		elseif($fieldID === 'CONTACT_ID')
		{
			return array(
				'selector' => array(
					'TYPE' => 'crm_entity',
					'DATA' => array(
						'ID' => 'contact',
						'FIELD_ID' => 'CONTACT_ID',
						'FIELD_ALIAS' => 'ASSOCIATED_CONTACT_ID',
						'ENTITY_TYPE_NAMES' => array(\CCrmOwnerType::ContactName)
					)
				)
			);
		}
		elseif($fieldID === 'COMPANY_ID')
		{
			return array(
				'selector' => array(
					'TYPE' => 'crm_entity',
					'DATA' => array(
						'ID' => 'company',
						'FIELD_ID' => 'COMPANY_ID',
						'ENTITY_TYPE_NAMES' => array(\CCrmOwnerType::CompanyName)
					)
				)
			);
		}
		elseif($fieldID === 'MYCOMPANY_ID')
		{
			return array(
				'selector' => array(
					'TYPE' => 'crm_entity',
					'DATA' => array(
						'ID' => 'my_company',
						'FIELD_ID' => 'MYCOMPANY_ID',
						'ENTITY_TYPE_NAMES' => array(\CCrmOwnerType::CompanyName),
						'IS_MULTIPLE' => false
					)
				)
			);
		}
		elseif($fieldID === 'ENTITIES_LINKS')
		{
			return array(
				'selector' => array(
					'TYPE' => 'crm_entity',
					'DATA' => array(
						'ID' => 'entities_links',
						'FIELD_ID' => 'ENTITIES_LINKS',
						'ENTITY_TYPE_NAMES' => array(
							\CCrmOwnerType::LeadName,
							\CCrmOwnerType::DealName,
							\CCrmOwnerType::CompanyName,
							\CCrmOwnerType::ContactName
						),
						'IS_MULTIPLE' => false
					)
				)
			);
		}
		elseif($fieldID === 'CREATED_BY_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'selector' => array(
					'TYPE' => 'user',
					'DATA' => array('ID' => 'created_by', 'FIELD_ID' => 'CREATED_BY_ID')
				)
			);
		}
		elseif($fieldID === 'MODIFY_BY_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'selector' => array(
					'TYPE' => 'user',
					'DATA' => array('ID' => 'modify_by', 'FIELD_ID' => 'MODIFY_BY_ID')
				)
			);
		}
		elseif($fieldID === 'PRODUCT_ROW_PRODUCT_ID')
		{
			return array(
				'params' => array('multiple' => 'N'),
				'selector' => array(
					'TYPE' => 'crm_entity',
					'DATA' => array(
						'ID' => 'product',
						'FIELD_ID' => 'PRODUCT_ROW_PRODUCT_ID',
						'ENTITY_TYPE_NAMES' => array('PRODUCT'),
						'IS_MULTIPLE' => false
					)
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