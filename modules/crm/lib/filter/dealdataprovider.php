<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Catalog\Config\State;
use Bitrix\Crm;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;

Loc::loadMessages(__FILE__);

class DealDataProvider extends EntityDataProvider implements FactoryOptionable
{
	use ForceUseFactoryTrait;

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
	protected function getFieldName($fieldID): string
	{
		$name =
			Loc::getMessage("CRM_DEAL_FILTER_{$fieldID}")
			?? Loc::getMessage("CRM_DEAL_FILTER_{$fieldID}_MSGVER_1")
			?? \CCrmDeal::GetFieldCaption($fieldID)
		;

		if (!$name && ParentFieldManager::isParentFieldName($fieldID))
		{
			$parentEntityTypeId = ParentFieldManager::getEntityTypeIdFromFieldName($fieldID);
			$name = \CCrmOwnerType::GetDescription($parentEntityTypeId);
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
			'TITLE' => $this->createField(
				'TITLE',
				[
					'default' => true,
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
			'OPPORTUNITY' => $this->createField(
				'OPPORTUNITY',
				[
					'type' => 'number',
					'default' => true,
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
				[
					'type' => 'list',
					'partial' => true
				]
			),
			'PROBABILITY' => $this->createField(
				'PROBABILITY',
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
			'IS_NEW' => $this->createField(
				'IS_NEW',
				[
					'type' => 'checkbox'
				]
			),
			'IS_RETURN_CUSTOMER' => $this->createField(
				'IS_RETURN_CUSTOMER',
				[
					'type' => 'checkbox'
				]
			),
			'IS_REPEATED_APPROACH' => $this->createField(
				'IS_REPEATED_APPROACH',
				[
					'type' => 'checkbox'
				]
			),
			'SOURCE_ID' => $this->createField(
				'SOURCE_ID',
				[
					'type' => 'list',
					'partial' => true
				]
			),
			'OBSERVER_IDS' => $this->createField(
				'OBSERVER_IDS',
				[
					'type' => 'entity_selector',
					'partial' => true,
				]
			),
		);

		if ($this->isActivityResponsibleEnabled())
		{
			$result['ACTIVITY_RESPONSIBLE_IDS'] = $this->createField(
				'ACTIVITY_RESPONSIBLE_IDS',
				[
					'type' => 'entity_selector',
					'partial' => true,
				]
			);
		}

		if(!$this->settings->checkFlag(DealSettings::FLAG_RECURRING))
		{
			$result['CLOSEDATE'] = $this->createField(
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
			);

			$result['CLOSED'] = $this->createField(
				'CLOSED',
				[
					'type' => 'checkbox'
				]
			);

			$result['ACTIVITY_COUNTER'] = $this->createField(
				'ACTIVITY_COUNTER',
				[
					'type' => 'list',
					'partial' => true
				]
			);
		}

		$result['STAGE_SEMANTIC_ID'] = $this->createField(
			'STAGE_SEMANTIC_ID',
			[
				'type' => 'list',
				'default' => true,
				'partial' => true
			]
		);

		if($this->getCategoryID() >= 0)
		{
			$result['STAGE_ID'] = $this->createField(
				'STAGE_ID',
				[
					'type' => 'list',
					'partial' => true
				]
			);
		}
		elseif(\Bitrix\Crm\Category\DealCategory::isCustomized())
		{
			$result['CATEGORY_ID'] = $this->createField(
				'CATEGORY_ID',
				[
					'type' => 'list',
					'partial' => true
				]
			);
		}
		else
		{
			$result['STAGE_ID'] = $this->createField(
				'STAGE_ID',
				[
					'type' => 'list',
					'partial' => true
				]
			);
		}

		$result['DELIVERY_STAGE'] = $this->createField(
			'DELIVERY_STAGE',
			[
				'type' => 'list',
				'partial' => true
			]
		);

		$result['PAYMENT_STAGE'] = $this->createField(
			'PAYMENT_STAGE',
			[
				'type' => 'list',
				'partial' => true
			]
		);

		$result['PAYMENT_PAID'] = $this->createField(
			'PAYMENT_PAID',
			[
				'type' => 'date',
				'data' => [
					'additionalFilter' => [
						'isEmpty',
						'hasAnyValue',
					],
				],
			]
		);

		$result['BEGINDATE'] = $this->createField(
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
		);

		//region OUTDATED EVENT FIELDS
		$result['EVENT_DATE'] = $this->createField(
			'EVENT_DATE',
			[
				'type' => 'date',
				'data' => [
					'additionalFilter' => [
						'isEmpty',
						'hasAnyValue',
					],
				],
			]
		);

		$result['EVENT_ID'] = $this->createField(
			'EVENT_ID',
			[
				'type' => 'list',
				'partial' => true
			]
		);
		//endregion

		//endregion

		$result += [
			'CONTACT_ID' => $this->createField(
				'CONTACT_ID',
				[
					'type' => 'dest_selector',
					'partial' => true
				]
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
				[
					'type' => 'dest_selector',
					'partial' => true
				]
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
			'TYPE_ID' => $this->createField(
				'TYPE_ID',
				[
					'type' => 'list',
					'partial' => true
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
			)
		];

		if(!$this->settings->checkFlag(DealSettings::FLAG_RECURRING))
		{
			$result['PRODUCT_ROW_PRODUCT_ID'] = $this->createField(
				'PRODUCT_ROW_PRODUCT_ID',
				[
					'type' => 'entity_selector',
					'partial' => true,
				]
			);

			if (Loader::includeModule('catalog') && State::isUsedInventoryManagement() && !\CCrmSaleHelper::isWithOrdersMode())
			{
				$result['IS_PRODUCT_RESERVED'] = $this->createField(
					'IS_PRODUCT_RESERVED',
					[
						'type' => 'checkbox'
					]
				);
			}

			$result['ORIGINATOR_ID'] = $this->createField(
				'ORIGINATOR_ID',
				[
					'type' => 'list',
					'partial' => true
				]
			);

			$result['WEBFORM_ID'] = $this->createField(
				'WEBFORM_ID',
				[
					'type' => 'entity_selector',
					'partial' => true
				]
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
		}
		else
		{
			$result['CRM_DEAL_RECURRING_ACTIVE'] = $this->createField(
				'CRM_DEAL_RECURRING_ACTIVE',
				[
					'name' => Loc::getMessage('CRM_DEAL_FILTER_RECURRING_ACTIVE'),
					'type' => 'checkbox'
				]
			);
			$result['CRM_DEAL_RECURRING_NEXT_EXECUTION'] = $this->createField(
				'CRM_DEAL_RECURRING_NEXT_EXECUTION',
				[
					'name' => Loc::getMessage('CRM_DEAL_FILTER_RECURRING_NEXT_EXECUTION'),
					'type' => 'date'
				]
			);
			$result['CRM_DEAL_RECURRING_LIMIT_DATE'] = $this->createField(
				'CRM_DEAL_RECURRING_LIMIT_DATE',
				[
					'type' => 'date',
					'name' => Loc::getMessage('CRM_DEAL_FILTER_RECURRING_LIMIT_DATE'),
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			);
			$result['CRM_DEAL_RECURRING_COUNTER_REPEAT'] = $this->createField(
				'CRM_DEAL_RECURRING_COUNTER_REPEAT',
				[
					'name' => Loc::getMessage('CRM_DEAL_FILTER_RECURRING_COUNTER_REPEAT'),
					'type' => 'number',
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			);
		}

		$result['ACTIVE_TIME_PERIOD'] = $this->createField(
			'ACTIVE_TIME_PERIOD',
			[
				'type' => 'date',
				'name' => Loc::getMessage('CRM_DEAL_FILTER_ACTIVE_TIME_PERIOD'),
				'data' => [
					'additionalFilter' => [
						'isEmpty',
						'hasAnyValue',
					],
				],
			]
		);

		$result['STAGE_ID_FROM_HISTORY'] = $this->createField(
			'STAGE_ID_FROM_HISTORY',
			[
				'type' => 'list',
				'partial' => true
			]
		);

		$result['STAGE_ID_FROM_SUPPOSED_HISTORY'] = $this->createField(
			'STAGE_ID_FROM_SUPPOSED_HISTORY',
			[
				'type' => 'list',
				'partial' => true
			]
		);

		$result['STAGE_SEMANTIC_ID_FROM_HISTORY'] = $this->createField(
			'STAGE_SEMANTIC_ID_FROM_HISTORY',
			[
				'type' => 'list',
				'partial' => true
			]
		);

		$result['ORDER_SOURCE'] = $this->createField(
			'ORDER_SOURCE',
			[
				'type' => 'list',
				'partial' => true
			]
		);

		$result['ROBOT_DEBUGGER'] = $this->createField(
			'ROBOT_DEBUGGER',
			[
				'type' => 'list',
				'partial' => true,
			]
		);

		$parentFields = Container::getInstance()->getParentFieldManager()->getParentFieldsOptionsForFilterProvider(
			\CCrmOwnerType::Deal
		);
		foreach ($parentFields as $code => $parentField)
		{
			$result[$code] = $this->createField($code, $parentField);
		}

		$factory = Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
		if ($factory && $factory->isLastActivityEnabled())
		{
			$result['LAST_ACTIVITY_TIME'] = $this->createField(
				'LAST_ACTIVITY_TIME',
				[
					'type' => 'date',
					'partial' => true,
				]
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
		elseif(in_array($fieldID, ['ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID', 'OBSERVER_IDS', 'ACTIVITY_RESPONSIBLE_IDS'], true))
		{
			$factory = Container::getInstance()->getFactory(\CCrmOwnerType::Deal);


			$isEnableAllUsers = in_array($fieldID, ['ASSIGNED_BY_ID', 'ACTIVITY_RESPONSIBLE_IDS'], true);
			$isEnableOtherUsers = in_array($fieldID, ['ASSIGNED_BY_ID', 'ACTIVITY_RESPONSIBLE_IDS'], true);

			if (in_array($fieldID, ['ACTIVITY_RESPONSIBLE_IDS', 'OBSERVER_IDS'], true))
			{
				$referenceClass = null;
			}
			else
			{
				$referenceClass = ($factory ? $factory->getDataClass() : null);
			}

			return $this->getUserEntitySelectorParams(
				EntitySelector::CONTEXT,
				[
					'fieldName' => $fieldID,
					'referenceClass' => $referenceClass,
					'isEnableAllUsers' => $isEnableAllUsers,
					'isEnableOtherUsers' => $isEnableOtherUsers,
				]
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
		elseif($fieldID === 'DELIVERY_STAGE')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => Crm\Order\DeliveryStage::getList()
			);
		}
		elseif($fieldID === 'PAYMENT_STAGE')
		{
			return array(
				'params' => ['multiple' => 'Y'],
				'items' => Crm\Workflow\PaymentStage::getMessages()
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
					'context' => EntitySelector::CONTEXT,
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
					'context' => EntitySelector::CONTEXT,
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
			return Crm\WebForm\Helper::getEntitySelectorParams(\CCrmOwnerType::Deal);
		}
		elseif($fieldID === 'SOURCE_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmStatus::GetStatusList('SOURCE')
			);
		}
		elseif($fieldID === 'ORDER_SOURCE')
		{
			$orderSourceItems = [];
			$tradingPlatformIterator = Sale\TradingPlatform\Manager::getList([
				'select' => ['ID', 'NAME'],
			]);
			while ($tradingPlatformData = $tradingPlatformIterator->fetch())
			{
				$orderSourceItems[$tradingPlatformData['ID']]
					= "{$tradingPlatformData['NAME']} [{$tradingPlatformData['ID']}]"
				;
			}

			return array(
				'params' => ['multiple' => 'Y'],
				'items' => $orderSourceItems,
			);
		}
		elseif ($fieldID === 'ROBOT_DEBUGGER')
		{
			return [
				'params' => [
					'multiple' => 'N',
				],
				'items' => \Bitrix\Crm\Automation\Debugger\DebuggerFilter::getFilterItems(),
			];
		}
		elseif (ParentFieldManager::isParentFieldName($fieldID))
		{
			return Container::getInstance()->getParentFieldManager()->prepareParentFieldDataForFilterProvider(
				\CCrmOwnerType::Deal,
				$fieldID
			);
		}

		return null;
	}

	protected function applySettingsDependantFilter(array &$filterFields): void
	{
		$filterFields['=IS_RECURRING'] = $this->getSettings()->checkFlag(DealSettings::FLAG_RECURRING) ? 'Y' : 'N';

		$categoryId = $this->getSettings()->getCategoryID();
		if ($categoryId >= 0)
		{
			$filterFields['@CATEGORY_ID'] = $categoryId;
		}
	}

	protected function getCounterExtras(): array
	{
		$settings = $this->getSettings();

		$result = parent::getCounterExtras();
		if ($settings->checkFlag(\Bitrix\Crm\Filter\DealSettings::FLAG_RECURRING))
		{
			$result['IS_RECURRING'] = 'Y';
		}
		$categoryId = $settings->getCategoryID();
		if ($categoryId >= 0)
		{
			$result['CATEGORY_ID'] = $categoryId;
		}

		return $result;
	}
}
