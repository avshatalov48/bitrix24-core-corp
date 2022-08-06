<?php

namespace Bitrix\Crm\Controller\Order;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale;

Main\Localization\Loc::loadLanguageFile(__FILE__);
Main\Loader::requireModule('sale');

class Payment extends Entity
{
	private const PAYMENT_ACCESS_DENIED_ERROR_CODE = 'PAYMENT_ACCESS_DENIED';

	protected function processBeforeAction(Main\Engine\Action $action)
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		$actionArguments = $action->getArguments();
		$id = $actionArguments['payment'] ? $actionArguments['payment']->getId() : 0;

		if (!Crm\Order\Permissions\Payment::checkUpdatePermission($id, $userPermissions))
		{
			$this->addError(
				new Main\Error(
					Main\Localization\Loc::getMessage('CRM_CONTROLLER_PAYMENT_DOCUMENT_ACCESS_DENIED'),
					self::PAYMENT_ACCESS_DENIED_ERROR_CODE
				)
			);
			return false;
		}

		$this->temporarilyDisableAutomationIfNeeded();

		return parent::processBeforeAction($action);
	}

	protected function processAfterAction(Main\Engine\Action $action, $result)
	{
		$this->restoreDisabledAutomationIfNeeded();

		parent::processAfterAction($action, $result);
	}

	public function getPrimaryAutoWiredParameter()
	{
		return new Main\Engine\AutoWire\ExactParameter(
			Crm\Order\Payment::class,
			'payment',
			function($className, $id) {
				$payment = Sale\Repository\PaymentRepository::getInstance()->getById($id);

				if ($payment)
				{
					return $payment;
				}

				$this->addError(new Main\Error('payment not found'));
				return null;
			}
		);
	}

	public function setPaidAction(Crm\Order\Payment $payment, string $value): void
	{
		$order = $payment->getOrder();

		$setResult = $payment->setPaid($value);
		if ($setResult->isSuccess())
		{
			$saveOrderResult = $order->save();
			if (!$saveOrderResult->isSuccess())
			{
				$this->addErrors($saveOrderResult->getErrors());
			}
		}
		else
		{
			$this->addErrors($setResult->getErrors());
		}
	}

	public function deleteAction(Crm\Order\Payment $payment): void
	{
		$order = $payment->getOrder();

		$deleteResult = $payment->delete();
		if ($deleteResult->isSuccess())
		{
			$saveOrderResult = $order->save();
			if (!$saveOrderResult->isSuccess())
			{
				$this->addErrors($saveOrderResult->getErrors());
			}
		}
		else
		{
			$this->addErrors($deleteResult->getErrors());
		}
	}
}
