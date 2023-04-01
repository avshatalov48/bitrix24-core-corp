<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Order\BindingsMaker\TimelineBindingsMaker;
use Bitrix\Crm\Order\Shipment;
use Bitrix\Main\Event;
use Bitrix\Sale\Delivery\Services\Base;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Delivery\Requests;
use Bitrix\Sale\Delivery\Requests\Message;
use Bitrix\Crm\ItemIdentifier;

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
	public function createDeliveryRequestMessage(
		array $messageData,
		int $deliveryRequestId,
		?int $shipmentId = null
	): ?int
	{
		$deliveryRequest = Requests\RequestTable::getById($deliveryRequestId)->fetch();
		if (
			!$deliveryRequest
			|| !($deliveryService = Manager::getObjectById($deliveryRequest['DELIVERY_ID']))
		)
		{
			return null;
		}

		$bindings = TimelineBindingsMaker::makeByDeliveryRequestId($deliveryRequest['ID']);

		$id = DeliveryEntry::create([
			'ENTITY_TYPE_ID' => \CCrmOwnerType::DeliveryRequest,
			'ENTITY_ID' => $deliveryRequest['ID'],
			'AUTHOR_ID' => (isset($deliveryRequest['CREATED_BY']) && (int)$deliveryRequest['CREATED_BY'] > 0)
				? (int)$deliveryRequest['CREATED_BY']
				: null,
			'SETTINGS' => [
				'FIELDS' => [
					'MESSAGE_DATA' => $messageData,
					'DELIVERY_SERVICE' => $this->makeDeliveryService($deliveryService),
				],
			],
			'BINDINGS' => $bindings,
		]);
		if (!$id)
		{
			return null;
		}

		$this->sendPullEventOnAddForBindings($id, $bindings);

		return $id;
	}

	/**
	 * @param array $messageData
	 * @param Shipment $shipment
	 * @return int|null
	 */
	public function createShipmentMessage(array $messageData, Shipment $shipment): ?int
	{
		$bindings = TimelineBindingsMaker::makeByShipment($shipment);

		$id = DeliveryEntry::create([
			'ENTITY_TYPE_ID' => \CCrmOwnerType::OrderShipment,
			'ENTITY_ID' => $shipment->getId(),
			'AUTHOR_ID' => $this->getShipmentResponsibleId($shipment),
			'SETTINGS' => [
				'FIELDS' => [
					'MESSAGE_DATA' => $messageData,
					'DELIVERY_SERVICE' =>
						$shipment->getDelivery()
							? $this->makeDeliveryService($shipment->getDelivery())
							: []
					,
				],
			],
			'BINDINGS' => $bindings,
		]);
		if (!$id)
		{
			return null;
		}

		$this->sendPullEventOnAddForBindings($id, $bindings);

		return $id;
	}

	/**
	 * @param int $id
	 * @param array $bindings
	 * @return void
	 */
	private function sendPullEventOnAddForBindings(int $id, array $bindings): void
	{
		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new ItemIdentifier(
					$binding['ENTITY_TYPE_ID'],
					$binding['ENTITY_ID']
				),
				$id
			);
		}
	}

	/**
	 * @param Message\Message $message
	 * @return array
	 */
	private static function makeMessageDataByObject(Message\Message $message): array
	{
		$result = [
			'TITLE' => $message->getSubject() ?? '',
			'DESCRIPTION' => $message->getBody(),
			'MONEY_VALUES' => $message->getMoneyValues(),
			'CURRENCY' => $message->getCurrency(),
			'DATE_VALUES' => $message->getDateValues(),
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
	}

	/**
	 * @param Shipment $shipment
	 * @return int|null
	 */
	private function getShipmentResponsibleId(Shipment $shipment): ?int
	{
		$responsibleId =
			$shipment->getField('RESPONSIBLE_ID')
				? (int)$shipment->getField('RESPONSIBLE_ID')
				: (int)$shipment->getField('EMP_RESPONSIBLE_ID')
		;

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
}
