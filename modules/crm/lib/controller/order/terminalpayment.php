<?php

namespace Bitrix\Crm\Controller\Order;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale;

Main\Localization\Loc::loadLanguageFile(__FILE__);
Main\Loader::requireModule('sale');

class TerminalPayment extends Entity
{
	private const TERMINAL_PAYMENT_ACCESS_DENIED_ERROR_CODE = 'TERMINAL_PAYMENT_ACCESS_DENIED';

	protected function processBeforeAction(Main\Engine\Action $action)
	{
		$this->checkPermissions($action);
		if ($this->getErrors())
		{
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

	private function checkPermissions(Main\Engine\Action $action): void
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		$actionArguments = $action->getArguments();

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
						Main\Localization\Loc::getMessage('CRM_CONTROLLER_TERMINAL_PAYMENT_DOCUMENT_ACCESS_DENIED'),
						self::TERMINAL_PAYMENT_ACCESS_DENIED_ERROR_CODE
					)
				);

				return;
			}
		}
	}

	public function deleteAction(Crm\Order\Payment $payment): void
	{
		if ($payment->isPaid())
		{
			$this->addError(
				new Main\Error(
					Main\Localization\Loc::getMessage(
						'CRM_CONTROLLER_TERMINAL_PAYMENT_DOCUMENT_ERROR_DELETE',
						[
							'#ID#' => $payment->getField('ACCOUNT_NUMBER'),
						]
					)
				)
			);

			return;
		}

		$deleteResult = Crm\Order\Order::delete($payment->getOrderId());
		if (!$deleteResult->isSuccess())
		{
			$this->addErrors($deleteResult->getErrors());
		}
	}

	public function deleteListAction(Main\Type\Dictionary $paymentList): void
	{
		/** @var Crm\Order\Payment $payment */
		foreach ($paymentList as $payment)
		{
			if ($payment->isPaid())
			{
				$this->addError(
					new Main\Error(
						Main\Localization\Loc::getMessage(
							'CRM_CONTROLLER_TERMINAL_PAYMENT_DOCUMENT_ERROR_DELETE',
							[
								'#ID#' => $payment->getField('ACCOUNT_NUMBER'),
							]
						)
					)
				);

				continue;
			}

			$deleteResult = Crm\Order\Order::delete($payment->getOrderId());
			if (!$deleteResult->isSuccess())
			{
				$this->addErrors($deleteResult->getErrors());
			}
		}
	}
}
