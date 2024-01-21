<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Terminal\Actions\Entity;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Service\Container;
use Bitrix\Sale\Payment;
use Bitrix\Crm\Order\ProductManager;
use Bitrix\Crm\Service\Sale\Terminal\CreatePaymentOptions;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Controller\Salescenter\Product2BasketItemConverter;
use Bitrix\CrmMobile\Integration\Sale\Payment\GetPaymentQuery;
use Bitrix\Sale\Helpers\Order\Builder\Converter\CatalogJSProductForm;
use Bitrix\SalesCenter\Integration\CrmManager;

class CreatePaymentAction extends Action
{
	final public function run(Item $entity, int $responsibleId, array $products): ?array
	{
		$paymentService = Container::getInstance()->getTerminalPaymentService();

		$createPaymentOptions =
			(new CreatePaymentOptions())
				->setEntity(ItemIdentifier::createByItem($entity))
				->setResponsibleId($responsibleId)
				->setCurrency($entity->getCurrencyId())
		;

		$primaryContact = $entity->getPrimaryContact();
		if ($primaryContact)
		{
			$primaryContactId = $primaryContact->getId();

			$contactPhoneNumber = CrmManager::getContactPhoneFormat($primaryContactId);
			if ($contactPhoneNumber)
			{
				$createPaymentOptions->setPhoneNumber($contactPhoneNumber);
			}
		}

		$basketItems = Product2BasketItemConverter::convert($products);
		$createPaymentResult = $paymentService->createByProducts(
			CatalogJSProductForm::convertToBuilderFormat($basketItems),
			$createPaymentOptions
		);
		if (!$createPaymentResult->isSuccess())
		{
			return null;
		}

		/** @var Payment $payment */
		$payment = $createPaymentResult->getData()['payment'];

		/** @var Order $order */
		$order = $payment->getOrder();

		$productManager = new ProductManager($entity->getEntityTypeId(), $entity->getId());
		$productManager->setOrder($order)->syncOrderProducts(
			$basketItems
		);

		return [
			'payment' => (new GetPaymentQuery($payment))->execute(),
		];
	}
}
