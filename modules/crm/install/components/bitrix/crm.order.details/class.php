<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Crm\Order;
use Bitrix\Crm\Binding;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\PaySystem;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Services;
use Bitrix\Crm\Component\ComponentError;
use Bitrix\Crm\Security\EntityPermissionType;
use Bitrix\Crm\Component\EntityDetails\ComponentMode;

if(!Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

Loc::loadMessages(__FILE__);

class CCrmOrderDetailsComponent extends Crm\Component\EntityDetails\BaseComponent
{
	/** @var Bitrix\Crm\Order\Order */
	private  $order = null;
	private  $profileId = null;
	private  $propertyMap = null;

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Order;
	}

	protected function getUserFieldEntityID()
	{
		return Order\Order::getUfId();
	}

	public function formatResultSettings()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID']) ? (int)$this->arParams['~ENTITY_ID'] : 0;

		$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] =
			CrmCheckPath('PATH_TO_USER_PROFILE', $this->arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');

		$this->arResult['PATH_TO_BUYER_PROFILE'] = $this->arParams['PATH_TO_BUYER_PROFILE'] =
			CrmCheckPath('PATH_TO_BUYER_PROFILE', $this->arParams['PATH_TO_BUYER_PROFILE'], '/shop/settings/sale_buyers_profile/?USER_ID=#user_id#&lang=' . LANGUAGE_ID);

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $this->arParams['NAME_TEMPLATE']);

		$this->arResult['PATH_TO_ORDER_CHECK_SHOW'] = CrmCheckPath(
			'PATH_TO_ORDER_CHECK_DETAILS',
			$this->arParams['PATH_TO_ORDER_CHECK_DETAILS'],
			$APPLICATION->GetCurPage() . '?check_id=#check_id#&check&show'
		);

		$this->arResult['PATH_TO_ORDER_CHECK_CHECK_STATUS'] = CrmCheckPath(
			'PATH_TO_ORDER_CHECK_DETAILS',
			$APPLICATION->GetCurPage() . '?check_id=#check_id#&action=check_status', null
		);

		$this->arResult['PATH_TO_ORDER_CHECK_DELETE'] = CrmCheckPath(
			'PATH_TO_ORDER_CHECK_DELETE',
			'/bitrix/components/bitrix/crm.order.check.list/lazyload.ajax.php?id=#check_id#&action=delete&site=' . $this->arResult['SITE_ID'] . '&' . bitrix_sessid_get(), null
		);

		$this->arResult['PATH_TO_ORDER_CHECK_EDIT'] = CrmCheckPath(
			'PATH_TO_ORDER_CHECK_EDIT',
			'/shop/orders/check/details/#check_id#/?init_mode=edit', null
		);

		$enableUfCreation = \CCrmAuthorizationHelper::CheckConfigurationUpdatePermission();
		$this->arResult['ENABLE_USER_FIELD_CREATION'] = $enableUfCreation;
		$this->arResult['USER_FIELD_ENTITY_ID'] = $this->getUserFieldEntityID();
		$this->arResult['USER_FIELD_CREATE_PAGE_URL'] = CCrmOwnerType::GetUserFieldEditUrl($this->arResult['USER_FIELD_ENTITY_ID'], 0);
		$this->arResult['USER_FIELD_CREATE_SIGNATURE'] = $enableUfCreation
			? $this->userFieldDispatcher->getCreateSignature(array('ENTITY_ID' => $this->arResult['USER_FIELD_ENTITY_ID']))
			: '';
		$this->arResult['ENABLE_TASK'] = IsModuleInstalled('tasks');
		$this->arResult['PRODUCT_DATA_FIELD_NAME'] = 'ORDER_PRODUCT_DATA';
		$this->arResult['PRODUCT_EDITOR_ID'] = 'order_product_editor';
		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::OrderName . '_' . $this->arResult['ENTITY_ID'];
		$this->arResult['CONTEXT_PARAMS'] = array(
			'PATH_TO_USER_PROFILE' => $this->arResult['PATH_TO_USER_PROFILE'],
			'PATH_TO_BUYER_PROFILE' => $this->arResult['PATH_TO_BUYER_PROFILE'],
			'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE']
		);

		$this->arResult['EXTERNAL_CONTEXT_ID'] = $this->request->get('external_context_id');
		if ($this->arResult['EXTERNAL_CONTEXT_ID'] === null) {
			$this->arResult['EXTERNAL_CONTEXT_ID'] = $this->request->get('external_context');
			if ($this->arResult['EXTERNAL_CONTEXT_ID'] === null) {
				$this->arResult['EXTERNAL_CONTEXT_ID'] = '';
			}
		}

		$this->arResult['ORIGIN_ID'] = $this->request->get('origin_id');
		if ($this->arResult['ORIGIN_ID'] === null) {
			$this->arResult['ORIGIN_ID'] = '';
		}
	}

	protected function getFileHandlerUrl()
	{
		return '/bitrix/components/bitrix/crm.order.details/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#';
	}

	protected function checkIfEntityExists()
	{
		if($this->entityID > 0)
		{
			$dbRes = Crm\Order\Order::getList(array(
				'filter' => array('=ID' => $this->entityID)
			));

			return (bool)$dbRes->fetch();
		}

		return false;
	}

	protected function getErrorMessage($error)
	{
		if($error === ComponentError::ENTITY_NOT_FOUND)
		{
			return Loc::getMessage('CRM_ORDER_NOT_FOUND');
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

			if($k === 'PATH_TO_USER_PROFILE')
			{
				$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] = $v;
			}
			elseif($k === 'PATH_TO_BUYER_PROFILE')
			{
				$this->arResult['PATH_TO_BUYER_PROFILE'] = $this->arParams['PATH_TO_BUYER_PROFILE'] = $v;
			}
			elseif($k === 'NAME_TEMPLATE')
			{
				$this->arResult['NAME_TEMPLATE'] = $this->arParams['NAME_TEMPLATE'] = $v;
			}
			elseif($k === 'ORDER_ID')
			{
				$this->arResult[$k] = $this->arParams[$k] = (int)$v;
			}
		}
	}

	public function obtainOrder()
	{
		if($this->order === null)
		{
			if($this->entityID > 0)
			{
				if ($this->mode !== ComponentMode::COPING)
				{
					$order = Order\Order::load($this->entityID);
				}
				else
				{
					$order = Order\Manager::copy($this->entityID);
				}
			}
			else
			{
				$userId = null;
				if ((int)$_REQUEST['USER_ID'] > 0)
				{
					$userId = (int)$_REQUEST['USER_ID'];
				}
				$siteId = !empty($_REQUEST['SITE_ID']) ? $_REQUEST['SITE_ID'] : SITE_ID;
				$order = \Bitrix\Crm\Order\Manager::createEmptyOrder($siteId, $userId);

				if ($userId > 0)
				{
					$personType = $order->getPersonTypeId();
					$resultProfileLoading = \Bitrix\Sale\OrderUserProperties::loadProfiles($userId, $personType);
					if ($resultProfileLoading->isSuccess())
					{
						$profiles = $resultProfileLoading->getData();
						if (!empty($profiles) && is_array($profiles))
						{
							$personTypeProfiles = $profiles[$personType];
							if (!empty($personTypeProfiles))
							{
								$this->profileId = key($personTypeProfiles);
								$currentProfile = current($personTypeProfiles);
								$values = $currentProfile['VALUES'];
								$propertyCollection = $order->getPropertyCollection();
								$propertyCollection->setValuesFromPost(
									['PROPERTIES' => $values],[]
								);
							}
						}

					}
				}
			}

			$this->setOrder($order);
		}

		return $this->order;
	}

	public function setOrder(Order\Order $order)
	{
		$this->order = $order;
		$this->arResult['SITE_ID'] = $this->order->getSiteId();
	}

	/** Main\Error[] $errors */
	protected function addErrors(array $errors)
	{
		/** @var Main\Error $error */
		foreach($errors as $error)
		{
			parent::addError($error->getMessage());
		}
	}

	public function executeComponent()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		//region Params
		$this->formatResultSettings();

		$this->setEntityID($this->arResult['ENTITY_ID']);

		if(!$this->tryToDetectMode())
		{
			$this->showErrors();
			return;
		}

		$this->obtainOrder();
		$this->prepareEntityData($this->mode);

		//region GUID
		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID'])
			? $this->arParams['GUID'] : "order_{$this->entityID}_details";

		$this->arResult['EDITOR_CONFIG_ID'] = isset($this->arParams['EDITOR_CONFIG_ID'])
			? $this->arParams['EDITOR_CONFIG_ID'] : 'order_details';
		//endregion

		$this->arResult['ENABLE_PROGRESS_CHANGE'] = $this->mode !== ComponentMode::VIEW;

		//region Entity Info
		$this->arResult['ENTITY_INFO'] = array(
			'ENTITY_ID' => $this->entityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::Order,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::OrderName,
			'TITLE' => $this->entityData['TITLE'],
			'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Order, $this->entityID, false),
		);
		//endregion

		$APPLICATION->SetTitle($this->entityData['TITLE']);
		$this->prepareFieldInfos();

		//region Config

		$mainConfig = 	array(
			'name' => 'main',
			'title' => Loc::getMessage('CRM_ORDER_SECTION_MAIN'),
			'type' => 'section',
			'elements' => array(
				array('name' => 'TRADING_PLATFORM'),
				array('name' => 'PERSON_TYPE_ID'),
				array('name' => 'PRICE_WITH_CURRENCY'),
				array('name' => 'DATE_INSERT'),
				array('name' => 'STATUS_ID')
			)
		);

		if($this->entityID > 0 && $this->mode !== ComponentMode::COPING)
		{
			$mainConfig['elements'][] = array('name' => 'ACCOUNT_NUMBER');
		}

		$this->arResult['ENTITY_CONFIG'] = array(
			$mainConfig,
			array(
				'name' => 'client',
				'title' => Loc::getMessage('CRM_ORDER_SECTION_CLIENT'),
				'type' => 'section',
				'elements' => array(
					array('name' => 'USER_ID'),
					array('name' => 'CLIENT')
				)
			),
			array(
				'name' => 'responsible',
				'title' => Loc::getMessage('CRM_ORDER_FIELD_RESPONSIBLE_ID'),
				'type' => 'section',
				'elements' =>
					array(
						array('name' => 'RESPONSIBLE_ID')
					)
			)
		);

		if($this->entityID > 0)
		{
			$this->arResult['ENTITY_CONFIG'][] =
				array(
					'name' => 'products',
					'title' => Loc::getMessage('CRM_ORDER_SECTION_PRODUCTS'),
					'type' => 'section',
					'elements' => array(
						array('name' => 'PRODUCT_ROW_SUMMARY')
					)
				);
		}

		$this->arResult['ENTITY_CONFIG'] = array_merge(
			$this->arResult['ENTITY_CONFIG'],
			array(
				array(
					'name' => 'properties',
					'title' => Loc::getMessage('CRM_ORDER_SECTION_PROPERTIES'),
					'type' => 'section',
					'data' => array(
						'showButtonPanel' => false
					),
					'elements' => 	array(
						array('name' => 'USER_PROFILE'),
						array('name' => 'PROPERTIES'),
					)
				),
				array(
					'name' => 'payment',
					'title' => Loc::getMessage('CRM_ORDER_SECTION_PAYMENT'),
					'type' => 'section',
					'data' => array(
						'showButtonPanel' => false,
						'enableToggling' =>  false
					),
					'elements' => 	array(
						array('name' => 'PAYMENT'),
					)
				),
				array(
					'name' => 'shipment',
					'title' => Loc::getMessage('CRM_ORDER_SECTION_SHIPMENT'),
					'type' => 'section',
					'data' => array(
						'showButtonPanel' => false,
						'enableToggling' =>  false,
					),
					'elements' => 	array(
						array('name' => 'SHIPMENT'),
					)
				)
			)
		);

		//region CONTROLLERS
		$this->arResult['ENTITY_CONTROLLERS'] = array(
			array(
				"name" => "ORDER_CONTROLLER",
				"type" => "order_controller",
				"config" => array(
					"editorId" => $this->arResult['PRODUCT_EDITOR_ID'],
					"serviceUrl" => '/bitrix/components/bitrix/crm.order.details/ajax.php',
					"dataFieldName" => $this->arResult['PRODUCT_DATA_FIELD_NAME']
				)
			)
		);
		//endregion

		//region Tabs
		$this->arResult['TABS'] = array();

		$this->arResult['PRODUCT_COMPONENT_DATA'] = array(
			'template' => '.default',
			'params' => array(
				'INTERNAL_FILTER' => array('ORDER_ID' => $this->entityID),
				'PATH_TO_ORDER_PRODUCT_LIST' => '/bitrix/components/bitrix/crm.order.product.list/class.php?&site='.$this->order->getSiteId().'&'.bitrix_sessid_get(),
				'ACTION_URL' => '/bitrix/components/bitrix/crm.order.product.list/lazyload.ajax.php?&site='.$this->order->getSiteId().'&'.bitrix_sessid_get(),
				'ORDER_ID' => $this->order->getId(),
				'SITE_ID' => $this->order->getSiteId()
			)
		);

		if ($this->mode !== ComponentMode::COPING && $this->mode !== ComponentMode::CREATION)
		{
			$this->arResult['TABS'][] = array(
				'id' => 'tab_products',
				'name' => Loc::getMessage('CRM_ORDER_TAB_PRODUCTS'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.order.product.list/lazyload.ajax.php?&site='.$this->order->getSiteId().'&'.bitrix_sessid_get(),
					'componentData' => $this->arResult['PRODUCT_COMPONENT_DATA']
				)
			);
		}
		else
		{
			ob_start();
			$componentParams['ORDER'] = $this->order;
			$componentParams['AJAX_MODE'] = 'N';
			$componentParams['AJAX_OPTION_JUMP'] = 'N';
			$componentParams['AJAX_OPTION_HISTORY'] = 'N';
			$APPLICATION->IncludeComponent('bitrix:crm.order.product.list',
				isset( $this->arResult['PRODUCT_COMPONENT_DATA']['template']) ?  $this->arResult['PRODUCT_COMPONENT_DATA']['template'] : '',
				$componentParams,
				false,
				array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
			);
			$html = ob_get_contents();
			ob_end_clean();

			$this->arResult['TABS'][] = array(
				'id' => 'tab_products',
				'name' => Loc::getMessage('CRM_ORDER_TAB_PRODUCTS'),
				'html' => $html
			);
		}

		if($this->entityID > 0 && $this->mode !== ComponentMode::COPING)
		{
			$this->arResult['TABS'][] = array(
				'id' => 'tab_payment',
				'name' => Loc::getMessage('CRM_ORDER_TAB_PAYMENT'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.order.payment.list/lazyload.ajax.php?&site='.$this->arResult['SITE_ID'].'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '.default',
						'params' => array(
							'INTERNAL_FILTER' => array('ORDER_ID' => $this->entityID),
							'ORDER_ID' => $this->order->getId(),
							'ENABLE_TOOLBAR' => true,
							'PATH_TO_ORDER_PAYMENT_LIST' => '/bitrix/components/bitrix/crm.order.payment.list/class.php?&site='.$this->arResult['SITE_ID'].'&'.bitrix_sessid_get()
						)
					)
				)
			);

			$licensePrefix = Main\Loader::IncludeModule("bitrix24") ? \CBitrix24::getLicensePrefix() : "";
			if (!Main\ModuleManager::isModuleInstalled("bitrix24") || in_array($licensePrefix, array("ru")))
			{
				$this->arResult['TABS'][] = array(
					'id' => 'tab_check',
					'name' => Loc::getMessage('CRM_ORDER_TAB_CHECK'),
					'loader' => array(
						'serviceUrl' => '/bitrix/components/bitrix/crm.order.check.list/lazyload.ajax.php?&site'.$this->arResult['SITE_ID'].'&'.bitrix_sessid_get(),
						'componentData' => array(
							'template' => '',
							'params' => array(
								'ENABLE_TOOLBAR' => true,
								'CHECK_COUNT' => '20',
								'OWNER_ID' => $this->entityID,
								'OWNER_TYPE' => CCrmOwnerType::Order,
								'PATH_TO_ORDER_CHECK_SHOW' => $this->arResult['PATH_TO_ORDER_CHECK_SHOW'],
								'PATH_TO_ORDER_CHECK_EDIT' => $this->arResult['PATH_TO_ORDER_CHECK_EDIT'],
								'PATH_TO_ORDER_CHECK_CHECK_STATUS' => $this->arResult['PATH_TO_ORDER_CHECK_CHECK_STATUS'],
								'PATH_TO_ORDER_CHECK_DELETE' => $this->arResult['PATH_TO_ORDER_CHECK_DELETE'],
								'GRID_ID_SUFFIX' => 'CHECK_DETAILS',
								'TAB_ID' => 'tab_check',
								'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE']
							)
						)
					)
				);
			}

			$this->arResult['TABS'][] = array(
				'id' => 'tab_shipment',
				'name' => Loc::getMessage('CRM_ORDER_TAB_SHIPMENT'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.order.shipment.list/lazyload.ajax.php?&site='.$this->arResult['SITE_ID'].'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '.default',
						'params' => array(
							'INTERNAL_FILTER' => array('ORDER_ID' => $this->entityID),
							'ENABLE_TOOLBAR' => true,
							'PATH_TO_ORDER_SHIPMENT_LIST' => '/bitrix/components/bitrix/crm.order.shipment.list/class.php?&site='.$this->arResult['SITE_ID'].'&'.bitrix_sessid_get()
						)
					)
				)
			);

			if(\Bitrix\Crm\Automation\Factory::isAutomationAvailable(CCrmOwnerType::Order))
			{
				Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/crm.automation/templates/.default/style.css');
				$this->arResult['TABS'][] = array(
					'id' => 'tab_automation',
					'name' => Loc::getMessage('CRM_ORDER_TAB_AUTOMATION'),
					'loader' => array(
						'serviceUrl' => '/bitrix/components/bitrix/crm.automation/lazyload.ajax.php?&site='.$this->arResult['SITE_ID'].'&'.bitrix_sessid_get(),
						'componentData' => array(
							'template' => '',
							'params' => array(
								'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
								'ENTITY_ID' => $this->entityID,
								'back_url' => \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Order, $this->entityID)
							)
						)
					)
				);
			}

			$this->arResult['TABS'][] = array(
				'id' => 'tab_tree',
				'name' => Loc::getMessage('CRM_ORDER_TAB_TREE'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.entity.tree/lazyload.ajax.php?&site='.$this->arResult['SITE_ID'].'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '.default',
						'params' => array(
							'ENTITY_ID' => $this->entityID,
							'ENTITY_TYPE_NAME' => CCrmOwnerType::OrderName,
						)
					)
				)
			);

			$this->arResult['TABS'][] = array(
				'id' => 'tab_event',
				'name' => Loc::getMessage('CRM_ORDER_TAB_EVENT'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.event.view/lazyload.ajax.php?&site='.$this->arResult['SITE_ID'].'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '',
						'contextId' => "ORDER_{$this->entityID}_EVENT",
						'params' => array(
							'AJAX_OPTION_ADDITIONAL' => "ORDER_{$this->entityID}_EVENT",
							'ENTITY_TYPE' => 'ORDER',
							'ENTITY_ID' => $this->entityID,
							'PATH_TO_USER_PROFILE' => $this->arResult['PATH_TO_USER_PROFILE'],
							'TAB_ID' => 'tab_event',
							'INTERNAL' => 'Y',
							'SHOW_INTERNAL_FILTER' => 'Y',
							'PRESERVE_HISTORY' => true,
							'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE']
						)
					)
				)
			);
		}
		else
		{
			$this->arResult['TABS'][] = array(
				'id' => 'tab_payment',
				'name' => Loc::getMessage('CRM_ORDER_TAB_PAYMENT'),
				'enabled' => false
			);

			$this->arResult['TABS'][] = array(
				'id' => 'tab_check',
				'name' => Loc::getMessage('CRM_ORDER_TAB_CHECK'),
				'enabled' => false
			);

			$this->arResult['TABS'][] = array(
				'id' => 'tab_shipment',
				'name' => Loc::getMessage('CRM_ORDER_TAB_SHIPMENT'),
				'enabled' => false
			);

			if(\Bitrix\Crm\Automation\Factory::isAutomationAvailable(CCrmOwnerType::Order))
			{
				$this->arResult['TABS'][] = array(
					'id' => 'tab_automation',
					'name' => Loc::getMessage('CRM_ORDER_TAB_AUTOMATION'),
					'enabled' => false
				);
			}

			$this->arResult['TABS'][] = array(
				'id' => 'tab_tree',
				'name' => Loc::getMessage('CRM_ORDER_TAB_TREE'),
				'enabled' => false
			);

			$this->arResult['TABS'][] = array(
				'id' => 'tab_event',
				'name' => Loc::getMessage('CRM_ORDER_TAB_EVENT'),
				'enabled' => false
			);
		}
		//endregion

		//region WAIT TARGET DATES
		$this->arResult['WAIT_TARGET_DATES'] = array();

		if ($this->userType)
		{
			$userFields = $this->userType->GetFields();
			foreach($userFields as $userField)
			{
				if($userField['USER_TYPE_ID'] === 'date')
				{
					$this->arResult['WAIT_TARGET_DATES'][] = array(
						'name' => $userField['FIELD_NAME'],
						'caption' => isset($userField['EDIT_FORM_LABEL'])
							? $userField['EDIT_FORM_LABEL'] : $userField['FIELD_NAME']
					);
				}
			}
		}
		//endregion

		//region VIEW EVENT
		if($this->entityID > 0 && \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isViewEventEnabled())
		{
			CCrmEvent::RegisterViewEvent(CCrmOwnerType::Order, $this->entityID, $this->userID);
		}
		//endregion
		if(Bitrix\Main\Loader::includeModule('pull'))
		{
			\CPullWatch::Add($this->userID, 'CRM_ENTITY_ORDER');
			\CPullWatch::Add($this->userID, 'CRM_ENTITY_ORDER_PAYMENT');
			\CPullWatch::Add($this->userID, 'CRM_ENTITY_ORDER_SHIPMENT');
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
		$allStatuses = array();

		$statusList = Order\OrderStatus::getListInCrmFormat();
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

		$tradingPlatforms = [];
		if(\Bitrix\Main\Loader::includeModule('landing'))
		{
			$platformData = \Bitrix\Landing\Site::getList([
				'filter' => ['=TYPE' => 'STORE'],
				'select' => ['ID', 'TITLE']
			]);

			while ($platform = $platformData->fetch())
			{
				$code = \Bitrix\Sale\TradingPlatform\Landing\Landing::getCodeBySiteId($platform['ID']);
				$tradingPlatforms[$code] = $platform['TITLE']." [".$platform['ID']."]";
			}
			if (count($tradingPlatforms) > 0)
			{
				array_unshift($tradingPlatforms, Loc::getMessage('CRM_ORDER_STORE_NOT_CHOSEN'));
			}
		}

		$this->arResult['ORDER_PROPERTIES'] = $this->prepareProperties($this->order);

		$isCurrencyEditable = $this->order->getId() <= 0;

		$this->arResult['ENTITY_FIELDS'] = array(
			array(
				'name' => 'ID',
				'title' => Loc::getMessage('CRM_ORDER_FIELD_ID'),
				'type' => 'text',
				'editable' => false
			),
			array(
				'name' => 'TRADING_PLATFORM',
				'title' => Loc::getMessage('CRM_ORDER_FIELD_TRADING_PLATFORM'),
				'type' => 'list',
				'editable' => count($tradingPlatforms) > 0,
				'data' => array(
					'items'=> \CCrmInstantEditorHelper::PrepareListOptions($tradingPlatforms)
				)
			),
			array(
				'name' => 'ORDER_TOPIC',
				'title' => Loc::getMessage('CRM_ORDER_FIELD_TOPIC'),
				'type' => 'text',
				'editable' => true
			),
			array(
				'name' => 'STATUS_ID',
				'title' => Loc::getMessage('CRM_ORDER_FIELD_STATUS_ID'),
				'type' => 'list',
				'required' => true,
				'editable' => !($this->order->isCanceled()),
				'data' => array(
					'items'=> \CCrmInstantEditorHelper::PrepareListOptions(
						$allStatuses,
						array('EXCLUDE_FROM_EDIT' => $disabledStatusIDs)
					)
				)
			),
			array(
				'name' => 'PRICE_WITH_CURRENCY',
				'title' => Loc::getMessage('CRM_ORDER_FIELD_OPPORTUNITY_WITH_CURRENCY'),
				'type' => 'money',
				'editable' => $isCurrencyEditable,
				'data' => array(
					'affectedFields' => array('CURRENCY', 'PRICE'),
					'currency' => array(
						'name' => 'CURRENCY',
						'items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems())
					),
					'amount' => 'PRICE',
					'formatted' => 'FORMATTED_PRICE',
					'formattedWithCurrency' => 'FORMATTED_PRICE_WITH_CURRENCY'
				)
			),
			array(
				'name' => 'USER_ID',
				'title' => Loc::getMessage('CRM_ORDER_FIELD_USER_ID'),
				'type' => 'order_user',
				'editable' => true,
				'requiredConditionally' => true,
				'data' => array(
					'enableEditInView' => true,
					'formated' => 'USER_FORMATTED_NAME',
					'position' => 'USER_WORK_POSITION',
					'photoUrl' => 'USER_PHOTO_URL',
					'showUrl' => 'PATH_TO_USER',
					'defaultUserList' => 'USER_LIST_SELECT',
					'pathToProfile' => $this->arResult['PATH_TO_BUYER_PROFILE'],
					'pathToUserSelector' => '/shop/settings/user_search.php?lang='.LANGUAGE_ID.'&FN=#form_id#&FC=USER_ID'
				)
			),
			array(
				'name' => 'RESPONSIBLE_ID',
				'title' => Loc::getMessage('CRM_ORDER_FIELD_RESPONSIBLE_ID'),
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
				'name' => 'DATE_INSERT',
				'title' => Loc::getMessage('CRM_ORDER_FIELD_DATE_INSERT'),
				'type' => 'datetime',
				'editable' => false,
				'data' => array('enableTime' => true)
			),
			array(
				'name' => 'COMMENTS',
				'title' => Loc::getMessage('CRM_ORDER_FIELD_COMMENTS'),
				'type' => 'text',
				'editable' => true
			),
			array(
				'name' => 'USER_DESCRIPTION',
				'title' => Loc::getMessage('CRM_ORDER_FIELD_USER_DESCRIPTION'),
				'type' => 'text',
				'editable' => true
			),
			array(
				'name' => 'CLIENT',
				'title' => Loc::getMessage('CRM_ORDER_FIELD_CLIENT'),
				'type' => 'order_client',
				'editable' => true,
				'requiredConditionally' => true,
				'data' => array(
					'map' => array(
						'data' => 'CLIENT',
						'companyId' => 'COMPANY_ID',
						'contactIds' =>  'CONTACT_IDS'
					),
					'info' => 'CLIENT_INFO',
					'lastCompanyInfos' => 'LAST_COMPANY_INFO',
					'lastContactInfos' => 'LAST_CONTACT_INFOS',
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

		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'SHIPMENT',
			'type' => 'shipment',
			'editable' => true,
			'transferable' => false,
			'required' => true,
			'data' => array(
				'addShipmentDocumentUrl' => '/bitrix/components/bitrix/crm.order.shipment.document/slider.ajax.php?'.bitrix_sessid_get().'&site='.$this->arResult['SITE_ID'],
			)
		);

		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'PAYMENT',
			'type' => 'payment',
			'editable' => true,
			'transferable' => false,
			'required' => true,
			'data' => array(
				'addPaymentDocumentUrl' => '/bitrix/components/bitrix/crm.order.payment.voucher/slider.ajax.php?'.bitrix_sessid_get().'&site='.$this->arResult['SITE_ID'],
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
				'active' => is_array($this->arResult['ORDER_PROPERTIES']["ACTIVE"]) ? $this->arResult['ORDER_PROPERTIES']["ACTIVE"] : [],
				'hidden' => is_array($this->arResult['ORDER_PROPERTIES']["HIDDEN"]) ? $this->arResult['ORDER_PROPERTIES']["HIDDEN"] : [],
			],
			'data' => array(
				'managerUrl' => '/shop/orderform/#person_type_id#/',
				'editorUrl' => '/shop/orderform/#person_type_id#/prop/#property_id#/',
			)
		);
		$personTypeList = is_array($this->arResult['PERSON_TYPES']) ? $this->arResult['PERSON_TYPES'] : array();
		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'PERSON_TYPE_ID',
			'title' => Loc::getMessage('CRM_ORDER_SECTION_PERSON_TYPE_ID'),
			'required' => true,
			'transferable' => false,
			'type' => 'order_person_type',
			'editable' => true,
			'data' => array(
				'items'=> \CCrmInstantEditorHelper::PrepareListOptions($personTypeList)
			)
		);
		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'USER_PROFILE',
			'title' => Loc::getMessage('CRM_ORDER_SECTION_USER_PROFILE'),
			'required' => false,
			'transferable' => false,
			'type' => 'order_person_type',
			'editable' => ($this->mode === ComponentMode::CREATION),
			'visibilityPolicy' => 'edit',
			'data' => array(
				'items'=> $this->loadProfiles($this->order->getUserId(), $this->entityData['PERSON_TYPE_ID'])
			)
		);

		if($this->entityID > 0 && $this->mode !== ComponentMode::COPING)
		{
			$this->arResult['ENTITY_FIELDS'][] = array(
				'name' => 'ACCOUNT_NUMBER',
				'title' => Loc::getMessage('CRM_ORDER_FIELD_NUMBER'),
				'type' => 'text',
				'editable' => false,
			);

			$this->arResult['ENTITY_FIELDS'][] = array(
				'name' => 'PRODUCT_ROW_SUMMARY',
				'title' => Loc::getMessage('CRM_ORDER_FIELD_PRODUCTS'),
				'type' => 'product_row_summary',
				'editable' => false,
				'transferable' => false
			);
		}

		$this->arResult['ENTITY_FIELDS'] = array_merge(
			$this->arResult['ENTITY_FIELDS'],
			array_values($this->userFieldInfos)
		);

		return $this->arResult['ENTITY_FIELDS'];
	}

	protected function getStatusList($entityPermissionTypeID)
	{
		$allStatuses = array();

		$statusList = Order\OrderStatus::getListInCrmFormat();
		foreach ($statusList as $status)
		{
			$allStatuses[$status['STATUS_ID']] = $status['NAME'];
		}

		$statuses = array();
		foreach ($allStatuses as $ID => $title)
		{
			if(\Bitrix\Crm\Order\Permissions\Order::checkStatusPermission($ID, $entityPermissionTypeID, $this->userPermissions))
			{
				$statuses[$ID] = $title;
			}
		}
		return $statuses;
	}

	public function loadProfiles($userId, $personTypeId)
	{
		$data = array();
		$userId = (int)$userId;
		$personTypeId = (int)$personTypeId;
		if($userId > 0 && $personTypeId > 0)
		{
			$result = \Bitrix\Sale\OrderUserProperties::getList(array(
				'filter' => array(
					'USER_ID' => $userId,
					'PERSON_TYPE_ID' => $personTypeId
				),
				'order' => array('DATE_UPDATE' => 'DESC')
			));
			while ($profile = $result->fetch())
			{
				$data[$profile['ID']] = $profile['NAME'];
			}
		}

		$data['NEW'] = Loc::getMessage('CRM_ORDER_CREATE_NEW_PROFILE');

		$data = \CCrmInstantEditorHelper::PrepareListOptions($data);

		return $data;
	}

	public function prepareEntityData($prepareDataMode = ComponentMode::UNDEFINED)
	{
		if($this->entityData)
			return $this->entityData;

		if($this->mode === ComponentMode::UNDEFINED)
		{
			$this->tryToDetectMode();
		}

		if($prepareDataMode === ComponentMode::CREATION)
		{
			//region Default Dates
			$dateInsert = time() + \CTimeZone::GetOffset();
			$time = localtime($dateInsert, true);
			$dateInsert -= $time['tm_sec'] + 60 * $time['tm_min'] + 3600 * $time['tm_hour'];
			$this->entityData['DATE_INSERT'] = ConvertTimeStamp($dateInsert, 'SHORT', $this->arResult['SITE_ID']);
			$this->entityData['CURRENCY'] = \CCrmCurrency::GetBaseCurrencyID();

			//region Default Responsible
			if($this->userID > 0)
			{
				$this->entityData['RESPONSIBLE_ID'] = $this->userID;
			}
			//endregion

			//region Default Stage ID
			$statusList = $this->getStatusList(EntityPermissionType::CREATE);
			if(!empty($statusList))
			{
				$requestStatusId = $this->request->get('status_id');
				if(isset($statusList[$requestStatusId]))
				{
					$this->entityData['STATUS_ID'] = $requestStatusId;
				}
				else
				{
					$this->entityData['STATUS_ID'] = current(array_keys($statusList));
				}
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
			$propertiesData = Order\Property::getList(
				array(
					'filter' => array('ACTIVE' => 'Y'),
					'order' => array('SORT')
				)
			);

			while ($property = $propertiesData->fetch())
			{
				$defaultValue = $property['DEFAULT_VALUE'];
				$id = $property['ID'];
				$name = "PROPERTY_{$id}";
				if($property['TYPE'] === 'LOCATION' || $property['TYPE'] === 'FILE')
				{
					$property['ONCHANGE'] = "BX.onCustomEvent('CrmOrderPropertySetCustom', ['{$name}']);";
					if($property['TYPE'] === 'LOCATION')
					{
						$property['IS_SEARCH_LINE'] = true;
					}

					$locationHtml = \Bitrix\Sale\Internals\Input\Manager::getEditHtml(
						$name,
						$property,
						$defaultValue
					);

					$this->entityData["{$name}_EDIT_HTML"] = $locationHtml;
				}

				$this->entityData[$name] = $defaultValue;
			}

			$this->entityData['USER_ID'] = $this->order->getUserId();
			$this->entityData['USER_PROFILE'] = !is_null($this->profileId) ? (int)$this->profileId : 'NEW';
			$this->entityData['OLD_USER_ID'] = null;
		}
		else
		{
			$this->entityData = $this->order->getFieldValues();
			//HACK: Removing time from DATE_INSERT because of 'datetime' type (see CCrmQuote::GetFields)
			if(isset($this->entityData['DATE_INSERT']))
				$this->entityData['DATE_INSERT'] = \CCrmComponentHelper::TrimZeroTime($this->entityData['DATE_INSERT']);

			if(!isset($this->entityData['CURRENCY']) || $this->entityData['CURRENCY'] === '')
				$this->entityData['CURRENCY'] = \CCrmCurrency::GetBaseCurrencyID();

			$this->entityData['OLD_CURRENCY'] = $this->entityData['CURRENCY'];

			//region Default Responsible and Status ID for copy mode
			if($prepareDataMode === ComponentMode::COPING)
			{
				if($this->userID > 0)
				{
					$this->entityData['RESPONSIBLE_ID'] = $this->userID;
				}

				$statusList = $this->getStatusList(EntityPermissionType::CREATE);
				if(!empty($statusList))
				{
					$this->entityData['STATUS_ID'] = current(array_keys($statusList));
				}
				unset($this->entityData['ID'], $this->entityData['ACCOUNT_NUMBER']);
			}
			$this->entityData['OLD_USER_ID'] = $this->entityData['USER_ID'];
			$ccCollection = $this->order->getContactCompanyCollection();

			$companyId = 0;
			if($company = $ccCollection->getPrimaryCompany())
			{
				$companyId = $company->getField('ENTITY_ID');
				$this->entityData['COMPANY_ID'] = $companyId;
			}

			$contactIds = [];
			$contacts = $ccCollection->getContacts();
			foreach ($contacts as $contact)
			{
				$contactIds[] = $contact->getField('ENTITY_ID');
			}
			$this->entityData['CLIENT'] = [
				'COMPANY_ID' => $companyId,
				'CONTACT_IDS' => $contactIds
			];

			$platforms = [];
			$platformsData = \Bitrix\Sale\TradingPlatformTable::getList([
				'select' => ['CODE', 'ID', 'CLASS']
			]);
			while ($platform = $platformsData->fetch())
			{
				$platforms[$platform['ID']] = [
					'CODE' => $platform['CODE'],
					'CLASS' => $platform['CLASS']
				];
			}

			$tradingCollection = $this->order->getTradeBindingCollection();
			/** @var \Bitrix\Sale\TradeBindingEntity $item */
			foreach ($tradingCollection as $item)
			{
				$platformId = $item->getField('TRADING_PLATFORM_ID');
				if (!empty($platforms[$platformId]) && $platforms[$platformId]['CLASS'] === "\\".\Bitrix\Sale\TradingPlatform\Landing\Landing::class)
				{
					$this->entityData['TRADING_PLATFORM'] = $platforms[$platformId]['CODE'];
				}
			}
			//endregion
		}
		$properties = $this->getPropertyEntityData($this->order);
		$this->entityData = array_merge($this->entityData, $properties);
		//region Responsible
		if(isset($this->entityData['RESPONSIBLE_ID']) && $this->entityData['RESPONSIBLE_ID'] > 0)
		{
			$responsibleId = (int)$this->entityData['RESPONSIBLE_ID'];
			$this->entityData += $this->getUserEntityData($responsibleId, 'RESPONSIBLE');
			$this->entityData['PATH_TO_RESPONSIBLE_USER'] = CComponentEngine::MakePathFromTemplate(
				$this->arResult['PATH_TO_USER_PROFILE'],
				array('user_id' => $responsibleId)
			);
		}
		//endregion

		//region User ID
		if(isset($this->entityData['USER_ID']) && $this->entityData['USER_ID'] > 0)
		{
			$userId = (int)$this->entityData['USER_ID'];
			$this->entityData += $this->getUserEntityData($userId, 'USER');
			$this->entityData['PATH_TO_USER'] = CComponentEngine::MakePathFromTemplate(
				$this->arResult['PATH_TO_BUYER_PROFILE'],
				array('user_id' => $userId)
			);
		}
		//endregion

		//region PRICE & Currency
		$this->entityData['FORMATTED_PRICE_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['PRICE'],
			$this->entityData['CURRENCY'],
			''
		);
		$this->entityData['FORMATTED_PRICE'] = \CCrmCurrency::MoneyToString(
			$this->entityData['PRICE'],
			$this->entityData['CURRENCY'],
			'#'
		);
		//endregion

		//region Client Data & Multifield Data
		$clientInfo = array();
		$multiFildData = array();
		$companyId = $this->entityData['COMPANY_ID'];
		if($companyId > 0)
		{
			self::prepareMultifieldData(\CCrmOwnerType::Company, $companyId, 'PHONE', $multiFildData);
			self::prepareMultifieldData(\CCrmOwnerType::Company, $companyId, 'EMAIL', $multiFildData);

			$isEntityReadPermitted = \CCrmCompany::CheckReadPermission($companyId, $this->userPermissions);
			$companyInfo = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::CompanyName,
				$companyId,
				array(
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_MULTIFIELDS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
				)
			);

			$clientInfo['COMPANY_DATA'] = $companyInfo;
		}

		$contactBindings = array();
		if($this->entityID > 0)
		{
			$contactBindings = \Bitrix\Crm\Binding\OrderContactCompanyTable::getOrderBindings($this->entityID);
		}
		elseif(isset($this->entityData['CONTACT_BINDINGS']) && is_array($this->entityData['CONTACT_BINDINGS']))
		{
			$contactBindings = $this->entityData['CONTACT_BINDINGS'];
		}
		elseif(isset($this->entityData['CONTACT_ID']) && $this->entityData['CONTACT_ID'] > 0)
		{
			$contactBindings = Binding\EntityBinding::prepareEntityBindings(
				CCrmOwnerType::Contact,
				array($this->entityData['CONTACT_ID'])
			);
		}

		$contactIDs = Binding\EntityBinding::prepareEntityIDs(\CCrmOwnerType::Contact, $contactBindings);
		$clientInfo['CONTACT_DATA'] = array();
		foreach($contactIDs as $contactID)
		{
			self::prepareMultifieldData(CCrmOwnerType::Contact, $contactID, 'PHONE', $multiFildData);
			self::prepareMultifieldData(CCrmOwnerType::Contact, $contactID, 'EMAIL', $multiFildData);

			$isEntityReadPermitted = CCrmContact::CheckReadPermission($contactID, $this->userPermissions);
			$clientInfo['CONTACT_DATA'][] = CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::ContactName,
				$contactID,
				array(
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_MULTIFIELDS' => true,
					'REQUIRE_BINDINGS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
				)
			);
		}
		$this->entityData['CLIENT_INFO'] = $clientInfo;

		$this->entityData['REQUISITE_BINDING'] = $this->order->getRequisiteLink();

		$this->entityData['MULTIFIELD_DATA'] = $multiFildData;
		$this->entityData['USER_LIST_SELECT'] = $this->getDefaultUserList();
		$lastUserClients = $this->getLastUserClients();
		$this->entityData['LAST_COMPANY_INFO'] = $lastUserClients[\CCrmOwnerType::Company];
		$this->entityData['LAST_CONTACT_INFOS'] = $lastUserClients[\CCrmOwnerType::Contact];

		//region Product row
		$productRowCount = 0;
		$productRowTotalSum = 0.0;
		$productRowInfos = array();

		if($this->order->getId() > 0)
		{
			$basket = $this->order->getBasket();

			/** @var \Bitrix\Sale\BasketItem $item */
			foreach($basket->getBasketItems() as $item)
			{
				$sum = $item->getPrice() * $item->getQuantity();
				$productRowTotalSum += $sum;
				$productRowCount++;

				if($productRowCount > 10)
					continue;

				$productRowInfos[] = array(
					'PRODUCT_NAME' => $item->getField('NAME'),
					'SUM' => CCrmCurrency::MoneyToString($sum, $item->getCurrency())
				);
			}

			$this->entityData['PRODUCT_ROW_SUMMARY'] = array(
				'count' => $productRowCount,
				'total' => CCrmCurrency::MoneyToString($productRowTotalSum, $this->entityData['CURRENCY']),
				'items' => $productRowInfos
			);
		}

		$this->entityData += $this->getPaymentEntityData();
		$this->entityData += $this->getShipmentEntityData();
		$this->arResult['PERSON_TYPES'] = array();
		$personTypes = \Bitrix\Crm\Order\PersonType::load($this->arResult['SITE_ID']);
		if(empty($this->entityData['PERSON_TYPE_ID']))
		{
			$this->entityData['PERSON_TYPE_ID'] = key($personTypes);
		}
		foreach ($personTypes as $type)
		{
			$this->arResult['PERSON_TYPES'][$type['ID']] = $type['NAME'];
		}
		$this->entityData['OLD_PERSON_TYPE_ID'] = $this->entityData['PERSON_TYPE_ID'];
		if($prepareDataMode === ComponentMode::CREATION)
		{
			$title = Loc::getMessage('CRM_ORDER_CREATION_PAGE_TITLE');
		}
		elseif($prepareDataMode === ComponentMode::COPING)
		{
			$title = Loc::getMessage('CRM_ORDER_COPY_PAGE_TITLE');
		}
		else
		{
			$title = Loc::getMessage(
				'CRM_ORDER_TITLE2',
				array(
					'#ACCOUNT_NUMBER#' => $this->entityData['ACCOUNT_NUMBER']
				));

			if(strlen($this->entityData['ORDER_TOPIC']) > 0)
			{
				$title .= ' "'.$this->entityData['ORDER_TOPIC'].'"';
			}
		}

		$this->entityData['TITLE'] = $title;
		$this->entityData['STORAGE_TYPE_ID'] =  Bitrix\Crm\Integration\StorageType::File;

		//region User Fields
		foreach($this->userFields as $fieldName => $userField)
		{
			$fieldValue = isset($userField['VALUE']) ? $userField['VALUE'] : '';
			$fieldData = isset($this->userFieldInfos[$fieldName])
				? $this->userFieldInfos[$fieldName] : null;

			if(!is_array($fieldData))
			{
				continue;
			}

			$isEmptyField = true;
			$fieldParams = $fieldData['data']['fieldInfo'];

			if((is_string($fieldValue) && $fieldValue !== '')
				|| (is_array($fieldValue) && !empty($fieldValue))
			)
			{
				$fieldParams['VALUE'] = $fieldValue;
				$isEmptyField = false;
			}

			$fieldSignature = $this->userFieldDispatcher->getSignature($fieldParams);

			if($isEmptyField)
			{
				$this->entityData[$fieldName] = array(
					'SIGNATURE' => $fieldSignature,
					'IS_EMPTY' => true
				);
			}
			else
			{
				$this->entityData[$fieldName] = array(
					'VALUE' => $fieldValue,
					'SIGNATURE' => $fieldSignature,
					'IS_EMPTY' => false
				);
			}
		}
		//endregion

		//endregion
		return ($this->arResult['ENTITY_DATA'] = $this->entityData);
	}

	protected function getUserEntityData($id, $name)
	{
		$userFields = [];
		$user = self::getUser((int)$id);
		if(!is_array($user))
		{
			return $userFields;
		}

		$userFields["{$name}_LOGIN"] = $user['LOGIN'];
		$userFields["{$name}_NAME"] = isset($user['NAME']) ? $user['NAME'] : '';
		$userFields["{$name}_SECOND_NAME"] = isset($user['SECOND_NAME']) ? $user['SECOND_NAME'] : '';
		$userFields["{$name}_LAST_NAME"] = isset($user['LAST_NAME']) ? $user['LAST_NAME'] : '';
		$userFields["{$name}_PERSONAL_PHOTO"] = isset($user['PERSONAL_PHOTO']) ? $user['PERSONAL_PHOTO'] : '';

		$userFields["{$name}_FORMATTED_NAME"] =
			\CUser::FormatName(
				$this->arResult['NAME_TEMPLATE'],
				array(
					'LOGIN' => $userFields["{$name}_LOGIN"],
					'NAME' => $userFields["{$name}_NAME"],
					'LAST_NAME' => $userFields["{$name}_LAST_NAME"],
					'SECOND_NAME' => $userFields["{$name}_SECOND_NAME"]
				),
				true,
				false
			);

		$assignedByPhotoID = isset($userFields["{$name}_PERSONAL_PHOTO"])
			? (int)$userFields["{$name}_PERSONAL_PHOTO"] : 0;

		if($assignedByPhotoID > 0)
		{
			$file = new CFile();
			$fileInfo = $file->ResizeImageGet(
				$assignedByPhotoID,
				array('width' => 60, 'height'=> 60),
				BX_RESIZE_IMAGE_EXACT
			);
			if(is_array($fileInfo) && isset($fileInfo['src']))
			{
				$userFields["{$name}_PERSONAL_PHOTO"] = $fileInfo['src'];
			}
		}

		return $userFields;
	}

	protected function getVoucherUrl($type, $paymentId)
	{
		return CHTTP::urlAddParams('/bitrix/components/bitrix/crm.order.payment.voucher/slider.ajax.php?'.bitrix_sessid_get(),
			array(
				'siteID' => SITE_ID,
				'paymentId' => $paymentId,
				'paymentType' => (int)$type,
			)
		);
	}

	/**
	 * @param Order\Order $order
	 * @return array
	 */
	public function getPropertyEntityData(Order\Order $order)
	{
		$properties = array();
		$propertyCollection = $order->getPropertyCollection();

		/**@var Bitrix\Sale\PropertyValue $property*/
		foreach ($propertyCollection as $property)
		{
			$code = null;
			$propertyData = $property->getProperty();
			if ((int)$propertyData['ID'] > 0)
			{
				$code = (int)$propertyData['ID'];
			}
			elseif (is_array($property->getValue()) || strlen($property->getValue()) > 0)
			{
				$code = 'n'.$property->getId();
			}

			if (empty($code))
			{
				continue;
			}

			if($property->getType() === 'LOCATION' || $property->getType() === 'FILE')
			{
				$params = $property->getProperty();
				$name = "PROPERTY_{$code}";
				$params['ONCHANGE'] = "BX.onCustomEvent('CrmOrderPropertySetCustom', ['{$name}']);";

				if ($property->getType() === 'LOCATION')
				{
					$params['IS_SEARCH_LINE'] = true;
				}

				$html = \Bitrix\Sale\Internals\Input\Manager::getEditHtml(
					$name,
					$params,
					$property->getValue()
				);

				$properties["{$name}_EDIT_HTML"] = $html;
				$properties["{$name}_VIEW_HTML"] = $property->getValue() ? $property->getViewHtml() : "";
				$properties["{$name}_EMPTY_HTML"] = Loc::getMessage('CRM_ORDER_NOT_SELECTED');
			}

			$properties['PROPERTY_'.$code] = $property->getValue();
		}

		return $properties;
	}

	protected function getShipmentEntityData()
	{
		$result = array(
			'SHIPMENT' => array()
		);

		$index = 0;
		$shipments = $this->order->getShipmentCollection();

		/** @var Order\Shipment $shipment */
		foreach ($shipments as $shipment)
		{
			if($shipment->isSystem())
				continue;

			$deliveryId = $shipment->getDeliveryId();
			$deliveryList = Order\Manager::getDeliveryServicesList($shipment);
			$profilesList = Order\Manager::getDeliveryProfiles($deliveryId, $deliveryList);
			$currency = !empty($shipment->getField('CURRENCY')) ? $shipment->getField('CURRENCY') : $this->order->getCurrency();
			$currency = $this->getCurrencyNameShort($currency);
			$selectorDeliveryId = 0;
			$selectorProfileId = 0;

			if(isset($deliveryList[$deliveryId]))
			{
				$selectorDeliveryId = $deliveryId;
			}

			if($selectorDeliveryId <= 0)
			{
				foreach($deliveryList as $delivery)
				{
					if(isset($delivery['ITEMS']))
					{
						foreach($delivery['ITEMS'] as $item)
						{
							if($item['ID'] == $deliveryId)
							{
								$selectorDeliveryId = $deliveryId;
								break 2;
							}
						}
					}
				}
			}

			if($selectorDeliveryId <= 0 && isset($profilesList[$deliveryId]))
			{
				$selectorProfileId = $profilesList[$deliveryId]['ID'];
				$profile = \Bitrix\Sale\Delivery\Services\Manager::getById($selectorProfileId);
				$selectorDeliveryId = $profile['PARENT_ID'];
			}

			$documentInfo = '';
			$documentNum = $shipment->getField('DELIVERY_DOC_NUM');

			if(!empty($documentNum))
			{
				$documentInfo = htmlspecialcharsbx($documentNum);
				$documentDate = htmlspecialcharsbx($shipment->getField('DELIVERY_DOC_DATE'));
				$documentInfo .= !empty($documentDate) ? " ".$documentDate : "";
			}

			$delivery = $shipment->getDelivery();
			$deliveryServiceName = '';
			$logoPath = '';

			if($delivery)
			{
				$logoFileId = (int)$delivery->getLogotip();
				$deliveryServiceName = htmlspecialcharsbx($delivery->getNameWithParent());

				if ($logoFileId > 0)
				{
					$logoData = \CFile::ResizeImageGet(
						$logoFileId,
						array('width' => 200, 'height' => 80)
					);

					$logoPath = $logoData['src'];
				}

				if(empty($logoPath))
				{
					$logoPath = $this->getPath().'/images/delivery_logo.png';
				}
			}

			$errors = [];
			$statusControlShipment = "";

			if (!$this->order->isNew())
			{
				$statusControlShipment = CCrmViewHelper::RenderOrderShipmentStatusControl(
					array(
						'PREFIX' => "SHIPMENT_PROGRESS_BAR_".$shipment->getId(),
						'ENTITY_ID' => $shipment->getId(),
						'CURRENT_ID' => $shipment->getField('STATUS_ID'),
						'SERVICE_URL' => '/bitrix/components/bitrix/crm.order.shipment.list/list.ajax.php',
						'READ_ONLY' => false
					)
				);
			}

			if($deliveryId > 0 && $this->isDeliveryRestricted($shipment))
			{
				$errors[] = Loc::getMessage('CRM_ORDER_ERROR_DELIVERY_SERVICE', ['#NAME#' => $deliveryServiceName]);
			}

			$fields = array(
				'NUMBER_AND_DATE' => Loc::getMessage('CRM_ORDER_SHIPMENT_SUBTITLE_MASK', array(
					'#ID#' => $shipment->getField('ID'),
					'#DATE_BILL#' => CCrmComponentHelper::TrimDateTimeString(
						ConvertTimeStamp(
							MakeTimeStamp(
								$shipment->getField('DATE_INSERT')),
							'SHORT',
							$this->arResult['SITE_ID']
				)))),
				'STATUS_CONTROL' => $statusControlShipment,
				'DELIVERY_SERVICES_LIST' => $deliveryList,
				'DELIVERY_PROFILES_LIST' => $profilesList,
				'DELIVERY_SERVICE_NAME' => $deliveryServiceName,
				'DELIVERY_LOGO' => $logoPath,
				'DELIVERY_SELECTOR_DELIVERY_ID' => $selectorDeliveryId,
				'DELIVERY_SELECTOR_PROFILE_ID' => $selectorProfileId,
				'DELIVERY_STORE_ID' => $shipment->getStoreId(),
				'DELIVERY_STORES_LIST' => \Bitrix\Sale\Helpers\Admin\Blocks\OrderShipment::getStoresList(
					$deliveryId,
					$shipment->getStoreId()
				),
				'FORMATTED_PRICE_DELIVERY_WITH_CURRENCY' => \CCrmCurrency::MoneyToString(
					$shipment->getField('PRICE_DELIVERY'),
					$shipment->getField('CURRENCY'),
					''
				),
				'FORMATTED_PRICE_DELIVERY' => CCrmCurrency::MoneyToString(
					$shipment->getField('PRICE_DELIVERY'),
					$shipment->getField('CURRENCY'),
					'#'
				),
				'DOCUMENT_INFO' => $documentInfo,
				'CURRENCY_NAME' => $currency,
				'DISCOUNTS' => $this->getShipmentDiscounts(),
				'ERRORS' => []
			);

			if ($deliveryId > 0)
			{
				$extraServiceManager = new \Bitrix\Sale\Delivery\ExtraServices\Manager($deliveryId);
				$extraServiceManager->setOperationCurrency($shipment->getField('CURRENCY'));
				$extraServiceManager->setValues($shipment->getExtraServices());
				$extraService = $extraServiceManager->getItems();

				if($extraService)
				{
					$fields['EXTRA_SERVICES_DATA'] = $this->getExtraServices(
						$extraService,
						$index,
						$shipment
					);
				}
			}

			if ($this->order->isNew())
			{
				$calcPrice = $shipment->calculateDelivery();
				$price = $calcPrice->getPrice();

				$fields['PRICE_DELIVERY_CALCULATED'] = $price;
				$fields['FORMATTED_PRICE_DELIVERY_CALCULATED'] = \CCrmCurrency::MoneyToString(
					$price,
					$this->entityData['CURRENCY'],
					'#'
				);
				$fields['FORMATTED_PRICE_DELIVERY_CALCULATED_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
					$price,
					$this->entityData['CURRENCY'],
					''
				);

				if(!$shipment->isCustomPrice())
				{
					$errors = array_merge($errors, $calcPrice->getErrorMessages());
				}
			}

			if(!empty($errors))
			{
				$fields['ERRORS'] = $errors;
			}

			if(!empty($fields['DELIVERY_STORES_LIST']))
			{
				$fields['DELIVERY_STORES_LIST'] = array_merge(
					[0 => ['ID' => 0, 'TITLE' => Loc::getMessage('CRM_ORDER_STORE_NOT_CHOSEN')]],
					$fields['DELIVERY_STORES_LIST']
				);
			}

			if(isset($fields['DELIVERY_STORES_LIST'][$fields['DELIVERY_STORE_ID']]['TITLE']))
			{
				$fields['DELIVERY_STORE_TITLE'] = $fields['DELIVERY_STORES_LIST'][$fields['DELIVERY_STORE_ID']]['TITLE'];
			}

			if(isset($fields['DELIVERY_STORES_LIST'][$fields['DELIVERY_STORE_ID']]['ADDRESS']))
			{
				$fields['DELIVERY_STORE_ADDRESS'] = $fields['DELIVERY_STORES_LIST'][$fields['DELIVERY_STORE_ID']]['ADDRESS'];
			}


			$result['SHIPMENT'][] = array_merge($shipment->getFieldValues(), $fields);
			$index++;
		}

		return $result;
	}

	protected function getShipmentDiscounts()
	{
		\Bitrix\Sale\Helpers\Admin\OrderEdit::initCouponsData($this->order->getUserId(), $this->order->getId(), null);
		$discounts = \Bitrix\Sale\Helpers\Admin\OrderEdit::getOrderedDiscounts($this->order);
		return is_array($discounts['RESULT']['DELIVERY']) ? $discounts['RESULT']['DELIVERY'] : [];
	}

	/**
	 * @param Order\Shipment $shipment
	 * @return bool
	 * @throws Main\SystemException
	 */
	protected function isDeliveryRestricted($shipment)
	{
		$result = false;

		if($deliveryService = $shipment->getDelivery())
		{
			$restrictResult = Delivery\Restrictions\Manager::checkService($deliveryService->getId(), $shipment, Delivery\Restrictions\Manager::MODE_MANAGER);
			$result = ($restrictResult !== Delivery\Restrictions\Manager::SEVERITY_NONE)
				|| (!$deliveryService->isCompatible($shipment));
		}

		return $result;
	}

	/**
	 * @param $extraService Delivery\ExtraServices\Manager
	 * @param $index
	 * @param Order\Shipment|null $shipment
	 * @return array
	 */
	protected function getExtraServices($extraService, $index, Order\Shipment $shipment)
	{
		$result = [];

		foreach ($extraService as $itemId => $item)
		{
			$viewHtml = $item->getViewControl();
			$editHtml = '';

			if($item->canManagerEditValue())
			{
				$editHtml = $item->getEditControl('SHIPMENT['.$index.'][EXTRA_SERVICES]['.(int)$itemId.']');
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

	protected function getPaySystemLogoPath($logotip, $psaActionFile)
	{
		$paySystemLogoPath = '';

		if(strlen($logotip) > 0 )
		{
			$paySystemLogoPath = intval($logotip) > 0 ? \CFile::GetPath($logotip) : '';
		}
		elseif(strlen($psaActionFile))
		{
			$paySystemLogoPath = '/bitrix/images/sale/sale_payments/'.$psaActionFile.'.png';
		}

		if(empty($paySystemLogoPath))
		{
			$paySystemLogoPath = $this->getPath().'/images/pay_system_logo.png';
		}

		return $paySystemLogoPath;
	}

	protected function getDefaultUserList()
	{
		$resultList = [];
		$clientCollection = $this->order->getContactCompanyCollection();
		$primaryClient = $clientCollection->getPrimaryContact();
		$type = CCrmOwnerType::Contact;
		if (empty($primaryClient))
		{
			$primaryClient = $clientCollection->getPrimaryCompany();
			$type = CCrmOwnerType::Company;
		}

		if (empty($primaryClient))
		{
			$resultList[] = ['subtitle' => Loc::getMessage('CRM_ORDER_EMPTY_USER_INPUT_VALUE_WITH_CLIENT')];
			return $resultList;
		}

		$userDataRaw = Binding\OrderContactCompanyTable::getList([
			'select' => [
				'USER_ID' => 'ORDER.USER_ID',
				'USER_NAME' => 'ORDER.USER.NAME',
				'USER_SECOND_NAME' => 'ORDER.USER.SECOND_NAME',
				'USER_LAST_NAME' => 'ORDER.USER.LAST_NAME',
				'USER_LOGIN' => 'ORDER.USER.LOGIN',
				'USER_EMAIL' => 'ORDER.USER.EMAIL'
			],
			'filter' => [
				'ENTITY_ID' => $primaryClient->getField('ENTITY_ID'),
				'ENTITY_TYPE_ID' => $type,
			],
			'group' => ['ORDER.USER_ID']
		]);

		$anonymousId = Order\Manager::getAnonymousUserID();
		$nameFormat = \CSite::getNameFormat(false);
		while ($user = $userDataRaw->fetch())
		{
			if ($anonymousId == $user['USER_ID'])
			{
				continue;
			}

			$resultList[] = [
				'title' => \CUser::FormatName(
					$nameFormat,
					array(
						'LOGIN' => $user['USER_LOGIN'],
						'NAME' => $user['USER_NAME'],
						'LAST_NAME' => $user['USER_LAST_NAME'],
						'SECOND_NAME' => $user['USER_SECOND_NAME']
					),
					true,
					false
				),
				'subtitle' => $user['USER_LOGIN'],
				'email' => $user['USER_EMAIL'],
				'id' => $user['USER_ID']
			];
		}

		if (empty($resultList))
		{
			$resultList[] = ['subtitle' => Loc::getMessage('CRM_ORDER_EMPTY_USER_INPUT_VALUE')];
		}

		return $resultList;
	}
	protected function getLastUserClients()
	{
		$userId = $this->order->getUserId();
		$params = [
			'select' => ['ENTITY_ID', 'ENTITY_TYPE_ID'],
			'order' => ['ID' => 'DESC'],
			'group' => ['ENTITY_ID', 'ENTITY_TYPE_ID']
		];
		if (!empty($userId) && $userId !== Order\Manager::getAnonymousUserID())
		{
			$params['filter'] = [
				'ORDER.USER_ID' => $userId
			];
		}
		else
		{
			$params['limit'] = 20;
		}

		$clientDataRaw = Binding\OrderContactCompanyTable::getList($params);

		$clientData = $clientDataRaw->fetchAll();
		$preparedData = Crm\Controller\Action\Entity\SearchAction::prepareSearchResultsJson($clientData);

		$result = [];
		foreach ($preparedData as $item)
		{
			if ($item['entityType'] === CCrmOwnerType::ContactName)
			{
				$result[CCrmOwnerType::Contact][] = $item;
			}
			elseif ($item['entityType'] === CCrmOwnerType::CompanyName)
			{
				$result[CCrmOwnerType::Company][] = $item;
			}
		}

		if (empty($result[CCrmOwnerType::Contact]))
		{
			$result[CCrmOwnerType::Contact][] = ['subtitle' =>  Loc::getMessage('CRM_ORDER_EMPTY_USER_INPUT_VALUE')];
		}
		if (empty($result[CCrmOwnerType::Company]))
		{
			$result[CCrmOwnerType::Company][] = ['subtitle' => Loc::getMessage('CRM_ORDER_EMPTY_USER_INPUT_VALUE')];
		}

		return $result;
	}

	protected function getCurrencyNameShort($currency)
	{
		$result = $currency;

		if(\Bitrix\Main\Loader::includeModule('currency'))
		{
			$parsedCurrencyFormat = \CCurrencyLang::getParsedCurrencyFormat($currency);
			$key = array_search('#', $parsedCurrencyFormat);
			$parsedCurrencyFormat[$key] = '';
			$result = implode('', $parsedCurrencyFormat);
		}

		return $result;
	}

	protected function getPaymentEntityData()
	{
		$result = array(
			'PAYMENT' => array()
		);

		$index = 0;
		$payments = $this->order->getPaymentCollection();

		/** @var Order\Payment $payment */
		foreach ($payments as $payment)
		{
			$errors = [];
			$paySystemName = $payment->getPaymentSystemName();
			$paySystemLogoPath = '';
			$currency = !empty($payment->getField('CURRENCY')) ? $payment->getField('CURRENCY') : $this->order->getCurrency();
			$currencyName = $this->getCurrencyNameShort($currency);

			/** @var \Bitrix\Sale\PaySystem\Service $paySystem */
			if($paySystem = $payment->getPaySystem())
			{
				if(strlen($paySystem->getField('NAME')) > 0)
				{
					$paySystemName = htmlspecialcharsbx($paySystem->getField('NAME'));
				}

				$paySystemLogoPath = $this->getPaySystemLogoPath($paySystem->getField('LOGOTIP'), $paySystem->getField('PSA_ACTION_FILE'));

				if($paySystem->getField('ID') > 0 && $this->isPaySystemRestricted($paySystem->getField('ID'), $payment))
				{
					$errors[] = Loc::getMessage("CRM_ORDER_ERROR_PAYSYSTEM_SERVICE", ['#NAME#' => $paySystemName]);
				}
			}

			$voucherInfo = '';
			$payVoucherNum = $payment->getField('PAY_VOUCHER_NUM');

			if(!empty($payVoucherNum))
			{
				$voucherInfo = htmlspecialcharsbx($payVoucherNum);
				$payVoucherDate = htmlspecialcharsbx($payment->getField('PAY_VOUCHER_DATE'));
				$voucherInfo .= !empty($payVoucherDate) ? " ".$payVoucherDate : "";
			}

			$paymentId = $payment->getId();

			$result['PAYMENT'][] = array_merge(
				$payment->getFieldValues(),
				array(
					'ID' => $paymentId > 0 ? $id = $paymentId : $id = 'n'.$index,
					'PAY_SYSTEM_ID' => $payment->getPaymentSystemId(),
					'PAY_SYSTEM_NAME' => $paySystemName,
					'PAY_SYSTEM_LOGO_PATH' => $paySystemLogoPath,
					'DATE_PAID' => $payment->getField('DATE_PAID'),
					'NUMBER_AND_DATE' => Loc::getMessage('CRM_ORDER_PAYMENT_SUBTITLE_MASK', array(
						'#ID#' => $payment->getField('ID'),
						'#DATE_BILL#' => CCrmComponentHelper::TrimDateTimeString(
							ConvertTimeStamp(
								MakeTimeStamp(
									$payment->getField('DATE_BILL')),
								'SHORT',
								$this->arResult['SITE_ID']
							)
						)
					)),
					'FORMATTED_SUM_WITH_CURRENCY' => \CCrmCurrency::MoneyToString(
						$payment->getField('SUM'),
						$currency,
						''
					),
					'FORMATTED_SUM' => CCrmCurrency::MoneyToString(
						$payment->getField('SUM'),
						$currency,
						'#'
					),
					'VOUCHER_INFO' => $voucherInfo,
					'PAY_SYSTEMS_LIST' => $this->getPaySystemsList($payment),
					'CURRENCY_NAME' => $currencyName,
					'PAY_VOUCHER_URL' => $this->getVoucherUrl(Crm\Order\Manager::ORDER_PAYMENT_DOCUMENT_TYPE_VOUCHER, $paymentId),
					'PAY_RETURN_URL' => $this->getVoucherUrl(Crm\Order\Manager::ORDER_PAYMENT_DOCUMENT_TYPE_RETURN, $paymentId),
					'PAY_CANCEL_URL' => $this->getVoucherUrl(Crm\Order\Manager::ORDER_PAYMENT_DOCUMENT_TYPE_CANCEL, $paymentId),
					'ERRORS' => $errors
				)
			);

			$index++;
		}

		return $result;
	}

	protected function isPaySystemRestricted($paymentSystemId, $payment)
	{
		$checkServiceResult = Services\PaySystem\Restrictions\Manager::checkService(
			$paymentSystemId,
			$payment,
			Services\PaySystem\Restrictions\Manager::MODE_MANAGER
		);

		return $checkServiceResult !== Services\PaySystem\Restrictions\Manager::SEVERITY_NONE;
	}

	protected function getPaySystemsList(Order\Payment $payment)
	{
		$result = [
			[
				'ID' => '0',
				'NAME' => Loc::getMessage('CRM_ORDER_CHOOSE_PAY_SYSTEM'),
			]
		];

		$paySystems = PaySystem\Manager::getListWithRestrictions($payment, Services\PaySystem\Restrictions\Manager::MODE_MANAGER);

		foreach ($paySystems as $paySystem)
		{
			$params = [
				'ID' => $paySystem['ID'],
				'NAME' => "[".$paySystem["ID"]."] ".$paySystem["NAME"],
				'CAN_PRINT_CHECK' => $paySystem['CAN_PRINT_CHECK']
			];

			if(isset($paySystem['RESTRICTED']))
			{
				$params['RESTRICTED'] = $paySystem['RESTRICTED'];
			}

			$result[$paySystem['ID']] = $params;
		}

		return $result;
	}
	/**
	 * @param Order\Order $order
	 *
	 * @return array
	 */
	public function prepareProperties(Order\Order $order)
	{
		$rawProperties = array();
		$result = array(
			'PERSON_TYPE_ID' => $order->getPersonTypeId()
		);
		$allowConfig = $this->userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

		$filter = array('ACTIVE' => 'Y');
		if ($order->isNew())
		{
			$filter['PERSON_TYPE_ID'] = $order->getPersonTypeId();
		}

		$propertiesData = Order\Property::getList(
			array(
				'filter' => $filter,
				'order' => array('SORT')
			)
		);

		while ($property = $propertiesData->fetch())
		{
			$rawProperties[$property['ID']] = $property;
			$property['ENABLE_MENU'] = $allowConfig;
			$preparedData = $this->formatProperty($property);
			if($property['SETTINGS']['IS_HIDDEN'] === 'Y')
			{
				$result["HIDDEN"][] = $preparedData;
			}
			else
			{
				$result['ACTIVE'][] = $preparedData;
			}
		}

		$propertyCollection = $order->getPropertyCollection();

		/** @var \Bitrix\Sale\PropertyValue $propertyValue */
		foreach ($propertyCollection as $propertyValue)
		{
			$property = $propertyValue->getProperty();
			$value = $propertyValue->getValue();

			if(empty($property['ID']) && !empty($value) && !is_array($value))
			{
				$fieldValues = $propertyValue->getFieldValues();
				if (isset($rawProperties[$fieldValues['ORDER_PROPS_ID']])){
					$property = $rawProperties[$fieldValues['ORDER_PROPS_ID']];
					$property['ORDER_PROPS_ID'] = $fieldValues['ORDER_PROPS_ID'];
				}
				$property['ID'] = 'n'.$propertyValue->getId();
				$property['ENABLE_MENU'] = false;
				$property['IS_DRAG_ENABLED'] = false;
				$preparedData = $this->formatProperty($property);
				$result['ACTIVE'][] = $preparedData;
			}
		}

		return $result;
	}

	/**
	 * @param $propertyType
	 * @param bool $isMultiple
	 *
	 * @return string
	 */
	protected function resolvePropertyType($propertyType, $isMultiple = false)
	{
		switch($propertyType)
		{
			case 'STRING' :
				return 'text';
			case 'NUMBER' :
				return 'number';
			case 'Y/N' :
				return 'boolean';
			case 'DATE' :
				return 'datetime';
			case 'ENUM' :
				if($isMultiple)
					return 'multilist';
				return 'list';
			case 'FILE' :
				return 'order_property_file';
			case 'LOCATION' :
				return 'custom';
		}
		return '';
	}

	private function getPropertyLinkInfo($property)
	{
		if(!$this->propertyMap && (int)($property['PERSON_TYPE_ID']) > 0)
		{
			$matchedProperties = Bitrix\Crm\Order\Matcher\FieldMatcher::getMatchedProperties($property['PERSON_TYPE_ID']);
			foreach ($matchedProperties as $id => $match)
			{
				$entity = null;
				$entityName = '';
				if ((int)$match['CRM_ENTITY_TYPE'] === CCrmOwnerType::Contact)
				{
					$entity = \CCrmOwnerType::ContactName;
					$entityName = Loc::getMessage('CRM_ENTITY_CONTACT');
				}
				elseif ((int)$match['CRM_ENTITY_TYPE'] === CCrmOwnerType::Company)
				{
					$entity = \CCrmOwnerType::CompanyName;
					$entityName = Loc::getMessage('CRM_ENTITY_COMPANY');
				}

				if ((int)$match['CRM_FIELD_TYPE'] === \Bitrix\Crm\Order\Matcher\BaseEntityMatcher::REQUISITE_FIELD_TYPE)
				{
					$entity = \CCrmOwnerType::RequisiteName;
				}

				if ((int)$match['CRM_FIELD_TYPE'] === \Bitrix\Crm\Order\Matcher\BaseEntityMatcher::BANK_DETAIL_FIELD_TYPE)
				{
					$entity = 'BANK_DETAIL';
				}

				$field = $match['CRM_FIELD_CODE'];
				if ($field === 'RQ_ADDR')
				{
					$entity = 'ADDRESS';
					$field = $match['SETTINGS']['RQ_ADDR_CODE'];
				}

				if (!empty($entity))
				{
					$this->propertyMap[$id] = [
						'ENTITY_NAME' => $entityName,
						'CAPTION' => \Bitrix\Crm\Order\Matcher\FieldSynchronizer::getFieldCaption($entity, $field)
					];
				}


			}
		}

		return $this->propertyMap[$property['ID']];
	}

	/**
	 * @param array $property
	 *
	 * @return array
	 */
	public function formatProperty(array $property)
	{
		$propertyId = (int)$property['ORDER_PROPS_ID'] > 0 ? (int)$property['ORDER_PROPS_ID'] : $property['ID'];
		$name = 'PROPERTY_'.$property['ID'];
		$data = array(
			'propertyId' => $propertyId,
			'personTypeId' => $property['PERSON_TYPE_ID'],
			'type' => $property['TYPE']
		);
		$linked = null;
		if ($linkInfo = $this->getPropertyLinkInfo($property))
		{
			$linked = Loc::getMessage("CRM_ORDER_PROPERTY_TITLE_LINK", array(
				'#CAPTION#' => !empty($linkInfo['CAPTION']) ? htmlspecialcharsbx($linkInfo['CAPTION']) : $property['NAME'],
				'#ENTITY_NAME#' => $linkInfo['ENTITY_NAME']
			));
		}

		if($property['TYPE'] === 'LOCATION' || $property['TYPE'] === 'FILE')
		{
			$data += array(
				'edit' => "{$name}_EDIT_HTML",
				'view' => "{$name}_VIEW_HTML",
				'empty' => "{$name}_EMPTY_HTML",
				'type' => $property['TYPE'],
				'classNames' => ['crm-entity-widget-content-block-field-'.strtolower($property['TYPE'])]
			);
		}
		elseif ($property['TYPE'] === 'ENUM')
		{
			$list = Order\PropertyValue::loadOptions($propertyId);
			$options = array();
			if($property['MULTIPLE'] !== 'Y')
			{
				$options['NOT_SELECTED'] = Loc::getMessage('CRM_ORDER_NOT_SELECTED');
			}
			$data['items'] = \CCrmInstantEditorHelper::PrepareListOptions($list, $options);
		}
		return array(
			'name' => $name,
			'title' => $property['NAME'],
			'type' => $this->resolvePropertyType($property['TYPE'], $property['MULTIPLE'] === 'Y'),
			'editable' => true,
			'required' => $property['REQUIRED'] === 'Y',
			'enabledMenu' => ($property['ENABLE_MENU'] === true),
			'transferable' => false,
			'linked' => $linked,
			'isDragEnabled' => ($property['IS_DRAG_ENABLED'] !== false),
			'optionFlags' => ($property['SETTINGS']['SHOW_ALWAYS'] === 'Y') ? 1 : 0,
			'data' => $data
		);
	}
}