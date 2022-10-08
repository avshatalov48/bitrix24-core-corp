<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\AccountNumberGenerator;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Component\ComponentError;
use Bitrix\Crm\Component\EntityDetails\ComponentMode;

if(!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

Loc::loadMessages(__FILE__);

class CCrmOrderPaymentDetailsComponent extends Crm\Component\EntityDetails\BaseComponent
{
	const COMPONENT_ERROR_EMPTY_ORDER_ID = -0x3;

	/** @var \Bitrix\Sale\Payment */
	private  $orderPayment = null;


	public function getEntityTypeID()
	{
		return \CCrmOwnerType::OrderPayment;
	}

	protected function getUserFieldEntityID()
	{
		return Crm\Order\Payment::getUfId();
	}

	protected function checkIfEntityExists()
	{
		if ($this->entityID > 0)
		{
			$dbRes = Crm\Order\Payment::getList(array('filter' => array('=ID' => $this->entityID)));
			return $dbRes->fetch();
		}

		return false;
	}

	protected function getErrorMessage($error)
	{
		if($error === ComponentError::ENTITY_NOT_FOUND)
		{
			return Loc::getMessage('CRM_ORDER_PAYMENT_NOT_FOUND');
		}
		if($error === self::COMPONENT_ERROR_EMPTY_ORDER_ID)
		{
			return Loc::getMessage('CRM_ORDER_PAYMENT_EMPTY_ORDER_ID');
		}
		return ComponentError::getMessage($error);
	}

	public function initializeParams(array $params)
	{
		foreach($params as $k => $v)
		{
			if($k === 'EXTRAS')
			{
				$this->arParams[$k] = $v;
			}

			if(!is_string($v))
			{
				continue;
			}

			if($k === 'PATH_TO_PRODUCT_SHOW')
			{
				$this->arResult['PATH_TO_PRODUCT_SHOW'] = $this->arParams['PATH_TO_PRODUCT_SHOW'] = $v;
			}
			elseif($k === 'PATH_TO_USER_PROFILE')
			{
				$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] = $v;
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

	public function loadOrderPayment()
	{
		if($this->entityID > 0 && $this->orderPayment === null)
		{
			$this->orderPayment = Crm\Order\Manager::getPaymentObject($this->entityID);
		}
	}

	public function setPayment(Crm\Order\Payment $payment)
	{
		$this->orderPayment = $payment;
		$this->arResult['ORDER_ID'] = $payment->getOrderId();
	}

	public function executeComponent()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		//region Params
		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID']) ? (int)$this->arParams['~ENTITY_ID'] : 0;

		$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] =
			CrmCheckPath('PATH_TO_USER_PROFILE', $this->arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');

		$this->arResult['DATA_FIELD_NAME'] = 'ORDER_PAYMENT_DATA';

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams['NAME_TEMPLATE']);

		$this->arResult['PATH_TO_ORDER_PAYMENT_SHOW'] = CrmCheckPath(
			'PATH_TO_ORDER_PAYMENT_SHOW',
			$this->arParams['PATH_TO_ORDER_PAYMENT_SHOW'],
			$APPLICATION->GetCurPage().'?order_id=#payment_id#&show'
		);
		$this->arResult['PATH_TO_ORDER_PAYMENT_EDIT'] = CrmCheckPath(
			'PATH_TO_ORDER_PAYMENT_EDIT',
			$this->arParams['PATH_TO_ORDER_PAYMENT_EDIT'],
			$APPLICATION->GetCurPage().'?payment_id=#order_id#&edit'
		);

		$this->arResult['PATH_TO_ORDER_CHECK_SHOW'] = CrmCheckPath(
			'PATH_TO_ORDER_CHECK_DETAILS',
			$this->arParams['PATH_TO_ORDER_CHECK_DETAILS'],
			$APPLICATION->GetCurPage().'?check_id=#check_id#&check&show'
		);

		$this->arResult['PATH_TO_ORDER_CHECK_EDIT'] = CrmCheckPath(
			'PATH_TO_ORDER_CHECK_EDIT',
			'/shop/orders/check/details/#check_id#/?init_mode=edit', null
		);

		$this->arResult['PATH_TO_ORDER_CHECK_DELETE'] = CrmCheckPath(
			'PATH_TO_ORDER_CHECK_DELETE',
			'/shop/orders/check/details/#check_id#/?action=delete', null
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
		$this->arResult['PRODUCT_DATA_FIELD_NAME'] = 'ORDER_PRODUCT_DATA';
		$this->arResult['PRODUCT_EDITOR_ID'] = 'order_product_editor';

		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::OrderPaymentName.'_'.$this->arResult['ENTITY_ID'];
		$this->arResult['CONTEXT_PARAMS'] = array(
			'PATH_TO_PRODUCT_SHOW' => $this->arResult['PATH_TO_PRODUCT_SHOW'],
			'PATH_TO_USER_PROFILE' => $this->arResult['PATH_TO_USER_PROFILE'],
			'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE'],
			'EXTRAS' => isset($this->arParams['EXTRAS']) ? $this->arParams['EXTRAS'] : false
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
		$this->prepareOrderPayment();
		if (empty($this->orderPayment))
		{
			return;
		}
		$this->prepareEntityData();

		//region GUID
		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID'])
			? $this->arParams['GUID'] : "order_payment_{$this->entityID}_details";

		$this->arResult['EDITOR_CONFIG_ID'] = isset($this->arParams['EDITOR_CONFIG_ID'])
			? $this->arParams['EDITOR_CONFIG_ID'] : 'order_payment_details';
		//endregion

		$this->arResult['ENABLE_PROGRESS_CHANGE'] = false;

		//region Entity Info
		$this->arResult['ENTITY_INFO'] = array(
			'ENTITY_ID' => $this->entityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::OrderPayment,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::OrderPaymentName,
			'TITLE' => $this->entityData['TITLE'],
			'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::OrderPayment, $this->entityID, false),
		);
		//endregion

		//region Page title
		if($this->mode === ComponentMode::CREATION)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_ORDER_PAYMENT_CREATION_PAGE_TITLE'));
		}
		elseif($this->mode === ComponentMode::COPING)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CRM_ORDER_PAYMENT_COPY_PAGE_TITLE'));
		}
		elseif(!empty($this->entityData['TITLE']))
		{
			$APPLICATION->SetTitle($this->entityData['TITLE']);
		}
		//endregion

		//region Fields
		$this->prepareFieldInfos();
		//endregion

		$entityConfig = array(
			array(
				'name' => 'main',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_BLOCK_MAIN_TITLE'),
				'type' => 'section',
				'elements' => array(
					array('name' => 'PAY_SYSTEM_ID'),
					array('name' => 'SUM_WITH_CURRENCY'),
					array('name' => 'STATUS'),
				)
			)
		);

		if ($this->mode !== ComponentMode::CREATION)
		{
			$entityConfig[] = [
				'name' => 'ps_status',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_PS_STATUS_TITLE'),
				'type' => 'section',
				'editable' => false,
				'elements' => 	[
					['name' => 'PS_STATUS'],
					['name' => 'PS_INVOICE_ID'],
					['name' => 'PS_STATUS_CODE'],
					['name' => 'PS_STATUS_DESCRIPTION'],
					['name' => 'PS_STATUS_MESSAGE'],
					['name' => 'PS_SUM'],
					['name' => 'PS_CURRENCY'],
					['name' => 'PS_RESPONSE_DATE'],
				]
			];

			$entityConfig[] = array(
				'name' => 'checks',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_BLOCK_CHECK_TITLE'),
				'type' => 'section',
				'editable' => false,
				'elements' => 	array(
					array('name' => 'CHECK'),
				)
			);

			$entityConfig[] = array(
				'name' => 'vouchers',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_BLOCK_VOUCHERS_TITLE'),
				'type' => 'section',
				'elements' => array(
					array('name' => 'VOUCHERS')
				)
			);
		}
		//region Config
		$this->arResult['ENTITY_CONFIG'] = $entityConfig;
		//endregion

		$this->arResult['ENTITY_CONTROLLERS'] = array(
			array(
				"name" => "ORDER_PAYMENT_CONTROLLER",
				"type" => "order_payment_controller",
				"config" => array(
					"editorId" => $this->arResult['PRODUCT_EDITOR_ID'],
					"dataFieldName" => $this->arResult['DATA_FIELD_NAME'],
					"serviceUrl" => '/bitrix/components/bitrix/crm.order.payment.details/ajax.php',
				)
			)
		);

		//region Tabs
		$this->arResult['TABS'] = array();

		if($this->entityID > 0)
		{
			$licensePrefix = Main\Loader::IncludeModule("bitrix24") ? \CBitrix24::getLicensePrefix() : "";
			if (!Main\ModuleManager::isModuleInstalled("bitrix24") || in_array($licensePrefix, array("ru", "ua")))
			{
				$this->arResult['TABS'][] = array(
					'id' => 'tab_check',
					'name' => Loc::getMessage('CRM_ORDER_PAYMENT_TAB_CHECK'),
					'loader' => array(
						'serviceUrl' => '/bitrix/components/bitrix/crm.order.check.list/lazyload.ajax.php?&site'.SITE_ID.'&'.bitrix_sessid_get(),
						'componentData' => array(
							'template' => '',
							'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
								'ENABLE_TOOLBAR' => true,
								'CHECK_COUNT' => '20',
								'OWNER_ID' => $this->entityID,
								'OWNER_TYPE' => CCrmOwnerType::OrderPayment,
								'PATH_TO_ORDER_CHECK_SHOW' => $this->arResult['PATH_TO_ORDER_CHECK_SHOW'],
								'PATH_TO_ORDER_CHECK_EDIT' => $this->arResult['PATH_TO_ORDER_CHECK_EDIT'],
								'GRID_ID_SUFFIX' => 'CHECK_DETAILS',
								'TAB_ID' => 'tab_check',
								'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE']
							], 'crm.order.check.list')
						)
					)
				);
			}

			$this->arResult['TABS'][] = array(
				'id' => 'tab_tree',
				'name' => Loc::getMessage('CRM_ORDER_PAYMENT_TAB_TREE'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.entity.tree/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '.default',
						'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
							'ENTITY_ID' => $this->entityID,
							'ENTITY_TYPE_NAME' => CCrmOwnerType::OrderPaymentName,
						], 'crm.entity.tree')
					)
				)
			);

			$this->arResult['TABS'][] = $this->getEventTabParams();
		}
		else
		{
			$this->arResult['TABS'][] = array(
				'id' => 'tab_check',
				'name' => Loc::getMessage('CRM_ORDER_PAYMENT_TAB_CHECK'),
				'enabled' => false
			);

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
			CCrmEvent::RegisterViewEvent(CCrmOwnerType::OrderPayment, $this->entityID, $this->userID);
		}
		//endregion

		$this->includeComponentTemplate();
	}

	public function prepareOrderPayment()
	{
		if(!$this->tryToDetectMode())
		{
			$this->showErrors();
			return;
		}

		if ($this->mode === ComponentMode::CREATION)
		{
			$order = null;

			if ((int)$this->arParams['EXTRAS']['ORDER_ID'] > 0)
			{
				$this->arResult['ORDER_ID'] = (int)$this->arParams['EXTRAS']['ORDER_ID'];
				$order = Crm\Order\Order::load($this->arResult['ORDER_ID']);
				if ($order)
				{
					$this->orderPayment = $order->getPaymentCollection()->createItem();
				}
			}

			if (!$order)
			{
				$this->addError(self::COMPONENT_ERROR_EMPTY_ORDER_ID);
				$this->showErrors();
				return;
			}
		}
		else
		{
			$this->orderPayment = Crm\Order\Manager::getPaymentObject($this->entityID);
			$this->arResult['ORDER_ID'] = $this->orderPayment->getOrderId();
		}
	}
	protected function prepareFieldInfos()
	{
		if(isset($this->arResult['ENTITY_FIELDS']))
		{
			return $this->arResult['ENTITY_FIELDS'];
		}

		//region Client primary entity
		$companyId = isset($this->entityData['COMPANY_ID']) ? (int)$this->entityData['COMPANY_ID'] : 0;
		if(isset($this->entityData['CONTACT_BINDINGS']))
		{
			$contactBindings = $this->entityData['CONTACT_BINDINGS'];
		}
		elseif($this->entityID > 0)
		{
			$dbRes = Crm\Order\ContactCompanyCollection::getList([
				'select' => [
					'ENTITY_ID', 'SORT', 'ROLE_ID', 'IS_PRIMARY'
				],
				'filter' => [
					'=ENTITY_TYPE_ID' => \CCrmOwnerType::Contact,
					'=ORDER_ID' => (int)$this->orderPayment->getOrderId()
				],
				'order' => ['SORT' => 'ASC']
			]);

			$contactBindings = [];
			while ($data = $dbRes->fetch())
			{
				$contactBindings[] = [
					'CONTACT_ID' => (int)$data['ENTITY_ID'],
					'SORT' => (int)$data['SORT'],
					'ROLE_ID' => (int)$data['ROLE_ID'],
					'IS_PRIMARY' => $data['IS_PRIMARY']
				];
			}
		}
		elseif(isset($this->entityData['CONTACT_ID']))
		{
			//For backward compatibility
			$contactBindings = EntityBinding::prepareEntityBindings(
				CCrmOwnerType::Order,
				array($this->entityData['CONTACT_ID'])
			);
		}
		else
		{
			$contactBindings = array();
		}
		//endregion

		$primaryEntityTypeName = ($companyId > 0 || empty($contactBindings))
			? CCrmOwnerType::CompanyName : CCrmOwnerType::ContactName;

		$this->arResult['ENTITY_FIELDS'] = array(
			array(
				'name' => 'ID',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_ID'),
				'type' => 'text',
				'editable' => false
			),
			array(
				'name' => 'ORDER_ID',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_ORDER_ID'),
				'type' => 'hidden',
				'transferable' => false,
				'editable' => false
			),
			array(
				'name' => 'ACCOUNT_NUMBER',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_ACCOUNT_NUMBER'),
				'type' => 'text',
				'editable' => false
			),
			[
				'name' => 'PS_STATUS',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_PS_STATUS'),
				'type' => 'text',
				'editable' => false
			],
			[
				'name' => 'PS_INVOICE_ID',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_PS_INVOICE'),
				'type' => 'text',
				'editable' => false
			],
			[
				'name' => 'PS_STATUS_CODE',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_PS_STATUS_CODE'),
				'type' => 'text',
				'editable' => false
			],
			[
				'name' => 'PS_STATUS_DESCRIPTION',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_PS_STATUS_DESCRIPTION'),
				'type' => 'text',
				'editable' => false
			],
			[
				'name' => 'PS_STATUS_MESSAGE',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_PS_STATUS_MESSAGE'),
				'type' => 'text',
				'editable' => false
			],
			[
				'name' => 'PS_SUM',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_PS_SUM'),
				'type' => 'text',
				'editable' => false
			],
			[
				'name' => 'PS_CURRENCY',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_PS_CURRENCY'),
				'type' => 'text',
				'editable' => false
			],
			[
				'name' => 'PS_RESPONSE_DATE',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_PS_DATE'),
				'type' => 'datetime',
				'editable' => false
			],
			array(
				'name' => 'PAY_SYSTEM_ID',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_SYSTEM'),
				'type' => 'pay_system_selector',
				'editable' => true,
				'data' => array(
					'items' =>  \CCrmInstantEditorHelper::PrepareListOptions($this->entityData['PAY_SYSTEM_LIST']),
					'isHtml' => true
				),
				'required' => true
			),
			array(
				'name' => 'DATE_BILL',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_DATE_BILL'),
				'type' => 'datetime',
				'editable' => false,
				'data' => array('enableTime' => false)
			),
			array(
				'name' => 'STATUS',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_STATUS'),
				'type' => 'payment_status',
				'editable' => true,
				'transferable' => false
			),
			array(
				'name' => 'SUM_WITH_CURRENCY',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_SUM_WITH_CURRENCY'),
				'type' => 'money',
				'editable' => true,
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
				'name' => 'XML_ID',
				'title' => 'XML_ID',
				'type' => 'text',
				'editable' => true
			),
			array(
				'name' => 'RESPONSIBLE_ID',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_RESPONSIBLE_ID'),
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
				'name' => 'EMP_RESPONSIBLE_ID',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_EMP_RESPONSIBLE'),
				'type' => 'user',
				'editable' => false,
				'data' => array(
					'enableEditInView' => false,
					'formated' => 'EMP_RESPONSIBLE_FORMATTED_NAME',
					'position' => 'EMP_RESPONSIBLE_WORK_POSITION',
					'photoUrl' => 'EMP_RESPONSIBLE_PHOTO_URL',
					'showUrl' => 'PATH_TO_EMP_RESPONSIBLE_USER',
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE']
				)
			),
			array(
				'name' => 'DATE_RESPONSIBLE_ID',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_DATE_RESPONSIBLE_ID'),
				'type' => 'datetime',
				'editable' => false,
				'data' => array('enableTime' => true)
			),
			array(
				'name' => 'COMMENTS',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_COMMENT'),
				'type' => 'text',
				'editable' => true
			),
			array(
				'name' => 'CLIENT',
				'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_CLIENT'),
				'type' => 'client',
				'editable' => true,
				'data' => array(
					'map' => array(
						'primaryEntityType' => 'CLIENT_PRIMARY_ENTITY_TYPE',
						'primaryEntityId' => 'CLIENT_PRIMARY_ENTITY_ID',
						'secondaryEntityType' => 'CLIENT_SECONDARY_ENTITY_TYPE',
						'secondaryEntityIds' => 'CLIENT_SECONDARY_ENTITY_IDS',
						'unboundSecondaryEntityIds' => 'CLIENT_UBOUND_SECONDARY_ENTITY_IDS',
						'boundSecondaryEntityIds' => 'CLIENT_BOUND_SECONDARY_ENTITY_IDS'
					),
					'info' => 'CLIENT_INFO',
					'primaryEntityTypeName' => $primaryEntityTypeName,
					'secondaryEntityTypeName' => CCrmOwnerType::ContactName,
					'secondaryEntityLegend' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_CONTACT_LEGEND'),
					'loaders' => array(
						'primary' => array(
							CCrmOwnerType::CompanyName => array(
								'action' => 'GET_CLIENT_INFO',
								'url' => '/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get()
							),
							CCrmOwnerType::ContactName => array(
								'action' => 'GET_CLIENT_INFO',
								'url' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get()
							)
						),
						'secondary' => array(
							CCrmOwnerType::CompanyName => array(
								'action' => 'GET_SECONDARY_ENTITY_INFOS',
								'url' => '/bitrix/components/bitrix/crm.order.details/ajax.php?'.bitrix_sessid_get()
							)
						)
					)
				)
			)
		);

		if ($this->mode !== ComponentMode::CREATION)
		{
			$voucherElements = array(
				array(
					'name' => 'PAY_VOUCHER',
					'type' => 'order_subsection',
					'elements' => array(
						array(
							'name' => 'PAY_VOUCHER_NUM',
							'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_VOUCHER_NUM'),
							'type' => 'text',
							'editable' => true,
							'enabledMenu' => false,
							'transferable' => false
						),
						array(
							'name' => 'PAY_VOUCHER_DATE',
							'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_VOUCHER_DATE'),
							'type' => 'datetime',
							'editable' => true,
							'enabledMenu' => false,
							'transferable' => false,
							'data' => array('enableTime' => false)
						)
					)
				)
			);


			if (empty($this->entityData['PAY_VOUCHER_NUM']))
			{
				$voucherElements[] = array(
					'name' => 'PAY_VOUCHER_LINK',
					'title' => ' ',
					'type' => 'custom',
					'editable' => false,
					'enabledMenu' => false,
					'data' => array(
						'view' => "PAY_VOUCHER_LINK",
						'edit' => ''
					),
					'transferable' => false
				);
			}

			if ($this->entityData['PAID'] === 'Y' || !empty($this->entityData['PAY_RETURN_NUM']))
			{
				$voucherElements[] = array(
					'name' => 'PAY_RETURN',
					'type' => 'order_subsection',
					'elements' => array(
						array(
							'name' => 'PAY_RETURN_NUM',
							'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_RETURN_NUM'),
							'type' => 'text',
							'editable' => true,
							'enabledMenu' => false,
							'transferable' => false
						),
						array(
							'name' => 'PAY_RETURN_DATE',
							'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_RETURN_DATE'),
							'type' => 'datetime',
							'editable' => true,
							'enabledMenu' => false,
							'transferable' => false,
							'data' => array('enableTime' => false)
						),
						array(
							'name' => 'PAY_RETURN_COMMENT',
							'title' => Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_RETURN_COMMENT'),
							'type' => 'text',
							'editable' => true,
							'enabledMenu' => false,
							'transferable' => false
						)
					)
				);
			}

			if ($this->entityData['PAID'] === 'Y' && empty($this->entityData['PAY_RETURN_NUM']))
			{
				$voucherElements[] = array(
					'name' => 'PAY_RETURN_LINK',
					'title' => ' ',
					'type' => 'custom',
					'editable' => false,
					'enabledMenu' => false,
					'data' => array(
						'view' => "PAY_RETURN_LINK",
						'edit' => ''
					),
					'transferable' => false
				);
			}

			$this->arResult['ENTITY_FIELDS'][] = array(
				'name' => 'CHECK',
				'type' => 'payment_check',
				'editable' => false,
				'transferable' => false,
			);

			$this->arResult['ENTITY_FIELDS'][] = array(
				'name' => 'VOUCHERS',
				'type' => 'order_subsection',
				'elements' => $voucherElements
			);
		}

		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'USER_BUDGET',
			'title' => Loc::getMessage('CRM_ORDER_PAYMENT_BUDGET'),
			'type' => 'money',
			'editable' => false,
			'data' => array(
				'affectedFields' => array('CURRENCY', 'BUDGET'),
				'currency' => array(
					'name' => 'BUDGET',
					'items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems())
				),
				'amount' => 'BUDGET',
				'formatted' => 'FORMATTED_BUDGET',
				'formattedWithCurrency' => 'FORMATTED_BUDGET_WITH_CURRENCY'
			)
		);

		$this->arResult['ENTITY_FIELDS'] = array_merge(
			$this->arResult['ENTITY_FIELDS'],
			array_values($this->userFieldInfos)
		);

		return $this->arResult['ENTITY_FIELDS'];
	}

	public function prepareEntityData()
	{
		if ($this->entityData)
		{
			return $this->entityData;
		}

		$this->entityData = array();
		$order = \Bitrix\Crm\Order\Order::load($this->arResult['ORDER_ID']);

		if ($this->mode === ComponentMode::CREATION)
		{
			//region Default Dates
			$dateInsert = time() + \CTimeZone::GetOffset();
			$time = localtime($dateInsert, true);
			$dateInsert -= $time['tm_sec'] + 60 * $time['tm_min'] + 3600 * $time['tm_hour'];
			$this->entityData['DATE_BILL'] = ConvertTimeStamp($dateInsert, 'SHORT', SITE_ID);

			//region Default Responsible
			if($this->userID > 0)
			{
				$this->entityData['RESPONSIBLE_ID'] = $this->userID;
			}
			//endregion

			$externalCompanyID = $this->request->get('company_id');
			if($externalCompanyID > 0)
			{
				$this->entityData['COMPANY_ID'] = $externalCompanyID;
			}

			$externalContactID = $this->request->get('contact_id');
			if($externalContactID > 0)
			{
				$this->entityData['CONTACT_ID'] = $externalContactID;
			}

			$paymentCollection = $order->getPaymentCollection();
			$paymentSum = $paymentCollection->getSum();
			$orderPrice = $order->getPrice();

			$deltaSum = $orderPrice - $paymentSum;

			if ($deltaSum > 0)
			{
				$this->entityData['SUM'] = $deltaSum;
			}

			$currency = \CCrmCurrency::GetBaseCurrencyID();

			if (empty($orderCurrency))
			{
				$currency = $order->getCurrency();
			}

			$this->entityData['CURRENCY'] = $currency;

			$this->entityData['ACCOUNT_NUMBER'] = AccountNumberGenerator::generateForPayment($this->orderPayment);
		}
		else
		{
			$this->entityData = $this->orderPayment->getFieldValues();

			if (isset($this->entityData['DATE_BILL']))
			{
				$this->entityData['DATE_BILL'] = \CCrmComponentHelper::TrimZeroTime($this->entityData['DATE_BILL']);
			}

			if (isset($this->entityData['DATE_PAID']))
			{
				$this->entityData['DATE_PAID'] = \CCrmComponentHelper::TrimZeroTime($this->entityData['DATE_PAID']);
			}

			if (!isset($this->entityData['CURRENCY']) || $this->entityData['CURRENCY'] === '')
			{
				$this->entityData['CURRENCY'] = \CCrmCurrency::GetBaseCurrencyID();
			}

			$this->entityData['PAY_VOUCHER_LINK'] = $this->createVoucherLink(
				Crm\Order\Manager::ORDER_PAYMENT_DOCUMENT_TYPE_VOUCHER,
				Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_VOUCHER_LINK')
			);
			$this->entityData['PAY_RETURN_LINK'] = $this->createVoucherLink(
				Crm\Order\Manager::ORDER_PAYMENT_DOCUMENT_TYPE_RETURN,
				Loc::getMessage('CRM_ORDER_PAYMENT_FIELD_PAY_RETURN_LINK')
			);
		}

		$this->entityData['PAY_VOUCHER_URL'] = $this->getVoucherUrl(Crm\Order\Manager::ORDER_PAYMENT_DOCUMENT_TYPE_VOUCHER);
		$this->entityData['PAY_RETURN_URL'] = $this->getVoucherUrl(Crm\Order\Manager::ORDER_PAYMENT_DOCUMENT_TYPE_RETURN);
		$this->entityData['PAY_CANCEL_URL'] = $this->getVoucherUrl(Crm\Order\Manager::ORDER_PAYMENT_DOCUMENT_TYPE_CANCEL);
		$this->entityData['PAY_SYSTEM_LIST'] = array();
		$paySystemList = \Bitrix\Sale\PaySystem\Manager::getListWithRestrictions($this->orderPayment, \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::MODE_MANAGER);

		if (is_array($paySystemList))
		{
			foreach ($paySystemList as $paySystem)
			{
				if ($paySystem['LOGOTIP'] > 0)
				{
					$logo = CFile::GetFileArray($paySystem['LOGOTIP']);
				}
				else
				{
					$logo = '/bitrix/images/sale/sale_payments/'.$paySystem['ACTION_FILE'].'.png';

					if (!\Bitrix\Main\IO\File::isFileExists($_SERVER['DOCUMENT_ROOT'].$logo))
					{
						$logo = '/bitrix/images/sale/sale_payments/default.png';
					}
				}

				$logoFile = CFile::ShowImage($logo, 100, 30, "border=0", "", false);
				$value = htmlspecialcharsbx($paySystem['NAME'])." [".$paySystem['ID']."] ".$logoFile;
				$this->entityData['PAY_SYSTEM_LIST'][$paySystem['ID']] = $value;
			}
		}

		if ($paySystem = \Bitrix\Sale\PaySystem\Manager::getObjectById((int)$this->entityData['PAY_SYSTEM_ID']))
		{
			$this->entityData['PAY_SYSTEM_NAME'] = $paySystem->getField('NAME');
			$logoId = $paySystem->getField('LOGOTIP');
			if ((int)$logoId > 0)
			{
				$logo = CFile::GetPath($logoId);
			}
			else
			{
				$logo = '/bitrix/images/sale/sale_payments/'.$paySystem->getField('PSA_ACTION_FILE').'.png';
				if (!\Bitrix\Main\IO\File::isFileExists($_SERVER['DOCUMENT_ROOT'].$logo))
				{
					$logo = '/bitrix/images/sale/sale_payments/default.png';
				}
			}
			$this->entityData['PAY_SYSTEM_LOGO'] = $logo;
			$this->entityData['IS_REFUNDABLE'] = $paySystem->isRefundable() ? 'Y' : 'N';
		}

		$this->entityData['FORMATED_TITLE_WITH_DATE_BILL'] = Loc::getMessage(
			'CRM_ORDER_PAYMENT_SUBTITLE_MASK',
			array(
				'#ID#' => $this->entityData['ID'],
				'#DATE_INSERT#' => 	CCrmComponentHelper::TrimDateTimeString(
					ConvertTimeStamp(
						MakeTimeStamp(
							$this->entityData['DATE_BILL'],
							'SHORT'
						),
						SITE_ID
					)
				)
			)
		);
		//region PRICE_DELIVERY & Currency
		$this->entityData['FORMATTED_SUM_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['SUM'],
			$this->entityData['CURRENCY'],
			''
		);
		$this->entityData['FORMATTED_SUM'] = \CCrmCurrency::MoneyToString(
			$this->entityData['SUM'],
			$this->entityData['CURRENCY'],
			'#'
		);
		$this->entityData['STATUS'] = array(
			'datePaid' => $this->entityData['DATE_PAID'],
			'isPaid' => $this->entityData['PAID']
		);
		//endregion

		$this->addUserDataToEntity('RESPONSIBLE');

		if ($this->entityData['EMP_RESPONSIBLE_ID'])
		{
			$this->addUserDataToEntity('EMP_RESPONSIBLE');
		}

		$title = Loc::getMessage(
			'CRM_ORDER_PAYMENT_TITLE2',
			array(
				'#ACCOUNT_NUMBER#' => $this->entityData['ACCOUNT_NUMBER']
			));
		$this->entityData['TITLE'] = $title;
		$this->entityData['ORDER_ID'] = $this->arResult['ORDER_ID'];
		$this->entityData['CHECK'] = $this->getCheckEntityData();
		$this->entityData['INNER_PAY_SYSTEM_ID'] = \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId();

		//region USER_BUDGET
		$this->entityData['BUDGET'] = \Bitrix\Sale\Internals\UserBudgetPool::getUserBudget(
			$order->getUserId(),
			$this->entityData['CURRENCY']
		);

		$this->entityData['FORMATTED_BUDGET'] = \CCrmCurrency::MoneyToString(
			$this->entityData['BUDGET'],
			$this->entityData['CURRENCY'],
			'#'
		);

		$this->entityData['FORMATTED_BUDGET_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['BUDGET'],
			$this->entityData['CURRENCY'],
			''
		);
		//endregion

		return ($this->arResult['ENTITY_DATA'] = $this->entityData);
	}

	protected function getVoucherUrl($type)
	{
		return CHTTP::urlAddParams('/bitrix/components/bitrix/crm.order.payment.voucher/slider.ajax.php?'.bitrix_sessid_get(),
			array(
				'siteID' => SITE_ID,
				'paymentId' => $this->entityID,
				'paymentType' => (int)$type,
			)
		);
	}

	protected function createVoucherLink ($type, $text)
	{
		$voucherScript = "BX.Crm.Page.openSlider(\"".$this->getVoucherUrl($type)."\", { width: 500 }); return;";
		return "<a class='crm-entity-widget-content-block-edit-action-btn' onclick='{$voucherScript}'>".htmlspecialcharsbx($text)."</a>";
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

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\NotImplementedException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function getCheckEntityData()
	{
		$result = [
			'items' => []
		];

		if(Crm\Settings\LayoutSettings::getCurrent()->isSimpleTimeFormatEnabled())
		{
			$timeFormat = array(
				'tommorow' => 'tommorow',
				's' => 'sago',
				'i' => 'iago',
				'H3' => 'Hago',
				'today' => 'today',
				'yesterday' => 'yesterday',
				'-' => Main\Type\DateTime::convertFormatToPhp(FORMAT_DATE)
			);
		}
		else
		{
			$timeFormat = preg_replace('/:s$/', '', Main\Type\DateTime::convertFormatToPhp(FORMAT_DATETIME));
		}

		$params = [
			'filter' => [
				'ORDER_ID' => $this->arResult['ORDER_ID'],
				[
					'LOGIC' => 'OR',
					[
						'REF.ENTITY_ID' => $this->entityID,
						'REF.ENTITY_TYPE' => \Bitrix\Sale\Cashbox\Internals\CheckRelatedEntitiesTable::ENTITY_TYPE_PAYMENT
					],
					'PAYMENT_ID' =>  $this->entityID
				]
			],
			'runtime' => [
				new Main\Entity\ReferenceField(
					"REF",
					'Bitrix\Sale\Cashbox\Internals\CheckRelatedEntitiesTable',
					["=this.ID"=>"ref.CHECK_ID"],
					["join_type"=>"left"]
				)
			]
		];

		$checkIds = array();
		$checkTypeMap = \Bitrix\Sale\Cashbox\CheckManager::getCheckTypeMap();
		$cashboxList = \Bitrix\Sale\Cashbox\Manager::getListFromCache();
		$resultCheckData = \Bitrix\Sale\Cashbox\Internals\CashboxCheckTable::getList($params);

		while ($check = $resultCheckData->fetch())
		{
			/** @var \Bitrix\Sale\Cashbox\Check $checkClass */
			$checkClass = $checkTypeMap[$check['TYPE']];
			$cashboxId = $check['CASHBOX_ID'];
			$checkIds[] = $check['ID'];
			$check['DATE_CREATE'] = FormatDate($timeFormat, MakeTimeStamp($check['DATE_CREATE']));
			$checkDate = FormatDate(Main\Type\Date::getFormat(), MakeTimeStamp($check['DATE_CREATE']));
			$checkTitle = Loc::getMessage("CRM_ORDER_PAYMENT_CHECK_TITLE",array(
				"#ID#" => $check['ID'],
				"#DATE_CREATE#" => $checkDate,
			));
			$cashbox = null;
			$checkLink = '';

			if ($check['CASHBOX_ID'] > 0)
			{
				$cashbox = \Bitrix\Sale\Cashbox\Manager::getObjectById($check['CASHBOX_ID']);

				if ($cashbox && is_array($check['LINK_PARAMS']))
				{
					$link = $cashbox->getCheckLink($check['LINK_PARAMS']);

					if ($link)
					{
						$checkLink = '<a href="'.$link.'" target="_blank">'.Loc::getMessage('CRM_ORDER_PAYMENT_CHECK_LOOK').'</a>';
					}
				}
			}

			$result['items'][] = [
				'TITLE' => $checkTitle,
				'CHECK_TYPE' => class_exists($checkClass) ? $checkClass::getName() : '',
				'SUM_WITH_CURRENCY' => CCrmCurrency::MoneyToString($check['SUM'], $check['CURRENCY']),
				'LINK' => $checkLink,
				'CASHBOX_NAME' => htmlspecialcharsbx($cashboxList[$cashboxId]['NAME']),
				'STATUS_NAME' => Loc::getMessage('CRM_ORDER_PAYMENT_CASHBOX_STATUS_'.$check['STATUS']),
			] + $check;
		}

		if (!empty($checkIds))
		{
			$relatedDb = \Bitrix\Sale\Cashbox\Internals\CheckRelatedEntitiesTable::getList(array(
				'filter' => array('=CHECK_ID' => $checkIds)
			));

			while ($related = $relatedDb->fetch())
			{
				$type = null;
				if ($related['ENTITY_TYPE'] === \Bitrix\Sale\Cashbox\Internals\CheckRelatedEntitiesTable::ENTITY_TYPE_SHIPMENT)
				{
					$type = 'SHIPMENT';
				}
				elseif ($related['ENTITY_TYPE'] === \Bitrix\Sale\Cashbox\Internals\CheckRelatedEntitiesTable::ENTITY_TYPE_PAYMENT)
				{
					$type = 'PAYMENT';
				}
			}
		}

		return $result;
	}

	protected function getEventTabParams(): array
	{
		return CCrmComponentHelper::getEventTabParams(
			$this->entityID,
			Loc::getMessage('CRM_ORDER_PAYMENT_TAB_EVENT'),
			CCrmOwnerType::OrderPaymentName,
			$this->arResult
		);
	}
}
