<?php

use Bitrix\Crm\Order\DealBinding;
use Bitrix\Main,
	Bitrix\Sale,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader,
	Bitrix\Sale\Payment,
	Bitrix\Sale\Cashbox,
	Bitrix\ImOpenLines\Model\SessionTable,
	Bitrix\Sale\PaySystem,
	Bitrix\Main\Config\Option,
	Bitrix\Crm;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

/**
 * Class SalesCenterPaymentPay
 */
class SalesCenterPaymentPay extends \CBitrixComponent implements Main\Engine\Contract\Controllerable
{
	/** @var  Main\ErrorCollection $errorCollection*/
	protected $errorCollection;

	protected $isViewMode = false;
	protected $paymentId = null;
	/** @var Sale\Payment $payment */
	protected $payment = null;
	protected $orderId = null;

	/**
	 * @var Sale\Registry registry
	 */
	protected $registry = null;

	public function configureActions()
	{
		return array();
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

	protected function listKeysSignedParameters()
	{
		return array(
			'PAYMENT_ACCOUNT_NUMBER',
			'PAYMENT_ID'
		);
	}

	/**
	 * Function checks and prepares all the parameters passed. Everything about $arParam modification is here.
	 * @param mixed[] $params List of unchecked parameters
	 * @return mixed[] Checked and valid parameters
	 */
	public function onPrepareComponentParams($params)
	{
		$this->errorCollection = new Main\ErrorCollection();
		$this->checkModules();

		if (empty($params["ACTIVE_DATE_FORMAT"]))
		{
			$params["ACTIVE_DATE_FORMAT"] = Main\Type\DateTime::getFormat();
		}

		if (empty($params["ALLOW_PAYMENT_REDIRECT"]))
		{
			$params["ALLOW_PAYMENT_REDIRECT"] = "Y";
		}

		if ((int)($params["PAYMENT_ID"]) > 0)
		{
			$filter = ['ID' => (int)$params["PAYMENT_ID"]];
		}
		elseif ($params["PAYMENT_ACCOUNT_NUMBER"] <> '')
		{
			$filter = ['ACCOUNT_NUMBER' => $params["PAYMENT_ACCOUNT_NUMBER"]];
		}

		if (empty($filter))
		{
			$this->isViewMode = true;
			$params['VIEW_MODE'] = 'Y';

			return $params;
		}

		$paymentRaw = Sale\Payment::getList([
			'filter' => $filter,
			'select' => ['ORDER_ID', 'ID'],
			'limit' => 1
		]);
		if ($paymentData = $paymentRaw->fetch())
		{
			$this->paymentId = $paymentData['ID'];
			$this->orderId = $paymentData['ORDER_ID'];
		}
		else
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_PAYMENT_NOT_FOUND')));
			return null;
		}

		return $params;
	}

	/**
	 * Check Required Modules
	 * @throws Main\SystemException
	 * @return bool
	 */
	protected function checkModules()
	{
		if (!Loader::includeModule('salescenter'))
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_MODULE_SALESCENTER_NOT_INSTALL')));
			return false;
		}
		if (!Loader::includeModule('sale'))
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_MODULE_SALE_NOT_INSTALL')));
			return false;
		}
		if (!Loader::includeModule('catalog'))
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_MODULE_CATALOG_NOT_INSTALL')));
			return false;
		}
		if (!Loader::includeModule('iblock'))
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_MODULE_IBLOCK_NOT_INSTALL')));
			return false;
		}
		if (!Loader::includeModule('crm'))
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_MODULE_CRM_NOT_INSTALL')));
			return false;
		}
		if (!Loader::includeModule('documentgenerator'))
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_MODULE_DOCUMENTGENERATOR_NOT_INSTALL')));
			return false;
		}

		return true;
	}

	/**
	 * @param Sale\Order $order
	 *
	 * @return bool
	 */
	private function checkAuthorized($order)
	{
		$request = Main\Context::getCurrent()->getRequest();
		if ($request->get('access') !== $order->getHash())
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage("SPOD_ACCESS_DENIED")));
			return false;
		}
		return true;
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
			$this->setRegistry();

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
					$this->arResult['PAYMENT']['FORMATTED_SUM'] = SaleFormatCurrency($this->payment->getSum(), $this->payment->getField('CURRENCY'));
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
				}

				$this->arResult['RETURN_URL'] = (new PaySystem\Context())->getUrl();
			}
		}

		$this->formatResultErrors();
		$this->includeComponentTemplate($templateName);
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
		$salesCenterRestrictionIds = PaySystem\Manager::getList(array(
			'filter' => [
				'!=ACTION_FILE' => 'inner',
				'ACTIVE' => 'Y',
			],
			'select' => ['ID']
		))->fetchAll();
		$salesCenterRestrictionIds = array_column($salesCenterRestrictionIds, 'ID');
		$paySystemList = PaySystem\Manager::getListWithRestrictions($payment);
		foreach ($paySystemList as $paySystemElement)
		{
			if (!in_array($paySystemElement['ID'], $salesCenterRestrictionIds))
			{
				continue;
			}

			$logo = null;
			if ($paySystemElement["LOGOTIP"])
			{
				$logo = CFile::GetFileArray($paySystemElement['LOGOTIP']);
			}

			if ($logo)
			{
				$paySystemElement["LOGOTIP"] = CFile::ResizeImageGet(
					$logo["ID"],
					array(),
					BX_RESIZE_IMAGE_PROPORTIONAL,
					true
				)['src'];
			}
			else
			{
				$paySystemElement["LOGOTIP"] = null;
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
	 * Return current class registry
	 *
	 * @param mixed[] array that date conversion performs in
	 * @return void
	 */
	protected function setRegistry()
	{
		$this->registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
	}

	/**
	 * Move all errors to $this->arResult, if there were any
	 * @return void
	 */
	protected function formatResultErrors()
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
	 * Initialize new order
	 * @return Sale\Order $order
	 */
	protected function loadOrder()
	{
		$orderClassName = $this->registry->getOrderClassName();
		/** @var Sale\Order $order */
		$order = $orderClassName::load($this->orderId);
		if (!$order)
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('SPP_PAYMENT_NOT_FOUND')));
			return null;
		}

		return $order;
	}

	/**
	 * @param Payment $payment
	 * @throws Main\ArgumentException
	 * @throws Main\NotImplementedException
	 */
	protected function prepareCheckFields(Sale\Payment $payment): void
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
			$this->setRegistry();
			$order = $this->loadOrder();
			if ($order)
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
					$this->addTimelineEntryOnPay($this->payment);

					if ($returnUrl = $params['RETURN_URL'])
					{
						$paySystemObject->getContext()->setUrl($returnUrl);
					}

					$request = null;
					if ($this->isPaySystemOrderDocument($paySystemObject))
					{
						$request = Main\Context::getCurrent()->getRequest();
						$request->set(["template" => "template_download"]);
					}
					$result = $paySystemObject->initiatePay($this->payment, $request, PaySystem\BaseServiceHandler::STRING);
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
	 * @param Payment $payment
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	private function addTimelineEntryOnPay(Sale\Payment $payment): void
	{
		/** @var Crm\Order\Order $order */
		$order = $payment->getOrder();

		$bindings = [
			[
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_ID' => $order->getId(),
			]
		];

		/** @var DealBinding $dealBindings */
		if ($dealBindings = $order->getDealBinding())
		{
			$bindings[] = [
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID' => $dealBindings->getDealId(),
			];
		}

		$timelineParams =  [
			'FIELDS' => $payment->getFieldValues(),
			'SETTINGS' => [
				'CHANGED_ENTITY' => \CCrmOwnerType::OrderPaymentName,
				'FIELDS' => [
					'PAY_SYSTEM_ID' => $payment->getPaymentSystemId(),
					'PAY_SYSTEM_NAME' => $payment->getPaymentSystemName(),
				],
			],
			'BINDINGS' => $bindings,
		];

		Crm\Timeline\OrderPaymentController::getInstance()->onClick($payment->getId(), $timelineParams);
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
}