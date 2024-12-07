<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

use Bitrix\Main\Loader;
use Bitrix\Crm\Order\Permissions;
use Bitrix\Main\Localization\Loc;

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

		$fieldValues = array_intersect_key($this->request, array_flip(
			['PAY_RETURN_NUM', 'PAY_RETURN_DATE', 'PAY_RETURN_COMMENT', 'PAY_VOUCHER_NUM', 'PAY_VOUCHER_DATE', 'IS_RETURN'])
		);

		if($id > 0)
		{
			if(!Permissions\Order::checkUpdatePermission($id, $this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
				return;
			}

			$res = \Bitrix\Crm\Order\Payment::getList(['filter'=>['=ID' => $id]]);

			if(!($paymentFields = $res->fetch()))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_PAYMENT_NOT_FOUND'));
				return;
			}
			$order = \Bitrix\Crm\Order\Order::load($paymentFields['ORDER_ID']);
			if (!$order)
			{
				$this->addError(Loc::getMessage('CRM_ORDER_PAYMENT_NOT_FOUND'));
				return;
			}
			$paymentCollection = $order->getPaymentCollection();
			$payment = $paymentCollection->getItemById($id);

			if (isset($fieldValues['PAY_RETURN_DATE']) && $fieldValues['PAY_RETURN_DATE'] <> '')
			{
				$fieldValues['PAY_RETURN_DATE'] = new Bitrix\Main\Type\Date($fieldValues['PAY_RETURN_DATE']);
			}

			if (isset($fieldValues['PAY_VOUCHER_DATE']) && $fieldValues['PAY_VOUCHER_DATE'] <> '')
			{
				$fieldValues['PAY_VOUCHER_DATE'] = new Bitrix\Main\Type\Date($fieldValues['PAY_VOUCHER_DATE']);
			}

			$payment->setFields($fieldValues);

			$result = $order->save();
			if(!$result->isSuccess())
			{
				$this->addErrors($result->getErrors());
				return;
			}
			if ($result->hasWarnings())
			{
				$this->addErrors($result->getWarnings());
				return;
			}
		}
		else
		{
			if(!Permissions\Order::checkCreatePermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_ORDER_ACCESS_DENIED'));
				return;
			}
		}

		$this->addData(['ENTITY_ID' => $id, 'ENTITY_DATA' => $fieldValues]);
	}

	protected function buildPayment($formData)
	{
		$builderSettings = new \Bitrix\Sale\Helpers\Order\Builder\SettingsContainer([]);
		$orderBuilder = new \Bitrix\Crm\Order\OrderBuilderCrm($builderSettings);
		$director = new \Bitrix\Sale\Helpers\Order\Builder\Director;
		$payment = $director->getUpdatedPayment($orderBuilder, $formData);

		if(!$payment)
		{
			$this->addErrors($orderBuilder->getErrorsContainer()->getErrors());
		}

		return $payment;
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
