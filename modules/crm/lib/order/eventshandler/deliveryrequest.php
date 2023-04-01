<?php

namespace Bitrix\Crm\Order\EventsHandler;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sale\Delivery\Requests\Manager;
use Bitrix\Sale\Delivery\Requests\Message;
use Bitrix\Sale\Repository\ShipmentRepository;
use Bitrix\Crm\Activity;

/**
 * Class DeliveryRequest
 * @package Bitrix\Crm\Order\EventsHandler
 * @internal
 */
final class DeliveryRequest
{
	/**
	 * @param Main\Event $event
	 */
	public static function OnMessageReceived(Main\Event $event)
	{
		/** @var int $shipmentId */
		$shipmentId = $event->getParameter('SHIPMENT_ID');

		/** @var Message\Message $message */
		$message = $event->getParameter('MESSAGE');

		/** @var string $addressee */
		$addressee = $event->getParameter('ADDRESSEE');

		if (
			!$message instanceof Message\Message
			|| $addressee !== Manager::MESSAGE_RECIPIENT_ADDRESSEE
		)
		{
			return;
		}

		$shipment = ShipmentRepository::getInstance()->getById($shipmentId);
		if (!$shipment instanceof Crm\Order\Shipment)
		{
			return;
		}

		$order = $shipment->getOrder();

		$entityCommunication = $order->getContactCompanyCollection()->getEntityCommunication();
		$phoneTo = $order->getContactCompanyCollection()->getEntityCommunicationPhone();
		if (!$entityCommunication || !$phoneTo)
		{
			return;
		}

		$authorId = $shipment->getField('RESPONSIBLE_ID')
			? (int)$shipment->getField('RESPONSIBLE_ID')
			: (int)$shipment->getField('EMP_RESPONSIBLE_ID');

		$sendersOptions = [
			Crm\Integration\SmsManager::getSenderCode() => [
				'ACTIVITY_PROVIDER_TYPE_ID' => Crm\Activity\Provider\Sms::PROVIDER_TYPE_SALESCENTER_DELIVERY,
				'MESSAGE_BODY' => $message->getBodyForHtml(),
			]
		];
		if ($message->getType() === Message\Message::TYPE_SHIPMENT_PICKUPED)
		{
			$sendersOptions[Crm\Integration\NotificationsManager::getSenderCode()] = [
				'ACTIVITY_PROVIDER_TYPE_ID' => Activity\Provider\Notification::PROVIDER_TYPE_NOTIFICATION,
				'TEMPLATE_CODE' => 'ORDER_IN_TRANSIT',
				'PLACEHOLDERS' => [
					'NAME' => $entityCommunication->getCustomerName(),
					'ORDER' => $order->getField('ACCOUNT_NUMBER'),
				]
			];
		}

		Crm\MessageSender\MessageSender::send(
			$sendersOptions,
			[
				'COMMON_OPTIONS' => [
					'PHONE_NUMBER' => $phoneTo,
					'USER_ID' => $authorId,
					'ADDITIONAL_FIELDS' => [
						'ENTITY_TYPE' => $entityCommunication::getEntityTypeName(),
						'ENTITY_TYPE_ID' => $entityCommunication::getEntityType(),
						'ENTITY_ID' => $entityCommunication->getField('ENTITY_ID'),
						'BINDINGS' => Crm\Order\BindingsMaker\ActivityBindingsMaker::makeByShipment(
							$shipment,
							[
								'extraBindings' => [
									[
										'TYPE_ID' => $entityCommunication::getEntityType(),
										'ID' => $entityCommunication->getField('ENTITY_ID'),
									],
								],
							]
						),
					]
				]
			]
		);
	}
}
