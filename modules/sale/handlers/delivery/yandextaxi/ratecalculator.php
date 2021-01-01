<?php

namespace Sale\Handlers\Delivery\YandexTaxi;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Shipment;
use Sale\Handlers\Delivery\YandexTaxi\Api\Api;
use Sale\Handlers\Delivery\YandexTaxi\Api\RequestEntity\Address;
use Sale\Handlers\Delivery\YandexTaxi\Api\RequestEntity\Estimation;
use Sale\Handlers\Delivery\YandexTaxi\Api\RequestEntity\TariffsOptions;
use Sale\Handlers\Delivery\YandexTaxi\Api\RequestEntity\TransportClassification;
use Sale\Handlers\Delivery\YandexTaxi\ClaimBuilder\ClaimBuilder;

/**
 * Class RateCalculator
 * @package Sale\Handlers\Delivery\YandexTaxi
 * @internal
 */
final class RateCalculator
{
	private const ERROR_CODE = 'DELIVERY_CALCULATION';

	/** @var Api */
	protected $api;

	/** @var ClaimBuilder */
	protected $claimBuilder;

	/**
	 * RateCalculator constructor.
	 * @param Api $api
	 * @param ClaimBuilder $claimBuilder
	 */
	public function __construct(Api $api, ClaimBuilder $claimBuilder)
	{
		$this->api = $api;
		$this->claimBuilder = $claimBuilder;
	}

	/**
	 * @param Shipment $shipment
	 * @return CalculationResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public function calculateRate(Shipment $shipment): CalculationResult
	{
		$result = new CalculationResult();

		$addressFromResult = $this->claimBuilder->buildAddressFrom($shipment);
		if (!$addressFromResult->isSuccess())
		{
			return $result->addErrors(
				$this->getFormattedErrors($addressFromResult->getErrors())
			);
		}
		/** @var Address $addressFrom */
		$addressFrom = $addressFromResult->getData()['ADDRESS'];

		$addressToResult = $this->claimBuilder->buildAddressTo($shipment);
		if (!$addressToResult->isSuccess())
		{
			return $result->addErrors(
				$this->getFormattedErrors($addressToResult->getErrors())
			);
		}
		/** @var Address $addressFrom */
		$addressTo = $addressToResult->getData()['ADDRESS'];

		$vehicleType = $this->claimBuilder->getVehicleType($shipment);
		if (!$vehicleType)
		{
			return $result->addError(
				new Error(Loc::getMessage('SALE_YANDEX_TAXI_AUTO_CLASS_NOT_SPECIFIED'), static::ERROR_CODE)
			);
		}

		$tariffsResult = $this->api->getTariffs((new TariffsOptions)->setStartPoint($addressFrom->getCoordinates()));
		if (!$tariffsResult->isSuccess() || !in_array($vehicleType, $tariffsResult->getTariffs()))
		{
			return $result->addError(
				new Error(Loc::getMessage('SALE_YANDEX_TAXI_TARIFF_NOT_SUPPORTED'), static::ERROR_CODE)
			);
		}

		$shippingItemCollection = $this->claimBuilder->getShippingItemCollection($shipment);
		$validationResult = $shippingItemCollection->isValid();
		if (!$validationResult->isSuccess())
		{
			return $result->addErrors(
				$this->getFormattedErrors($validationResult->getErrors())
			);
		}

		$estimationRequest = (new Estimation())
			->addRoutePoint($addressFrom)
			->addRoutePoint($addressTo)
			->setRequirements((new TransportClassification())->setTaxiClass($vehicleType));

		foreach ($shippingItemCollection as $shippingItem)
		{
			$estimationRequest->addItem($shippingItem);
		}

		if (!$this->claimBuilder->isDoorDeliveryRequired($shipment))
		{
			$estimationRequest->setSkipDoorToDoor(true);
		}

		$priceResult = $this->api->checkPrice($estimationRequest);
		if (!$priceResult->isSuccess())
		{
			return $result->addError(
				new Error(Loc::getMessage('SALE_YANDEX_TAXI_RATE_CALCULATE_ERROR'), static::ERROR_CODE)
			);
		}

		if ($priceResult->getCurrency() !== $shipment->getCollection()->getOrder()->getCurrency())
		{
			return $result->addError(
				new Error(
					Loc::getMessage('SALE_YANDEX_TAXI_RATE_CALCULATE_CURRENCY_MISMATCH_ERROR'),
					static::ERROR_CODE
				)
			);
		}

		$result->setDeliveryPrice($priceResult->getPrice());

		return $result;
	}

	/**
	 * @param array $errors
	 * @return array
	 */
	private function getFormattedErrors(array $errors)
	{
		$result = [];

		foreach ($errors as $error)
		{
			$result[] = new Error($error->getMessage(), static::ERROR_CODE);
		}

		return $result;
	}
}
