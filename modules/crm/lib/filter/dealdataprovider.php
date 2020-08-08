<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

use Bitrix\Crm;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Report\VisualConstructor\Helper\Analytic;

Loc::loadMessages(__FILE__);

class DealDataProvider extends EntityDataProvider
{
	/** @var DealSettings|null */
	protected $settings = null;

	function __construct(DealSettings $settings)
	{
		$this->settings = $settings;
	}

	/**
	 * Get Settings
	 * @return DealSettings
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * Get Deal Category ID
	 * @return int
	 */
	public function getCategoryID()
	{
		return $this->settings->getCategoryID();
	}

	/**
	 * Get Deal Category Access Data
	 * @return array
	 */
	public function getCategoryAccessData()
	{
		return $this->settings->getCategoryAccessData();
	}

	/**
	 * Get specified entity field caption.
	 * @param string $fieldID Field ID.
	 * @return string
	 */
	protected function getFieldName($fieldID)
	{
		$name = Loc::getMessage("CRM_DEAL_FILTER_{$fieldID}");
		if($name === null)
		{
			$name = \CCrmDeal::GetFieldCaption($fieldID);
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
			'PROBABILITY' => $this->createField(
				'PROBABILITY',
				array('type' => 'number')
			),
			'IS_NEW' => $this->createField(
				'IS_NEW',
				array('type' => 'checkbox')
			),
			'IS_RETURN_CUSTOMER' => $this->createField(
				'IS_RETURN_CUSTOMER',
				array('type' => 'checkbox')
			),
			'IS_REPEATED_APPROACH' => $this->createField(
				'IS_REPEATED_APPROACH',
				array('type' => 'checkbox')
			),
			'SOURCE_ID' => $this->createField(
				'SOURCE_ID',
				array('type' => 'list', 'default' => true, 'partial' => true)
			)
		);

		$result['STAGE_SEMANTIC_ID'] = $this->createField(
			'STAGE_SEMANTIC_ID',
			array('type' => 'list', 'default' => true, 'partial' => true)
		);

		if($this->getCategoryID() >= 0)
		{
			$result['STAGE_ID'] = $this->createField(
			'STAGE_ID',
				array('type' => 'list', 'default' => true, 'partial' => true)
			);
		}
		elseif(\Bitrix\Crm\Category\DealCategory::isCustomized())
		{
			$result['CATEGORY_ID'] = $this->createField(
				'CATEGORY_ID',
				array('type' => 'list', 'default' => true, 'partial' => true)
			);
		}
		else
		{
			$result['STAGE_ID'] = $this->createField(
				'STAGE_ID',
				array('type' => 'list', 'default' => true, 'partial' => true)
			);
		}

		$result['ORDER_STAGE'] = $this->createField(
			'ORDER_STAGE',
			array('type' => 'list', 'default' => false, 'partial' => true)
		);

		$result['BEGINDATE'] = $this->createField(
			'BEGINDATE',
			array('type' => 'date')
		);

		if(!$this->settings->checkFlag(DealSettings::FLAG_RECURRING))
		{
			$result['CLOSEDATE'] = $this->createField(
				'CLOSEDATE',
				array('type' => 'date', 'default' => true)
			);

			$result['CLOSED'] = $this->createField(
				'CLOSED',
				array('type' => 'checkbox')
			);

			$result['ACTIVITY_COUNTER'] = $this->createField(
				'ACTIVITY_COUNTER',
				array('type' => 'list', 'default' => true, 'partial' => true)
			);
		}

		//region OUTDATED EVENT FIELDS
		$result['EVENT_DATE'] = $this->createField(
			'EVENT_DATE',
			array('type' => 'date')
		);

		$result['EVENT_ID'] = $this->createField(
			'EVENT_ID',
			array('type' => 'list', 'partial' => true)
		);
		//endregion

		//endregion

		$result += array(
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
			'COMMENTS' => $this->createField('COMMENTS'),
			'TYPE_ID' => $this->createField(
				'TYPE_ID',
				array('type' => 'list', 'partial' => true)
			),
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
			)
		);

		if(!$this->settings->checkFlag(DealSettings::FLAG_RECURRING))
		{
			$result['PRODUCT_ROW_PRODUCT_ID'] = $this->createField(
				'PRODUCT_ROW_PRODUCT_ID',
				array('type' => 'dest_selector', 'partial' => true)
			);

			$result['ORIGINATOR_ID'] = $this->createField(
				'ORIGINATOR_ID',
				array('type' => 'list', 'partial' => true)
			);

			$result['WEBFORM_ID'] = $this->createField(
				'WEBFORM_ID',
				array('type' => 'list', 'partial' => true)
			);

			Crm\Tracking\UI\Filter::appendFields($result, $this);

			//region UTM
			foreach (Crm\UtmTable::getCodeNames() as $code => $name)
			{
				$result[$code] = $this->createField($code, array('name' => $name));
			}
			//endregion
		}
		else
		{
			$result['CRM_DEAL_RECURRING_ACTIVE'] = $this->createField(
				'CRM_DEAL_RECURRING_ACTIVE',
				array(
					'name' => Loc::getMessage('CRM_DEAL_FILTER_RECURRING_ACTIVE'),
					'default' => true,
					'type' => 'checkbox'
				)
			);
			$result['CRM_DEAL_RECURRING_NEXT_EXECUTION'] = $this->createField(
				'CRM_DEAL_RECURRING_NEXT_EXECUTION',
				array(
					'name' => Loc::getMessage('CRM_DEAL_FILTER_RECURRING_NEXT_EXECUTION'),
					'default' => true,
					'type' => 'date'
				)
			);
			$result['CRM_DEAL_RECURRING_LIMIT_DATE'] = $this->createField(
				'CRM_DEAL_RECURRING_LIMIT_DATE',
				array(
					'name' => Loc::getMessage('CRM_DEAL_FILTER_RECURRING_LIMIT_DATE'),
					'type' => 'date'
				)
			);
			$result['CRM_DEAL_RECURRING_COUNTER_REPEAT'] = $this->createField(
				'CRM_DEAL_RECURRING_COUNTER_REPEAT',
				array(
					'name' => Loc::getMessage('CRM_DEAL_FILTER_RECURRING_COUNTER_REPEAT'),
					'type' => 'number'
				)
			);
		}

		$result['ACTIVE_TIME_PERIOD'] = $this->createField(
			'ACTIVE_TIME_PERIOD',
			array(
				'name' => Loc::getMessage('CRM_DEAL_FILTER_ACTIVE_TIME_PERIOD'),
				'type' => 'date'
			)
		);

		$result['STAGE_ID_FROM_HISTORY'] = $this->createField(
			'STAGE_ID_FROM_HISTORY',
			array('type' => 'list', 'default' => true, 'partial' => true)
		);

		$result['STAGE_ID_FROM_SUPPOSED_HISTORY'] = $this->createField(
			'STAGE_ID_FROM_SUPPOSED_HISTORY',
			array('type' => 'list', 'default' => true, 'partial' => true)
		);

		$result['STAGE_SEMANTIC_ID_FROM_HISTORY'] = $this->createField(
			'STAGE_SEMANTIC_ID_FROM_HISTORY',
			array('type' => 'list', 'default' => true, 'partial' => true)
		);

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

		if($fieldID === 'CURRENCY_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmCurrencyHelper::PrepareListItems()
			);
		}
		elseif($fieldID === 'TYPE_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmStatus::GetStatusList('DEAL_TYPE')
			);
		}
		elseif($fieldID === 'ASSIGNED_BY_ID')
		{
			return array(
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_DEAL_FILTER_ASSIGNED_BY_ID',
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
		elseif($fieldID === 'STAGE_ID' || $fieldID === 'STAGE_ID_FROM_HISTORY' || $fieldID === 'STAGE_ID_FROM_SUPPOSED_HISTORY')
		{
			$categoryID = $this->getCategoryID();
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => DealCategory::getStageList(max($categoryID, 0))
			);
		}
		elseif($fieldID === 'ORDER_STAGE')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => Crm\Order\OrderStage::getList()
			);
		}
		elseif($fieldID === 'STAGE_SEMANTIC_ID' || $fieldID === 'STAGE_SEMANTIC_ID_FROM_HISTORY')
		{
			return PhaseSemantics::getListFilterInfo(
				\CCrmOwnerType::Deal,
				array('params' => array('multiple' => 'Y'))
			);
		}
		elseif($fieldID === 'CATEGORY_ID')
		{
			$categoryAccess = $this->getCategoryAccessData();
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => isset($categoryAccess['READ'])
					? DealCategory::prepareSelectListItems($categoryAccess['READ']) : array()
			);
		}
		elseif($fieldID === 'ACTIVITY_COUNTER')
		{
			return EntityCounterType::getListFilterInfo(
				array('params' => array('multiple' => 'Y')),
				array('ENTITY_TYPE_ID' => \CCrmOwnerType::Deal)
			);
		}
		elseif($fieldID === 'CONTACT_ID')
		{
			return array(
				'alias' => 'ASSOCIATED_CONTACT_ID',
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_DEAL_FILTER_CONTACT_ID',
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
					'context' => 'CRM_DEAL_FILTER_COMPANY_ID',
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
		elseif($fieldID === 'CREATED_BY_ID')
		{
			return array(
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_DEAL_FILTER_CREATED_BY_ID',
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
		elseif($fieldID === 'MODIFY_BY_ID')
		{
			return array(
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_DEAL_FILTER_MODIFY_BY_ID',
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
		elseif($fieldID === 'ORIGINATOR_ID')
		{
			return array(
				'items' => array('' => Loc::getMessage('CRM_DEAL_FILTER_ALL'))
					+ \CCrmExternalSaleHelper::PrepareListItems()
			);
		}
		elseif($fieldID === 'EVENT_ID')
		{
			return array('items' => array('' => '') + \CCrmStatus::GetStatusList('EVENT_TYPE'));
		}
		elseif($fieldID === 'PRODUCT_ROW_PRODUCT_ID')
		{
			return array(
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_DEAL_FILTER_PRODUCT_ID',
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
				'params' => array('multiple' => 'Y'),
				'items' => Crm\WebForm\Manager::getListNames()
			);
		}
		elseif($fieldID === 'SOURCE_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmStatus::GetStatusList('SOURCE')
			);
		}
		return null;
	}

	/**
	 * Prepare field parameter for specified field.
	 * @param array $filter Filter params.
	 * @param string $fieldID Field ID.
	 * @return void
	 */
	public function prepareListFilterParam(array &$filter, $fieldID)
	{
		if($fieldID === 'TITLE'
			|| $fieldID === 'COMMENTS'
		)
		{
			$value = isset($filter[$fieldID]) ? trim($filter[$fieldID]) : '';
			if($value !== '')
			{
				$filter["?{$fieldID}"] = $value;
			}
			unset($filter[$fieldID]);
		}
	}
}