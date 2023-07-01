<?php

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Sale\Cashbox;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem;
use Bitrix\Crm;
use Bitrix\Crm\Workflow\PaymentWorkflow;
use Bitrix\Crm\Workflow\PaymentStage;
use Bitrix\ImOpenLines\Model\SessionTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

/**
 * Class SalesCenterPaymentPay
 */
class SalesCenterPaymentPay extends \CBitrixComponent implements Main\Engine\Contract\Controllerable
{
	const CACHE_TTL = 31536000;

	const CACHE_BASE_ID = 'BITRIX_SALESCENTER_PAYMENT_PAY_COMPONENT';

	/**
	 * @var Main\ErrorCollection
	 */
	private $errorCollection;

	/**
	 * @var bool
	 */
	private $isViewMode = false;

	/**
	 * @var int
	 */
	private $paymentId = null;

	/**
	 * @var Sale\Payment
	 */
	private $payment = null;

	/**
	 * @var int
	 */
	private $orderId = null;

	/**
	 * @var Sale\Registry
	 */
	private $registry = null;

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [];
	}

	/**
	 * @return Main\ErrorCollection
	 */
	public function getErrorCollection()
	{
		return $this->errorCollection;
	}

	/**
	 * @return Payment
	 */
	public function getPayment()
	{
		return $this->payment;
	}

	/**
	 * Component entrypoint and initialization.
	 *
	 * @param mixed[] $params List of unchecked parameters
	 * @return mixed[] Checked and valid parameters
	 */
	public function onPrepareComponentParams($params)
	{
		$this->errorCollection = new Main\ErrorCollection();
		$this->checkModules();
		$this->setupRegistry();

		$request = $this->request;

		if (empty($params['ACTIVE_DATE_FORMAT']))
		{
			$params['ACTIVE_DATE_FORMAT'] = Main\Type\DateTime::getFormat();
		}

		if (empty($params['ALLOW_PAYMENT_REDIRECT']))
		{
			$params['ALLOW_PAYMENT_REDIRECT'] = 'Y';
		}

		if (empty($params['RETURN_URL']))
		{
			$params['RETURN_URL'] = (new PaySystem\Context())->getUrl();
		}

		if (empty($params['ACCESS_CODE']))
		{
			$params['ACCESS_CODE'] = $request->get('access');
		}

		if (isset($params['EXCLUDED_PAY_SYSTEMS']) || $request->get('excludedPS'))
		{
			$excludedPaySystems = $params['EXCLUDED_PAY_SYSTEMS'] ?? $request->get('excludedPS');

			$params['NEED_VALIDATE_EXCLUDED_PAY_SYSTEMS'] = is_array($excludedPaySystems);

			$params['EXCLUDED_PAY_SYSTEMS'] =
				$params['NEED_VALIDATE_EXCLUDED_PAY_SYSTEMS']
					? array_map('intval', $excludedPaySystems)
					: []
			;
		}
		else
		{
			$params['NEED_VALIDATE_EXCLUDED_PAY_SYSTEMS'] = false;
		}

		if ((int)($params['PAYMENT_ID']) > 0 || $params['PAYMENT_ACCOUNT_NUMBER'] != '')
		{
			$this->initPaymentDataByPaymentId($params);
		}
		elseif ((int)($params['ORDER_ID']) > 0)
		{
			$this->initPaymentDataByOrderId($params);
		}
		else
		{
			$this->isViewMode = true;
			$params['VIEW_MODE'] = 'Y';
		}

		$params['TEMPLATE_MODE'] ??= '';

		return $params;
	}

	/**
	 * Function implements all the life cycle of our component
	 * @return void
	 */
	public function executeComponent()
	{
		$templateName = null;

		if ($this->errorCollection->isEmpty())
		{
			if ($this->isViewMode)
			{
				$orderClassName = $this->registry->getOrderClassName();
				/** @var Sale\Order $order */
				$order = $orderClassName::create(SITE_ID);
				$paymentCollection = $order->getPaymentCollection();
				$this->payment = $paymentCollection->createItem();
				$this->arResult['PAYSYSTEMS_LIST'] = $this->formatPaySystemList($this->payment);
			}
			else
			{
				$order = $this->loadOrder();
				if ($order && $this->checkAuthorized($order))
				{
					if (!$order->isCanceled())
					{
						$paymentCollection = $order->getPaymentCollection();
						$this->payment = $paymentCollection->getItemById($this->paymentId);
						$this->arResult['PAYMENT'] = $this->payment->getFieldValues();
						$dateBillFormatted = $this->arResult['PAYMENT']['DATE_BILL'];
						if ($this->arResult['PAYMENT']['DATE_BILL'] instanceof Main\Type\Date)
						{
							$dateBillFormatted = \CIBlockFormatProperties::DateFormat(
								$this->arParams['ACTIVE_DATE_FORMAT'],
								$this->arResult['PAYMENT']['DATE_BILL']->getTimestamp()
							);
						}
						$this->arResult['PAYMENT']['DATE_BILL_FORMATTED'] = $dateBillFormatted;
						$this->arResult['PAYMENT']['FORMATTED_SUM'] = SaleFormatCurrency(
							$this->payment->getSum(),
							$this->payment->getField('CURRENCY')
						);
						$this->arResult['PAYSYSTEMS_LIST'] = $this->formatPaySystemList($this->payment);
						$defaultPaySystem = [];
						foreach ($this->arResult['PAYSYSTEMS_LIST'] as $paySystem)
						{
							if (!$defaultPaySystem || $paySystem['ACTION_FILE'] === 'cash')
							{
								$defaultPaySystem = $paySystem;
							}
							if ((int)$paySystem['ID'] === (int)$this->payment->getPaymentSystemId())
							{
								$this->arResult['PAYMENT']['PAY_SYSTEM_INFO'] = $paySystem;
								break;
							}
						}

						if (empty($this->arResult['PAYMENT']['PAY_SYSTEM_INFO']))
						{
							$this->arResult['PAYMENT']['PAY_SYSTEM_INFO'] = $defaultPaySystem;
						}

						$this->prepareConsentSettings($order);
						$this->prepareCheckFields($this->payment);

						if (Main\Loader::includeModule('crm'))
						{
							$needEmitOrderViewedEvent = false;
							$paymentWorkflow = PaymentWorkflow::createFrom($this->payment);

							if ($paymentWorkflow->setStage(PaymentStage::VIEWED_NO_PAID))
							{
								$needEmitOrderViewedEvent = true;
							}

							if ($this->needAddTimelineEntityOnOpen($this->payment))
							{
								$needEmitOrderViewedEvent = true;
								$this->addTimelineEntityOnView($this->payment);

								if (!$this->payment->isPaid())
								{
									/** @var Crm\Order\EntityBinding $binding */
									$binding = $order->getEntityBinding();
									if (
										$binding
										&& $binding->getOwnerTypeId() == CCrmOwnerType::Deal
									)
									{
										$this->changeOrderStageDealOnViewedNoPaid(
											$binding->getOwnerId()
										);
									}
								}
							}

							if ($needEmitOrderViewedEvent)
							{
								$this->emitOrderViewedEvent($this->payment);
							}
						}
					}
					else
					{
						$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_ORDER_CANCELED')));
					}
				}

				$this->arResult['RETURN_URL'] = $this->arParams['RETURN_URL'];
			}
		}

		$this->arResult['COMPONENT_THEME'] = $this->getComponentTheme();

		$this->formatResultErrors();
		$this->includeComponentTemplate($templateName);
	}

	private function getComponentTheme(): string
	{
		switch ($this->arParams['TEMPLATE_MODE'])
		{
			case 'graymode':
				return 'gray-theme';
			case 'darkmode':
				return 'bx-dark';
			default:
				return '';
		}
	}

	/**
	 * Action for ajax call
	 *
	 * @param array $params
	 * @return PaySystem\ServiceResult
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectException
	 * @throws Main\SystemException
	 */
	public function initiatePayAction(array $params = [])
	{
		$result = new PaySystem\ServiceResult();

		$paysystemId = (int)$params['PAY_SYSTEM_ID'];
		if ($paysystemId > 0)
		{
			$order = $this->loadOrder();
			if ($order && $this->checkAuthorized($order, $params['ACCESS_CODE']))
			{
				$paymentCollection = $order->getPaymentCollection();
				/** @var Sale\Payment $payment */
				$this->payment = $paymentCollection->getItemById($this->paymentId);
				$paySystemObject = PaySystem\Manager::getObjectById($paysystemId);

				Sale\DiscountCouponsManagerBase::freezeCouponStorage();

				$paymentResult = $this->payment->setFields([
					'PAY_SYSTEM_ID' => $paySystemObject->getField('ID'),
					'PAY_SYSTEM_NAME' => $paySystemObject->getField('NAME')
				]);

				if ($paymentResult->isSuccess())
				{
					$order->save();

					if ($returnUrl = $params['RETURN_URL'])
					{
						$paySystemObject->getContext()->setUrl($returnUrl);
					}

					$request = null;
					if ($this->isPaySystemOrderDocument($paySystemObject))
					{
						$request = Main\Context::getCurrent()->getRequest();
						$request->set(['template' => 'template_download']);
					}
					$result = $paySystemObject->initiatePay(
						$this->payment,
						$request,
						PaySystem\BaseServiceHandler::STRING
					);
				}
				else
				{
					$result->addErrors($paymentResult->getErrors());
				}

				Sale\DiscountCouponsManagerBase::unFreezeCouponStorage();
			}
			else
			{
				$result->addError(new Main\Error(Loc::getMessage('SPP_ORDER_NOT_FOUND')));
			}
		}
		else
		{
			$result->addError(new Main\Error(Loc::getMessage('SPP_PAYSYSTEM_NOT_FOUND')));
		}

		return $result;
	}

	/**
	 * @param array $params
	 * @return bool
	 */
	private function initPaymentDataByPaymentId(array $params): bool
	{
		if ((int)($params['PAYMENT_ID']) > 0)
		{
			$filter = ['ID' => (int)$params['PAYMENT_ID']];
		}
		elseif ($params['PAYMENT_ACCOUNT_NUMBER'] != '')
		{
			$filter = ['ACCOUNT_NUMBER' => $params['PAYMENT_ACCOUNT_NUMBER']];
		}

		if (!empty($filter))
		{
			$paymentRow = Sale\Payment::getList([
				'filter' => $filter,
				'select' => ['ORDER_ID', 'ID'],
				'limit' => 1
			]);
			if ($paymentData = $paymentRow->fetch())
			{
				$this->paymentId = (int)$paymentData['ID'];
				$this->orderId = (int)$paymentData['ORDER_ID'];

				return true;
			}
		}
		$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_PAYMENT_NOT_FOUND')));
		return false;
	}

	/**
	 * @param array $params
	 * @return bool
	 */
	private function initPaymentDataByOrderId(array $params): bool
	{
		if ((int)($params['ORDER_ID']) <= 0)
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_ORDER_NOT_FOUND')));
			return false;
		}

		if (!$order = $this->loadOrderById((int)$params['ORDER_ID']))
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_ORDER_NOT_FOUND')));
			return false;
		}

		$paymentSum = $order->getPrice();
		$filter = [
			'ORDER_ID' => $order->getId(),
			'SUM' => $paymentSum,
		];

		$paymentRow = Sale\Payment::getList([
			'filter' => $filter,
			'select' => ['ORDER_ID', 'ID'],
			'limit' => 1
		]);
		if ($paymentData = $paymentRow->fetch())
		{
			$this->paymentId = (int)$paymentData['ID'];
			$this->orderId = (int)$paymentData['ORDER_ID'];
			return true;
		}
		else
		{
			$paySystemObject = $this->getPaySystemForNewPayment($order, $params);
			if (!$paySystemObject)
			{
				$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_PAYSYSTEM_NOT_FOUND')));
				return false;
			}

			$paymentCollection = $order->getPaymentCollection();

			$payment = $paymentCollection->createItem($paySystemObject);
			$paymentResult = $payment->setField('SUM', $paymentSum);

			if (!$paymentResult->isSuccess())
			{
				$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_CANNOT_CREATE_PAYMENT')));
				return false;
			}

			$orderResult = $order->save();
			if ($orderResult->isSuccess())
			{
				$this->paymentId = $payment->getId();
				$this->orderId = $order->getId();
				return true;
			}
			else
			{
				$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_CANNOT_CREATE_PAYMENT')));
				return false;
			}
		}
	}

	/**
	 * @param Sale\Order $order
	 * @param array $componentParams
	 * @return Bitrix\Sale\PaySystem\Service|null
	 */
	private function getPaySystemForNewPayment(Sale\Order $order, array $componentParams)
	{
		if (isset($componentParams['PAY_SYSTEM_ID']) && (int)$componentParams['PAY_SYSTEM_ID'] > 0)
		{
			$paySystemId = (int)$componentParams['PAY_SYSTEM_ID'];
		}
		else
		{
			$paySystemId = $this->getDefaultPaySystemId($order);
		}

		if (!$paySystemId)
		{
			return null;
		}

		return PaySystem\Manager::getObjectById($paySystemId);
	}

	/**
	 * @param Sale\Order $order
	 * @return int|null
	 */
	private function getDefaultPaySystemId(Sale\Order $order)
	{
		$id = 0;

		$cacheManager = Application::getInstance()->getManagedCache();
		$cacheId = self::CACHE_BASE_ID . '_DEFAULT_PS_ID';

		if ($cacheManager->read(self::CACHE_TTL, $cacheId))
		{
			$id = (int)$cacheManager->get($cacheId);
		}

		if ($id <= 0)
		{
			$paySystem = [];
			$paySystemList = Sale\PaySystem\Manager::getListWithRestrictionsByOrder($order);

			foreach ($paySystemList as $item)
			{
				if ($item['ACTION_FILE'] === 'cash')
				{
					$paySystem = $item;
					break;
				}
			}

			if (!$paySystem)
			{
				$paySystem = current($paySystemList);
			}

			if ($paySystem['ID'])
			{
				$id = (int)$paySystem['ID'];
				$cacheManager->set($cacheId, $id);
			}
		}

		return ($id > 0) ? $id : null;
	}

	/**
	 * @param Payment $payment
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	private function formatPaySystemList(Payment $payment)
	{
		$formattedPaySystems = [];
		$salesCenterRestrictionIds = PaySystem\Manager::getList([
			'filter' => [
				'!=ACTION_FILE' => ['inner', 'cash'],
				'ACTIVE' => 'Y',
			],
			'select' => ['ID']
		])->fetchAll();
		$salesCenterRestrictionIds = array_column($salesCenterRestrictionIds, 'ID');
		$paySystemList = PaySystem\Manager::getListWithRestrictions($payment);

		$excludedPaySystemIds = $this->arParams['EXCLUDED_PAY_SYSTEMS'] ?? [];
		$needValidateExcludedPaySystems = $this->arParams['NEED_VALIDATE_EXCLUDED_PAY_SYSTEMS'] ?? false;

		foreach ($paySystemList as $paySystemElement)
		{
			if (!in_array($paySystemElement['ID'], $salesCenterRestrictionIds))
			{
				continue;
			}
			if ($needValidateExcludedPaySystems && in_array((int)$paySystemElement['ID'], $excludedPaySystemIds, true))
			{
				continue;
			}

			$logo = null;
			if ($paySystemElement['LOGOTIP'])
			{
				$logo = CFile::GetFileArray($paySystemElement['LOGOTIP']);
			}

			if ($logo)
			{
				$paySystemElement['LOGOTIP'] = CFile::ResizeImageGet(
					$logo['ID'],
					array(),
					BX_RESIZE_IMAGE_PROPORTIONAL,
					true
				)['src'];
			}
			else
			{
				$paySystemElement['LOGOTIP'] = null;
			}

			$formattedPaySystems[] = $paySystemElement;
		}

		return $formattedPaySystems;
	}

	/**
	 * @param Sale\Order $order
	 */
	private function prepareConsentSettings(Sale\Order $order)
	{
		$agreementId = 0;
		$isConsentActive = Option::get('salescenter', '~SALESCENTER_USER_CONSENT_ACTIVE', 'Y');
		if ($isConsentActive === 'Y')
		{
			if (Loader::includeModule('imopenlines'))
			{
				$request = Main\Context::getCurrent()->getRequest();
				$sessionId = (int)$request->get('sessionIm');
				if ($sessionId > 0)
				{
					$sessionData = SessionTable::getList([
						'select' => ['AGREEMENT_ID' => 'CONFIG.AGREEMENT_ID'],
						'filter' => [
							'=ID' => $sessionId,
							'=USER_ID' => (int)$order->getUserId(),
							'=CONFIG.AGREEMENT_MESSAGE' => 'Y'
						],
						'limit' => 1
					])->fetch();

					if ((int)$sessionData['AGREEMENT_ID'] > 0)
					{
						$agreementId = (int)$sessionData['AGREEMENT_ID'];
						$this->arResult['USER_CONSENT'] = 'Y';
						$this->arResult['USER_CONSENT_IS_CHECKED'] = 'Y';
					}
				}
			}

			if ($agreementId <= 0)
			{
				$agreementId = (int)Option::get('salescenter', '~SALESCENTER_USER_CONSENT_ID');
				if ($agreementId > 0)
				{
					$this->arResult['USER_CONSENT'] = 'Y';
					$this->arResult['USER_CONSENT_IS_CHECKED'] = Option::get('salescenter', '~SALESCENTER_USER_CONSENT_CHECKED');
				}
			}
		}

		$this->arResult['USER_CONSENT_ID'] = $agreementId;
	}

	/**
	 * @return void
	 */
	private function setupRegistry(): void
	{
		$this->registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
	}

	/**
	 * Move all errors to $this->arResult, if there were any
	 * @return void
	 */
	private function formatResultErrors()
	{
		if (!$this->errorCollection->isEmpty())
		{
			/** @var Main\Error $error */
			foreach ($this->errorCollection->toArray() as $error)
			{
				$this->arResult['errorMessage'][] = $error->getMessage();
			}
		}
	}

	/**
	 * @return Sale\Order $order
	 */
	private function loadOrder()
	{
		$order = $this->loadOrderById((int)$this->orderId);
		if (!$order)
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_ORDER_NOT_FOUND')));
			return null;
		}

		return $order;
	}

	/**
	 * @param int $orderId
	 * @return ?Sale\Order
	 */
	private function loadOrderById(int $orderId)
	{
		if ($orderId <= 0)
		{
			return null;
		}

		$orderClassName = $this->registry->getOrderClassName();
		/** @var Sale\Order $order */
		$order = $orderClassName::load($orderId);
		return $order;
	}

	/**
	 * @param Payment $payment
	 * @throws Main\ArgumentException
	 * @throws Main\NotImplementedException
	 */
	private function prepareCheckFields(Sale\Payment $payment): void
	{
		$this->arResult['CHECK'] = [];

		$checkList = Cashbox\CheckManager::getCheckInfo($payment);
		foreach ($checkList as $check)
		{
			$this->arResult['CHECK'][$check['ID']] = [
				'ID' => $check['ID'],
				'DATE_CREATE' => $check['DATE_CREATE'],
				'LINK' => $check['LINK'],
				'STATUS' => $check['STATUS'],
			];
		}
	}

	/**
	 * @param PaySystem\Service $service
	 * @return bool
	 */
	private function isPaySystemOrderDocument(Sale\PaySystem\Service $service): bool
	{
		$handlerClassName = Sale\PaySystem\Manager::getFolderFromClassName(
			\Sale\Handlers\PaySystem\OrderDocumentHandler::class
		);

		return $handlerClassName === $service->getField('ACTION_FILE');
	}

	/**
	 * @return array
	 */
	protected function listKeysSignedParameters()
	{
		return [
			'PAYMENT_ACCOUNT_NUMBER',
			'PAYMENT_ID',
			'ORDER_ID',
			'ACCESS_CODE',
		];
	}

	/**
	 * Check Required Modules
	 * @throws Main\SystemException
	 * @return bool
	 */
	private function checkModules()
	{
		$requiredModules = [
			'sale' => 'SPP_MODULE_SALE_NOT_INSTALL',
			'catalog' => 'SPP_MODULE_CATALOG_NOT_INSTALL',
			'iblock' => 'SPP_MODULE_IBLOCK_NOT_INSTALL',
			'documentgenerator' => 'SPP_MODULE_DOCUMENTGENERATOR_NOT_INSTALL',
		];

		foreach ($requiredModules as $module => $errorMessageCode)
		{
			if (!Loader::includeModule($module))
			{
				$this->errorCollection->setError(new Main\Error(Loc::getMessage($errorMessageCode)));
				return false;
			}
		}

		return true;
	}

	/**
	 * @param Sale\Order $order
	 *
	 * @return bool
	 */
	private function checkAuthorized($order, $accessCode = null)
	{
		if ($accessCode === null)
		{
			$accessCode = $this->arParams['ACCESS_CODE'];
		}
		if ($accessCode !== $order->getHash())
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPOD_ACCESS_DENIED')));
			return false;
		}
		return true;
	}

	/**
	 * @param Payment $payment
	 * @return bool
	 */
	protected function needAddTimelineEntityOnOpen(Sale\Payment $payment): bool
	{
		$dbRes = Crm\Timeline\Entity\TimelineTable::getList([
			'order' => ['ID' => 'ASC'],
			'filter' => [
				'TYPE_ID' => Crm\Timeline\TimelineType::ORDER,
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::OrderPayment,
				'ASSOCIATED_ENTITY_ID' => $payment->getId(),
			]
		]);

		while ($item = $dbRes->fetch())
		{
			if (isset($item['SETTINGS']['FIELDS']['VIEWED']) && $item['SETTINGS']['FIELDS']['VIEWED'] === 'Y')
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @param Payment $payment
	 */
	protected function addTimelineEntityOnView(Sale\Payment $payment): void
	{
		/** @var Crm\Order\Order $order */
		$order = $payment->getOrder();

		$bindings = [
			[
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $order->getId()
			]
		];

		if ($order->getEntityBinding())
		{
			$bindings[] = [
				'ENTITY_TYPE_ID' => $order->getEntityBinding()->getOwnerTypeId(),
				'ENTITY_ID' => $order->getEntityBinding()->getOwnerId()
			];
		}

		$params = [
			'ORDER_FIELDS' => $order->getFieldValues(),
			'SETTINGS' => [
				'FIELDS' => [
					'ORDER_ID' => $order->getId(),
					'PAYMENT_ID' => $payment->getId(),
				]
			],
			'BINDINGS' => $bindings,
			'FIELDS' => $payment->getFieldValues(),
		];

		Crm\Timeline\OrderPaymentController::getInstance()->onView($payment->getId(), $params);
	}

	/**
	 * @param Payment $payment
	 */
	protected function emitOrderViewedEvent(Sale\Payment $payment): void
	{
		if(!Main\Loader::includeModule('pull'))
		{
			return;
		}

		$orderId = $payment->getOrder()->getId();
		if ($orderId <= 0)
		{
			return;
		}

		$tagName = "SALESCENTER_ORDER_PAYMENT_VIEWED_$orderId";
		$params = [
			'ORDER_ID' => $orderId,
			'PAYMENT_ID' => $payment->getId(),
		];
		$message = [
			'module_id' => 'salescenter',
			'command' => 'onOrderPaymentViewed',
			'params' => $params,
		];

		\CPullWatch::AddToStack($tagName, $message);
	}

	/**
	 * @param $dealId
	 */
	private function changeOrderStageDealOnViewedNoPaid($dealId): void
	{
		$fields = ['ORDER_STAGE' => Crm\Order\OrderStage::VIEWED_NO_PAID];

		$deal = new \CCrmDeal(false);
		$deal->Update($dealId, $fields);
	}
}
