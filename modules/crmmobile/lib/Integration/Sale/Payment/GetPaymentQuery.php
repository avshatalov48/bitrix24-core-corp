<?php

namespace Bitrix\CrmMobile\Integration\Sale\Payment;

use Bitrix\Mobile\Query;
use Bitrix\Sale\Payment;

class GetPaymentQuery extends Query
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
			return null;
		}

		$itemData = DtoItemDataConverter::convert($this->payment);

		$fieldsProvider = (new EntityEditorFieldsProvider())->setItemData($itemData);

		$itemData->fields = [
			$fieldsProvider->getSumField(),
			$fieldsProvider->getPhoneField(),
			$fieldsProvider->getClientField(),
			$fieldsProvider->getStatusField(),
			$fieldsProvider->getDatePaidField(),
			$fieldsProvider->getPaymentSystemField(),
			$fieldsProvider->getSlipLinkField(),
			$fieldsProvider->getResponsibleField(),
		];

		return $itemData;
	}
}
