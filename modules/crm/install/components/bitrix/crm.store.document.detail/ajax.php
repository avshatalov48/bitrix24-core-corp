<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('BX_PUBLIC_MODE', true);
define('DisableEventsCheck', true);

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Order\Permissions;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\Helpers\Order\Builder;
use Bitrix\Catalog;
use Bitrix\Crm\Service\Sale\Reservation\ShipmentService;
use Bitrix\Salescenter;

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

Loc::loadMessages(__FILE__);

if (!Loader::includeModule('crm'))
{
	die('Can\'t include module "CRM"');
}

if (!Main\Loader::includeModule('salescenter'))
{
	die('Can\'t include module "salescenter"');
}

/** @internal  */
final class AjaxProcessor extends Crm\Order\AjaxProcessor
{
	private const PATH_TO_SHIPMENT_DETAIL = '/shop/documents/details/sales_order/#DOCUMENT_ID#/';

	use Crm\Component\EntityDetails\SaleProps\AjaxProcessorTrait;

	protected function changeDeliveryAction(): void
	{
		$formData = $this->getFormData();
		if (!$formData)
		{
			return;
		}

		if ((int)$formData['ID'] <= 0)
		{
			if (!Permissions\Shipment::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_INSUFFICIENT_RIGHTS'));
				return;
			}
		}
		elseif (!Permissions\Shipment::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_INSUFFICIENT_RIGHTS'));
			return;
		}

		$deliveryId = (int)($formData['DELIVERY_ID']);

		if ($deliveryId <= 0)
		{
			return;
		}

		$service = Delivery\Services\Manager::getObjectById($deliveryId);
		if ($service && $service->canHasProfiles())
		{
			$profiles = Delivery\Services\Manager::getByParentId($deliveryId);
			reset($profiles);
			$initProfile = current($profiles);
			$formData['DELIVERY_ID'] = $initProfile['ID'];
		}

		$needEnableAutomation = false;
		if (Sale\Configuration::isEnableAutomaticReservation())
		{
			Sale\Configuration::disableAutomaticReservation();
			$needEnableAutomation = true;
		}

		$order = $this->buildOrder($formData);
		if (!$order)
		{
			return;
		}

		$shipmentId = (int)$formData['ID'];
		if ($shipmentId)
		{
			$shipment = $order->getShipmentCollection()->getItemById($shipmentId);
		}
		else
		{
			/** @var Crm\Order\Shipment $shipmentItem */
			foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipmentItem)
			{
				if ($shipmentItem->getId() > 0)
				{
					continue;
				}

				$shipment = $shipmentItem;
				break;
			}
		}

		if ($needEnableAutomation)
		{
			Sale\Configuration::enableAutomaticReservation();
		}

		if (!isset($shipment) || !$shipment)
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_SHIPMENT_NOT_FOUND'));
			return;
		}

		$this->addData([
			'SHIPMENT_DATA' => $this->createDataByComponent($shipment),
		]);
	}

	protected function refreshShipmentDataAction(): void
	{
		$formData = $this->getFormData();
		if (!$formData)
		{
			return;
		}

		if ((int)$formData['ID'] <= 0)
		{
			if (!Permissions\Shipment::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_INSUFFICIENT_RIGHTS'));
				return;
			}
		}
		elseif (!Permissions\Shipment::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_INSUFFICIENT_RIGHTS'));
			return;
		}

		$needEnableAutomation = false;
		if (Sale\Configuration::isEnableAutomaticReservation())
		{
			Sale\Configuration::disableAutomaticReservation();
			$needEnableAutomation = true;
		}

		$order = $this->buildOrder($formData);
		if (!$order)
		{
			return;
		}

		$shipmentId = (int)$formData['ID'];
		if ($shipmentId)
		{
			$shipment = $order->getShipmentCollection()->getItemById($shipmentId);
		}
		else
		{
			/** @var Crm\Order\Shipment $shipmentItem */
			foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipmentItem)
			{
				if ($shipmentItem->getId() > 0)
				{
					continue;
				}

				$shipment = $shipmentItem;
				break;
			}
		}

		if ($needEnableAutomation)
		{
			Sale\Configuration::enableAutomaticReservation();
		}

		if (!isset($shipment) || !$shipment)
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_SHIPMENT_NOT_FOUND'));
			return;
		}

		$this->addData([
			'SHIPMENT_DATA' => $this->createDataByComponent($shipment),
		]);
	}

	protected function saveAction(): void
	{
		$this->saveOrder();
	}

	protected function saveAndDeductAction(): void
	{
		$result = $this->saveOrder();
		if (!is_null($result))
		{
			[$orderId, $shipmentId] = $result;

			$shipment = Crm\Order\Manager::getShipmentObject($shipmentId);
			if (!$shipment)
			{
				$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_SHIPMENT_NOT_FOUND'));
				return;
			}

			$order = $shipment->getOrder();
			if (!$order)
			{
				return;
			}

			$needEnableAutomation = false;
			try
			{
				if (Sale\Configuration::isEnableAutomaticReservation())
				{
					Sale\Configuration::disableAutomaticReservation();
					$needEnableAutomation = true;
				}

				$setFieldResult = $shipment->setField('DEDUCTED', 'Y');
				if ($setFieldResult->isSuccess())
				{
					$saveOrderResult = $order->save();
					if (!$saveOrderResult->isSuccess())
					{
						$this->addErrors($saveOrderResult->getErrors());
						return;
					}
				}
				else
				{
					$this->addErrors($setFieldResult->getErrors());
					return;
				}
			}
			finally
			{
				if ($needEnableAutomation)
				{
					Sale\Configuration::enableAutomaticReservation();
				}
			}
		}
	}

	private function saveOrder(array $additionalFields = [])
	{
		$orderId = (int)$this->request['ORDER_ID'] > 0 ? (int)$this->request['ORDER_ID'] : 0;
		$shipmentId = (int)$this->request['ID'] > 0 ? (int)$this->request['ID'] : 0;
		$isRefreshDataAndSaveOperation = isset($this->request['REFRESH_ORDER_DATA_AND_SAVE']) && $this->request['REFRESH_ORDER_DATA_AND_SAVE'] === 'Y';

		$isShipmentNew = $shipmentId === 0;
		$isNew = $orderId === 0;

		if (!$isNew && !Permissions\Order::checkUpdatePermission($orderId, $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_INSUFFICIENT_RIGHTS'));
			return;
		}

		if ($isNew && !Permissions\Order::checkCreatePermission($this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_INSUFFICIENT_RIGHTS'));
			return;
		}

		if ($isNew && Crm\Restriction\OrderRestriction::isOrderLimitReached())
		{
			$this->addError('You have reached the order limit for your plan');
			return;
		}

		$contextParams = [];

		if (!empty($this->request['ORDER_SHIPMENT_PRODUCT_DATA']))
		{
			$productData = Main\Context::getCurrent()->getRequest()->getPostList()->getRaw('ORDER_SHIPMENT_PRODUCT_DATA');
			if (!defined('BX_UTF'))
			{
				$productData = Main\Text\Encoding::convertEncoding(
					$productData, 'UTF-8', SITE_CHARSET
				);
			}

			$productData = current(\CUtil::JsObjectToPhp($productData));
			$contextParams = $productData['PARAMS'] ?? [];

			$productData = array_merge(
				$productData,
				$additionalFields,
				array_intersect_key(
					$this->request,
					array_flip(
						Crm\Order\Order::getAllFields()
					)
				)
			);
		}

		if ($isNew)
		{
			$platformCode = Crm\Order\TradingPlatform\RealizationDocument::TRADING_PLATFORM_CODE;
			$platform = Crm\Order\TradingPlatform\RealizationDocument::getInstanceByCode($platformCode);
			if ($platform->isInstalled())
			{
				$productData['TRADING_PLATFORM'] = $platform->getId();
			}
		}

		$needEnableAutomation = false;
		if (Sale\Configuration::isEnableAutomaticReservation())
		{
			Sale\Configuration::disableAutomaticReservation();
			$needEnableAutomation = true;
		}

		$shipment = null;
		if (!empty($productData))
		{
			$order = $this->buildOrder($productData);
			if ($order)
			{
				$shipment = $this->findNewShipment($order);
			}
		}
		elseif ($orderId > 0)
		{
			$order = Crm\Order\Order::load($orderId);
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_ORDER_ID_NEGATIVE'));
			return;
		}

		if (!$order || !$this->result->isSuccess())
		{
			return;
		}

		$discount = $order->getDiscount();

		if ($isRefreshDataAndSaveOperation)
		{
			\Bitrix\Sale\DiscountCouponsManager::clearApply(true);
			\Bitrix\Sale\DiscountCouponsManager::useSavedCouponsForApply(true);
			$discount->setOrderRefresh(true);
			$discount->setApplyResult(array());

			/** @var \Bitrix\Sale\Basket $basket */
			if (!($basket = $order->getBasket()))
			{
				$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_CART_NOT_FOUND'));
				return;
			}

			$saveOrderResult = $basket->refresh(
				\Bitrix\Sale\Basket\RefreshFactory::create(
					\Bitrix\Sale\Basket\RefreshFactory::TYPE_FULL
				)
			);

			if (!$saveOrderResult->isSuccess())
			{
				$this->addErrors($saveOrderResult->getErrors());
			}
		}

		$saveOrderResult = $discount->calculate();
		if (!$saveOrderResult->isSuccess())
		{
			$this->addErrors($saveOrderResult->getErrors());
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

		if ($this->request['CLIENT'] && $this->request['CLIENT'] !== '')
		{
			try
			{
				$clientData = Main\Web\Json::decode(
					Main\Text\Encoding::convertEncoding($this->request['CLIENT'], LANG_CHARSET, 'UTF-8')
				);
			}
			catch (Main\SystemException $e)
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
					if ($companyID <= 0 && $companyTitle !== '' && $enableCompanyCreation)
					{
						$companyFields = array('TITLE' => $companyTitle);
						$multiFieldData =
							isset($companyData['multifields']) && is_array($companyData['multifields'])
								? $companyData['multifields']
								: array()
						;

						if (!empty($multiFieldData))
						{
							$multiFields = Crm\Component\EntityDetails\BaseComponent::prepareMultifieldsForSave(
								CCrmOwnerType::Company,
								0,
								$multiFieldData
							);

							if (!empty($multiFields))
							{
								$companyFields['FM'] = $multiFields;
							}
						}
						$companyID = $companyEntity->Add($companyFields, true, array('DISABLE_USER_FIELD_CHECK' => true));
						if ($companyID > 0 && $clientCollection)
						{
							/** @var Crm\Order\Company $company */
							$company = $clientCollection->createCompany();
							$company->setFields([
								'ENTITY_ID' => $companyID,
								'IS_PRIMARY' => 'Y',
							]);
						}
					}
				}
			}

			if (isset($clientData['CONTACT_DATA']) && is_array($clientData['CONTACT_DATA']))
			{
				$contactEntity = new \CCrmContact(false);
				$enableContactCreation = \CCrmContact::CheckCreatePermission($this->userPermissions);
				$contactData = $clientData['CONTACT_DATA'];
				foreach($contactData as $contactItem)
				{
					$contactID = isset($contactItem['id']) ? (int)$contactItem['id'] : 0;
					$contactTitle = isset($contactItem['title']) ? trim($contactItem['title']) : '';
					if ($contactID <= 0 && $contactTitle !== '' && $enableContactCreation)
					{
						$contactFields = array();
						Crm\Format\PersonNameFormatter::tryParseName(
							$contactTitle,
							Crm\Format\PersonNameFormatter::getFormatID(),
							$contactFields
						);

						$multiFieldData =
							isset($contactItem['multifields']) && is_array($contactItem['multifields'])
								? $contactItem['multifields']
								: array()
						;

						if (!empty($multiFieldData))
						{
							$multiFields = Crm\Component\EntityDetails\BaseComponent::prepareMultifieldsForSave(
								CCrmOwnerType::Contact,
								0,
								$multiFieldData
							);

							if (!empty($multiFields))
							{
								$contactFields['FM'] = $multiFields;
							}
						}

						$contactID = $contactEntity->Add($contactFields, true, array('DISABLE_USER_FIELD_CHECK' => true));
						if ($contactID > 0 && $clientCollection)
						{
							$contact = $clientCollection->createContact();
							$contact->setFields([
								'ENTITY_ID' => $contactID,
								'IS_PRIMARY' => $clientCollection->isPrimaryItemExists(\CCrmOwnerType::Contact) ? 'N' : 'Y',
							]);
						}
					}
				}
			}
		}

		$saveOrderResult = $order->save();
		if ($isNew && $saveOrderResult->isSuccess())
		{
			$orderId = $order->getId();
		}

		if ($needEnableAutomation)
		{
			Sale\Configuration::enableAutomaticReservation();
		}

		if (!$saveOrderResult->isSuccess())
		{
			$this->addErrors($saveOrderResult->getErrors());
			return;
		}

		if ($saveOrderResult->hasWarnings())
		{
			$this->addWarnings($saveOrderResult->getWarnings());
		}

		$userFields = [];
		foreach ($this->request as $key => $value)
		{
			if (mb_strpos($key, 'UF_') === 0)
			{
				$userFields[$key] = $value;
			}
		}

		if (!empty($userFields))
		{
			$GLOBALS['USER_FIELD_MANAGER']->EditFormAddFields(Crm\Order\Manager::getUfId(), $userFields, [
				'FORM' => $userFields,
				'FILES' => [],
			]);

			$GLOBALS['USER_FIELD_MANAGER']->Update(Crm\Order\Manager::getUfId(), $orderId, $userFields);
		}

		\CBitrixComponent::includeComponentClass('bitrix:crm.store.document.detail');
		$component = new \CrmStoreDocumentDetailComponent();
		$component->initComponent('bitrix:crm.store.document.detail');
		$component->initializeParams(
			isset($this->request['PARAMS']) && is_array($this->request['PARAMS']) ? $this->request['PARAMS'] : []
		);

		if (!$shipment && $shipmentId)
		{
			$shipment = $order->getShipmentCollection()->getItemById($shipmentId);
		}

		if (!$shipment)
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_SHIPMENT_NOT_FOUND'));
			return;
		}

		$this->syncShipmentProducts($shipment);

		$component->setShipment($shipment);

		$entityData = $component->prepareEntityData();
		$this->addData([
			'ENTITY_ID' => $shipment->getId(),
			'ENTITY_DATA' => $entityData,
		]);

		$isSaveAndDeduct = $this->request['ACTION'] === 'saveAndDeduct';
		if ($isSaveAndDeduct)
		{
			$this->addData([
				'REDIRECT_URL' => $this->getUrlToDocumentDetail(
					$shipment->getId(),
					$contextParams['PATH_TO_SHIPMENT_DETAIL'] ?? '',
					true
				),
			]);
		}
		elseif ($isShipmentNew)
		{
			$this->addData([
				'REDIRECT_URL' => $this->getUrlToDocumentDetail(
					$shipment->getId(),
					$contextParams['PATH_TO_SHIPMENT_DETAIL'] ?? ''
				),
			]);
		}

		return [$order->getId(), $shipment->getId()];
	}

	protected function deductAction(): void
	{
		$shipment = $this->deductShipment('Y');
		if ($shipment)
		{
			$this->addData([
				'REDIRECT_URL' => $this->getUrlToDocumentDetail(
					$shipment->getId(),
					$this->request['PARAMS']['PATH_TO_SHIPMENT_DETAIL'] ?? '',
					true
				),
			]);
		}
	}

	protected function cancelDeductAction(): void
	{
		$shipment = $this->deductShipment('N');
		if ($shipment)
		{
			$this->addData([
				'REDIRECT_URL' => $this->getUrlToDocumentDetail(
					$shipment->getId(),
					$this->request['PARAMS']['PATH_TO_SHIPMENT_DETAIL'] ?? ''
				),
			]);
		}
	}

	private function deductShipment(string $value): ?Crm\Order\Shipment
	{
		$id = (int)$this->request['ID'];
		if (!$id)
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_SHIPMENT_NOT_FOUND'));
			return null;
		}

		if (!Permissions\Shipment::checkUpdatePermission($id, $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_INSUFFICIENT_RIGHTS'));
			return null;
		}

		$needEnableAutomation = false;
		if (Sale\Configuration::isEnableAutomaticReservation())
		{
			Sale\Configuration::disableAutomaticReservation();
			$needEnableAutomation = true;
		}

		$shipment = Crm\Order\Manager::getShipmentObject($id);
		if (!$shipment)
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_SHIPMENT_NOT_FOUND'));
			return null;
		}

		$order = $shipment->getOrder();
		if (!$order)
		{
			return null;
		}

		if ($value === 'Y')
		{
			$products = $this->request['PRODUCT'];
			$products = is_array($products) ? $products : [];

			$products = array_filter(
				$products,
				static function ($product) {
					return !empty($product['SKU_ID']);
				}
			);

			if (empty($products) || !is_array($products))
			{
				$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_PRODUCT_NOT_FOUND'));
				return null;
			}

			/** @var Crm\Order\ShipmentItem $shipmentItem */
			foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
			{
				$shipmentItemStoreCollection = $shipmentItem->getShipmentItemStoreCollection();
				if ($shipmentItemStoreCollection->isEmpty())
				{
					$basketItem = $shipmentItem->getBasketItem();

					$product = array_filter(
						$products,
						static function ($product) use ($basketItem)
						{
							$basketId = (int)$product['BASKET_ID'];
							return $basketId > 0 && $basketId === $basketItem->getId();
						}
					);

					if ($product)
					{
						$product = current($product);
						$shipmentItemStore = $shipmentItemStoreCollection->createItem($basketItem);
						$setFieldResult = $shipmentItemStore->setFields([
							'BASKET_ID' => $basketItem->getId(),
							'STORE_ID' => (int)$product['STORE_FROM'],
							'QUANTITY' => $shipmentItem->getQuantity(),
							'ORDER_DELIVERY_BASKET_ID' => $shipmentItem->getId(),
						]);

						if (!$setFieldResult->isSuccess())
						{
							$this->addErrors($setFieldResult->getErrors());
							return null;
						}
					}
				}
			}
		}

		$setFieldResult = $shipment->setField('DEDUCTED', $value);
		if ($setFieldResult->isSuccess())
		{
			$saveOrderResult = $order->save();

			if ($saveOrderResult->isSuccess())
			{
				if ($value === 'N')
				{
					ShipmentService::getInstance()->reserveCanceledShipment($shipment);
				}
			}

			if ($needEnableAutomation)
			{
				Sale\Configuration::enableAutomaticReservation();
			}

			if (!$saveOrderResult->isSuccess())
			{
				$this->addErrors($saveOrderResult->getErrors());
				return null;
			}
		}
		else
		{
			$this->addErrors($setFieldResult->getErrors());
			return null;
		}

		return $shipment;
	}

	/**
	 * @param Crm\Order\Order $order
	 * @return Crm\Order\Shipment|null
	 */
	private function findNewShipment(Crm\Order\Order $order): ?Crm\Order\Shipment
	{
		foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipment)
		{
			if ($shipment->getId() === 0)
			{
				return $shipment;
			}
		}

		return null;
	}

	private function getUrlToDocumentDetail($documentId, $pathToDocumentDetail, $addCloseOnSaveParam = false)
	{
		if (!$pathToDocumentDetail)
		{
			$pathToDocumentDetail = self::PATH_TO_SHIPMENT_DETAIL;
		}

		if ($addCloseOnSaveParam)
		{
			$pathToDocumentDetail .= '?closeOnSave=Y';
		}

		return str_replace('#DOCUMENT_ID#', $documentId, $pathToDocumentDetail);
	}

	private function createDataByComponent(Crm\Order\Shipment $shipment)
	{
		\CBitrixComponent::includeComponentClass('bitrix:crm.store.document.detail');
		$component = new \CrmStoreDocumentDetailComponent();

		$formDataContextParams = $this->request['FORM_DATA']['PARAMS'] ?? [];
		$formDataParams = $this->request['PARAMS'] ?? [];
		$componentParams = array_merge($formDataContextParams, $formDataParams);

		$component->initializeParams($componentParams);
		$component->setEntityID($shipment->getId());
		$component->setShipment($shipment);

		$entityData = $component->prepareEntityData();

		$entityData['SHIPMENT_PROPERTIES_SCHEME'] = $component->prepareProperties(
			$shipment->getPropertyCollection(),
			Crm\Order\ShipmentProperty::class,
			$shipment->getPersonTypeId(),
			($shipment->getId() === 0)
		);

		return $entityData;
	}

	protected function buildOrder(array $formData): ?Crm\Order\Order
	{
		$shipmentFields = $formData;
		$orderId = $formData['ORDER_ID'] ?? 0;
		$isDeducted = isset($formData['DEDUCTED']) && $formData['DEDUCTED'] === 'Y';

		$formData['ID'] = $orderId;
		unset($formData['ORDER_ID'], $formData['STATUS_ID']);

		if (empty($formData['ID']) && empty($formData['CURRENCY']))
		{
			$formData['CURRENCY'] = \CCrmCurrency::GetBaseCurrencyID();
		}

		if (
			!empty($formData['CLIENT'])
			&& !is_array($formData['CLIENT'])
		)
		{
			$formData['CLIENT'] = $this->getClientIds();
		}

		if (
			!empty($formData['PARAMS']['OWNER_TYPE_ID'])
			&& !empty($formData['PARAMS']['OWNER_ID'])
		)
		{
			$formData['OWNER_TYPE_ID'] = $formData['PARAMS']['OWNER_TYPE_ID'];
			$formData['OWNER_ID'] = $formData['PARAMS']['OWNER_ID'];
		}

		$formDataProducts = $formData['PRODUCT'] ?? [];
		$formData['PRODUCT'] = $this->prepareProducts($formDataProducts);
		if ($isDeducted && empty($formData['PRODUCT']))
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_PRODUCT_NOT_FOUND'));
			return null;
		}

		$formData['SHIPMENT'][] = array_intersect_key(
			$shipmentFields,
			array_flip(
				Crm\Order\Shipment::getAllFields()
			)
		);

		foreach ($formData['SHIPMENT'] as $shipmentIndex => $shipment)
		{
			$formData['SHIPMENT'][$shipmentIndex]['PROPERTIES'] = $this->getPropertiesField($formData);
			$formData['SHIPMENT'][$shipmentIndex]['EXTRA_SERVICES'] = $formData['EXTRA_SERVICES'] ?? [];

			if ($formDataProducts)
			{
				$shipmentProducts = $this->prepareShipmentProducts($formDataProducts);
				$formData['SHIPMENT'][$shipmentIndex]['PRODUCT'] = $shipmentProducts;

				$checkProductQuantityResult = $this->checkProductsQuantity($formData['PRODUCT'], $shipmentProducts);
				if (!$checkProductQuantityResult->isSuccess())
				{
					$this->addErrors($checkProductQuantityResult->getErrors());
					return null;
				}
			}
		}

		$formData['PROPERTIES'] = $this->getPropertiesField($formData);

		$orderBuilder = Salescenter\Builder\Manager::getBuilder(
			Salescenter\Builder\SettingsContainer::BUILDER_SCENARIO_SHIPMENT
		);

		$director = new Builder\Director;
		/** @var Crm\Order\Order $order */
		$order = $director->createOrder($orderBuilder, $formData);

		$errorContainer = $orderBuilder->getErrorsContainer();
		if ($errorContainer && !empty($errorContainer->getErrors()))
		{
			$this->addErrors($errorContainer->getErrors());
		}

		if ($errorContainer && $errorContainer->hasWarnings())
		{
			$this->addWarnings($errorContainer->getWarnings());
		}

		return $order;
	}

	protected function getClientIds(): array
	{
		$result = [];

		if ($this->request['CLIENT'] && $this->request['CLIENT'] !== '')
		{
			try
			{
				$clientData = Main\Web\Json::decode(
					Main\Text\Encoding::convertEncoding($this->request['CLIENT'], LANG_CHARSET, 'UTF-8')
				);
			}
			catch (Main\SystemException $e)
			{
			}

			if (!isset($clientData) || !is_array($clientData))
			{
				$clientData = array();
			}

			if (isset($clientData['COMPANY_DATA']) && is_array($clientData['COMPANY_DATA']))
			{
				foreach ($clientData['COMPANY_DATA'] as $companyData)
				{
					$result['COMPANY_ID'] = isset($companyData['id']) ? (int)$companyData['id'] : 0;
				}
			}

			if (isset($clientData['CONTACT_DATA']) && is_array($clientData['CONTACT_DATA']))
			{
				$contactData = $clientData['CONTACT_DATA'];
				foreach($contactData as $contactItem)
				{
					$result['COMPANY_IDS'][] = isset($contactItem['id']) ? (int)$contactItem['id'] : 0;
				}
			}
		}

		return $result;
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

	protected function getFormData()
	{
		$result = [];

		if (isset($this->request['FORM_DATA']) && is_array($this->request['FORM_DATA']) && !empty($this->request['FORM_DATA']))
		{
			$result = $this->request['FORM_DATA'];
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_FORM_DATA_MISSING'));
		}

		return $result;
	}

	protected function changeProductAction(): void
	{
		$formData = $this->getFormData();
		if (!$formData)
		{
			return;
		}

		if ((int)$formData['ID'] <= 0)
		{
			if (!Permissions\Shipment::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_INSUFFICIENT_RIGHTS'));
				return;
			}
		}
		elseif (!Permissions\Shipment::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_INSUFFICIENT_RIGHTS'));
			return;
		}

		$needEnableAutomation = false;
		if (Sale\Configuration::isEnableAutomaticReservation())
		{
			Sale\Configuration::disableAutomaticReservation();
			$needEnableAutomation = true;
		}

		$order = $this->buildOrder($formData);
		if (!$order)
		{
			return;
		}

		$shipmentId = (int)$formData['ID'];
		if ($shipmentId)
		{
			$shipment = $order->getShipmentCollection()->getItemById($shipmentId);
		}
		else
		{
			/** @var Crm\Order\Shipment $shipmentItem */
			foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipmentItem)
			{
				if ($shipmentItem->getId() > 0)
				{
					continue;
				}

				$shipment = $shipmentItem;
				break;
			}
		}

		if ($needEnableAutomation)
		{
			Sale\Configuration::enableAutomaticReservation();
		}

		if (!isset($shipment) || !$shipment)
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_SHIPMENT_NOT_FOUND'));
			return;
		}

		$this->addData([
			'SHIPMENT_DATA' => $this->createDataByComponent($shipment),
		]);
	}

	protected function prepareProducts(array $products): array
	{
		$result = [];

		foreach ($products as $product)
		{
			$productId = $product['SKU_ID'] ?? null;
			if (!$productId)
			{
				continue;
			}

			$basketCode = $product['BASKET_ID'] ?? null;
			if (
				!$basketCode
				|| (mb_strpos($basketCode, 'n') === 0)
				|| array_key_exists($basketCode, $result)
			)
			{
				$basketCode = ('n' . (count($result) + 1));
			}

			$item = [
				'NAME' => $product['NAME'],
				'QUANTITY' => (float)$product['AMOUNT'] > 0 ? (float)$product['AMOUNT'] : 1,
				'PRODUCT_PROVIDER_CLASS' => '\\' . Catalog\Product\CatalogProvider::class,
				'MODULE' => 'catalog',
				'BASKET_CODE' => $basketCode,
				'PRODUCT_ID' => $productId,
				'OFFER_ID' => $productId,
				'BASE_PRICE' => $product['PURCHASING_PRICE'],
				'PRICE' => $product['BASE_PRICE'],
				'CUSTOM_PRICE' => 'Y',
				'DISCOUNT_PRICE' => 0,
				'MEASURE_NAME' => $product['MEASURE_NAME'],
				'MEASURE_CODE' => (int)$product['MEASURE_CODE'],
				'MANUALLY_EDITED' => 'Y',
			];

			$item['FIELDS_VALUES'] = Main\Web\Json::encode($item);

			$result[$basketCode] = $item;
		}

		return $result;
	}

	protected function prepareShipmentProducts(array $products): array
	{
		$result = [];

		foreach ($products as $product)
		{
			$productId = $product['SKU_ID'] ?? null;
			if (!$productId)
			{
				continue;
			}

			$basketCode = $product['BASKET_ID'] ?? null;
			if (
				!$basketCode
				|| (mb_strpos($basketCode, 'n') === 0)
				|| array_key_exists($basketCode, $result)
			)
			{
				$basketCode = ('n' . (count($result) + 1));
			}

			$quantity = (float)$product['AMOUNT'] > 0 ? (float)$product['AMOUNT'] : 1;
			$storeId = (int)$product['STORE_FROM'];

			$item = [
				'QUANTITY' => $quantity,
				'AMOUNT' => $quantity,
				'BASKET_ID' => $basketCode,
				'BASKET_CODE' => $basketCode,
				'XML_ID' => uniqid('bx_'),
				'BARCODE_INFO' => [
					$storeId => [
						'STORE_ID' => (int)$product['STORE_FROM'],
						'QUANTITY' => $quantity,
						'BARCODE' => [
							[
								'VALUE' => $product['BARCODE'],
							],
						],
					],
				],
			];

			$result[$basketCode] = $item;
		}

		return $result;
	}

	protected function checkProductsQuantity(array $basketProducts, array $shipmentProducts): Main\Result
	{
		$result = new Main\Result();

		/** @var Sale\Reservation\BasketReservationService $basketReservation */
		$basketReservation = Main\DI\ServiceLocator::getInstance()->get('sale.basketReservation');

		foreach ($basketProducts as $product)
		{
			$basketCode = $product['BASKET_CODE'];
			$productId = $product['PRODUCT_ID'];
			$storeId = key($shipmentProducts[$basketCode]['BARCODE_INFO']);
			$quantity = $product['QUANTITY'];
			$availableQuantity = $quantity;

			if ((int)$basketCode > 0)
			{
				$availableQuantity = $basketReservation->getAvailableCountForBasketItem(
					(int)$basketCode,
					$storeId
				);
			}
			else
			{
				$storeQuantityRow = Catalog\StoreProductTable::getRow([
					'select' => [
						'AMOUNT',
						'QUANTITY_RESERVED',
					],
					'filter' => [
						'=STORE_ID' => $storeId,
						'=PRODUCT_ID' => $productId,
					],
				]);
				if ($storeQuantityRow)
				{
					$availableQuantity = min(
						$quantity,
						$storeQuantityRow['AMOUNT'] - $storeQuantityRow['QUANTITY_RESERVED']
					);
				}
			}

			if ($quantity > $availableQuantity)
			{
				$result->addError(
					new Main\Error(
						Loc::getMessage(
							'CRM_STORE_DOCUMENT_SD_PRODUCT_QUANTITY_ERROR',
							[
								'#PRODUCT_NAME#' => $product['NAME'],
								'#PRODUCT_ID#' => $product['PRODUCT_ID'],
								'#STORE_NAME#' => \CCatalogStoreControlUtil::getStoreName($storeId),
								'#STORE_ID#' => $storeId,
							]
						)
					)
				);
			}
		}

		return $result;
	}

	protected function rollbackAction(): void
	{
		$formData = $this->getFormData();
		if (!$formData)
		{
			return;
		}

		$shipmentId = (int)$formData['ID'];
		if ($shipmentId <= 0)
		{
			return;
		}

		if (!Permissions\Shipment::checkUpdatePermission($shipmentId, $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_INSUFFICIENT_RIGHTS'));
			return;
		}

		$shipment = Crm\Order\Manager::getShipmentObject($shipmentId);
		if (!$shipment)
		{
			return;
		}

		$this->addData([
			'SHIPMENT_DATA' => $this->createDataByComponent($shipment),
		]);
	}

	protected function deleteAction(): void
	{
		$id = (int)$this->request['ACTION_ENTITY_ID'] > 0 ? (int)$this->request['ACTION_ENTITY_ID'] : 0;

		if ($id <= 0)
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_SHIPMENT_NOT_FOUND'));
			return;
		}

		if (!Permissions\Shipment::checkDeletePermission($id, $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_INSUFFICIENT_RIGHTS'));
			return;
		}
		$shipmentRaw = Crm\Order\Shipment::getList([
			'filter' => ['=ID' => $id],
			'select' => ['ORDER_ID'],
			'limit' => 1,
		]);
		$shipmentData = $shipmentRaw->fetch();
		$order = Crm\Order\Order::load($shipmentData['ORDER_ID']);
		if (!$order)
		{
			$this->addError(Loc::getMessage('CRM_STORE_DOCUMENT_SD_SHIPMENT_NOT_FOUND'));
			return;
		}

		$shipmentCollection = $order->getShipmentCollection();
		$shipment = $shipmentCollection->getItemById($id);
		$res = $shipment->delete();
		$order->save();
		if ($res->isSuccess())
		{
			$this->addData(['ENTITY_ID' => $id]);
		}
		else
		{
			$this->addErrors($res->getErrors());
		}
	}

	private function syncShipmentProducts(Crm\Order\Shipment $shipment): void
	{
		$order = $shipment->getOrder();
		$entityBinding = $order->getEntityBinding();
		if ($entityBinding)
		{
			$productManager = new Crm\Order\ProductManager(
				$entityBinding->getOwnerTypeId(),
				$entityBinding->getOwnerId()
			);
			$productManager->setOrder($order);

			$basketItems = $this->prepareBasketItemsForSync($shipment);
			$productManager->syncOrderProducts($basketItems);
		}
	}

	private function prepareBasketItemsForSync(Crm\Order\Shipment $shipment): array
	{
		$basketItems = [];

		if (Main\Loader::includeModule('catalog'))
		{
			$formBuilder = new Catalog\v2\Integration\JS\ProductForm\BasketBuilder();

			/** @var Crm\Order\ShipmentItem $shipmentItem */
			foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
			{
				$basketItem = $shipmentItem->getBasketItem();
				$product = $basketItem->getFieldValues();

				$item = $formBuilder->loadItemBySkuId($product['PRODUCT_ID']);
				if ($item)
				{
					$item
						->setDetailUrlManagerType(Crm\Product\Url\ProductBuilder::TYPE_ID)
						->addAdditionalField('originProductId', (string)$product['PRODUCT_ID'])
						->addAdditionalField('originBasketId', (string)$product['ID'])
						->setName($product['NAME'])
						->setPrice((float)$product['PRICE'])
						->setCode($product['ID'])
						->setBasePrice((float)$product['BASE_PRICE'])
						->setPriceExclusive((float)$product['PRICE'])
						->setQuantity((float)$product['QUANTITY'])
						->setMeasureCode((int)$product['MEASURE_CODE'])
						->setMeasureName($product['MEASURE_NAME'])
					;

					$basketItems[] = $item->getFields();
				}
			}
		}

		return $basketItems;
	}

	protected function prepareResponseError(\Bitrix\Sale\Result $result): string
	{
		$response = '';

		$trimErrors = static function (array $errors)
		{
			return array_map(
				static function ($error) {
					return trim($error, " \n\r\t\v\0.");
				},
				$errors
			);
		};

		if (!$result->isSuccess())
		{
			$response = implode('<br>', $trimErrors($result->getErrorMessages()));
		}

		if ($result->hasWarnings() && $this->showWarnings)
		{
			$warningString = implode('<br>', $trimErrors($result->getWarningMessages()));

			if (empty($response))
			{
				$response = $warningString;
			}
			else
			{
				$response .= '<br>' . $warningString;
			}
		}

		return $response;
	}
}

CUtil::JSPostUnescape();
$APPLICATION->RestartBuffer();
$processor = new AjaxProcessor($_REQUEST);
$result = $processor->checkConditions();

if ($result->isSuccess())
{
	$result = $processor->processRequest();
}

$processor->sendResponse($result);

if (!defined('PUBLIC_AJAX_MODE'))
{
	define('PUBLIC_AJAX_MODE', true);
}

\CMain::FinalActions();

die();
