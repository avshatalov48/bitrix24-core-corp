<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Order\Permissions;
use \Bitrix\Main\Type\Date;
use Bitrix\Sale\Payment;

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

Loc::loadMessages(__FILE__);

if(!Loader::includeModule('crm'))
{
	die('Can\'t include module CRM');
}

/** @internal  */
final class AjaxProcessor extends \Bitrix\Crm\Order\AjaxProcessor
{
	protected function saveAction()
	{
		$id = (int)$this->request['ACTION_ENTITY_ID'] > 0 ? (int)$this->request['ACTION_ENTITY_ID'] : 0;
		$isNew = $id <= 0;
		$paymentData = [];

		if(!empty($this->request['ORDER_PAYMENT_DATA']))
		{
			$paymentData = current(\CUtil::JsObjectToPhp($this->request['ORDER_PAYMENT_DATA']));

			if(!is_array($paymentData))
			{
				$paymentData = [];
			}
			else
			{
				unset($paymentData['ACCOUNT_NUMBER']);
			}
		}

		$paymentFields = [];

		if($id > 0)
		{
			if (!Permissions\Payment::checkUpdatePermission($id, $this->userPermissions))
			{
				$this->addError(Loc::getMessage("CRM_ORDER_P_ACCESS_DENIED"));
				return;
			}

			$res = \Bitrix\Crm\Order\Payment::getList(['filter'=>['=ID' => $id]]);

			if(!($paymentFields = $res->fetch()))
			{
				$this->addError(Loc::getMessage("CRM_ORDER_P_NOT_FOUND"));
				return;
			}
		}
		else
		{
			if (!Permissions\Payment::checkCreatePermission($this->userPermissions))
			{
				$this->addError(new \Bitrix\Main\Error(Loc::getMessage('CRM_ORDER_P_ACCESS_DENIED')));
				return;
			}
		}

		$paymentFields = array_merge(
			$paymentFields,
			$paymentData,
			$this->request
		);

		$payment = $this->buildPayment($paymentFields);
		if (!$payment || !$this->result->isSuccess())
		{
			return;
		}

		$verifyResult = $payment->verify();
		if (!$verifyResult->isSuccess())
		{
			$this->addErrors($verifyResult->getErrors());
			return;
		}

		$res = $payment->getCollection()->getOrder()->save();
		if(!$res->isSuccess())
		{
			$this->addErrors($res->getErrors());
			return;
		}

		if($res->hasWarnings())
		{
			$this->addWarnings($res->getWarnings());
		}

		$id = $payment->getId();
		$this->addData(['ENTITY_ID' => $payment->getId(), 'ENTITY_DATA' => $this->createDataByComponent($payment)]);

		if($isNew)
		{
			$this->addData(['REDIRECT_URL' =>\CCrmOwnerType::GetDetailsUrl(
				\CCrmOwnerType::OrderPayment,
				$id,
				false,
				['OPEN_IN_SLIDER' => true]
			)]);
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
				'PAYMENT_DATA' => $this->createDataByComponent($paymentObj)
			]);
		}
		else
		{
			$this->addErrors($res->getErrors());
		}
	}

	protected function refreshPaymentDataAction()
	{
		if(!($formData = $this->getFormData()))
		{
			return;
		}

		if ((int)$formData['ID'] <= 0)
		{
			if (!Permissions\Payment::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_P_ACCESS_DENIED'));
				return;
			}
		}
		elseif (!Permissions\Payment::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_P_ACCESS_DENIED'));
			return;
		}

		if(!($payment = $this->buildPayment($formData)))
		{
			return;
		}

		$this->addData([
			'PAYMENT_DATA' => $this->createDataByComponent($payment)
		]);
	}

	protected function rollbackAction()
	{
		if(!($formData = $this->getFormData()))
		{
			return;
		}

		if (!Permissions\Payment::checkUpdatePermission((int)$formData['ID'], $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_P_ACCESS_DENIED'));
			return;
		}

		if(!($payment = \Bitrix\Crm\Order\Manager::getPaymentObject($formData['ID'])))
		{
			return;
		}

		$this->addData([
			'PAYMENT_DATA' => $this->createDataByComponent($payment)
		]);
	}

	protected function getFormData()
	{
		$result = [];

		if(isset($this->request['FORM_DATA']) && is_array($this->request['FORM_DATA']) && !empty($this->request['FORM_DATA']))
		{
			$result = $this->request['FORM_DATA'];
		}
		else
		{
			$this->addError(Loc::getMessage('CRM_ORDER_P_FORM_DATA_MISSING'));
		}

		return $result;
	}

	private function createDataByComponent(\Bitrix\Crm\Order\Payment $payment)
	{
		\CBitrixComponent::includeComponentClass('bitrix:crm.order.payment.details');
		$component = new \CCrmOrderPaymentDetailsComponent();
		$component->initializeParams(
			isset($this->request['PARAMS']) && is_array($this->request['PARAMS']) ? $this->request['PARAMS'] : []
		);
		$component->setEntityID($payment->getId());
		$component->setPayment($payment);
		return $component->prepareEntityData();
	}

	/**
	 * @param $formData
	 * @return \Bitrix\Crm\Order\Payment
	 */
	protected function buildPayment($formData)
	{
		$builderSettings = new \Bitrix\Sale\Helpers\Order\Builder\SettingsContainer([]);
		$orderBuilder = new \Bitrix\Crm\Order\OrderBuilderCrm($builderSettings);
		$director = new \Bitrix\Sale\Helpers\Order\Builder\Director;
		$payment = $director->getUpdatedPayment($orderBuilder, $formData);

		if(!empty($orderBuilder->getErrorsContainer()->getErrors()))
		{
			$this->addErrors($orderBuilder->getErrorsContainer()->getErrors());
		}

		return $payment;
	}

	protected function deleteAction()
	{
		$id = (int)$this->request['ACTION_ENTITY_ID'] > 0 ? (int)$this->request['ACTION_ENTITY_ID'] : 0;

		if($id <= 0)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_P_NOT_FOUND'));
			return;
		}

		if(!Permissions\Payment::checkDeletePermission($id, $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_ORDER_P_ACCESS_DENIED'));
			return;
		}
		$paymentRaw = \Bitrix\Crm\Order\Payment::getList([
			'filter' => ['=ID' => $id],
			'select' => ['ORDER_ID'],
			'limit' => 1
		]);
		$paymentData = $paymentRaw->fetch();
		$order = \Bitrix\Crm\Order\Order::load($paymentData['ORDER_ID']);
		if(!$order)
		{
			$this->addError(Loc::getMessage('CRM_ORDER_P_NOT_FOUND'));
			return;
		}

		$paymentCollection = $order->getPaymentCollection();
		$payment = $paymentCollection->getItemById($id);
		$res = $payment->delete();
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
}

CUtil::JSPostUnescape();
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
