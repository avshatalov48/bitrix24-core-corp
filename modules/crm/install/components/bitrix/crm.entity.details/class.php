<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
CModule::IncludeModule("crm");
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CCrmEntityPopupComponent extends CBitrixComponent
{
	/** @var string */
	private $guid = '';
	/** @var int */
	private $entityTypeID = CCrmOwnerType::Undefined;
	/** @var int */
	private $entityID = 0;
	/** @var array|null  */
	private $extras = null;
	/** @var array|null  */
	private $entityInfo = null;

	/** @var  CCrmPerms|null */
	private $userPermissions = null;
	/** @var bool */
	private $isPermitted = false;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();
	}

	public function executeComponent()
	{
		$this->entityTypeID = isset($this->arParams['~ENTITY_TYPE_ID'])
			? (int)$this->arParams['~ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
		$this->entityID = isset($this->arParams['~ENTITY_ID'])
			? (int)$this->arParams['~ENTITY_ID'] : 0;
		$this->extras = isset($this->arParams['~EXTRAS']) && is_array($this->arParams['~EXTRAS'])
			? $this->arParams['~EXTRAS'] : array();
		$this->entityInfo = isset($this->arParams['~ENTITY_INFO']) && is_array($this->arParams['~ENTITY_INFO'])
			? $this->arParams['~ENTITY_INFO'] : array();

		if(isset($this->arParams['~GUID']))
		{
			$this->guid = $this->arResult['~GUID'] = $this->arParams['~GUID'];
		}
		else
		{
			$this->guid = $this->arResult['~GUID'] = strtolower(CCrmOwnerType::ResolveName($this->entityTypeID)).'_'.$this->entityID;
		}

		$this->arResult['READ_ONLY'] = isset($this->arParams['~READ_ONLY'])
			&& $this->arParams['~READ_ONLY'] === true;

		$this->arResult['ENABLE_PROGRESS_BAR'] = isset($this->arParams['~ENABLE_PROGRESS_BAR'])
			&& $this->arParams['~ENABLE_PROGRESS_BAR'] === true;

		$this->arResult['ENABLE_PROGRESS_CHANGE'] = isset($this->arParams['~ENABLE_PROGRESS_CHANGE'])
			? (bool)$this->arParams['~ENABLE_PROGRESS_CHANGE'] : !$this->arResult['READ_ONLY'];

		$this->arResult['CAN_CONVERT'] = isset($this->arParams['~CAN_CONVERT'])
			? (bool)$this->arParams['~CAN_CONVERT'] : false;

		$this->arResult['CONVERSION_TYPE_ID'] = isset($this->arParams['~CONVERSION_TYPE_ID'])
			? $this->arParams['~CONVERSION_TYPE_ID'] : 0;

		$this->arResult['CONVERSION_SCHEME'] = isset($this->arParams['~CONVERSION_SCHEME'])
			? $this->arParams['~CONVERSION_SCHEME'] : array();

		$this->isPermitted = \Bitrix\Crm\Security\EntityAuthorization::checkReadPermission(
			$this->entityTypeID,
			$this->entityID,
			$this->userPermissions
		);

		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeID;
		$this->arResult['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName($this->entityTypeID);
		$this->arResult['ENTITY_ID'] = $this->entityID;
		$this->arResult['ENTITY_INFO'] = $this->entityInfo;
		$this->arResult['EXTRAS'] = $this->extras;

		$this->arResult['EDITOR'] = isset($this->arParams['~EDITOR']) && is_array($this->arParams['~EDITOR']) ? $this->arParams['~EDITOR'] : array();
		$this->arResult['TIMELINE'] = isset($this->arParams['~TIMELINE']) && is_array($this->arParams['~TIMELINE']) ? $this->arParams['~TIMELINE'] : array();
		$this->arResult['PROGRESS_BAR'] = isset($this->arParams['~PROGRESS_BAR']) && is_array($this->arParams['~PROGRESS_BAR']) ? $this->arParams['~PROGRESS_BAR'] : array();

		$this->arResult['IS_PERMITTED'] = $this->isPermitted;

		$this->arResult['TABS'] = isset($this->arParams['TABS']) && is_array($this->arParams['TABS'])
			? $this->arParams['TABS'] : array();

		// region rest placement
		$this->arResult['REST_USE'] = false;
		if(Main\Loader::includeModule('rest') && $this->arParams['REST_USE'] != 'N')
		{
			$this->arResult['REST_USE'] = true;
			\CJSCore::Init(array('applayout'));

			$placement = 'CRM_'.\CCrmOwnerType::ResolveName($this->entityTypeID).'_DETAIL_TAB';
			$placementHandlerList = \Bitrix\Rest\PlacementTable::getHandlersList($placement);

			if(count($placementHandlerList) > 0)
			{
				foreach($placementHandlerList as $placementHandler)
				{
					$this->arResult['TABS'][] = array(
						'id' => 'tab_rest_'.$placementHandler['ID'],
						'name' => strlen($placementHandler['TITLE']) > 0
							? $placementHandler['TITLE']
							: $placementHandler['APP_NAME'],
						'enabled' => true,
						'loader' => array(
							'serviceUrl' => '/bitrix/components/bitrix/app.layout/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
							'componentData' => array(
								'template' => '',
								'params' => array(
									'PLACEMENT' => $placement,
									'PLACEMENT_OPTIONS' => array(
										'ID' => $this->entityID,
									),
									'ID' => $placementHandler['APP_ID'],
									'PLACEMENT_ID' => $placementHandler['ID'],
								),
							)
						)
					);
				}

			}

			$this->arResult['REST_PLACEMENT_CONFIG'] = array('PLACEMENT' => $placement);
		}
		// endregion

		$initMode = $this->request->get('init_mode');
		if(!is_string($initMode))
		{
			$initMode = '';
		}
		else
		{
			$initMode = strtolower($initMode);
			if($initMode !== 'edit' && $initMode !== 'view')
			{
				$initMode = '';
			}
		}
		$this->arResult['INITIAL_MODE'] = $initMode !== '' ? $initMode : ($this->entityID > 0  ? 'view' : 'edit');

		$this->arResult['GUID'] = $this->guid;
		$this->arResult['ACTIVITY_EDITOR_ID'] = isset($this->arParams['~ACTIVITY_EDITOR_ID']) ? $this->arParams['~ACTIVITY_EDITOR_ID'] : '';
		$this->arResult['SERVICE_URL'] = isset($this->arParams['~SERVICE_URL']) ? $this->arParams['~SERVICE_URL'] : '';

		//$this->arResult['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $this->arParams['PATH_TO_DEAL_EDIT'], '');
		$this->arResult['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $this->arParams['PATH_TO_QUOTE_EDIT'], '');
		$this->arResult['PATH_TO_INVOICE_EDIT'] = CrmCheckPath('PATH_TO_INVOICE_EDIT', $this->arParams['PATH_TO_INVOICE_EDIT'], '');
		$this->arResult['PATH_TO_ORDER_EDIT'] = CrmCheckPath('PATH_TO_ORDER_EDIT', $this->arParams['PATH_TO_ORDER_EDIT'], '');
		$this->arResult['PATH_TO_ORDER_SHIPMENT_EDIT'] = CrmCheckPath('PATH_TO_ORDER_SHIPMENT_EDIT', $this->arParams['PATH_TO_ORDER_SHIPMENT_EDIT'], '');
		$this->arResult['PATH_TO_ORDER_PAYMENT_EDIT'] = CrmCheckPath('PATH_TO_ORDER_PAYMENT_EDIT', $this->arParams['PATH_TO_ORDER_PAYMENT_EDIT'], '');

		$this->arResult['ENTITY_CREATE_URLS'] = array(
			\CCrmOwnerType::DealName =>
				\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Deal, 0, false),
			\CCrmOwnerType::LeadName =>
				\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Lead, 0, false),
			\CCrmOwnerType::CompanyName =>
				\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Company, 0, false),
			\CCrmOwnerType::ContactName =>
				\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Contact, 0, false),
			\CCrmOwnerType::QuoteName =>
				\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Quote, 0, false),
			\CCrmOwnerType::InvoiceName =>
				\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Invoice, 0, false),
			\CCrmOwnerType::OrderName =>
				\CCrmOwnerType::GetEntityEditPath(\CCrmOwnerType::Order, 0, false),
			\CCrmOwnerType::OrderShipmentName =>
				CComponentEngine::MakePathFromTemplate($this->arResult['PATH_TO_ORDER_SHIPMENT_EDIT'], array('shipment_id' => 0)),
			\CCrmOwnerType::OrderPaymentName =>
				CComponentEngine::MakePathFromTemplate($this->arResult['PATH_TO_ORDER_PAYMENT_EDIT'], array('payment_id' => 0))
		);

		$this->arResult['ENTITY_LIST_URLS'] = array(
			\CCrmOwnerType::DealName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::Deal, false),
			\CCrmOwnerType::LeadName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::Lead, false),
			\CCrmOwnerType::CompanyName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::Company, false),
			\CCrmOwnerType::ContactName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::Contact, false),
			\CCrmOwnerType::QuoteName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::Quote, false),
			\CCrmOwnerType::InvoiceName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::Invoice, false),
			\CCrmOwnerType::OrderName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::Order, false),
			\CCrmOwnerType::OrderShipmentName =>
				\CCrmOwnerType::GetListUrl(\CCrmOwnerType::OrderShipment, false),
		);

		//region Deal Categories
		$this->arResult['DEAL_CATEGORY_ACCESS'] = array(
			'CREATE' => \CCrmDeal::GetPermittedToCreateCategoryIDs($this->userPermissions),
			'READ' => \CCrmDeal::GetPermittedToReadCategoryIDs($this->userPermissions),
			'UPDATE' => \CCrmDeal::GetPermittedToUpdateCategoryIDs($this->userPermissions)
		);
		//endregion

		$this->arResult['ANALYTIC_PARAMS'] = isset($this->arParams['~ANALYTIC_PARAMS']) && is_array($this->arParams['~ANALYTIC_PARAMS'])
			? $this->arParams['~ANALYTIC_PARAMS'] : array();

		$this->includeComponentTemplate();
	}
}