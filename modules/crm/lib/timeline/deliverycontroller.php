<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Order\BindingsMaker\TimelineBindingsMaker;
use Bitrix\Crm\Order\Shipment;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Location\Entity\Address;
use Bitrix\Location\Entity\Address\Converter\StringConverter;
use Bitrix\Location\Entity\Format\TemplateType;
use Bitrix\Location\Service\FormatService;
use Bitrix\Main;
use Bitrix\Main\Event;
use Bitrix\Sale\Delivery\Services\Base;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Delivery\Requests;
use Bitrix\Sale\Delivery\Requests\Message;

/**
 * Class DeliveryController
 * @package Bitrix\Crm\Timeline
 */
class DeliveryController extends EntityController
{
	public const MESSAGE_STATUS_SEMANTIC_SUCCESS = 'success';
	public const MESSAGE_STATUS_SEMANTIC_ERROR = 'error';
	public const MESSAGE_STATUS_SEMANTIC_PROCESS = 'process';

	/** @var DeliveryController|null */
	protected static $instance = null;

	/**
	 * @return DeliveryController
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new DeliveryController();
		}
		return self::$instance;
	}

	/**
	 * @param Event $event
	 */
	public static function onMessageReceived(Event $event)
	{
		/** @var int $requestId */
		$requestId = $event->getParameter('REQUEST_ID');

		/** @var int $shipmentId */
		$shipmentId = $event->getParameter('SHIPMENT_ID');

		/** @var Message\Message $message */
		$message = $event->getParameter('MESSAGE');

		/** @var string $addressee */
		$addressee = $event->getParameter('ADDRESSEE');

		if (!$message instanceof Message\Message || $addressee !== Requests\Manager::MESSAGE_MANAGER_ADDRESSEE)
		{
			return;
		}

		self::getInstance()->createDeliveryRequestMessage(
			self::makeMessageDataByObject($message),
			$requestId,
			$shipmentId
		);
	}

	/**
	 * @param array $messageData
	 * @param int $deliveryRequestId
	 * @param int|null $shipmentId
	 * @return int|null
	 */
	public function createDeliveryRequestMessage(array $messageData, int $deliveryRequestId, ?int $shipmentId = null): ?int
	{
		$deliveryRequest = Requests\RequestTable::getById($deliveryRequestId)->fetch();
		if (
			!$deliveryRequest
			|| !($deliveryService = Manager::getObjectById($deliveryRequest['DELIVERY_ID']))
		)
		{
			return null;
		}

		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::DELIVERY,
			'TYPE_CATEGORY_ID' => DeliveryCategoryType::MESSAGE,
			'CREATED' => new Main\Type\DateTime(),
			'AUTHOR_ID' => (isset($deliveryRequest['CREATED_BY']) && (int)$deliveryRequest['CREATED_BY'] > 0)
				? (int)$deliveryRequest['CREATED_BY']
				: \CCrmSecurityHelper::GetCurrentUserID(),
			'SETTINGS' => [
				'FIELDS' => [
					'MESSAGE_DATA' => $messageData,
					'DELIVERY_SERVICE' => $this->makeDeliveryService($deliveryService),
				],
			],
			'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::DeliveryRequest,
			'ASSOCIATED_ENTITY_ID' => $deliveryRequest['ID'],
		]);
		if (!$result->isSuccess())
		{
			return null;
		}

		$bindings = TimelineBindingsMaker::makeByDeliveryRequestId($deliveryRequest['ID']);
		TimelineEntry::registerBindings($result->getId(), $bindings);

		foreach ($bindings as $binding)
		{
			$tag = TimelineEntry::prepareEntityPushTag(
				$binding['ENTITY_TYPE_ID'],
				$binding['ENTITY_ID']
			);
			self::pushHistoryEntry($result->getId(), $tag, 'timeline_activity_add');
		}

		return (int)$result->getId();
	}

	/**
	 * @param array $messageData
	 * @param Shipment $shipment
	 * @return int|null
	 */
	public function createShipmentDeliveryCalculationMessage(array $messageData, Shipment $shipment): ?int
	{
		$shipmentResponsibleId = $this->getShipmentResponsibleId($shipment);

		$addressFrom = $shipment->getPropertyCollection()->getAddressFrom();
		$addressTo = $shipment->getPropertyCollection()->getAddressTo();

		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::DELIVERY,
			'TYPE_CATEGORY_ID' => DeliveryCategoryType::DELIVERY_CALCULATION,
			'CREATED' => new Main\Type\DateTime(),
			'AUTHOR_ID' => $shipmentResponsibleId ?: \CCrmSecurityHelper::GetCurrentUserID(),
			'SETTINGS' => [
				'FIELDS' => [
					'MESSAGE_DATA' => $messageData,
					'DELIVERY_SERVICE' => $this->makeDeliveryService($shipment->getDelivery()),
					'ADDRESS_FROM_FORMATTED' => $addressFrom ? self::formatAddress($addressFrom->getValue()) : '',
					'ADDRESS_TO_FORMATTED' => $addressTo ? self::formatAddress($addressTo->getValue()) : '',
				],
			],
			'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::OrderShipment,
			'ASSOCIATED_ENTITY_ID' => $shipment->getId(),
		]);
		if (!$result->isSuccess())
		{
			return null;
		}

		$bindings = TimelineBindingsMaker::makeByShipment($shipment);
		TimelineEntry::registerBindings($result->getId(), $bindings);

		foreach ($bindings as $binding)
		{
			$tag = TimelineEntry::prepareEntityPushTag(
				$binding['ENTITY_TYPE_ID'],
				$binding['ENTITY_ID']
			);
			self::pushHistoryEntry($result->getId(), $tag, 'timeline_activity_add');
		}

		return (int)$result->getId();
	}

	/**
	 * @param Message\Message $message
	 * @return array
	 */
	private static function makeMessageDataByObject(Message\Message $message): array
	{
		$result = [
			'TITLE' => $message->getSubject() ?? '',
			'DESCRIPTION' => $message->getBodyForHtml(),
		];
		$messageStatus = $message->getStatus();

		if (
			$messageStatus
			&& in_array($messageStatus->getSemantic(), self::getStatusSemantics(), true)
		)
		{
			$result['STATUS'] = $messageStatus->getMessage();
			$result['STATUS_SEMANTIC'] = $messageStatus->getSemantic();
		}

		return $result;
	}

	/**
	 * @param Base $deliveryService
	 * @return array
	 */
	private function makeDeliveryService(Base $deliveryService): array
	{
		return [
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
	}

	/**
	 * @param Shipment $shipment
	 * @return int|null
	 */
	private function getShipmentResponsibleId(Shipment $shipment): ?int
	{
		$responsibleId = $shipment->getField('RESPONSIBLE_ID')
			? (int)$shipment->getField('RESPONSIBLE_ID')
			: (int)$shipment->getField('EMP_RESPONSIBLE_ID');

		return $responsibleId ?: null;
	}

	/**
	 * @inheritdoc
	 */
	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$data['FIELDS'] = $data['SETTINGS']['FIELDS'];

		unset($data['SETTINGS']);

		return parent::prepareHistoryDataModel($data, $options);
	}

	/**
	 * @return string[]
	 */
	private static function getStatusSemantics(): array
	{
		return [
			self::MESSAGE_STATUS_SEMANTIC_PROCESS,
			self::MESSAGE_STATUS_SEMANTIC_ERROR,
			self::MESSAGE_STATUS_SEMANTIC_SUCCESS,
		];
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
