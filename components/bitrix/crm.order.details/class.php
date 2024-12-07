<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Catalog;
use Bitrix\Crm;
use Bitrix\Crm\Binding;
use Bitrix\Crm\Component\ComponentError;
use Bitrix\Crm\Component\EntityDetails\ComponentMode;
use Bitrix\Crm\Order;
use Bitrix\Crm\Product\Url;
use Bitrix\Crm\Security\EntityPermissionType;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\Helpers\Order\Builder;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Services;
use Bitrix\Salescenter;

if (!Main\Loader::includeModule('crm'))
{
	ShowError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));

	return;
}

if (!Main\Loader::includeModule('catalog'))
{
	ShowError(Loc::getMessage('CATALOG_MODULE_NOT_INSTALLED'));
}

class CCrmOrderDetailsComponent extends Crm\Component\EntityDetails\BaseComponent
{
	use Crm\Component\EntityDetails\SaleProps\ComponentTrait;

	/** @var Bitrix\Crm\Order\Order */
	private  $order = null;
	private  $profileId = null;

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

		$this->arResult['ENTITY_ID'] = (int)($this->arParams['~ENTITY_ID'] ?? 0);

		$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] =
			CrmCheckPath(
				'PATH_TO_USER_PROFILE',
				$this->arParams['PATH_TO_USER_PROFILE'] ?? '',
				'/company/personal/user/#user_id#/'
			);

		$this->arResult['PATH_TO_BUYER_PROFILE'] = $this->arParams['PATH_TO_BUYER_PROFILE'] =
			CrmCheckPath(
				'PATH_TO_BUYER_PROFILE',
				$this->arParams['PATH_TO_BUYER_PROFILE'] ?? '',
				'/shop/settings/sale_buyers_profile/?USER_ID=#user_id#&lang=' . LANGUAGE_ID
			);

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $this->arParams['NAME_TEMPLATE']);

		$this->arResult['PATH_TO_ORDER_CHECK_SHOW'] = CrmCheckPath(
			'PATH_TO_ORDER_CHECK_DETAILS',
			$this->arParams['PATH_TO_ORDER_CHECK_DETAILS'] ?? '',
			$APPLICATION->GetCurPage() . '?check_id=#check_id#&check&show'
		);

		$this->arResult['PATH_TO_ORDER_CHECK_CHECK_STATUS'] = CrmCheckPath(
			'PATH_TO_ORDER_CHECK_DETAILS',
			$APPLICATION->GetCurPage() . '?check_id=#check_id#&action=check_status',
			null
		);

		$this->arResult['PATH_TO_ORDER_CHECK_DELETE'] = CrmCheckPath(
			'PATH_TO_ORDER_CHECK_DELETE',
			'/bitrix/components/bitrix/crm.order.check.list/lazyload.ajax.php?id=#check_id#&action=delete&site=' . ($this->arResult['SITE_ID'] ?? '') . '&' . bitrix_sessid_get(),
			null
		);

		$this->arResult['PATH_TO_ORDER_CHECK_EDIT'] = CrmCheckPath(
			'PATH_TO_ORDER_CHECK_EDIT',
			'/shop/orders/check/details/#check_id#/?init_mode=edit',
			null
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
		if ($this->arResult['EXTERNAL_CONTEXT_ID'] === null)
		{
			$this->arResult['EXTERNAL_CONTEXT_ID'] = $this->request->get('external_context');
			if ($this->arResult['EXTERNAL_CONTEXT_ID'] === null)
			{
				$this->arResult['EXTERNAL_CONTEXT_ID'] = '';
			}
		}

		$this->arResult['ORIGIN_ID'] = $this->request->get('origin_id');
		if ($this->arResult['ORIGIN_ID'] === null)
		{
			$this->arResult['ORIGIN_ID'] = '';
		}

		$this->arParams['BUILDER_CONTEXT'] = $this->arParams['BUILDER_CONTEXT'] ?? '';
		if (
			$this->arParams['BUILDER_CONTEXT'] !== Catalog\Url\ShopBuilder::TYPE_ID
			&& $this->arParams['BUILDER_CONTEXT'] !== Url\ProductBuilder::TYPE_ID
		)
		{
			$this->arParams['BUILDER_CONTEXT'] = Catalog\Url\ShopBuilder::TYPE_ID;
		}
		$this->arResult['BUILDER_CONTEXT'] = $this->arParams['BUILDER_CONTEXT'];
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
				$order = $this->createOrder();
			}

			if ($order)
			{
				$this->setOrder($order);
			}
		}

		return $this->order;
	}

	private function createOrder()
	{
		$siteId = !empty($_REQUEST['SITE_ID']) ? $_REQUEST['SITE_ID'] : SITE_ID;
		$formData = [
			'SITE_ID' => $siteId
		];

		$userId = null;
		if ((int)$this->request->get('USER_ID') > 0)
		{
			$formData['USER_ID'] = (int)$this->request->get('USER_ID');
		}

		$clientInfo = [
			'CONTACT_IDS' => []
		];
		if (
			isset($this->arParams["EXTRAS"]['IS_SALESCENTER_ORDER_CREATION'])
			&& $this->arParams["EXTRAS"]['IS_SALESCENTER_ORDER_CREATION'] === 'Y'
			&& Crm\Integration\SalesCenterManager::getInstance()->isEnabled()
		)
		{
			if(isset($this->arParams['EXTRAS']['CLIENT_INFO']))
			{
				$clientInfo = $this->arParams['EXTRAS']['CLIENT_INFO'];
				if(isset($clientInfo['USER_ID']))
				{
					$formData['USER_ID'] = $clientInfo['USER_ID'];
				}
			}
			elseif(isset($this->arParams["EXTRAS"]['SALESCENTER_SESSION_ID']) && $this->arParams["EXTRAS"]['SALESCENTER_SESSION_ID'] > 0)
			{
				$sessionId = $this->arParams["EXTRAS"]['SALESCENTER_SESSION_ID'];
				Salescenter\Integration\ImOpenLinesManager::getInstance()->setSessionId($sessionId);
				$userId = Salescenter\Integration\ImOpenLinesManager::getInstance()->getUserId();
				if ($userId)
				{
					$formData['USER_ID'] = $userId;
				}
				$crmInfo = Salescenter\Integration\ImOpenLinesManager::getInstance()->getCrmInfo();
				if ((int)$crmInfo['COMPANY'] > 0)
				{
					$clientInfo['COMPANY_ID'] = $crmInfo['COMPANY'];
				}

				if (!empty($crmInfo['CONTACT']))
				{
					if (is_array($crmInfo['CONTACT']))
					{
						$clientInfo['CONTACT_IDS'] = $crmInfo['CONTACT'];
					}
					else
					{
						$clientInfo['CONTACT_IDS'] = [(int)$crmInfo['CONTACT']];
					}
				}
			}
		}
		else
		{
			$externalContactID = $this->request->get('contact_id');
			if($externalContactID > 0)
			{
				$clientInfo['CONTACT_IDS'][] = $externalContactID;
			}

			$externalCompanyID = $this->request->get('company_id');
			if($externalCompanyID > 0)
			{
				$clientInfo['COMPANY_ID'] = $externalCompanyID;
				if(empty($clientInfo['CONTACT_IDS']))
				{
					$contactIds = Binding\ContactCompanyTable::getCompanyContactIDs($externalCompanyID);
					foreach ($contactIds as $contactId)
					{
						if(CCrmContact::CheckReadPermission($contactId, $this->userPermissions))
						{
							$clientInfo['CONTACT_IDS'][] = $contactId;
						}
					}
				}
			}
		}

		if (!empty($clientInfo))
		{
			$formData['CLIENT'] = $clientInfo;
		}

		if (!empty($clientInfo['COMPANY_ID']))
		{
			$formData['PERSON_TYPE_ID'] = Order\PersonType::getCompanyPersonTypeId();
		}
		else
		{
			$formData['PERSON_TYPE_ID'] = Order\PersonType::getContactPersonTypeId();
		}

		if(isset($_REQUEST['product']) && is_array($_REQUEST['product']))
		{
			$basketCode = 1;
			$productParams = Sale\Helpers\Admin\Blocks\OrderBasket::getProductsData(array_keys($_REQUEST['product']), $formData['SITE_ID'], [], (int)$_REQUEST['USER_ID']);

			foreach($_REQUEST['product'] as $productId => $quantity)
			{
				if(
					!is_array($productParams[$productId])
					|| empty($productParams[$productId])
					|| (int)$productParams[$productId]['PRODUCT_ID'] <= 0
					|| $productParams[$productId]['MODULE'] == ''
				)
				{
					continue;
				}

				$formData['PRODUCT'][$basketCode] = $productParams[$productId];
				$formData['PRODUCT'][$basketCode]['BASKET_CODE'] = $basketCode;
				$formData['PRODUCT'][$basketCode]['QUANTITY'] = $quantity;
				$basketCode++;
			}
		}

		$formData['CURRENCY'] = \CCrmCurrency::GetBaseCurrencyID();
		$settings = [
			'createUserIfNeed' => '',
			'acceptableErrorCodes' => [],
			'cacheProductProviderData' => true,
		];
		$builderSettings = new Builder\SettingsContainer($settings);
		$orderBuilder = new Crm\Order\OrderBuilderCrm($builderSettings);
		$director = new Builder\Director;
		/** @var Order\Order $order */
		$order = $director->createOrder($orderBuilder, $formData);

		$fuserId = (int)$this->request->get('FUSER_ID');
		if ($order && $fuserId > 0)
		{
			$basket = Order\Basket::loadItemsForFUser($fuserId, $order->getSiteId());
			$order->setBasket($basket);
			$shipmentCollection = $order->getShipmentCollection();
			$shipmentCollection->createItem();
		}

		if($order && isset($clientInfo['DEAL_ID']) && $clientInfo['DEAL_ID'] > 0)
		{
			$binding = $order->getEntityBinding();
			if ($binding === null)
			{
				$binding = $order->createEntityBinding();
			}

			$binding->setField('OWNER_ID', $clientInfo['DEAL_ID']);
			$binding->setField('OWNER_TYPE_ID', CCrmOwnerType::Deal);
		}

		return $order;
	}

	public function setOrder(Order\Order $order)
	{
		$this->order = $order;
		$this->arResult['SITE_ID'] = $this->order->getSiteId();
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

		$restrictedModes = [ComponentMode::CREATION, ComponentMode::COPING];
		if (in_array($this->getMode(), $restrictedModes) && Crm\Restriction\OrderRestriction::isOrderLimitReached())
		{
			$this->includeComponentTemplate('restrictions');
			return;
		}

		$this->obtainOrder();
		$this->prepareEntityData($this->mode);

		//region GUID
		$this->guid = $this->arResult['GUID'] = $this->arParams['GUID'] ?? "order_{$this->entityID}_details";
		$this->arResult['EDITOR_CONFIG_ID'] = $this->arParams['EDITOR_CONFIG_ID'] ?? 'order_details';
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

		$this->prepareConfiguration();

		//region CONTROLLERS

		$controllerConfig = [
			"editorId" => $this->arResult['PRODUCT_EDITOR_ID'],
			"serviceUrl" => '/bitrix/components/bitrix/crm.order.details/ajax.php',
			"dataFieldName" => $this->arResult['PRODUCT_DATA_FIELD_NAME']
		];

		if (
			isset($this->arParams["EXTRAS"]['IS_SALESCENTER_ORDER_CREATION'])
			&& $this->arParams["EXTRAS"]['IS_SALESCENTER_ORDER_CREATION'] === 'Y'
		)
		{
			$controllerConfig['isSalesCenterOrder'] = 'Y';
			$controllerConfig['salesCenterSessionId'] = htmlspecialcharsbx($this->arParams["EXTRAS"]['SALESCENTER_SESSION_ID']);
		}

		$this->arResult['ENTITY_CONTROLLERS'] = array(
			array(
				"name" => "ORDER_CONTROLLER",
				"type" => "order_controller",
				"config" => $controllerConfig
			)
		);
		//endregion

		//region Tabs
		$this->arResult['TABS'] = array();

		$productsParams = [
			'INTERNAL_FILTER' => array('ORDER_ID' => $this->entityID),
			'PATH_TO_ORDER_PRODUCT_LIST' => '/bitrix/components/bitrix/crm.order.product.list/class.php?&site='.$this->order->getSiteId().'&'.bitrix_sessid_get(),
			'ACTION_URL' => '/bitrix/components/bitrix/crm.order.product.list/lazyload.ajax.php?&site='.$this->order->getSiteId().'&'.bitrix_sessid_get(),
			'ORDER_ID' => $this->order->getId(),
			'SITE_ID' => $this->order->getSiteId(),
			'BUILDER_CONTEXT' => $this->arResult['BUILDER_CONTEXT']
		];

		if ($this->mode === ComponentMode::CREATION && (int)$this->request->get('FUSER_ID') > 0)
		{
			$productsParams['FUSER_ID'] = $this->order->getBasket()->getFUserId();
		}

		$this->arResult['PRODUCT_COMPONENT_DATA'] = array(
			'template' => '.default',
			'params' => $productsParams
		);

		$relationManager = Crm\Service\Container::getInstance()->getRelationManager();
		$this->arResult['TABS'] = array_merge(
			$this->arResult['TABS'],
			$relationManager->getRelationTabsForDynamicChildren(
				\CCrmOwnerType::Order,
				$this->entityID,
				($this->entityID === 0)
			)
		);

		if ($this->mode !== ComponentMode::COPING && $this->mode !== ComponentMode::CREATION)
		{
			$productComponentData = $this->arResult['PRODUCT_COMPONENT_DATA'];
			$productComponentData['signedParameters'] = \CCrmInstantEditorHelper::signComponentParams(
				(array)$productComponentData['params'],
				'crm.order.product.list'
			);
			unset($productComponentData['params']);

			$this->arResult['TABS'][] = array(
				'id' => 'tab_products',
				'name' => Loc::getMessage('CRM_ORDER_TAB_PRODUCTS'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.order.product.list/lazyload.ajax.php?&site='.$this->order->getSiteId().'&'.bitrix_sessid_get(),
					'componentData' => $productComponentData
				),
				'html' => '<div class="crm-entity-section-product-loader"></div>'
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
						'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
							'INTERNAL_FILTER' => array('ORDER_ID' => $this->entityID),
							'ORDER_ID' => $this->order->getId(),
							'ENABLE_TOOLBAR' => true,
							'PATH_TO_ORDER_PAYMENT_LIST' => '/bitrix/components/bitrix/crm.order.payment.list/class.php?&site='.$this->arResult['SITE_ID'].'&'.bitrix_sessid_get()
						], 'crm.order.payment.list')
					)
				)
			);

			$licensePrefix = Main\Loader::IncludeModule("bitrix24") ? \CBitrix24::getLicensePrefix() : "";
			if (!Main\ModuleManager::isModuleInstalled("bitrix24") || in_array($licensePrefix, array("ru", "ua")))
			{
				if ($this->request->get('tab') !== 'check')
				{
					$this->arResult['TABS'][] = array(
						'id' => 'tab_check',
						'name' => Loc::getMessage('CRM_ORDER_TAB_CHECK'),
						'loader' => array(
							'serviceUrl' => '/bitrix/components/bitrix/crm.order.check.list/lazyload.ajax.php?&site'.$this->arResult['SITE_ID'].'&'.bitrix_sessid_get(),
							'componentData' => array(
								'template' => '',
								'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
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
								], 'crm.order.check.list')
							)
						)
					);
				}
				else
				{
					ob_start();
					$componentParams = [
						'ENABLE_TOOLBAR' => true,
						'CHECK_COUNT' => '20',
						'OWNER_ID' => $this->entityID,
						'AJAX_MODE' => 'N',
						'AJAX_OPTION_JUMP' => 'N',
						'AJAX_OPTION_HISTORY' => 'N',
						'OWNER_TYPE' => CCrmOwnerType::Order,
						'PATH_TO_ORDER_CHECK_SHOW' => $this->arResult['PATH_TO_ORDER_CHECK_SHOW'],
						'PATH_TO_ORDER_CHECK_EDIT' => $this->arResult['PATH_TO_ORDER_CHECK_EDIT'],
						'PATH_TO_ORDER_CHECK_CHECK_STATUS' => $this->arResult['PATH_TO_ORDER_CHECK_CHECK_STATUS'],
						'PATH_TO_ORDER_CHECK_DELETE' => $this->arResult['PATH_TO_ORDER_CHECK_DELETE'],
						'GRID_ID_SUFFIX' => 'CHECK_DETAILS',
						'TAB_ID' => 'tab_check',
						'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE']
					];
					$APPLICATION->IncludeComponent('bitrix:crm.order.check.list',
						'',
						$componentParams,
						false,
						array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
					);
					$checkListHtml = ob_get_contents();
					ob_end_clean();

					$this->arResult['TABS'][] = array(
						'id' => 'tab_check',
						'active' => true,
						'name' => Loc::getMessage('CRM_ORDER_TAB_CHECK'),
						'html' => $checkListHtml
					);
				}
			}

			$this->arResult['TABS'][] = array(
				'id' => 'tab_shipment',
				'name' => Loc::getMessage('CRM_ORDER_TAB_SHIPMENT'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.order.shipment.list/lazyload.ajax.php?&site='.$this->arResult['SITE_ID'].'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '.default',
						'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
							'INTERNAL_FILTER' => array('ORDER_ID' => $this->entityID),
							'ENABLE_TOOLBAR' => true,
							'PATH_TO_ORDER_SHIPMENT_LIST' => '/bitrix/components/bitrix/crm.order.shipment.list/class.php?&site='.$this->arResult['SITE_ID'].'&'.bitrix_sessid_get(),
							'BUILDER_CONTEXT' => $this->arResult['BUILDER_CONTEXT']
						], 'crm.order.shipment.list')
					)
				)
			);

			if(\Bitrix\Crm\Automation\Factory::isAutomationAvailable(CCrmOwnerType::Order))
			{
				$robotsTab = [
					'id' => 'tab_automation',
					'name' => Loc::getMessage('CRM_ORDER_TAB_AUTOMATION'),
					'url' => Container::getInstance()->getRouter()->getAutomationUrl(CCrmOwnerType::Order)
						->addParams(['id' => $this->entityID]),
				];

				$toolsManager = \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager();
				if (!$toolsManager->checkRobotsAvailability())
				{
					$robotsTab['availabilityLock'] = \Bitrix\Crm\Restriction\AvailabilityManager::getInstance()
						->getRobotsAvailabilityLock()
					;
					$robotsTab['url'] = '';
				}

				$this->arResult['TABS'][] = $robotsTab;
				$checkAutomationTourGuideData = CCrmBizProcHelper::getHowCheckAutomationTourGuideData(
					CCrmOwnerType::Order,
					0,
					$this->userID
				);
				if ($checkAutomationTourGuideData)
				{
					$this->arResult['AUTOMATION_CHECK_AUTOMATION_TOUR_GUIDE_DATA'] = [
						'options' => $checkAutomationTourGuideData,
					];
				}
				unset($checkAutomationTourGuideData);
			}

			$this->arResult['TABS'][] = array(
				'id' => 'tab_tree',
				'name' => Loc::getMessage('CRM_ORDER_TAB_TREE'),
				'loader' => array(
					'serviceUrl' => '/bitrix/components/bitrix/crm.entity.tree/lazyload.ajax.php?&site='.$this->arResult['SITE_ID'].'&'.bitrix_sessid_get(),
					'componentData' => array(
						'template' => '.default',
						'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
							'ENTITY_ID' => $this->entityID,
							'ENTITY_TYPE_NAME' => CCrmOwnerType::OrderName,
						], 'crm.entity.tree')
					)
				)
			);

			$this->arResult['TABS'][] = $this->getEventTabParams();
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
			CCrmEvent::RegisterViewEvent(CCrmOwnerType::Order, $this->entityID, $this->userID);
		}
		//endregion
		if(Bitrix\Main\Loader::includeModule('pull'))
		{
			\CPullWatch::Add($this->userID, 'CRM_ENTITY_ORDER');
			\CPullWatch::Add($this->userID, 'CRM_ENTITY_ORDER_PAYMENT');
			\CPullWatch::Add($this->userID, 'CRM_ENTITY_ORDER_SHIPMENT');
			\CPullWatch::Add($this->userID, 'SALE_DELIVERY_SERVICE');
			\CPullWatch::Add($this->userID, 'SALE_DELIVERY_REQUEST');
		}

		$this->includeComponentTemplate();
	}
	public function prepareFieldInfos()
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

		$tradingPlatforms = [
			0 => Loc::getMessage('CRM_ORDER_STORE_NOT_CHOSEN')
		];

		if (Main\Loader::includeModule('sale'))
		{
			$tradingPlatforms += Sale\TradingPlatform\Manager::getActivePlatformList();
		}

		$this->arResult['ORDER_PROPERTIES'] = $this->prepareProperties(
			$this->order->getPropertyCollection(),
			Order\Property::class,
			$this->order->getPersonTypeId(),
			$this->order->isNew()
		);

		$shipment = $this->getFirstShipment($this->order);
		$this->arResult['SHIPMENT_PROPERTIES'] = $shipment
			? $this->prepareProperties(
				$shipment->getPropertyCollection(),
				Order\ShipmentProperty::class,
				$shipment->getPersonTypeId(),
				($shipment->getId() === 0)
			)
			: [];

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
				'type' => 'order_trading_platform',
				'editable' => count($tradingPlatforms) > 0
					&& (string)($this->arParams['EXTRAS']['IS_SALESCENTER_ORDER_CREATION'] ?? '') !== 'Y' ,
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
				'editable' => !(isset($this->arParams['EXTRAS']['IS_SALESCENTER_ORDER_CREATION']) && $this->arParams['EXTRAS']['IS_SALESCENTER_ORDER_CREATION'] === 'Y' && $this->order->getUserId()) ,
				'requiredConditionally' => true,
				'data' => array(
					'enableEditInView' => true,
					'formated' => 'USER_FORMATTED_NAME',
					'position' => 'USER_WORK_POSITION',
					'photoUrl' => 'USER_PHOTO_URL',
					'showUrl' => 'PATH_TO_USER',
					'defaultUserList' => 'USER_LIST_SELECT',
					'pathToProfile' => $this->arResult['PATH_TO_BUYER_PROFILE'] ?? '',
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
					'photoUrl' => 'RESPONSIBLE_PERSONAL_PHOTO',
					'showUrl' => 'PATH_TO_RESPONSIBLE_USER',
					'pathToProfile' => $this->arResult['PATH_TO_USER_PROFILE'] ?? ''
				)
			),
			array(
				'name' => 'DATE_INSERT',
				'title' => Loc::getMessage('CRM_ORDER_FIELD_DATE_INSERT'),
				'type' => 'datetime',
				'editable' => false,
				'data' => [
					'enableTime' => true,
					'dateViewFormat' =>
						\Bitrix\Main\Application::getInstance()->getContext()->getCulture()->getLongDateFormat()
						. ' '
						. \Bitrix\Main\Application::getInstance()->getContext()->getCulture()->getShortTimeFormat()
					,
				],
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
					),
					'clientEditorFieldsParams' => CCrmComponentHelper::prepareClientEditorFieldsParams(),
				)
			)
		);

		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'SHIPMENT',
			'type' => 'shipment',
			'editable' => true,
			'transferable' => false,
			'enabledMenu' => false,
			'required' => true,
			'data' => array(
				'addShipmentDocumentUrl' => '/bitrix/components/bitrix/crm.order.shipment.document/slider.ajax.php?'.bitrix_sessid_get().'&site='.$this->arResult['SITE_ID'],
			)
		);

		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'SHIPMENT_PROPERTIES',
			'title' => Loc::getMessage('CRM_ORDER_SHIPMENT_PROPS'),
			'type' => 'order_property_wrapper',
			'transferable' => false,
			'editable' => true,
			'isDragEnabled' => $this->userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'),
			'elements' => [],
			'sortedElements' => [
				'active' => isset($this->arResult['SHIPMENT_PROPERTIES']["ACTIVE"]) && is_array($this->arResult['SHIPMENT_PROPERTIES']["ACTIVE"])
					? $this->arResult['SHIPMENT_PROPERTIES']["ACTIVE"]
					: [],
				'hidden' => isset($this->arResult['SHIPMENT_PROPERTIES']["HIDDEN"]) && is_array($this->arResult['SHIPMENT_PROPERTIES']["HIDDEN"])
					? $this->arResult['SHIPMENT_PROPERTIES']["HIDDEN"]
					: [],
			],
			'data' => [
				'entityType' => 'shipment',
				'doNotDisplayInShowFieldList' => true
			]
		);

		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'PAYMENT',
			'type' => 'payment',
			'editable' => true,
			'enabledMenu' => false,
			'transferable' => false,
			'required' => true,
			'data' => array(
				'addPaymentDocumentUrl' => '/bitrix/components/bitrix/crm.order.payment.voucher/slider.ajax.php?'.bitrix_sessid_get().'&site='.$this->arResult['SITE_ID'],
			)
		);

		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'USER_BUDGET',
			'title' => Loc::getMessage('CRM_ORDER_FIELD_BUDGET'),
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

		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'PROPERTIES',
			'type' => 'order_property_wrapper',
			'transferable' => false,
			'editable' => true,
			'isDragEnabled' => $this->userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'),
			'elements' => [],
			'sortedElements' => [
				'active' => isset($this->arResult['ORDER_PROPERTIES']["ACTIVE"]) && is_array($this->arResult['ORDER_PROPERTIES']["ACTIVE"])
					? $this->arResult['ORDER_PROPERTIES']["ACTIVE"]
					: [],
				'hidden' => isset($this->arResult['ORDER_PROPERTIES']["HIDDEN"]) && is_array($this->arResult['ORDER_PROPERTIES']["HIDDEN"])
					? $this->arResult['ORDER_PROPERTIES']["HIDDEN"]
					: [],
			],
			'data' => array(
				'managerUrl' => '/shop/orderform/#person_type_id#/',
				'editorUrl' => '/shop/orderform/#person_type_id#/prop/#property_id#/',
				'entityType' => 'order',
			)
		);
		$personTypeList = isset($this->arResult['PERSON_TYPES']) && is_array($this->arResult['PERSON_TYPES'])
			? $this->arResult['PERSON_TYPES']
			: [];
		$personTypeOptions = [];

		if (empty($personTypeList))
		{
			$personTypeOptions = [
				'NOT_SELECTED' => Loc::getMessage('CRM_ORDER_STORE_NOT_CHOSEN'),
				'NOT_SELECTED_VALUE' => '',
			];
		}
		$this->arResult['ENTITY_FIELDS'][] = array(
			'name' => 'PERSON_TYPE_ID',
			'title' => Loc::getMessage('CRM_ORDER_SECTION_PERSON_TYPE_ID'),
			'required' => true,
			'transferable' => false,
			'type' => 'order_person_type',
			'editable' => true,
			'data' => array(
				'items'=> \CCrmInstantEditorHelper::PrepareListOptions($personTypeList, $personTypeOptions)
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
				'items'=> $this->loadProfiles(
					$this->order->getUserId(),
					$this->entityData['PERSON_TYPE_ID'] ?? null
				)
			)
		);

		if($this->mode !== ComponentMode::COPING)
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

		\Bitrix\Crm\Tracking\UI\Details::appendEntityFields($this->arResult['ENTITY_FIELDS']);

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

		$this->entityData = $this->order->getFieldValues();
		if($prepareDataMode === ComponentMode::CREATION)
		{
			//region Default Dates
			$dateInsert = time() + \CTimeZone::GetOffset();
			$time = localtime($dateInsert, true);
			$dateInsert -= $time['tm_sec'] + 60 * $time['tm_min'] + 3600 * $time['tm_hour'];
			$this->entityData['DATE_INSERT'] = ConvertTimeStamp($dateInsert, 'SHORT', $this->arResult['SITE_ID']);
			$this->entityData['CURRENCY'] = \CCrmCurrency::GetBaseCurrencyID();

			//region Default Responsible
			if (!Crm\Settings\OrderSettings::getCurrent()->getDefaultResponsibleId())
			{
				$this->entityData['RESPONSIBLE_ID'] = $this->userID;
			}
			//endregion

			//region Default Stage ID
			$statusList = $this->getStatusList(EntityPermissionType::CREATE);
			if(!empty($statusList))
			{
				$requestStatusId = $this->request->get('stage_id');
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

			//region Properties
			$propertyEntityClassNames = [
				Order\Property::class,
				Order\ShipmentProperty::class,
			];
			foreach ($propertyEntityClassNames as $propertyEntityClassName)
			{
				$propertiesData = $propertyEntityClassName::getList(
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
					$simplePropertyTypes = ['STRING', 'NUMBER', 'ENUM', 'DATE', 'Y/N'];
					if (!in_array($property['TYPE'], $simplePropertyTypes, true))
					{
						$property['ONCHANGE'] = "BX.onCustomEvent('CrmOrderPropertySetCustom', ['{$name}']);";
						if($property['TYPE'] === 'LOCATION')
						{
							$property['IS_SEARCH_LINE'] = true;
						}

						$html = \Bitrix\Sale\Internals\Input\Manager::getEditHtml(
							$name,
							$property,
							$defaultValue
						);

						$this->entityData["{$name}_EDIT_HTML"] = $html;
					}

					$this->entityData[$name] = $defaultValue;
				}
			}
			// endregion

			$this->entityData['USER_PROFILE'] = !is_null($this->profileId) ? (int)$this->profileId : 'NEW';
			$this->entityData['OLD_USER_ID'] = null;
			$this->entityData['OLD_USER_PROFILE'] = null;
			$this->entityData['OLD_TRADING_PLATFORM'] = null;
		}
		else
		{
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

			$tradingCollection = $this->order->getTradeBindingCollection();

			/** @var Sale\TradeBindingEntity $item */
			foreach ($tradingCollection as $item)
			{
				$this->entityData['TRADING_PLATFORM'] = $item->getField('TRADING_PLATFORM_ID');
			}

			$this->entityData['OLD_TRADING_PLATFORM'] = $this->entityData['TRADING_PLATFORM'] ?? null;
			//endregion
		}
		$this->entityData = array_merge(
			$this->entityData,
			$this->getPropertyEntityData($this->order->getPropertyCollection())
		);

		//region Responsible
		$responsibleId = (int)($this->entityData['RESPONSIBLE_ID'] ?? 0);
		if ($responsibleId > 0)
		{
			$this->entityData += $this->getUserEntityData($responsibleId, 'RESPONSIBLE');
			$this->entityData['PATH_TO_RESPONSIBLE_USER'] = CComponentEngine::MakePathFromTemplate(
				$this->arResult['PATH_TO_USER_PROFILE'],
				['user_id' => $responsibleId]
			);
		}
		//endregion

		//region User ID
		$userId = (int)($this->entityData['USER_ID'] ?? 0);
		if ($userId > 0)
		{
			$this->entityData += $this->getUserEntityData($userId, 'USER');
			$this->entityData['PATH_TO_USER'] = CComponentEngine::MakePathFromTemplate(
				$this->arResult['PATH_TO_BUYER_PROFILE'],
				array('user_id' => $userId)
			);
		}
		//endregion

		//region PRICE & Currency
		$this->entityData['FORMATTED_PRICE_WITH_CURRENCY'] = \CCrmCurrency::MoneyToString(
			$this->entityData['PRICE'] ?? 0.0,
			$this->entityData['CURRENCY'],
			''
		);
		$this->entityData['FORMATTED_PRICE'] = str_replace('&nbsp;', ' ', \CCrmCurrency::MoneyToString(
			$this->entityData['PRICE'] ?? 0.0,
			$this->entityData['CURRENCY'],
			'#'
		));
		//endregion

		//region USER_BUDGET
		$this->entityData['BUDGET'] = \Bitrix\Sale\Internals\UserBudgetPool::getUserBudget(
			$this->entityData['USER_ID'],
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

		//region Client Data & Multifield Data
		$ccCollection = $this->order->getContactCompanyCollection();

		$companyId = 0;
		if($company = $ccCollection->getPrimaryCompany())
		{
			$companyId = $company->getField('ENTITY_ID');
			$this->entityData['COMPANY_ID'] = $companyId;
		}

		$contactIDs = [];
		$contacts = $ccCollection->getContacts();
		foreach ($contacts as $contact)
		{
			$contactIDs[] = $contact->getField('ENTITY_ID');
		}

		if (!empty($companyId) || !empty($contactIDs))
		{
			$this->entityData['CLIENT'] = [
				'COMPANY_ID' => $companyId,
				'CONTACT_IDS' => $contactIDs
			];
		}

		$clientInfo = array();
		if ($companyId > 0)
		{
			\CCrmComponentHelper::prepareMultifieldData(
				\CCrmOwnerType::Company,
				[$companyId],
				[
					'PHONE',
					'EMAIL',
				],
				$this->entityData
			);

			$isEntityReadPermitted = \CCrmCompany::CheckReadPermission($companyId, $this->userPermissions);
			$companyInfo = \CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::CompanyName,
				$companyId,
				array(
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_EDIT_REQUISITE_DATA' => true,
					'REQUIRE_MULTIFIELDS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
				)
			);

			$clientInfo['COMPANY_DATA'] = [$companyInfo];
		}

		$clientInfo['CONTACT_DATA'] = array();
		$iteration= 0;

		\CCrmComponentHelper::prepareMultifieldData(
			\CCrmOwnerType::Contact,
			$contactIDs,
			[
				'PHONE',
				'EMAIL',
			],
			$this->entityData
		);
		foreach ($contactIDs as $contactID)
		{
			$isEntityReadPermitted = CCrmContact::CheckReadPermission($contactID, $this->userPermissions);
			$clientInfo['CONTACT_DATA'][] = CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::ContactName,
				$contactID,
				[
					'ENTITY_EDITOR_FORMAT' => true,
					'IS_HIDDEN' => !$isEntityReadPermitted,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_EDIT_REQUISITE_DATA' => ($iteration === 0), // load full requisite data for first item only (due to performance optimisation)
					'REQUIRE_MULTIFIELDS' => true,
					'REQUIRE_BINDINGS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
					'NORMALIZE_MULTIFIELDS' => true,
				]
			);
			$iteration++;
		}
		$this->entityData['CLIENT_INFO'] = $clientInfo;

		$this->entityData['REQUISITE_BINDING'] = $this->order->getRequisiteLink();

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

				if(++$productRowCount > 10)
				{
					continue;
				}

				$itemData = $item->toArray();
				$itemData['PRODUCT_NAME'] = $itemData['NAME'];
				$productRowInfos[] = EditorAdapter::formProductRowData(Crm\ProductRow::createFromArray($itemData), $item->getCurrency());
			}

			$this->entityData['PRODUCT_ROW_SUMMARY'] = [
				'count' => $productRowCount,
				'total' => CCrmCurrency::MoneyToString($productRowTotalSum, $this->entityData['CURRENCY']),
				'items' => $productRowInfos,
				'isReadOnly' => false,
			];
		}
		else
		{
			$this->entityData['PRODUCT_ROW_SUMMARY'] = [
				'isReadOnly' => false,
			];
		}

		$this->entityData += $this->getPaymentEntityData();
		$this->entityData += $this->getShipmentEntityData();
		$this->arResult['PERSON_TYPES'] = array();
		$personTypes = \Bitrix\Crm\Order\PersonType::load($this->arResult['SITE_ID']);
		if (
			empty($this->entityData['PERSON_TYPE_ID'])
			|| !array_key_exists((int)$this->entityData['PERSON_TYPE_ID'], $personTypes)
		)
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
					'#ACCOUNT_NUMBER#' => $this->entityData['ACCOUNT_NUMBER'] ?? ''
				));

			if (!empty($this->entityData['ORDER_TOPIC']))
			{
				$title .= ' "' . $this->entityData['ORDER_TOPIC'] . '"';
			}
		}

		$this->entityData['TITLE'] = $title;
		$this->entityData['STORAGE_TYPE_ID'] =  Bitrix\Crm\Integration\StorageType::File;

		//region User Fields
		foreach ($this->userFields as $fieldName => $userField)
		{
			$fieldValue = $userField['VALUE'] ?? '';
			$fieldData = $this->userFieldInfos[$fieldName] ?? null;
			if (!is_array($fieldData))
			{
				continue;
			}

			$isEmptyField = true;
			$fieldParams = $fieldData['data']['fieldInfo'];

			if(
				(is_string($fieldValue) && $fieldValue !== '')
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

		\Bitrix\Crm\Tracking\UI\Details::prepareEntityData(
			\CCrmOwnerType::Order,
			$this->order->getId(),
			$this->entityData
		);

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

		$userFields["{$name}_LOGIN"] = $user['LOGIN'] ?? '';
		$userFields["{$name}_NAME"] = $user['NAME'] ?? '';
		$userFields["{$name}_SECOND_NAME"] = $user['SECOND_NAME'] ?? '';
		$userFields["{$name}_LAST_NAME"] = $user['LAST_NAME'] ?? '';
		$userFields["{$name}_PERSONAL_PHOTO"] = $user['PERSONAL_PHOTO'] ?? '';

		$userFields["{$name}_FORMATTED_NAME"] =
			\CUser::FormatName(
				$this->arResult['NAME_TEMPLATE'],
				array(
					'LOGIN' => $userFields["{$name}_LOGIN"] ?? '',
					'NAME' => $userFields["{$name}_NAME"] ?? '',
					'LAST_NAME' => $userFields["{$name}_LAST_NAME"] ?? '',
					'SECOND_NAME' => $userFields["{$name}_SECOND_NAME"] ?? '',
				),
				true,
				false
			);

		$assignedByPhotoID = (int)($userFields["{$name}_PERSONAL_PHOTO"] ?? 0);
		if ($assignedByPhotoID > 0)
		{
			$file = new CFile();
			$fileInfo = $file->ResizeImageGet(
				$assignedByPhotoID,
				array('width' => 60, 'height'=> 60),
				BX_RESIZE_IMAGE_EXACT
			);

			if (is_array($fileInfo) && isset($fileInfo['src']))
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

				$documentDate = $shipment->getField('DELIVERY_DOC_DATE');
				if ($documentDate)
				{
					$documentDate = htmlspecialcharsbx(
						(new Main\Type\Date($documentDate))->toString()
					);
					$documentInfo .= !empty($documentDate) ? " ".$documentDate : "";
				}
			}

			$deliveryServiceName = $shipment->getDeliveryName();
			$logoPath = $this->getPath().'/images/delivery_logo.png';

			$delivery = $shipment->getDelivery();
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
					'#ID#' => htmlspecialcharsbx($shipment->getField('ACCOUNT_NUMBER')),
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
				'FORMATTED_PRICE_DELIVERY' => str_replace('&nbsp;', ' ', CCrmCurrency::MoneyToString(
					$shipment->getField('PRICE_DELIVERY'),
					$shipment->getField('CURRENCY'),
					'#'
				)),
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
				$fields['FORMATTED_PRICE_DELIVERY_CALCULATED'] =  str_replace('&nbsp;', ' ', CCrmCurrency::MoneyToString(
					$shipment->getField('PRICE_DELIVERY'),
					$shipment->getField('CURRENCY'),
					'#'
				));
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

			// @TODO add multi-shipment properties support
			$propertyEntityData = $this->getPropertyEntityData($shipment->getPropertyCollection());
			foreach ($propertyEntityData as $propertyKey => $value)
			{
				$result[$propertyKey] = $value;
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

		if($logotip <> '' )
		{
			$paySystemLogoPath = intval($logotip) > 0 ? \CFile::GetPath($logotip) : '';
		}
		elseif($psaActionFile <> '')
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
			$resultList[] = ['subTitle' => Loc::getMessage('CRM_ORDER_EMPTY_USER_INPUT_VALUE_WITH_CLIENT')];
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
				'id' => $user['USER_ID'],
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
				'subTitle' => $user['USER_LOGIN'],
				'attributes' => [
					'email' => [
						['value' => $user['USER_EMAIL']]
					]
				]
			];
		}

		if (empty($resultList))
		{
			$resultList[] = ['subTitle' => Loc::getMessage('CRM_ORDER_EMPTY_USER_INPUT_VALUE')];
		}

		return $resultList;
	}
	protected function getLastUserClients()
	{
		$userId = $this->order->getUserId();

		if ($userId === Order\Manager::getAnonymousUserID() || (int)$userId === 0)
		{
			return [
				CCrmOwnerType::Contact => [
					['subTitle' =>  Loc::getMessage('CRM_ORDER_EMPTY_USER_INPUT_VALUE')]
				],
				CCrmOwnerType::Company => [
					['subTitle' => Loc::getMessage('CRM_ORDER_EMPTY_USER_INPUT_VALUE')]
				]
			];
		}

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

		$params['limit'] = 20;
		$clientDataRaw = Binding\OrderContactCompanyTable::getList($params);

		$clientData = $clientDataRaw->fetchAll();
		$preparedData = Crm\Controller\Action\Entity\SearchAction::prepareSearchResultsJson($clientData);

		$result = [];
		foreach ($preparedData as $item)
		{
			if ($item['type'] === CCrmOwnerType::ContactName)
			{
				$result[CCrmOwnerType::Contact][] = $item;
			}
			elseif ($item['type'] === CCrmOwnerType::CompanyName)
			{
				$result[CCrmOwnerType::Company][] = $item;
			}
		}

		if (empty($result[CCrmOwnerType::Contact]))
		{
			$result[CCrmOwnerType::Contact][] = ['subTitle' =>  Loc::getMessage('CRM_ORDER_EMPTY_USER_INPUT_VALUE')];
		}
		if (empty($result[CCrmOwnerType::Company]))
		{
			$result[CCrmOwnerType::Company][] = ['subTitle' => Loc::getMessage('CRM_ORDER_EMPTY_USER_INPUT_VALUE')];
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
			$paySystemLogoPath = $this->getPaySystemLogoPath("", "");
			$currency = !empty($payment->getField('CURRENCY')) ? $payment->getField('CURRENCY') : $this->order->getCurrency();
			$currencyName = $this->getCurrencyNameShort($currency);

			/** @var \Bitrix\Sale\PaySystem\Service $paySystem */
			if($paySystem = $payment->getPaySystem())
			{
				if($paySystem->getField('NAME') <> '')
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
						'#ID#' => htmlspecialcharsbx($payment->getField('ACCOUNT_NUMBER')),
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
					'FORMATTED_SUM' => str_replace('&nbsp;', ' ', CCrmCurrency::MoneyToString(
						$payment->getField('SUM'),
						$currency,
						'#'
					)),
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

	public function prepareConfiguration()
	{
		if (isset($this->arResult['ENTITY_CONFIG']))
		{
			return $this->arResult['ENTITY_CONFIG'];
		}

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

		if($this->mode !== ComponentMode::COPING)
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
					array('name' => 'CLIENT'),
					array('name' => 'USER_BUDGET')
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

		$this->arResult['ENTITY_CONFIG'][] = array(
			'name' => 'products',
			'title' => Loc::getMessage('CRM_ORDER_SECTION_PRODUCTS'),
			'type' => 'section',
			'elements' => array(
				array('name' => 'PRODUCT_ROW_SUMMARY')
			)
		);

		$this->arResult['ENTITY_CONFIG'][] = array(
			'name' => 'properties',
			'title' => Loc::getMessage('CRM_ORDER_SECTION_PROPERTIES'),
			'type' => 'section',
			'data' => array(
				'showButtonPanel' => false
			),
			'elements' => 	array(
				array('name' => 'USER_PROFILE'),
				array('name' => 'PROPERTIES')
			)
		);

		$this->arResult['ENTITY_CONFIG'][] = array(
			'name' => 'payment',
			'title' => Loc::getMessage('CRM_ORDER_SECTION_PAYMENT'),
			'type' => 'section',
			'data' => array(
				'showButtonPanel' => false,
				'enableToggling' =>  false
			),
			'elements' => 	array(
				array('name' => 'PAYMENT')
			)
		);

		$this->arResult['ENTITY_CONFIG'][] = array(
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
		);

		if (in_array($this->mode, [ComponentMode::CREATION, ComponentMode::COPING], true))
		{
			$this->arResult['ENTITY_CONFIG'][] = array(
				'name' => 'shipment_properties',
				'title' => Loc::getMessage('CRM_ORDER_SECTION_SHIPMENT_PROPERTIES'),
				'type' => 'section',
				'forceInclude' => true,
				'data' => array(
					'showButtonPanel' => false,
					'onlyDefault' => 'Y',
				),
				'elements' => 	array(
					array('name' => 'SHIPMENT_PROPERTIES')
				)
			);
		}

		return $this->arResult['ENTITY_CONFIG'];
	}

	/**
	 * @param Order\Order $order
	 * @return Order\Shipment|null
	 */
	public function getFirstShipment(Order\Order $order): ?Order\Shipment
	{
		$result = null;

		foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipment)
		{
			$result = $shipment;
			break;
		}

		return $result;
	}

	public function prepareKanbanConfiguration()
	{
		$scheme = [
			[
				'name' => 'main',
				'title' => Loc::getMessage('CRM_ORDER_SECTION_MAIN'),
				'type' => 'section',
				'elements' => [
					['name' => 'TITLE'],
					['name' => 'ID'],
					['name' => 'SOURCE_ID'],
					['name' => 'PRICE'],
					['name' => 'CURRENCY'],
					['name' => 'CLIENT'],
					['name' => 'DATE_INSERT'],
					['name' => 'STATUS_ID'],
					['name' => 'PROBLEM_NOTIFICATION'],
					['name' => 'PAYMENT'],
					['name' => 'SHIPMENT'],
					['name' => 'USER'],
					['name' => 'RESPONSIBLE_ID'],
					['name' => 'CANCELED'],
					['name' => 'PAYED'],
					['name' => 'DEDUCTED'],
					['name' => 'PERSON_TYPE_ID'],
					['name' => 'ORDER_TOPIC'],
					['name' => 'ACCOUNT_NUMBER'],
					['name' => 'DATE_UPDATE'],
					['name' => 'XML_ID'],
				]
			]
		];

		$propertyElements = [];
		$propertiesRaw = \Bitrix\Crm\Order\Property::getList([
			'filter' => [
				'=ACTIVE' => 'Y',
				'=TYPE' => ['STRING', 'NUMBER', 'Y/N', 'ENUM', 'DATE']
			],
			'select' => ["ID"],
		]);

		while ($property = $propertiesRaw->fetch())
		{
			$propertyElements[] = ['name' => 'PROPERTY_'.$property['ID']];
		}
		if (!empty($propertyElements))
		{
			$scheme[] = [
				'name' => 'properties',
				'title' => Loc::getMessage('CRM_ORDER_SECTION_PROPERTIES'),
				'type' => 'section',
				'elements' => $propertyElements
			];
		}

		$scheme[] = [
			'name' => 'additional',
			'title' => Loc::getMessage('CRM_ORDER_SECTION_ADDITIONAL'),
			'type' => 'section',
			'elements' => []
		];
		return $scheme;
	}

	protected function getEventTabParams(): array
	{
		return CCrmComponentHelper::getEventTabParams(
			$this->entityID,
			Loc::getMessage('CRM_ORDER_TAB_EVENT'),
			CCrmOwnerType::OrderName,
			$this->arResult
		);
	}
}
