<?php

namespace Bitrix\CrmMobile\Integration\Sale\Payment;

use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Service\Container;
use Bitrix\CrmMobile\Terminal;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Order\Permissions;
use Bitrix\Crm\Terminal\OrderProperty;
use Bitrix\Sale\Payment;
use Bitrix\SalesCenter\Component\PaymentSlip;
use Bitrix\Crm\Terminal\Config\TerminalPaysystemManager;
use Bitrix\SalesCenter\Integration\LandingManager;

Loc::loadMessages(__DIR__ . '/Payment.php');

class DtoItemDataConverter
{
	public static function convert(Payment $payment): DtoItemData
	{
		/** @var Order $order */
		$order = $payment->getOrder();

		/** @var DateTime|null $datePaid */
		$datePaid = $payment->getField('DATE_PAID');

		/** @var DateTime|null $date */
		$date = $payment->getField('DATE_BILL');

		$isTerminalPayment = Container::getInstance()
			->getTerminalPaymentService()
			->isTerminalPayment($payment->getId())
		;

		if (Loader::includeModule('salescenter'))
		{
			$connectedSiteId = LandingManager::getInstance()->getConnectedSiteId();
			$isPhoneConfirmed = LandingManager::getInstance()->isPhoneConfirmed();
		}

		$itemData = DtoItemData::make([
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

			'hasEntityBinding' => (bool)$payment->getOrder()->getEntityBinding(),
			'productsCnt' => $payment->getPayableItemCollection()->getBasketItems()->count(),

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
			'responsibleId' => $payment->getField('RESPONSIBLE_ID'),
			'permissions' => self::getPermissions($payment),
			'isTerminalPayment' => $isTerminalPayment,
			'connectedSiteId' => $connectedSiteId ?? 0,
			'terminalPaymentSystems' =>
				$isTerminalPayment
					? Terminal\PaymentSystemRepository::getByPayment($payment)
					: []
			,
			'paymentSystems' => [],
			'isPhoneConfirmed' => $isPhoneConfirmed ?? true,
			'fields' => [],
			'isLinkPaymentEnabled' => TerminalPaysystemManager::getInstance()->getConfig()->isLinkPaymentEnabled(),
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
