<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PaymentCollection;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PriceMaths;

/**
 * Class LiqPayHandler
 * @package Sale\Handlers\PaySystem
 */
class LiqPayHandler extends PaySystem\ServiceHandler
{
	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotImplementedException
	 */
	public function initiatePay(Payment $payment, Request $request = null)
	{
		$busValues = $this->getParamsBusValue($payment);
		$busValues['LIQPAY_PATH_TO_RESULT_URL'] = $this->getPathResultUrl($payment);

		$xml = "<request>
			<version>1.2</version>
			<result_url>".$busValues['LIQPAY_PATH_TO_RESULT_URL']."</result_url>
			<server_url>".$busValues['LIQPAY_PATH_TO_SERVER_URL']."</server_url>
			<merchant_id>".$busValues['LIQPAY_MERCHANT_ID']."</merchant_id>
			<order_id>PAYMENT_".$busValues['PAYMENT_ID']."</order_id>
			<amount>".$busValues["PAYMENT_SHOULD_PAY"]."</amount>
			<currency>".$busValues['PAYMENT_CURRENCY']."</currency>
			<description>".$this->getPaymentDescription($payment)."</description>
			<default_phone>".$busValues['BUYER_PERSON_PHONE']."</default_phone>
			<pay_way>".$busValues['LIQPAY_PAY_METHOD']."</pay_way>
			</request>";

		$signature = base64_encode(sha1($busValues['LIQPAY_SIGN'].$xml.$busValues['LIQPAY_SIGN'], 1));

		$params = array(
			'URL' => $this->getUrl($payment, 'pay'),
			'OPERATION_XML' => base64_encode($xml),
			'SIGNATURE' => $signature,
		);

		if ($busValues['PAYMENT_CURRENCY'] == "RUB")
			$params['PAYMENT_CURRENCY'] = "RUR";

		$this->setExtraParams($params);

		return $this->showTemplate($payment, "template");
	}

	/**
	 * @return array
	 */
	public static function getIndicativeFields()
	{
		return array('operation_xml', 'signature');
	}

	/**
	 * @param Payment $payment
	 * @param $request
	 * @return bool
	 */
	private function isCorrectHash(Payment $payment, Request $request)
	{
		if ($request->get('operation_xml') !== null)
		{
			$sign = $this->getBusinessValue($payment, 'LIQPAY_SIGN');
			if ($sign)
			{
				$hash = base64_encode(sha1($sign.$this->getOperationXml($request).$sign, 1));
				return $request->get('signature') == $hash;
			}
		}

		return false;
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return bool
	 */
	private function isCorrectSum(Payment $payment, Request $request)
	{
		$sum = $this->getValueByTag($this->getOperationXml($request), 'amount');
		$paymentSum = $this->getBusinessValue($payment, 'PAYMENT_SHOULD_PAY');

		return PriceMaths::roundPrecision($paymentSum) === PriceMaths::roundPrecision($sum);
	}

	/**
	 * @param Request $request
	 * @return mixed
	 */
	public function getPaymentIdFromRequest(Request $request)
	{
		$orderId = $this->getValueByTag($this->getOperationXml($request), 'order_id');
		return str_replace("PAYMENT_", "", $orderId);
	}

	/**
	 * @return mixed
	 */
	protected function getUrlList()
	{
		return array(
			'pay' => array(
				self::ACTIVE_URL => 'https://www.liqpay.ua/?do=clickNbuy'
			)
		);
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 */
	public function processRequest(Payment $payment, Request $request)
	{
		$result = new PaySystem\ServiceResult();

		if ($request->get('signature') === null || $request->get('operation_xml') === null)
		{
			$errorMessage = Loc::getMessage('SALE_HPS_LIQPAY_POST_ERROR');
			$result->addError(new Error($errorMessage));

			PaySystem\Logger::addError('LiqPay: processRequest: '.$errorMessage);
		}

		$status = $this->getValueByTag($this->getOperationXml($request), 'status');

		if ($this->isCorrectHash($payment, $request))
		{
			if ($status === 'success' || $status === 'wait_reserve')
			{
				return $this->processNoticeAction($payment, $request);
			}

			if ($status === 'wait_secure')
			{
				return new PaySystem\ServiceResult();
			}
		}
		else
		{
			PaySystem\Logger::addError('LiqPay: processRequest: Incorrect hash');
			$result->addError(new Error('Incorrect hash'));
		}

		return $result;
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 */
	private function processNoticeAction(Payment $payment, Request $request)
	{
		$result = new PaySystem\ServiceResult();

		$response = $this->getOperationXml($request);

		$description = 'sender phone: '.$this->getValueByTag($response, 'sender_phone').'; ';
		$description .= 'amount: '.$this->getValueByTag($response, 'amount').'; ';
		$description .= 'currency: '.$this->getValueByTag($response, 'currency').'; ';

		$statusMessage = 'status: '.$this->getValueByTag($response, 'status').'; ';
		$statusMessage .= 'transaction_id: '.$this->getValueByTag($response, 'transaction_id').'; ';
		$statusMessage .= 'pay_way: '.$this->getValueByTag($response, 'pay_way').'; ';
		$statusMessage .= 'payment_id: '.$this->getValueByTag($response, 'order_id').'; ';


		$fields = array(
			"PS_STATUS" => "Y",
			"PS_STATUS_CODE" => mb_substr($this->getValueByTag($response, 'status'), 0, 5),
			"PS_STATUS_DESCRIPTION" => $description,
			"PS_STATUS_MESSAGE" => $statusMessage,
			"PS_SUM" => $this->getValueByTag($response, 'amount'),
			"PS_CURRENCY" => $this->getValueByTag($response, 'currency'),
			"PS_RESPONSE_DATE" => new DateTime(),
		);

		$result->setPsData($fields);

		if ($this->isCorrectSum($payment, $request))
		{
			$result->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
		}
		else
		{
			PaySystem\Logger::addError('LiqPay: processNoticeAction: Incorrect sum');
			$result->addError(new Error('Incorrect sum'));
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getCurrencyList()
	{
		return ['RUB', 'USD', 'EUR', 'UAH'];
	}

	/**
	 * @param $string
	 * @param $tag
	 * @return string
	 */
	private function getValueByTag($string, $tag)
	{
		$string = str_replace("\n", "", str_replace("\r", "", $string));
		$open = '<'.$tag.'>';
		$close = '</'.$tag;
		$start = mb_strpos($string, $open) + mb_strlen($open);
		$end = mb_strpos($string, $close);

		return mb_substr($string, $start, ($end - $start));
	}

	/**
	 * @param Request $request
	 * @return string
	 */
	private function getOperationXml(Request $request)
	{
		static $operationXml = '';

		if ($operationXml === '')
			$operationXml = base64_decode($request->get('operation_xml'));

		return $operationXml;
	}

	/**
	 * @param Payment $payment
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotImplementedException
	 */
	private function getPaymentDescription(Payment $payment)
	{
		/** @var PaymentCollection $collection */
		$collection = $payment->getCollection();
		$order = $collection->getOrder();
		$userEmail = $order->getPropertyCollection()->getUserEmail();

		return str_replace(
			[
				'#PAYMENT_NUMBER#',
				'#ORDER_NUMBER#',
				'#PAYMENT_ID#',
				'#ORDER_ID#',
				'#USER_EMAIL#'
			],
			[
				$payment->getField('ACCOUNT_NUMBER'),
				$order->getField('ACCOUNT_NUMBER'),
				$payment->getId(),
				$order->getId(),
				($userEmail) ? $userEmail->getValue() : ''
			],
			$this->getBusinessValue($payment, 'LIQPAY_PAYMENT_DESCRIPTION')
		);
	}

	/**
	 * @param Payment $payment
	 * @return mixed|string
	 */
	private function getPathResultUrl(Payment $payment)
	{
		$url = $this->getBusinessValue($payment, 'LIQPAY_PATH_TO_RESULT_URL') ?: $this->service->getContext()->getUrl();

		return str_replace('&', '&amp;', $url);
	}
}