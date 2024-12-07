<?php

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('BX_PUBLIC_MODE', true);
define('DisableEventsCheck', true);

use Bitrix\Catalog;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Order\Permissions;
use Bitrix\Crm\Restriction\OrderRestriction;
use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Sale\Helpers\Admin\OrderEdit;
use Bitrix\Sale\Helpers\Order\Builder;
use Bitrix\Sale\Payment;
use Bitrix\SalesCenter;

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

Loc::loadMessages(__FILE__);

if (!Loader::includeModule('crm'))
{
	die('Can\'t include module CRM');
}

final class AjaxProcessor extends \Bitrix\Crm\Order\AjaxProcessor
{
	use \Bitrix\Crm\Component\EntityDetails\SaleProps\AjaxProcessorTrait;

	protected function getActionMethodName($action)
	{
		if ($action === 'GET_SECONDARY_ENTITY_INFOS')
		{
			$action = 'getSecondaryEntityInfos';
		}

		return parent::getActionMethodName($action);
	}

	protected function saveAction()
	{
		$id = (int)($this->request['ACTION_ENTITY_ID'] ?? 0);
		$isRefreshDataAndSaveOperation = isset($this->request['REFRESH_ORDER_DATA_AND_SAVE']) && $this->request['REFRESH_ORDER_DATA_AND_SAVE'] === 'Y';

		$isNew = $id === 0;

		if (!$isNew && !Permissions\Order::checkUpdatePermission($id, $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_INSUFFICIENT_RIGHTS'));

			return;
		}

		if ($isNew && !Permissions\Order::checkCreatePermission($this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_INSUFFICIENT_RIGHTS'));

			return;
		}

		if ($isNew && OrderRestriction::isOrderLimitReached())
		{
			$this->addError('You have reached the order limit for your plan');

			return;
		}

		if (!empty($this->request['ORDER_PRODUCT_DATA']))
		{
			// json can be broken sometimes; see http://jabber.bx/view.php?id=133871
			$productData = Context::getCurrent()->getRequest()->getPostList()->getRaw('ORDER_PRODUCT_DATA');
			try
			{
				$productData = current(\Bitrix\Main\Web\Json::decode($productData));
			}
			catch (\Bitrix\Main\ArgumentException $e)
			{
				$productData = [];
			}
			if (!$isNew)
			{
				$productData['PRODUCT'] = $this->prepareRefreshBasket($id, $productData['PRODUCT'] ?? []);
				$productData['DELETED_PRODUCT_IDS'] = $_SESSION['ORDER_BASKET'][$id]['DELETED_ITEM_IDS'] ?? [];
			}

			$productData = array_merge(
				$productData,
				array_intersect_key(
					$this->request,
					array_flip(
						Order::getAllFields()
					)
				)
			);

			if ($isNew && !empty($this->request['SALES_CENTER_SESSION_ID']) && Loader::includeModule('salescenter'))
			{
				$salesCenterLandingId = SalesCenter\Integration\LandingManager::getInstance()->getConnectedSiteId();
				if ($salesCenterLandingId > 0)
				{
					$code = \Bitrix\Sale\TradingPlatform\Landing\Landing::getCodeBySiteId($salesCenterLandingId);
					$platform = \Bitrix\Sale\TradingPlatform\Landing\Landing::getInstanceByCode($code);
					$productData['TRADING_PLATFORM'] = $platform->getId();
				}

				$controller = new SalesCenter\Controller\Order();
				$clientInfo = $controller->getClientInfo([
					'sessionId' => $this->request['SALES_CENTER_SESSION_ID'],
				]);
				if (isset($clientInfo['DEAL_ID']) && $clientInfo['DEAL_ID'] > 0)
				{
					$productData['DEAL_ID'] = $clientInfo['DEAL_ID'];
				}
			}
		}

		if (!empty($productData))
		{
			$order = $this->buildOrder(
				$productData,
				[
					'createUserIfNeed' => Builder\SettingsContainer::SET_ANONYMOUS_USER,
					'acceptableErrorCodes' => [],
					'cacheProductProviderData' => false,
				]
			);
		}
		elseif ($id > 0)
		{
			$order = \Bitrix\Crm\Order\Order::load($id);
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_ORDER_ID_NEGATIVE'));

			return;
		}

		if (!$order || !$this->result->isSuccess())
		{
			return;
		}

		$discount = $order->getDiscount();

		if ($isRefreshDataAndSaveOperation)
		{
			DiscountCouponsManager::clearApply(true);
			DiscountCouponsManager::useSavedCouponsForApply(true);
			$discount->setOrderRefresh(true);
			$discount->setApplyResult(array());

			/** @var \Bitrix\Sale\Basket $basket */
			if (!($basket = $order->getBasket()))
			{
				$this->addError(new Error(Loc::getMessage('CRM_ORDER_DA_CART_NOT_FOUND')));
				return;
			}

			$res = $basket->refresh(Basket\RefreshFactory::create(Basket\RefreshFactory::TYPE_FULL));

			if (!$res->isSuccess())
			{
				$this->addErrors($res->getErrors());
			}
		}

		$res = $discount->calculate();

		if (!$res->isSuccess())
		{
			$this->addErrors($res->getErrors());
		}

		if ($isRefreshDataAndSaveOperation && !$order->isCanceled() && !$order->isPaid())
		{
			/** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
			if (($paymentCollection = $order->getPaymentCollection()) && count($paymentCollection) == 1)
			{
				/** @var \Bitrix\Sale\Payment $payment */
				if (($payment = $paymentCollection->rewind()) && !$payment->isPaid())
				{
					$payment->setFieldNoDemand('SUM', $order->getPrice());
				}
			}
		}

		if (isset($this->request['CLIENT']) && $this->request['CLIENT'] !== '')
		{
			try
			{
				$clientData = \Bitrix\Main\Web\Json::decode($this->request['CLIENT']);
			}
			catch (\Bitrix\Main\SystemException $e)
			{
			}

			if (!isset($clientData) || !is_array($clientData))
			{
				$clientData = array();
			}

			$clientCollection = $order->getContactCompanyCollection();

			if (isset($clientData['COMPANY_DATA']) && is_array($clientData['COMPANY_DATA']))
			{
				$companyEntity = new \CCrmCompany(false);
				$enableCompanyCreation = \CCrmCompany::CheckCreatePermission($this->userPermissions);
				foreach ($clientData['COMPANY_DATA'] as $companyData)
				{
					$companyID = isset($companyData['id']) ? (int)$companyData['id'] : 0;
					$companyTitle = isset($companyData['title']) ? trim($companyData['title']) : '';
					if($companyID <= 0 && $companyTitle !== '' && $enableCompanyCreation)
					{
						$companyFields = array('TITLE' => $companyTitle);
						$multifieldData =  isset($companyData['multifields']) && is_array($companyData['multifields'])
							? $companyData['multifields']  : array();

						if(!empty($multifieldData))
						{
							$multifields = \Bitrix\Crm\Component\EntityDetails\BaseComponent::prepareMultifieldsForSave(
								CCrmOwnerType::Company,
								0,
								$multifieldData
							);

							if(!empty($multifields))
							{
								$companyFields['FM'] = $multifields;
							}
						}
						$companyID = $companyEntity->Add($companyFields, true, array('DISABLE_USER_FIELD_CHECK' => true));
						if($companyID > 0)
						{
							/** @var \Bitrix\Crm\Order\Company $company */
							$company = $clientCollection->createCompany();
							$company->setFields([
								'ENTITY_ID' => $companyID,
								'IS_PRIMARY' => 'Y'
							]);

							$arErrors = [];

							\CCrmBizProcHelper::AutoStartWorkflows(
								CCrmOwnerType::Company,
								$companyID,
								\CCrmBizProcEventType::Create,
								$arErrors
							);
						}
					}
					else
					{
						\Bitrix\Crm\Component\EntityDetails\BaseComponent::updateEntity(
							CCrmOwnerType::Company,
							$companyID,
							$companyData,
							[
								'startWorkFlows' => true
							],
						);
					}
				}
			}

			$contactIDs = null;
			$bindContactIDs = null;
			if (isset($clientData['CONTACT_DATA']) && is_array($clientData['CONTACT_DATA']))
			{
				$contactEntity = new \CCrmContact(false);
				$enableContactCreation = \CCrmContact::CheckCreatePermission($this->userPermissions);
				$contactData = $clientData['CONTACT_DATA'];
				foreach($contactData as $contactItem)
				{
					$contactID = isset($contactItem['id']) ? (int)$contactItem['id'] : 0;
					$contactTitle = isset($contactItem['title']) ? trim($contactItem['title']) : '';
					if($contactID <= 0 && $contactTitle !== '' && $enableContactCreation)
					{
						$contactFields = array();
						\Bitrix\Crm\Format\PersonNameFormatter::tryParseName(
							$contactTitle,
							\Bitrix\Crm\Format\PersonNameFormatter::getFormatID(),
							$contactFields
						);

						$multifieldData =  isset($contactItem['multifields']) && is_array($contactItem['multifields'])
							? $contactItem['multifields']
							: array();

						if(!empty($multifieldData))
						{
							$multifields = \Bitrix\Crm\Component\EntityDetails\BaseComponent::prepareMultifieldsForSave(
								CCrmOwnerType::Contact,
								0,
								$multifieldData
							);

							if(!empty($multifields))
							{
								$contactFields['FM'] = $multifields;
							}
						}

						$contactID = $contactEntity->Add($contactFields, true, array('DISABLE_USER_FIELD_CHECK' => true));
						if($contactID > 0)
						{
							$contact = $clientCollection->createContact();
							$contact->setFields([
								'ENTITY_ID' => $contactID,
								'IS_PRIMARY' => $clientCollection->isPrimaryItemExists(\CCrmOwnerType::Contact) ? 'N' : 'Y'
							]);

							$arErrors = [];

							\CCrmBizProcHelper::AutoStartWorkflows(
							    CCrmOwnerType::Contact,
								$contactID,
							    \CCrmBizProcEventType::Create,
							    $arErrors
							);
						}
					}
					else
					{
						\Bitrix\Crm\Component\EntityDetails\BaseComponent::updateEntity(
							CCrmOwnerType::Contact,
							$contactID,
							$contactItem,
							[
								'startWorkFlows' => true
							],
						);
					}
				}
			}
		}

		$requisites = [];
		$requisiteId = (int)($this->request['REQUISITE_ID'] ?? 0);
		if ($requisiteId > 0)
		{
			$requisites['REQUISITE_ID'] = $requisiteId;
		}

		$bankDetailId = (int)($this->request['BANK_DETAIL_ID'] ?? 0);
		if ($bankDetailId > 0)
		{
			$requisites['BANK_DETAIL_ID'] = $bankDetailId;
		}

		if (!empty($requisites))
		{
			$order->setRequisiteLink($requisites);
		}

		$res = $order->save();
		if ($isNew && $res->isSuccess())
		{
			$id = $order->getId();
		}

		if (!$res->isSuccess())
		{
			$this->addErrors($res->getErrors());

			return;
		}

		if ($res->hasWarnings())
		{
			$this->addWarnings($res->getWarnings());
		}

		if (isset($_SESSION['ORDER_BASKET'][$id]))
		{
			unset($_SESSION['ORDER_BASKET'][$id]);
		}

		if (
			$isNew
			&& ((int)$this->request['USER_PROFILE'] > 0 || $this->request['USER_PROFILE'] === 'NEW')
			&& (int)$order->getUserId() > 0
			&& $order->getUserId() !== \Bitrix\Crm\Order\Manager::getAnonymousUserID()
		)
		{
			$profileId = null;
			$error = '';
			$profileName = '';
			if ((int)$this->request['USER_PROFILE'] > 0)
			{
				$profileData = \Bitrix\Sale\OrderUserProperties::getList(
					array(
						'filter' => array(
							'ID' => (int)$this->request['USER_PROFILE'],
							'USER_ID' => $order->getUserId(),
							'PERSON_TYPE_ID' => $order->getPersonTypeId(),
						),
						'limit' => 1
					)
				);

				if ($profileData->fetch())
				{
					$profileId = (int)$this->request['USER_PROFILE'];
				}
			}

			$propertyCollection = $order->getPropertyCollection();
			$propertyProfileName = $propertyCollection->getProfileName();
			if ($propertyProfileName)
			{
				$profileName = $propertyProfileName->getValue();
			}

			$propertyValues = [];
			foreach ($propertyCollection as $property)
			{
				$propertyValues[$property->getPropertyId()] = $property->getValue();
			}

			$profileId = \CSaleOrderUserProps::DoSaveUserProfile(
				$order->getUserId(),
				$profileId,
				$profileName,
				$order->getPersonTypeId(),
				$propertyValues,
				$error
			);
		}

		$userFields = [];
		foreach ($this->request as $key => $value)
		{
			if(mb_strpos($key, 'UF_') === 0)
			{
				$userFields[$key] = $value;
			}
		}

		if (!empty($userFields))
		{
			$GLOBALS['USER_FIELD_MANAGER']->EditFormAddFields(\Bitrix\Crm\Order\Manager::getUfId(), $userFields, [
				'FORM' => $userFields,
				'FILES' => [],
			]);

			$GLOBALS['USER_FIELD_MANAGER']->Update(\Bitrix\Crm\Order\Manager::getUfId(), $id, $userFields);
		}

		\Bitrix\Crm\Tracking\UI\Details::saveEntityData(
			\CCrmOwnerType::Order,
			$order->getId(),
			$this->request,
			$isNew
		);

		\CBitrixComponent::includeComponentClass('bitrix:crm.order.details');
		$component = new \CCrmOrderDetailsComponent();
		$component->initComponent('bitrix:crm.order.details');
		$component->initializeParams(
			isset($this->request['PARAMS']) && is_array($this->request['PARAMS']) ? $this->request['PARAMS'] : []
		);
		$component->setEntityID($id);
		$order = $component->obtainOrder();
		$entityData = $component->prepareEntityData();


		if (
			(int)($this->request['USER_ID'] ?? 0) > 0
			|| (int)($this->request['PERSON_TYPE_ID'] ?? 0) > 0
		)
		{
			$entityData['USER_PROFILE_LIST'] = $component->loadProfiles($order->getUserId(), $order->getPersonTypeId());
		}

		if (!empty($profileId))
		{
			$entityData['USER_PROFILE'] = $profileId;
		}

		if ($isNew)
		{
			$fuser = (int)($this->request['PRODUCT_COMPONENT_DATA']['params']['FUSER_ID'] ?? 0);
			if ($fuser > 0)
			{
				$itemsDataList = \Bitrix\Sale\Internals\BasketTable::getList(
					array(
						"filter" => array(
							"=ORDER_ID" => NULL,
							"=FUSER_ID" => $fuser,
						),
						"select" => ["ID"]
					)
				);

				while ($item = $itemsDataList->fetch())
				{
					\Bitrix\Sale\Internals\BasketTable::deleteWithItems($item['ID']);
				}
			}
		}

		if (!empty($productData['PRODUCT']))
		{
			$entityData['PRODUCT_COMPONENT_RESULT'] = $this->getProductComponentData($order);
		}

		$this->addData(['ENTITY_ID' => $id, 'ENTITY_DATA' => $entityData]);
		if ($isNew)
		{
			if (!empty($this->request['SALES_CENTER_SESSION_ID']) && Loader::includeModule('salescenter'))
			{
				\Bitrix\SalesCenter\Integration\SaleManager::pushOrder($id, $this->request['SALES_CENTER_SESSION_ID']);
			}
			else
			{
				$this->addData(['REDIRECT_URL' =>\CCrmOwnerType::GetDetailsUrl(
					\CCrmOwnerType::Order,
					$id,
					false,
					['OPEN_IN_SLIDER' => true]
				)]);
			}
		}
	}

	protected function deleteAction()
	{
		$id = (int)($this->request['ACTION_ENTITY_ID'] ?? 0);
		if ($id <= 0)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_NOT_FOUND'));

			return;
		}

		if (!Permissions\Order::checkDeletePermission($id, $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));

			return;
		}

		$res = \Bitrix\Crm\Order\Order::delete($id);
		if ($res->isSuccess())
		{
			$this->addData(['ENTITY_ID' => $id]);
		}
		else
		{
			$this->addErrors($res->getErrors());
		}
	}

	protected function cancelAction()
	{
		$id = (int)($this->request['ACTION_ENTITY_ID'] ?? 0);
		if ($id <= 0)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_NOT_FOUND'));

			return;
		}

		if (!Permissions\Order::checkUpdatePermission($id, $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));

			return;
		}

		$order = \Bitrix\Crm\Order\Order::load($id);
		if ($order)
		{
			if ($this->request['VALUE'] === 'Y')
			{
				$comment = !empty($this->request['COMMENT']) ? $this->request['COMMENT'] : '';
				$res = $order->cancel($comment);
			}
			else
			{
				$res = $order->restore();
			}

			if ($res->isSuccess())
			{
				$this->addData(['ENTITY_ID' => $id]);
			}
			else
			{
				$this->addErrors($res->getErrors());
			}
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_NOT_FOUND'));
		}
	}

	protected function setPaymentPaidFieldAction()
	{
		$this->setPaymentField('PAID');
	}

	protected function setPaymentReturnFieldAction()
	{
		$this->setPaymentField('IS_RETURN');
	}

	protected function setPaymentField($fieldName)
	{
		$paymentId = isset($this->request['FIELDS']['PAYMENT_ID']) && (int)$this->request['FIELDS']['PAYMENT_ID'] > 0 ? (int)$this->request['FIELDS']['PAYMENT_ID'] : 0;

		if($paymentId <= 0)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_PAYMENT_NOT_FOUND'));
			return;
		}

		if (!in_array($fieldName, ['PAID', 'IS_RETURN']))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_WRONG_FIELD_VALUE'));
			return;
		}

		$value = isset($this->request['FIELDS'][$fieldName]) ? trim($this->request['FIELDS'][$fieldName]) : '';

		if($fieldName === 'PAID' && !in_array($value, ['Y', 'N']))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_WRONG_FIELD_VALUE'));
			return;
		}

		if(
			$fieldName === 'IS_RETURN'
			&& !in_array($value,[Payment::RETURN_NONE, Payment::RETURN_INNER, Payment::RETURN_PS]))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_WRONG_FIELD_VALUE'));
			return;
		}

		$voucherFields = [];

		if(isset($this->request['FIELDS']['PAY_VOUCHER_NUM']))
		{
			$voucherFields['PAY_VOUCHER_NUM'] = $this->request['FIELDS']['PAY_VOUCHER_NUM'];
		}

		if(isset($this->request['FIELDS']['PAY_VOUCHER_DATE']))
		{
			$voucherFields['PAY_VOUCHER_DATE'] = new Date($this->request['FIELDS']['PAY_VOUCHER_DATE']);
		}

		if(isset($this->request['FIELDS']['PAY_RETURN_NUM']))
		{
			$voucherFields['PAY_RETURN_NUM'] = $this->request['FIELDS']['PAY_RETURN_NUM'];
		}

		if(isset($this->request['FIELDS']['PAY_RETURN_DATE']))
		{
			$voucherFields['PAY_RETURN_DATE'] = new Date($this->request['FIELDS']['PAY_RETURN_DATE']);
		}

		if(isset($this->request['FIELDS']['PAY_RETURN_COMMENT']))
		{
			$voucherFields['PAY_RETURN_COMMENT'] = $this->request['FIELDS']['PAY_RETURN_COMMENT'];
		}

		$res = \Bitrix\Crm\Order\Payment::getList([
			'filter' => ['=ID' => $paymentId]
		]);

		$payment = $res->fetch();

		if(!$payment || (int)$payment['ORDER_ID'] <= 0)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_PAYMENT_NOT_FOUND'));
			return;
		}

		if(!\Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission($payment['ORDER_ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
			return;
		}

		$order = \Bitrix\Crm\Order\Order::load($payment['ORDER_ID']);

		if(!$order)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_NOT_FOUND'));
			return;
		}

		$collection = $order->getPaymentCollection();
		/** @var \Bitrix\Crm\Order\Payment $paymentObj */
		$paymentObj = $collection->getItemById($paymentId);

		if(!$paymentObj)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_PAYMENT_NOT_FOUND'));
			return;
		}

		if ($fieldName === 'PAID')
		{
			$setResult = $paymentObj->setPaid($value);
		}
		elseif ($fieldName === 'IS_RETURN')
		{
			$setResult = $paymentObj->setReturn($value);
		}

		if(!$setResult->isSuccess())
		{
			$this->addErrors($setResult->getErrors());
			return;
		}

		if(!empty($voucherFields))
		{
			$setResult = $paymentObj->setFields($voucherFields);

			if(!$setResult->isSuccess())
			{
				$this->addErrors($setResult->getErrors());
				return;
			}
		}

		$res = $order->save();

		if($res->isSuccess())
		{
			$this->addData([
				'ORDER_DATA' => $this->formatResultData($order),
				'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($order)
			]);
		}
		else
		{
			$this->addErrors($res->getErrors());
		}
	}

	protected function setShipmentFieldAction()
	{
		$shipmentId = isset($this->request['SHIPMENT_ID']) && intval($this->request['SHIPMENT_ID']) > 0 ? intval($this->request['SHIPMENT_ID']) : 0;
		$fieldName = isset($this->request['FIELD_NAME']) ? trim($this->request['FIELD_NAME']) : '';
		$fieldValue = isset($this->request['FIELD_VALUE']) ? trim($this->request['FIELD_VALUE']) : '';

		if($shipmentId <= 0)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_SHIPMENT_NOT_FOUND'));
			return;
		}

		if($fieldName == '' || ($fieldName != 'DEDUCTED' && $fieldName != 'ALLOW_DELIVERY'))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_WRONG_FIELD_NAME'));
			return;
		}

		if($fieldValue != 'Y' && $fieldValue != 'N')
		{
			$this->addError(Loc::getMessage('CRM_ORDER_WRONG_FIELD_VALUE'));
			return;
		}

		$res = \Bitrix\Crm\Order\Shipment::getList([
			'filter' => ['=ID' => $shipmentId]
		]);

		$shipmentFields = $res->fetch();

		if(!$shipmentFields || intval($shipmentFields['ORDER_ID']) <= 0)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_SHIPMENT_NOT_FOUND'));
			return;
		}

		if(!Permissions\Order::checkUpdatePermission($shipmentFields['ORDER_ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
			return;
		}

		$order = \Bitrix\Crm\Order\Order::load($shipmentFields['ORDER_ID']);

		if(!$order)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_NOT_FOUND'));
			return;
		}

		$collection = $order->getShipmentCollection();
		$shipment = $collection->getItemById($shipmentId);

		if(!$shipment)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_SHIPMENT_NOT_FOUND'));
			return;
		}

		$res = $shipment->setField($fieldName, $fieldValue);

		if(!$res->isSuccess())
		{
			$this->addErrors($res->getErrors());
			return;
		}
		else
		{
			$res = $order->save();

			if(!$res->isSuccess())
			{
				$this->addErrors($res->getErrors());
				return;
			}
		}

		$this->addData([
			'ORDER_DATA' => $this->formatResultData($order),
			'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($order)
		]);
	}

	protected function addPropertyAction()
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		$allowConfig = $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
		if (!$allowConfig)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
			return;
		}

		$createParams = [
			"ENTITY_REGISTRY_TYPE" => \Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER
		];

		$createParams['PERSON_TYPE_ID'] = isset($this->request['PERSON_TYPE_ID']) ? max((int)$this->request['PERSON_TYPE_ID'], 0) : 0;
		if ($createParams['PERSON_TYPE_ID'] <= 0)
		{
			$this->addError('PERSON TYPE ID NOT FOUND');
			return;
		}

		if (isset($this->request['PARAMS']['label']))
		{
			$createParams['NAME'] = $this->request['PARAMS']['label'];
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_WRONG_PROPERTY_NAME'));
			return;
		}

		if (isset($this->request['PARAMS']['typeId']))
		{
			switch ($this->request['PARAMS']['typeId'])
			{
				case 'string':
					$createParams['TYPE'] = 'STRING';
					break;
				case 'integer':
				case 'double':
					$createParams['TYPE'] = 'NUMBER';
					break;
				case 'boolean':
					$createParams['TYPE'] = 'Y/N';
					break;
				case 'datetime':
					$createParams['TYPE'] = 'DATE';
					break;
				case 'file':
					$createParams['TYPE'] = 'FILE';
					break;
				case 'enumeration':
					$createParams['TYPE'] = 'ENUM';
					break;
				case 'location':
					$createParams['TYPE'] = 'LOCATION';
					break;
			}
		}
		if (empty($createParams['TYPE']))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_WRONG_PROPERTY_TYPE'));
			return;
		}

		if ($this->request['PARAMS']['multiple'] === 'true')
		{
			$createParams['MULTIPLE'] = 'Y';
		}
		if ($this->request['PARAMS']['mandatory'] === 'true')
		{
			$createParams['REQUIRED'] = 'Y';
		}
		if ($this->request['PARAMS']['showAlways'] === 'true')
		{
			$createParams['SETTINGS'] = [
				"SHOW_ALWAYS" => 'Y'
			];
		}

		$createParams['UTIL'] = 'Y';

		$groupData = \Bitrix\Sale\Internals\OrderPropsGroupTable::getList(
			[
				'filter' => array('PERSON_TYPE_ID' => $createParams['PERSON_TYPE_ID']),
				'select' => array('ID'),
				'limit' => 1,
			]
		);

		$lastPropertyData = \Bitrix\Crm\Order\Property::getList(array(
			'select' => array('SORT'),
			'order' => array('SORT' => 'DESC'),
			'limit' => 1
		));

		if ($last = $lastPropertyData->fetch())
		{
			$createParams['SORT'] = $last['SORT'] + 10;
		}

		if ($group = $groupData->fetch())
		{
			$createParams['PROPS_GROUP_ID'] = $group['ID'];
		}

		$result = \Bitrix\Sale\Internals\OrderPropsTable::add($createParams);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return;
		}

		$name ='PROPERTY_'.$result->getId();

		$data = [
			'personTypeId' => $createParams['PERSON_TYPE_ID'],
			'propertyId' => $result->getId()
		];
		$resultData = [];

		if ($createParams['TYPE'] === 'ENUM' && is_array($this->request['PARAMS']['enumeration']))
		{
			$elements = [];
			$value = 1;
			foreach ($this->request['PARAMS']['enumeration'] as $option)
			{
				if ($option['VALUE'] <> '')
				{
					\Bitrix\Crm\Order\PropertyVariant::add([
						'ORDER_PROPS_ID' => $result->getId(),
						'NAME' => $option['VALUE'],
						'VALUE' => $value
					]);
					$elements[$value] = $option['VALUE'];
					$value++;
				}
			}
			if (!empty($elements))
			{
				$data['items'] = \CCrmInstantEditorHelper::PrepareListOptions(
					$elements,
					array('NOT_SELECTED' => Loc::getMessage('CRM_ORDER_NOT_SELECTED'))
				);
			}
		}
		elseif ($createParams['TYPE'] === 'LOCATION' || $createParams['TYPE'] === 'FILE')
		{
			$data += [
				'edit' => "{$name}_EDIT_HTML",
				'view' => "{$name}_VIEW_HTML"
			];

			$orderId = (int)$this->request['ENTITY_ID'] > 0 ? (int)$this->request['ENTITY_ID'] > 0 : 0;
			if ($orderId > 0)
			{
				\CBitrixComponent::includeComponentClass('bitrix:crm.order.details');
				$component = new \CCrmOrderDetailsComponent();
				$component->initializeParams(
					isset($this->request['PARAMS']) && is_array($this->request['PARAMS']) ? $this->request['PARAMS'] : []
				);
				$component->setEntityID($orderId);
				$component->obtainOrder();
				$resultData['ENTITY_ID'] = $orderId;
				$resultData['ENTITY_DATA'] = $component->prepareEntityData();
			}

		}

		switch ($_POST['PARAMS']['typeId'])
		{
			case 'string':
				$type = 'text';
				break;
			case 'integer':
			case 'double':
				$type = 'number';
				break;
			case 'enumeration':
				$type = 'list';
				break;
			case 'file':
			case 'location':
				$type = 'custom';
				break;
			default:
				$type = $_POST['PARAMS']['typeId'];
		}

		$resultData['FIELD'] = [
			'FIELD' => $name,
			'USER_TYPE_ID' => $type,
			'EDIT_FORM_LABEL' => $_POST['PARAMS']['label'],
			'MANDATORY' => ($createParams['REQUIRED'] === 'Y') ? 'Y' : 'N',
			"DATA" => $data
		];

		$this->addData($resultData);
	}

	protected function preparePropertySchemeAction()
	{
		if(isset($this->request['PROPERTY']) && is_array($this->request['PROPERTY']) && !empty($this->request['PROPERTY']))
		{
			$property = $this->request['PROPERTY'];
		}
		else
		{
			$this->addError('');
			return null;
		}

		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		$allowConfig = $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
		if (!$allowConfig)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
			return;
		}

		$property['ENABLE_MENU'] = $allowConfig;
		$property['IS_VISIBLE'] = true;
		\CBitrixComponent::includeComponentClass('bitrix:crm.order.details');
		$component = new \CCrmOrderDetailsComponent();
		$schemeField = $component->formatProperty($property);
		$this->addData(array('FIELD' => $schemeField));
	}
	protected function getPropertiesSchemeAction()
	{
		$orderId = (int)$this->request['ORDER_ID'];

		if (!Permissions\Order::checkReadPermission($this->userPermissions))
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage('CRM_ORDER_P_ACCESS_DENIED')));
			return;
		}

		\CBitrixComponent::includeComponentClass('bitrix:crm.order.details');
		$component = new \CCrmOrderDetailsComponent();
		$component->initializeParams(
			isset($this->request['PARAMS']) && is_array($this->request['PARAMS']) ? $this->request['PARAMS'] : []
		);
		$component->setEntityID($orderId);
		$order = $component->obtainOrder();
		if (!$order)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_NOT_FOUND'));
			return;
		}
		$properties = $component->prepareProperties(
			$order->getPropertyCollection(),
			\Bitrix\Crm\Order\Property::class,
			$order->getPersonTypeId(),
			$order->isNew()
		);

		$this->addData([
			'PROPERTIES' => $properties,
			'ORDER_ID' => $order->getId()
		]);
	}

	protected function fillPropsFromProfile(Order $order, int $userProfileId): void
	{
		$profileData = \Bitrix\Sale\OrderUserProperties::getList(
			array(
				'filter' => array(
					'ID' => $userProfileId,
					'USER_ID' => $order->getUserId(),
					'PERSON_TYPE_ID' => $order->getPersonTypeId(),
				),
				'limit' => 1
			)
		);

		if ($profile = $profileData->fetch())
		{
			$resultLoading =  \Bitrix\Sale\OrderUserProperties::loadProfiles($profile['USER_ID'], $profile['PERSON_TYPE_ID'], $profile['ID']);

			if ($resultLoading->isSuccess())
			{
				$profileData = $resultLoading->getData();
				$profileValues = $profileData[$profile['PERSON_TYPE_ID']][$profile['ID']]['VALUES'];
				/**
				 * @var  \Bitrix\Crm\Order\PropertyValue $property
				 */
				foreach ($order->getPropertyCollection() as $property)
				{
					if (isset($profileValues[$property->getPropertyId()]))
					{
						$property->setValue($profileValues[$property->getPropertyId()]);
					}
				}
			}
		}
	}

	protected function refreshOrderDataAction()
	{
		if (!($formData = $this->getFormData()))
		{
			return;
		}

		if ((int)($formData['ID'] ?? 0) <= 0)
		{
			if (!Permissions\Order::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));

				return;
			}
		}
		elseif (!Permissions\Order::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage("CRM_ORDER_ACCESS_DENIED"));

			return;
		}

		if (!($order = $this->buildOrder($formData)))
		{
			return;
		}

		if ($order->isNew())
		{
			$userProfile = (int)($formData['USER_PROFILE'] ?? 0);
			if (
				$userProfile > 0
				&& $userProfile !== (int)$formData['OLD_USER_PROFILE']
				&& !empty($order->getUserId())
				&& $order->getUserId() !== \Bitrix\Crm\Order\Manager::getAnonymousUserID()
			)
			{
				$this->fillPropsFromProfile($order, $userProfile);
			}

			if (
				!empty($formData['OLD_CURRENCY'])
				&& $formData['OLD_CURRENCY'] !== $formData['CURRENCY']
				&& !empty($formData["PRODUCT"])
			)
			{
				$this->addWarning(Loc::getMessage('CRM_ORDER_DA_CURRENCY_CHANGED'));
			}
		}

		$this->addData([
			'ORDER_DATA' => $this->formatResultData($order, $formData),
			'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($order)
		]);
	}

	protected function rollbackAction()
	{
		$orderId = (int)($this->request['FORM_DATA']['ID'] ?? 0);
		if ($orderId <= 0)
		{
			return;
		}

		if (!Permissions\Order::checkUpdatePermission($orderId, $this->userPermissions))
		{
			$this->addError(Loc::getMessage("CRM_ORDER_ACCESS_DENIED"));
			return;
		}

		$order = Order::load($orderId);
		if(!$order)
		{
			return;
		}
		$changedData = [];
		if (isset($this->request['CHANGED_DATA'])
			&& is_array($this->request['CHANGED_DATA'])
			&& !empty($this->request['CHANGED_DATA'])
		)
		{
			$changedData = $this->request['CHANGED_DATA'];
		}

		$this->addData([
			'ORDER_DATA' =>  $this->formatResultData($order, $changedData),
			'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($order)
		]);
	}

	protected function loadOrderAction()
	{
		$orderId = (int)($this->request['ORDER_ID'] ?? 0);
		if ($orderId <= 0)
		{
			return;
		}

		if (!Permissions\Order::checkReadPermission($orderId, $this->userPermissions))
		{
			$this->addError(Loc::getMessage("CRM_ORDER_ACCESS_DENIED"));

			return;
		}

		$order = Order::load($orderId);
		if (!$order)
		{
			return;
		}

		$this->addData([
			'ORDER_DATA' =>  $this->formatResultData($order),
			'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($order)
		]);
	}

	/**
	 * @param Order $order
	 * @param array $changedFields
	 *
	 * @return array|null
	 */
	protected function formatResultData(Order $order, array $changedFields = array())
	{
		$freshData = $this->createDataByComponent($order);

		$freshData['USER_PROFILE'] = $changedFields['USER_PROFILE'] ?? null;

		$oldDeliveryId = isset($changedFields['DELIVERY_ID']) ? (int)$changedFields['DELIVERY_ID'] : null;
		$newDeliveryId = isset($freshData['DELIVERY_ID']) ? (int)$freshData['DELIVERY_ID'] : null;
		$isDeliverySystemChanged = ($oldDeliveryId !== $newDeliveryId);

		$oldTradingPlatformId = isset($changedFields['OLD_TRADING_PLATFORM']) ? (int)$changedFields['OLD_TRADING_PLATFORM'] : null;
		$newTradingPlatformId = isset($freshData['TRADING_PLATFORM']) ? (int)$freshData['TRADING_PLATFORM'] : null;
		$isTradingPlatformChanged = ($oldTradingPlatformId !== $newTradingPlatformId);
		if (is_null($newTradingPlatformId))
		{
			$freshData['TRADING_PLATFORM'] = 0;
		}

		$oldPaymentSystemId = isset($changedFields['PAY_SYSTEM_ID']) ? (int)$changedFields['PAY_SYSTEM_ID'] : null;
		$newPaymentSystemId = isset($freshData['PAY_SYSTEM_ID']) ? (int)$freshData['PAY_SYSTEM_ID'] : null;
		$isPaymentSystemChanged = ($oldPaymentSystemId !== $newPaymentSystemId);

		if (
			(isset($changedFields['OLD_PERSON_TYPE_ID']) && (int)$changedFields['OLD_PERSON_TYPE_ID'] !== (int)$freshData['PERSON_TYPE_ID'])
			|| (isset($changedFields['OLD_USER_ID']) && (int)$changedFields['OLD_USER_ID'] !== (int)$freshData['USER_ID'])
			|| $isDeliverySystemChanged
			|| $isPaymentSystemChanged
			|| $isTradingPlatformChanged
		)
		{
			\CBitrixComponent::includeComponentClass('bitrix:crm.order.details');
			$component = new \CCrmOrderDetailsComponent();
			$component->initializeParams(
				isset($this->request['PARAMS']) && is_array($this->request['PARAMS']) ? $this->request['PARAMS'] : []
			);
			if ($order->isNew())
			{
				$freshData['USER_PROFILE_LIST'] = $component->loadProfiles($order->getUserId(), $order->getPersonTypeId());

				if (!empty($freshData['USER_PROFILE_LIST']))
				{
					if (empty($freshData['USER_PROFILE']))
					{
						$freshData['USER_PROFILE'] = $freshData['USER_PROFILE_LIST'][0]['VALUE'];
					}

					$this->fillPropsFromProfile($order, (int)$freshData['USER_PROFILE']);

					// It seems not optimal to call this method second time.
					$freshData = array_merge($freshData, $this->createDataByComponent($order));
				}
			}

			$freshData['PROPERTIES_SCHEME'] = $component->prepareProperties(
				$order->getPropertyCollection(),
				\Bitrix\Crm\Order\Property::class,
				$order->getPersonTypeId(),
				$order->isNew()
			);
		}

		if (
			(isset($changedFields['OLD_PERSON_TYPE_ID']) && (int)$changedFields['OLD_PERSON_TYPE_ID'] !== (int)$freshData['PERSON_TYPE_ID'])
			|| (isset($changedFields['OLD_USER_ID']) && (int)$changedFields['OLD_USER_ID'] !== (int)$freshData['USER_ID'])
			|| $isDeliverySystemChanged
			|| $isTradingPlatformChanged
		)
		{
			\CBitrixComponent::includeComponentClass('bitrix:crm.order.details');
			$component = new \CCrmOrderDetailsComponent();
			$component->initializeParams(
				isset($this->request['PARAMS']) && is_array($this->request['PARAMS']) ? $this->request['PARAMS'] : []
			);
			$shipment = $component->getFirstShipment($order);
			$freshData['SHIPMENT_PROPERTIES_SCHEME'] = $shipment
				? $component->prepareProperties(
					$shipment->getPropertyCollection(),
					\Bitrix\Crm\Order\ShipmentProperty::class,
					$shipment->getPersonTypeId(),
					($shipment->getId() === 0)
				)
				: [];
		}

		if (empty($freshData['USER_PROFILE']) && $order->isNew())
		{
			$freshData['USER_PROFILE'] = 'NEW';
		}

		$freshData['OLD_USER_PROFILE'] = $freshData['USER_PROFILE'];
		return $freshData;
	}
	/**
	 * @param null|int $orderId
	 * @param array $products
	 *
	 * @return array
	 */
	protected function prepareRefreshBasket($orderId, $products)
	{
		$orderId = (int)$orderId;
		if (
			$orderId <= 0
			|| empty($_SESSION['ORDER_BASKET'][$orderId])
			|| !is_array($_SESSION['ORDER_BASKET'][$orderId])
		)
		{
			return $products;
		}

		$result = [];

		foreach ($_SESSION['ORDER_BASKET'][$orderId]['ITEMS'] as $basketItemId => $fields)
		{
			if (!empty($products[$basketItemId]))
			{
				$result[$basketItemId] = $products[$basketItemId];
			}
			else
			{
				$result[$basketItemId]['FIELDS_VALUES'] = \Bitrix\Main\Web\Json::encode($fields);
			}
		}

		return $result;
	}

	protected function buildOrder(array &$formData, array $settings = [])
	{
		$formData['PROPERTIES'] = $this->getPropertiesField($formData);

		if (isset($formData['SHIPMENT']) && is_array($formData['SHIPMENT']))
		{
			foreach ($formData['SHIPMENT'] as $shipmentIndex => $shipment)
			{
				//@TODO add multi-shipment properties support
				$formData['SHIPMENT'][$shipmentIndex]['PROPERTIES'] = $this->getPropertiesField($formData);
			}
		}

		$settings = array_merge(
			[
				//Not during refreshing yes during saving
				'createUserIfNeed' => Builder\SettingsContainer::DISALLOW_NEW_USER_CREATION,
				'cacheProductProviderData' => true,
				'propsFiles' => $this->preparePropertyFiles(),
				//we have to skip this errors during refreshing, but not during saving the order
				'acceptableErrorCodes' => $this->getDefaultAcceptableErrorCodes()
			],
			$settings
		);

		$builderSettings = new Builder\SettingsContainer($settings);
		$orderBuilder = new \Bitrix\Crm\Order\OrderBuilderCrm($builderSettings);
		$director = new Builder\Director;
		/** @var Order $order */
		$order = $director->createOrder($orderBuilder, $formData);

		$errorContainer = $orderBuilder->getErrorsContainer();
		if(!empty($errorContainer->getErrors()))
		{
			$this->addErrors($errorContainer->getErrors());
		}

		if($errorContainer->hasWarnings())
		{
			$this->addWarnings($errorContainer->getWarnings());
		}

		return $order;
	}

	/**
	 * @return string[]
	 */
	protected function getDefaultAcceptableErrorCodes(): array
	{
		return [
			'CATALOG_QUANTITY_NOT_ENOGH',
			'SALE_ORDER_SYSTEM_SHIPMENT_LESS_QUANTITY',
			'CATALOG_NO_QUANTITY_PRODUCT',
			'SALE_SHIPMENT_SYSTEM_QUANTITY_ERROR',
			'SALE_BASKET_ITEM_WRONG_AVAILABLE_QUANTITY',
			'SALE_BASKET_ITEM_REMOVE_IMPOSSIBLE_BECAUSE_SHIPPED',
			'OB_DELIVERY_NOT_FOUND',
			'SALE_ORDEREDIT_ERROR_CHANGE_USER_WITH_PAID_PAYMENTS',
			'SALE_SHIPMENT_WRONG_DELIVERY_SERVICE',
		];
	}

	protected function skuSelectAction()
	{
		if(isset($this->request['SKU_PROPS']) && is_array($this->request['SKU_PROPS']) && !empty($this->request['SKU_PROPS']))
		{
			$skuProps = $this->request['SKU_PROPS'];
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_PROPERTIES_ABSENT'));
			return;
		}

		if(isset($this->request['SKU_ORDER']) && is_array($this->request['SKU_ORDER']) && !empty($this->request['SKU_ORDER']))
		{
			$skuOrder = $this->request['SKU_ORDER'];
		}
		else
		{
			$this->addError(Loc::getMessage("CRM_ORDER_DA_PROPERTIES_ORDER_ABSENT"));
			return;
		}

		if((int)($this->request['PRODUCT_ID']) > 0)
		{
			$productId = (int)$this->request['PRODUCT_ID'];
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_PRODUCT_ID_NOT_DEFINED'));
			return;
		}

		if((int)($this->request['CHANGED_SKU_ID']) > 0)
		{
			$changedSkuId = (int)$this->request['CHANGED_SKU_ID'];
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_PRODUCT_ID_NOT_DEFINED'));
			return;
		}

		if($this->request['BASKET_CODE'] <> '')
		{
			$basketCode = $this->request['BASKET_CODE'];
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_BASKET_CODE_ABSENT'));
			return;
		}

		if(!($formData = $this->getFormData()))
		{
			return;
		}
		if ((int)$formData['ID'] <= 0)
		{
			if (!Permissions\Order::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
				return;
			}
		}
		elseif (!Permissions\Order::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage("CRM_ORDER_ACCESS_DENIED"));
			return;
		}

		if(!$order = $this->buildOrder($formData))
		{
			return;
		}

		$newProductId = \Bitrix\Sale\Helpers\Admin\SkuProps::getProductId($skuProps, $productId, $skuOrder, $changedSkuId);
		$basket = $order->getBasket();
		$basketItem = $basket->getItemByBasketCode($basketCode);
		if ($basketItem)
		{
			$basketItem->setField('CUSTOM_PRICE', 'N');
			$basketItem->setFieldNoDemand('PRODUCT_ID', $newProductId);
			$this->updateBasketItemProperties($basketItem, (int)$newProductId);
			$basket->refresh(Basket\RefreshFactory::createSingle($basketItem->getBasketCode()));
		}

		$this->addData([
			'ORDER_DATA' => $this->formatResultData($order, $formData),
			'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($order)
		]);
	}

	/**
	 * Removes old basket item's properties and creates new properties
	 * from the new product's properties
	 * @param \Bitrix\Sale\BasketItemBase $basketItem
	 * @param int $newProductId
	 */
	private function updateBasketItemProperties(\Bitrix\Sale\BasketItemBase $basketItem, int $newProductId): void
	{
		$oldProductId = (int)$basketItem->getFields()->getOriginalValues()['PRODUCT_ID'];

		$repositoryFacade = Catalog\v2\IoC\ServiceContainer::getRepositoryFacade();
		if (!$repositoryFacade)
		{
			return;
		}

		$oldVariation = $repositoryFacade->loadVariation($oldProductId);
		$newVariation = $repositoryFacade->loadVariation($newProductId);
		if (!$oldVariation || !$newVariation)
		{
			return;
		}

		// get properties with "Add value to shopping cart" and, if needed, "Can select SKU's" features
		// for both the old and the new product
		$oldVariationPropertyCodes = $this->getVariationPropertyCodes($oldVariation);
		$newVariationPropertyCodes = $this->getVariationPropertyCodes($newVariation);

		$mergedProperties = $oldVariationPropertyCodes + $newVariationPropertyCodes;

		$basketItemProperties = $basketItem->getPropertyCollection();
		if (!$basketItemProperties)
		{
			return;
		}

		// clear all the properties that remain from the old product and that the new product might have
		foreach ($basketItemProperties->toArray() as $basketItemPropertyIndex => $basketItemProperty)
		{
			$currentPropertyCode = $basketItemProperty['CODE'];
			if (
				isset($mergedProperties[(int)$currentPropertyCode])
				|| in_array($currentPropertyCode, $mergedProperties, true)
			)
			{
				$basketItemProperties->deleteItem($basketItemPropertyIndex);
			}
		}

		// load all the properties we've gathered earlier for the new product and add them to the collection
		$skuInfo = \CCatalogSku::GetInfoByOfferIBlock($newVariation->getIblockId());
		if (empty($skuInfo))
		{
			return;
		}

		$parentIblockId = $skuInfo['PRODUCT_IBLOCK_ID'];
		$properties = \CIBlockPriceTools::GetOfferProperties(
			$newProductId,
			$parentIblockId,
			array_keys($newVariationPropertyCodes)
		);

		$productXmlId = $newVariation->getField('XML_ID');
		if (mb_strpos($productXmlId, '#') === false)
		{
			$parent = $newVariation->getParent();
			if ($parent)
			{
				$productXmlId = $parent->getId() . '#' . $productXmlId;
			}
		}
		foreach ($basketItemProperties as $basketItemProperty)
		{
			if ($basketItemProperty->getField('CODE') === 'PRODUCT.XML_ID')
			{
				$basketItemProperty->setField('VALUE', $productXmlId);
				break;
			}
		}

		foreach ($properties as $propertyValues)
		{
			$basketItemProperty = $basketItemProperties->createItem();
			$basketItemProperty->initFields($propertyValues);
		}
	}

	private function getVariationPropertyCodes(Catalog\v2\Sku\BaseSku $variation)
	{
		$result = [];
		$iblockId = $variation->getIblockId();
		if (!$iblockId)
		{
			return $result;
		}

		$basketPropertyIds = Catalog\Product\PropertyCatalogFeature::getBasketPropertyCodes($iblockId);
		$basketPropertyCodes = Catalog\Product\PropertyCatalogFeature::getBasketPropertyCodes($iblockId, ['CODE' => 'Y']);
		$result = array_combine($basketPropertyIds, $basketPropertyCodes);
		if ($variation->getType() !== Catalog\ProductTable::TYPE_OFFER)
		{
			return $result;
		}

		$offerTreeIds = Catalog\Product\PropertyCatalogFeature::getOfferTreePropertyCodes($iblockId);
		$offerTreeCodes = Catalog\Product\PropertyCatalogFeature::getOfferTreePropertyCodes($iblockId, ['CODE' => 'Y']);
		$offerTreeProperties = array_combine($offerTreeIds, $offerTreeCodes);
		$result += $offerTreeProperties;

		return $result;
	}

	protected function getFormData()
	{
		$result = [];

		if(isset($this->request['FORM_DATA']) && is_array($this->request['FORM_DATA']) && !empty($this->request['FORM_DATA']))
		{
			$result = $this->prepareFormData($this->request['FORM_DATA']);
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_SD_FORM_DATA_MISSING'));
		}

		return $result;
	}

	protected function createProductAction()
	{
		if(!($formData = $this->getFormData()))
		{
			return;
		}

		if ((int)$formData['ID'] <= 0)
		{
			if (!Permissions\Order::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
				return;
			}
		}
		elseif (!Permissions\Order::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage("CRM_ORDER_ACCESS_DENIED"));
			return;
		}

		if(!$order = $this->buildOrder($formData))
		{
			return;
		}
		$productProperties = $this->request['PRODUCT_DATA']['PROPS'];
		$fields = array_intersect_key($this->request['PRODUCT_DATA'], array_flip(\Bitrix\Crm\Order\BasketItem::getAvailableFields()));
		$basket = $order->getBasket();
		if ((int)($fields['PRODUCT_ID']) > 0)
		{
			$productId = (int)($fields['PRODUCT_ID']);
		}
		else
		{
			$productId = 0;
			foreach ($basket as $basketItem)
			{
				$maxProductId = $basketItem->getProductId();
				$productId = max($maxProductId, $productId);
			}
			$productId++;
			$fields['PRODUCT_ID'] = $productId;
		}
		$basketItem = $basket->createItem("", $productId);
		$fields['QUANTITY'] = (float)$fields['QUANTITY'];
		$fields['WEIGHT'] = (float)$fields['WEIGHT'];
		$fields['BASE_PRICE'] = $fields['PRICE'];
		$res = $basketItem->setFields($fields);
		if (!empty($productProperties) && is_array($productProperties))
		{
			$propertyCollection = $basketItem->getPropertyCollection();
			foreach ($productProperties as $propertyData)
			{
				if (empty($propertyData['VALUE']) && empty($propertyData['NAME']))
				{
					continue;
				}

				$propertyItem = $propertyCollection->createItem();
				$propertyItem->setFields([
					'NAME' => $propertyData['NAME'],
					'VALUE' => $propertyData['VALUE'],
					'CODE' => $propertyData['CODE'],
					'SORT' => $propertyData['SORT']
				]);
			}
		}
		if($res->isSuccess())
		{
			$this->addData([
				'ORDER_DATA' => $this->formatResultData($order, $formData),
				'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($order)
			]);
		}
		else
		{
			$this->result->addErrors($res->getErrors());
		}
	}

	protected function updateProductAction()
	{
		if (!($formData = $this->getFormData()))
		{
			return;
		}

		if ((int)($formData['ID'] ?? 0) <= 0)
		{
			if (!Permissions\Order::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));

				return;
			}
		}
		elseif (!Permissions\Order::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage("CRM_ORDER_ACCESS_DENIED"));

			return;
		}

		if (!$order = $this->buildOrder($formData))
		{
			return;
		}

		if (isset($this->request['BASKET_ID']))
		{

			$basket = $order->getBasket();
			$basketItem = $basket->getItemByBasketCode($this->request['BASKET_ID']);
			if (!$basketItem)
			{
				return ;
			}

			$fields = array_intersect_key($this->request['PRODUCT_DATA'], array_flip(\Bitrix\Crm\Order\BasketItem::getAvailableFields()));
			if (isset($this->request['PRODUCT_DATA']['PRODUCT_ID']))
			{
				$fields['PRODUCT_ID'] = $this->request['PRODUCT_DATA']['PRODUCT_ID'] ?? null;
			}

			$fields['QUANTITY'] = (float)($fields['QUANTITY'] ?? 0.0);
			$fields['WEIGHT'] = (float)($fields['WEIGHT'] ?? 0.0);

			if ($order->isNew())
			{
				$fields['BASE_PRICE'] = $fields['PRICE'];
			}

			$basketItem->setFieldsNoDemand($fields);
			$propertyCollection = $basketItem->getPropertyCollection();
			$propertyCollection->clearCollection();
			$productProperties = $this->request['PRODUCT_DATA']['PROPS'];
			if (!empty($productProperties) && is_array($productProperties))
			{
				$propertyCollection = $basketItem->getPropertyCollection();
				foreach ($productProperties as $propertyData)
				{
					if (empty($propertyData['VALUE']) && empty($propertyData['NAME']))
					{
						continue;
					}

					$propertyItem = $propertyCollection->createItem();
					$propertyItem->setFields([
						'NAME' => $propertyData['NAME'],
						'VALUE' => $propertyData['VALUE'],
						'CODE' => $propertyData['CODE'],
						'SORT' => $propertyData['SORT']
					]);
				}
			}
		}

		$this->addData([
			'ORDER_DATA' => $this->formatResultData($order, $formData),
			'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($order)
		]);
	}

	protected function addProductAction()
	{
		$productId = (int)($this->request['PRODUCT_ID'] ?? 0);
		if ($productId <= 0)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_PRODUCT_ID_NOT_DEFINED'));

			return;
		}

		$quantity = (float)($this->request['QUANTITY'] ?? 0.0);
		if ($quantity <= 0)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_QUANTITY_ABSENT'));

			return;
		}

		if (!($formData = $this->getFormData()))
		{
			return;
		}

		if ((int)($formData['ID'] ?? 0) <= 0)
		{
			if (!Permissions\Order::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));

				return;
			}
		}
		elseif (!Permissions\Order::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage("CRM_ORDER_ACCESS_DENIED"));

			return;
		}

		$settings = (isset($formData['PRODUCT']))
			? []
			: [
				'acceptableErrorCodes' => array_merge(
					$this->getDefaultAcceptableErrorCodes(),
					['DELIVERY_CALCULATION']
				)
			];

		$order = $this->buildOrder($formData, $settings);
		if (!$order)
		{
			return;
		}

		$basketItemFields = array(
			'PRODUCT_ID' => $productId,
			'QUANTITY' => $quantity,
			'CURRENCY' => $order->getCurrency(),
			'PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider',
			'SORT' => $this->getAddedProductSort($order, $productId)
		);

		$context = array(
			'SITE_ID' => $order->getSiteId(),
			'CURRENCY' => $order->getCurrency(),
		);

		$options = [
			'FILL_PRODUCT_PROPERTIES' => 'Y',
		];

		if (isset($this->request['USE_MERGE']))
		{
			$options['USE_MERGE'] = $this->request['USE_MERGE'] === 'Y' ? 'Y' : 'N';
		}

		if (!Loader::includeModule('catalog'))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_MODULE_CATALOG_ERROR'));
			return;
		}

		$basket = $order->getBasket();
		if (!$basket)
		{
			return;
		}

		$res = Catalog\Product\Basket::addProductToBasket(
			$basket,
			$basketItemFields,
			$context,
			$options
		);

		if ($res->isSuccess())
		{
			$basketItemCode = $res->getData()['BASKET_ITEM']->getBasketCode();
			$refreshResult = $basket->refresh(Basket\RefreshFactory::createSingle($basketItemCode));
			if (!$refreshResult->isSuccess())
			{
				$this->result->addErrors($refreshResult->getErrors());
			}

			$this->addData([
				'ORDER_DATA' => $this->formatResultData($order, $formData),
				'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($order)
			]);
		}
		else
		{
			$this->result->addErrors($res->getErrors());
		}
	}

	/**
	 * @param Order $order
	 * @param int $productId
	 * @return int
	 */
	protected function getAddedProductSort($order, $productId)
	{
		if(!$order)
		{
			return 100;
		}

		$maxSort = 0;

		if($basket = $order->getBasket())
		{
			/** @var \Bitrix\Sale\BasketItem $basketItem */
			foreach($basket->getBasketItems() as $basketItem)
			{
				if ($basketItem->getProductId() == $productId && $basketItem->getField('MODULE') == 'catalog')
				{
					return (int)$basketItem->getField('SORT');
				}

				if($maxSort < (int)$basketItem->getField('SORT'))
				{
					$maxSort = (int)$basketItem->getField('SORT');
				}
			}
		}

		return $maxSort + 100;
	}

	protected function getProductComponentData(Order $order)
	{
		global $APPLICATION;
		$componentParams = $this->request['PRODUCT_COMPONENT_DATA'] ?? [];
		$sessionBasket = [];
		$basket = $order->getBasket();
		/** @var \Bitrix\Sale\BasketItem $basketItem */
		foreach ($basket as $basketItem)
		{
			$item = $basketItem->getFieldValues();
			$item['BASKET_CODE'] = $basketItem->getBasketCode();
			$item['VAT'] = $basketItem->getVat();
			$propertyCollection = $basketItem->getPropertyCollection();
			foreach ($propertyCollection as $property)
			{
				$propertyValues = $property->getFieldValues();
				unset($propertyValues['BASKET_ID']);
				$item['PROPS'][] = $propertyValues;
			}

			$sessionBasket[$basketItem->getBasketCode()] = $item;
		}
		$_SESSION['ORDER_BASKET'][$order->getId()]['ITEMS'] = $sessionBasket;
		$ajaxParams = $componentParams;
		$ajaxParams['params']['SESSION_BASKET'] = 'Y';
		$ajaxParams['signedParameters'] = \CCrmInstantEditorHelper::signComponentParams(
			(array)$ajaxParams['params'],
			'crm.order.product.list'
		);
		unset($ajaxParams['params']);

		$template = $componentParams['template'] ?? '';
		$params = $componentParams['params'] ?? [];

		ob_start();
		$APPLICATION->IncludeComponent('bitrix:crm.order.product.list',
			$template,
			array_merge(
				$params,
				[
					'AJAX_LOADER' => array(
						'url' => '/bitrix/components/bitrix/crm.order.product.list/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
						'method' => 'POST',
						'dataType' => 'ajax',
						'data' => array('PARAMS' => $ajaxParams)
					),
					'ORDER' => $order,
					'AJAX_MODE' => 'N'
				]
			),
			false,
			array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
		);

		return ob_get_clean();
	}

	protected function deleteProductAction()
	{
		if($this->request['BASKET_CODE'] <> '')
		{
			$basketCode = $this->request['BASKET_CODE'];
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_BASKET_CODE_ABSENT'));
			return;
		}

		if(!($formData = $this->getFormData()))
		{
			return;
		}

		if ((int)$formData['ID'] <= 0)
		{
			if (!Permissions\Order::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
				return;
			}
		}
		elseif (!Permissions\Order::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage("CRM_ORDER_ACCESS_DENIED"));
			return;
		}

		if(!($order = $this->buildOrder($formData)))
		{
			return;
		}

		if(!($basket = $order->getBasket()))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_CART_NOT_FOUND'));
			return;
		}

		if(!($basketItem = $basket->getItemByBasketCode($basketCode)))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_BASKET_ID_BY_CODE_ERROR'));
			return;
		}

		$res = $basketItem->delete();

		if($res->isSuccess())
		{
			if (isset($_SESSION['ORDER_BASKET'][$order->getId()]))
			{
				unset($_SESSION['ORDER_BASKET'][$order->getId()]['ITEMS'][$basketCode]);
			}
			if ((int)$basketCode > 0)
			{
				$_SESSION['ORDER_BASKET'][$order->getId()]['DELETED_ITEM_IDS'][] = (int)$basketCode;
			}
			$this->addData([
				'ORDER_DATA' => $this->formatResultData($order, $formData),
				'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($order)
			]);
		}
		else
		{
			$this->result->addErrors($res->getErrors());
		}
	}

	protected function productGroupAction()
	{
		if(is_array($this->request['BASKET_CODES']))
		{
			$basketCodes = $this->request['BASKET_CODES'];
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_BASKET_CODE_ABSENT'));
			return;
		}

		if($this->request['GROUP_ACTION'] <> '')
		{
			$groupAction = $this->request['GROUP_ACTION'];
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_GROUP_ACTION_ABSENT'));
			return;
		}

		$forAll = isset($this->request['FOR_ALL']) && $this->request['FOR_ALL'] == 'Y';

		if(!($formData = $this->getFormData()))
		{
			return;
		}

		if ((int)$formData['ID'] <= 0)
		{
			if (!Permissions\Order::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
				return;
			}
		}
		elseif (!Permissions\Order::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage("CRM_ORDER_ACCESS_DENIED"));
			return;
		}

		if(!($order = $this->buildOrder($formData)))
		{
			return;
		}

		if(!($basket = $order->getBasket()))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_CART_NOT_FOUND'));
			return;
		}

		if($forAll)
		{
			$basketCodes = [];
			foreach($basket->getBasketItems() as $item)
			{
				$basketCodes[] = $item->getBasketCode();
			}
		}

		if(empty($basketCodes))
		{
			return;
		}

		if($groupAction == 'delete')
		{
			foreach($basketCodes as $basketCode)
			{
				if(!($basketItem = $basket->getItemByBasketCode($basketCode)))
				{
					$this->addError(Loc::getMessage('CRM_ORDER_DA_BASKET_ID_BY_CODE_ERROR'));
					continue;
				}

				$res = $basketItem->delete();

				if($res->isSuccess())
				{
					if (isset($_SESSION['ORDER_BASKET'][$order->getId()]))
					{
						unset($_SESSION['ORDER_BASKET'][$order->getId()]['ITEMS'][$basketCode]);
					}
					if ((int)$basketCode > 0)
					{
						$_SESSION['ORDER_BASKET'][$order->getId()]['DELETED_ITEM_IDS'][] = (int)$basketCode;
					}

					$this->addData([
						'ORDER_DATA' => $this->formatResultData($order, $formData),
						'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($order)
					]);
				}
				else
				{
					$this->result->addErrors($res->getErrors());
				}
			}
		}
	}


	protected function addCouponAction()
	{
		if(!empty($this->request['COUPON']))
		{
			$coupons = explode(",", $this->request["COUPON"]);
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_COUPONS_ABSENT'));
			return;
		}

		if(!($formData = $this->getFormData()))
		{
			return;
		}

		if ((int)$formData['ID'] <= 0)
		{
			if (!Permissions\Order::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
				return;
			}
		}
		elseif (!Permissions\Order::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage("CRM_ORDER_ACCESS_DENIED"));
			return;
		}

		if(!($order = $this->buildOrder($formData)))
		{
			return;
		}

		OrderEdit::initCouponsData($order->getUserId(), $order->getId());

		if(is_array($coupons) && count($coupons) > 0)
		{
			foreach($coupons as $coupon)
			{
				if($coupon <> '')
				{
					DiscountCouponsManager::add($coupon);
				}
			}

			$this->addData([
				'ORDER_DATA' => $this->formatResultData($order, $formData),
				'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($order)
			]);
		}
		else
		{
			$this->result->addError(new Error(Loc::getMessage('CRM_ORDER_DA_ADD_COUPON_ERROR')));
		}
	}

	protected function changeDeliveryAction()
	{
		if(isset($this->request['INDEX']))
		{
			$index = (int)$this->request['INDEX'];
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_ADD_COUPON_ERROR'));
			return;
		}

		if(!($formData = $this->getFormData()))
		{
			return;
		}

		if ((int)$formData['ID'] <= 0)
		{
			if (!Permissions\Order::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
				return;
			}
		}
		elseif (!Permissions\Order::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage("CRM_ORDER_ACCESS_DENIED"));
			return;
		}

		$deliveryId = intval($formData['SHIPMENT'][$index]['DELIVERY_ID']);

		if ($deliveryId > 0)
		{
			$service = Delivery\Services\Manager::getObjectById($deliveryId);

			if ($service && $service->canHasProfiles())
			{
				$profiles = Delivery\Services\Manager::getByParentId($deliveryId);
				reset($profiles);
				$initProfile = current($profiles);
				$formData['SHIPMENT'][$index]['DELIVERY_ID'] = $initProfile['ID'];
			}
		}

		if(!($order = $this->buildOrder($formData)))
		{
			return;
		}

		$this->addData([
			'ORDER_DATA' => $this->formatResultData($order, $formData),
			'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($order)
		]);
	}

	protected function loadUserInfoAction()
	{
		if((int)($this->request['USER_ID']) > 0)
		{
			$id = (int)$this->request['USER_ID'];
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_WRONG_FIELD_VALUE'));
			return;
		}

		if ((int)$this->request['ENTITY_ID'] <= 0)
		{
			if (!Permissions\Order::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
				return;
			}
		}
		elseif (!Permissions\Order::checkUpdatePermission((int)$this->request['ENTITY_ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
			return;
		}

		$user = \Bitrix\Main\UserTable::getById($id)->fetch();
		if (!$user)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_WRONG_FIELD_VALUE'));
			return;
		}

		$result = [];
		if (\Bitrix\Main\Loader::includeModule('socialservices'))
		{
			$nameTemplate = \CSite::GetNameFormat(false);
			$user = \Bitrix\Socialservices\Network::formatUserParam($user);
			if (empty($user['PERSONAL_PHOTO']) && !empty($user['PERSONAL_PHOTO_ORIGINAL']))
			{
				$user['PERSONAL_PHOTO'] = $user['PERSONAL_PHOTO_ORIGINAL'];
			}
			$result = \CSocNetLogDestination::formatNetworkUser($user, array(
				"NAME_TEMPLATE" => $nameTemplate,
			));
		}

		$this->addData([
			'USER_DATA' => $result
		]);
	}

	/**
	 * Get info about client secondary entities. Action is 'GET_SECONDARY_ENTITY_INFOS'.
	 */
	protected function getSecondaryEntityInfosAction()
	{
		if (!Permissions\Order::checkCreatePermission($this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
			return;
		}

		$params = isset($this->request['PARAMS']) && is_array($this->request['PARAMS']) ? $this->request['PARAMS'] : [];

		$ownerTypeName = isset($params['OWNER_TYPE_NAME']) ? $params['OWNER_TYPE_NAME'] : '';
		if($ownerTypeName === '')
		{
			$this->addError('Owner type is not specified.');
			return;
		}

		$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
		if($ownerTypeID !== CCrmOwnerType::Order)
		{
			$description = CCrmOwnerType::GetDescription($ownerTypeID);
			$this->addError("Type '{$description}' is not supported in current context.");
			return;
		}

		$primaryTypeName = isset($params['PRIMARY_TYPE_NAME']) ? $params['PRIMARY_TYPE_NAME'] : '';
		if($primaryTypeName === '')
		{
			$this->addError("Primary type is not specified.");
			return;
		}

		$primaryTypeID = CCrmOwnerType::ResolveID($primaryTypeName);
		if($primaryTypeID !== CCrmOwnerType::Company)
		{
			$this->addError("Primary type is not supported in current context.");
			return;
		}

		$primaryID = isset($params['PRIMARY_ID']) ? (int)$params['PRIMARY_ID'] : 0;
		if($primaryID <= 0)
		{
			$this->addError("Primary ID is not specified.");
			return;
		}

		$secondaryTypeName = isset($params['SECONDARY_TYPE_NAME']) ? $params['SECONDARY_TYPE_NAME'] : '';
		if($secondaryTypeName === '')
		{
			$this->addError("Secondary type is not specified.");
			return;
		}

		$secondaryTypeID = CCrmOwnerType::ResolveID($secondaryTypeName);
		if($secondaryTypeID !== CCrmOwnerType::Contact)
		{
			$this->addError("Secondary type is not supported in current context.");
			return;
		}

		$orderIds = [];
		$userDataRaw = \Bitrix\Crm\Binding\OrderContactCompanyTable::getList([
			'select' => ['ORDER_ID'],
			'filter' => [
				'ENTITY_ID' => $primaryID,
				'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
				'IS_PRIMARY' => 'Y'
			],
			'order' => ['ORDER_ID' => 'DESC'],
			'limit' => 5
		]);

		while ($user = $userDataRaw->fetch())
		{
			$orderIds[] = $user['ORDER_ID'];
		}

		$secondaryIDs = [];
		if (!empty($orderIds))
		{
			$contactRaw = \Bitrix\Crm\Binding\OrderContactCompanyTable::getList([
				'select' => ['ENTITY_ID', 'ORDER_ID'],
				'filter' => [
					'ORDER_ID' => $orderIds,
					'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
					'IS_PRIMARY' => 'N'
				],
				'order' => ['ORDER_ID' => 'DESC']
			]);
			$lastOrderWithSecond = null;
			while ($contact = $contactRaw->fetch())
			{
				if (!empty($secondaryIDs) && $lastOrderWithSecond !== $contact['ORDER_ID'])
				{
					break;
				}

				$secondaryIDs[] = $contact['ENTITY_ID'];
				$lastOrderWithSecond = $contact['ORDER_ID'];
			}
		}

		if(empty($secondaryIDs))
		{
			$secondaryIDs = \Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs($primaryID);
		}

		$secondaryInfos = array();
		foreach($secondaryIDs as $entityID)
		{
			if(!CCrmContact::CheckReadPermission($entityID, $this->userPermissions))
			{
				continue;
			}

			$secondaryInfos[]  = CCrmEntitySelectorHelper::PrepareEntityInfo(
				CCrmOwnerType::ContactName,
				$entityID,
				array(
					'ENTITY_EDITOR_FORMAT' => true,
					'REQUIRE_REQUISITE_DATA' => true,
					'REQUIRE_MULTIFIELDS' => true,
					'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
				)
			);
		}

		$this->addData([
			'ENTITY_INFOS' => $secondaryInfos
		]);
	}

	protected function createDataByComponent(Order $order)
	{
		\CBitrixComponent::includeComponentClass('bitrix:crm.order.details');
		$component = new \CCrmOrderDetailsComponent();
		$component->initComponent('bitrix:crm.order.details');
		$component->initializeParams(
			isset($this->request['PARAMS']) && is_array($this->request['PARAMS']) ? $this->request['PARAMS'] : []
		);
		$component->setEntityID($order->getId());
		$component->setOrder($order);
		$component->formatResultSettings();
		return $component->prepareEntityData();
	}

	protected function deleteCouponAction()
	{
		if(!empty($this->request['COUPON']))
		{
			$coupon = $this->request['COUPON'];
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_DA_COUPONS_ABSENT'));
			return;
		}

		if(!($formData = $this->getFormData()))
		{
			return;
		}

		if ((int)$formData['ID'] <= 0)
		{
			if (!Permissions\Order::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
				return;
			}
		}
		elseif (!Permissions\Order::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage("CRM_ORDER_ACCESS_DENIED"));
			return;
		}

		if(!($order = $this->buildOrder($formData)))
		{
			return;
		}

		OrderEdit::initCouponsData($order->getUserId(), $order->getId());

		if(DiscountCouponsManager::delete($coupon))
		{
			$this->addData([
				'ORDER_DATA' => $this->formatResultData($order, $formData),
				'PRODUCT_COMPONENT_RESULT' => $this->getProductComponentData($order)
			]);
		}
		else
		{
			$this->result->addError(new Error(Loc::getMessage('CRM_ORDER_DA_DELETE_COUPON_ERROR')));
		}
	}

	/**
	 * @param array $formData
	 *
	 * @return mixed
	 */
	protected function prepareFormData(array $formData = array())
	{
		if (!empty($formData['PRODUCT']))
		{
			$formData['PRODUCT'] = $this->prepareRefreshBasket($formData['ID'], $formData['PRODUCT']);
			$formData['DELETED_PRODUCT_IDS'] = $_SESSION['ORDER_BASKET'][$formData['ID']]['DELETED_ITEM_IDS'];
		}

		return $formData;
	}
}

$APPLICATION->RestartBuffer();
$processor = new AjaxProcessor($_REQUEST);
$result = $processor->checkConditions();

if($result->isSuccess())
{
	$result = $processor->processRequest();
}

$processor->sendResponse($result);

if(!defined('PUBLIC_AJAX_MODE'))
{
	define('PUBLIC_AJAX_MODE', true);
}

\CMain::FinalActions();

die();
