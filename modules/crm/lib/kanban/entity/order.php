<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Binding\OrderContactCompanyTable;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\Kanban\Exception;
use Bitrix\Crm\Order\EntityMarker;
use Bitrix\Crm\Order\OrderStatus;
use Bitrix\Crm\Order\Payment;
use Bitrix\Crm\Order\PersonType;
use Bitrix\Crm\Order\PropertyValue;
use Bitrix\Crm\Order\PropertyVariant;
use Bitrix\Crm\Order\Shipment;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Display\Field;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\UI\Filter\FieldAdapter;

Loc::loadMessages(__FILE__);

class Order extends Entity
{
	protected $loadedOrders = [];

	public function __construct()
	{
		parent::__construct();
		Loader::includeModule('sale');
	}

	public function getTypeName(): string
	{
		return \CCrmOwnerType::OrderName;
	}

	public function getItemsSelectPreset(): array
	{
		return ['ID', 'ACCOUNT_NUMBER', 'STATUS_ID', 'DATE_INSERT', 'PAY_VOUCHER_DATE', 'DATE_PAYED', 'ORDER_TOPIC', 'PRICE', 'CURRENCY', 'RESPONSIBLE_ID'];
	}

	public function getStatusEntityId(): string
	{
		return OrderStatus::NAME;
	}

	public function getStagesList(): array
	{
		$orderStatuses = OrderStatus::getListInCrmFormat(true);
		foreach($orderStatuses as &$status)
		{
			$status['SEMANTICS'] = OrderStatus::getSemanticID($status['STATUS_ID']);
		}

		return array_merge(parent::getStagesList(), $orderStatuses);
	}

	public function getFilterPresets(): array
	{
		$user = $this->getCurrentUserInfo();

		return [
			'filter_in_work' => [
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_ORDER_PRESET_MY_WORK'),
				'default' => true,
				'fields' => ['STATUS_ID' => OrderStatus::getSemanticProcessStatuses()],
			],
			'filter_my' => [
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_ORDER_PRESET_MY'),
				'fields' => [
					'RESPONSIBLE_ID_name' => $user['name'],
					'RESPONSIBLE_ID' => $user['id'],
					'STATUS_ID' => OrderStatus::getSemanticProcessStatuses(),
				],
			],
			'filter_won' => [
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_ORDER_PRESET_WON'),
				'fields' => ['STATUS_ID' =>  [OrderStatus::getFinalStatus()]]
			],
		];
	}

	protected function getDefaultAdditionalSelectFields(): array
	{
		return [
			'TITLE' => '',
			'PRICE' => '',
			'DATE_INSERT' => '',
			'CLIENT' => '',
			'ORDER_STAGE' => '',
		];
	}

	public function getAdditionalEditFields(): array
	{
		return (array)$this->getAdditionalEditFieldsFromOptions();
	}

	public function isCustomPriceFieldsSupported(): bool
	{
		return false;
	}

	public function isInlineEditorSupported(): bool
	{
		return false;
	}

	protected function getDetailComponentName(): ?string
	{
		return 'bitrix:crm.order.details';
	}

	protected function getDetailComponent(): ?\CBitrixComponent
	{
		/** @var \CCrmOrderDetailsComponent $component */
		$component = parent::getDetailComponent();
		if($component)
		{
			$component->obtainOrder();
		}

		return $component;
	}

	protected function getInlineEditorConfiguration(\CBitrixComponent $component): array
	{
		/** @var \CCrmOrderDetailsComponent $component */
		return $component->prepareKanbanConfiguration();
	}

	protected function getTotalSumFieldName(): string
	{
		return 'SUM';
	}

	public function getAssignedByFieldName(): string
	{
		return 'RESPONSIBLE_ID';
	}

	public function hasOpenedField(): bool
	{
		return false;
	}

	public function isStageEmpty(string $stageId): bool
	{
		$list = \Bitrix\Crm\Order\Order::getList([
			'filter' => [$this->getStageFieldName() => $stageId],
			'limit' => 1,
		]);

		return !$list->fetch();
	}

	protected function getDataToCalculateTotalSums(string $fieldSum, array $filter, array $runtime): array
	{
		if (!$this->checkReadPermissions())
		{
			return [];
		}

		$queryParameters = [
			'filter' => $filter,
			'select' => [
				$this->getStageFieldName(),
				new ExpressionField($fieldSum, 'SUM(%s)', 'PRICE'),
				new ExpressionField('CNT', 'COUNT(1)'),
			]
		];
		if (!empty($runtime))
		{
			$queryParameters['runtime'] = $runtime;
		}
		$data = [];
		$res = \Bitrix\Crm\Order\Order::getList($queryParameters);
		while ($row = $res->fetch())
		{
			$data[] = $row;
		}

		return $data;
	}

	public function getItemsSelect(array $additionalFields): array
	{
		if(!empty($additionalFields))
		{
			if (in_array('USER', $additionalFields, true))
			{
				$additionalFields[] = 'USER_ID';
			}

			$ufSelect = preg_grep( "/^UF_/", $additionalFields);
			$additionalFields = array_intersect($additionalFields,  \Bitrix\Crm\Order\Order::getAllFields());
			if (!empty($ufSelect))
			{
				global $USER_FIELD_MANAGER;
				$crmUserType = new \CCrmUserType($USER_FIELD_MANAGER, \Bitrix\Crm\Order\Order::getUfId());
				$userFields = $crmUserType->GetFields();
				if (is_array($userFields))
				{
					foreach ($ufSelect as $userFieldName)
					{
						if (isset($userFields[$userFieldName]))
						{
							$additionalFields[] = $userFieldName;
						}
					}
				}
			}

			if (in_array('SOURCE_ID', $additionalFields, true))
			{
				$additionalFields['SOURCE_ID'] = 'TRADING_PLATFORM.TRADING_PLATFORM.NAME';
			}
		}

		return parent::getItemsSelect($additionalFields);
	}

	public function getItems(array $parameters): \CDBResult
	{
		$items = [];

		$list = \Bitrix\Crm\Order\Order::getList($parameters);
		while($item = $list->fetch())
		{
			$items[$item['ID']] = $item;
		}
		$items = $this->prepareEntityFields($items);

		$result = new \CDBResult();
		$result->InitFromArray($items);

		return $result;
	}

	protected function prepareEntityFields(array $orders): array
	{
		static $currencies = [];
		static $personTypes = [];

		if(empty($orders))
		{
			return $orders;
		}
		$ids = array_keys($orders);
		if (empty($currencies))
		{
			$currencies = \CCrmCurrencyHelper::PrepareListItems();
		}
		if (empty($personTypes))
		{
			$personTypes = PersonType::load(SITE_ID);
		}
		$orderClientRaw = OrderContactCompanyTable::getList([
			'filter' => [
				'=ORDER_ID' => $ids
			]
		]);
		while ($orderClient = $orderClientRaw->fetch())
		{
			$orderId = $orderClient['ORDER_ID'];
			if ((int)$orderClient['ENTITY_TYPE_ID'] === \CCrmOwnerType::Contact)
			{
				if (empty($orders[$orderId]['CONTACT_ID']) || $orderClient['IS_PRIMARY'] === 'Y')
				{
					$orders[$orderId]['CONTACT_ID'] = $orderClient['ENTITY_ID'];
				}
			}
			elseif ((int)$orderClient['ENTITY_TYPE_ID'] === \CCrmOwnerType::Company)
			{
				$orders[$orderId]['COMPANY_ID'] = $orderClient['ENTITY_ID'];
			}
		}

		$paymentRaw = Payment::getList([
			'filter' => [
				'=ORDER_ID' => $ids
			]
		]);
		while ($payment = $paymentRaw->fetch())
		{
			$orderId = $payment['ORDER_ID'];
			$orders[$orderId]['PAYMENT'][$payment['ID']] = $payment;
		}

		$shipmentRaw = Shipment::getList([
			'filter' => [
				'=ORDER_ID' => $ids,
				'=SYSTEM' => 'N',
			],
		]);

		while ($shipment = $shipmentRaw->fetch())
		{
			$orderId = $shipment['ORDER_ID'];
			$orders[$orderId]['SHIPMENT'][$shipment['ID']] = $shipment;
		}

		$markersRaw = EntityMarker::getList([
			'filter' => [
				'=ENTITY_TYPE' => EntityMarker::ENTITY_TYPE_ORDER,
				'=ENTITY_ID' => $ids,
				'=SUCCESS' => 'N',
			],
			'select' => ['MESSAGE', 'ENTITY_ID']
		]);

		$markers = [];
		while ($marker = $markersRaw->fetch())
		{
			$markers[$marker['ENTITY_ID']][] = $marker['MESSAGE'];
		}

		if (!empty($markers))
		{
			foreach ($markers as $orderId => $marker )
			{
				if (count($marker) > 1)
				{
					$value = implode('<br>', $marker);
				}
				else
				{
					$value = $marker[0];
				}
				$orders[$orderId]['PROBLEM_NOTIFICATION'] = $value;
			}
		}

		$enumVariants = [];
		$enumPropertyVariantRaw = PropertyVariant::getList([
			'select' => ['VALUE', 'NAME', 'ORDER_PROPS_ID']
		]);
		while ($variant = $enumPropertyVariantRaw->fetch())
		{
			$enumVariants[$variant['ORDER_PROPS_ID']][$variant['VALUE']] = $variant['NAME'];
		}

		$propertyValuesRaw = PropertyValue::getList([
			'filter' => [
				'=ORDER_ID' => $ids
			],
			'select' => ['TYPE' => 'PROPERTY.TYPE', 'VALUE', 'ORDER_PROPS_ID', 'ORDER_ID']
		]);
		while ($propertyValue = $propertyValuesRaw->fetch())
		{
			$value = $propertyValue['VALUE'];
			$currentPropertyId = $propertyValue['ORDER_PROPS_ID'];
			if ($propertyValue['TYPE'] === 'ENUM')
			{
				$valueNameList = [];

				if (is_array($value))
				{
					foreach ($value as $currentValue)
					{
						$valueNameList[] = $enumVariants[$currentPropertyId][$currentValue];
					}
				}
				else
				{
					$valueNameList[] = $enumVariants[$currentPropertyId][$value];
				}

				$orders[$propertyValue['ORDER_ID']]['PROPERTY_'.$currentPropertyId] = implode(', ', $valueNameList);
			}
			if ($propertyValue['TYPE'] === 'Y/N')
			{
				$value = ($value === 'Y') ? 'Y' : 'N';
				$orders[$propertyValue['ORDER_ID']]['PROPERTY_'.$currentPropertyId] = Loc::getMessage('CRM_KANBAN_CHAR_' . $value);
			}
			else
			{
				$orders[$propertyValue['ORDER_ID']]['PROPERTY_'.$currentPropertyId] = $value;
			}
		}

		foreach ($orders as &$order)
		{
			if (isset($order['PERSON_TYPE_ID']))
			{
				$type = $personTypes[$order['PERSON_TYPE_ID']];
				$order['PERSON_TYPE_ID'] = $type['NAME'];
			}
			$order['CURRENCY_ID'] = $order['CURRENCY'];
			$order['USER'] = $order['USER_ID'] ?? null;
			$order['CURRENCY'] = $currencies[$order['CURRENCY']];
			$order['TITLE'] = Loc::getMessage('CRM_KANBAN_ORDER_TITLE', [
				'#ACCOUNT_NUMBER#' => $order['ACCOUNT_NUMBER']
			]);
		}

		return $orders;
	}

	public function prepareItemCommonFields(array $item): array
	{
		$item['FORMAT_TIME'] = false;
		$item['DATE'] = $item['DATE_INSERT'];
		$item['USER'] = $item['USER_ID'] ?? null;

		$item = parent::prepareItemCommonFields($item);
		$item['PRICE_FORMATTED'] = \CCrmCurrency::MoneyToString($item['PRICE'], $item['CURRENCY_ID']);

		return $item;
	}

	protected function getExtraDisplayedFields()
	{
		$result = parent::getExtraDisplayedFields();

		$result['PAYMENT'] =
			(Field::createByType('string', 'PAYMENT'))
				->setWasRenderedAsHtml(true)
		;
		$result['SHIPMENT'] =
			(Field::createByType('string', 'SHIPMENT'))
				->setWasRenderedAsHtml(true)
		;
		$result['USER'] =
			(Field::createByType('user', 'USER'))
				->addDisplayParams([
					'SHOW_URL_TEMPLATE' => '/shop/settings/sale_buyers_profile/?USER_ID=#user_id#&lang=' . LANGUAGE_ID
				])
		;

		return $result;
	}

	public function appendRelatedEntitiesValues(array $items, array $selectedFields): array
	{
		$items = parent::appendRelatedEntitiesValues($items, $selectedFields);

		$needAddPayment = in_array('PAYMENT', $selectedFields);
		$needAddShipment = in_array('SHIPMENT', $selectedFields);
		if ($needAddPayment || $needAddShipment)
		{
			foreach ($items as $itemId => $item)
			{
				if ($needAddPayment && isset($item['PAYMENT']))
				{
					$items[$itemId]['PAYMENT'] = $this->preparePaymentOrShipmentDisplayValue(
						'PAYMENT',
						(array)$item['PAYMENT']
				);
				}
				if ($needAddShipment && isset($item['SHIPMENT']))
				{
					$items[$itemId]['SHIPMENT'] = $this->preparePaymentOrShipmentDisplayValue(
						'SHIPMENT',
						(array)$item['SHIPMENT']
					);
				}
			}
		}

		return $items;
	}

	protected function preparePaymentOrShipmentDisplayValue(string $fieldId, array $value): string
	{
		$result = '';

		foreach ($value as $rowCodeItem)
		{
			if ($fieldId === 'PAYMENT')
			{
				$pathSubItem = Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()
					->getPaymentDetailsLink(
						$rowCodeItem['ID'],
						Service\Sale\EntityLinkBuilder\Context::getShopAreaContext()
					);
			}
			else
			{
				$pathSubItem = Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()
					->getShipmentDetailsLink(
						$rowCodeItem['ID'],
						Service\Sale\EntityLinkBuilder\Context::getShopAreaContext()
					);
			}

			$price = ($fieldId === 'PAYMENT') ? $rowCodeItem['SUM'] : $rowCodeItem['PRICE_DELIVERY'];
			$sum = \CCrmCurrency::MoneyToString(
				$price,
				$rowCodeItem['CURRENCY']
			);

			$title = '';

			$paySystemName =  ($fieldId === 'PAYMENT') ? $rowCodeItem['PAY_SYSTEM_NAME'] : $rowCodeItem['DELIVERY_NAME'];
			$paySystemName = htmlspecialcharsbx($paySystemName);
			if (!empty($paySystemName))
			{
				$title .= $paySystemName. " ";
			}

			if (!empty($sum))
			{
				$title .= "({$sum})";
			}

			if (empty($title))
			{
				$title = htmlspecialcharsbx($rowCodeItem['ACCOUNT_NUMBER']);
			}

			$result .= "<a href='{$pathSubItem}'>{$title}</a></br>";
		}

		return $result;
	}

	/**
	 * @param array $ids
	 * @param bool $isIgnore
	 * @param \CCrmPerms|null $permissions
	 * @param array $params
	 * @throws Exception
	 */
	public function deleteItems(array $ids, bool $isIgnore = false, \CCrmPerms $permissions = null, array $params = []): void
	{
		foreach ($ids as $id)
		{
			$id = (int)$id;
			$checkPermission = \Bitrix\Crm\Order\Permissions\Order::checkDeletePermission($id, $permissions);

			if (!$checkPermission)
			{
				throw new Exception(Loc::getMessage('CRM_KANBAN_ORDER_PERMISSION_ERROR'));
			}

			$res = \Bitrix\Crm\Order\Order::delete($id);

			if(!$res->isSuccess())
			{
				throw new Exception(implode(', ', $res->getErrorMessages()));
			}
		}
	}

	public function checkReadPermissions(int $id = 0, ?\CCrmPerms $permissions = null): bool
	{
		return \Bitrix\Crm\Order\Permissions\Order::checkReadPermission();
	}

	public function checkUpdatePermissions(int $id, ?\CCrmPerms $permissions = null): bool
	{
		return \Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission($id, $permissions);
	}

	public function getItem(int $id, array $fieldsToSelect = []): ?array
	{
		$order = \Bitrix\Crm\Order\Order::load($id);

		$this->loadedOrders[$id] = $order;

		return ($order instanceof \Bitrix\Crm\Order\Order) ? $order->getFieldValues() : null;
	}

	public function updateItemStage(int $id, string $stageId, array $newStateParams, array $stages): Result
	{
		$result = new Result();

		$order = $this->loadedOrders[$id] ?? null;
		if(!$order)
		{
			$order = \Bitrix\Crm\Order\Order::load($id);
		}
		if(!$order)
		{
			return $result->addError(new Error('Order not found'));
		}

		$order->setField('STATUS_ID', $stageId);
		return $order->save();
	}

	public function setItemsAssigned(array $ids, int $assignedId, \CCrmPerms $permissions): Result
	{
		$result = new Result();

		foreach ($ids as $id)
		{
			if ($this->checkUpdatePermissions($id, $permissions))
			{
				$order = \Bitrix\Crm\Order\Order::load($id);
				if($order)
				{
					$order->setField('RESPONSIBLE_ID', $assignedId);
					$saveResult = $order->save();
					if(!$saveResult->isSuccess())
					{
						$result->addErrors($saveResult->getErrors());
					}
				}
			}
		}

		return $result;
	}

	protected function getPopupGeneralFields(): array
	{
		$result = [];

		$filter = $this->getFilter();
		foreach ($filter->getFields() as $field)
		{
			if(strpos($field->getId(), "PROPERTY_") === 0)
			{
				continue;
			}

			$result[$field->getId()] = FieldAdapter::adapt($field->toArray(
				['lightweight' => true]
			));
		}

		return $result;
	}

	protected function getPopupAdditionalFields(string $viewType = self::VIEW_TYPE_VIEW): array
	{
		$result = parent::getPopupAdditionalFields($viewType);

		$additionalFields = [
			'TITLE' =>  Loc::getMessage('CRM_KANBAN_FIELD_ORDER_TITLE'),
			'PAYMENT' =>  Loc::getMessage('CRM_KANBAN_FIELD_ORDER_PAYMENTS'),
			'SHIPMENT' =>  Loc::getMessage('CRM_KANBAN_FIELD_ORDER_SHIPMENTS'),
			'PROBLEM_NOTIFICATION' =>  Loc::getMessage('CRM_KANBAN_FIELD_ORDER_PROBLEM_NOTIFICATION'),
		];
		foreach ($additionalFields as $fieldName => $fieldLabel)
		{
			$result[$fieldName] =  [
				'ID' => 'field_' . $fieldName,
				'NAME' => $fieldName,
				'LABEL' => $fieldLabel
			];
		}

		$propertiesRaw = \Bitrix\Crm\Order\Property::getList(
			array(
				'filter' => array(
					'=ACTIVE' => 'Y',
					'=TYPE' => ['STRING', 'NUMBER', 'Y/N', 'ENUM', 'DATE']
				),
				'order' => array(
					"PERSON_TYPE_ID" => "ASC", "SORT" => "ASC"
				),
				'select' => array(
					"ID", "NAME", "PERSON_TYPE_NAME" => "PERSON_TYPE.NAME", "LID" => "PERSON_TYPE.LID", "PERSON_TYPE_ID"
				),
			)
		);

		while ($property = $propertiesRaw->fetch())
		{
			$fieldName = 'PROPERTY_'.$property['ID'];
			$fieldLabel = htmlspecialcharsbx("{$property['NAME']} ({$property['PERSON_TYPE_NAME']}) [{$property['LID']}]");
			$result[$fieldName] =  [
				'ID' => 'field_' . $fieldName,
				'NAME' => $fieldName,
				'LABEL' => $fieldLabel
			];
		}

		return $result;
	}

	protected function getPopupHiddenFields(): array
	{
		return array_merge(parent::getPopupHiddenFields(), [
			'COUPON', 'PAY_SYSTEM', 'DELIVERY_SERVICE', 'CREATED_BY', 'SHIPMENT_TRACKING_NUMBER', 'SHIPMENT_DELIVERY_DOC_DATE'
		]);
	}

	public function getTypeInfo(): array
	{
		return array_merge(
			parent::getTypeInfo(),
			[
				'canUseCreateTaskInPanel' => true,
				'canUseCallListInPanel' => true,
			]
		);
	}

	protected function getHideSumForStagePermissionType(string $stageId, \CCrmPerms $userPermissions): ?string
	{
		return $userPermissions->GetPermType(
			$this->getTypeName(),
			'HIDE_SUM',
			["STAGE_ID{$stageId}"]
		);
	}
}
