<?php

namespace Bitrix\CrmMobile\Terminal;

use Bitrix\Crm\Order\Payment;
use Bitrix\Mobile\Query;
use Bitrix\Sale\Repository\PaymentRepository;

class GetPaymentQuery extends Query
{
	private int $id;

	public function __construct(int $id)
	{
		$this->id = $id;
	}

	public function execute()
	{
		/** @var Payment $payment */
		$payment = PaymentRepository::getInstance()->getById($this->id);
		if (is_null($payment))
		{
			return null;
		}

		$itemData = DtoItemDataConverter::convert($payment);
		$itemData->paymentSystems = PaymentSystemRepository::getByPayment($payment);

		$fieldsProvider = (new EntityEditorFieldsProvider())->setItemData($itemData);

		$itemData->fields = [
			$fieldsProvider->getSumField(),
			$fieldsProvider->getPhoneField(),
			$fieldsProvider->getClientField(),
			$fieldsProvider->getStatusField(),
			$fieldsProvider->getDatePaidField(),
			$fieldsProvider->getPaymentSystemField(),
			$fieldsProvider->getSlipLinkField(),
		];

		return $itemData;
	}
}
