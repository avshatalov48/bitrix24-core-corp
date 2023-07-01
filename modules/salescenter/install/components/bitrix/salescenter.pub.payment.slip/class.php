<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Sale\Repository\PaymentRepository;
use Bitrix\SalesCenter\Integration\CrmManager;
use Bitrix\SalesCenter\Integration\LocationManager;
use Bitrix\SalesCenter\Component\PaymentSlip;

final class PubPaymentSlipComponent extends CBitrixComponent
{
	private ErrorCollection $errorCollection;

	public function executeComponent()
	{
		Loc::setCurrentLang(\Bitrix\SalesCenter\Component\PaymentSlip::getZone());
		Loc::loadMessages(__FILE__);

		if (
			\Bitrix\Main\Loader::includeModule('sale')
			&& \Bitrix\Main\Loader::includeModule('salescenter')
			&& \Bitrix\Main\Loader::includeModule('crm')
		)
		{
			$this->fillComponentData();
		}
		else
		{
			$this->arResult['ERROR_OCCURRED'] = true;
		}

		if ($this->arResult['ERROR_OCCURRED'] || $this->arResult['SLIP_NOT_FOUND_ERROR'])
		{
			$this->arParams['ERROR_MSG'] = \Bitrix\Main\Localization\Loc::getMessage('SALESCENTER_PS_SLIP_CANT_FIND_SLIP_ERROR_MSG');
			$this->includeComponentTemplate('errorPage');
			return;
		}

		$this->includeComponentTemplate();
	}

	private function fillComponentData(): void
	{
		$paymentDataResult = $this->getPreparedPaymentData();
		if ($paymentDataResult->isSuccess())
		{
			$this->arResult['PAYMENT_DATA'] = $paymentDataResult->getData();
		}
		else
		{
			$this->arResult['SLIP_NOT_FOUND_ERROR'] = true;
		}

		$this->arResult['COMPANY_DATA'] = $this->getPreparedCompanyData();
		$this->arResult['PAYMENT_SLIP_WARNING'] = PaymentSlip::getRegionWarning();
	}

	private function getPreparedCompanyData(): array
	{
		$company = \CCrmCompany::GetByID(EntityLink::getDefaultMyCompanyId(), false);

		return [
			'COMPANY_TITLE' => $company['TITLE'] ?? '',
			'COMPANY_ADDRESS' => $this->getCompanyAddress(),
			'COMPANY_NUMBER' => CrmManager::getPublishedCompanyPhone()['VALUE'] ?? '',
		];
	}

	private function getCompanyAddress(): string
	{
		if (!\Bitrix\Main\Loader::includeModule('location'))
		{
			return '';
		}

		$requisiteInstance = \Bitrix\Crm\EntityRequisite::getSingleInstance();
		if ($requisiteInstance)
		{
			$requisiteData = $requisiteInstance->getList(
				[
					'select' => ['ID'],
					'filter' => [
						'=ENTITY_TYPE_ID' => CCrmOwnerType::Company,
						'=ENTITY_ID' => (int)EntityLink::getDefaultMyCompanyId(),
					],
				]
			)->fetch();
		}

		if (empty($requisiteData))
		{
			return '';
		}

		$addresses = \Bitrix\Crm\AddressTable::getList(
			[
				'filter' => [
					'ENTITY_ID' => (int)$requisiteData['ID'],
					'ENTITY_TYPE_ID' => CCrmOwnerType::Requisite,
					'>LOC_ADDR_ID' => 0,
					'TYPE_ID' => [\Bitrix\Crm\EntityAddressType::Primary, \Bitrix\Crm\EntityAddressType::Registered]
				],
			]
		)->fetchAll();

		uasort(
			$addresses,
			static function ($firstAddress, $secondAddress) {
				if ($firstAddress['TYPE_ID'] === $secondAddress['TYPE_ID'])
				{
					return 0;
				}

				return $firstAddress['TYPE_ID'] === \Bitrix\Crm\EntityAddressType::Primary ? -1 : 1;
			}
		);

		foreach ($addresses as $addressData)
		{
			if (!isset($addressData['LOC_ADDR_ID']))
			{
				continue;
			}

			$address = \Bitrix\Location\Entity\Address::load($addressData['LOC_ADDR_ID']);
			if (!$address)
			{
				continue;
			}

			return $address->toString(
				\Bitrix\Location\Service\FormatService::getInstance()->findDefault(LANGUAGE_ID),
				\Bitrix\Location\Entity\Address\Converter\StringConverter::STRATEGY_TYPE_FIELD_TYPE,
				\Bitrix\Location\Entity\Address\Converter\StringConverter::CONTENT_TYPE_TEXT,
			);
		}

		return '';
	}

	private function getPreparedPaymentData(): \Bitrix\Main\Result
	{
		$result = new \Bitrix\Main\Result();

		if (isset($this->arParams['SIGNED_PAYMENT_ID']) && is_string($this->arParams['SIGNED_PAYMENT_ID']))
		{
			$paymentId = PaymentSlip::unsignPaymentId($this->arParams['SIGNED_PAYMENT_ID']);
		}

		if (!isset($paymentId))
		{
			return $result->addError(new Error("Payment not found"));
		}

		$payment = PaymentRepository::getInstance()->getById($paymentId);
		if (!$payment || !$payment->isPaid() || $payment->isReturn())
		{
			$result->addError(new Error('Payment not found'));

			return $result;
		}

		$paymentData  = [];

		if (CrmManager::getInstance()->isPaymentFromTerminal($payment))
		{
			$paymentData['TRANSACTION_ID'] = $payment->getField('PS_INVOICE_ID');
		}
		else
		{
			$result->addError(new Error('Payment not from terminal'));

			return $result;
		}

		$paymentSum = CurrencyFormatNumber($payment->getSum(), $payment->getOrder()->getCurrency());
		$paymentCode = $payment->getOrder()->getCurrency();
		$paymentData['SUM'] = "{$paymentSum} {$paymentCode}";

		$paymentData['PS_NAME'] = $payment->getPaymentSystemName();

		/** @var \Bitrix\Main\Type\DateTime $paidDate */
		$paidDate = $payment->getField('DATE_PAID');
		if ($paidDate !== null)
		{
			$paymentData['DATE'] = $paidDate->disableUserTime()->toString() . ' (' . $this->getGMTOffset() . ')';
		}
		else
		{
			$result->addError(new Error('Payment paid date not found'));

			return $result;
		}

		$result->setData($paymentData);

		return $result;
	}

	private function getGMTOffset(): string
	{
		$serverGMTOffset = (int)date('Z');
		return ('GMT' . ($serverGMTOffset >= 0 ? '+' : '-') . floor($serverGMTOffset / 3600));
	}
}
