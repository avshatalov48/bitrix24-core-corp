<?php

namespace Bitrix\Sale\PaySystem;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\Type;
use Bitrix\Sale\Internals\PaySystemRestHandlersTable;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;

/**
 * Class RestHandler
 * @package Sale\Handlers\PaySystem
 */
class RestHandler extends PaySystem\ServiceHandler
{
	private $handlerSettings = array();

	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return ServiceResult
	 */
	public function initiatePay(Payment $payment, Request $request = null)
	{
		$result = $this->showTemplate($payment, "template");
		if (!$result->isSuccess())
		{
			$result = new ServiceResult();
			$template = $this->getDefaultTemplate($payment, $request);
			if ($this->initiateMode === static::STREAM)
			{
				echo $template;
			}
			else
			{
				$result->setTemplate($template);
			}
		}

		return $result;
	}

	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return string
	 */
	private function getDefaultTemplate(Payment $payment, Request $request = null)
	{
		$settings = $this->getHandlerSettings();
		$formSettings = $settings['FORM_DATA'];

		$params = $this->getParamsBusValue($payment);

		$template = '<form action="'.htmlspecialcharsbx($formSettings['ACTION_URI']).'" method="'.htmlspecialcharsbx($formSettings['METHOD']).'">';
		foreach ($formSettings['PARAMS'] as $key => $value)
		{
			$template .= '<input type="hidden" name="'.htmlspecialcharsbx($key).'" value="'.htmlspecialcharsbx($params[$value]).'">';
		}
		$template .= '<input type="hidden" name="BX_PAYSYSTEM_ID" value="'.$this->service->getField('ID').'">';
		$template .= '<input name="button" value="'.Loc::getMessage('SALE_HANDLERS_REST_HANDLER_BUTTON_PAID').'" type="submit">';
		$template .= '</form>';

		return $template;
	}

	/**
	 * @return array
	 */
	public function getCurrencyList()
	{
		$settings = $this->getHandlerSettings();
		return $settings['CURRENCY'];
	}

	/**
	 * @return array
	 */
	public function getDescription()
	{
		$settings = $this->getHandlerSettings();

		return array(
			'NAME' => $settings['NAME'],
			'CODES' => $settings['CODES'] ?: []
		);
	}

	/**
	 * @return array
	 */
	private function getHandlerSettings()
	{
		if (!$this->handlerSettings)
		{
			$handler = $this->service->getField('ACTION_FILE');
			$dbRes = PaySystemRestHandlersTable::getList(array('filter' => array('CODE' => $handler)));
			$data = $dbRes->fetch();
			if ($data)
			{
				$this->handlerSettings = $data['SETTINGS'];
			}
		}

		return $this->handlerSettings;
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return ServiceResult
	 */
	public function processRequest(Payment $payment, Request $request)
	{
		$result = new ServiceResult();

		$result->setPsData($this->getPsData($request));
		$result->setOperationType(ServiceResult::MONEY_COMING);

		return $result;
	}

	/**
	 * @param Request $request
	 * @return array
	 */
	private function getPsData(Request $request): array
	{
		$psData = [
			'PS_STATUS' => 'Y',
			'PS_STATUS_CODE' => 'Y',
			'PS_RESPONSE_DATE' => new Type\DateTime(),
			'PAY_VOUCHER_DATE' => new Type\Date(),
		];

		if ($psInvoiceId = $request->get('PS_INVOICE_ID'))
		{
			$psData['PS_INVOICE_ID'] = $psInvoiceId;
		}

		if ($psStatusCode = $request->get('PS_STATUS_CODE'))
		{
			$psData['PS_STATUS_CODE'] = $psStatusCode;
		}

		if ($psStatusDescription = $request->get('PS_STATUS_DESCRIPTION'))
		{
			$psData['PS_STATUS_DESCRIPTION'] = $psStatusDescription;
		}

		if ($psStatusMessage = $request->get('PS_STATUS_MESSAGE'))
		{
			$psData['PS_STATUS_MESSAGE'] = $psStatusMessage;
		}

		if ($psSum = $request->get('PS_SUM'))
		{
			$psData['PS_SUM'] = $psSum;
		}

		if ($psCurrency = $request->get('PS_CURRENCY'))
		{
			$psData['PS_CURRENCY'] = $psCurrency;
		}

		if ($psRecurringToken = $request->get('PS_RECURRING_TOKEN'))
		{
			$psData['PS_RECURRING_TOKEN'] = $psRecurringToken;
		}

		return $psData;
	}

	/**
	 * @param Request $request
	 * @return mixed
	 */
	public function getPaymentIdFromRequest(Request $request)
	{
		return $request->get('PAYMENT_ID');
	}
}