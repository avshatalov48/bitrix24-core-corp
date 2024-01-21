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

		if ($action->getName() === 'setPaid')
		{
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
		}
		elseif (in_array($action->getName(), ['delete', 'deleteList'], true))
		{
			$ids = [];

			if ($action->getName() === 'delete')
			{
				$ids[] = $actionArguments['payment'] ? $actionArguments['payment']->getId() : 0;
			}
			elseif ($action->getName() === 'deleteList')
			{
				$paymentList = $actionArguments['paymentList'] ?: [];
				/** @var Crm\Order\Payment $payment */
				foreach ($paymentList as $payment)
				{
					$ids[] = $payment->getId();
				}
			}

			foreach ($ids as $id)
			{
				if (!Crm\Order\Permissions\Payment::checkDeletePermission($id, $userPermissions))
				{
					$this->addError(
						new Main\Error(
							Main\Localization\Loc::getMessage('CRM_CONTROLLER_PAYMENT_DOCUMENT_ACCESS_DENIED'),
							self::PAYMENT_ACCESS_DENIED_ERROR_CODE
						)
					);

					return false;
				}
			}
		}

		$this->temporarilyDisableAutomationIfNeeded();

		return parent::processBeforeAction($action);
	}

	protected function processAfterAction(Main\Engine\Action $action, $result)
	{
		$this->restoreDisabledAutomationIfNeeded();

		parent::processAfterAction($action, $result);
	}

	public function getAutoWiredParameters()
	{
		$autoWiredParameters = parent::getAutoWiredParameters();

		$autoWiredParameters[] = new Main\Engine\AutoWire\ExactParameter(
			Crm\Order\Payment::class,
			'payment',
			function($className, int $id) {
				$payment = Sale\Repository\PaymentRepository::getInstance()->getById($id);

				if ($payment)
				{
					return $payment;
				}

				$this->addError(new Main\Error('payment not found'));
				return null;
			}
		);

		$autoWiredParameters[] = new Main\Engine\AutoWire\ExactParameter(
			Main\Type\Dictionary::class,
			'paymentList',
			function($className, array $ids) {
				$paymentList = [];

				foreach ($ids as $id)
				{
					$payment = Sale\Repository\PaymentRepository::getInstance()->getById($id);

					if ($payment)
					{
						$paymentList[] = $payment;
					}
					else
					{
						$this->addError(new Main\Error('payment not found'));
					}
				}

				$paymentDictionary = new Main\Type\Dictionary();
				$paymentDictionary->setValues($paymentList);

				return $paymentDictionary;
			}
		);

		return $autoWiredParameters;
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
		$this->deletePayment($payment);
	}

	public function deleteListAction(Main\Type\Dictionary $paymentList): void
	{
		/** @var Crm\Order\Payment $payment */
		foreach ($paymentList as $payment)
		{
			$this->deletePayment($payment);
		}
	}

	private function deletePayment(Crm\Order\Payment $payment): void
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
