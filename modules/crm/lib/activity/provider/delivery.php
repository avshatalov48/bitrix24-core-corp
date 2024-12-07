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
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Delivery\Requests;
use Bitrix\Sale\Delivery\Requests\RequestResult;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Repository\ShipmentRepository;
use Bitrix\Location\Entity\Address;
use Bitrix\Crm\ActivityBindingTable;

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
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_DEFAULT,
			],
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
		return [
			[
				'LABEL' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_DELIVERY_ACTIVITY_NAME_LABEL'),
				'TYPE' => 'SUBJECT',
				'VALUE' => $activity['SUBJECT'],
			],
		];
	}

	/**
	 * @inheritdoc
	 */
	public static function getCustomViewLink(array $activityFields): ?string
	{
		$bindings = \CCrmActivity::GetBindings((int)$activityFields['ID']);
		foreach ($bindings as $binding)
		{
			if ((int)$binding['OWNER_TYPE_ID'] === \CCrmOwnerType::Deal)
			{
				return \CComponentEngine::MakePathFromTemplate(
					CrmCheckPath('PATH_TO_DEAL_DETAILS', '', ''),
					['deal_id' => $binding['OWNER_ID']]
				);
			}
		}

		return parent::getCustomViewLink($activityFields);
	}

	/**
	 * @param Shipment $shipment
	 * @return int|null
	 */
	public static function addActivity(Shipment $shipment): ?int
	{
		$authorId =
			$shipment->getField('RESPONSIBLE_ID')
				? (int)$shipment->getField('RESPONSIBLE_ID')
				: (int)$shipment->getField('EMP_RESPONSIBLE_ID')
		;
		$typeId = self::PROVIDER_TYPE_DEFAULT;
		$deadlineTime = (new DateTime())->add('+1 day')->setTime(19, 0, 0);

		$fields = [
			'TYPE_ID' => \CCrmActivityType::Provider,
			'PROVIDER_ID' => 'CRM_DELIVERY',
			'PROVIDER_TYPE_ID' => $typeId,
			'START_TIME' => $deadlineTime,
			'END_TIME' => $deadlineTime,
			'DEADLINE' => $deadlineTime,
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
				],
			],
		];

		$activityId = (int)\CCrmActivity::add($fields, false);
		if ($activityId)
		{
			$deliveryService = $shipment->getDelivery();
			if ($deliveryService)
			{
                $deliveryServiceName = $deliveryService->getName();
                if ($deliveryService->getParentService())
                {
                    $deliveryServiceName = implode(
                        ', ',
                        [
                            (string)$deliveryService->getParentService()->getName(),
                            (string)$deliveryService->getName(),
                        ],
                    );
                }

				DeliveryController::getInstance()->createShipmentMessage(
					[
						'TITLE' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_DELIVERY_DELIVERY_CREATED'),
						'DESCRIPTION' => $deliveryServiceName . ' ' . '#PRICE#',
						'CURRENCY' => $shipment->getCurrency(),
						'MONEY_VALUES' => [
							'#PRICE#' => $shipment->getPrice(),
						],
					],
					$shipment
				);
			}

			AddEventToStatFile(
				'sale',
				'deliveryActivityCreation',
				$activityId,
				$deliveryService->getServiceCode(),
				'delivery_service_code'
			);
		}

		return $activityId ?? null;
	}

	/**
	 * @param string $action Action ADD or UPDATE.
	 * @param array $fields Activity fields.
	 * @param int $id Activity ID.
	 * @param null|array $params Additional parameters.
	 * @return Result Check fields result.
	 */
	public static function checkFields($action, &$fields, $id, $params = null)
	{
		$result = new Result();

		if (empty($fields['PROVIDER_TYPE_ID']))
		{
			$fields['PROVIDER_TYPE_ID'] = static::PROVIDER_TYPE_DEFAULT;
		}

		//Only START_TIME can be taken for DEADLINE!
		if ($action === 'UPDATE')
		{
			if (isset($fields['START_TIME']) && $fields['START_TIME'] !== '')
			{
				$fields['DEADLINE'] = $fields['START_TIME'];
			}
			elseif (isset($fields['~START_TIME']) && $fields['~START_TIME'] !== '')
			{
				$fields['~DEADLINE'] = $fields['~START_TIME'];
			}
		}

		return $result;
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

		return
			$deliveryServiceName
			? sprintf('%s: %s', $result, $deliveryServiceName)
			: $result
		;
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

        /** @var RequestResult|null $requestResult */
        $requestResult = null;

		$requestResults = $result->getRequestResults();
		if ($result->isSuccess() && !empty($requestResults))
		{
            $requestResult = $requestResults[0];
		}

		if (
			isset($additional['ACTIVITY_ID'])
			&& ($activity = self::getActivity(['ID' => (int)$additional['ACTIVITY_ID']]))
		)
		{
			\CCrmActivity::update(
				$activity['ID'],
				[
                    'ASSOCIATED_ENTITY_ID' => $requestResult ? $requestResult->getInternalId() : null,
                ],
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
			$deliveryRequest = Requests\RequestTable::getById($deliveryRequestId)->fetch();
			if ($deliveryRequest)
			{
				$deliveryRequest['IS_PROCESSED'] =
					(int)$deliveryRequest['STATUS'] === Requests\Manager::STATUS_PROCESSED
						? 'Y'
						: 'N'
				;
			}
			$result['DELIVERY_REQUEST'] = $deliveryRequest;

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

			$addressFrom = $shipment->getPropertyCollection()->getAddressFrom();
			$addressFromValue = $addressFrom ? $addressFrom->getValue() : null;
			$addressTo = $shipment->getPropertyCollection()->getAddressTo();
			$addressToValue = $addressTo ? $addressTo->getValue() : null;

			$result['SHIPMENTS'][] = [
				'ID' => $shipment->getId(),
				'PRICE_DELIVERY' => $shipment->getField('PRICE_DELIVERY'),
				'CURRENCY' => $shipment->getOrder()->getCurrency(),
				'ADDRESS_FROM_FORMATTED' => is_array($addressFromValue) ? self::formatAddress($addressFromValue) : '',
				'ADDRESS_TO_FORMATTED' => is_array($addressToValue) ? self::formatAddress($addressToValue) : '',
			];

			if (
				$deliveryServiceId
				&& $deliveryService = Manager::getObjectById($deliveryServiceId)
			)
			{
				$deliveryRequestHandler = $deliveryService->getDeliveryRequestHandler();

				$result['DELIVERY_SERVICE'] = [
					'ID' => $deliveryService->getId(),
					'IDS' =>
						$deliveryService->getParentService()
							? [$deliveryService->getId(), $deliveryService->getParentService()->getId()]
							: [$deliveryService->getId()]
					,
					'PARENT_ID' =>
						$deliveryService->getParentService()
							? $deliveryService->getParentService()->getId()
							: null
					,
					'IS_PROFILE' => (bool)$deliveryService->getParentService(),
					'NAME' => $deliveryService->getName(),
					'PARENT_NAME' =>
						$deliveryService->getParentService()
							? $deliveryService->getParentService()->getName()
							: null
					,
					'LOGO' => $deliveryService->getLogotipPath(),
					'PARENT_LOGO' =>
						$deliveryService->getParentService()
							? $deliveryService->getParentService()->getLogotipPath()
							: null
					,
				];

				$deliveryRequestActions =
					$deliveryRequestHandler
						? $deliveryRequestHandler->getActions(null)
						: []
				;
				if (
					$deliveryRequestHandler
					&& isset($deliveryRequestActions[$deliveryRequestHandler::CANCEL_ACTION_CODE])
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
	 * @param int $shipmentId
	 */
	public static function onShipmentDeleted(int $shipmentId): void
	{
		$shipmentBindingsList = ActivityBindingTable::getList([
			'filter' => [
				'OWNER_ID' => $shipmentId,
				'OWNER_TYPE_ID' => \CCrmOwnerType::OrderShipment,
			]
		]);
		while ($shipmentBinding = $shipmentBindingsList->fetch())
		{
			ActivityBindingTable::delete((int)$shipmentBinding['ID']);

			$activity = \CCrmActivity::GetByID((int)$shipmentBinding['ACTIVITY_ID'], false);
			if ($activity)
			{
				$shipmentIds =
					isset($activity['SETTINGS']['FIELDS']['SHIPMENT_ID'])
						? (
							is_array($activity['SETTINGS']['FIELDS']['SHIPMENT_ID'])
								? $activity['SETTINGS']['FIELDS']['SHIPMENT_ID']
								: [$activity['SETTINGS']['FIELDS']['SHIPMENT_ID']]
						)
						: []
				;

				$newShipmentIds = array_values(array_diff($shipmentIds, [$shipmentId]));
				if (empty($newShipmentIds))
				{
					\CCrmActivity::Delete((int)$activity['ID'], false);
				}
				else
				{
					$newSettings = $activity['SETTINGS'];
					$newSettings['FIELDS']['SHIPMENT_ID'] = $newShipmentIds;

					\CCrmActivity::update(
						$activity['ID'],
						['SETTINGS' => $newSettings],
						false
					);
				}
			}
		}
	}

	/**
	 * @param array $address
	 * @return string
	 */
	private static function formatAddress(array $address): string
	{
		return StringConverter::convertToStringTemplate(
			Address::fromArray($address),
			FormatService::getInstance()->findDefault(LANGUAGE_ID)->getTemplate(TemplateType::AUTOCOMPLETE),
			StringConverter::STRATEGY_TYPE_TEMPLATE,
			StringConverter::CONTENT_TYPE_TEXT
		);
	}
}
