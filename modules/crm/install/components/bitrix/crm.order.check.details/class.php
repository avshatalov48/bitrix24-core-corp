<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm;
use Bitrix\Crm\Order\EntityBinding;
use Bitrix\Main;
use Bitrix\Crm\Order;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Cashbox;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Component\ComponentError;
use Bitrix\Crm\Security\EntityPermissionType;
use Bitrix\Crm\Component\EntityDetails\ComponentMode;
use Bitrix\Crm\Service;

Loc::loadMessages(__FILE__);
if(!Main\Loader::includeModule('crm'))
{
	ShowError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}
if(!Main\Loader::includeModule('sale'))
{
	ShowError(Loc::getMessage('SALE_MODULE_NOT_INSTALLED'));
	return;
}

class CCrmOrderCheckDetailsComponent extends Crm\Component\EntityDetails\BaseComponent
{
	/** @var \Bitrix\Sale\Order */
	private  $order = null;
	/** @var null  */
	private  $orderId = null;
	/** @var array  */
	private  $checkTypeMap = array();

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::OrderCheck;
	}

	protected function getErrorMessage($error)
	{
		if($error === ComponentError::ENTITY_NOT_FOUND)
		{
			return Loc::getMessage('CRM_ORDER_NOT_FOUND');
		}
		return ComponentError::getMessage($error);
	}

	/**
	 * @inheritDoc
	 */
	public function onPrepareComponentParams($arParams)
	{
		$arParams['OWNER_TYPE'] = (int)$this->request->get('owner_type');
		$arParams['OWNER_ID'] = (int)$this->request->get('owner_id');

		return parent::onPrepareComponentParams($arParams);
	}

	public function loadOrder(): void
	{
		if ($this->orderId > 0 && $this->order === null)
		{
			$this->order = Order\Order::load($this->orderId);
		}
	}

	public function executeComponent()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		//region Params
		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID']) ? (int)$this->arParams['~ENTITY_ID'] : 0;

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams['NAME_TEMPLATE']);

		$this->arResult['PATH_TO_ORDER_SHOW'] = CrmCheckPath(
			'PATH_TO_ORDER_SHOW',
			$APPLICATION->GetCurPage().'?order_id=#order_id#&show',
			null
		);
		$this->arResult['ACTION_URI'] = $this->arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;
		$this->arResult['DATE_FORMAT'] = Main\Type\Date::getFormat();
		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::OrderCheckName.'_'.$this->arResult['ENTITY_ID'];
		$this->arResult['CONTEXT_PARAMS'] = array(
			'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE']
		);

		$this->arResult['EXTERNAL_CONTEXT_ID'] = $this->request->get('external_context_id');
		if($this->arResult['EXTERNAL_CONTEXT_ID'] === null)
		{
			$this->arResult['EXTERNAL_CONTEXT_ID'] = $this->request->get('external_context');
			if($this->arResult['EXTERNAL_CONTEXT_ID'] === null)
			{
				$this->arResult['EXTERNAL_CONTEXT_ID'] = '';
			}
		}

		$this->setEntityID($this->arResult['ENTITY_ID']);
		$this->checkTypeMap = Cashbox\CheckManager::getCheckTypeMap();
		if ($this->arResult['ENTITY_ID'] > 0)
		{
			$entityData = $this->prepareEntityData($this->arResult['ENTITY_ID']);
			$this->arResult['READ_ONLY'] = true;
		}
		else
		{
			$this->orderId = (int)$this->arParams['EXTRAS']['ORDER_ID'];
			if ($this->orderId <= 0)
			{
				$this->addError(Loc::getMessage('CRM_ORDER_NOT_FOUND'));
				$this->showErrors();
				return;
			}
			$dateInsert = time() + \CTimeZone::GetOffset();
			$time = localtime($dateInsert, true);
			$dateInsert -= $time['tm_sec'] + 60 * $time['tm_min'] + 3600 * $time['tm_hour'];
			$entityData['DATE_CREATE'] = ConvertTimeStamp($dateInsert, 'SHORT', SITE_ID);
		}

		$this->loadOrder();
		if (empty($this->order))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_NOT_FOUND'));
			$this->showErrors();
			return;
		}

		if (
			!CCrmSaleHelper::isWithOrdersMode()
			&& $this->order->getTradeBindingCollection()->isEmpty()
		)
		{
			ShowError(Loc::getMessage('CRM_ORDER_CHECK_NOT_FOUND'));
			return;
		}

		if(!$this->tryToDetectMode())
		{
			$this->addError(Loc::getMessage('CRM_PERMISSION_DENIED'));
			$this->showErrors();
			return;
		}

		if ($this->arResult['ENTITY_ID'] <= 0 && Cashbox\Manager::isEnabledPaySystemPrint())
		{
			ShowError(Loc::getMessage('CRM_ORDER_CASHBOX_MANUAL_PRINT_ERROR'));
			return;
		}

		$this->arResult['ENTITY_DATA'] = $entityData;

		//region GUID
		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID'])
			? $this->arParams['GUID'] : "order_check_{$this->entityID}_details";

		$this->arResult['EDITOR_CONFIG_ID'] = isset($this->arParams['EDITOR_CONFIG_ID'])
			? $this->arParams['EDITOR_CONFIG_ID'] : 'order_check_details';
		//endregion

		$title = Loc::getMessage(
			'CRM_ORDER_CHECK_TITLE',
			array(
				'#ID#' => $entityData['ID'],
				'#DATE_CREATE#' => FormatDate(Main\Type\Date::getFormat(), MakeTimeStamp($entityData['DATE_CREATE']))
			));

		//region Entity Info
		$this->arResult['ENTITY_INFO'] = array(
			'ENTITY_ID' => $this->entityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::OrderCheck,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::OrderCheckName,
			'TITLE' => $title,
			'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::OrderCheck, $this->entityID, false),
		);
		//endregion

		//region Page title
		if($this->mode === ComponentMode::CREATION)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_ORDER_CHECK_ADD_TITLE'));
		}
		elseif($this->mode === ComponentMode::COPING)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_ORDER_COPY_PAGE_TITLE'));
		}
		elseif(!empty($title))
		{
			$APPLICATION->SetTitle($title);
		}
		//endregion

		//region Fields
		$this->prepareFieldInfos();
		//endregion

		//region Config
		$this->arResult['ENTITY_CONFIG'] = array(
			array(
				'name' => 'main',
				'title' => Loc::getMessage('CRM_ORDER_SECTION_MAIN'),
				'type' => 'section',
				'elements' => [
					array('name' => 'ID'),
					array('name' => $this->isWithOrdersMode() ? 'ORDER_ID' : 'ENTITY_ID'),
					array('name' => 'CASHBOX_NAME'),
					array('name' => 'SUM_WITH_CURRENCY'),
					array('name' => 'STATUS_NAME'),
					array('name' => 'CHECK_LINK'),
					array('name' => 'DATE_CREATE')
				]
			),
			array(
				'name' => 'payment_information',
				'title' => Loc::getMessage('CRM_ORDER_SECTION_ADDITIONAL'),
				'type' => 'section',
				'elements' => array(
					array('name' => 'TYPE'),
					array('name' => 'PAYMENT_VALUE'),
					array('name' => 'SHIPMENT_VALUE')
				)
			)
		);
		//endregion

		//region VIEW EVENT
		if($this->entityID > 0 && \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isViewEventEnabled())
		{
			CCrmEvent::RegisterViewEvent(CCrmOwnerType::OrderCheck, $this->entityID, $this->userID);
		}
		//endregion
		$template = '';
		if ($this->entityID === 0)
		{
			$template = 'edit';
		}

		$this->includeComponentTemplate($template);
	}
	protected function prepareFieldInfos()
	{
		if(isset($this->arResult['ENTITY_FIELDS']))
		{
			return $this->arResult['ENTITY_FIELDS'];
		}

		$paymentData = $this->preparePaymentFieldData();

		if ($this->entityID > 0)
		{
			$this->arResult['ENTITY_FIELDS'] = array(
				array(
					'name' => 'ID',
					'title' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_ID'),
					'type' => 'text',
					'editable' => false
				),
				array(
					'name' => 'CASHBOX_NAME',
					'title' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_CASHBOX_ID'),
					'type' => 'text',
					'editable' => false
				),
				array(
					'name' => 'STATUS_NAME',
					'title' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_STATUS'),
					'type' => 'text',
					'editable' => false
				),
				array(
					'name' => 'SUM_WITH_CURRENCY',
					'title' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_SUM'),
					'type' => 'money',
					'editable' => false,
					'data' => array(
						'affectedFields' => array('CURRENCY', 'SUM'),
						'currency' => array(
							'name' => 'CURRENCY',
							'items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems())
						),
						'amount' => 'SUM',
						'formatted' => 'FORMATTED_SUM',
						'formattedWithCurrency' => 'FORMATTED_SUM_WITH_CURRENCY'
					)
				),
				array(
					'name' => 'DATE_CREATE',
					'title' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_DATE_CREATE'),
					'type' => 'datetime',
					'editable' => false,
					'data' => array('enableTime' => true)
				),
				...[
					$this->isWithOrdersMode()
						? [
							'name' => 'ORDER_ID',
							'title' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_ORDER_ID'),
							'type' => 'text',
							'editable' => false,
						]
						: [
							'name' => 'ENTITY_ID',
							'title' => Loc::getMessage(
								'CRM_COLUMN_ORDER_CHECK_ENTITY',
								[
									'#ENTITY_NAME#' => lcfirst(
										CCrmOwnerType::getDescription(
											$this->getOrderBindingOwnerTypeId()
										)
									)
								]
							),
							'type' => 'text',
							'editable' => false
						]
				],
				array(
					'name' => 'CHECK_LINK',
					'title' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_LINK'),
					'type' => 'custom',
					'editable' => false,
					'data' =>  array('view' => 'CHECK_LINK')
				),
				array(
					'name' => 'TYPE',
					'title' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_TYPE'),
					'type' => 'list',
					'editable' => false,
					'data' => array(
						'items' =>  $paymentData['TYPE_MAP']
					)
				),
				array(
					'name' => 'PAYMENT_VALUE',
					'title' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_PAYMENT_DESCR'),
					'type' => 'custom',
					'editable' => false,
					'data' => array('view' => 'PAYMENT_VALUE')
				),
				array(
					'name' => 'SHIPMENT_VALUE',
					'title' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_SHIPMENT_DESCR'),
					'type' => 'custom',
					'editable' => false,
					'data' => array('view' => 'SHIPMENT_VALUE')
				)
			);
		}
		else
		{
			$this->arResult['ORDER_ID'] = $this->orderId;
			$this->arResult['IS_MULTIPLE'] = Cashbox\Manager::isSupportedFFD105() ? 'Y' : 'N';
			$this->arResult['MAIN_LIST'] = $this->getEntityList();
			$this->arResult['DEFAULT_MAIN_ENTITY'] = $this->getDefaultMainEntity();

			$checkTypes = $this->getCheckTypes($this->arResult['DEFAULT_MAIN_ENTITY']['TYPE']);

			$this->arResult['ADDITION_LIST'] = $this->getAdditionalEntityList(
				$this->arResult['DEFAULT_MAIN_ENTITY'],
				$checkTypes['CURRENT_TYPE']
			);

			$this->arResult = array_merge($this->arResult, $checkTypes);
		}

		return $this->arResult['ENTITY_FIELDS'];
	}

	/**
	 * @param $defaultEntity
	 * @param $checkType
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 */
	protected function getAdditionalEntityList($defaultEntity, $checkType)
	{
		$result = [];

		if ($defaultEntity['TYPE'] == Cashbox\Check::SUPPORTED_ENTITY_TYPE_PAYMENT)
		{
			$additionList = Cashbox\CheckManager::getRelatedEntitiesForPayment(
				$checkType,
				$defaultEntity['VALUE']
			);
		}
		else
		{
			$additionList = Cashbox\CheckManager::getRelatedEntitiesForShipment(
				$checkType,
				$defaultEntity['VALUE']
			);
		}

		if (isset($additionList['PAYMENTS']))
		{
			foreach ($additionList['PAYMENTS'] as $payment)
			{
				$paymentName = Loc::getMessage('CRM_ORDER_PAYMENT', array(
					'#PAYMENT_NUMBER#' => $payment['ACCOUNT_NUMBER'],
					'#PAYSYSTEM_NAME#' => $payment['NAME']
				));
				$paymentSum = \CCrmCurrency::MoneyToString($payment['SUM'], $payment['CURRENCY']);
				$item = [
					'TYPE' => Cashbox\Check::SUPPORTED_ENTITY_TYPE_PAYMENT,
					'VALUE' => $payment['ID'],
					'NAME' => htmlspecialcharsbx($paymentName) . " ($paymentSum)"
				];

				$result[] = $item;
			}
		}

		if (isset($additionList['SHIPMENTS']))
		{
			foreach ($additionList['SHIPMENTS'] as $shipment)
			{
				$shipmentName = Loc::getMessage('CRM_ORDER_SHIPMENT', array(
					'#SHIPMENT_NUMBER#' => $shipment['ACCOUNT_NUMBER'],
					'#DELIVERY_SYSTEM_NAME#' => $shipment['NAME']
				));
				$shipmentSum = \CCrmCurrency::MoneyToString($shipment['PRICE_DELIVERY'], $shipment['CURRENCY']);
				$result[] = [
					'TYPE' => Cashbox\Check::SUPPORTED_ENTITY_TYPE_SHIPMENT,
					'VALUE' => $shipment['ID'],
					'NAME' => htmlspecialcharsbx($shipmentName) . " ($shipmentSum)"
				];
			}
		}

		return $result;
	}

	public function getEntityList()
	{
		$result = [];

		if ($this->order)
		{
			/** @var \Bitrix\Sale\Payment $payment */
			foreach ($this->order->getPaymentCollection() as $payment)
			{
				$paymentName = Loc::getMessage('CRM_ORDER_PAYMENT', array(
					'#PAYMENT_NUMBER#' => $payment->getField('ACCOUNT_NUMBER'),
					'#PAYSYSTEM_NAME#' => $payment->getPaymentSystemName(),
				));
				$paymentSum = \CCrmCurrency::MoneyToString($payment->getSum(), $payment->getField('CURRENCY'));
				$entityData = [
					'VALUE' => $payment->getId(),
					'TYPE' => Cashbox\Check::SUPPORTED_ENTITY_TYPE_PAYMENT,
					'NAME' => htmlspecialcharsbx($paymentName) . " ($paymentSum)",
				];

				$result[] = $entityData;
			}

			if (Cashbox\Manager::isSupportedFFD105())
			{
				/** @var \Bitrix\Sale\Shipment $shipment */
				foreach ($this->order->getShipmentCollection() as $shipment)
				{
					if ($shipment->isSystem())
					{
						continue;
					}

					$shipmentName = Loc::getMessage('CRM_ORDER_SHIPMENT', array(
						'#SHIPMENT_NUMBER#' => $shipment->getField('ACCOUNT_NUMBER'),
						'#DELIVERY_SYSTEM_NAME#' => $shipment->getDeliveryName()
					));
					$shipmentSum = \CCrmCurrency::MoneyToString($shipment->getPrice(), $shipment->getCurrency());

					$entityData = [
						'VALUE' => $shipment->getId(),
						'TYPE' => Cashbox\Check::SUPPORTED_ENTITY_TYPE_SHIPMENT,
						'NAME' => htmlspecialcharsbx($shipmentName) . " ($shipmentSum)",
					];

					$result[] = $entityData;
				}
			}
		}

		return $result;
	}

	public function getCheckTypes($entityType, $checkType = null)
	{
		$result['CHECK_TYPES'] = array();

		if (empty($this->checkTypeMap))
		{
			$this->checkTypeMap = Cashbox\CheckManager::getCheckTypeMap();
		}

		$types = array();
		/** @var Cashbox\Check $typeClass */
		foreach ($this->checkTypeMap as $id => $typeClass)
		{
			if (
				$typeClass::getSupportedEntityType() === $entityType ||
				$typeClass::getSupportedEntityType() === Cashbox\Check::SUPPORTED_ENTITY_TYPE_ALL
			)
			{
				if (class_exists($typeClass))
				{
					$result['CHECK_TYPES'][] = array("VALUE" => $id, "NAME" => $typeClass::getName());
					$types[] = $id;
				}
			}
		}

		if (empty($checkType) || !in_array($checkType, $types))
		{
			$checkType = $result['CHECK_TYPES'][0]['VALUE'];
			$result['CURRENT_TYPE_NAME'] = $result['CHECK_TYPES'][0]['NAME'];
		}

		$result['CURRENT_TYPE'] = $checkType;

		return $result;
	}


	public function prepareEditData($entityId, $entityType, $checkType = null)
	{
		$result['CHECK_TYPES'] = array();

		if (empty($this->checkTypeMap))
		{
			$this->checkTypeMap = Cashbox\CheckManager::getCheckTypeMap();
		}

		$types = array();
		/** @var Cashbox\Check $typeClass */
		foreach ($this->checkTypeMap as $id => $typeClass)
		{
			if (
				$typeClass::getSupportedEntityType() === $entityType ||
				$typeClass::getSupportedEntityType() === Cashbox\Check::SUPPORTED_ENTITY_TYPE_ALL
			)
			{
				if (class_exists($typeClass))
				{
					$result['CHECK_TYPES'][] = array("VALUE" => $id, "NAME" => $typeClass::getName());
					$types[] = $id;
				}
			}
		}

		if (empty($checkType) || !in_array($checkType, $types))
		{
			$checkType = $result['CHECK_TYPES'][0]['VALUE'];
			$result['CURRENT_TYPE_NAME'] = $result['CHECK_TYPES'][0]['NAME'];
		}

		$result['CURRENT_TYPE'] = $checkType;

		if ($entityType === Cashbox\Check::SUPPORTED_ENTITY_TYPE_PAYMENT)
		{
			$relatedEntities = Cashbox\CheckManager::getRelatedEntitiesForPayment($checkType, $entityId);
		}
		else
		{
			$relatedEntities = Cashbox\CheckManager::getRelatedEntitiesForShipment($checkType, $entityId);
		}

		$result['ADDITION_LIST'] = [];
		foreach ($relatedEntities as $type => $entityTypeList)
		{
			foreach ($entityTypeList as $entity)
			{
				$entityData = array(
					'VALUE' => $entity['ID']
				);
				if ($type === 'PAYMENTS')
				{
					$paymentName = Loc::getMessage('CRM_ORDER_PAYMENT', array(
						'#PAYMENT_NUMBER#' => $entity['ACCOUNT_NUMBER'],
						'#PAYSYSTEM_NAME#' => $entity['NAME']
					));
					$paymentSum = \CCrmCurrency::MoneyToString($entity['SUM'], $entity['CURRENCY']);
					$entityData['TYPE'] = Cashbox\Check::SUPPORTED_ENTITY_TYPE_PAYMENT;
					$entityData['NAME'] = htmlspecialcharsbx($paymentName) . " ($paymentSum)";
					if (!empty($entity['PAYMENT_TYPES']) && is_array($entity['PAYMENT_TYPES']))
					{
						foreach ( $entity['PAYMENT_TYPES'] as &$paymentType)
						{
							$paymentType['ENTITY_ID'] = $entity['ID'];
						}
						$entityData['PAYMENT_TYPES'] = $entity['PAYMENT_TYPES'];
						$entityData['PAYMENT_SELECTED_TYPE'] = current($entity['PAYMENT_TYPES']);
					}
				}
				else
				{
					$shipmentName = Loc::getMessage('CRM_ORDER_SHIPMENT', array(
						'#SHIPMENT_NUMBER#' => $entity['ACCOUNT_NUMBER'],
						'#DELIVERY_SYSTEM_NAME#' => $entity['NAME']
					));
					$shipmentSum = \CCrmCurrency::MoneyToString($entity['PRICE_DELIVERY'], $entity['CURRENCY']);
					$entityData['TYPE'] = Cashbox\Check::SUPPORTED_ENTITY_TYPE_SHIPMENT;
					$entityData['NAME'] = htmlspecialcharsbx($shipmentName) . " ($shipmentSum)";
				}

				$result['ADDITION_LIST'][] = $entityData;
			}
		}

		$result = $result + $relatedEntities;

		$result['IS_MULTIPLE'] = Cashbox\Manager::isSupportedFFD105() ? 'Y' : 'N';

		return $result;
	}

	protected function preparePaymentFieldData()
	{
		$data = array();

		$this->arResult['ENTITY_DATA']['SHIPMENT_VALUE'] = '';
		$this->arResult['ENTITY_DATA']['PAYMENT_VALUE'] = '';

		$paymentCollection = $this->order->getPaymentCollection();
		foreach ($paymentCollection as $payment)
		{
			$paymentId = $payment->getId();
			$data['PAYMENT'][] = array(
				'NAME' => Loc::getMessage('CRM_ORDER_PAYMENT', array(
					'#PAYMENT_NUMBER#' => $payment->getField('ACCOUNT_NUMBER'),
					'#PAYSYSTEM_NAME#' => $payment->getField('PAY_SYSTEM_NAME')
				)),
				'VALUE' => $paymentId,
				'TYPE' => Cashbox\Check::SUPPORTED_ENTITY_TYPE_PAYMENT
			);

			if ($this->entityID > 0 && in_array($paymentId, $this->arResult['ENTITY_DATA']['PAYMENT']))
			{
				$paymentTitle = Loc::getMessage("CRM_ORDER_PAYMENT_TITLE",array(
					"#ACCOUNT_NUMBER#" => $payment->getField('ACCOUNT_NUMBER'),
					"#DATE_BILL#" => FormatDate($this->arResult['DATE_FORMAT'], MakeTimeStamp($payment->getField('DATE_BILL')))
				));
				$this->arResult['ENTITY_DATA']['PAYMENT_VALUE'] .= CCrmViewHelper::RenderInfo(
					Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()->getPaymentDetailsLink(
						$paymentId
					),
					htmlspecialcharsbx($paymentTitle),
					htmlspecialcharsbx($payment->getField('PAY_SYSTEM_NAME')),
					array('TARGET' => '_self')
				);
			}
		}

		$shipmentCollection = $this->order->getShipmentCollection();

		foreach ($shipmentCollection as $shipment)
		{
			if ($shipment->isSystem())
			{
				continue;
			}
			$shipmentId = $shipment->getId();
			$data['SHIPMENT'][] = array(
				'NAME' => Loc::getMessage('CRM_ORDER_SHIPMENT', array(
					'#SHIPMENT_NUMBER#' => $shipment->getField('ACCOUNT_NUMBER'),
					'#DELIVERY_SYSTEM_NAME#' => $shipment->getField('DELIVERY_NAME')
				)),
				'VALUE' => $shipment->getId(),
				'TYPE' => Cashbox\Check::SUPPORTED_ENTITY_TYPE_SHIPMENT
			);

			$shipmentTitle = Loc::getMessage("CRM_ORDER_SHIPMENT_TITLE",array(
				"#ACCOUNT_NUMBER#" => $shipment->getField('ACCOUNT_NUMBER'),
				"#DATE_INSERT#" => FormatDate($this->arResult['DATE_FORMAT'], MakeTimeStamp($shipment->getField('DATE_INSERT')))
			));

			$this->arResult['ENTITY_DATA']['SHIPMENT_VALUE'] .= CCrmViewHelper::RenderInfo(
				Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()
					->getShipmentDetailsLink($shipmentId),
				htmlspecialcharsbx($shipmentTitle),
				htmlspecialcharsbx($shipment->getField('DELIVERY_NAME')),
				array('TARGET' => '_self')
			);
		}
		foreach ($this->checkTypeMap as $typeValue => $className)
		{
			if (class_exists($className))
			{
				$data['TYPE_MAP'][] = array(
					'NAME' => $className::getName(),
					'VALUE' => $typeValue,
				);
			}
		}

		return $data;
	}

	protected function prepareEntityData($checkId)
	{
		$cashboxList = Cashbox\Manager::getListFromCache();
		$resultCheckData = Cashbox\Internals\CashboxCheckTable::getList(
			array(
				'filter' => array('=ID' => $checkId),
				'limit' => 1
			)
		);

		if ($check = $resultCheckData->fetch())
		{
			if (class_exists($this->checkTypeMap[$check['TYPE']]))
			{
				$type = $this->checkTypeMap[$check['TYPE']];
				$check['CHECK_TYPE'] = $type::getName();
			}
			$cashboxId = $check['CASHBOX_ID'];
			$check['CASHBOX_NAME'] = $cashboxList[$cashboxId]['NAME'];
			$check['PAYMENT'] = array($check['PAYMENT_ID']);
			$check['SHIPMENT'] = array($check['SHIPMENT_ID']);
			$check['FORMATTED_SUM_WITH_CURRENCY'] = CCrmCurrency::MoneyToString($check['SUM'], $check['CURRENCY'], '');
			$check['FORMATTED_SUM'] = CCrmCurrency::MoneyToString($check['SUM'], $check['CURRENCY'], '#');

			$check['CHECK_LINK'] = '';
			if ($check['LINK_PARAMS'])
			{
				$cashbox = \Bitrix\Sale\Cashbox\Manager::getObjectById($check['CASHBOX_ID']);
				if ($cashbox)
				{
					$check['CHECK_LINK'] = CCrmViewHelper::RenderInfo(
						$cashbox->getCheckLink($check['LINK_PARAMS']),
						Loc::getMessage('CRM_ORDER_CHECK_LINK'),
						'',
						array('TARGET' => '_blank')
					);
				}
			}

			$check['STATUS_NAME'] = Loc::getMessage('CRM_ORDER_CASHBOX_STATUS_'.$check['STATUS']);
			if (isset($check['ERROR_MESSAGE']))
			{
				$check['STATUS_NAME'] .= ' (' . $check['ERROR_MESSAGE'] . ')';
			}
		}

		$relatedDb = Cashbox\Internals\CheckRelatedEntitiesTable::getList(array(
			'filter' => array('=CHECK_ID' => $checkId)
		));

		while ($related = $relatedDb->fetch())
		{
			$type = null;
			if ($related['ENTITY_TYPE'] === Cashbox\Internals\CheckRelatedEntitiesTable::ENTITY_TYPE_SHIPMENT)
			{
				$type = 'SHIPMENT';
			}
			elseif ($related['ENTITY_TYPE'] === Cashbox\Internals\CheckRelatedEntitiesTable::ENTITY_TYPE_PAYMENT)
			{
				$type = 'PAYMENT';
			}

			if (!empty($type))
			{
				$check[$type][] = $related['ENTITY_ID'];
			}
		}

		$this->orderId = $check['ORDER_ID'];
		$this->loadOrder();
		if (!$this->isWithOrdersMode())
		{
			$check['ENTITY_ID'] = $this->getOrderBindingOwnerId();
		}

		return $check;
	}

	protected function tryToDetectMode()
	{
		if($this->entityID <= 0 || $this->getRequestParamOrDefault('copy', '') !== '')
		{
			if(!$this->checkEntityPermission(EntityPermissionType::UPDATE))
			{
				$this->addError(ComponentError::PERMISSION_DENIED);
				return false;
			}

			$this->mode = ComponentMode::CREATION;
		}
		else
		{
			if(!$this->checkEntityPermission(EntityPermissionType::READ))
			{
				$this->addError(ComponentError::PERMISSION_DENIED);
				return false;
			}

			$this->mode = ComponentMode::VIEW;
		}

		$this->arResult['COMPONENT_MODE'] = $this->mode;
		return true;
	}
	protected function checkEntityPermission($permissionTypeID)
	{
		return EntityAuthorization::checkPermission(
			$permissionTypeID,
			\CCrmOwnerType::Order,
			$this->orderId,
			$this->userPermissions
		);
	}

	/**
	 * @return bool
	 */
	private function isWithOrdersMode(): bool
	{
		return CCrmSaleHelper::isWithOrdersMode();
	}

	/**
	 * @return int|null
	 */
	private function getOrderBindingOwnerTypeId(): ?int
	{
		$binding = $this->getOrderBinding();

		return $binding ? $binding->getOwnerTypeId() : null;
	}

	/**
	 * @return int|null
	 */
	private function getOrderBindingOwnerId(): ?int
	{
		$binding = $this->getOrderBinding();

		return $binding ? $binding->getOwnerId() : null;
	}

	/**
	 * @return EntityBinding|null
	 */
	private function getOrderBinding(): ?EntityBinding
	{
		return $this->order ? $this->order->getEntityBinding() : null;
	}

	/**
	 * @return int
	 */
	private function getDefaultMainEntity(): array
	{
		$entityTypeMap = [
			Cashbox\Check::SUPPORTED_ENTITY_TYPE_PAYMENT => CCrmOwnerType::OrderPayment,
			Cashbox\Check::SUPPORTED_ENTITY_TYPE_SHIPMENT => CCrmOwnerType::ShipmentDocument,
		];

		foreach ($this->arResult['MAIN_LIST'] as $entityIndex => $entity)
		{
			if (
				isset($entityTypeMap[$entity['TYPE']])
				&& (int)$entityTypeMap[$entity['TYPE']] === (int)$this->arParams['OWNER_TYPE']
				&& (int)$entity['VALUE'] === (int)$this->arParams['OWNER_ID']
			)
			{
				return $entity;
			}
		}

		return $this->arResult['MAIN_LIST'][0];
	}
}
