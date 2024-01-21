<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Terminal\Actions\App;

use Bitrix\Crm\Service\Sale\Terminal\CreatePaymentOptions;
use Bitrix\CrmMobile\Integration\Sale\Payment\LocHelper;
use Bitrix\Sale\Payment;
use Bitrix\Crm;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Integration\Sale\Payment\GetPaymentQuery;

LocHelper::loadMessages();

class CreatePaymentAction extends Action
{
	final public function run(
		float $sum,
		string $currency,
		string $phoneNumber = null,
		?array $client = null,
		?string $clientName = null
	): ?array
	{
		$paymentService = Crm\Service\Container::getInstance()->getTerminalPaymentService();

		$createPaymentOptions = [
			'currency' => $currency,
			'phoneNumber' => $phoneNumber,
			'clientName' => $clientName,
		];
		if (!empty($client))
		{
			$createPaymentOptions['client'] = new Crm\ItemIdentifier((int)$client['entityTypeId'], (int)$client['id']);
		}

		$createPaymentResult = $paymentService->createByAmount(
			$sum,
			CreatePaymentOptions::createFromArray($createPaymentOptions)
		);
		if (!$createPaymentResult->isSuccess())
		{
			$this->addErrors($createPaymentResult->getErrors());

			return null;
		}

		/** @var Payment $payment */
		$payment = $createPaymentResult->getData()['payment'];

		return [
			'payment' => (new GetPaymentQuery($payment))->execute(),
		];
	}
}
