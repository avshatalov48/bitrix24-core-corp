<?php

namespace Bitrix\CrmMobile\Integration\Sale\Check;

use Bitrix\Mobile\Query;
use Bitrix\Sale\Cashbox\CheckManager;
use Bitrix\Sale\Payment;

class GetPaymentChecksQuery extends Query
{
	private ?Payment $payment;

	public function __construct(?Payment $payment)
	{
		$this->payment = $payment;
	}

	public function execute()
	{
		if (is_null($this->payment))
		{
			return [];
		}

		$result = [];

		$checksList = CheckManager::getList([
			'filter' => [
				'=PAYMENT_ID' => $this->payment->getId(),
			],
			'select' => [
				'ID',
				'DATE_CREATE',
				'TYPE',
				'LINK_PARAMS',
				'CASHBOX_ID',
			],
		]);

		while ($checkRow = $checksList->fetch())
		{
			$check = CheckManager::create($checkRow);
			if (!$check)
			{
				continue;
			}

			$result[] = DtoItemDataConverter::convert($check);
		}

		return $result;
	}
}
