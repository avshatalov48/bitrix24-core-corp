<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);
Main\Loader::includeModule('sale');

class OrderDataProvider extends EntityDataProvider implements FactoryOptionable
{
	use ForceUseFactoryTrait;

	/** @var InvoiceSettings|null */
	protected $settings = null;

	function __construct(OrderSettings $settings)
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
		$name = Loc::getMessage("CRM_ORDER_FILTER_{$fieldID}");
		if($name === null)
		{
//			$name = \CCrmInvoice::GetFieldCaption($fieldID);
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
			'ACCOUNT_NUMBER' => $this->createField(
				'ACCOUNT_NUMBER',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'ORDER_TOPIC' => $this->createField(
				'ORDER_TOPIC',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'PRICE' => $this->createField(
				'PRICE',
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
			'DATE_INSERT' => $this->createField(
				'DATE_INSERT',
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
			'DATE_UPDATE' => $this->createField(
				'DATE_UPDATE',
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
			'DEDUCTED' => $this->createField('DEDUCTED', array('type' => 'checkbox')),
			'PAYED' => $this->createField('PAYED', array('type' => 'checkbox')),
			'CANCELED' => $this->createField('CANCELED', array('type' => 'checkbox')),
			'USER' => $this->createField(
				'USER',
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
			'CREATED_BY' => $this->createField(
				'CREATED_BY',
				array('type' => 'custom_entity', 'partial' => true)
			),
			'RESPONSIBLE_ID' => $this->createField(
				'RESPONSIBLE_ID',
				array('type' => 'dest_selector', 'default' => true, 'partial' => true)
			),
			'STATUS_ID' => $this->createField(
				'STATUS_ID',
				array('default' => true, 'type' => 'list', 'partial' => true)
			),
			'PERSON_TYPE_ID' => $this->createField(
				'PERSON_TYPE_ID',
				array('type' => 'list', 'partial' => true)
			),
			'CURRENCY' => $this->createField(
				'CURRENCY',
				array('type' => 'list', 'partial' => true)
			),
			'CONTACT_ID' => $this->createField(
				'CONTACT_ID',
				array('type' => 'dest_selector', 'partial' => true)
			),
			'COMPANY_ID' => $this->createField(
				'COMPANY_ID',
				array('type' => 'dest_selector', 'partial' => true)
			),
			'ACTIVITY_COUNTER' => $this->createField(
				'ACTIVITY_COUNTER',
				array('type' => 'list', 'default' => true, 'partial' => true)
			),
			'SOURCE_ID' => $this->createField(
				'SOURCE_ID',
				array('type' => 'list', 'default' => true, 'partial' => true)
			),
			'PAY_SYSTEM' => $this->createField(
				'PAY_SYSTEM',
				array('type' => 'list', 'default' => true, 'partial' => true)
			),
			'DELIVERY_SERVICE' => $this->createField(
				'DELIVERY_SERVICE',
				array('type' => 'list', 'default' => true, 'partial' => true)
			),
			'COUPON' => $this->createField(
				'COUPON',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'SHIPMENT_TRACKING_NUMBER' => $this->createField(
				'SHIPMENT_TRACKING_NUMBER',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'SHIPMENT_DELIVERY_DOC_DATE' => $this->createField(
				'SHIPMENT_DELIVERY_DOC_DATE',
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
			'CHECK_PRINTED' => $this->createField(
				'CHECK_PRINTED',
				['type' => 'checkbox']
			),
			'HAS_ASSOCIATED_DEAL' => $this->createField('HAS_ASSOCIATED_DEAL', ['type' => 'checkbox']),
			'XML_ID' => $this->createField(
				'XML_ID',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
		);

		$factory = Container::getInstance()->getFactory(\CCrmOwnerType::Order);
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

		Tracking\UI\Filter::appendFields($result, $this);

		$result = array_merge($result, $this->getPropertyFields());

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
					'context' => 'CRM_ORDER_FILTER_RESPONSIBLE_ID',
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
		elseif($fieldID == 'ACTIVITY_RESPONSIBLE_IDS')
		{
			return $this->getUserEntitySelectorParams(
			EntitySelector::CONTEXT,
				[
					'fieldName' => $fieldID,
					'referenceClass' => null,
					'isEnableAllUsers' => true,
					'isEnableOtherUsers' => true,
				]
			);
		}
		elseif ($fieldID === 'CREATED_BY')
		{
			return array(
				'selector' => array(
					'TYPE' => 'client',
					'DATA' => array('ID' => 'created_by', 'FIELD_ID' => 'CREATED_BY')
				)
			);
		}
		else if ($fieldID === 'STATUS_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' =>  \Bitrix\Crm\Order\OrderStatus::getListInCrmFormat()
			);
		}
		else if ($fieldID === 'CURRENCY')
		{
			return array(
				'params' => array('multiple' => 'N'),
				'items' =>  \CCrmCurrencyHelper::PrepareListItems()
			);
		}
		else if ($fieldID === 'PERSON_TYPE_ID')
		{
			return array(
				'params' => array('multiple' => 'N'),
				'items' =>  $this->getPersonTypes()
			);
		}
		elseif($fieldID === 'CONTACT_ID')
		{
			return array(
				'alias' => 'ASSOCIATED_CONTACT_ID',
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_ORDER_FILTER_CONTACT_ID',
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
					'context' => 'CRM_ORDER_FILTER_COMPANY_ID',
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
		elseif($fieldID === 'ACTIVITY_COUNTER')
		{
			return EntityCounterType::getListFilterInfo(
				array('params' => array('multiple' => 'Y')),
				array('ENTITY_TYPE_ID' => \CCrmOwnerType::Order)
			);
		}
		elseif($fieldID === 'SOURCE_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => $this->getSources()
			);
		}
		elseif($fieldID === 'PAY_SYSTEM')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => $this->getPaySystems()
			);
		}
		elseif($fieldID === 'DELIVERY_SERVICE')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => $this->getDeliveryServices()
			);
		}
		elseif (preg_match("/^PROPERTY_/", $fieldID))
		{
			return $this->getPropertyListData($fieldID);
		}
		elseif(Tracking\UI\Filter::hasField($fieldID))
		{
			return Tracking\UI\Filter::getFieldData($fieldID);
		}

		return null;
	}

	private function getPersonTypes()
	{
		static $personTypes = null;
		if (empty($personTypes))
		{
			$personTypes = \Bitrix\Crm\Order\PersonType::load(SITE_ID);
		}
		return $personTypes;
	}

	/**
	 * Get landings for filter
	 * @return array
	 */
	private function getSources()
	{
		if (Main\Loader::includeModule('sale'))
		{
			return Sale\TradingPlatform\Manager::getActivePlatformList();
		}

		return [];
	}

	/**
	 * @return array
	 */
	private function getProperties()
	{
		static $properties = [];
		if (empty($properties))
		{
			$propertiesRaw = \Bitrix\Crm\Order\Property::getList(
				array(
					'filter' => array(
						'=ACTIVE' => 'Y',
						'=IS_FILTERED' => 'Y',
						'=TYPE' => ['STRING', 'NUMBER', 'Y/N', 'ENUM', 'DATE']
					),
					'order' => array(
						"PERSON_TYPE_ID" => "ASC", "SORT" => "ASC"
					),
					'select' => array(
						"ID", "NAME", "PERSON_TYPE_NAME" => "PERSON_TYPE.NAME", "LID" => "PERSON_TYPE.LID", "PERSON_TYPE_ID", "SORT", "IS_FILTERED", "TYPE", "CODE", "SETTINGS"
					),
				)
			);

			while ($property = $propertiesRaw->fetch())
			{
				$properties['PROPERTY_'.$property['ID']] = $property;
			}
		}

		return $properties;
	}

	/**
	 * @return array
	 */
	private function getPropertyFields()
	{
		$result = [];
		foreach ($this->getProperties() as $key => $property)
		{
			if ($property['TYPE'] === 'Y/N')
			{
				$type = 'checkbox';
			}
			elseif ($property['TYPE'] === 'ENUM')
			{
				$type = 'list';
			}
			elseif ($property['TYPE'] === 'DATE')
			{
				$type = 'string';
			}
			else
			{
				$type = mb_strtolower($property['TYPE']);
			}

			$name = htmlspecialcharsbx("{$property['NAME']} ({$property['PERSON_TYPE_NAME']}) [{$property['LID']}]");

			$result[$key] = $this->createField(
				$key,
				[
					'type' => $type,
					'default' => false,
					'name' => $name,
					'partial' => true
				]
			);
		}

		return $result;
	}

	/**
	 * @param $code
	 */
	private function getPropertyListData($code)
	{
		static $propertyValues = [];
		$properties = $this->getProperties();
		if (!isset($properties[$code]) || $properties[$code]['TYPE'] !== 'ENUM')
		{
			return null;
		}

		if (empty($propertyValues))
		{
			$propertyIds = [];
			/** @var \Bitrix\Crm\Filter\Field $property */
			foreach ($properties as $property)
			{
				if ($property['TYPE'] !== 'ENUM')
				{
					continue;
				}
				$propertyIds[] = (int)$property['ID'];
			}

			if (empty($propertyIds))
			{
				return [];
			}

			$result = \Bitrix\Crm\Order\PropertyVariant::getList([
				'filter' => ['=ORDER_PROPS_ID' => $propertyIds],
				'order' => ['SORT' => 'ASC']
			]);
			while ($row = $result->fetch())
			{
				$propertyCode = 'PROPERTY_'.$row['ORDER_PROPS_ID'];
				$propertyValues[$propertyCode][$row['VALUE']] = htmlspecialcharsbx($row['NAME']);
			}
		}

		return [
			'params' => [
				'multiple' => ($properties[$code]['MULTIPLE'] === 'Y') ? 'Y' : 'N'
			],
			'items' => $propertyValues[$code]
		];
	}

	/**
	 * @return array
	 */
	private function getPaySystems()
	{
		$result = [];
		$personTypes = $this->getPersonTypes();

		$res = Sale\PaySystem\Manager::getList(array(
			'select' => array('ID', 'NAME'),
			'filter' => array(
				'ACTIVE' => 'Y',
				'=ENTITY_REGISTRY_TYPE' => Sale\Registry::REGISTRY_TYPE_ORDER
			),
			'order' => array("SORT"=>"ASC", "NAME"=>"ASC")
		));

		$paySystemList = [];
		while ($paySystem = $res->fetch())
		{
			$paySystemList[$paySystem['ID']]['NAME'] = $paySystem['NAME'];
		}

		if (!empty($paySystemList))
		{
			$restrictionsRaw = Sale\Services\PaySystem\Restrictions\Manager::getList(array(
				'select' => array('SERVICE_ID', 'PARAMS'),
				'filter' => array(
					'=CLASS_NAME' => '\\'.Sale\Services\PaySystem\Restrictions\PersonType::class,
					'SERVICE_ID' => array_keys($paySystemList)
				)
			));

			while ($restriction = $restrictionsRaw->fetch())
			{
				$paySystemList[$restriction['SERVICE_ID']]['PERSON_TYPE_ID'] = $restriction['PARAMS']['PERSON_TYPE_ID'];
			}

			foreach ($paySystemList as $paySystemId => $paySystem)
			{
				$itemName = "[{$paySystemId}] ".$paySystem['NAME'];
				if (!empty($paySystem['PERSON_TYPE_ID']))
				{
					$restrictedTypes = is_array($paySystem['PERSON_TYPE_ID']) ? $paySystem['PERSON_TYPE_ID'] : [$paySystem['PERSON_TYPE_ID']];
					$paySystemPersonTypes = [];
					foreach ($restrictedTypes as $typeId)
					{
						$paySystemPersonTypes[] = $personTypes[$typeId]['NAME']."/".$personTypes[$typeId]["LID"];
					}
					if (!empty($paySystemPersonTypes))
					{
						$itemName .= ' ('.join(', ', $paySystemPersonTypes).')';
					}
				}

				$result[$paySystemId] = $itemName;
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getDeliveryServices()
	{
		Sale\Delivery\Services\Manager::getHandlersList();
		$deliveryServiceParentListParent = array();
		$deliveryServiceListAll = array();
		$deliveryServiceList = array();

		$res = Sale\Delivery\Services\Table::getList(array(
			'select' => array('ID', 'NAME', 'PARENT_ID', 'CLASS_NAME', 'PARENT_NAME' => 'PARENT.NAME'),
			'filter' => array('ACTIVE' => 'Y'),
			'order' => array("SORT"=>"ASC", "NAME"=>"ASC")
		));

		while ($deliveryService = $res->fetch())
		{
			if(intval($deliveryService['PARENT_ID']) == 0)
			{
				$deliveryServiceParentListParent[$deliveryService['ID']] = $deliveryService['NAME'];
			}
			elseif(class_exists($deliveryService['CLASS_NAME']) && $deliveryService['CLASS_NAME']::canHasProfiles())
			{
				$deliveryServiceParentListParent[$deliveryService['ID']] = $deliveryService['PARENT_NAME'].':'.$deliveryService['NAME'];
			}
			else
			{
				$deliveryServiceListAll[$deliveryService['PARENT_ID']][$deliveryService['ID']] = $deliveryService['NAME'];
			}
		}

		foreach($deliveryServiceParentListParent as $deliveryServiceParentId => $deliveryServiceParentName)
		{
			if (!empty($deliveryServiceListAll[$deliveryServiceParentId]))
			{
				foreach($deliveryServiceListAll[$deliveryServiceParentId] as $deliveryServiceId => $deliveryServiceName)
				{
					$deliveryServiceList[$deliveryServiceId] = $deliveryServiceParentName.":".$deliveryServiceName;
				}
			}
			else
			{
				$deliveryServiceList[$deliveryServiceParentId] = $deliveryServiceParentName;
			}

		}
		$result = [];
		if (!empty($deliveryServiceList))
		{
			foreach ($deliveryServiceList as $deliveryServiceId => $deliveryServiceName)
			{
				$result[$deliveryServiceId] = "[{$deliveryServiceId}] {$deliveryServiceName}";
			}
		}
		return $result;
	}
}
