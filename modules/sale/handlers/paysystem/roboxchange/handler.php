<?php
namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Error;
use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PriceMaths;

Loc::loadMessages(__FILE__);

/**
 * Class RoboxchangeHandler
 * @package Sale\Handlers\PaySystem
 */
class RoboxchangeHandler extends PaySystem\ServiceHandler
{
	public const TEMPLATE_TYPE_CHECKOUT = 'checkout';
	public const TEMPLATE_TYPE_IFRAME = 'iframe';

	protected const DEFAULT_TEMPLATE_NAME = 'template';

	private const ANALYTICS_LABEL_VALUE = 'api_1c-bitrix';

	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function initiatePay(Payment $payment, Request $request = null)
	{
		$params = [
			'URL' => $this->getUrl($payment, 'pay'),
			'PS_MODE' => $this->service->getField('PS_MODE'),
			'SIGNATURE_VALUE' => $this->getSignatureValue($payment),
			'BX_PAYSYSTEM_CODE' => $payment->getPaymentSystemId(),
			'ROBOXCHANGE_ORDERDESCR' => $this->getOrderDescription($payment),
			'PAYMENT_ID' => $this->getBusinessValue($payment, 'PAYMENT_ID'),
			'SUM' => PriceMaths::roundPrecision($payment->getSum()),
			'CURRENCY' => $payment->getField('CURRENCY'),
			'ADDITIONAL_USER_FIELDS' => $this->getAdditionalUserFields($payment),
		];
		$this->setExtraParams($params);

		return $this->showTemplate($payment, $this->getTemplateName($payment));
	}

	/**
	 * @param Payment $payment
	 * @return array
	 */
	private function getAdditionalUserFields(Payment $payment): array
	{
		$additionalUserFields = [
			'SHP_BX_PAYSYSTEM_CODE' => $payment->getPaymentSystemId(),
			'SHP_HANDLER' => 'ROBOXCHANGE',
			'SHP_PARTNER' => self::ANALYTICS_LABEL_VALUE,
		];
		ksort($additionalUserFields);

		return $additionalUserFields;
	}

	/**
	 * @param Payment $payment
	 * @return string
	 */
	private function getSignatureValue(Payment $payment): string
	{
		$code = 'ROBOXCHANGE_SHOPPASSWORD';
		if ($this->isTestMode($payment))
		{
			$code .= '_TEST';
		}

		$shopPassword1 = (string)$this->getBusinessValue($payment, $code);

		$signatureValue =
			$this->getBusinessValue($payment, 'ROBOXCHANGE_SHOPLOGIN') . ":"
			. (float)$payment->getSum() . ":"
			. $this->getBusinessValue($payment, 'PAYMENT_ID') . ":"
			. $shopPassword1;

		foreach ($this->getAdditionalUserFields($payment) as $fieldName => $fieldValue)
		{
			$signatureValue .= ":{$fieldName}={$fieldValue}";
		}

		return md5($signatureValue);
	}

	/**
	 * @param Payment $payment
	 * @return false|string
	 */
	private function getOrderDescription(Payment $payment)
	{
		return mb_substr($this->getBusinessValue($payment, 'ROBOXCHANGE_ORDERDESCR'), 0, 100);
	}

	/**
	 * @param Payment $payment
	 * @return string
	 */
	private function getTemplateName(Payment $payment): string
	{
		$templateType = (string)$this->getBusinessValue($payment, 'ROBOXCHANGE_TEMPLATE_TYPE');
		if (empty($templateType) || $templateType === self::TEMPLATE_TYPE_CHECKOUT)
		{
			return static::DEFAULT_TEMPLATE_NAME;
		}

		return $templateType;
	}

	/**
	 * @return array
	 */
	public static function getIndicativeFields()
	{
		return ['SHP_HANDLER' => 'ROBOXCHANGE'];
	}

	/**
	 * @param Request $request
	 * @param $paySystemId
	 * @return bool
	 */
	protected static function isMyResponseExtended(Request $request, $paySystemId)
	{
		$id = (int)$request->get('SHP_BX_PAYSYSTEM_CODE');
		return $id === (int)$paySystemId;
	}

	/**
	 * @param Payment $payment
	 * @param $request
	 * @return bool
	 */
	private function isCorrectHash(Payment $payment, Request $request): bool
	{
		$code = 'ROBOXCHANGE_SHOPPASSWORD2';
		if ($this->isTestMode($payment))
		{
			$code .= '_TEST';
		}

		$shopPassword2 = (string)$this->getBusinessValue($payment, $code);

		$hash =
			$request->get('OutSum') . ":"
			. $request->get('InvId') . ":"
			. $shopPassword2;

		foreach ($this->getAdditionalUserFields($payment) as $fieldName => $fieldValue)
		{
			$hash .= ":{$fieldName}={$fieldValue}";
		}

		return ToUpper(md5($hash)) === ToUpper($request->get('SignatureValue'));
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ObjectException
	 */
	private function isCorrectSum(Payment $payment, Request $request): bool
	{
		$sum = PriceMaths::roundPrecision($request->get('OutSum'));
		$paymentSum = PriceMaths::roundPrecision($payment->getSum());

		PaySystem\Logger::addDebugInfo(__CLASS__.": requestSum={$sum}; paymentSum={$paymentSum}");

		return $paymentSum === $sum;
	}

	/**
	 * @param Request $request
	 * @return mixed
	 */
	public function getPaymentIdFromRequest(Request $request)
	{
		return $request->get('InvId');
	}

	/**
	 * @return mixed
	 */
	protected function getUrlList()
	{
		return [
			'pay' => [
				self::ACTIVE_URL => 'https://auth.robokassa.ru/Merchant/Index.aspx'
			]
		];
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function processRequest(Payment $payment, Request $request)
	{
		$result = new PaySystem\ServiceResult();

		if ($this->isCorrectHash($payment, $request))
		{
			return $this->processNoticeAction($payment, $request);
		}

		$result->addError(new Error(Loc::getMessage('SALE_HPS_ROBOXCHANGE_INCORRECT_HASH')));

		return $result;
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ObjectException
	 */
	private function processNoticeAction(Payment $payment, Request $request): PaySystem\ServiceResult
	{
		$result = new PaySystem\ServiceResult();

		$psStatusDescription = Loc::getMessage('SALE_HPS_ROBOXCHANGE_RES_NUMBER').": ".$request->get('InvId');
		$psStatusDescription .= "; ".Loc::getMessage('SALE_HPS_ROBOXCHANGE_RES_DATEPAY').": ".date("d.m.Y H:i:s");

		if ($request->get("IncCurrLabel") !== null)
		{
			$psStatusDescription .= "; ".Loc::getMessage('SALE_HPS_ROBOXCHANGE_RES_PAY_TYPE').": ".$request->get("IncCurrLabel");
		}

		$fields = [
			"PS_INVOICE_ID" => $request->get('InvId'),
			"PS_STATUS" => "N",
			"PS_STATUS_CODE" => "-",
			"PS_STATUS_DESCRIPTION" => $psStatusDescription,
			"PS_STATUS_MESSAGE" => Loc::getMessage('SALE_HPS_ROBOXCHANGE_RES_PAYED'),
			"PS_SUM" => $request->get('OutSum'),
			"PS_CURRENCY" => $payment->getField('CURRENCY'),
			"PS_RESPONSE_DATE" => new DateTime(),
		];

		$result->setPsData($fields);

		if ($this->isCorrectSum($payment, $request))
		{
			$fields["PS_STATUS"] = 'Y';

			PaySystem\Logger::addDebugInfo(
				__CLASS__.': PS_CHANGE_STATUS_PAY='.$this->getBusinessValue($payment, 'PS_CHANGE_STATUS_PAY')
			);

			if ($this->getBusinessValue($payment, 'PS_CHANGE_STATUS_PAY') === 'Y')
			{
				$result->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
			}
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('SALE_HPS_ROBOXCHANGE_ERROR_SUM')));
		}

		return $result;
	}

	/**
	 * @param Payment|null $payment
	 * @return bool
	 */
	protected function isTestMode(Payment $payment = null)
	{
		return $this->getBusinessValue($payment, 'PS_IS_TEST') === 'Y';
	}

	/**
	 * @return array
	 */
	public function getCurrencyList()
	{
		return ['RUB'];
	}

	/**
	 * @param PaySystem\ServiceResult $result
	 * @param Request $request
	 * @return mixed|string|void
	 */
	public function sendResponse(PaySystem\ServiceResult $result, Request $request)
	{
		global $APPLICATION;
		if ($result->isResultApplied())
		{
			$APPLICATION->RestartBuffer();
			echo 'OK'.$request->get('InvId');
		}
	}

	/**
	 * @return array
	 */
	public static function getHandlerModeList()
	{
		return [
			'bank_card' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_BANKCARD_MODE'),
			'alfa_bank' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_ALFABANK_MODE'),
			'apple_pay' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_APPLEPAY_MODE'),
			'samsung_pay' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_SAMSUNGPAY_MODE'),
		];
	}
}