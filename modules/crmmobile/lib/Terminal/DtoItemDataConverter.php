<?php

namespace Bitrix\CrmMobile\Terminal;

use Bitrix\Crm\Order\Payment;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Order\Permissions;
use Bitrix\Crm\Terminal\OrderProperty;
use Bitrix\SalesCenter\Component\PaymentSlip;

LocHelper::loadMessages();

class DtoItemDataConverter
{
	public static function convert(Payment $payment): DtoItemData
	{
		$order = $payment->getOrder();

		/** @var DateTime|null $datePaid */
		$datePaid = $payment->getField('DATE_PAID');

		/** @var DateTime|null $date */
		$date = $payment->getField('DATE_BILL');

		$itemData = new DtoItemData([
			'id' => $payment->getId(),
			'accountNumber' => $payment->getField('ACCOUNT_NUMBER'),
			'accessCode' => $order ? $order->getHash() : null,
			'name' => Loc::getMessage(
				'M_CRM_TL_PAYMENT_TITLE',
				[
					'#NUMBER#' => $payment->getField('ACCOUNT_NUMBER'),
				]
			),
			'date' => $date ? $date->getTimestamp() : null,
			'phoneNumber' => OrderProperty::getTerminalPhoneValue($order),
			'sum' => $payment->getSum(),
			'currency' => $payment->getField('CURRENCY'),
			'companyId' => self::getCompanyId($payment),
			'contactIds' => self::getContactIds($payment),
			'datePaid' => $datePaid ? $datePaid->getTimestamp() : null,
			'isPaid' => $payment->isPaid(),
			'paymentSystemId' => $payment->getPaymentSystemId(),
			'paymentSystemName' => $payment->getPaymentSystemName(),
			'slipLink' =>
				Loader::includeModule('salescenter')
					? PaymentSlip::getLink($payment->getId())
					: ''
			,
			'permissions' => self::getPermissions($payment),
			'paymentSystems' => [],
			'fields' => [],
		]);

		return $itemData;
	}

	private static function getPermissions(Payment $payment): array
	{
		return [
			'delete' => Permissions\Payment::checkDeletePermission($payment->getId()),
		];
	}

	private static function getCompanyId(Payment $payment): ?int
	{
		$contactCompanyCollection = $payment->getOrder()->getContactCompanyCollection();
		if (is_null($contactCompanyCollection))
		{
			return null;
		}

		$company = $contactCompanyCollection->getCompany();
		if (is_null($company))
		{
			return null;
		}

		return $company->getField('ENTITY_ID');
	}

	private static function getContactIds(Payment $payment): array
	{
		$contactCompanyCollection = $payment->getOrder()->getContactCompanyCollection();
		if (is_null($contactCompanyCollection))
		{
			return [];
		}

		$result = [];

		$contacts = $contactCompanyCollection->getContacts();
		foreach ($contacts as $contact)
		{
			$result[] = $contact->getField('ENTITY_ID');
		}

		return $result;
	}
}
