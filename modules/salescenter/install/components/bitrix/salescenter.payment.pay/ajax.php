<?php

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\PostDecodeFilter;
use Bitrix\Sale\Controller\Action\Entity\UserConsentRequestAction;

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
	 * @param int $paysystemId
	 * @param string $returnUrl
	 * @return array
	 * @example BX.ajax.runComponentAction('bitrix:salescenter.payment.pay', 'initiatePay', { mode: 'ajax', data: { paysystemId: 1, returnUrl: '/' } });
	 */
	public function initiatePayAction($paysystemId, $returnUrl)
	{
		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
		$request->addFilter(new PostDecodeFilter);

		$params = $this->getUnsignedParameters();
		if (!$params)
		{
			$params = [];
		}
		if ($request->get('orderId'))
		{
			$params['ORDER_ID'] = (int)$request->get('orderId');
		}
		if (isset($request['paymentId']))
		{
			$params['PAYMENT_ID'] = (int)$request->get('paymentId');
		}
		if (isset($request['access']))
		{
			$params['ACCESS_CODE'] = (string)$request->get('access');
		}
		$params['PAY_SYSTEM_ID'] = (int)$paysystemId;
		$params['RETURN_URL'] = (string)$returnUrl;

		CBitrixComponent::includeComponentClass('bitrix:salescenter.payment.pay');

		$component = new SalesCenterPaymentPay();
		$component->initComponent('bitrix:salescenter.payment.pay');
		$params = $component->onPrepareComponentParams($params);
		$initiatePayResult = null;

		$result = [];

		if ($component->getErrorCollection()->isEmpty())
		{
			$initiatePayResult = $component->initiatePayAction($params);
			if ($initiatePayResult->isSuccess())
			{
				$result = [
					'html' => $initiatePayResult->getTemplate(),
					'url' => $initiatePayResult->getPaymentUrl(),
				];

				$result['status'] = 'success';
				if (empty($result['html']))
				{
					$payment = $component->getPayment();
					if ($payment)
					{
						$result['fields'] = [
							'SUM_WITH_CURRENCY' => SaleFormatCurrency($payment->getSum(), $payment->getField('CURRENCY')),
							'PAY_SYSTEM_NAME' => htmlspecialcharsbx($payment->getPaymentSystemName()),
						];
					}
				}
			}
			else
			{
				$buyerErrors = $initiatePayResult->getBuyerErrors();
				if (count($buyerErrors) > 0)
				{
					$component->getErrorCollection()->add($buyerErrors);
				}
				else
				{
					$component->getErrorCollection()->add([new Error('')]);
				}
			}
		}

		if (
			($initiatePayResult && !$initiatePayResult->isSuccess())
			|| $component->getErrorCollection()->count() > 0
		)
		{
			$result['status'] = 'error';
			$result['errors'] = [];

			/** @var \Bitrix\Main\Error $error */
			foreach ($component->getErrorCollection() as $error)
			{
				$result['errors'][$error->getCode()][] = $error->getMessage();
				$this->addError($error);
			}
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
}
