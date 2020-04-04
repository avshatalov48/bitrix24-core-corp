<?php

namespace Bitrix\Sale\PaySystem;

use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Main\Entity\EntityError;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\Request;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\Registry;
use Bitrix\Sale\Result;
use Bitrix\Main\IO;
use Bitrix\Sale\ResultError;

Loc::loadMessages(__FILE__);

/**
 * Class Service
 * @package Bitrix\Sale\PaySystem
 */
class Service
{
	const EVENT_ON_BEFORE_PAYMENT_PAID = 'OnSalePsServiceProcessRequestBeforePaid';
	const PAY_SYSTEM_PREFIX = 'PAYSYSTEM_';

	/** @var ServiceHandler|IHold|IRefund|IPrePayable|ICheckable|IPayable|IRequested $handler */
	private $handler = null;

	/** @var array */
	private $fields = array();

	/** @var bool */
	protected $isClone = false;

	/**
	 * Service constructor.
	 * @param $fields
	 */
	public function __construct($fields)
	{
		$handlerType = '';
		$className = '';

		$name = Manager::getFolderFromClassName($fields['ACTION_FILE']);

		foreach (Manager::getHandlerDirectories() as $type => $path)
		{
			if (IO\File::isFileExists($_SERVER['DOCUMENT_ROOT'].$path.$name.'/handler.php'))
			{
				$className = Manager::getClassNameFromPath($fields['ACTION_FILE']);
				if (!class_exists($className))
					require_once($_SERVER['DOCUMENT_ROOT'].$path.$name.'/handler.php');

				if (class_exists($className))
				{
					$handlerType = $type;
					break;
				}

				$className = '';
			}
		}

		if ($className === '')
		{
			if (Manager::isRestHandler($fields['ACTION_FILE']))
			{
				$className = '\Bitrix\Sale\PaySystem\RestHandler';
				if (!class_exists($fields['ACTION_FILE']))
				{
					class_alias($className, $fields['ACTION_FILE']);
				}
			}
			else
			{
				$className = '\Bitrix\Sale\PaySystem\CompatibilityHandler';
			}
		}

		$this->fields = $fields;
		$this->handler = new $className($handlerType, $this);
	}

	/**
	 * @param Payment $payment
	 */
	public function preInitiatePay(Payment $payment)
	{
		$this->handler->preInitiatePay($payment);
	}

	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @param int $mode
	 * @return ServiceResult
	 */
	public function initiatePay(Payment $payment, Request $request = null, $mode = BaseServiceHandler::STREAM)
	{
		$this->handler->setInitiateMode($mode);
		$initResult = $this->handler->initiatePay($payment, $request);

		$psData = $initResult->getPsData();
		if ($psData)
		{
			$setResult = $payment->setFields($psData);
			if ($setResult->isSuccess())
			{
				/** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
				$paymentCollection = $payment->getCollection();
				if ($paymentCollection)
				{
					$order = $paymentCollection->getOrder();
					if ($order)
					{
						$saveResult = $order->save();
						if (!$saveResult->isSuccess())
							$initResult->addErrors($saveResult->getErrors());
					}
				}
			}
			else
			{
				$initResult->addErrors($setResult->getErrors());
			}
		}

		if (!$initResult->isSuccess())
		{
			$error = implode("\n", $initResult->getErrorMessages());
			Logger::addError($error);
		}

		return $initResult;
	}

	/**
	 * @return bool
	 */
	public function isRefundable()
	{
		if ($this->handler instanceof IRefundExtended)
			return $this->handler->isRefundableExtended();

		return $this->handler instanceof IRefund;
	}

	/**
	 * @param Payment $payment
	 * @param int $refundableSum
	 * @return ServiceResult|Result
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function refund(Payment $payment, $refundableSum = 0)
	{
		if ($this->isRefundable())
		{
			$result = new Result();

			if (!$payment->isPaid())
			{
				$result->addError(new ResultError(Loc::getMessage('SALE_PS_SERVICE_PAYMENT_NOT_PAID')));
				return $result;
			}

			if ($refundableSum == 0)
				$refundableSum = $payment->getSum();

			/** @var ServiceResult $result */
			$result = $this->handler->refund($payment, $refundableSum);

			return $result;
		}

		throw new SystemException();
	}

	/**
	 * @param Request $request
	 * @return Result
	 */
	public function processRequest(Request $request)
	{
		$processResult = new Result();

		if (!($this->handler instanceof ServiceHandler))
		{
			return $processResult;
		}

		$debugInfo = 'request: '.($request->toArray() ? implode("\n", $request->toArray()) : '[]');
		Logger::addDebugInfo($debugInfo);

		$paymentId = $this->handler->getPaymentIdFromRequest($request);

		if (empty($paymentId))
		{
			$processResult->addError(new Error(Loc::getMessage('SALE_PS_SERVICE_PAYMENT_ERROR_EMPTY')));

			Logger::addError('processRequest: '.Loc::getMessage('SALE_PS_SERVICE_PAYMENT_ERROR_EMPTY'));

			return $processResult;
		}

		list($orderId, $paymentId) = Manager::getIdsByPayment($paymentId, $this->getField('ENTITY_REGISTRY_TYPE'));

		if (!$orderId)
		{
			$errorMessage = str_replace('#ORDER_ID#', $orderId, Loc::getMessage('SALE_PS_SERVICE_ORDER_ERROR'));
			$processResult->addError(new Error($errorMessage));

			Logger::addError('processRequest: '.$errorMessage);

			return $processResult;
		}

		$registry = Registry::getInstance($this->getField('ENTITY_REGISTRY_TYPE'));
		/** @var Order $orderClassName */
		$orderClassName = $registry->getOrderClassName();

		$order = $orderClassName::load($orderId);

		if (!$order)
		{
			$errorMessage = str_replace('#ORDER_ID#', $orderId, Loc::getMessage('SALE_PS_SERVICE_ORDER_ERROR'));
			$processResult->addError(new Error($errorMessage));

			Logger::addError('processRequest: '.$errorMessage);

			return $processResult;
		}

		if ($order->isCanceled())
		{
			$errorMessage = str_replace('#ORDER_ID#', $orderId, Loc::getMessage('SALE_PS_SERVICE_ORDER_CANCELED'));
			$processResult->addError(new Error($errorMessage));

			Logger::addError('processRequest: '.$errorMessage);

			return $processResult;
		}

		/** @var \Bitrix\Sale\PaymentCollection $collection */
		$collection = $order->getPaymentCollection();

		/** @var \Bitrix\Sale\Payment $payment */
		$payment = $collection->getItemById($paymentId);

		if (!$payment)
		{
			$errorMessage = str_replace('#PAYMENT_ID#', $paymentId, Loc::getMessage('SALE_PS_SERVICE_PAYMENT_ERROR'));
			$processResult->addError(new Error($errorMessage));

			Logger::addError('processRequest: '.$errorMessage);

			return $processResult;
		}

		/** @var \Bitrix\Sale\PaySystem\ServiceResult $serviceResult */
		$serviceResult = $this->handler->processRequest($payment, $request);

		if ($serviceResult->isSuccess())
		{
			$status = null;
			$operationType = $serviceResult->getOperationType();

			if ($operationType == ServiceResult::MONEY_COMING)
				$status = 'Y';
			else if ($operationType == ServiceResult::MONEY_LEAVING)
				$status = 'N';

			if ($status !== null)
			{
				$event = new Event('sale', self::EVENT_ON_BEFORE_PAYMENT_PAID,
					array(
						'payment' => $payment,
						'status' => $status,
						'pay_system_id' => $this->getField('ID')
					)
				);
				$event->send();

				$paidResult = $payment->setPaid($status);
				if (!$paidResult->isSuccess())
				{
					$error = 'PAYMENT SET PAID: '.join(' ', $paidResult->getErrorMessages());
					Logger::addError($error);

					$serviceResult->setResultApplied(false);
				}
			}

			$psData = $serviceResult->getPsData();
			if ($psData)
			{
				$res = $payment->setFields($psData);

				if (!$res->isSuccess())
				{
					$error = 'PAYMENT SET PAID: '.join(' ', $res->getErrorMessages());
					Logger::addError($error);

					$serviceResult->setResultApplied(false);
				}
			}

			$saveResult = $order->save();

			if (!$saveResult->isSuccess())
			{
				$error = 'ORDER SAVE: '.join(' ', $saveResult->getErrorMessages());
				Logger::addError($error);

				$serviceResult->setResultApplied(false);
			}
		}
		else
		{
			$serviceResult->setResultApplied(false);
			$processResult->addErrors($serviceResult->getErrors());
		}

		$this->handler->sendResponse($serviceResult, $request);

		return $processResult;
	}

	/**
	 * @return string
	 */
	public function getConsumerName()
	{
		return static::PAY_SYSTEM_PREFIX.$this->fields['ID'];
	}

	/**
	 * @return array
	 */
	public function getHandlerDescription()
	{
		return $this->handler->getDescription();
	}

	/**
	 * @return bool
	 */
	public function isBlockable()
	{
		return $this->handler instanceof IHold;
	}

	/**
	 * @param Payment $payment
	 * @return mixed
	 * @throws SystemException
	 */
	public function cancel(Payment $payment)
	{
		if ($this->isBlockable())
			return $this->handler->cancel($payment);

		throw new SystemException();
	}

	/**
	 * @param Payment $payment
	 * @return mixed
	 * @throws SystemException
	 */
	public function confirm(Payment $payment)
	{
		if ($this->isBlockable())
			return  $this->handler->confirm($payment);

		throw new SystemException();
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function getField($name)
	{
		return $this->fields[$name];
	}

	/**
	 * @return array
	 */
	public function getCurrency()
	{
		return $this->handler->getCurrencyList();
	}

	/**
	 * @return bool
	 */
	public function isCash()
	{
		return $this->fields['IS_CASH'] == 'Y';
	}

	/**
	 * @param Payment $payment
	 * @return ServiceResult
	 */
	public function creditNoDemand(Payment $payment)
	{
		return $this->handler->creditNoDemand($payment);
	}

	/**
	 * @param Payment $payment
	 * @return ServiceResult
	 */
	public function debitNoDemand(Payment $payment)
	{
		return $this->handler->debitNoDemand($payment);
	}

	/**
	 * @return bool
	 */
	public function isPayable()
	{
		if ($this->handler instanceof IPayable)
			return true;

		if (method_exists($this->handler, 'isPayableCompatibility'))
			return $this->handler->isPayableCompatibility();

		return false;
	}

	/**
	 * @return bool
	 */
	public function isAffordPdf()
	{
		return $this->handler->isAffordPdf();
	}

	/**
	 * @param Payment $payment
	 * @return mixed
	 */
	public function getPaymentPrice(Payment $payment)
	{
		if ($this->isPayable())
			return $this->handler->getPrice($payment);

		return 0;
	}

	/**
	 * @param array $params
	 */
	public function setTemplateParams(array $params)
	{
		$this->handler->setExtraParams($params);
	}

	/**
	 * @param Payment|null $payment
	 * @param $templateName
	 * @return ServiceResult
	 */
	public function showTemplate(Payment $payment = null, $templateName)
	{
		return $this->handler->showTemplate($payment, $templateName);
	}

	/**
	 * @return bool
	 */
	public function isPrePayable()
	{
		return $this->handler instanceof IPrePayable;
	}

	/**
	 * @param Payment|null $payment
	 * @param Request $request
	 * @throws NotSupportedException
	 */
	public function initPrePayment(Payment $payment = null, Request $request)
	{
		if ($this->isPrePayable())
			return $this->handler->initPrePayment($payment, $request);

		throw new NotSupportedException;
	}

	/**
	 * @return mixed
	 * @throws NotSupportedException
	 */
	public function getPrePaymentProps()
	{
		if ($this->isPrePayable())
			return $this->handler->getProps();

		throw new NotSupportedException;
	}

	/**
	 * @param array $orderData
	 * @return mixed
	 * @throws NotSupportedException
	 */
	public function basketButtonAction(array $orderData = array())
	{
		if ($this->isPrePayable())
			return $this->handler->basketButtonAction($orderData);

		throw new NotSupportedException;
	}

	/**
	 * @param array $orderData
	 * @return mixed
	 * @throws NotSupportedException
	 */
	public function setOrderDataForPrePayment($orderData = array())
	{
		if ($this->isPrePayable())
			return $this->handler->setOrderConfig($orderData);

		throw new NotSupportedException;
	}

	/**
	 * @param $orderData
	 * @return mixed
	 * @throws NotSupportedException
	 */
	public function payOrderByPrePayment($orderData)
	{
		if ($this->isPrePayable())
			return $this->handler->payOrder($orderData);

		throw new NotSupportedException;
	}

	/**
	 * @return array
	 */
	public function getFieldsValues()
	{
		return $this->fields;
	}

	/**
	 * @return bool
	 */
	public function isAllowEditPayment()
	{
		return $this->fields['ALLOW_EDIT_PAYMENT'] == 'Y';
	}

	/**
	 * @return bool
	 */
	public function isCheckable()
	{
		if ($this->handler instanceof ICheckable)
			return true;

		if (method_exists($this->handler, 'isCheckableCompatibility'))
			return $this->handler->isCheckableCompatibility();

		return false;
	}

	/**
	 * @param Payment $payment
	 * @return ServiceResult
	 */
	public function check(Payment $payment)
	{
		$result = new ServiceResult();

		if ($this->isCheckable())
		{
			/** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
			$paymentCollection = $payment->getCollection();

			/** @var \Bitrix\Sale\Order $order */
			$order = $paymentCollection->getOrder();

			if (!$order->isCanceled())
			{
				/** @var ServiceResult $result */
				$checkResult = $this->handler->check($payment);
				if ($checkResult instanceof ServiceResult && $checkResult->isSuccess())
				{
					$psData = $checkResult->getPsData();
					if ($psData)
					{
						$res = $payment->setFields($psData);
						if (!$res->isSuccess())
							$result->addErrors($res->getErrors());
					}

					if ($checkResult->getOperationType() == ServiceResult::MONEY_COMING)
					{
						$res = $payment->setPaid('Y');
						if (!$res->isSuccess())
							$result->addErrors($res->getErrors());
					}

					$res = $order->save();
					if (!$res->isSuccess())
						$result->addErrors($res->getErrors());
				}
				elseif (!$checkResult)
				{
					$result->addError(new Error(Loc::getMessage('SALE_PS_SERVICE_ERROR_CONNECT_PS')));
				}
			}
			else
			{
				$result->addError(new EntityError(Loc::getMessage('SALE_PS_SERVICE_ORDER_CANCELED', array('#ORDER_ID#' => $order->getId()))));
			}
		}

		return $result;
	}

	/**
	 * @param \SplObjectStorage $cloneEntity
	 *
	 * @return Service
	 */
	public function createClone(\SplObjectStorage $cloneEntity)
	{
		if ($this->isClone() && $cloneEntity->contains($this))
		{
			return $cloneEntity[$this];
		}

		$paySystemServiceClone = clone $this;
		$paySystemServiceClone->isClone = true;

		if (!$cloneEntity->contains($this))
		{
			$cloneEntity[$this] = $paySystemServiceClone;
		}

		if ($handler = $this->handler)
		{
			if (!$cloneEntity->contains($handler))
			{
				$cloneEntity[$handler] = $handler->createClone($cloneEntity);
			}

			if ($cloneEntity->contains($handler))
			{
				$paySystemServiceClone->handler = $cloneEntity[$handler];
			}
		}

		return $paySystemServiceClone;
	}

	/**
	 * @return bool
	 */
	public function isClone()
	{
		return $this->isClone;
	}

	/**
	 * @return bool
	 */
	public function isCustom()
	{
		return in_array($this->handler->getHandlerType(), array('CUSTOM', 'USER'));
	}

	/**
	 * @param Payment $payment
	 * @return array
	 */
	public function getParamsBusValue(Payment $payment)
	{
		return $this->handler->getParamsBusValue($payment);
	}

	/**
	 * @return bool
	 */
	public function isRequested()
	{
		return $this->handler instanceof IRequested;
	}

	/**
	 * @param $requestId
	 * @return ServiceResult
	 * @throws NotSupportedException
	 */
	public function checkMovementListStatus($requestId)
	{
		if ($this->isRequested())
			return $this->handler->getMovementListStatus($requestId);

		throw new NotSupportedException;
	}

	/**
	 * @param $requestId
	 * @return ServiceResult
	 * @throws NotSupportedException
	 */
	public function getMovementList($requestId)
	{
		if ($this->isRequested())
			return $this->handler->getMovementList($requestId);

		throw new NotSupportedException;
	}

	/**
	 * @param Request $request
	 * @return ServiceResult
	 */
	public function processAccountMovementList(Request $request)
	{
		$serviceResult = new ServiceResult();

		if ($this->isRequested())
		{
			$requestId = $request->get('requestId');
			if ($requestId === null)
			{
				$result = $this->handler->createMovementListRequest($request);
				if ($result->isSuccess())
				{
					$data = $result->getData();
					$requestId = $data['requestId'];
				}
				else
				{
					$error = 'createMovementListRequest: '.implode("\n", $result->getErrorMessages());
					Logger::addError($error);

					$serviceResult->addErrors($result->getErrors());
					return $serviceResult;
				}
			}

			if ($requestId)
			{
				$result = $this->handler->getMovementListStatus($requestId);
				if (!$result->isSuccess())
				{
					$serviceResult->addErrors($result->getErrors());
					return $serviceResult;
				}

				$data = $result->getData();
				if ($data['status'] === false)
				{
					$timeSleep = 2;
					$estimatedTime = $data['estimatedTime'] ?: '';
					if ($estimatedTime)
					{
						$dateTime = new DateTime($estimatedTime);
						$timeSleep = $dateTime->getTimestamp() - time();
					}

					$serviceResult->setData(array('timeSleep' => $timeSleep, 'requestId' => $requestId));
				}
				else
				{
					$result = $this->handler->getMovementList($requestId);
					if ($result->isSuccess())
					{
						$data = $result->getData();
						if ($data['ITEMS'])
						{
							$result = $this->applyAccountMovementList($data['ITEMS'], $this->getField('ENTITY_REGISTRY_TYPE'));
							if (!$result->isSuccess())
							{
								$error = 'getMovementList: '.implode("\n", $result->getErrorMessages());
								Logger::addError($error);
							}

							return $result;
						}
					}
					else
					{
						$error = 'getMovementList: '.implode("\n", $result->getErrorMessages());
						Logger::addError($error);
						$serviceResult->addErrors($result->getErrors());
					}
				}
			}
			else
			{
				$serviceResult->addError(new Error(Loc::getMessage('SALE_PS_SERVICE_REQUEST_ERROR')));
			}
		}

		return $serviceResult;
	}

	/**
	 * @param $movementList
	 * @param string $registryType
	 * @return ServiceResult
	 */
	private function applyAccountMovementList($movementList, $registryType = Registry::REGISTRY_TYPE_ORDER)
	{
		$serviceResult = new ServiceResult();
		$paymentList = array();
		$usedOrders = array();

		if ($this->isRequested())
		{
			foreach ($movementList as $item)
			{
				if ($item['OPERATION'] !== 'C')
					continue;

				$info = array(
					'ACCOUNT_NUMBER' => '',
					'PRICE' => $item['SUM'],
					'CONTRACTOR_INN' => $item['CONTRACTOR_INN'],
					'CONTRACTOR_KPP' => $item['CONTRACTOR_KPP'],
					'DOC_NUMBER' => $item['DOC_NUMBER'],
					'CHARGE_DATE' => $item['CHARGE_DATE'],
					'PAID_BEFORE' => 'N'
				);

				$entityIds = array();
				if (!empty($item['PAYMENT_ID']))
					$entityIds[] = Manager::getIdsByPayment($item['PAYMENT_ID']);
				else
					$entityIds = $this->findEntityIds($item);

				if ($entityIds)
				{
					foreach ($entityIds as $entityId)
					{
						list($orderId, $paymentId) = $entityId;
						if ($orderId > 0)
						{
							$hash = md5($orderId);
							if (isset($usedOrders[$hash]))
							{
								continue;
							}

							$registry = Registry::getInstance($registryType);
							/** @var Order $orderClassName */
							$orderClassName = $registry->getOrderClassName();

							$order = $orderClassName::load($orderId);
							if ($order)
							{
								$paymentCollection = $order->getPaymentCollection();
								if ($paymentCollection && $paymentId > 0)
								{
									/** @var \Bitrix\Sale\Payment $payment */
									$payment = $paymentCollection->getItemById($paymentId);
									if ($payment)
									{
										if (roundEx($payment->getSum(), 2) === roundEx($item['SUM'], 2))
										{
											$info['ACCOUNT_NUMBER'] = $order->getField('ACCOUNT_NUMBER');
											$info['ORDER_ID'] = $order->getId();

											$usedOrders[$hash] = $orderId;
											if ($payment->isPaid())
											{
												$info['PAID_BEFORE'] = 'Y';
											}
											else
											{
												$result = $payment->setPaid('Y');
												if ($result->isSuccess())
													$result = $order->save();

												if (!$result->isSuccess())
													$serviceResult->addErrors($result->getErrors());
												else
													break;
											}
										}
									}
								}
							}
						}
					}
				}

				$paymentList[] = $info;
			}
		}

		$serviceResult->setData(array('PAYMENT_LIST' => $paymentList));
		return $serviceResult;
	}

	/**
	 * @param $item
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function findEntityIds($item)
	{
		$result = array();

		$personTypeList = Manager::getPersonTypeIdList($this->getField('ID'));

		$map = BusinessValue::getMapping('BUYER_PERSON_COMPANY_INN', $this->getConsumerName(), array_shift($personTypeList));
		if ($map)
		{
			$filter = array();
			$runtimeFields = array();

			$type = $map['PROVIDER_KEY'];
			$value = $map['PROVIDER_VALUE'];

			if ($type == 'PROPERTY')
			{
				$runtimeFields['PROP'] = array(
					'data_type' => 'Bitrix\Sale\Internals\OrderPropsValueTable',
					'reference' => array('ref.ORDER_ID' => 'this.ORDER_ID'),
					'join_type' => 'inner'
				);

				$filter = array('PROP.CODE' => $value, 'PROP.VALUE' => $item['CONTRACTOR_INN']);
			}
			elseif ($type == 'REQUISITE')
			{
				if (!Loader::includeModule('crm'))
					return $result;

				$orderIds = array();

				$requisite = new EntityRequisite();
				$res = $requisite->getList(
					array(
						'select' => array('ID'),
						'filter' => array(
							'=ENTITY_TYPE_ID' => array(\CCrmOwnerType::Company, \CCrmOwnerType::Contact),
							'=RQ_INN' => $item['CONTRACTOR_INN']
						)
					)
				);

				$rqIds = array();
				while ($row = $res->fetch())
					$rqIds[] = $row['ID'];

				if ($rqIds)
				{
					$res = EntityLink::getList(
						array(
							'select' => array('ENTITY_ID'),
							'filter' => array('=ENTITY_TYPE_ID' => \CCrmOwnerType::Invoice, '=REQUISITE_ID' => $rqIds)
						)
					);

					while ($row = $res->fetch())
						$orderIds[] = $row['ENTITY_ID'];
				}

				if ($orderIds)
					$filter = array('ORDER_ID' => $orderIds);
			}

			if ($filter)
			{
				$dbRes = Payment::getList(array('select' => array('ID', 'ORDER_ID', 'SUM', 'CURRENCY'), 'filter' => $filter, 'order' => array('ID' => 'ASC'), 'runtime' => $runtimeFields));
				while ($data = $dbRes->fetch())
				{
					if (roundEx($data['SUM'], 2) === roundEx($item['SUM'], 2))
						$result[] = array($data['ORDER_ID'], $data['ID']);
				}
			}
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function isTuned()
	{
		return $this->handler->isTuned();
	}

	/**
	 * @return array
	 */
	public function getDemoParams()
	{
		return $this->handler->getDemoParams();
	}

	/**
	 * @param $mode
	 */
	public function setTemplateMode($mode)
	{
		$this->handler->setInitiateMode($mode);
	}
}