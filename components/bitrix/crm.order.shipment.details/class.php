<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Main;
use Bitrix\Crm\Order;
use Bitrix\Sale\Delivery;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Component\ComponentError;
use Bitrix\Crm\Security\EntityPermissionType;
use Bitrix\Crm\Component\EntityDetails\ComponentMode;
use Bitrix\Sale\Internals\AccountNumberGenerator;

if(!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

Loc::loadMessages(__FILE__);

class CCrmOrderShipmentDetailsComponent extends Crm\Component\EntityDetails\BaseComponent
{
	use Crm\Component\EntityDetails\SaleProps\ComponentTrait;

	const COMPONENT_ERROR_EMPTY_ORDER_ID = -0x3;

	/** @var Order\Shipment */
	private $shipment = null;

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::OrderShipment;
	}

	protected function getUserFieldEntityID()
	{
		return Order\Shipment::getUfId();
	}

	protected function checkIfEntityExists()
	{
		if ($this->entityID > 0)
		{
			$res = Crm\Order\Shipment::getList(array(
				'filter' => array('=ID' => $this->entityID)
			));

			return (bool)$res->fetch();
		}

		return false;
	}

	protected function getErrorMessage($error)
	{
		if($error === ComponentError::ENTITY_NOT_FOUND)
		{
			return Loc::getMessage('CRM_ORDER_SHIPMENT_SHIPMENT_NOT_FOUND');
		}
		return ComponentError::getMessage($error);
	}

	public function initializeParams(array $params)
	{
		foreach($params as $k => $v)
		{
			if(!is_string($v))
			{
				continue;
			}

			if($k === 'PATH_TO_PRODUCT_SHOW')
			{
				$this->arResult['PATH_TO_PRODUCT_SHOW'] = $this->arParams['PATH_TO_PRODUCT_SHOW'] = $v;
			}
			elseif($k === 'NAME_TEMPLATE')
			{
				$this->arResult['NAME_TEMPLATE'] = $this->arParams['NAME_TEMPLATE'] = $v;
			}
			elseif($k === 'ORDER_SHIPMENT_ID')
			{
				$this->arResult[$k] = $this->arParams[$k] = (int)$v;
			}
		}
	}

	public function obtainShipment()
	{
		if($this->shipment === null)
		{
			if($this->entityID > 0)
			{
				$this->shipment = Order\Manager::getShipmentObject($this->entityID);
			}
		}

		return $this->shipment;
	}

	public function setShipment(Order\Shipment $shipment)
	{
		$this->shipment = $shipment;
		$this->arResult['SITE_ID'] = $this->shipment->getOrder()->getSiteId();
	}

	public function executeComponent()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		//region Params
		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID']) ? (int)$this->arParams['~ENTITY_ID'] : 0;

		$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] =
			CrmCheckPath('PATH_TO_USER_PROFILE', $this->arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams['NAME_TEMPLATE']);

		$this->arResult['PATH_TO_ORDER_SHIPMENT_SHOW'] = CrmCheckPath(
			'PATH_TO_ORDER_SHIPMENT_SHOW',
			$this->arParams['PATH_TO_ORDER_SHIPMENT_SHOW'],
			$APPLICATION->GetCurPage().'?shipment_id=#shipment_id#&show'
		);
		$this->arResult['PATH_TO_ORDER_SHIPMENT_EDIT'] = CrmCheckPath(
			'PATH_TO_ORDER_SHIPMENT_EDIT',
			$this->arParams['PATH_TO_ORDER_SHIPMENT_EDIT'],
			$APPLICATION->GetCurPage().'?shipment_id=#shipment_id#&edit'
		);

		$this->arResult['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath(
			'PATH_TO_PRODUCT_EDIT',
			$this->arParams['PATH_TO_PRODUCT_EDIT'],
			$APPLICATION->GetCurPage().'?product_id=#product_id#&edit'
		);

		$this->arResult['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath(
			'PATH_TO_PRODUCT_SHOW',
			$this->arParams['PATH_TO_PRODUCT_SHOW'],
			$APPLICATION->GetCurPage().'?product_id=#product_id#&show'
		);

		$this->arResult['ACTION_URI'] = $this->arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;
		$this->arResult['PRODUCT_DATA_FIELD_NAME'] = 'ORDER_SHIPMENT_PRODUCT_DATA';
		$this->arResult['PRODUCT_EDITOR_ID'] = 'order_shipment_product_editor';

		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::OrderShipmentName.'_'.$this->arResult['ENTITY_ID'];
		$this->arResult['CONTEXT_PARAMS'] = array(
			'PATH_TO_PRODUCT_SHOW' => $this->arResult['PATH_TO_PRODUCT_SHOW'],
			'PATH_TO_USER_PROFILE' => $this->arResult['PATH_TO_USER_PROFILE'],
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

		$this->arResult['ORIGIN_ID'] = $this->request->get('origin_id');
		if($this->arResult['ORIGIN_ID'] === null)
		{
			$this->arResult['ORIGIN_ID'] = '';
		}

		$this->setEntityID($this->arResult['ENTITY_ID']);

		if(!$this->tryToDetectMode())
		{
			$this->showErrors();
			return;
		}

		$this->arResult['ORDER_ID'] = (int)$this->arParams['EXTRAS']['ORDER_ID'];

		if ($this->arResult['ORDER_ID'] <= 0)
		{
			if($this->entityID > 0)
			{
				$this->shipment = Crm\Order\Manager::getShipmentObject($this->entityID);

				if(!$this->shipment)
				{
					$this->addError(new Main\Error(Loc::getMessage('CRM_ORDER_SD_SHIPMENT_NOT_FOUND')));
					$this->showErrors();
					return;
				}

				$order = $this->shipment->getCollection()->getOrder();

				if($order)
				{
					$this->arResult['ORDER_ID'] = $order->getId();
				}
			}
			else
			{
				$this->addError(self::COMPONENT_ERROR_EMPTY_ORDER_ID);
				$this->showErrors();
				return;
			}
		}
		else
		{
			$order = Order\Order::load($this->arResult['ORDER_ID']);
		}

		if(!$order)
		{
			$this->addError(new Main\Error(Loc::getMessage('CRM_ORDER_SD_ORDER_NOT_FOUND')));
			$this->showErrors();
			return;
		}

		$shipments = $order->getShipmentCollection();

		if ($this->mode === ComponentMode::CREATION)
		{
			$this->shipment = $shipments->createItem();
		}
		elseif(!$this->shipment)
		{
			$this->shipment = $shipments->getItemById($this->entityID);

			if(!$this->shipment)
			{
				$this->addError(new Main\Error(Loc::getMessage('CRM_ORDER_SD_SHIPMENT_NOT_FOUND')));
				$this->showErrors();
				return;
			}
		}

		$this->arResult['SITE_ID'] = $order->getSiteId();
		$this->prepareEntityData();

		//region GUID
		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID'])
			? $this->arParams['GUID'] : "order_shipment_{$this->entityID}_details";

		$this->arResult['EDITOR_CONFIG_ID'] = isset($this->arParams['EDITOR_CONFIG_ID'])
			? $this->arParams['EDITOR_CONFIG_ID'] : 'order_shipment_details';
		//endregion

		if($this->entityData['STATUS_ID'] <> '' )
			$this->arResult['PROGRESS_SEMANTICS'] = Order\OrderStatus::getStatusSemantics($this->entityData['STATUS_ID']);
		else
			$this->arResult['PROGRESS_SEMANTICS'] = '';

		$this->arResult['ENABLE_PROGRESS_CHANGE'] = $this->mode !== ComponentMode::VIEW;

		//region Entity Info
		$this->arResult['ENTITY_INFO'] = array(
			'ENTITY_ID' => $this->entityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::OrderShipment,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::OrderShipmentName,
			'TITLE' => $this->entityData['TITLE'],
			'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::OrderShipment, $this->entityID, false),
		);
		//endregion

		//region Page title
		if($this->mode === ComponentMode::CREATION)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_ORDER_SHIPMENT_CREATION_PAGE_TITLE'));
		}
		elseif($this->mode === ComponentMode::COPING)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_ORDER_SHIPMENT_COPY_PAGE_TITLE'));
		}
		elseif(!empty($this->entityData['TITLE']))
		{
			$APPLICATION->SetTitle($this->entityData['TITLE']);
		}
		//endregion

		//region Fields
		$this->prepareFieldInfos();
		//endregion

		//region Config
		$this->arResult['ENTITY_CONFIG'] = array(
			array(
				'name' => 'main',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT'),
				'type' => 'section',
				'elements' => array(
					array('name' => 'DELIVERY_LOGO'),
					array('name' => 'DELIVERY_ID'),
					array('name' => 'PRICE_DELIVERY_WITH_CURRENCY'),
					array('name' => 'PRICE_DELIVERY_CALCULATED_WITH_CURRENCY'),
					array('name' => 'ALLOW_DELIVERY'),
					array('name' => 'DEDUCTED'),
					array('name' => 'TRACKING_NUMBER'),
					array('name' => 'COMMENTS'),
					array('name' => 'DISCOUNTS'),
					array('name' => 'EXTRA_SERVICES_DATA')
				)
			),
			array(
				'name' => 'properties',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_PROPERTIES'),
				'type' => 'section',
				'data' => array(
					'showButtonPanel' => false
				),
				'elements' => 	array(
					array('name' => 'PROPERTIES')
				)
			),
		);

		//endregion

		//region CONTROLLERS
		$this->arResult['ENTITY_CONTROLLERS'] = array(
			array(
				"name" => "ORDER_SHIPMENT_CONTROLLER",
				"type" => "order_shipment_controller",
				"config" => array(
					"editorId" => $this->arResult['PRODUCT_EDITOR_ID'],
					"serviceUrl" => '/bitrix/components/bitrix/crm.order.shipment.details/ajax.php',
					"productDataFieldName" => $this->arResult['PRODUCT_DATA_FIELD_NAME'],
					"orderId" => $this->arResult['ORDER_ID'],
				)
			)
		);
		//endregion

		$this->arResult['PRODUCT_COMPONENT_DATA'] = array(
			'template' => $_REQUEST['product_html'] == 'y' ? '.html': '.default',
			'params' => array(
				'INTERNAL_FILTER' => array('SHIPMENT_ID' => $this->entityID),
				'PATH_TO_ORDER_SHIPMENT_PRODUCT_LIST' => '/bitrix/components/bitrix/crm.order.shipment.product.list/class.php?&shipmentId='.$this->shipment->getId().'&'.bitrix_sessid_get(),
				'SHIPMENT_ID' => $this->shipment->getId(),
				'ORDER_ID' => $this->arResult['ORDER_ID']
			)
		);

		//region Tabs
		$this->arResult['TABS'] = array();

		$productComponentData = $this->arResult['PRODUCT_COMPONENT_DATA'];
		$productComponentData['signedParameters'] = \CCrmInstantEditorHelper::signComponentParams(
			(array)$productComponentData['params'],
			'crm.order.shipment.product.list'
		);
		unset($productComponentData['params']);

		$this->arResult['TABS'][] = array(
			'id' => 'tab_products',
			'name' => Loc::getMessage('CRM_ORDER_SHIPMENT_PRODUCT_LIST'),
			'loader' => array(
				'serviceUrl' => '/bitrix/components/bitrix/crm.order.shipment.product.list/lazyload.ajax.php?&shipmentId='.$this->shipment->getId().'&'.bitrix_sessid_get(),
				'componentData' => $productComponentData,
			)
		);

		if($this->entityID > 0)
		{
			$this->arResult['TABS'][] = array(
				'id' => 'tab_tree',
				'name' => Loc::getMessage('CRM_ORDER_SHIPMENT_TAB_TREE'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.entity.tree/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '.default',
						'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
							'ENTITY_ID' => $this->entityID,
							'ENTITY_TYPE_NAME' => CCrmOwnerType::OrderShipmentName,
						], 'crm.entity.tree')
					)
				)
			);
			$this->arResult['TABS'][] = $this->getEventTabParams();
		}
		else
		{
			$this->arResult['TABS'][] = $this->getEventTabParams();
		}
		//endregion

		//region WAIT TARGET DATES
		$this->arResult['WAIT_TARGET_DATES'] = [];
		if ($this->userType)
		{
			$userFields = $this->userType->GetFields();
			foreach($userFields as $userField)
			{
				if($userField['USER_TYPE_ID'] === 'date' && $userField['MULTIPLE'] !== 'Y')
				{
					$this->arResult['WAIT_TARGET_DATES'][] = [
						'name' => $userField['FIELD_NAME'],
						'caption' => $userField['EDIT_FORM_LABEL'] ?? $userField['FIELD_NAME']
					];
				}
			}
		}
		//endregion

		//region VIEW EVENT
		if($this->entityID > 0 && \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isViewEventEnabled())
		{
			CCrmEvent::RegisterViewEvent(CCrmOwnerType::OrderShipment, $this->entityID, $this->userID);
		}
		//endregion

		if (Bitrix\Main\Loader::includeModule('pull'))
		{
			\CPullWatch::Add($this->userID, 'CRM_ENTITY_ORDER_SHIPMENT');
			\CPullWatch::Add($this->userID, 'SALE_DELIVERY_SERVICE');
			\CPullWatch::Add($this->userID, 'SALE_DELIVERY_REQUEST');
		}

		$this->includeComponentTemplate();
	}
	protected function prepareFieldInfos()
	{
		if(isset($this->arResult['ENTITY_FIELDS']))
		{
			return $this->arResult['ENTITY_FIELDS'];
		}

		//region Disabled Statuses
		$disabledStatusIDs = array();
		$statusList = Order\DeliveryStatus::getListInCrmFormat();

		$allStatuses = array();
		foreach ($statusList as $status)
		{
			$allStatuses[$status['STATUS_ID']] = $status['NAME'];
		}

		$statusSelectorPermissionType = ($this->mode === ComponentMode::CREATION ||
			$this->mode === ComponentMode::COPING) ? EntityPermissionType::CREATE : EntityPermissionType::UPDATE;

		foreach(array_keys($allStatuses) as $statusID)
		{
			if($this->mode === ComponentMode::VIEW)
			{
				$disabledStatusIDs[] = $statusID;
			}
			else
			{
				if(!\Bitrix\Crm\Order\Permissions\Order::checkStatusPermission($statusID, $statusSelectorPermissionType, $this->userPermissions))
				{
					$disabledStatusIDs[] = $statusID;
				}
			}
		}
		//endregion

		$this->arResult['SHIPMENT_PROPERTIES'] = $this->prepareProperties(
			$this->shipment->getPropertyCollection(),
			Order\ShipmentProperty::class,
			$this->shipment->getPersonTypeId(),
			($this->shipment->getId() === 0)
		);

		$this->arResult['ENTITY_FIELDS'] = array(
			array(
				'name' => 'ID',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_FIELD_ID'),
				'type' => 'text',
				'editable' => false
			),
			array(
				'name' => 'ORDER_ID',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_ORDER_ID'),
				'type' => 'hidden',
				'editable' => false
			),
			array(
				'name' => 'ACCOUNT_NUMBER',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_ACCOUNT_NUMBER'),
				'type' => 'text',
				'editable' => false
			),
			array(
				'name' => 'DELIVERY_LOGO',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_DELIVERY_LOGO'),
				'type' => 'image',
				'editable' => false,
				'data' => array('showUrl' => 'DELIVERY_LOGO')
			),
			array(
				'name' => 'DATE_INSERT',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_FIELD_DATE_INSERT'),
				'type' => 'datetime',
				'editable' => false,
				'data' => array('enableTime' => false)
			),
			array(
				'name' => 'PRICE_DELIVERY_WITH_CURRENCY',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_PRICE_DELIVERY_WITH_CURRENCY'),
				'type' => 'money',
				'editable' => true,
				'data' => array(
					'affectedFields' => array('CURRENCY', 'PRICE_DELIVERY'),
					'currency' => array(
						'name' => 'CURRENCY',
						'items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems())
					),
					'amount' => 'PRICE_DELIVERY',
					'formatted' => 'FORMATTED_PRICE_DELIVERY',
					'formattedWithCurrency' => 'FORMATTED_PRICE_DELIVERY_WITH_CURRENCY'
				)
			),
			array(
				'name' => 'PRICE_DELIVERY_CALCULATED_WITH_CURRENCY',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_PRICE_DELIVERY_CALCULATED_WITH_CURRENCY'),
				'type' => 'calculated_delivery_price',
				'editable' => false,
				'data' => array(
					'affectedFields' => array('CURRENCY', 'PRICE_DELIVERY_CALCULATED'),
					'currency' => array(
						'name' => 'CURRENCY',
						'items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems())
					),
					'amount' => 'PRICE_DELIVERY_CALCULATED',
					'formatted' => 'FORMATTED_PRICE_DELIVERY_CALCULATED',
					'formattedWithCurrency' => 'FORMATTED_PRICE_DELIVERY_CALCULATED_WITH_CURRENCY'
				)
			),
			array(
				'name' => 'DISCOUNT_PRICE_WITH_CURRENCY',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_DISCOUNT_PRICE_WITH_CURRENCY'),
				'type' => 'money',
				'editable' => false,
				'data' => array(
					'affectedFields' => array('CURRENCY', 'DISCOUNT_PRICE'),
					'currency' => array(
						'name' => 'CURRENCY',
						'items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems())
					),
					'amount' => 'DISCOUNT_PRICE',
					'formatted' => 'FORMATTED_DISCOUNT_PRICE',
					'formattedWithCurrency' => 'FORMATTED_DISCOUNT_PRICE_WITH_CURRENCY'
				)
			),
			array(
				'name' => 'BASE_PRICE_DELIVERY_WITH_CURRENCY',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_BASE_PRICE_DELIVERY_WITH_CURRENCY'),
				'type' => 'money',
				'editable' => false,
				'data' => array(
					'affectedFields' => array('CURRENCY', 'BASE_PRICE_DELIVERY'),
					'currency' => array(
						'name' => 'CURRENCY',
						'items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems())
					),
					'amount' => 'BASE_PRICE_DELIVERY',
					'formatted' => 'FORMATTED_BASE_PRICE_DELIVERY',
					'formattedWithCurrency' => 'FORMATTED_BASE_PRICE_DELIVERY_WITH_CURRENCY'
				)
			),
			array(
				'name' => 'CUSTOM_PRICE_DELIVERY',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_CUSTOM_PRICE_DELIVERY'),
				'type' => 'hidden',
				'editable' => false
			),
			array(
				'name' => 'ALLOW_DELIVERY',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_ALLOW_DELIVERY'),
				'type' => 'boolean',
				'editable' => true
			),
			array(
				'name' => 'DATE_ALLOW_DELIVERY',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_ALLOW_DELIVERY'),
				'type' => 'datetime',
				'editable' => false,
				'data' => array('enableTime' => false)
			),

			array(
				'name' => 'EMP_ALLOW_DELIVERY',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_EMP_ALLOW_DELIVERY'),
				'type' => 'user',
				'editable' => false,
				'data' => array(
					'enableEditInView' => true,
					'formated' => 'EMP_ALLOW_DELIVERY_FORMATTED_NAME',
					'position' => 'EMP_ALLOW_DELIVERY_WORK_POSITION',
					'photoUrl' => 'EMP_ALLOW_DELIVERY_PHOTO_URL',
					'showUrl' => 'PATH_TO_EMP_ALLOW_DELIVERY_USER',
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE']
				)
			),
			array(
				'name' => 'DEDUCTED',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_DEDUCTED'),
				'type' => 'boolean',
				'editable' => true
			),
			array(
				'name' => 'DATE_DEDUCTED',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_DATE_DEDUCTED'),
				'type' => 'datetime',
				'editable' => false,
				'data' => array('enableTime' => false)
			),
			array(
				'name' => 'EMP_DEDUCTED',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_EMP_DEDUCTED'),
				'type' => 'user',
				'editable' => false,
				'data' => array(
					'enableEditInView' => true,
					'formated' => 'EMP_DEDUCTED_FORMATTED_NAME',
					'position' => 'EMP_DEDUCTED_WORK_POSITION',
					'photoUrl' => 'EMP_DEDUCTED_PHOTO_URL',
					'showUrl' => 'PATH_TO_EMP_DEDUCTED_USER',
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE']
				)
			),
			array(
				'name' => 'REASON_UNDO_DEDUCTED',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_REASON_UNDO_DEDUCTED'),
				'type' => 'text',
				'editable' => false
			),
			array(
				'name' => 'DELIVERY_DOC_NUM',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_DELIVERY_DOC_NUM'),
				'type' => 'text',
				'editable' => true
			),
			array(
				'name' => 'DELIVERY_DOC_DATE',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_DELIVERY_DOC_DATE'),
				'type' => 'datetime',
				'editable' => true,
				'data' => array('enableTime' => false)
			),
			array(
				'name' => 'TRACKING_NUMBER',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_TRACKING_NUMBER'),
				'type' => 'text',
				'editable' => true
			),
			array(
				'name' => 'TRACKING_STATUS',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_TRACKING_STATUS'),
				'type' => 'custom',
				'editable' => false,
				'data' => [
					'view' => 'TRACKING_STATUS_VIEW'
				]
			),
			array(
				'name' => 'TRACKING_DESCRIPTION',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_TRACKING_DESCRIPTION'),
				'editable' => false,
				'type' => 'html'
			),
			// todo: create checking
			array(
				'name' => 'TRACKING_LAST_CHECK',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_TRACKING_LAST_CHECK'),
				'type' => 'datetime',
				'editable' => false,
				'data' => array('enableTime' => false)
			),
			array(
				'name' => 'TRACKING_LAST_CHANGE',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_TRACKING_LAST_CHANGE'),
				'type' => 'datetime',
				'editable' => false,
				'data' => array('enableTime' => false)
			),
			array(
				'name' => 'XML_ID',
				'title' => 'XML_ID',
				'type' => 'text',
				'editable' => true
			),
			array(
				'name' => 'MARKED',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_MARKED'),
				'type' => 'boolean',
				'editable' => false
			),
			array(
				'name' => 'DATE_MARKED',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_DATE_MARKED'),
				'type' => 'datetime',
				'editable' => false,
				'data' => array('enableTime' => true)
			),
			array(
				'name' => 'EMP_MARKED',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_EMP_MARKED'),
				'type' => 'user',
				'editable' => false,
				'data' => array(
					'enableEditInView' => true,
					'formated' => 'EMP_MARKED_FORMATTED_NAME',
					'position' => 'EMP_MARKED_WORK_POSITION',
					'photoUrl' => 'EMP_MARKED_PHOTO_URL',
					'showUrl' => 'PATH_TO_EMP_MARKED_USER',
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE']
				)
			),
			array(
				'name' => 'REASON_MARKED',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_REASON_MARKED'),
				'type' => 'text',
				'editable' => false
			),
			array(
				'name' => 'RESPONSIBLE_ID',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_FIELD_RESPONSIBLE_ID'),
				'type' => 'user',
				'editable' => true,
				'data' => array(
					'enableEditInView' => true,
					'formated' => 'RESPONSIBLE_FORMATTED_NAME',
					'position' => 'RESPONSIBLE_WORK_POSITION',
					'photoUrl' => 'RESPONSIBLE_PHOTO_URL',
					'showUrl' => 'PATH_TO_RESPONSIBLE_USER',
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE']
				)
			),
			array(
				'name' => 'EMP_RESPONSIBLE',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_EMP_RESPONSIBLE'),
				'type' => 'user',
				'editable' => true,
				'data' => array(
					'enableEditInView' => true,
					'formated' => 'EMP_RESPONSIBLE_FORMATTED_NAME',
					'position' => 'EMP_RESPONSIBLE_WORK_POSITION',
					'photoUrl' => 'EMP_RESPONSIBLE_PHOTO_URL',
					'showUrl' => 'PATH_TO_EMP_RESPONSIBLE_USER',
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE']
				)
			),
			array(
				'name' => 'DATE_RESPONSIBLE_ID',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_DATE_RESPONSIBLE_ID'),
				'type' => 'datetime',
				'editable' => false,
				'data' => array('enableTime' => true)
			),
			array(
				'name' => 'COMMENTS',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_COMMENTS'),
				'type' => 'text',
				'editable' => true
			),
			array(
				'name' => 'DELIVERY_ID',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_DELIVERY_SERVICE'),
				'type' => 'delivery_selector',
				'editable' => true
			),
			array(
				'name' => 'DISCOUNTS',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_DISCOUNTS'),
				'type' => 'custom',
				'editable' => false,
				'data' => array(
					'view' => 'DISCOUNTS_VIEW'
				)
			),
			array(
				'name' => 'EXTRA_SERVICES_DATA',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_EXTRA_SERVICES'),
				'type' => 'shipment_extra_services',
				'editable' => true,
			)
		);

		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'PROPERTIES',
			'type' => 'order_property_wrapper',
			'transferable' => false,
			'editable' => true,
			'isDragEnabled' => $this->userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'),
			'elements' => [],
			'sortedElements' => [
				'active' => is_array($this->arResult['SHIPMENT_PROPERTIES']["ACTIVE"]) ? $this->arResult['SHIPMENT_PROPERTIES']["ACTIVE"] : [],
				'hidden' => is_array($this->arResult['SHIPMENT_PROPERTIES']["HIDDEN"]) ? $this->arResult['SHIPMENT_PROPERTIES']["HIDDEN"] : [],
			],
			'data' => [
				'entityType' => 'shipment',
			],
		);

		if($this->entityData['EXTRA_SERVICES_DATA'])
		{
			$this->arResult['ENTITY_FIELDS'][] = array(
				'name' => 'EXTRA_SERVICES_DATA',
				'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_EXTRA_SERVICES'),
				'type' => 'shipment_extra_services',
				'editable' => true,
			);
		}

		$this->arResult['ENTITY_FIELDS'] = array_merge(
			$this->arResult['ENTITY_FIELDS'],
			array_values($this->userFieldInfos)
		);

		return $this->arResult['ENTITY_FIELDS'];
	}

	public function prepareEntityData()
	{
		if($this->entityData)
			return $this->entityData;

		$this->entityData = $this->shipment->getFieldValues();

		$properties = $this->getPropertyEntityData($this->shipment->getPropertyCollection());
		$this->entityData = array_merge($this->entityData, $properties);

		if ($this->mode === ComponentMode::CREATION)
		{
			$this->entityData['ACCOUNT_NUMBER'] = AccountNumberGenerator::generateForShipment($this->shipment);
		}
		//HACK: Removing time from DATE_INSERT because of 'datetime' type (see CCrmQuote::GetFields)
		if(isset($this->entityData['DATE_INSERT']))
			$this->entityData['DATE_INSERT'] = \CCrmComponentHelper::TrimZeroTime($this->entityData['DATE_INSERT']);

		if(!isset($this->entityData['CURRENCY']) || $this->entityData['CURRENCY'] === '')
			$this->entityData['CURRENCY'] = \CCrmCurrency::GetBaseCurrencyID();

		$this->entityData['STATUS_CONTROL'] = CCrmViewHelper::RenderOrderShipmentStatusControl(
			array(
				'PREFIX' => "SHIPMENT_PROGRESS_BAR_". $this->shipment->getId(),
				'ENTITY_ID' =>  $this->shipment->getId(),
				'CURRENT_ID' => $this->entityData['STATUS_ID'],
				'SERVICE_URL' => '/bitrix/components/bitrix/crm.order.shipment.list/list.ajax.php',
				'READ_ONLY' => false
			)
		);

		//region DELIVERY_SERVICE
		$currentDeliveryService = null;
		$logo = '/bitrix/images/sale/logo-default-d.gif';

		if (
			(int)$this->entityData['DELIVERY_ID'] > 0
			&& $currentDeliveryService = \Bitrix\Sale\Delivery\Services\Manager::getObjectById($this->entityData['DELIVERY_ID'])
		)
		{
			$this->entityData['DELIVERY_SERVICE_NAME'] = htmlspecialcharsbx($currentDeliveryService->getNameWithParent());
			$logoFileId = $currentDeliveryService->getLogotip();

			if ((int)$logoFileId > 0)
			{
				$logoData = \CFile::ResizeImageGet(
					$logoFileId,
					array('width' => 200, 'height' => 80)
				);

				$logo = $logoData['src'];
			}

			$restrictResult = Delivery\Restrictions\Manager::checkService($this->entityData['DELIVERY_ID'], $this->shipment, Delivery\Restrictions\Manager::MODE_MANAGER);
			if(($restrictResult !== Delivery\Restrictions\Manager::SEVERITY_NONE)
				|| (!$currentDeliveryService->isCompatible($this->shipment)))
			{
				$this->addError(new Main\Error(Loc::getMessage('CRM_ORDER_ERROR_SHIPMENT_SERVICE_RESTRICTED')));
			}
		}
		else
		{
			$this->entityData['DELIVERY_SERVICE_NAME'] = '';
		}

		$this->entityData['DELIVERY_SERVICES_LIST'] = \Bitrix\Crm\Order\Manager::getDeliveryServicesList($this->shipment);
		$this->entityData['DELIVERY_PROFILES_LIST'] = \Bitrix\Crm\Order\Manager::getDeliveryProfiles($this->shipment->getDeliveryId(), $this->entityData['DELIVERY_SERVICES_LIST']);

		$deliveryId = 0;
		$profileId = 0;

		if(isset($this->entityData['DELIVERY_SERVICES_LIST'][$this->entityData['DELIVERY_ID']]))
		{
			$deliveryId = $this->entityData['DELIVERY_ID'];
		}

		if($deliveryId <= 0)
		{
			foreach($this->entityData['DELIVERY_SERVICES_LIST'] as $delivery)
			{
				if(isset($delivery['ITEMS']))
				{
					foreach($delivery['ITEMS'] as $item)
					{
						if($item['ID'] == $this->entityData['DELIVERY_ID'])
						{
							$deliveryId = $this->entityData['DELIVERY_ID'];
							break 2;
						}
					}
				}
			}
		}

		if($deliveryId <= 0 && isset($this->entityData['DELIVERY_PROFILES_LIST'][$this->entityData['DELIVERY_ID']]))
		{
			$profileId = $this->entityData['DELIVERY_PROFILES_LIST'][$this->entityData['DELIVERY_ID']]['ID'];
			$profile = \Bitrix\Sale\Delivery\Services\Manager::getById($profileId);
			$deliveryId = $profile['PARENT_ID'];
		}

		$this->entityData['DELIVERY_LOGO'] = $logo;
		$this->entityData['DELIVERY_SELECTOR_DELIVERY_ID'] = $deliveryId;
		$this->entityData['DELIVERY_SELECTOR_PROFILE_ID'] = $profileId;
		$this->entityData['DELIVERY_STORE_ID'] = $this->shipment->getStoreId();


		$this->entityData['DELIVERY_STORES_LIST'] =
			\Bitrix\Sale\Helpers\Admin\Blocks\OrderShipment::getStoresList(
				$this->entityData['DELIVERY_ID'],
				$this->entityData['DELIVERY_STORE_ID']
		);

		if(!empty($this->entityData['DELIVERY_STORES_LIST']))
		{
			$this->entityData['DELIVERY_STORES_LIST'] =
				[0 => ['ID' => 0, 'TITLE' => Loc::getMessage('CRM_ORDER_SD_NOT_CHOSEN')]] +
				$this->entityData['DELIVERY_STORES_LIST'];
		}

		if(isset($this->entityData['DELIVERY_STORES_LIST'][$this->entityData['DELIVERY_STORE_ID']]['TITLE']))
		{
			$this->entityData['DELIVERY_STORE_TITLE'] = htmlspecialcharsbx(
				$this->entityData['DELIVERY_STORES_LIST'][$this->entityData['DELIVERY_STORE_ID']]['TITLE']
			);
		}

		if(isset($this->entityData['DELIVERY_STORES_LIST'][$this->entityData['DELIVERY_STORE_ID']]['ADDRESS']))
		{
			$this->entityData['DELIVERY_STORE_ADDRESS'] = htmlspecialcharsbx(
				$this->entityData['DELIVERY_STORES_LIST'][$this->entityData['DELIVERY_STORE_ID']]['ADDRESS']
			);
		}

		//endregion

		$this->entityData['FORMATED_TITLE_WITH_DATE_INSERT'] = Loc::getMessage(
			'CRM_ORDER_SHIPMENT_SUBTITLE_MASK',
			array(
				'#ID#' => $this->entityData['ID'],
				'#DATE_INSERT#' => 	CCrmComponentHelper::TrimDateTimeString(
					ConvertTimeStamp(
						MakeTimeStamp(
							$this->entityData['DATE_INSERT'],
							'SHORT',
							SITE_ID
						)
					)
				)
			)
		);

		$calcPrice = $this->shipment->calculateDelivery();

		if($this->shipment->getId() <= 0 && $this->entityData['CUSTOM_PRICE_DELIVERY'] != 'Y')
		{
			$this->entityData['PRICE_DELIVERY'] = $calcPrice->getPrice();
		}

		//region PRICE_DELIVERY & Currency
		$this->entityData['FORMATTED_PRICE_DELIVERY_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['PRICE_DELIVERY'],
			$this->entityData['CURRENCY'],
			''
		);
		$this->entityData['FORMATTED_PRICE_DELIVERY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['PRICE_DELIVERY'],
			$this->entityData['CURRENCY'],
			'#'
		);
		//endregion

		//region PRICE_DELIVERY_CALCULATED & Currency

		$calcPrice = $this->shipment->calculateDelivery();

		if(!$calcPrice->isSuccess())
		{
			$this->entityData['ERRORS'] = $calcPrice->getErrorMessages();
		}

		$this->entityData['PRICE_DELIVERY_CALCULATED'] = $calcPrice->getPrice();

		$this->entityData['FORMATTED_PRICE_DELIVERY_CALCULATED_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['PRICE_DELIVERY_CALCULATED'],
			$this->entityData['CURRENCY'],
			''
		);
		$this->entityData['FORMATTED_PRICE_DELIVERY_CALCULATED'] = \CCrmCurrency::MoneyToString(
			$this->entityData['PRICE_DELIVERY_CALCULATED'],
			$this->entityData['CURRENCY'],
			'#'
		);
		//endregion

		//region DISCOUNT_PRICE & Currency
		$this->entityData['FORMATTED_DISCOUNT_PRICE_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['DISCOUNT_PRICE'],
			$this->entityData['CURRENCY'],
			''
		);
		$this->entityData['FORMATTED_DISCOUNT_PRICE'] = \CCrmCurrency::MoneyToString(
			$this->entityData['DISCOUNT_PRICE'],
			$this->entityData['CURRENCY'],
			'#'
		);
		//endregion

		//region BASE_PRICE_DELIVERY & Currency
		$this->entityData['FORMATTED_BASE_PRICE_DELIVERY_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['BASE_PRICE_DELIVERY'],
			$this->entityData['CURRENCY'],
			''
		);
		$this->entityData['FORMATTED_BASE_PRICE_DELIVERY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['BASE_PRICE_DELIVERY'],
			$this->entityData['CURRENCY'],
			'#'
		);
		//endregion

		$this->addUserDataToEntity('RESPONSIBLE');
		$this->addUserDataToEntity('EMP_ALLOW_DELIVERY');
		$this->addUserDataToEntity('EMP_DEDUCTED');
		$this->addUserDataToEntity('EMP_MARKED');
		$this->addUserDataToEntity('EMP_RESPONSIBLE');

		$title = Loc::getMessage(
			'CRM_ORDER_SHIPMENT_TITLE2',
			array(
				'#ACCOUNT_NUMBER#' => $this->entityData['ACCOUNT_NUMBER']
			));
		$this->entityData['TITLE'] = $title;
		$this->entityData['SITE_ID'] = $this->arResult['SITE_ID'];

		/** @var Order\Order $order */
		$order = $this->shipment->getCollection()->getOrder();
		$discounts = \Bitrix\Sale\Helpers\Admin\OrderEdit::getDiscountsApplyResult($order, false);

		if($orderDiscounts = $order->getDiscount())
		{
			$shipmentIds = $order->getDiscount()->getShipmentsIds();

			if(in_array($this->shipment->getId(), $shipmentIds))
			{
				//$this->entityData['DISCOUNTS_EDIT'] = 'DISCOUNTS EDIT HERE';

				$html = $this->getDiscountsViewHtml($discounts);

				if($html <> '')
				{
					$this->entityData['DISCOUNTS_VIEW'] = $html;
				}
			}
		}

		if($this->entityData['DELIVERY_ID'] > 0)
		{
			$extraServiceManager = new \Bitrix\Sale\Delivery\ExtraServices\Manager($this->entityData['DELIVERY_ID']);
			$extraServiceManager->setOperationCurrency($this->entityData['CURRENCY']);
			$extraServiceManager->setValues($this->shipment->getExtraServices());
			$extraService = $extraServiceManager->getItems();

			if($extraService)
			{
				$this->entityData['EXTRA_SERVICES_DATA'] = $this->getExtraServices(
					$extraService,
					$this->shipment
				);
			}
		}

		$this->entityData['TRACKING_STATUS_DATA'] = [
			'TRACKING_STATUS_NAME' =>
				Delivery\Tracking\Manager::getInstance()::getStatusName(
					(int)$this->entityData['TRACKING_STATUS']
				)
		];

		$this->entityData['TRACKING_STATUS_VIEW'] = '';

		$personTypes = \Bitrix\Crm\Order\PersonType::load($this->arResult['SITE_ID']);
		if(empty($this->entityData['PERSON_TYPE_ID']))
		{
			$this->entityData['PERSON_TYPE_ID'] = key($personTypes);
		}

		return ($this->arResult['ENTITY_DATA'] = $this->entityData);
	}

	public function getExtraServices($extraService, Order\Shipment $shipment)
	{
		$result = [];

		foreach ($extraService as $itemId => $item)
		{
			$viewHtml = $item->getViewControl();
			$editHtml = '';

			if($item->canManagerEditValue())
			{
				$editHtml = $item->getEditControl('EXTRA_SERVICES['.(int)$itemId.']');
			}

			if($price = $item->getPriceShipment($shipment))
			{
				$price = \CCrmCurrency::MoneyToString(
					floatval($price),
					$item->getOperatingCurrency(),
					''
				);
			}

			if($cost = $item->getCostShipment($shipment))
			{
				$cost = \CCrmCurrency::MoneyToString(
					floatval($cost),
					$item->getOperatingCurrency(),
					''
				);
			}

			$result[] = [
				'NAME' => htmlspecialcharsbx($item->getName()),
				'EDIT_HTML' => $editHtml,
				'VIEW_HTML' => $viewHtml,
				'PRICE' => $price ? $price : '',
				'COST' => $cost ? $cost : '',
			];
		}

		return $result;
	}

	protected function getDiscountsViewHtml($discounts)
	{
		if(!is_array($discounts['RESULT']['DELIVERY']))
		{
			return '';
		}

		$result = '';

		foreach($discounts['RESULT']['DELIVERY'] as $item)
		{
			$checked = $item['APPLY'] == 'Y' ? ' checked' : '';
			$result .= '<p><input type="checkbox" name="DISCOUNTS[DELIVERY]['.$item['DISCOUNT_ID'].']" value="Y" disabled'.$checked.'> ';

			if(!empty($item['DESCR']))
			{
				if(is_array($item['DESCR']))
				{
					$result .= implode(', ', $item['DESCR']);
				}
				else
				{
					$result .= $item['DESCR'];
				}
			}
			else
			{
				$result .= Loc::getMessage('CRM_ORDER_SD_UNKNOWN_DISCOUNT');
			}

			$itemParams = $discounts['DISCOUNT_LIST'][$item['DISCOUNT_ID']];
			$name = htmlspecialcharsbx($itemParams['NAME']);

			if($itemParams['EDIT_PAGE_URL'])
			{
				$name ='<a href="'.$this->prepareAdminLink($itemParams['EDIT_PAGE_URL']).'">'.$name.'</a>';
			}

			$result .= ' '.$name .'</p>';
		}

		return $result;
	}

	protected function prepareAdminLink($url)
	{
		return str_replace(
			[".php","/bitrix/admin/"],
			["/", "/shop/settings/"],
			$url
		);
	}

	protected function addUserDataToEntity($entityPrefix)
	{
		$userId = isset($this->entityData[$entityPrefix.'_ID']) ? (int)$this->entityData[$entityPrefix.'_ID'] : 0;

		if($userId <= 0)
			return;

		$user = self::getUser($this->entityData[$entityPrefix.'_ID']);

		if(is_array($user))
		{
			$this->entityData[$entityPrefix.'_LOGIN'] = $user['LOGIN'];
			$this->entityData[$entityPrefix.'_NAME'] = isset($user['NAME']) ? $user['NAME'] : '';
			$this->entityData[$entityPrefix.'_SECOND_NAME'] = isset($user['SECOND_NAME']) ? $user['SECOND_NAME'] : '';
			$this->entityData[$entityPrefix.'_LAST_NAME'] = isset($user['LAST_NAME']) ? $user['LAST_NAME'] : '';
			$this->entityData[$entityPrefix.'_PERSONAL_PHOTO'] = isset($user['PERSONAL_PHOTO']) ? $user['PERSONAL_PHOTO'] : '';
		}

		$this->entityData[$entityPrefix.'_FORMATTED_NAME'] =
			\CUser::FormatName(
				$this->arResult['NAME_TEMPLATE'],
				array(
					'LOGIN' => $this->entityData[$entityPrefix.'_LOGIN'],
					'NAME' => $this->entityData[$entityPrefix.'_NAME'],
					'LAST_NAME' => $this->entityData[$entityPrefix.'_LAST_NAME'],
					'SECOND_NAME' => $this->entityData[$entityPrefix.'_SECOND_NAME']
				),
				true,
				false
			);

		$photoId = isset($this->entityData[$entityPrefix.'_PERSONAL_PHOTO'])
			? (int)$this->entityData[$entityPrefix.'_PERSONAL_PHOTO'] : 0;

		if($photoId > 0)
		{
			$file = new CFile();
			$fileInfo = $file->ResizeImageGet(
				$photoId,
				array('width' => 60, 'height'=> 60),
				BX_RESIZE_IMAGE_EXACT
			);
			if(is_array($fileInfo) && isset($fileInfo['src']))
			{
				$this->entityData[$entityPrefix.'_PHOTO_URL'] = $fileInfo['src'];
			}
		}

		$this->entityData['PATH_TO_'.$entityPrefix.'_USER'] = CComponentEngine::MakePathFromTemplate(
			$this->arResult['PATH_TO_USER_PROFILE'],
			array('user_id' => $userId)
		);
	}

	protected function getEventTabParams(): array
	{
		return CCrmComponentHelper::getEventTabParams(
			$this->entityID,
			Loc::getMessage('CRM_ORDER_SHIPMENT_TAB_EVENT'),
			CCrmOwnerType::OrderShipmentName,
			$this->arResult
		);
	}
}
