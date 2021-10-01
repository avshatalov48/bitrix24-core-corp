<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity;
use Bitrix\Crm\Order\BindingsMaker\ActivityBindingsMaker;
use Bitrix\Crm\Order\Shipment;
use Bitrix\Crm\Timeline\DeliveryController;
use Bitrix\Location\Entity\Address\Converter\StringConverter;
use Bitrix\Location\Entity\Format\TemplateType;
use Bitrix\Location\Service\FormatService;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\Requests;
use Bitrix\Sale\Delivery\Requests\RequestResult;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Repository\ShipmentRepository;
use Bitrix\Location\Entity\Address;

Loc::loadMessages(__FILE__);

/**
 * Class Delivery
 * @package Bitrix\Crm\Activity\Provider
 */
class Delivery extends Activity\Provider\Base
{
	private const PROVIDER_TYPE_DEFAULT = 'DELIVERY';

	/**
	 * @inheritdoc
	 */
	public static function getId()
	{
		return 'CRM_DELIVERY';
	}

	/**
	 * @inheritdoc
	 */
	public static function getTypeId(array $activity)
	{
		return self::PROVIDER_TYPE_DEFAULT;
	}

	/**
	 * @inheritdoc
	 */
	public static function getTypes()
	{
		return [
			[
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_DELIVERY_TYPE_DEFAULT_NAME'),
				'PROVIDER_ID' => self::getId(),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_DEFAULT
			]
		];
	}

	/**
	 * @inheritdoc
	 */
	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_DELIVERY_NAME');
	}

	/**
	 * @inheritdoc
	 */
	public static function getTypeName($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_DELIVERY_TYPE_DEFAULT_NAME');
	}

	/**
	 * @param array $activity Activity data.
	 * @return array Fields.
	 */
	public static function getFieldsForEdit(array $activity)
	{
		return array(
			array(
				'LABEL' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_DELIVERY_ACTIVITY_NAME_LABEL'),
				'TYPE' => 'SUBJECT',
				'VALUE' => $activity['SUBJECT']
			)
		);
	}

	/**
	 * @inheritdoc
	 */
	public static function getCustomViewLink(array $activityFields): ?string
	{
		if ($activityFields['OWNER_ID'] == \CCrmOwnerType::Deal)
		{
			return parent::getCustomViewLink($activityFields);
		}

		return \CComponentEngine::MakePathFromTemplate(
			CrmCheckPath('PATH_TO_DEAL_DETAILS', '', ''),
			['deal_id' => $activityFields['OWNER_ID']]
		);
	}

	/**
	 * @param Shipment $shipment
	 * @return int|null
	 */
	public static function addActivity(Shipment $shipment): ?int
	{
		$authorId = $shipment->getField('RESPONSIBLE_ID')
			? (int)$shipment->getField('RESPONSIBLE_ID')
			: (int)$shipment->getField('EMP_RESPONSIBLE_ID');

		$typeId = self::PROVIDER_TYPE_DEFAULT;

		$fields = [
			'TYPE_ID' => \CCrmActivityType::Provider,
			'PROVIDER_ID' => 'CRM_DELIVERY',
			'PROVIDER_TYPE_ID' => $typeId,
			'SUBJECT' => self::getActivitySubject($shipment, $typeId),
			'IS_HANDLEABLE' => 'Y',
			'COMPLETED' => 'N',
			'STATUS' => \CCrmActivityStatus::Waiting,
			'RESPONSIBLE_ID' => $authorId,
			'PRIORITY' => \CCrmActivityPriority::Medium,
			'AUTHOR_ID' => $authorId,
			'BINDINGS' => ActivityBindingsMaker::makeByShipment($shipment),
			'SETTINGS' => [
				'FIELDS' => [
					'SHIPMENT_ID' => $shipment->getId(),
				]
			],
		];

		$activityId = (int)\CCrmActivity::add($fields, false);
		if ($activityId)
		{
			$deliveryService = $shipment->getDelivery();
			if ($deliveryService)
			{
				$messageFields = [
					'TITLE' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_DELIVERY_DELIVERY_CALCULATION_TITLE'),
				];
				$rateCalculationResult = $deliveryService->calculate($shipment);
				if ($rateCalculationResult->isSuccess())
				{
					$messageFields['DESCRIPTION'] = sprintf(
						'%s: %s',
						Loc::getMessage('CRM_ACTIVITY_PROVIDER_DELIVERY_CALCULATION_RECEIVED_SUCCESSFULLY'),
						SaleFormatCurrency(
							$rateCalculationResult->getDeliveryPrice(),
							$shipment->getOrder()->getCurrency()
						)
					);
				}
				else
				{
					$messageFields['STATUS'] = Loc::getMessage('CRM_ACTIVITY_PROVIDER_DELIVERY_CALCULATION_FAILURE_STATUS');
					$messageFields['DESCRIPTION'] = Loc::getMessage('CRM_ACTIVITY_PROVIDER_DELIVERY_CALCULATION_FAILED');
					$messageFields['STATUS_SEMANTIC'] = DeliveryController::MESSAGE_STATUS_SEMANTIC_ERROR;
				}

				DeliveryController::getInstance()->createShipmentDeliveryCalculationMessage(
					$messageFields,
					$shipment
				);
			}
		}

		return $activityId ?? null;
	}

	/**
	 * @param Shipment $shipment
	 * @param string $typeId
	 * @return string
	 */
	private static function getActivitySubject(Shipment $shipment, string $typeId): string
	{
		$result = (string)self::getTypeName($typeId);

		$deliveryServiceName = null;
		$deliveryService = $shipment->getDelivery();
		if ($deliveryService)
		{
			if ($deliveryService->getParentService())
			{
				$deliveryServiceName = sprintf(
					'%s (%s)',
					$deliveryService->getParentService()->getName(),
					$deliveryService->getName()

				);
			}
			else
			{
				$deliveryServiceName = $deliveryService->getName();
			}
		}

		return $deliveryServiceName
			? sprintf('%s: %s', $result, $deliveryServiceName)
			: $result;
	}

	/**
	 * @param array $filter
	 * @return array|null
	 */
	private static function getActivity(array $filter = []): ?array
	{
		$baseFilter = [
			'CHECK_PERMISSIONS' => 'N',
			'TYPE_ID' => \CCrmActivityType::Provider,
			'PROVIDER_ID' => self::getId(),
		];

		$activity = \CCrmActivity::getlist([], array_merge($filter, $baseFilter))->fetch();

		return is_array($activity) ? $activity : null;
	}

	// region Listeners

	/**
	 * @param Event $event
	 */
	public static function onDeliveryRequestCreated(Event $event): void
	{
		/** @var Requests\Result $result */
		$result = $event->getParameter('RESULT');

		/** @var array $additional */
		$additional = $event->getParameter('ADDITIONAL');

		$requestResults = $result->getRequestResults();
		if (!$result->isSuccess() || empty($requestResults) || count($requestResults) > 1)
		{
			return;
		}

		/** @var RequestResult $requestResult */
		$requestResult = $requestResults[0];

		if (
			isset($additional['ACTIVITY_ID'])
			&& ($activity = self::getActivity(['ID' => (int)$additional['ACTIVITY_ID']]))
		)
		{
			\CCrmActivity::update(
				$activity['ID'],
				['ASSOCIATED_ENTITY_ID' => $requestResult->getInternalId()],
				false
			);
		}
	}

	/**
	 * @param Event $event
	 */
	public static function onDeliveryRequestDeleted(Event $event): void
	{
		/** @var int $requestId */
		$requestId = $event->getParameter('REQUEST_ID');

		/** @var Requests\Result $result */
		$result = $event->getParameter('RESULT');

		if (!$result->isSuccess())
		{
			return;
		}

		$activity = self::getActivity(['ASSOCIATED_ENTITY_ID' => $requestId]);
		if (!$activity)
		{
			return;
		}

		\CCrmActivity::update(
			$activity['ID'],
			['ASSOCIATED_ENTITY_ID' => null],
			false
		);
	}

	/**
	 * @param Event $event
	 */
	public static function onDeliveryRequestUpdated(Event $event): void
	{
		/** @var int $requestId */
		$requestId = $event->getParameter('REQUEST_ID');

		/** @var array $fields */
		$fields = $event->getParameter('FIELDS');

		/** @var Requests\Result $result */
		$result = $event->getParameter('RESULT');

		if (!$result->isSuccess())
		{
			return;
		}

		$activity = self::getActivity(['ASSOCIATED_ENTITY_ID' => $requestId]);
		if (!$activity)
		{
			return;
		}

		if (isset($fields['STATUS']) && $fields['STATUS'] === Requests\Manager::STATUS_PROCESSED)
		{
			\CCrmActivity::update(
				$activity['ID'],
				['COMPLETED' => 'Y'],
				false
			);
		}
	}

	// endregion

	/**
	 * @param int $activityId
	 * @return array
	 */
	public static function getDeliveryInfo(int $activityId): array
	{
		$result = [
			'DELIVERY_REQUEST' => null,
			'DELIVERY_SERVICE' => null,
			'SHIPMENTS' => [],
			'MISCELLANEOUS' => [
				'CAN_USE_TELEPHONY' => (
					Loader::includeModule('voximplant')
					&& \Bitrix\Voximplant\Security\Helper::canCurrentUserPerformCalls()
				),
			],
		];

		$activity = self::getActivity(['ID' => $activityId]);
		if (!$activity)
		{
			return $result;
		}

		$shipmentId = $activity['SETTINGS']['FIELDS']['SHIPMENT_ID'] ?? [];
		$shipmentIds = is_array($shipmentId) ? array_map('intval', $shipmentId) : [(int)$shipmentId];
		$deliveryRequestId = $activity['ASSOCIATED_ENTITY_ID'];

		$deliveryServiceId = null;
		if ($deliveryRequestId)
		{
			$result['DELIVERY_REQUEST'] = Requests\RequestTable::getById($deliveryRequestId)->fetch();
			$deliveryServiceId = $result['DELIVERY_REQUEST'] ? $result['DELIVERY_REQUEST']['DELIVERY_ID'] : null;
		}

		/** @var Shipment[] $shipments */
		$shipments = ShipmentRepository::getInstance()->getByIds($shipmentIds);
		foreach ($shipments as $shipment)
		{
			if (is_null($deliveryServiceId) && $shipment->getDelivery())
			{
				$deliveryServiceId = $shipment->getDelivery()->getId();
			}

			$currency = $shipment->getOrder()->getCurrency();
			$priceDelivery = $shipment->getField('PRICE_DELIVERY');
			$basePriceDelivery = is_null($shipment->getField('BASE_PRICE_DELIVERY'))
				? $shipment->getField('PRICE_DELIVERY')
				: $shipment->getField('BASE_PRICE_DELIVERY');


			$addressFrom = $shipment->getPropertyCollection()->getAddressFrom();
			$addressTo = $shipment->getPropertyCollection()->getAddressTo();

			$result['SHIPMENTS'][] = [
				'ID' => $shipment->getId(),
				'PRICE_DELIVERY' => $priceDelivery,
				'BASE_PRICE_DELIVERY' => $basePriceDelivery,
				'PRICE_DELIVERY_FORMATTED' => SaleFormatCurrency($priceDelivery, $currency),
				'BASE_PRICE_DELIVERY_FORMATTED' => SaleFormatCurrency($basePriceDelivery, $currency),
				'CURRENCY' => $currency,
				'ADDRESS_FROM_FORMATTED' => $addressFrom ? self::formatAddress($addressFrom->getValue()) : '',
				'ADDRESS_TO_FORMATTED' => $addressTo ? self::formatAddress($addressTo->getValue()) : '',
			];

			if (
				$deliveryServiceId
				&& $deliveryService = Manager::getObjectById($deliveryServiceId)
			)
			{
				$deliveryRequestHandler = $deliveryService->getDeliveryRequestHandler();

				$result['DELIVERY_SERVICE'] = [
					'ID' => $deliveryService->getId(),
					'IDS' => $deliveryService->getParentService()
						? [$deliveryService->getId(), $deliveryService->getParentService()->getId()]
						: [$deliveryService->getId()],
					'PARENT_ID' => $deliveryService->getParentService()
						? $deliveryService->getParentService()->getId()
						: null,
					'IS_PROFILE' => $deliveryService->getParentService() ? true : false,
					'NAME' => $deliveryService->getName(),
					'PARENT_NAME' => $deliveryService->getParentService()
						? $deliveryService->getParentService()->getName()
						: null,
					'LOGO' => $deliveryService->getLogotipPath(),
					'PARENT_LOGO' => $deliveryService->getParentService()
						? $deliveryService->getParentService()->getLogotipPath()
						: null,
				];

				if (
					!is_null($deliveryRequestHandler)
					&& isset($deliveryRequestHandler->getActions(null)[$deliveryRequestHandler::CANCEL_ACTION_CODE])
				)
				{
					$result['DELIVERY_SERVICE'] = array_merge(
						$result['DELIVERY_SERVICE'],
						[
							'IS_CANCELLABLE' => true,
							'CANCEL_ACTION_CODE' => $deliveryRequestHandler->getCancelActionCode(),
							'CANCEL_ACTION_NAME' => $deliveryRequestHandler->getCancelActionName(),
						]
					);
				}
			}
		}

		return $result;
	}

	/**
	 * @param array $address
	 * @return string
	 */
	private static function formatAddress(?array $address): string
	{
		if (!$address)
		{
			return '';
		}

		return StringConverter::convertToStringTemplate(
			Address::fromArray($address),
			FormatService::getInstance()->findDefault(LANGUAGE_ID)->getTemplate(TemplateType::AUTOCOMPLETE),
			StringConverter::STRATEGY_TYPE_TEMPLATE,
			StringConverter::CONTENT_TYPE_TEXT
		);
	}
}
