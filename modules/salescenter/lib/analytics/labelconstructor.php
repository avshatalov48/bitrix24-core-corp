<?php

namespace Bitrix\Salescenter\Analytics;

use Bitrix\Crm\Order;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Sale;
use Bitrix\Sale\Label\EntityLabelService;
use Bitrix\Salescenter\Analytics\Dictionary\SectionDictionary;
use Bitrix\Salescenter\Analytics\Dictionary\SubSectionDictionary;

/**
 * Class LabelConstructor
 * @package Bitrix\Salescenter\Analytics
 */
class LabelConstructor
{
	public function getAnalyticsEventForPayment(string $event, Sale\Payment $payment): AnalyticsEvent
	{
		$analyticsEvent = new AnalyticsEvent($event, 'crm', 'payments');
		$sectionLabel = $this->getSectionLabelForPayment($payment);
		$analyticsEvent->setSection($sectionLabel);
		$subSectionLabel = $this->getSubSectionLabelForPayment($payment);
		$analyticsEvent->setSubSection($subSectionLabel);
		$typeLabel = $this->getTypeLabelForPayment($payment);
		$analyticsEvent->setType($typeLabel);

		return $analyticsEvent;
	}

	public function getSectionLabelForPayment(Sale\Payment $payment): string
	{
		/** @var EntityLabelService $entityLabelService */
		$entityLabelService = ServiceLocator::getInstance()->get('sale.entityLabel');
		$label = $entityLabelService->getLabelForEntity($payment, 'section');

		return $label ? $label->getValue() : SectionDictionary::CRM->value;
	}

	public function getSubSectionLabelForPayment(Sale\Payment $payment): string
	{
		/** @var EntityLabelService $entityLabelService */
		$entityLabelService = ServiceLocator::getInstance()->get('sale.entityLabel');
		$label = $entityLabelService->getLabelForEntity($payment, 'subSection');

		return $label ? $label->getValue() : SubSectionDictionary::WEB->value;
	}

	public function getTypeLabelForPayment(Sale\Payment $payment): string
	{
		$isTerminal = Container::getInstance()->getTerminalPaymentService()->isTerminalPayment($payment->getId());
		if ($isTerminal)
		{
			return 'terminal_payment';
		}

		$shipments = $payment->getPayableItemCollection()->getShipments();
		if ($shipments->count() > 0)
		{
			return 'delivery_payment';
		}

		return 'payment';
	}

	/**
	 * @param Sale\Order $order
	 * @return string
	 * @deprecated for old analytics
	 */
	public function getContextLabel(Sale\Order $order): string
	{
		/** @var Sale\TradeBindingEntity $item */
		foreach ($order->getTradeBindingCollection() as $item)
		{
			$platform = $item->getTradePlatform();
			if ($platform)
			{
				return $platform->getAnalyticCode();
			}
		}

		if (
			$order->getField('ID_1C')
			&& $order->getField('EXTERNAL_ORDER') === 'Y'
		)
		{
			return '1c';
		}

		return '';
	}

	/**
	 * @param Sale\Payment $payment
	 * @return string
	 */
	public function getPaySystemTag(Sale\Payment $payment): string
	{
		$service = $payment->getPaySystem();

		if ($service === null)
		{
			return '';
		}

		$tag = $service->getField('ACTION_FILE');
		if ($service->getField('PS_MODE'))
		{
			$tag .= ':'.$service->getField('PS_MODE');
		}

		return $tag;
	}

	/**
	 * @param Order\Order $order
	 * @return string
	 * @deprecated for old analytics
	 */
	public function getChannelLabel(Order\Order $order): string
	{
		$manager = new Order\SendingChannels\Manager();
		$data = $manager->getChannelList($order);

		return $data['CHANNEL_TYPE'] ?? '';
	}

	/**
	 * @param Order\Order $order
	 * @return string
	 */
	public function getChannelNameLabel(Order\Order $order): string
	{
		$manager = new Order\SendingChannels\Manager();
		$data = $manager->getChannelList($order);

		return $data['CHANNEL_NAME'] ?? '';
	}
}