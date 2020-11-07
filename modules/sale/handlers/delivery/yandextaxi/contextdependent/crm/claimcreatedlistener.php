<?php

namespace Sale\Handlers\Delivery\YandexTaxi\ContextDependent\Crm;

use Bitrix\Crm\Order\Shipment;
use Bitrix\Crm\Timeline\DeliveryController;
use Bitrix\Main\Event;
use Bitrix\Sale\Delivery\Services\Taxi\CreationRequestResult;
use Sale\Handlers\Delivery\YandexTaxi\Common\ShipmentDataExtractor;
use Sale\Handlers\Delivery\YandexTaxi\RateCalculator;

/**
 * Class ClaimCreatedListener
 * @package Sale\Handlers\Delivery\YandexTaxi\ContextDependent\Crm
 * @internal
 */
final class ClaimCreatedListener
{
	/** @var ActivityManager */
	protected $activityManager;

	/** @var ShipmentDataExtractor */
	protected $extractor;

	/** @var RateCalculator */
	protected $calculator;

	/** @var BindingsMaker */
	protected $bindingsMaker;

	/**
	 * ClaimCreatedListener constructor.
	 * @param ActivityManager $activityManager
	 * @param ShipmentDataExtractor $extractor
	 * @param RateCalculator $calculator
	 * @param BindingsMaker $bindingsMaker
	 */
	public function __construct(
		ActivityManager $activityManager,
		ShipmentDataExtractor $extractor,
		RateCalculator $calculator,
		BindingsMaker $bindingsMaker
	) {
		$this->activityManager = $activityManager;
		$this->extractor = $extractor;
		$this->calculator = $calculator;
		$this->bindingsMaker = $bindingsMaker;
	}

	/**
	 * @param Event $event
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function listen(Event $event)
	{
		/** @var Shipment $shipment */
		$shipment = $event->getParameter('SHIPMENT');

		$this->createCallMessage($shipment);

		/** @var CreationRequestResult $result */
		$result = $event->getParameter('RESULT');

		$this->updateActivity(
			$shipment,
			$result
		);
	}

	/**
	 * @param Shipment $shipment
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function createCallMessage(Shipment $shipment)
	{
		$fields = [
			'DELIVERY_SYSTEM_NAME' => $this->extractor->getDeliverySystemName($shipment),
			'DELIVERY_METHOD' => $this->extractor->getDeliveryMethod($shipment),
			'DELIVERY_SYSTEM_LOGO' => $this->extractor->getDeliverySystemLogo($shipment),
		];

		$rateCalculationResult = $this->calculator->calculateRate($shipment);
		if ($rateCalculationResult->isSuccess())
		{
			$fields['EXPECTED_PRICE_DELIVERY'] = SaleFormatCurrency(
				$rateCalculationResult->getDeliveryPrice(),
				$shipment->getOrder()->getCurrency()
			);
		}

		DeliveryController::getInstance()->createTaxiCallHistoryMessage(
			$shipment->getId(),
			[
				'AUTHOR_ID' => $this->extractor->getResponsibleUserId($shipment),
				'SETTINGS' => ['FIELDS' => $fields],
				'BINDINGS' => $this->bindingsMaker->makeByShipment($shipment)
			]
		);
	}

	/**
	 * @param Shipment $shipment
	 * @param CreationRequestResult $creationRequestResult
	 */
	private function updateActivity(Shipment $shipment, CreationRequestResult $creationRequestResult)
	{
		$this->activityManager->updateActivity(
			$shipment->getId(),
			[
				'REQUEST_ID' => $creationRequestResult->getRequestId(),
				'STATUS' => $creationRequestResult->getStatus(),
				'REQUEST_CANCELLATION_AVAILABLE' => true,
			]
		);
	}
}
