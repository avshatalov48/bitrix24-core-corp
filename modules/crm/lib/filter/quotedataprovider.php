<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

use Bitrix\Crm;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Loader;

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
		$name = null;
		$factory = Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Quote);
		if ($factory)
		{
			$name = $factory->getFieldCaption((string)$fieldID);
			if ($name === $fieldID)
			{
				$name = null;
			}
		}
		if (empty($name))
		{
			$phrase = "CRM_QUOTE_FILTER_{$fieldID}";
			if ($phrase === 'CRM_QUOTE_FILTER_MYCOMPANY_ID')
			{
				$name = Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Quote)->getFieldCaption(Crm\Item::FIELD_NAME_MYCOMPANY_ID);
			}
			else
			{
				$name = Loc::getMessage($phrase);
			}
		}
		if (empty($name))
		{
			$name = \CCrmQuote::GetFieldCaption($fieldID);
		}

		if (empty($name) && ParentFieldManager::isParentFieldName($fieldID))
		{
			$parentEntityTypeId = ParentFieldManager::getEntityTypeIdFromFieldName($fieldID);
			$name = \CCrmOwnerType::GetDescription($parentEntityTypeId);
		}

		if (empty($name))
		{
			$name = $fieldID;
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
			'QUOTE_NUMBER' => $this->createField(
				'QUOTE_NUMBER',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'TITLE' => $this->createField(
				'TITLE',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'ASSIGNED_BY_ID' => $this->createField(
				'ASSIGNED_BY_ID',
				[
					'type' => 'entity_selector',
					'default' => true,
					'partial' => true,
				]
			),
			'CREATED_BY_ID' => $this->createField(
				'CREATED_BY_ID',
				[
					'type' => 'entity_selector',
					'partial' => true,
				]
			),
			'MODIFY_BY_ID' => $this->createField(
				'MODIFY_BY_ID',
				[
					'type' => 'entity_selector',
					'partial' => true,
				]
			),
			'OPPORTUNITY' => $this->createField(
				'OPPORTUNITY',
				[
					'type' => 'number',
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
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
				[
					'type' => 'date',
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'CLOSEDATE' => $this->createField(
				'CLOSEDATE',
				[
					'type' => 'date',
					'default' => true,
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
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
			'CONTACT_FULL_NAME' => $this->createField(
				'CONTACT_FULL_NAME',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'COMPANY_ID' => $this->createField(
				'COMPANY_ID',
				array('type' => 'dest_selector', 'default' => true, 'partial' => true)
			),
			'COMPANY_TITLE' => $this->createField(
				'COMPANY_TITLE',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'MYCOMPANY_ID' => $this->createField(
				'MYCOMPANY_ID',
				array('type' => 'dest_selector', 'partial' => true)
			),
			'COMMENTS' => $this->createField(
				'COMMENTS',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'DATE_CREATE' => $this->createField(
				'DATE_CREATE',
				[
					'type' => 'date',
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'DATE_MODIFY' => $this->createField(
				'DATE_MODIFY',
				[
					'type' => 'date',
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'PRODUCT_ROW_PRODUCT_ID' => $this->createField(
				'PRODUCT_ROW_PRODUCT_ID',
				[
					'type' => 'entity_selector',
					'partial' => true,
				]
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
			$result[$code] = $this->createField(
				$code,
				[
					'name' => $name,
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			);
		}
		//endregion

		$parentFields = Container::getInstance()->getParentFieldManager()->getParentFieldsOptionsForFilterProvider(
			\CCrmOwnerType::Quote
		);
		foreach ($parentFields as $code => $parentField)
		{
			$result[$code] = $this->createField($code, $parentField);
		}

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
		elseif(in_array($fieldID, ['ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID'], true))
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Quote);
			$referenceClass = ($factory ? $factory->getDataClass() : null);

			return $this->getUserEntitySelectorParams(
				strtolower('crm_quote_filter_' . $fieldID),
				[
					'fieldName' => $fieldID,
					'referenceClass' => $referenceClass,
				]
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
		elseif($fieldID === 'PRODUCT_ROW_PRODUCT_ID')
		{
			return [
				'params' => [
					'multiple' => 'N',
					'dialogOptions' => [
						'height' => 200,
						'context' => 'catalog-products',
						'entities' => [
							Loader::includeModule('iblock')
							&& Loader::includeModule('catalog')
								? [
									'id' => 'product',
									'options' => [
										'iblockId' => \Bitrix\Crm\Product\Catalog::getDefaultId(),
										'basePriceId' => \Bitrix\Crm\Product\Price::getBaseId(),
									],
								]
								: [],
						],
					],
				],
			];
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
		elseif (ParentFieldManager::isParentFieldName($fieldID))
		{
			return Container::getInstance()->getParentFieldManager()->prepareParentFieldDataForFilterProvider(
				\CCrmOwnerType::Quote,
				$fieldID
			);
		}
		return null;
	}
}
