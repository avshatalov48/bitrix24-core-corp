<?php

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Controller\Action\Entity\UserConsentRequestAction;
use Bitrix\Sale\Controller\Action\Entity\InitiatePayAction;
use Bitrix\Sale\PaySystem;
use Sale\Handlers\PaySystem\OrderDocumentHandler;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadLanguageFile(__DIR__ . '/class.php');

class SalescenterPaymentPayAjaxController extends Controller
{
	/** @var string */
	private $actionName;

	/** @var array */
	private $actionConfig;

	public function configureActions()
	{
		return [
			'initiatePay' => [
				'-prefilters' => [
					Authentication::class,
				],
			],
			'userConsentRequest' => [
				'-prefilters' => [
					Authentication::class,
				],
			],
		];
	}

	/**
	 * @param Action $action
	 * @return bool
	 */
	protected function processBeforeAction(Action $action)
	{
		if (!Loader::includeModule('sale'))
		{
			$this->addError(new Error(Loc::getMessage('SPP_MODULE_SALE_NOT_INSTALL')));
			return false;
		}

		$this->actionName = $action->getName();
		$this->actionConfig = $action->getConfig() ?? [];

		$arguments = $action->getArguments();
		$converter = new Converter(
			Converter::KEYS
			| Converter::RECURSIVE
			| Converter::TO_SNAKE
			| Converter::TO_SNAKE_DIGIT
			| Converter::TO_UPPER
		);
		$arguments = $converter->process($arguments);
		$action->setArguments($arguments);

		return parent::processBeforeAction($action);
	}

	/**
	 * @param array $fields
	 *   paymentId
	 *   paySystemId
	 *   accessCode
	 *   returnUrl
	 * @return ?array
	 * @example BX.ajax.runComponentAction('bitrix:salescenter.payment.pay', 'initiatePay', {mode: 'ajax', data: { fields: {...} }});
	 */
	public function initiatePayAction(array $fields): ?array
	{
		$action = new InitiatePayAction($this->actionName, $this, $this->actionConfig);

		if ($this->isPaySystemOrderDocument((int)$fields['PAY_SYSTEM_ID']))
		{
			$fields['template'] = 'template_download';
		}

		$result = $action->run($fields);

		$errors = $action->getErrors();
		if ($errors)
		{
			$this->addError(new Error('initiate pay error'));
			return null;
		}

		if (empty($result['html']) && $payment = $action->getPayment())
		{
			$result['fields'] = [
				'SUM_WITH_CURRENCY' => SaleFormatCurrency($payment->getSum(), $payment->getField('CURRENCY')),
				'PAY_SYSTEM_NAME' => htmlspecialcharsbx($payment->getPaymentSystemName()),
			];
		}

		return $result;
	}

	/**
	 * @param array $fields
	 * @return Component|null
	 * @example BX.ajax.runComponentAction('bitrix:salescenter.payment.pay', 'userConsentRequest', { mode: 'ajax', data: { fields: { ... }}});
	 */
	public function userConsentRequestAction(array $fields): ?Component
	{
		$action = new UserConsentRequestAction($this->actionName, $this, $this->actionConfig);

		$result = $action->run($fields);

		$errors = $action->getErrors();
		if ($errors)
		{
			$this->addErrors($errors);
			return null;
		}

		return $result;
	}

	/**
	 * @param int $paySystemId
	 * @return bool
	 */
	private function isPaySystemOrderDocument(int $paySystemId): bool
	{
		$handlerClassName = PaySystem\Manager::getFolderFromClassName(OrderDocumentHandler::class);

		$service = PaySystem\Manager::getObjectById($paySystemId);

		return $service && $handlerClassName === $service->getField('ACTION_FILE');
	}
}
